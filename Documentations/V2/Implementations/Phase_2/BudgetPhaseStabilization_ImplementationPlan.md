# Budget Phase Stabilization — Implementation Plan

**Document Type:** Pre-implementation plan (Phase 2)  
**Prerequisite:** `BudgetPhaseStabilization_Audit.md`  
**Scope:** Domain correction before Phase 2.4 — no code changes in this document.

---

## SECTION 1 — Architectural Target State

### 1.1 Future Behavior

| Aspect | Target State |
|--------|--------------|
| **Phase assignment** | `phase` always equals `$project->current_phase` (or `1` when null). Never derived from array index. |
| **Edit scope** | Edit form and update() only touch budgets for the current phase. Other phases are never deleted or overwritten. |
| **Historical phases** | Rows with `phase != current_phase` remain intact. No delete-then-recreate across all phases. |
| **next_phase lifecycle** | Explicitly defined (see Section 4). No implicit 0 overwrites of meaningful stored values. |
| **Multi-phase preservation** | Projects with multiple phases retain all phase data. Update of phase 1 does not remove phase 2. |
| **Read consistency** | Edit loads current-phase budgets only. Show loads all phases (or current phase per decision). Resolver and validation services align with same semantics. |

### 1.2 Invariants

1. **Write:** `ProjectBudget::create(['phase' => $project->current_phase ?? 1, ...])` — phase is never `$phaseIndex + 1`.
2. **Delete:** `ProjectBudget::where('project_id', ...)->where('phase', $project->current_phase ?? 1)->delete()` — only current phase.
3. **Edit read:** Budgets passed to edit form are filtered by `phase === current_phase`.
4. **Create:** On store, phase is `$project->current_phase ?? 1` (project may not be saved yet; use request or default 1).
5. **next_phase:** Source and default behavior documented; no unintended overwrites.

### 1.3 Form Structure

- **Current:** `phases[0][budget][...]` — single phase index. No change required to form structure if we treat `phases[0]` as "current phase" semantically.
- **Optional simplification:** Remove `phases` wrapper and use `budget[...]` if desired. This is a **non-goal** for this plan — form structure change only if necessary for clarity.

---

## SECTION 2 — Minimal Code Changes Required

### 2.1 BudgetController — store()

**File:** `app/Http/Controllers/Projects/BudgetController.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| Replace `'phase' => $phaseIndex + 1` | Use `'phase' => (int) ($project->current_phase ?? 1)` | Phase must come from project lifecycle, not array index. On create, project may not have `current_phase` yet; use request or default 1. | **Low** |
| Iterate only `phases[0]` (or first phase) | Process only the current-phase payload. With single-phase form, this is `$phases[0] ?? []`. | Form submits one phase; we write one phase. Avoid creating duplicates if `phases` has multiple keys. | **Low** |
| Ensure `current_phase` on project | If project is new, ensure `current_phase` is set before BudgetController::store (e.g. from GeneralInfoController). | Store happens after general info; project should have `current_phase` by then. | **Low** |

**Note:** On create, `$project` is passed after `Project::create()` in ProjectController. Verify `current_phase` is persisted in GeneralInfoController or initial save. If not, use `$request->input('current_phase', 1)` as fallback.

---

### 2.2 BudgetController — update()

**File:** `app/Http/Controllers/Projects/BudgetController.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| Scope delete to current phase | `ProjectBudget::where('project_id', $project->project_id)->where('phase', $project->current_phase ?? 1)->delete()` | Prevent wiping other phases. Only current phase is delete-then-recreate. | **Medium** — first deployment changes behavior; historical multi-phase projects will now preserve prior phases. |
| Replace `'phase' => $phaseIndex + 1` | Use `'phase' => (int) ($project->current_phase ?? 1)` | Same as store. | **Low** |
| Iterate only current-phase payload | Process `$phases[0]` or equivalent (single phase). | Form submits one phase. | **Low** |

---

### 2.3 BudgetController — edit() / show()

**BudgetController does not define edit() or show().** Data is provided by ProjectController and passed to Blade partials.

| Location | Change | Why | Risk |
|----------|--------|-----|------|
| N/A | No new methods in BudgetController | Edit/show are handled by ProjectController and views. | — |

---

### 2.4 ProjectController — edit()

**File:** `app/Http/Controllers/Projects/ProjectController.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| Pass current-phase budgets to edit view | Either: (a) Add `budgetsForCurrentPhase` to compact, filtered by `$project->current_phase ?? 1`, or (b) Use a relationship scope. | Edit form must display only current-phase rows. Mixing phases in one form causes incorrect delete/recreate. | **Low** |

**Implementation options:**
- **Option A:** `$project->load(['budgets' => fn ($q) => $q->where('phase', $project->current_phase ?? 1)]);` — overloads `budgets` for this view. **Risk:** Other code in same request may expect all budgets. Prefer a separate variable.
- **Option B:** Add `$budgetsForEdit = $project->budgets->where('phase', $project->current_phase ?? 1)->values();` and pass to view. Blade uses `$budgetsForEdit` instead of `$project->budgets` for the budget partial.
- **Option C:** Create `Project::budgetsForCurrentPhase()` relationship (dynamic scope). Use in edit flow only.

**Recommended:** Option B — minimal change, explicit, no model changes.

---

### 2.5 ProjectController — show()

**File:** `app/Http/Controllers/Projects/ProjectController.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| Decide: all phases vs current phase | **Recommended:** Show all phases (no change). Display budget items from all phases for full history. | Show is read-only; users may need to see phase 1 + phase 2 totals. ProjectFundFieldsResolver already uses current_phase for Development fallback. | **Low** — no change preserves current behavior. |

**Alternative:** Show only current phase. Would require filtering in Show partial. **Not recommended** for this plan — explicit non-goal to change Show semantics unless required.

---

### 2.6 Blade — Edit/budget.blade.php

**File:** `resources/views/projects/partials/Edit/budget.blade.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| Use `$budgetsForEdit` instead of `$project->budgets` | Iterate over `$budgetsForEdit ?? $project->budgets` (fallback for backward compatibility during rollout). | Display only current-phase rows. | **Low** |

**Dependency:** ProjectController must pass `budgetsForEdit` in compact.

---

### 2.7 Blade — budget.blade.php (create)

**File:** `resources/views/projects/partials/budget.blade.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| None | Create form has no existing data. No phase filter needed. | Create writes phase from `$project->current_phase ?? 1` or request. | — |

---

### 2.8 Relationship Filters (Project model)

**File:** `app/Models/OldProjects/Project.php`

| Change | Behavior | Why | Risk |
|--------|----------|-----|------|
| **Optional:** Add `budgetsForPhase(int $phase)` | New method: `return $this->hasMany(ProjectBudget::class, ...)->where('phase', $phase);` | Reusable scoped access. | **Low** — optional; not required if Option B in 2.4 is used. |

**Recommendation:** Not required for minimal change. Use inline filter in ProjectController.

---

### 2.9 Other Consumers — BudgetValidationService, ProjectFundFieldsResolver

| File | Change | Why | Risk |
|------|--------|-----|------|
| `ProjectFundFieldsResolver` | None | Already filters by `current_phase` when `overall == 0`. | — |
| `BudgetValidationService` | Decide: sum all phases or current phase only. **Recommended:** Sum all phases for validation (total budget items across phases). | Validation checks overall consistency; `overall_project_budget` may aggregate all phases. Align with product intent. | **Low** — document decision; no change if "all phases" is correct. |

---

### 2.10 Summary of Files to Change

| File | Changes | Risk |
|------|---------|------|
| `app/Http/Controllers/Projects/BudgetController.php` | store: phase from project/request; update: phase from project, scope delete to current phase | Low / Medium |
| `app/Http/Controllers/Projects/ProjectController.php` | edit: pass `budgetsForEdit` filtered by current_phase | Low |
| `resources/views/projects/partials/Edit/budget.blade.php` | Use `$budgetsForEdit ?? $project->budgets` | Low |
| `app/Models/OldProjects/Project.php` | Optional: `budgetsForPhase()` — not required | Low |

**No other files** (validation, FormRequest, attachments, numeric bounds, derived calculations) are modified.

---

## SECTION 3 — Migration Safety Analysis

### 3.1 Impact on Historical Projects

| Scenario | Impact |
|----------|--------|
| **Projects with only phase 1** | No change. All existing data is phase 1. New logic writes phase 1, deletes only phase 1. Behavior effectively unchanged. |
| **Projects with multiple phases (phase 1, 2, …)** | **Positive.** Currently, update() deletes all phases and recreates only phase 1. After change, update() deletes only current phase; other phases are preserved. Any existing multi-phase data will no longer be lost on edit. |
| **Projects with null current_phase** | Treated as phase 1 (`$project->current_phase ?? 1`). Matches current behavior (form always submits phases[0] → phase 1). |

### 3.2 Impact on Existing Phase 1 Data

- No data migration needed. Phase 1 rows remain phase 1.
- Read and write semantics for phase 1 are unchanged when `current_phase` is 1 or null.

### 3.3 Risk of Duplicate Budgets

| Risk | Mitigation |
|------|------------|
| Same row created twice | Unlikely. Delete-then-recreate for current phase only. No double-call to create. |
| Phase mismatch (e.g. writing phase 2 when DB has phase 1) | Ensure `current_phase` is correct before BudgetController runs. GeneralInfoController updates `current_phase` in same request. Order of execution: GeneralInfo → Budget. Verify in ProjectController::update() flow. |

### 3.4 Risk of Orphaned Rows

| Risk | Mitigation |
|------|------------|
| Orphaned budgets (project_id exists, project deleted) | Handled by FK cascade. No change. |
| Orphaned phase rows (phase 2 exists, project edited in phase 1) | **None.** We explicitly preserve them. |

### 3.5 Data Migration Script Required

**No.** No schema change. No backfill. No transformation of existing rows. Phase semantics are corrected going forward; historical data is compatible.

### 3.6 DB Index Adjustments

**No.** Existing indexes on `project_id` (and possibly `phase`) are sufficient. If queries filter by `(project_id, phase)`, a composite index may help but is not required for correctness. Optional performance optimization only.

---

## SECTION 4 — next_phase Lifecycle Decision Matrix

### Option A — Preserve Existing DB Value if Not Present in Request

**Behavior:** On update, for each budget row, if `next_phase` is not in the request, keep the existing DB value for that row. On create, default to 0 or null.

| Pros | Cons | Risk |
|------|------|------|
| No accidental overwrite of stored values | Requires row-level merge (update vs create). Create has no "existing" value. | **Medium** — update logic becomes more complex; need to fetch existing budgets, match by some key (e.g. particular + index), and merge. |
| Backward compatible | Form does not submit `next_phase`; "preserve" means we never overwrite, but we also never write it from form. | |

**Implementation:** Update would need to: (1) delete current-phase rows, (2) recreate from request. To "preserve" we would have to fetch before delete, store `next_phase` per row, and re-apply when creating. This contradicts delete-then-recreate. **Not recommended** with current architecture.

---

### Option B — Auto-Calculate next_phase

**Behavior:** Server calculates `next_phase` from a formula (e.g. `(rate_quantity + rate_increase) * rate_multiplier * rate_duration` per DerivedCalculationService design). Do not use request value.

| Pros | Cons | Risk |
|------|------|------|
| Single source of truth | Formula not yet finalized (Phase 2.4). | **High** — Phase 2.4 is not started. Introducing formula here would be premature. |
| No client trust | Depends on DerivedCalculationService. | Blocked by Phase 2.4. |

**Recommendation:** **Defer to Phase 2.4.** Do not implement in Budget Phase Stabilization.

---

### Option C — Remove next_phase from Write Path

**Behavior:** Stop persisting `next_phase` in BudgetController. Set to `null` (or omit from create array). Column remains nullable.

| Pros | Cons | Risk |
|------|------|------|
| No implicit 0 overwrites | Any existing non-zero `next_phase` values would become null on next update. | **Low** — audit found form never sends `next_phase`; controller always writes 0. So existing values are likely 0 or null already. |
| Simplest change | Loses stored value if any exist. | Unlikely; no UI reads or edits it. |
| Clear semantics | next_phase has no defined source until Phase 2.4. | Aligns with "do not guess" principle. |

**Implementation:** In store/update, use `'next_phase' => null` or omit the key. If column is nullable, null is valid. If NOT NULL with default, would need migration — schema shows nullable, so no migration.

**Recommendation:** **Recommended for this plan.** Low risk, clear semantics, no dependency on Phase 2.4.

---

### Decision Summary

| Option | Recommended | Rationale |
|--------|-------------|-----------|
| A | No | Incompatible with delete-then-recreate; complex merge logic. |
| B | No | Blocked by Phase 2.4; formula not finalized. |
| C | **Yes** | Simple, low risk, no overwrites, no Phase 2.4 dependency. |

**Chosen:** Option C — Remove `next_phase` from write path. Set to `null` in create/update. Document that Phase 2.4 may reintroduce it with a calculated formula.

---

## SECTION 5 — Rollout Plan

### Step 1: Add Phase Scoping (Write)

1. In BudgetController::store(), replace `'phase' => $phaseIndex + 1` with `'phase' => (int) ($project->current_phase ?? $request->input('current_phase', 1))`.
2. In BudgetController::update(), same replacement.
3. Ensure only `phases[0]` (or first element) is processed in both methods.
4. **Verification:** Create project, edit budget, confirm `phase` in DB matches `projects.current_phase`.

**Order:** Before Step 2, so new writes are correct even if delete is not yet scoped.

---

### Step 2: Adjust Delete Logic

1. In BudgetController::update(), replace:
   - `ProjectBudget::where('project_id', $project->project_id)->delete();`
   - With: `ProjectBudget::where('project_id', $project->project_id)->where('phase', $project->current_phase ?? 1)->delete();`
2. **Verification:** If test DB has phase 1 and phase 2, edit (phase 1), save; phase 2 rows must remain.

---

### Step 3: Adjust Read Logic (Edit)

1. In ProjectController::edit(), after loading project, add:
   - `$budgetsForEdit = $project->budgets->where('phase', $project->current_phase ?? 1)->values();`
2. Add `budgetsForEdit` to compact() passed to the edit view.
3. In Edit/budget.blade.php, change `$project->budgets` to `$budgetsForEdit ?? $project->budgets` where the budget table is rendered.
4. **Verification:** Edit project with multiple phases; only current-phase rows appear in form.

---

### Step 4: Adjust Write Logic (next_phase)

1. In BudgetController::store() and update(), change `'next_phase' => $nextPhase` to `'next_phase' => null` (or remove the key if fillable allows).
2. Remove the `$nextPhase` variable and clamp logic for next_phase if no longer needed.
3. **Verification:** New/updated budgets have `next_phase = null` in DB.

---

### Step 5: Add Regression Tests

1. **Test:** store creates budget with `phase === project.current_phase`.
2. **Test:** update deletes only current-phase rows; other phases remain.
3. **Test:** edit form receives only current-phase budgets.
4. **Test:** next_phase is null after store/update.
5. **Test:** Create project with phase 1, add phase 2 budgets (via seeder or raw insert), set current_phase=2, edit; both phases must exist; only phase 2 edited.

---

### Implementation Checklist

- [x] Phase locked to `$project->current_phase`
- [x] Delete scoped to current phase only
- [x] Reads scoped to current phase only
- [x] next_phase lifecycle decision documented
- [x] Regression freeze test implemented

---

### Step 6: Manual Verification Checklist

- [ ] Create new institutional project; save budget; confirm `phase` in `project_budgets` equals `projects.current_phase`.
- [ ] Edit existing project (phase 1); change budget rows; save; confirm no duplicate rows, no phase change.
- [ ] (If multi-phase test data exists) Add phase 2 budgets; set `current_phase=2`; edit and save; confirm phase 1 budgets still exist.
- [ ] Confirm `next_phase` is null for new/updated rows.
- [ ] Show view still displays budget items (all phases or current per product decision).
- [ ] BudgetValidationService and ProjectFundFieldsResolver produce expected values.

---

## SECTION 6 — Explicit Non-Goals

This change **will NOT**:

| Area | Status |
|------|--------|
| **Numeric bounds** | Phase 2.3 frozen. No change to BoundedNumericService, NumericBoundsRule, decimal_bounds.php, or clamping behavior for `this_phase`. |
| **Derived calculations** | Phase 2.4 not started. No DerivedCalculationService, no server-side recalculation of `this_phase` or `next_phase` formula. |
| **Attachments** | Phase 2.2 frozen. No change to attachment handling. |
| **FormDataExtractor** | Phase 2.1 frozen. No change to FormDataExtractor or normalization layer. |
| **UI beyond budget partial** | No change to form structure except using `$budgetsForEdit` in Edit/budget.blade.php. No removal of `phases[0]` structure unless explicitly required. |
| **Validation rules** | StoreBudgetRequest and UpdateBudgetRequest unchanged. `next_phase` rule may remain (validates if present); controller simply does not pass it. |
| **ProjectBudget model** | No change to calculateTotalBudget, calculateRemainingBalance, or relationships. |
| **Other controllers** | IGE, ILP, IAH, IIES, IES budget controllers unchanged. |
| **Reports** | MonthlyDevelopmentProjectController, ReportController, etc. unchanged. |

---

## Document History

| Version | Date | Author | Notes |
|---------|------|--------|-------|
| 1.0 | 2026-02-09 | Phase 2 | Pre-implementation plan; no code changes. |

---

## Stabilization Baseline Complete

Phase 2.3 (Numeric Bounds) and Budget Phase Stabilization are now complete.

The system baseline now guarantees:

- Single active phase = `$project->current_phase`
- Phase is never derived from array index
- next_phase is nullable and not auto-zeroed
- Delete + recreate affects current phase only
- this_phase total is stable and regression-protected

Phase 2.4 (Derived Calculation Stabilization) may now proceed safely.
