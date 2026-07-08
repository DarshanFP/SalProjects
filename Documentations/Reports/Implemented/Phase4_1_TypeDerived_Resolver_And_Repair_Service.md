# Phase 4.1 — Type-Derived Resolver & Approved-Project Repair

**Status:** ✅ Implemented  
**Date:** 2026-06-13  
**Plan reference:** [Reporting_System_Phase_Wise_Implementation_Plan.md](../Reporting_System_Phase_Wise_Implementation_Plan.md) § Phase 4

## Problem

For approved individual/IGE projects, `ProjectFinancialResolver::resolve()` and `DirectMappedIndividualBudgetStrategy` intentionally read **stored** `projects.amount_sanctioned` and `opening_balance`. When those columns were never synced at approval, resolver output stays zero — so admin reconciliation (`acceptSuggested`) could not fix data either.

## Solution

### 1. `TypeDerivedFundFieldsInterface`

New interface: `app/Domain/Budget/Strategies/TypeDerivedFundFieldsInterface.php`

Implemented by:
- `DirectMappedIndividualBudgetStrategy` — IIES, IES, ILP, IAH, IGE type tables
- `PhaseBasedBudgetStrategy` — current-phase `project_budgets` + contribution fields

### 2. `ProjectFinancialResolver::resolveTypeDerivedFundFields()`

Computes fund fields from type sources **without** overlaying stale DB sanctioned/opening values. Enforces canonical rule:

`opening_balance = amount_sanctioned + amount_forwarded + local_contribution`

### 3. `BudgetSyncService::repairApprovedProject()`

Explicit write path for approved projects (not gated by sync feature flags). Skips when type-derived `amount_sanctioned` is zero. Logs via `BudgetAuditLogger`.

### 4. `AdminCorrectionService::acceptSuggested()`

Now uses `resolveTypeDerivedFundFields()` instead of `ProjectFundFieldsResolver::resolve()` so admin reconciliation applies type-table values.

### 5. `ApprovedProjectFundFieldAudit`

Shared detection service for audit/repair commands (`zero_stored_sanctioned`, `opening_balance_invariant`, per-field `derived_mismatch:*`).

## Files changed

| File | Change |
|------|--------|
| `TypeDerivedFundFieldsInterface.php` | New |
| `DirectMappedIndividualBudgetStrategy.php` | `resolveFromTypeTables()` extracted |
| `PhaseBasedBudgetStrategy.php` | `resolveFromTypeTables()` added |
| `ProjectFinancialResolver.php` | `resolveTypeDerivedFundFields()` |
| `ApprovedProjectFundFieldAudit.php` | New |
| `BudgetSyncService.php` | `repairApprovedProject()` |
| `AdminCorrectionService.php` | `acceptSuggested()` fix |

## Verification

```bash
php artisan reports:audit-project-fund-fields --project=IOGEP-0006
php artisan reports:repair-project-fund-fields --dry-run --project=IOGEP-0006
```

Expected: derived sanctioned **493,200.00** for IOGEP-0006 (local dev DB).
