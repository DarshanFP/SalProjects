# Phase 10.2 — Budget Calculation Report Tests

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## File

`tests/Unit/Services/BudgetCalculationServiceReportTest.php`

## Tests

| Test | Strategy | Assertion |
|------|----------|-----------|
| `test_get_budgets_for_report_returns_dp_phase_rows` | DirectMapping (DP) | Current-phase budget row returned |
| `test_get_budgets_for_report_calculates_ilp_amount_sanctioned` | SingleSourceContribution (ILP) | `amount_sanctioned` = 9000 per row (10k − 1k contribution share) |
| `test_get_budgets_for_report_calculates_iies_amount_sanctioned` | MultipleSourceContribution (IIES) | `amount_sanctioned` = 17000 per row |

## Run

```bash
php artisan test tests/Unit/Services/BudgetCalculationServiceReportTest.php
```
