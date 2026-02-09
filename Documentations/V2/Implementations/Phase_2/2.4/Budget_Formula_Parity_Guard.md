# Phase 2.4 – Budget Formula Parity Guard

## Purpose

This test ensures frontend (JavaScript) and backend (DerivedCalculationService) budget formulas produce identical numeric results. Because PHP cannot execute browser JS, the test simulates the canonical JS logic inline and compares it against the backend service.

## Protected Formula

```
rowTotal = quantity × multiplier × duration
phaseTotal = sum(rowTotals)
projectTotal = sum(phaseTotals)
```

These formulas must remain identical in:
- `public/js/budget-calculations.js` (JS)
- `App\Services\Budget\DerivedCalculationService` (PHP)

## Why This Matters

### Risk of JS drift

If the JS module is updated without updating the backend (or vice versa), the UI will show different totals than the server accepts or persists. Submissions may fail validation or silently store incorrect values.

### Silent rounding differences

Different rounding strategies (e.g. `.toFixed(2)` in JS vs PHP float handling) can cause small discrepancies that grow across many rows or phases.

### UI/backend mismatch

Users see one number in the form, but the backend computes another. This breaks trust and can cause support incidents.

## Enforcement Mechanism

`BudgetFormulaParityTest` ensures identical behavior by:

1. Using deterministic test data (fixed rows)
2. Computing the JS-equivalent result inline in PHP: `$total += $q * $m * $d`
3. Computing the backend result via `DerivedCalculationService::calculateProjectTotal`
4. Asserting `assertEqualsWithDelta($jsTotal, $backendTotal, 0.000001)`

Additional test cases cover:
- Large numbers
- Decimals
- Zero handling

## Failure Policy

If this test fails:

1. **JS was changed** — Updates to `budget-calculations.js` altered the formula without updating the backend.
2. **Backend was changed** — Updates to `DerivedCalculationService` altered the formula without updating the JS.
3. **Numeric precision drift** — Float handling or rounding changed in either layer.

Resolution: Restore formula parity between JS and backend.

## Architectural Guarantee

The Budget Domain is now:

- **Centralized** — All arithmetic lives in DerivedCalculationService (PHP) and budget-calculations.js (JS)
- **Deterministic** — Same inputs always produce same outputs
- **Parity-protected** — BudgetFormulaParityTest guards against drift
