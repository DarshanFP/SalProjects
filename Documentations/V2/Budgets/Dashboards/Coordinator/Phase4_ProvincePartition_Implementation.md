# Phase 4 — Province Partitioned Dataset Implementation

**Date:** 2026-03-06  
**Status:** Complete  
**Objective:** Build province partitions once per request and pass them to coordinator dashboard widgets to eliminate repeated `groupBy` operations.

---

## 1. Objective

Introduce province-level dataset partitioning so widgets can reuse grouped datasets instead of repeatedly performing `groupBy()`. Build partitions once per request and pass them to the four affected widgets.

---

## 2. Implementation Summary

| Component | Change |
|-----------|--------|
| **coordinatorDashboard** | Switched to `DatasetCacheService::getCoordinatorDataset`, `ProjectFinancialResolver::resolveCollection`, loads reports once, builds partitions once |
| **getSystemPerformanceData** | Now receives `$teamProjects`, `$resolvedFinancials`, `$projectsByProvince`, `$reportsByProvince`; removed internal groupBy and cache |
| **getSystemAnalyticsData** | Now receives `$teamProjects`, `$resolvedFinancials`, `$timeRange`, `$projectsByProvince`, `$approvedProjectsByProvince`, `$reportsByProvince`; removed internal groupBy and cache |
| **getSystemBudgetOverviewData** | Now receives `$request`, `$teamProjects`, `$resolvedFinancials`, `$approvedProjectsByProvince`; derives approved/pending from teamProjects |
| **getProvinceComparisonData** | Now receives `$teamProjects`, `$resolvedFinancials`, `$projectsByProvince`, `$reportsByProvince`; removed internal groupBy and cache |
| **DatasetCacheService** | Extended `applyCoordinatorFilters` to support `province` as `user.province` (string) for coordinator UI filters |

---

## 3. Code Changes

### 3.1 CoordinatorController::coordinatorDashboard

- Build filters from request (`province`, `center`, `role`, `parent_id`, `project_type`)
- `$teamProjects = DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)`
- `$resolvedFinancials = ProjectFinancialResolver::resolveCollection($teamProjects)`
- `$allReports = DPReport::whereIn('project_id', $teamProjects->pluck('project_id'))->with('user')->get()`
- Build partitions:
  - `$projectsByProvince`
  - `$reportsByProvince`
  - `$approvedProjectsByProvince`
- Derive `$projects` and `$allProjects` from `$teamProjects`
- Pass partitions to the four widgets

### 3.2 DatasetCacheService::applyCoordinatorFilters

- Extended province filter: when value is non-numeric (string), compare against `user.province` instead of `project.province_id` to align with coordinator UI filters

### 3.3 Widget Method Signatures

| Method | New Signature |
|--------|---------------|
| getSystemPerformanceData | `(Collection $teamProjects, array $resolvedFinancials, Collection $projectsByProvince, Collection $reportsByProvince)` |
| getSystemAnalyticsData | `(Collection $teamProjects, array $resolvedFinancials, $timeRange, Collection $projectsByProvince, Collection $approvedProjectsByProvince, Collection $reportsByProvince)` |
| getSystemBudgetOverviewData | `($request, Collection $teamProjects, array $resolvedFinancials, Collection $approvedProjectsByProvince)` |
| getProvinceComparisonData | `(Collection $teamProjects, array $resolvedFinancials, Collection $projectsByProvince, Collection $reportsByProvince)` |

---

## 4. Partition Architecture

```
$teamProjects (from DatasetCacheService::getCoordinatorDataset)
    |
    +-> $resolvedFinancials = ProjectFinancialResolver::resolveCollection($teamProjects)
    |
    +-> $allReports = DPReport::whereIn(...)->get()
    |
    +-> Partitions (immutable):
        - $projectsByProvince = $teamProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown')
        - $reportsByProvince = $allReports->groupBy(fn($r) => $r->user->province ?? 'Unknown')
        - $approvedProjectsByProvince = $teamProjects->filter(isApproved)->groupBy(province)
```

---

## 5. Widget Signature Changes

- **getSystemPerformanceData:** Uses provided `$projectsByProvince` and `$reportsByProvince`; removed internal `groupBy`
- **getSystemAnalyticsData:** Uses provided partitions; `$projectsByType` still derived from `$allApprovedProjects` (not a province partition)
- **getSystemBudgetOverviewData:** Uses `$approvedProjectsByProvince` for the "Budget by Province" loop; derives `$approvedProjects` and `$pendingProjects` from `$teamProjects`
- **getProvinceComparisonData:** Uses provided `$projectsByProvince` and `$reportsByProvince`

---

## 6. Immutability

- Partitions are derived from `$teamProjects` and must be treated as immutable collections
- Widgets must not modify these collections (no `transform()`, `push()`, etc.)
- Documentation added in `coordinatorDashboard` and widget PHPDoc blocks

---

## 7. Safety Verification

| Check | Status |
|-------|--------|
| Province metrics match previous behavior | Yes — same groupBy key (`$p->user->province ?? 'Unknown'`), same aggregation logic |
| No duplicate groupBy in affected widgets | Yes — removed from getSystemPerformanceData, getSystemAnalyticsData, getProvinceComparisonData; getSystemBudgetOverviewData uses provided partition |
| Partitions built once per request | Yes — built in coordinatorDashboard before widget calls |
| Resolver results correct | Yes — single `resolveCollection($teamProjects)`; map passed to all widgets |

---

## 8. Next Phase — Resolver Batch Optimization (Phase 5)

Phase 4 already uses `ProjectFinancialResolver::resolveCollection($teamProjects)` once per request and passes `$resolvedFinancials` to all four widgets. The per-project resolver loops inside these widgets have been removed in favor of the pre-resolved map.

Phase 5 will focus on:

- Passing `$resolvedFinancials` to remaining widgets (getProvincialManagementData, getSystemHealthData) if they still use per-project resolution
- Ensuring all coordinator dashboard flows use the shared resolved map
- Removing any remaining per-project `$resolver->resolve($project)` calls

---

## 9. Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/CoordinatorController.php` | coordinatorDashboard refactor, widget signatures, partition flow |
| `app/Services/DatasetCacheService.php` | Extended `applyCoordinatorFilters` for `user.province` (string) |
