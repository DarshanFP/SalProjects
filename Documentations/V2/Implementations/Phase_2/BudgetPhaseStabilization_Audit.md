# Budget Phase Stabilization Audit

**Document Type:** Design stabilization checkpoint (Phase 2)  
**Scope:** BudgetController, ProjectBudget model, budget form/read lifecycle  
**Purpose:** Forensic and architectural analysis before Phase 2.4 — no code changes.

---

## 1. Current Behavior: BudgetController

### 1.1 BudgetController store()

**File:** `app/Http/Controllers/Projects/BudgetController.php`  
**Lines:** 24–80

**Flow:**
1. BudgetSyncGuard::canEditBudget($project) — blocks if project approved.
2. StoreBudgetRequest::createFrom($request) → getNormalizedInput() → validate().
3. Reads `$validated['phases']` (array).
4. Iterates `foreach ($phases as $phaseIndex => $phase)`.
5. For each `$phase['budget']` row: clamps `this_phase` and `next_phase` via BoundedNumericService.
6. Creates `ProjectBudget` with `'phase' => $phaseIndex + 1` (1-based).

**Code snippet (lines 57–76):**

```php
foreach ($phases as $phaseIndex => $phase) {
    if (isset($phase['budget']) && is_array($phase['budget'])) {
        foreach ($phase['budget'] as $budget) {
            $thisPhase = $bounded->clamp((float) ($budget['this_phase'] ?? 0), $maxPhase);
            $nextPhase = $bounded->clamp((float) ($budget['next_phase'] ?? 0), $maxPhase);

            ProjectBudget::create([
                'project_id' => $project->project_id,
                'phase' => $phaseIndex + 1,
                'particular' => $budget['particular'] ?? '',
                'rate_quantity' => $budget['rate_quantity'] ?? 0,
                'rate_multiplier' => $budget['rate_multiplier'] ?? 0,
                'rate_duration' => $budget['rate_duration'] ?? 0,
                'rate_increase' => $budget['rate_increase'] ?? 0,
                'this_phase' => $thisPhase,
                'next_phase' => $nextPhase,
            ]);
        }
    }
}
```

**Invocation:** `ProjectController::store()` line 633: `(new BudgetController())->store($request, $project)` — only when `ProjectType::isInstitutional($request->project_type)` is true.

---

### 1.2 BudgetController update()

**File:** `app/Http/Controllers/Projects/BudgetController.php`  
**Lines:** 84–146

**Flow:**
1. BudgetSyncGuard::canEditBudget($project).
2. UpdateBudgetRequest::createFrom($request) → getNormalizedInput() → validate().
3. **Deletes all budgets for project:** `ProjectBudget::where('project_id', $project->project_id)->delete()` (line 113).
4. Recreates from `$validated['phases']` with same logic as store().
5. Calls `$project->load('budgets')` and `BudgetSyncService::syncFromTypeSave($project)`.

**Code snippet (lines 113–135):**

```php
ProjectBudget::where('project_id', $project->project_id)->delete();

$bounded = app(BoundedNumericService::class);
$maxPhase = $bounded->getMaxFor('project_budgets.this_phase');

foreach ($phases as $phaseIndex => $phase) {
    if (isset($phase['budget']) && is_array($phase['budget'])) {
        foreach ($phase['budget'] as $budget) {
            // ... same create logic as store()
            ProjectBudget::create([
                'project_id' => $project->project_id,
                'phase' => $phaseIndex + 1,
                // ...
            ]);
        }
    }
}
```

**Invocation:** `ProjectController::update()` line 1404: `(new BudgetController())->update($request, $project)` — only when `ProjectType::isInstitutional($project->project_type)` is true.

---

### 1.3 BudgetController edit() / show()

**BudgetController does not define `edit()` or `show()`.**

- **Edit:** Project edit view receives `$project` with `budgets` relationship loaded via `ProjectController::edit()`. The edit form uses `$project->budgets` directly in `resources/views/projects/partials/Edit/budget.blade.php`.
- **Show:** Project show loads `$project` with `->with(['budgets', ...])` in `ProjectController::show()` line 789. The Show partial `projects/partials/Show/budget.blade.php` iterates `$project->budgets`.

---

## 2. ProjectBudget Model Usage

### 2.1 Model Definition

**File:** `app/Models/OldProjects/ProjectBudget.php`

**Fillable:** `project_id`, `phase`, `particular`, `rate_quantity`, `rate_multiplier`, `rate_duration`, `rate_increase`, `this_phase`, `next_phase`.

**Relationships:**
- `belongsTo(Project::class, 'project_id', 'project_id')`
- `hasMany(DPAccountDetail::class, 'project_id', 'project_id')`

**Methods:**
- `calculateTotalBudget()` — returns `rate_quantity * rate_multiplier * rate_duration * rate_increase` (line 79).
- `calculateRemainingBalance()` — uses `calculateTotalBudget()` minus `dpAccountDetails()->sum('total_expenses')`.

---

### 2.2 Project budgets Relationship

**File:** `app/Models/OldProjects/Project.php`  
**Lines:** 427–430

```php
public function budgets()
{
    return $this->hasMany(ProjectBudget::class, 'project_id', 'project_id');
}
```

**No phase scoping:** The relationship returns all budgets for the project regardless of phase.

---

### 2.3 Other Loaders of budgets

| Location | Code | Phase filter? |
|----------|------|---------------|
| ProjectController::create (predecessor) | `$predecessorProject->budgets->groupBy('phase')` | No — loads all phases |
| ProjectController::show | `->with(['budgets', ...])` | No |
| ProjectController::edit | `$project` passed with budgets from parent query | No |
| ProjectFundFieldsResolver::resolveDevelopment | `$project->budgets->where('phase', $currentPhase)` | **Yes** — filters by `current_phase` |
| BudgetValidationService::calculateBudgetData | `$project->budgets->sum('this_phase')` | No — sums all phases |
| MonthlyDevelopmentProjectController | `ProjectBudget::where(...)->where('phase', $highestPhase)` | **Yes** — uses max phase |
| config/budget.php | `ProjectBudget::class` in field mappings | N/A |

---

## 3. Phase Determination

### 3.1 Phase is Derived from Array Index

**File:** `app/Http/Controllers/Projects/BudgetController.php`  
**Lines:** 65, 126

```php
'phase' => $phaseIndex + 1,
```

- `$phaseIndex` is the PHP array index of `$phases` (0, 1, 2, …).
- Phase is written as `$phaseIndex + 1` (1-based).
- **Phase is not** derived from `projects.current_phase`.
- **Phase is not** sent in the request; it is purely positional.

---

### 3.2 Request Structure: phases[0] Only

**Form structure (create):**  
**File:** `resources/views/projects/partials/budget.blade.php`  
**Lines:** 31–35

```blade
<textarea name="phases[0][budget][0][particular]" ...>
<input name="phases[0][budget][0][rate_quantity]" ...>
<input name="phases[0][budget][0][rate_multiplier]" ...>
<input name="phases[0][budget][0][rate_duration]" ...>
<input name="phases[0][budget][0][this_phase]" ...>
```

**Form structure (edit):**  
**File:** `resources/views/projects/partials/Edit/budget.blade.php`  
**Lines:** 33–37

```blade
name="phases[0][budget][{{ $budgetIndex }}][particular]"
name="phases[0][budget][{{ $budgetIndex }}][rate_quantity]"
...
name="phases[0][budget][{{ $budgetIndex }}][this_phase]"
```

**Effect:** All budget rows are under `phases[0]`. Only one phase index exists in the form. `$phaseIndex` is always 0, so `phase` is always 1 in the database.

---

### 3.3 projects.current_phase

**Schema:** `projects` table has `current_phase` (nullable integer).  
**Usage:** General info form, ProjectFundFieldsResolver (for Development types), predecessor selection.  
**Relationship to budget form:** None. The budget form does not read or write `current_phase`. The controller does not use `current_phase` when persisting budgets.

---

## 4. Delete Behavior

### 4.1 update() Deletes ALL Budgets for Project

**File:** `app/Http/Controllers/Projects/BudgetController.php`  
**Line:** 113

```php
ProjectBudget::where('project_id', $project->project_id)->delete();
```

**Scope:** Project only. No phase filter. Every budget row for the project is deleted before recreating from the request.

**Cause/effect:** If the DB had multiple phases (e.g. phase 1 and phase 2), and the form submits only `phases[0]`, the update would delete phase 2 budgets and never recreate them. In practice, the form only ever submits `phases[0]`, so all stored budgets are phase 1.

---

## 5. Read Behavior

### 5.1 Edit Form: How Budgets Are Fetched

**Flow:**
1. `ProjectController::edit()` loads `$project` with `->with('budgets', 'attachments', 'objectives', 'sustainabilities')` (line 1100).
2. Edit view includes `projects.partials.Edit.budget` for institutional types (line 112 of `edit.blade.php`).
3. `Edit/budget.blade.php` uses `$project->budgets`:

**File:** `resources/views/projects/partials/Edit/budget.blade.php`  
**Lines:** 29–40

```blade
@if($project->budgets && $project->budgets->count())
    @foreach($project->budgets as $budgetIndex => $budget)
        ...
        {{ old('phases.0.budget.' . $budgetIndex . '.particular', $budget->particular) }}
        ...
        value="{{ old('phases.0.budget.' . $budgetIndex . '.this_phase', $budget->this_phase) }}"
```

**Phase filter:** None. All budgets from `$project->budgets` are rendered. If multiple phases existed, all would appear in a single flat list under `phases[0]`.

---

### 5.2 Show View: How Budgets Are Displayed

**File:** `resources/views/projects/partials/Show/budget.blade.php`  
**Lines:** 247–275

```blade
@forelse($project->budgets as $index => $budget)
    <tr>
        ...
        <td>{{ format_indian($budget->this_phase ?? 0, 2) }}</td>
    </tr>
...
<th>{{ format_indian($project->budgets->sum('this_phase'), 2) }}</th>
```

**Phase filter:** None. All phases are shown and summed.

---

### 5.3 ProjectFundFieldsResolver (Development Types)

**File:** `app/Services/Budget/ProjectFundFieldsResolver.php`  
**Lines:** 86–95

```php
if ($overall == 0 && $project->relationLoaded('budgets') && $project->budgets->isNotEmpty()) {
    $currentPhase = (int) ($project->current_phase ?? 1);
    $phaseBudgets = $project->budgets->where('phase', $currentPhase);
    $overall = $phaseBudgets->sum(function ($b) {
        return (float) ($b->this_phase ?? 0);
    });
}
```

**Phase filter:** Yes — sums only `phase === current_phase` when `overall_project_budget` is 0.

---

### 5.4 BudgetValidationService

**File:** `app/Services/BudgetValidationService.php`  
**Lines:** 51–53, 101–103

```php
$overallBudget = $project->overall_project_budget ?? 0;
if ($overallBudget == 0 && $project->relationLoaded('budgets')) {
    $overallBudget = $project->budgets->sum('this_phase');
}
// ...
$budgetItemsTotal = $project->relationLoaded('budgets')
    ? $project->budgets->sum('this_phase')
    : 0;
```

**Phase filter:** No. Sums all phases.

---

## 6. next_phase Lifecycle

### 6.1 next_phase in Create Form

**File:** `resources/views/projects/partials/budget.blade.php`

**Columns:** particular, rate_quantity, rate_multiplier, rate_duration, this_phase.  
**next_phase:** Not present. No input for `next_phase`.  
**rate_increase:** Not present. Controller uses `$budget['rate_increase'] ?? 0` (defaults to 0).

---

### 6.2 next_phase in Edit Form

**File:** `resources/views/projects/partials/Edit/budget.blade.php`

**Columns:** particular, rate_quantity, rate_multiplier, rate_duration, this_phase.  
**next_phase:** Not present. No input for `next_phase`.  
**rate_increase:** Not present. Stored value from DB is not displayed; on submit, absent key defaults to 0.

---

### 6.3 next_phase in Request

- Form does not submit `next_phase`.
- StoreBudgetRequest and UpdateBudgetRequest normalize and validate `phases.*.budget.*.next_phase` (nullable, numeric, min:0, NumericBoundsRule).
- If key is absent, normalization does not add it; controller uses `$budget['next_phase'] ?? 0`.

**Effect:** `next_phase` is effectively 0 unless some client-side script adds it (none found in budget forms).

---

### 6.4 next_phase Written to DB

**File:** `app/Http/Controllers/Projects/BudgetController.php`  
**Lines:** 61, 72, 122, 133

```php
$nextPhase = $bounded->clamp((float) ($budget['next_phase'] ?? 0), $maxPhase);
// ...
'next_phase' => $nextPhase,
```

**Source:** `$budget['next_phase']` from request. When absent, 0 is used and clamped.

---

### 6.5 next_phase Defaulted to 0

- Controller: `(float) ($budget['next_phase'] ?? 0)`.
- FormRequest normalizeInput: `PlaceholderNormalizer::normalizeToZero($row[$key])` only when `array_key_exists($key, $row)`. If `next_phase` is not in the row, it is not normalized and remains absent; controller falls back to 0.

---

### 6.6 next_phase Nullable in Schema

**File:** `database/migrations/2024_07_20_085654_create_project_budgets_table.php`  
**Line:** 20

```php
$table->decimal('next_phase', 10, 2)->nullable();
```

**Nullable:** Yes.

---

### 6.7 next_phase in Derived Calculations

- **ProjectBudget::calculateTotalBudget()** — uses `rate_quantity * rate_multiplier * rate_duration * rate_increase`. Does not use `next_phase`.
- **ProjectBudget::calculateRemainingBalance()** — uses `calculateTotalBudget()`. Does not use `next_phase`.
- ** scripts.blade.php** — `calculateBudgetTotals(phaseCard)` sums `row.querySelector('[name$="[next_phase]"]').value`, but the current budget form has no `next_phase` input; that code is dead for this form.
- ** scripts-edit.blade.php** — no `next_phase` calculation; only `this_phase` via `calculateBudgetRowTotals()`.

---

## 7. Structural Mismatch

### 7.1 DB Supports Multiple Phases

- `project_budgets.phase` is nullable integer.
- Schema allows multiple rows per project with different phase values.
- ProjectFundFieldsResolver and MonthlyDevelopmentProjectController assume multiple phases and filter by phase.

---

### 7.2 Form Submits phases[0] Only

- Create and edit forms use `phases[0][budget][...]` exclusively.
- All budget rows are under a single phase index.
- Controller assigns `phase = $phaseIndex + 1` = 1 for all rows.

---

### 7.3 Controller Delete-then-Recreate

- `update()` deletes all budgets for the project, then recreates from request.
- Request contains only `phases[0]`, so only phase 1 is recreated.
- Any other phases in the DB are permanently removed on update.

---

### 7.4 Phase Derived from Array Index

- Phase is `$phaseIndex + 1`, not from `projects.current_phase` or request.
- With only `phases[0]`, phase is always 1.

---

### 7.5 next_phase Not in Request but Persisted

- Form has no `next_phase` input.
- Controller persists `next_phase` from `$budget['next_phase'] ?? 0` (always 0 in practice).
- Column is nullable and receives 0 or clamped value.

---

## 8. Required Corrections Before Phase 2.4

### A) Lock Phase to `$project->current_phase`

**Requirement:** When writing budgets, phase must be set from `$project->current_phase`, not from `$phaseIndex`.

**Rationale:** Aligns stored phase with the project’s lifecycle and avoids arbitrary phase from array position.

**Scope:** BudgetController store() and update().

---

### B) Scope Delete to Current Phase Only

**Requirement:** In `update()`, delete only budgets for the current phase:

```text
ProjectBudget::where('project_id', $project->project_id)
    ->where('phase', $project->current_phase ?? 1)
    ->delete();
```

**Rationale:** Prevents wiping other phases when the form only edits the current phase.

---

### C) Scope Reads to Current Phase Only

**Requirement:** Where the UI and logic intend to show/edit the current phase’s budgets:

- Edit form: Load `$project->budgets()->where('phase', $project->current_phase ?? 1)->get()` (or equivalent) instead of `$project->budgets`.
- Show form: Decide whether to show all phases or only current phase; if current phase only, apply the same filter.
- BudgetValidationService and ProjectFundFieldsResolver: Ensure they use the same phase semantics (current phase vs all phases) as the rest of the system.

**Rationale:** Avoids mixing phases in a single form and ensures consistency with delete/write scope.

---

### D) Define Lifecycle for next_phase

**Options:**
1. **Preserve:** Keep `next_phase` in DB; add to form if needed; ensure it is validated and persisted correctly.
2. **Calculate:** Server-side derivation (e.g. via DerivedCalculationService) before persist; do not trust client.
3. **Remove from write path:** Stop persisting `next_phase`; set to null or omit from create/update if not used.

**Requirement:** Choose one strategy and document it. Currently `next_phase` is persisted but not displayed or edited, so its purpose and source must be clarified.

---

## Appendix: File Reference Summary

| File | Relevance |
|------|-----------|
| `app/Http/Controllers/Projects/BudgetController.php` | store, update, phase assignment, delete |
| `app/Models/OldProjects/ProjectBudget.php` | model, relations, calculateTotalBudget, calculateRemainingBalance |
| `app/Models/OldProjects/Project.php` | budgets() relationship |
| `app/Http/Requests/Projects/StoreBudgetRequest.php` | validation, normalization |
| `app/Http/Requests/Projects/UpdateBudgetRequest.php` | validation, normalization |
| `resources/views/projects/partials/budget.blade.php` | create form — phases[0] only, no next_phase |
| `resources/views/projects/partials/Edit/budget.blade.php` | edit form — phases[0] only, no next_phase |
| `resources/views/projects/partials/Show/budget.blade.php` | show — all budgets, no phase filter |
| `resources/views/projects/partials/scripts.blade.php` | calculateBudgetRowTotals (this_phase only for current form) |
| `resources/views/projects/partials/scripts-edit.blade.php` | calculateBudgetRowTotals, addBudgetRow |
| `app/Services/Budget/ProjectFundFieldsResolver.php` | resolveDevelopment — filters by current_phase |
| `app/Services/Budget/BudgetSyncService.php` | syncFromTypeSave after update |
| `app/Services/BudgetValidationService.php` | sums all budgets, no phase filter |
| `database/migrations/2024_07_20_085654_create_project_budgets_table.php` | schema — phase nullable, next_phase nullable |
| `app/Http/Controllers/Projects/ProjectController.php` | store, update, edit, show — invokes BudgetController |

---

## Phase Lock Confirmed (Regression Freeze)

**Date:** 2026-02-09

A regression test (`DevelopmentBudgetPhaseFreezeTest`) has been implemented to freeze and protect the stabilized behavior.

The test confirms:

- BudgetController uses `$project->current_phase` as the single source of truth.
- Budget delete operation is scoped to `project_id + current_phase` only.
- Budget reads are scoped to current phase only.
- `next_phase` remains `NULL` when not provided in the request.
- Row count remains unchanged during edit.
- `this_phase` aggregation remains unchanged after update.
- No row is written with `phase = 1` unless `current_phase = 1`.

This behavior is now protected by automated regression testing.

Budget Phase Stabilization is considered locked and safe for future refactors.
