# Phase 1 — Coordinator Query Layer Implementation

**Date:** 2026-03-06  
**Status:** Complete  
**Scope:** Safe refactor — add query layer only; no behavioral changes

---

## 1. Objective

Introduce `ProjectQueryService::forCoordinator(User $coordinator, string $fy)` to centralize coordinator project queries and align the Coordinator dashboard architecture with the Provincial pipeline.

**Purpose:**

- Provide a single entry point for Coordinator project queries
- Delegate access logic to `ProjectAccessService` for consistency
- Prepare the system for Phase 2 (DatasetCacheService) and future dataset caching
- Avoid direct `Project::inFinancialYear($fy)` and `Project::approved()->inFinancialYear($fy)` usage in coordinator code paths

**Non-goals in this phase:**

- No replacement of existing queries in `CoordinatorController` or widgets
- No dataset caching or controller refactoring
- No behavioral changes at runtime

---

## 2. Implementation Summary

A new static method `forCoordinator` was added to `ProjectQueryService`. It delegates to `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)`.

**Behavior:**

- Coordinator has global visibility across all provinces (no user hierarchy, no `getAccessibleUserIds`)
- FY filtering is applied by `ProjectAccessService` when `$fy` is passed
- Resulting query behaves like `Project::inFinancialYear($fy)` for coordinator role, with centralized access control

**Architectural alignment:**

- Mirrors `ProjectQueryService::forProvincial()` pattern
- Uses the same centralized access service for future consistency
- Ensures changes to `ProjectAccessService` propagate automatically to coordinator queries

---

## 3. Code Changes

| File | Change |
|------|--------|
| `app/Services/ProjectQueryService.php` | Added `forCoordinator(User $coordinator, string $fy): Builder` with PHPDoc |

**No other files were modified.** No controller, widget, or model code was changed.

---

## 4. Architecture Alignment

| Aspect | Provincial | Coordinator (Phase 1) |
|--------|------------|------------------------|
| Query method | `ProjectQueryService::forProvincial()` | `ProjectQueryService::forCoordinator()` |
| Access logic | `ProjectAccessService::getAccessibleUserIds()` + `accessibleByUserIds()` | `ProjectAccessService::getVisibleProjectsQuery()` |
| FY scope | `inFinancialYear($fy)` in `ProjectQueryService` | `inFinancialYear($fy)` in `ProjectAccessService` |
| Scope | Province-bound (user hierarchy) | Global (no hierarchy) |

Both roles now use `ProjectQueryService` as the query entry point and rely on `ProjectAccessService` for access rules. The pipeline pattern is consistent:

```
ProjectQueryService::for{Role}() → ProjectAccessService → Builder with FY scope
```

Phase 2 will introduce `DatasetCacheService::getCoordinatorDataset()`, which will consume `ProjectQueryService::forCoordinator()` in the same way `getProvincialDataset()` consumes `forProvincial()`.

---

## 5. Safety Verification

| Check | Result |
|-------|--------|
| Existing method names | No conflict; `forCoordinator` is new |
| Existing query behavior | Unchanged; no existing methods modified |
| Controller code | Not modified |
| Widget code | Not modified |
| Breaking changes | None |

This phase is **non-breaking**. All existing coordinator dashboard logic continues to use direct queries; the new method is additive and unused until Phase 2.

---

## 6. Next Phase

**Phase 2 — Dataset Cache Layer**

- Add `DatasetCacheService::getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection`
- Base implementation on `ProjectQueryService::forCoordinator($coordinator, $fy)`
- Wire cache invalidation for project/report approval, revert, budget sync events

---

## 7. References

- `Coordinator_Dashboard_Implementation_Roadmap.md` — Phase 1 definition
- `Coordinator_Dashboard_Implementation_Feasibility_Audit.md` — Feasibility findings
- `ProjectAccessService::getVisibleProjectsQuery()` — Centralized access logic
