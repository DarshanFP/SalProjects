# Phase-1 Implementation Report

**Date:** 2026-03-04  
**Phase:** Phase 1 — Scope Architecture Preparation  
**Plan Reference:** Executor_Dashboard_Scope_FY_Phased_Implementation_Plan.md  
**Status:** Complete

---

## 1. Overview

Phase-1 adds ProjectQueryService methods for in-charge and combined (owned + in-charge) approved datasets with optional FY support. The objective is to prepare the scope architecture for the Executor dashboard without changing ExecutorController behaviour. All changes are additive; existing methods and signatures remain unchanged.

---

## 2. Methods Added

### getApprovedInChargeProjectsForUser

**Signature:**
```php
getApprovedInChargeProjectsForUser(User $user, array $with = [], ?string $financialYear = null): \Illuminate\Database\Eloquent\Collection
```

**Description:** Returns approved projects where the user is in-charge but not owner (`in_charge = user.id` AND `user_id != user.id`). Base query: `getInChargeProjectsQuery($user)`. Supports optional FY filter and eager loading.

---

### getApprovedProjectsForUserInFinancialYear

**Signature:**
```php
getApprovedProjectsForUserInFinancialYear(User $user, array $with = [], ?string $financialYear = null): \Illuminate\Database\Eloquent\Collection
```

**Description:** Returns approved projects where the user is owner OR in-charge, using a single query with `getProjectsForUserQuery($user)` and `->distinct()` to ensure dataset uniqueness. Dataset originates from one OR-based query; no collection merge. Supports optional FY filter and eager loading.

---

### getApprovedProjectsForExecutorScope

**Signature:**
```php
getApprovedProjectsForExecutorScope(User $user, string $scope, array $with = [], ?string $fy = null): \Illuminate\Database\Eloquent\Collection
```

**Description:** Helper that delegates to scope-specific methods based on `$scope`:
- `owned` → `getApprovedOwnedProjectsForUser()`
- `in_charge` → `getApprovedInChargeProjectsForUser()`
- `owned_and_in_charge` → `getApprovedProjectsForUserInFinancialYear()`
- Default fallback: `owned`

---

## 3. Code Implementation Summary

### Approved statuses

All three approved statuses are applied consistently via `ProjectStatus` constants:

- `ProjectStatus::APPROVED_BY_COORDINATOR`
- `ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR`
- `ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL`

These align with `getApprovedOwnedProjectsForUser` and `getApprovedProjectsForUser`.

### FY filter

When `$financialYear` (or `$fy`) is non-null, `->inFinancialYear($financialYear)` is applied to the query. The Project model’s `scopeInFinancialYear` uses `FinancialYearHelper::startDate` and `endDate` to filter on `commencement_month_year`.

### Eager loading

When `$with` is non-empty, `->with($with)` is applied before `->get()`, preserving existing eager-loading behaviour used by ExecutorController (e.g. `['reports', 'reports.accountDetails', 'budgets']`).

---

## 4. Deduplication Safety

For `owned_and_in_charge`, the implementation uses:

1. **Single OR query:** `getProjectsForUserQuery($user)` filters with `(user_id = X OR in_charge = X)`, so the combined scope comes from one query.
2. **distinct():** `->distinct()` ensures each project appears at most once even when the user is both owner and in-charge.

The plan explicitly avoids merging `$owned->merge($inCharge)`. This implementation follows that rule by using the single OR-based query and `distinct()` instead of merging collections.

---

## 5. Compatibility Audit

| Check | Result |
|-------|--------|
| Existing methods unchanged | Confirmed — no changes to `getApprovedProjectsForUser`, `getProjectsForUserQuery`, `getOwnedProjectsQuery`, `getInChargeProjectsQuery`, or any other method |
| Existing method signatures unchanged | Confirmed |
| No controller dependencies introduced | Confirmed — no controller changes; new methods are not yet wired into ExecutorController |
| No query conflicts | Confirmed — new methods call existing query builders and add filters; no overlap with existing call paths |

**Existing ProjectQueryService callers (unchanged):**

- ExecutorController: `getOwnedProjectsQuery`, `getInChargeProjectsQuery`, `getApprovedOwnedProjectsForUser`, `getOwnedProjectIds`, `getProjectIdsForUser`, etc.
- ProjectController: `getProjectsForUserQuery`, `getApprovedProjectsForUser`
- GeneralController: `getProjectsForUsersQuery`, `getProjectIdsForUsers`
- Report controllers: `getProjectIdsForUser`, `getOwnedProjectIds`

All existing usages remain valid; new methods are additive.

---

## 6. Risk Assessment

**LOW RISK**

- Only new methods were added.
- No existing methods or signatures were modified.
- No controller or view changes.
- Backward compatibility preserved.
- PHP syntax validated (`php -l`).
- No linter errors reported.

---

## 7. Next Phase Readiness

Phase-1 is complete. Ready to proceed to:

**Phase 2 — Dynamic FY Infrastructure**

- Add `FinancialYearHelper::listAvailableFYFromProjects()`.
- Derive FY list from project data (`commencement_month_year`).
- Add fallback when the derived list is empty.

Phase-1 provides the scope-aware dataset methods required for Phase 3 (Executor Scope Selector Backend), which will use `getApprovedProjectsForExecutorScope` to fetch the dataset based on the selected scope.

---

## 8. Validation Checklist

| Item | Status |
|------|--------|
| getApprovedInChargeProjectsForUser added | Done |
| getApprovedProjectsForUserInFinancialYear added | Done |
| getApprovedProjectsForExecutorScope added | Done |
| Deduplication: single OR query for owned_and_in_charge | Done |
| Deduplication: distinct() applied | Done |
| No collection merge | Done |
| Syntax check | Passed |
| Linter | No errors |
| Existing methods unchanged | Confirmed |
| No controller modifications | Confirmed |
