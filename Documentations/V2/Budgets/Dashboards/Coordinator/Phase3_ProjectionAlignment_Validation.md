# Phase 3 — Projection Alignment Validation

**Date:** 2026-03-06  
**Status:** Validated  
**Scope:** Validation only — no code changes

---

## 1. Objective

Verify that the Coordinator dataset projection (`DatasetCacheService::getCoordinatorDataset`) matches the Provincial dataset projection (`getProvincialDataset`) and that both are compatible with:

- `ProjectFinancialResolver::resolve()` and `resolveCollection()`
- Coordinator dashboard widget calculations
- Memory-efficient payload (reduced compared to full model load)

---

## 2. Validation Steps Executed

### STEP 1 — getCoordinatorDataset() Inspection

**File:** `app/Services/DatasetCacheService.php` (lines 102–119)

**Select fields:** 16 columns

| # | Field |
|---|-------|
| 1 | id |
| 2 | project_id |
| 3 | province_id |
| 4 | society_id |
| 5 | project_type |
| 6 | user_id |
| 7 | in_charge |
| 8 | commencement_month_year |
| 9 | opening_balance |
| 10 | amount_sanctioned |
| 11 | amount_forwarded |
| 12 | local_contribution |
| 13 | overall_project_budget |
| 14 | status |
| 15 | current_phase |
| 16 | project_title |

**Eager loads:** `['user', 'reports.accountDetails', 'budgets']`

---

### STEP 2 — getProvincialDataset() Comparison

**File:** `app/Services/DatasetCacheService.php` (lines 33–50)

**Select fields:** Identical 16 columns (same order, same names).

**Eager loads:** Identical — `['user', 'reports.accountDetails', 'budgets']`.

**Conclusion:** Coordinator and Provincial projections are aligned.

---

### STEP 3 — Resolver Compatibility

**File:** `app/Domain/Budget/ProjectFinancialResolver.php`

#### Required by `resolve()` and strategies

| Field/Relation | In Projection | Used By |
|----------------|---------------|---------|
| project_id | ✓ | Map key in `resolveCollection()` |
| status | ✓ | `isApproved()`, `BudgetSyncGuard::isApproved()` |
| amount_sanctioned | ✓ | Canonical separation, strategies |
| amount_forwarded | ✓ | PhaseBasedBudgetStrategy, fallback |
| local_contribution | ✓ | PhaseBasedBudgetStrategy, fallback |
| overall_project_budget | ✓ | PhaseBasedBudgetStrategy, fallback |
| opening_balance | ✓ | Canonical separation |
| current_phase | ✓ | PhaseBasedBudgetStrategy |
| project_type | ✓ | Strategy selection, DirectMappedIndividual |
| budgets | ✓ (eager) | PhaseBasedBudgetStrategy |

#### resolveCollection() PHPDoc

> Projects must have reports, reports.accountDetails, budgets eager-loaded.

**Eager loads:** `user`, `reports.accountDetails`, `budgets` — all present in both datasets.

**DirectMappedIndividualBudgetStrategy:** Uses `loadMissing()` for type-specific relations (iiesExpenses, iesExpenses, ilpBudget, etc.). These are loaded on demand; not part of the shared projection. Fallback uses projected fields only.

**Verdict:** Resolver compatible with the Coordinator projection.

---

### STEP 4 — Widget Compatibility

| Widget Method | Required Project Fields/Relations | In Projection |
|---------------|-----------------------------------|---------------|
| calculateBudgetSummariesFromProjects | project_type, user, reports.accountDetails, project_id | ✓ |
| getSystemPerformanceData | project_id, status, user->province, budgets | ✓ |
| getSystemAnalyticsData | project_id, status, user, user.parent, budgets | ✓ (user loads parent via relation) |
| getSystemBudgetOverviewData | project_type, user->province, reports, budgets | ✓ |
| getProvinceComparisonData | project_id, status, user->province | ✓ |
| getProvincialManagementData | user_id, status, user, user.parent | ✓ |
| getSystemHealthData | project_id, status, user, budgets | ✓ |

**Note:** `user.parent` is loaded via the `user` relation. The `user` relation is eager-loaded; `user.parent` will be loaded when accessed (or can be added to `$with` if needed — Provincial uses `user.parent` in some controller paths but the dataset uses `['user', 'reports.accountDetails', 'budgets']`). Current Provincial dataset does not include `user.parent` in `$with`; Coordinator matches. If widgets use `user->parent` and it is not loaded, Laravel will lazy-load it. No projection mismatch.

**Verdict:** Widget requirements are satisfied.

---

### STEP 5 — Memory Impact

**Project model:** Contains many additional columns (created_at, updated_at, deleted_at, timestamps, soft deletes, and various nullable/text columns).

**Projection:** 16 selected columns vs full model. Typical full Project row can include 30+ columns. The projection:

- Excludes timestamps, soft-delete columns, and long text fields where not needed
- Keeps all financial and status fields required by resolver and widgets
- Keeps identifiers (id, project_id) and relation keys (user_id, in_charge, province_id, society_id)

**Verdict:** Projection reduces payload compared to full model load.

---

## 3. Projection Comparison Summary

| Aspect | Provincial | Coordinator | Match |
|--------|------------|-------------|-------|
| Select fields | 16 columns | 16 columns | ✓ |
| Field list | id, project_id, province_id, society_id, project_type, user_id, in_charge, commencement_month_year, opening_balance, amount_sanctioned, amount_forwarded, local_contribution, overall_project_budget, status, current_phase, project_title | Same | ✓ |
| Eager loads | user, reports.accountDetails, budgets | user, reports.accountDetails, budgets | ✓ |

---

## 4. Resolver Compatibility Summary

| Requirement | Status |
|-------------|--------|
| project_id | ✓ In projection |
| opening_balance | ✓ In projection |
| amount_sanctioned | ✓ In projection |
| amount_forwarded | ✓ In projection |
| local_contribution | ✓ In projection |
| overall_project_budget | ✓ In projection |
| status (for isApproved) | ✓ In projection |
| current_phase | ✓ In projection |
| project_type | ✓ In projection |
| budgets relation | ✓ Eager loaded |
| reports.accountDetails | ✓ Eager loaded |

---

## 5. Final Verdict

**PASS — No code changes required.**

- Coordinator and Provincial projections are identical.
- Resolver requirements are met.
- Widget requirements are met.
- Projection reduces payload vs full model.
- Phase 3 validation is complete; safe to proceed to Phase 4 (Province Partitioned Dataset).
