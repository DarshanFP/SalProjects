# JS–Backend Naming Symmetry — Implementation Report

**Date:** February 9, 2025  
**Phase:** 2.4 — Step 3  
**Scope:** Align JS function names with DerivedCalculationService

---

## Summary

Global JS budget functions have been renamed to match `DerivedCalculationService` so JS and backend share the same domain vocabulary. No formulas, behavior, or logic were changed.

---

## Files Modified

| File | Change |
|------|--------|
| `resources/views/projects/partials/scripts.blade.php` | Renamed 3 functions; updated all references |
| `resources/views/projects/partials/scripts-edit.blade.php` | Renamed 4 functions; updated all references |
| `resources/views/projects/partials/budget.blade.php` | Updated `oninput`/`onclick` handlers |
| `resources/views/projects/partials/Edit/budget.blade.php` | Updated `oninput`/`onclick` handlers |
| `resources/views/projects/partials/NPD/budget.blade.php` | Updated `oninput` handlers |

---

## Renamed Functions

| Old Name | New Name | Backend Equivalent |
|----------|----------|--------------------|
| `calculateBudgetRowTotals` | `calculateRowTotal` | `DerivedCalculationService::calculateRowTotal` |
| `calculateTotalAmountSanctioned` | `calculateProjectTotal` | `DerivedCalculationService::calculateProjectTotal` |
| `calculateBudgetFields` | `calculateAmountSanctioned` | `DerivedCalculationService::calculateAmountSanctioned` |
| `calculateBudgetTotals` (scripts-edit only) | `calculatePhaseTotal` | `DerivedCalculationService::calculatePhaseTotal` |

---

## Old → New Mapping

| Context | Old | New |
|---------|-----|-----|
| Row total (DOM handler) | `calculateBudgetRowTotals(element)` | `calculateRowTotal(element)` |
| Project total (sums this_phase, updates overall budget) | `calculateTotalAmountSanctioned()` | `calculateProjectTotal()` |
| Amount sanctioned + opening balance | `calculateBudgetFields()` | `calculateAmountSanctioned()` |
| Phase footer totals (scripts-edit) | `calculateBudgetTotals()` | `calculatePhaseTotal()` |

---

## Module Unchanged

`public/js/budget-calculations.js` was not modified. It already exposed:

- `BudgetCalculations.calculateRowTotal`
- `BudgetCalculations.calculatePhaseTotal`
- `BudgetCalculations.calculateProjectTotal`
- `BudgetCalculations.calculateRemainingBalance`
- `BudgetCalculations.calculateAmountSanctioned`

---

## Confirmation No Formula Changed

- **Row total:** `calculateRowTotal` still calls `BudgetCalculations.calculateRowTotal(q, m, d)`.
- **Project total:** `calculateProjectTotal` still uses `BudgetCalculations.calculateProjectTotal(thisPhaseValues)`.
- **Amount sanctioned:** `calculateAmountSanctioned` still uses `BudgetCalculations.calculateAmountSanctioned(overallBudget, combined)`.
- **Phase total:** `calculatePhaseTotal` still uses `BudgetCalculations.calculateProjectTotal(thisPhaseValues)` for footer.

---

## Confirmation No Behavior Changed

- Same DOM reads/writes
- Same event bindings (`oninput`, `addEventListener`)
- Same call order (e.g. `calculateRowTotal` → `calculateProjectTotal` → `calculateAmountSanctioned`)
- Same validation and correction logic

---

## Post-Conditions Met

- [x] JS and backend share domain vocabulary
- [x] No arithmetic change
- [x] No UI change
- [x] No logic change

---

Implementation Complete — Step 3
