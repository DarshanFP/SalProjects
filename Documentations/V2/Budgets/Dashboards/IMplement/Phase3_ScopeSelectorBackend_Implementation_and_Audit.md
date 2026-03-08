# Phase-3 Implementation Report

**Date:** 2026-03-04  
**Phase:** Phase 3 — Executor Scope Selector Backend  
**Plan Reference:** Executor_Dashboard_Scope_FY_Phased_Implementation_Plan.md  
**Status:** Complete

---

## 1. Overview

Phase-3 adds backend support for the Executor dashboard scope selector. The controller reads `scope` from the request (default `owned`), fetches a scope-aware approved project dataset, and passes it to budget summary, chart data, and quick stats. Financial aggregation, project-type filters, and widget totals now respect the selected scope (Owned, In-Charge, or Owned + In-Charge). The default scope `owned` preserves existing behaviour.

---

## 2. Scope Handling

| Scope | Description | Dataset source |
|-------|-------------|----------------|
| **owned** | Projects where user is owner | `getApprovedOwnedProjectsForUser` |
| **in_charge** | Projects where user is in-charge (not owner) | `getApprovedInChargeProjectsForUser` |
| **owned_and_in_charge** | Projects where user is owner OR in-charge (single OR query) | `getApprovedProjectsForUserInFinancialYear` |

**Validation:** `$scope` is read from `$request->input('scope', 'owned')` and restricted to `['owned', 'in_charge', 'owned_and_in_charge']`. Invalid values default to `owned`.

---

## 3. Dataset Resolution

**Budget summary dataset:**
```php
$approvedProjectsForSummary = ProjectQueryService::getApprovedProjectsForExecutorScope($user, $scope, $with, $fy);
```

**Project types filter:**
- `owned` → `getOwnedProjectsQuery($user)->inFinancialYear($fy)`
- `in_charge` → `getInChargeProjectsQuery($user)->inFinancialYear($fy)`
- `owned_and_in_charge` → `getProjectsForUserQuery($user)->inFinancialYear($fy)`

**No collection merge:** For `owned_and_in_charge`, `getApprovedProjectsForExecutorScope` delegates to `getApprovedProjectsForUserInFinancialYear`, which uses a single OR-based query via `getProjectsForUserQuery` and `distinct()`, so no merging of owned and in-charge collections occurs.

---

## 4. Financial Resolution

`resolveCollection()` continues to run once per request:

```php
$resolvedFinancials = ProjectFinancialResolver::resolveCollection($approvedProjectsForSummary);
```

The map is passed into `calculateBudgetSummariesFromProjects`, `getChartData`, `getQuickStats`, and `enhanceProjectsWithMetadata`. Because the dataset is scope-aware, the resolved map matches the selected scope.

---

## 5. Controller Method Updates

| Method | Change |
|--------|--------|
| **getChartData** | Added `?string $scope = 'owned'`. Uses `getApprovedProjectsForExecutorScope` for projects and project IDs for monthly expense trends. |
| **getQuickStats** | Added `?string $scope = 'owned'`. Uses scope-aware query for total projects, new projects, and trends; `getApprovedProjectsForExecutorScope` for approved projects and budget totals. |

**Action items and deadlines:** Left on owned scope; not changed in Phase-3.

---

## 6. Compatibility Audit

| Check | Result |
|-------|--------|
| Default behaviour unchanged | Default `scope=owned` matches prior behaviour |
| No duplicate aggregation | Single dataset and single resolve pass; no merge of collections |
| Dynamic FY compatible | `$fy` passed to `getApprovedProjectsForExecutorScope`; FY filtering preserved |
| ProjectQueryService unchanged | No edits to ProjectQueryService |
| FinancialYearHelper unchanged | No edits to FinancialYearHelper |

---

## 7. Risk Assessment

**LOW RISK**

- Default `scope=owned` keeps prior behaviour
- Scope validation restricts values; fallback to `owned`
- `owned_and_in_charge` uses a single OR query; no collection merge
- Optional `$scope` parameter with default `'owned'` keeps backward compatibility

---

## 8. Next Phase Readiness

Phase-3 is complete. Ready for:

**Phase 4 — Scope Selector UI Integration**

- Add scope dropdown to executor dashboard filters
- Preserve `fy` and `scope` across form submissions
- Add hidden inputs for `fy` and `scope` in Project Budgets Overview form
