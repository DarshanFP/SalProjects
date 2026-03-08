# Phase 2 — Coordinator Dataset Cache Implementation

**Date:** 2026-03-06  
**Status:** Complete  
**Scope:** Safe refactor — add dataset cache layer only; no controller or widget changes

---

## 1. Objective

Introduce a dataset cache layer for the Coordinator dashboard so that:

- All coordinator project data for a given FY can be loaded once and reused by widgets (in later phases)
- Repeated heavy database queries are reduced via a 10-minute cache
- The architecture aligns with the Provincial pipeline: `ProjectQueryService` → dataset retrieval → cache

**Purpose of dataset caching:**

- Single source of truth per request (or cache hit) for coordinator projects in an FY
- Preparation for Phase 4 (province partitioning) and Phase 6 (widget refactor to consume shared dataset)
- Consistent pattern with `DatasetCacheService::getProvincialDataset()`

**Non-goals in this phase:**

- No changes to `CoordinatorController` or any widget
- No wiring of `clearCoordinatorDataset` to events (documented for later)
- No behavioral change at runtime until the controller is refactored to use the new method

---

## 2. Implementation Summary

### getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection

- **Base query:** `ProjectQueryService::forCoordinator($coordinator, $fy)` (Phase 1)
- **Projection:** Same lightweight `$select` as Provincial (id, project_id, province_id, society_id, project_type, user_id, in_charge, commencement_month_year, opening_balance, amount_sanctioned, amount_forwarded, local_contribution, overall_project_budget, status, current_phase, project_title)
- **Eager load:** `user`, `reports.accountDetails`, `budgets`
- **Cache:** Key `coordinator_dataset_{$fy}`, TTL 10 minutes, `Cache::remember()`
- **Filters:** Optional `$filters` (province, center, role, parent_id, project_type) applied in-memory after cache/query so the cache key stays FY-only and invalidation remains simple

### clearCoordinatorDataset(string $fy): void

- **Implementation:** `Cache::forget("coordinator_dataset_{$fy}")`
- **Future wiring:** Project approval/revert, report approval/revert, budget sync (same pattern as Provincial)

---

## 3. Code Changes

| File | Change |
|------|--------|
| `app/Services/DatasetCacheService.php` | Added `getCoordinatorDataset()`, `applyCoordinatorFilters()`, `clearCoordinatorDataset()`; added `User` and `Collection` imports; class docblock updated for Phase 2 |

**No other files were modified.** Provincial logic, controllers, and views are unchanged.

---

## 4. Dataset Architecture

1. **Entry:** `DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)`
2. **Cache lookup:** Key `coordinator_dataset_{$fy}`; on miss, run base query and store for 10 minutes.
3. **Base query:** `ProjectQueryService::forCoordinator($coordinator, $fy)` → `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` (global scope, FY applied).
4. **Query shape:** `select($select)->with(['user', 'reports.accountDetails', 'budgets'])->get()`.
5. **Optional filters:** If `$filters` is non-empty, `applyCoordinatorFilters()` filters the collection in-memory by:
   - `province` → `project.province_id`
   - `project_type` → `project.project_type`
   - `center` → `project->user->center`
   - `role` → `project->user->role`
   - `parent_id` → `project->user->parent_id`  
   Values can be scalar or array (whereIn-style).
6. **Return:** Eloquent/Support `Collection` of `Project` models with relations loaded, ready for resolver and widgets in later phases.

---

## 5. Cache Strategy

| Aspect | Value |
|--------|--------|
| Key | `coordinator_dataset_{$fy}` (e.g. `coordinator_dataset_2025-26`) |
| TTL | 10 minutes (`now()->addMinutes(10)`) |
| Scope | Per FY; no filter hash in key |
| Invalidation | `clearCoordinatorDataset($fy)` — single key per FY |

Filters are applied after cache/query so one cache entry serves all filter combinations and invalidation does not require multiple keys or tags.

---

## 6. Safety Verification

| Check | Result |
|-------|--------|
| Provincial dataset logic | Unchanged |
| getProvincialDataset / clearProvincialDataset | Unchanged |
| CoordinatorController | Not modified |
| Widgets | Not modified |
| New code only | Additive; no existing method signatures or behavior changed |

Phase 2 is **non-breaking**. The new methods are unused until the controller is refactored to call `getCoordinatorDataset()` in a later phase.

---

## 7. Next Phase

**Phase 3 — Lightweight Dataset Projection Alignment**

- Confirm `getCoordinatorDataset` uses the same `$select` (and `$with`) as `getProvincialDataset` (already done in Phase 2)
- No new logic; validation/alignment only

Phase 4 will then introduce province partitioning and shared dataset consumption by widgets.

---

## 8. References

- `Coordinator_Dashboard_Implementation_Roadmap.md` — Phase 2 definition and completion note
- `Phase1_QueryLayer_Implementation.md` — `ProjectQueryService::forCoordinator()`
- `app/Services/ProjectAccessService.php` — `getVisibleProjectsQuery()`
- Provincial: `Phase3_3_DatasetCacheLayer_Implementation.md`, `DatasetCacheService::getProvincialDataset()`
