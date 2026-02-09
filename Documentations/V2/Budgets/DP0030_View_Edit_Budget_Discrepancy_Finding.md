# Budget Discrepancy Finding: View Page vs Edit Page (DP-0030)

**Date:** February 9, 2026  
**Project ID:** DP-0030  
**Status:** Finding documented — remediation pending  
**Related:** `ProjectFundFieldsResolver`, `BudgetSyncService`, `general_info.blade.php`, `Edit/budget.blade.php`

---

## 1. Executive Summary

A discrepancy exists between budget values displayed on the **Basic Information (View)** page and the **Budget section (Edit)** page for Development Projects. The View page shows **Overall Project Budget** and **Opening Balance** as Rs. 14,03,000.00, while the Edit page shows **Overall Project Budget** as Rs. 18,13,000.00 and **Opening Balance** as Rs. 18,13,000.00. Local Contribution (Rs. 4,10,000.00) matches in both views.

---

## 2. Observed Discrepancy

### 2.1 View Page (Basic Information)

| Field | Value | Source |
|-------|-------|--------|
| Overall Project Budget | Rs. 14,03,000.00 | `resolvedFundFields['overall_project_budget']` |
| Amount Forwarded | Rs. 0.00 | `resolvedFundFields['amount_forwarded']` |
| Local Contribution | Rs. 4,10,000.00 | `resolvedFundFields['local_contribution']` |
| Amount Requested | Rs. 9,93,000.00 | Computed: `overall - (forwarded + local)` |
| Amount Sanctioned | Rs. 9,93,000.00 | `resolvedFundFields['amount_sanctioned']` |
| Opening Balance | Rs. 14,03,000.00 | `resolvedFundFields['opening_balance']` |

### 2.2 Edit Page (Budget Section)

| Field | Value | Source |
|-------|-------|--------|
| Overall Project Budget | Rs. 18,13,000.00 | JS: sum of all budget item `this_phase` values |
| Amount Forwarded | Rs. 0.00 | `$project->amount_forwarded` |
| Local Contribution | Rs. 4,10,000.00 | `$project->local_contribution` |
| Amount Sanctioned (To Request) | Rs. 14,03,000.00 | `Overall - (Forwarded + Local)` |
| Opening Balance | Rs. 18,13,000.00 | `Amount Sanctioned + (Forwarded + Local)` |

### 2.3 Internal Consistency Check (Edit Page)

- Amount Sanctioned (To Request) = 18,13,000 − 4,10,000 = **14,03,000** ✓  
- Opening Balance = 14,03,000 + 4,10,000 = **18,13,000** ✓  

The Edit page formulas are internally consistent. The View page derives from a different **overall** value.

---

## 3. Root Cause Analysis

### 3.1 Data Flow

1. **View page** uses `ProjectFundFieldsResolver::resolve()` to compute budget values.
2. **Edit page** uses:
   - Budget items from `$project->budgets` (summed by JS).
   - Stored `amount_forwarded`, `local_contribution` from `$project`.

### 3.2 ProjectFundFieldsResolver Logic (Development Projects)

**File:** `app/Services/Budget/ProjectFundFieldsResolver.php` — `resolveDevelopment()`

```php
$overall = (float) ($project->overall_project_budget ?? 0);

// Locked rule: overall from General Info; phase sum only if overall = 0
if ($overall == 0) {
    $project->loadMissing('budgets');
}
if ($overall == 0 && $project->relationLoaded('budgets') && $project->budgets->isNotEmpty()) {
    $currentPhase = (int) ($project->current_phase ?? 1);
    $phaseBudgets = $project->budgets->where('phase', $currentPhase);
    $overall = $phaseBudgets->sum(function ($b) {
        return (float) ($b->this_phase ?? 0);
    });
}
```

**Behaviour:**

- When `projects.overall_project_budget` is **non-zero**, the resolver uses it directly.
- It only falls back to `sum(this_phase)` for the current phase when `overall == 0`.

### 3.3 Source of Truth Mismatch

| Aspect | View Page | Edit Page |
|--------|-----------|-----------|
| Overall Project Budget | `projects.overall_project_budget` (DB) | Sum of all budget items (`this_phase`) via JS |
| When DB is stale | View shows outdated value | Edit shows correct value from live calculation |

For DP-0030:

- `projects.overall_project_budget` = 14,03,000 (stale).
- Sum of budget items = 18,13,000 (correct).

The resolver trusts the DB value, so the View shows 14,03,000.

### 3.4 Possible Causes of Stale `overall_project_budget`

1. **GeneralInfoController** updates `overall_project_budget` from the request before BudgetController runs. If the form’s hidden `overall_project_budget` is not updated by JS before submit, it can be wrong.
2. **BudgetSyncService::syncFromTypeSave** uses the resolver’s output. The resolver uses `project->overall_project_budget`, so the sync can perpetuate a stale value instead of correcting it.
3. **Phase filter**: The resolver’s fallback uses only `current_phase`. If budget items span multiple phases, the sum may differ from the edit form’s total.
4. **JS not run**: If JS does not run or fails, the hidden field may retain the initial (possibly stale) value.

---

## 4. Impact

- **Trust**: Users see different numbers on View vs Edit.
- **Reporting**: Exports and reports may rely on `projects.overall_project_budget`, so they can be wrong.
- **Amount Requested / Sanctioned**: View shows 9,93,000 vs Edit’s 14,03,000.
- **Opening Balance**: View shows 14,03,000 vs Edit’s 18,13,000.

---

## 5. Recommended Fixes

### 5.1 Short-term: Resolver Uses Budget Items When Available

**Change:** In `ProjectFundFieldsResolver::resolveDevelopment()`, when `budgets` are loaded and non-empty, compute `overall` from budget items instead of relying on `project->overall_project_budget`.

**Option A — Always prefer sum of budget items when budgets exist:**

```php
// Pseudocode
if ($project->relationLoaded('budgets') && $project->budgets->isNotEmpty()) {
    $overall = $project->budgets->sum('this_phase');  // All phases, or filter by current_phase if required
} else {
    $overall = (float) ($project->overall_project_budget ?? 0);
}
```

**Option B — Use sum when DB is zero; otherwise prefer DB (current behaviour):**

- Keep current logic but ensure `BudgetSyncService` updates `overall_project_budget` from the sum of budget items before it calls the resolver. That would require a separate step that computes overall from budgets and passes it into the resolver or project before `resolve()`.

### 5.2 Medium-term: BudgetSyncService Syncs Overall from Budget Items

**Change:** When syncing after a budget save, compute `overall_project_budget` from budget items (via `DerivedCalculationService::calculateProjectTotal`), not from the resolver. The resolver would then read a correct value from the project.

### 5.3 Long-term: Single Source of Truth

- **Overall Project Budget** is always derived from `sum(project_budgets.this_phase)` (or equivalent).
- `projects.overall_project_budget` is treated as a cached value, updated only by sync.
- Eliminate dual sources: either DB-backed or computed from budget items, not both.

---

## 6. Related Documentation

| Document | Relevance |
|----------|-----------|
| `Documentations/V2/Implementations/Phase_2/DerivedCalculationService_Design.md` | Canonical formulas, derived calculations |
| `Documentations/V2/Implementations/Phase_2/2.4/Budget_Formula_Parity_Guard.md` | JS vs PHP formula parity |
| `Documentations/V2/Implementations/Phase_2/PHASE_2_4_DERIVED_CALCULATION_AUDIT_FULL.md` | ProjectFundFieldsResolver behaviour |
| `Documentations/OLd Projects Funds/01_Budget_Fields_End_To_End_Changes.md` | Budget field semantics |

---

## 7. Log Files Reviewed

- `storage/logs/laravel-2.log` — No DP-0030 entries for this discrepancy.
- `storage/logs/laravel-07022026.log` — Too large; no DP-0030-specific entries in search.

The discrepancy is primarily a logic/design issue rather than a runtime error.

---

## 8. Verification Steps (Post-fix)

1. Open project DP-0030 edit page and note Overall Project Budget and Opening Balance.
2. Save without changes.
3. Open project DP-0030 view page.
4. Confirm Overall Project Budget and Opening Balance match the Edit page.
5. Add a budget row, save, and verify View and Edit show the same values.

---

## Root Cause Analysis (Forensic Audit)

### Exact Divergence Point

**File:** `app/Services/Budget/ProjectFundFieldsResolver.php`  
**Method:** `resolveDevelopment()`  
**Lines:** 80–96

When `$project->overall_project_budget` is **non-zero**, the resolver uses it directly and **never** recomputes from `project_budgets`. The fallback to `sum(this_phase)` runs only when `$overall == 0`.

```php
// Line 82: Primary source — uses DB value when non-zero
$overall = (float) ($project->overall_project_budget ?? 0);

// Lines 87–96: Fallback ONLY when overall == 0 (never reached when DB has 14,03,000)
if ($overall == 0 && $project->relationLoaded('budgets') && $project->budgets->isNotEmpty()) {
    $phaseBudgets = $project->budgets->where('phase', $currentPhase);
    $overall = $phaseBudgets->sum(function ($b) { return (float) ($b->this_phase ?? 0); });
}
```

### Query Differences

| Page | Data Source | Query / Logic |
|------|-------------|---------------|
| **View** | `projects.overall_project_budget` | Single column from `projects` (no join to `project_budgets`) |
| **Edit** | `project_budgets.this_phase` | `$project->budgets->where('phase', current_phase)` summed by JS |

View reads `projects`; Edit sums `project_budgets`. There is no shared logic that aligns them.

### Accessor / Mutator / Cast Behavior

- **Project model:** `overall_project_budget` is in `$fillable`; no accessor or mutator; no special cast.
- **ProjectBudget model:** No accessors on `this_phase`; standard decimal cast.
- **No transformation** between DB and presentation for these fields.

### Phase Scoping Differences

| Location | Scope | Summation |
|----------|-------|-----------|
| **View** (Basic Info) | N/A | Uses `projects.overall_project_budget` (no phase) |
| **View** (Budget table) | All phases | `$project->budgets->sum('this_phase')` |
| **Edit** (budgetsForEdit) | Current phase only | `$project->budgets->where('phase', current_phase)` |
| **Resolver fallback** | Current phase only | `$project->budgets->where('phase', $currentPhase)->sum('this_phase')` |
| **Edit JS** | Displayed rows | Sums `budgetsForEdit` = current phase only |

Basic Info on View and Edit use different sources; both Edit and resolver fallback use current phase for budget sums.

### Transformation Differences

| Layer | View | Edit |
|-------|------|------|
| **Source** | `projects.overall_project_budget` | `sum(project_budgets.this_phase)` for current phase |
| **Resolver** | Uses `projects.overall_project_budget` when non-zero | Not used |
| **Blade** | `format_indian_currency($resolvedFundFields['overall_project_budget'])` | `value="{{ old('overall_project_budget', $project->overall_project_budget) }}"` (initial) |
| **JS** | None | `calculateProjectTotal()` overwrites hidden field with sum of rows |

---

## Data Flow Comparison

### View Page Data Flow

1. **Route:** `GET /executor/projects/{project_id}` → `ProjectController@show`
2. **Controller** (`ProjectController.php` ~787–807): `Project::where('project_id', $project_id)->with(['budgets', ...])->firstOrFail()`
3. **Resolver decision** (~1069–1076): `$shouldResolveForShow = config('budget.resolver_enabled') || in_array($project_type, $typesWithTypeSpecificBudget)`. Development Projects is **not** in `$typesWithTypeSpecificBudget`; resolution runs only if `resolver_enabled` is true.
4. **Resolver call** (~1073): `$resolver->resolve($project, true)` → `resolveDevelopment()`
5. **Resolver logic** (`ProjectFundFieldsResolver.php` 80–96): `$overall = (float) ($project->overall_project_budget ?? 0)`. Fallback to budget sum is skipped when `$overall != 0`.
6. **View data:** `$data['resolvedFundFields']` returned to Blade (or `null` when resolver disabled).
7. **Blade** (`general_info.blade.php` 30–33, 111–165):  
   - `$budget_overall = $resolvedFundFields['overall_project_budget'] ?? $project->overall_project_budget`  
   - Renders `format_indian_currency($resolvedFundFields['overall_project_budget'] ?? 0, 2)`
8. **Output:** Rs. 14,03,000.00 (value from `projects.overall_project_budget`)

### Edit Page Data Flow

1. **Route:** `GET /executor/projects/{project_id}/edit` → `ProjectController@edit`
2. **Controller** (`ProjectController.php` ~1099–1110):  
   - `Project::where('project_id', $project_id)->with(['budgets', ...])->firstOrFail()`  
   - `$budgetsForEdit = $project->budgets->where('phase', current_phase)->values()`
3. **View data:** `$project`, `$budgetsForEdit` passed to Blade
4. **Blade** (`Edit/budget.blade.php` 28–36, 79–82, 173):  
   - Renders budget rows from `$budgetsForEdit ?? $project->budgets`  
   - Hidden input: `value="{{ old('overall_project_budget', $project->overall_project_budget ?? 0) }}"`
5. **JS** (`scripts-edit.blade.php` 4, 130, 1036–1072):  
   - `DOMContentLoaded` → `calculateProjectTotal()`  
   - Sums `.budget-rows tr` rows’ `[name$="[this_phase]"]`  
   - Sets `overall_project_budget` and `overall_project_budget_display` to `totalAmount.toFixed(2)`  
   - Calls `calculateAmountSanctioned()` for Amount Sanctioned and Opening Balance
6. **Displayed values:** Rs. 18,13,000.00 (from JS sum of current-phase budget rows)

---

## Verified Differences

| Field | Expected (Edit) | Actual (View) | Source of Divergence |
|-------|----------------|----------------|----------------------|
| Overall Project Budget | 18,13,000.00 | 14,03,000.00 | View uses `projects.overall_project_budget`; Edit uses JS sum of `project_budgets.this_phase` |
| Amount Sanctioned | 14,03,000.00 | 9,93,000.00 | Derived from wrong overall: `overall - (forwarded + local)` |
| Opening Balance | 18,13,000.00 | 14,03,000.00 | Derived from wrong overall: `sanctioned + forwarded + local` |
| Amount Forwarded | 0.00 | 0.00 | No divergence |
| Local Contribution | 4,10,000.00 | 4,10,000.00 | No divergence |

---

## Classification of Issue

- [ ] Query mismatch  
- [x] Phase scoping issue (Edit uses current phase; View uses `projects.overall_project_budget` not tied to phase)  
- [ ] Accessor mutation  
- [x] Controller transformation (Resolver chooses DB over budget sum)  
- [ ] Blade arithmetic  
- [x] JS mutation (Edit overwrites with live sum; View never uses this)  
- [ ] Formatting inconsistency  
- [x] Other: **Source-of-truth mismatch** — View and Edit use different sources for Overall Project Budget

---

## Architectural Layer Impacted

- **Persistence:** `projects.overall_project_budget` can be out of sync with `project_budgets`.
- **Retrieval:** No shared retrieval; View reads projects, Edit reads project_budgets.
- **Transformation:** Resolver uses `projects.overall_project_budget` when non-zero instead of recomputing from budget items.
- **Presentation:** View and Edit render different values because they use different sources.

---

## Risk Assessment

- **DP-0030 only?** No. Any Development Project type with `projects.overall_project_budget` stale will show the same mismatch.
- **Systemic?** Yes. Same logic applies to all Development Projects, RST, LDP, CIC, CCI, Edu-RUT using `resolveDevelopment()`.
- **Affects all projects?** Only those using `resolveDevelopment()` (institutional types with `project_budgets`).
- **Audit trust?** Yes. View and Edit disagree, exports use `projects.overall_project_budget`, and reports may be inconsistent.

---

## Recommended Fix Strategy (Do NOT Implement)

### Minimal surgical correction

1. **Change:** In `ProjectFundFieldsResolver::resolveDevelopment()`, when `budgets` are loaded and non-empty, compute `overall` from `sum(this_phase)` (current phase) instead of `$project->overall_project_budget`, so View matches Edit.
2. **Location:** `app/Services/Budget/ProjectFundFieldsResolver.php` lines 80–96.
3. **Effect:** View will use the same logic as Edit (sum of current-phase budget rows).

### Guard test required

- Add a test that, for a Development Project with budget rows, asserts `resolvedFundFields['overall_project_budget']` equals `sum(project_budgets.this_phase)` for the current phase.

### Parity test

- Add a test that, for a Development Project, compares View Basic Info totals with Edit totals (same project, same session) to ensure they match.

---

**Finding status:** Forensic audit complete. Remediation to be implemented per recommended fixes above.
