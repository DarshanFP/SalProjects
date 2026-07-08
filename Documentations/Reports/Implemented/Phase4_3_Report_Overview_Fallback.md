# Phase 4.3 — Report SOA Overview Fallback

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

`ReportController::create()` and `edit()` passed `$project->amount_sanctioned` directly to views. Approved individual/IGE projects with unsynced DB columns showed **Rs. 0.00** in SOA overview (`amount_sanctioned_overview` default).

## Solution

Private helper on `ReportController`:

```php
resolveReportOverviewSanctioned(Project $project): float
```

Logic:
1. If stored `amount_sanctioned > 0` → use stored
2. If project not approved → use stored (may be 0; pre-approval uses requested elsewhere)
3. If approved and stored = 0 → `ProjectFinancialResolver::resolveTypeDerivedFundFields()` fallback
4. Logs `Report overview using type-derived sanctioned amount (stored was zero)` at WARNING level

Applied in:
- `create()` — new monthly report form
- `edit()` — existing report edit; `showBudgetDiscrepancyNote` now compares report overview vs **resolved** overview (not raw DB)

Views unchanged — they already bind `$amountSanctioned` to `amount_sanctioned_overview` inputs.

## Permanent fix

Run repair command (Phase 4.2) so DB matches type tables; fallback remains defensive for any future drift.

## Files

- `app/Http/Controllers/Reports/Monthly/ReportController.php`

## Manual test

1. Open report create for IOGEP-0006 (before repair): overview should show **493,200.00** (not 0)
2. After `reports:repair-project-fund-fields --project=IOGEP-0006`: overview should match stored column
