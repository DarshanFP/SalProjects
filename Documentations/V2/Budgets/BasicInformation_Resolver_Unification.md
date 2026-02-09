# Basic Information Resolver Unification

**Date:** February 9, 2026  
**Status:** Implemented  
**Scope:** `ProjectFundFieldsResolver::resolveDevelopment()` — Overall Project Budget source of truth

---

## 1. Summary

The View (Basic Information) and Edit (Budget Section) pages now use the **same source of truth** for Overall Project Budget on Development projects. The resolver derives `overall_project_budget` from budget rows (current phase) when available, matching the Edit page JS behavior. The DB-stored `overall_project_budget` is used only as a fallback when no budget rows exist.

---

## 2. Previous Behavior

| Condition | Behavior |
|-----------|----------|
| `project.overall_project_budget != 0` | Used DB value; **never** recomputed from budget rows |
| `project.overall_project_budget == 0` | Loaded budgets, summed `this_phase` for `current_phase` |

**Problem:** When `overall_project_budget` was non-zero but stale (e.g. out of sync with budget rows), the View showed the wrong value while Edit showed the correct sum from JS. This caused View–Edit discrepancy (e.g. DP-0030).

---

## 3. New Behavior

| Condition | Behavior |
|-----------|----------|
| Project has budget rows for `current_phase` | `overall = DerivedCalculationService::calculateProjectTotal(budgets->map(this_phase))` |
| No budget rows for `current_phase` | `overall = (float) project.overall_project_budget` (fallback) |
| Project is approved | `amount_sanctioned` and `opening_balance` from DB (unchanged) |

**Rule:** If budget rows exist for `current_phase`, the resolver derives overall from them. DB value is ignored. If no rows exist, fallback to DB.

---

## 4. Why Change Was Required

- **Parity:** View and Edit must show the same Overall Project Budget.
- **Consistency:** Edit uses JS `calculateProjectTotal()` summing `this_phase` from current phase rows. View must use the same logic.
- **Stale data:** DB `overall_project_budget` can be out of sync; resolver must not trust it when budget rows exist.

---

## 5. Phase Filtering Logic

- **Filter:** `project.budgets->where('phase', project.current_phase)`
- **Strict:** Only `current_phase` is used; no `next_phase`, no summing across phases.
- **Default:** `current_phase ?? 1` when null.

---

## 6. Fallback Logic

- **When:** No budget rows exist for `current_phase`.
- **Value:** `(float) project.overall_project_budget ?? 0`
- **Rationale:** Preserves behavior for projects without budget rows (e.g. legacy, or before first save).

---

## 7. Approval Preservation Rules

- **Approved projects:** `BudgetSyncGuard::isApproved($project)` is true.
- **Sanctioned:** Use `project.amount_sanctioned` from DB; do not recompute.
- **Opening:** Use `project.opening_balance` from DB; do not recompute.
- **Overall:** Still derived from budget rows when available (for display). Approval flow writes sanctioned/opening at approval time; resolver only reads them for approved projects.

---

## 8. Implementation Details

### File Modified

- `app/Services/Budget/ProjectFundFieldsResolver.php`

### Changes

1. **Constructor injection:** `DerivedCalculationService` injected via constructor (no `app()` calls).
2. **Overall computation:** When budget rows exist for `current_phase`, use `DerivedCalculationService::calculateProjectTotal()` on `this_phase` values.
3. **Approved projects:** Use DB `amount_sanctioned` and `opening_balance` when `BudgetSyncGuard::isApproved($project)`.

### Constraints Preserved

- No database schema changes.
- No removal of `overall_project_budget` column.
- No changes to save/store/update flows.
- No changes to approval workflow logic.
- No changes to `DerivedCalculationService` implementation.
- No changes to JS.
- **Display logic only.**

---

## 9. Test Coverage

| Test | File | Assertion |
|------|------|-----------|
| Resolver returns sum when budgets exist | `tests/Feature/Budget/ViewEditParityTest.php` | `overall == sum(this_phase)`; DB value ignored |
| No budgets → fallback | `ViewEditParityTest` | `overall == project.overall_project_budget` |
| Approved project → sanctioned untouched | `ViewEditParityTest` | `amount_sanctioned` and `opening_balance` from DB |
| Phase filter | `ViewEditParityTest` | Only `current_phase` rows summed; other phases ignored |

---

## 10. Related Documentation

- `BasicInformation_Budget_Audit.md` — Full audit of 6 budget fields
- `DP0030_View_Edit_Budget_Discrepancy_Finding.md` — Root cause analysis
- `PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md` — Phase-wise plan
