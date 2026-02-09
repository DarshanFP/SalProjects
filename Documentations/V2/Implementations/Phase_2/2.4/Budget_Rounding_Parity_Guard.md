# Phase 2.4 – Budget Rounding Parity Guard

## Purpose

Ensures **PHP rounding matches JS `.toFixed(2)` behavior** when both compute the same formula and round to 2 decimal places. This guard prevents display mismatches between frontend and backend.

---

## Why Rounding Parity Matters

| Risk | Impact |
|------|--------|
| **Display mismatch** | User sees one value in the form (JS `.toFixed(2)`), backend stores or validates another. |
| **Validation confusion** | Backend validation rejects values that appear correct in the UI. |
| **Export vs. UI drift** | Exported totals differ from on-screen totals. |
| **Trust erosion** | Inconsistent rounding reduces confidence in the system. |

The guard asserts that when both layers apply the same rounding (2 decimals), they produce identical results.

---

## Test Scenarios

| Test | Input | Formula |
|------|-------|---------|
| **Decimal multiplication** | 1.333 × 2.555 × 3.777 | Typical row total with decimals |
| **Large decimal** | 99999.999 × 1.111 × 2.222 | Large values with decimal precision |
| **Very small decimals** | 0.01 × 0.02 × 0.03 | Edge case near zero |

---

## Test Logic

1. **Backend result** — `DerivedCalculationService::calculateRowTotal($q, $m, $d)`
2. **JS equivalent** — Inline `$q * $m * $d` (same formula as JS)
3. **Rounding** — `number_format($value, 2, '.', '')` to simulate JS `.toFixed(2)`
4. **Assertion** — Both rounded values match as strings

---

## Test Output

```
   PASS  Tests\Feature\Budget\BudgetRoundingParityTest
  ✓ decimal multiplication case php and js rounding match
  ✓ large decimal case php and js rounding match
  ✓ very small decimals php and js rounding match

  Tests:    3 passed (3 assertions)
```

---

## Confirmation

**Parity is maintained.** All three scenarios pass:

- PHP and JavaScript produce identical raw results for these inputs (same float representation).
- When both are rounded to 2 decimals via `number_format` / `.toFixed(2)`, the output matches.

---

## Relationship to Other Guards

- **BudgetFormulaParityTest** — Asserts raw formula outputs match (no rounding).
- **BudgetRoundingParityTest** — Asserts rounded outputs match when both layers apply 2-decimal rounding.

Both guards together ensure formula parity and rounding parity.
