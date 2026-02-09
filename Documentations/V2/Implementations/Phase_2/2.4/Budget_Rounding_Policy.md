# Phase 2.4 – Budget Rounding Policy

## Goal

Ensure all monetary outputs from `DerivedCalculationService` are rounded to 2 decimal places consistently.

## Why Rounding Was Centralized

Centralizing rounding in `DerivedCalculationService` would:

- **Guarantee consistency** — All monetary values returned by the service would conform to 2-decimal precision.
- **Avoid display drift** — No reliance on disparate `.toFixed(2)` calls in controllers or views.
- **Single source of truth** — Rounding policy lives in one place.

## Attempted Implementation

### Changes Made (since reverted)

1. **Added private method**:

   ```php
   private function roundMoney(float $value): float
   {
       return round($value, 2);
   }
   ```

2. **Applied rounding to final return values only** in:
   - `calculateRowTotal()`
   - `calculatePhaseTotal()`
   - `calculateProjectTotal()`
   - `calculateRemainingBalance()`

3. **No intermediate rounding** — Only the final return value of each method was rounded.

### Confirmation: No Logic Changed

- Formula logic was unchanged: `rowTotal = quantity × multiplier × duration`, etc.
- No controllers, models, or database were modified.
- Only `DerivedCalculationService` was modified.

## Parity Drift and Revert

### Result

Adding rounding in `DerivedCalculationService` caused **parity drift** with `BudgetFormulaParityTest`.

### Test Results Summary

| Test | Result |
|------|--------|
| All Budget tests | Pass |
| `BudgetFormulaParityTest::test_decimals_js_and_backend_produce_identical_results` | **Fail** |

### Cause

- **Backend (with rounding)** returns `8.24` for inputs `[1.111, 2.222, 3.333]`, `[0.1, 0.2, 0.3]`.
- **JS-equivalent** (parity test simulates JS) returns `8.233983786000001` (raw, unrounded).
- Assertion `assertEqualsWithDelta($jsTotal, $backendTotal, 0.000001)` fails because the difference exceeds the tolerance.

### Policy Applied

Per implementation rules: **STOP if any parity drift appears.**

Rounding was **reverted** in `DerivedCalculationService` to restore parity. The codebase is back to unrounded service outputs.

## Current State

- **`DerivedCalculationService`** — No rounding. Returns raw calculated values.
- **Display** — Views use `.toFixed(2)` for display formatting.
- **Parity** — All tests pass, including `BudgetFormulaParityTest`.

## Future Path to Rounding

To introduce rounding without breaking parity:

1. **Update JS module** — `budget-calculations.js` must round its outputs to 2 decimals (e.g. `round(value, 2)` or equivalent).
2. **Update parity test** — `computeJsEquivalent()` must apply the same rounding before comparison.
3. **Re-apply backend rounding** — Add `roundMoney()` back to `DerivedCalculationService` and round final return values.

All three layers (JS, backend, parity test) must share the same rounding policy for parity to hold.
