# Coordinator Dashboard Performance Architecture Audit

**Date:** 2026-03-06  
**Scope:** Coordinator role — deep performance and architectural audit  
**Objective:** Identify performance risks, anti-patterns, and design a scalable Coordinator dashboard architecture reusing the optimized Provincial pipeline.  
**Mode:** Audit only — no code modifications.

---

## Executive Summary

The Coordinator dashboard provides **global oversight** across all provinces. The current implementation exhibits significant **architectural anti-patterns** that limit scalability:

- **ProjectQueryService:** Not used — all project queries bypass the centralized query layer.
- **DatasetCacheService:** Not used — no dataset caching layer.
- **resolveCollection():** Not used — per-project resolver execution in **8+ locations**, causing N× resolver calls where N = project count.
- **Duplicate queries:** At least **12 independent project queries** per dashboard load across 9 widget methods; the same FY-scoped dataset is fetched repeatedly.
- **Dashboard cache:** Widget-level cache only (2–15 min TTL); no full dashboard cache equivalent to Provincial Phase 6.

**Recommendation:** Implement the same optimized pipeline as Provincial: `ProjectQueryService::forCoordinator()` → `DatasetCacheService::getCoordinatorDataset()` → lightweight projection → `resolveCollection()` → province-level aggregation → dashboard cache. Estimated impact: **75–85% reduction** in project queries, **~80% reduction** in resolver executions, and **10–30×** improvement on cache hits.

---

## 1. Current Coordinator Architecture

### 1.1 Controller & Entry Points

| Route | Controller | Method | Purpose |
|-------|------------|--------|---------|
| `/coordinator/dashboard` | CoordinatorController | `coordinatorDashboard()` | Main dashboard |
| `/coordinator/projects-list` | CoordinatorController | `projectList()` | Paginated project list |
| `/coordinator/approved-projects` | CoordinatorController | `approvedProjects()` | Approved projects view |
| `/coordinator/budgets` | CoordinatorController | `projectBudgets()` | Budget view |
| `/coordinator/budget-overview` | CoordinatorController | `budgetOverview()` | Budget overview page |
| `/coordinator/report-list` | CoordinatorController | `reportList()` | Report list |
| `/coordinator/report-list/pending` | CoordinatorController | `pendingReports()` | Pending reports |
| `/coordinator/report-list/approved` | CoordinatorController | `approvedReports()` | Approved reports |

### 1.2 Data Flow (Current)

```
coordinatorDashboard()
  ├─ Project::approved()->inFinancialYear($fy)     [Query 1]
  ├─ Project::inFinancialYear($fy)                 [Query 2 - statistics]
  ├─ calculateBudgetSummariesFromProjects()        [uses projects; resolver per project]
  ├─ getPendingApprovalsData($fy)                  [Query 3-4: Project + DPReport]
  ├─ getProvincialOverviewData($fy)                [Queries 5+ per provincial]
  ├─ getSystemPerformanceData($fy)                 [Query: Project::inFinancialYear]
  ├─ getSystemAnalyticsData($fy, $timeRange)       [Query: Project::inFinancialYear]
  ├─ getSystemBudgetOverviewData($request)         [Queries: approved + notApproved]
  ├─ getProvinceComparisonData($fy)                [Query: Project::inFinancialYear]
  ├─ getProvincialManagementData($fy)              [Similar queries]
  ├─ getSystemHealthData($fy)                      [Query: Project::inFinancialYear]
  └─ getSystemActivityFeedData($fy, $limit)        [Query: Project::inFinancialYear pluck]
```

### 1.3 Pipeline Comparison

| Layer | Provincial (Optimized) | Coordinator (Current) |
|-------|------------------------|------------------------|
| Query | ProjectQueryService::forProvincial | Direct `Project::` |
| Dataset | DatasetCacheService::getProvincialDataset | None — per-widget fetch |
| Projection | Lightweight select | Full model |
| Resolver | resolveCollection() once | Per-project in loops |
| Aggregation | Shared dataset + map | Per-widget independent |
| Cache | Full dashboard cache | Widget-level only |

---

## 2. Coordinator Query Analysis

### 2.1 Project Access Scope

| Service | Method | Coordinator Behavior |
|---------|--------|----------------------|
| ProjectAccessService | `getVisibleProjectsQuery($coordinator, $fy)` | Returns unfiltered `Project::query()` with `inFinancialYear($fy)` — global scope |
| ProjectAccessService | `getAccessibleUserIds($coordinator)` | **Not used** — coordinator has no hierarchy |
| ProjectQueryService | `forProvincial()` | **Not used** — no `forCoordinator()` exists |

### 2.2 Project Query Inventory (CoordinatorController)

| Location | Line(s) | Query Pattern | ProjectQueryService |
|----------|---------|---------------|---------------------|
| coordinatorDashboard | 59 | `Project::approved()->inFinancialYear($fy)->with('user')` | No |
| coordinatorDashboard | 110 | `Project::approved()->inFinancialYear($fy)->distinct()->pluck('project_type')` | No |
| coordinatorDashboard | 113 | `Project::inFinancialYear($fy)->with('user')` | No |
| projectList | 483 | `projectAccessService->getVisibleProjectsQuery()` | No (uses PAS, not PQS) |
| projectBudgets | 1243 | `Project::approved()->inFinancialYear($fy)->with('user')` | No |
| projectBudgets | 1285 | `Project::approved()->inFinancialYear($fy)->distinct()->pluck()` | No |
| getPendingApprovalsData | 1484 | `Project::inFinancialYear($fy)->pluck('project_id')` | No |
| getPendingApprovalsData | 1507 | `Project::where('status', FORWARDED)->inFinancialYear($fy)` | No |
| getProvincialOverviewData | 1587, 1597, 1617 | Per-provincial: `Project::whereIn()->inFinancialYear()` | No |
| getSystemPerformanceData | 1674 | `Project::inFinancialYear($fy)->with(...)->get()` | No |
| getSystemAnalyticsData | 1764 | `Project::inFinancialYear($fy)->with(...)->get()` | No |
| getSystemBudgetOverviewData | 2021 | `Project::approved()->inFinancialYear($fy)` | No |
| getSystemBudgetOverviewData | 2058 | `Project::notApproved()->inFinancialYear($fy)` | No |
| getProvinceComparisonData | 2342 | `Project::inFinancialYear($fy)->with(...)->get()` | No |
| getProvincialManagementData | 2445 | `Project::inFinancialYear($fy)->with(...)->get()` | No |
| getSystemHealthData | 2565 | `Project::inFinancialYear($fy)->with(...)->get()` | No |
| getSystemActivityFeedData | 1918 | `Project::inFinancialYear($fy)->pluck('project_id')` | No |
| budgetOverview | 2708, 2712 | `Project::approved()->...->whereIn()` | No |

### 2.3 Queries That Should Use ProjectQueryService::forCoordinator()

All project queries that scope by FY and optional filters should be replaced with:

```php
ProjectQueryService::forCoordinator($coordinator, $fy)
    ->approved()   // or ->notApproved() as needed
    ->with([...])
    ->get();
```

---

## 3. Duplicate Query Detection

### 3.1 Same Dataset Fetched Multiple Times

| Dataset | Fetched In | Count |
|---------|------------|-------|
| All FY projects (`Project::inFinancialYear($fy)`) | getSystemPerformanceData, getSystemAnalyticsData, getProvinceComparisonData, getProvincialManagementData, getSystemHealthData | **5×** |
| Approved FY projects | coordinatorDashboard, getSystemBudgetOverviewData, projectBudgets, budgetOverview | **4×** |
| Pending FY projects | getPendingApprovalsData, getSystemBudgetOverviewData | **2×** |
| FY project IDs only | getPendingApprovalsData, getSystemActivityFeedData | **2×** |

### 3.2 Per-Request Query Multiplicity (coordinatorDashboard)

For a single coordinator dashboard load with default FY:

| Phase | Queries |
|-------|---------|
| Main dashboard projects | 2 (approved + all) |
| Filter options | 1 (project_types) |
| getPendingApprovalsData | 2+ (pluck + pending projects + DPReport) |
| getProvincialOverviewData | N+2 (N provincials × project/report queries) |
| getSystemPerformanceData | 2 (projects + reports) |
| getSystemAnalyticsData | 2 (projects + reports) |
| getSystemBudgetOverviewData | 4+ (approved + pending + multiple DPReport) |
| getProvinceComparisonData | 3 (projects + reports + User) |
| getProvincialManagementData | 2 (projects + reports) |
| getSystemHealthData | 2 (projects + reports) |
| getSystemActivityFeedData | 2 (pluck + ActivityHistory) |

**Total:** ~25+ project/report queries per request (excluding DPReport/DPAccountDetail sub-queries).

### 3.3 Anti-Pattern Summary

- **Same project query executed in multiple widget methods:** Yes — `Project::inFinancialYear($fy)->get()` appears in 5 widgets.
- **Same dataset fetched multiple times per request:** Yes — FY projects loaded independently by each widget.
- **Same query executed for different widgets:** Yes — approved projects, pending projects, and FY-scoped projects are each fetched 2–5 times.

---

## 4. Resolver Misuse Detection

### 4.1 Per-Project Resolution Locations

| Location | Line | Pattern | Loop Size |
|----------|------|---------|-----------|
| calculateBudgetSummariesFromProjects | 297 | `$resolver->resolve($project)` in foreach | N (approved projects) |
| projectList map | 582 | `$resolver->resolve($project)` per paginated project | 100/page |
| getSystemBudgetOverviewData | 2082-2085 | `foreach ($approvedProjects) { $resolver->resolve($project) }` | N (approved) |
| getSystemBudgetOverviewData | 2089 | `$pendingProjects->sum(fn ($p) => $resolver->resolve($p)['amount_requested'])` | M (pending) |
| getProvinceComparisonData | 2346-2348 | `foreach ($allProjects) { $resolver->resolve($project) }` | N (all FY) |
| getSystemAnalyticsData | 1769-1771 | `foreach ($allProjects) { $resolver->resolve($project) }` | N (all FY) |
| getProvincialManagementData | 2449-2451 | Same loop | N (all FY) |
| getSystemHealthData | 2569-2571 | Same loop | N (all FY) |
| budgetOverview (projectBudgets) | 2738 | In top projects loop | 10 |

### 4.2 resolveCollection() Usage

**CoordinatorController:** `resolveCollection()` is **never** used.

### 4.3 Resolver Call Count (Per Dashboard Load)

Assuming 500 projects in FY:

| Method | Resolver Calls |
|--------|----------------|
| calculateBudgetSummariesFromProjects | ~400 (approved) |
| getSystemBudgetOverviewData | ~400 (approved) + ~100 (pending) |
| getProvinceComparisonData | 500 |
| getSystemAnalyticsData | 500 |
| getProvincialManagementData | 500 |
| getSystemHealthData | 500 |
| **Total (widgets only)** | **~2,900** |

With `resolveCollection()`: **1 call** (500 resolves in single batch). **Reduction: ~99.97%.**

---

## 5. Dataset Reuse Opportunities

### 5.1 Current Anti-Pattern

**Multiple widgets execute independent project queries** — no shared dataset.

### 5.2 Provincial Pattern (Target)

```php
$teamProjects = DatasetCacheService::getProvincialDataset($provincial, $fy);
$resolvedFinancials = ProjectFinancialResolver::resolveCollection($teamProjects);
// Pass $teamProjects + $resolvedFinancials to all widget methods
```

### 5.3 Coordinator Reuse Opportunity

| Widget | Could Use Shared Dataset | Notes |
|--------|--------------------------|-------|
| calculateBudgetSummariesFromProjects | Yes | Filter approved from teamProjects |
| getSystemBudgetOverviewData | Yes | Approved + pending subsets |
| getProvinceComparisonData | Yes | Group by province from teamProjects |
| getSystemPerformanceData | Yes | Same dataset |
| getSystemAnalyticsData | Yes | Same dataset |
| getProvincialManagementData | Yes | Same dataset |
| getSystemHealthData | Yes | Same dataset |

**Exception:** getPendingApprovalsData and getSystemActivityFeedData may require real-time data; consider separate fetch or short TTL cache.

### 5.4 DatasetCacheService Support

`DatasetCacheService` currently has only `getProvincialDataset()`. A `getCoordinatorDataset($coordinator, $fy, $filters)` method can be added with the same pattern: query → lightweight select → cache.

---

## 6. DatasetCacheService Compatibility

### 6.1 Current Implementation

- **Scope:** Provincial only — `getProvincialDataset($provincial, $fy)`
- **Source:** `ProjectQueryService::forProvincial($provincial, $fy)` → `Project::accessibleByUserIds($ids)->inFinancialYear($fy)`
- **Cache key:** `provincial_dataset_{$provincial->id}_{$fy}`

### 6.2 Coordinator Scope Options

| Option | Scope | Cache Key | Use Case |
|--------|-------|-----------|----------|
| **A) Global** | All projects in FY | `coordinator_dataset_{$fy}` | All coordinators see same data |
| **B) Per-coordinator** | Same as A (coordinator = global) | `coordinator_dataset_{$coordinator->id}_{$fy}` | Future: coordinator-specific filters |
| **C) Filtered** | Apply request filters to query | `coordinator_dataset_{$fy}_{$filterHash}` | Province, center, role, etc. |

**Recommendation:** Option C — include filter hash for consistency with `getSystemBudgetOverviewData` cache key. Base dataset can be global; filters applied in query or in-memory.

### 6.3 Proposed API

```php
DatasetCacheService::getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null): Collection
```

- Uses `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` as base.
- Applies filters (province, center, role, parent_id, project_type) when provided.
- Same lightweight select as provincial; eager-load user, reports.accountDetails, budgets.
- Cache key: `coordinator_dataset_{$fy}_{md5(json_encode($filters ?? []))}`

---

## 7. Province Aggregation Design

### 7.1 Current Approach

- **Grouping key:** `$project->user->province` (string on users table)
- **Method:** `$projects->groupBy(fn($p) => $p->user->province ?? 'Unknown')`
- **Metrics:** Budget (resolver opening_balance), expenses (DPAccountDetail sum), utilization, approval rate, counts

### 7.2 Aggregation Structure

| Aggregation | Source | Computation |
|-------------|--------|-------------|
| Projects per province | Shared dataset | `$dataset->groupBy(...)->map->count()` |
| Budget per province | Resolved map | `$group->sum(fn($p) => $resolvedFinancials[$p->project_id]['opening_balance'])` |
| Expenses per province | DPAccountDetail | Batch query by report IDs; group by project→user→province |
| Utilization per province | Derived | `expenses / budget` |
| Approval rate per province | Reports | Group reports by province; approved/total |

### 7.3 Recommended Strategy

- **In-memory aggregation** — use shared dataset; group by `user.province`; sum from `$resolvedFinancials` map.
- **Expense totals** — batch `DPAccountDetail::whereIn('report_id', $reportIds)->get()`; group by report→project→province.
- **Hybrid** — for 50,000+ projects, consider SQL `GROUP BY` for province-level totals; keep resolver for per-project detail when needed.

---

## 7A. Province Dataset Partitioning Architecture

### 7A.1 Problem Description

Coordinator dashboards require province-level aggregation across **6 widgets**. The current design assumes each widget will call `$teamProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown')` independently. This causes:

- **Repeated CPU work** — `groupBy()` iterates the full collection, hashes keys, and builds a new structure. With 6 widgets, this happens **6 times** per request on the same dataset.
- **Scalability risk** — At 2,000–5,000 projects, each `groupBy` is O(N); 6× repeated grouping adds measurable latency.

### 7A.2 Province-Level Aggregation Points

| Widget | Province Grouping | Pattern |
|--------|-------------------|---------|
| coordinatorDashboard | projects_by_province | `$projects->groupBy('user.province')` |
| getPendingApprovalsData | pendingByProvince | `$pendingReports->groupBy(fn($r) => $r->user->province ?? 'Unknown')` |
| getSystemPerformanceData | province_metrics | `projectsByProvince`, `reportsByProvince` |
| getSystemAnalyticsData | province comparison | `projectsByProvince`, `approvedProjectsByProvince`, `reportsByProvince` |
| getSystemBudgetOverviewData | budgetByProvince | `$approvedProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown')` |
| getProvinceComparisonData | provincePerformance | `projectsByProvince`, `reportsByProvince` |

**getProvincialManagementData** groups by **provincial (parent_id)**, not by province — different structure; iterates provincials and filters by team members. **getSystemHealthData** uses system-wide aggregates; no province grouping.

### 7A.3 Performance Analysis

| Dataset Size | groupBy Cost (est.) | 6× Repeated | Single Pre-Partition |
|--------------|---------------------|-------------|----------------------|
| 100 projects | ~1 ms | ~6 ms | ~1 ms |
| 500 projects | ~5 ms | ~30 ms | ~5 ms |
| 2,000 projects | ~20 ms | ~120 ms | ~20 ms |
| 5,000 projects | ~50 ms | ~300 ms | ~50 ms |

**Conclusion:** Pre-partitioning eliminates 5 redundant `groupBy` passes. At 5,000 projects, this saves ~250 ms per request. Benefit scales linearly with dataset size and widget count.

### 7A.4 Dataset Structure Options

| Option | Description | Pros | Cons |
|--------|-------------|------|------|
| **A) Flat only** | Pass `$teamProjects`; widgets call `groupBy` when needed | Simple; no extra structure | 6× repeated grouping; CPU waste |
| **B) On-demand grouping** | Same as A; document that widgets should avoid redundant grouping | No change | Does not solve the problem |
| **C) Pre-partitioned** | Build `$projectsByProvince` once; pass to widgets | Single grouping pass; widgets reuse; simpler province logic | Slightly more memory (grouped structure); one extra structure to pass |

**Recommendation:** **Option C — Pre-partitioned dataset.** Build once after `resolveCollection`, pass `$projectsByProvince` and `$reportsByProvince` (and optionally `$approvedProjectsByProvince`) alongside `$teamProjects` and `$resolvedFinancials`.

### 7A.5 CPU and Memory Trade-offs

| Aspect | Impact |
|--------|--------|
| **CPU** | Positive — 1× groupBy instead of 6× |
| **Memory** | Neutral/slight increase — `groupBy` returns Collection of Collections; same project objects, different container structure. Overhead: ~few KB for province keys and collection wrappers (typical 10–30 provinces). |
| **Serialization** | Compatible — Laravel collections serialize; nested `Collection` structures serialize correctly for dashboard cache. |

### 7A.6 Where to Partition

| Location | Recommendation |
|----------|----------------|
| **A) Inside DatasetCacheService** | **No** — DatasetCacheService should return flat collections. Partitioning is aggregation/view logic, not dataset retrieval. |
| **B) CoordinatorController after dataset retrieval** | **Yes** — After `DatasetCacheService::getCoordinatorDataset()` and `resolveCollection()`, build partitions in controller; pass to widgets. |
| **C) Resolver pipeline** | **No** — Resolver handles financial resolution; partitioning is orthogonal. |

### 7A.7 Cache Compatibility

- **Dataset cache** — Store flat `$teamProjects` only. Partitioning occurs post-retrieval.
- **Dashboard cache** — Cache the **final widget payload**, which may include pre-aggregated province structures. Serialization of `$projectsByProvince` (Collection of Collections) is supported by Laravel Cache.
- **Invalidation** — Same as dataset; no change.

### 7A.8 Recommended Dataset Structure

```php
// After DatasetCacheService + resolveCollection
$teamProjects = DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters);
$resolvedFinancials = ProjectFinancialResolver::resolveCollection($teamProjects);
$allReports = /* batch load */;

// Build partitions once
$projectsByProvince = $teamProjects->groupBy(fn($p) => $p->user->province ?? 'Unknown');
$reportsByProvince = $allReports->groupBy(fn($r) => $r->user->province ?? 'Unknown');
$approvedProjectsByProvince = $teamProjects->filter(fn($p) => $p->isApproved())->groupBy(fn($p) => $p->user->province ?? 'Unknown');

// Pass to widgets
getProvinceComparisonData($teamProjects, $resolvedFinancials, $projectsByProvince, $reportsByProvince);
getSystemBudgetOverviewData(..., $approvedProjectsByProvince);
// etc.
```

### 7A.9 Impact on Widget Design

- Widget methods receive `$projectsByProvince`, `$reportsByProvince` (and `$approvedProjectsByProvince` where needed) as optional parameters.
- Widgets iterate over province keys or use `$projectsByProvince->get($province, collect())` instead of calling `groupBy` internally.
- **Immutable:** Partitioned structures are derived from `$teamProjects`; widgets must not mutate them (same rule as Provincial Phase 4A).

---

## 8. Coordinator Cache Strategy

### 8.1 Current Caching

| Cache | Key Pattern | TTL | Scope |
|-------|-------------|-----|-------|
| Pending approvals | `coordinator_pending_approvals_data_{$fy}` | 2 min | Per FY |
| Provincial overview | `coordinator_provincial_overview_data_{$fy}` | 5 min | Per FY |
| System performance | `coordinator_system_performance_data_{$fy}` | 15 min | Per FY |
| System analytics | `coordinator_system_analytics_data_{$fy}_{$timeRange}` | 15 min | Per FY + range |
| Budget overview | `coordinator_system_budget_overview_data_{$filterHash}` | 15 min | Per filter |
| Province comparison | `coordinator_province_comparison_data_{$fy}` | 15 min | Per FY |
| Provincial management | `coordinator_provincial_management_data_{$fy}` | 15 min | Per FY |
| System health | `coordinator_system_health_data_{$fy}` | 15 min | Per FY |
| Activity feed | `coordinator_system_activity_feed_data_{$fy}_{$limit}` | 2 min | Per FY + limit |

### 8.2 Missing Layers

| Layer | Provincial | Coordinator |
|-------|------------|-------------|
| Dataset cache | Yes | **No** |
| Full dashboard cache | Yes | **No** |

### 8.3 Proposed Cache Keys

| Layer | Key | TTL | Invalidation |
|-------|-----|-----|--------------|
| Dataset | `coordinator_dataset_{$fy}_{$filterHash}` | 10 min | Project/report approval, revert, budget sync |
| Dashboard | `coordinator_dashboard_{$fy}_{$filterHash}` | 5–10 min | Same as dataset; exclude real-time widgets |

### 8.4 Invalidation Gap

`invalidateDashboardCache()` clears keys such as `coordinator_pending_approvals_data` but widgets use `coordinator_pending_approvals_data_{$fy}`. Keys are misaligned; some caches may not invalidate correctly. Recommend cache tags (Redis) or aligned key structure.

---

## 9. Performance Risk Analysis

### 9.1 High-Risk Anti-Patterns

| Risk | Severity | Location | Impact |
|------|----------|----------|--------|
| Duplicate project queries | **High** | 9+ widget methods | 5×+ redundant DB loads per request |
| Per-project resolver loops | **High** | 8 locations | N× resolver calls; 2,000+ for 500 projects |
| No shared dataset | **High** | coordinatorDashboard | No dataset reuse; repeated fetches |
| No dataset cache | **Medium** | CoordinatorController | Every request hits DB for full dataset |
| No dashboard cache | **Medium** | coordinatorDashboard | No request-level caching |
| ProjectQueryService bypass | **Medium** | All project queries | Inconsistent query layer; no centralization |
| Cache key misalignment | **Low** | invalidateDashboardCache | Some caches may not invalidate |

### 9.2 Scalability Projection

| Projects in FY | Current (est.) | Optimized (est.) |
|----------------|----------------|------------------|
| 100 | ~3s | ~0.5s |
| 500 | ~12s | ~1.5s |
| 2,000 | ~45s+ | ~4s |
| 5,000 | Timeout risk | ~10s |

Optimized = single dataset + resolveCollection + dashboard cache.

### 9.3 Performance Risk Checklist

- [x] Duplicate project queries detected
- [x] Multiple resolver passes detected
- [x] Missing dataset reuse
- [x] Missing dataset caching layer
- [x] Missing full dashboard cache
- [x] ProjectQueryService not used

---

## 10. Recommended Coordinator Architecture

### 10.1 Target Pipeline

```
ProjectQueryService::forCoordinator($coordinator, $fy)
    ↓
DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)
    ↓
Lightweight dataset projection (same columns as Provincial)
    ↓
ProjectFinancialResolver::resolveCollection($teamProjects)
    ↓
Province-level aggregation (in-memory from shared dataset + map)
    ↓
Dashboard cache (optional; exclude real-time)
    ↓
View
```

### 10.2 Provincial Pipeline Reuse

| Provincial Component | Coordinator Equivalent |
|----------------------|------------------------|
| ProjectQueryService::forProvincial | ProjectQueryService::forCoordinator |
| DatasetCacheService::getProvincialDataset | DatasetCacheService::getCoordinatorDataset |
| Lightweight select | Same $select array |
| resolveCollection | Same call |
| Widget aggregation | Same pattern: receive dataset + map |
| provincial_dashboard_{province_id}_{fy}_{filterHash} | coordinator_dashboard_{fy}_{filterHash} |

### 10.3 Scope Difference

- **Provincial:** Scope = `accessibleByUserIds($ids)` (executors/applicants under provincial).
- **Coordinator:** Scope = `Project::inFinancialYear($fy)` (all projects; no user filter).

`ProjectQueryService::forCoordinator()` returns `Project::inFinancialYear($fy)` (or `getVisibleProjectsQuery` for consistency).

### 10.4 Implementation Components

| Component | Action |
|-----------|--------|
| ProjectQueryService | Add `forCoordinator(User $coordinator, string $fy): Builder` |
| DatasetCacheService | Add `getCoordinatorDataset($coordinator, $fy, $filters)`, `clearCoordinatorDataset($fy)` |
| CoordinatorController | Refactor coordinatorDashboard to: fetch dataset → resolveCollection → pass to widgets |
| Widget methods | Accept `$teamProjects`, `$resolvedFinancials`; remove internal queries and resolver loops |
| Dashboard cache | Add full dashboard cache with key `coordinator_dashboard_{$fy}_{$filterHash}` |

---

## 11. Implementation Roadmap

### Phase 1 — ProjectQueryService Extension
- Add `ProjectQueryService::forCoordinator($coordinator, $fy)` returning `Project::inFinancialYear($fy)` or delegating to `getVisibleProjectsQuery`.

### Phase 2 — DatasetCacheService Extension
- Add `getCoordinatorDataset($coordinator, $fy, $filters)` with lightweight select.
- Add `clearCoordinatorDataset($fy)`; wire to approval/revert/budget events.

### Phase 3 — Resolver Batch Optimization
- In `coordinatorDashboard`, call `resolveCollection($teamProjects)` once.
- Pass `$resolvedFinancials` to all widget methods.
- Refactor widget methods to use map lookup; remove per-project resolver calls.

### Phase 4 — Shared Dataset
- Replace widget-internal project queries with single dataset from DatasetCacheService.
- Apply filters consistently (in query or in-memory).

### Phase 5 — Widget Method Refactoring
- Update getSystemBudgetOverviewData, getProvinceComparisonData, getSystemPerformanceData, getSystemAnalyticsData, getProvincialManagementData, getSystemHealthData to accept `$teamProjects`, `$resolvedFinancials`.
- Remove internal project queries.

### Phase 6 — Dashboard Cache
- Implement full coordinator dashboard cache: `coordinator_dashboard_{$fy}_{$filterHash}`.
- Exclude or short-TTL: getPendingApprovalsData, getSystemActivityFeedData.
- Align invalidateDashboardCache with new keys.

### Phase 7 — projectList and projectBudgets
- projectList: use resolveCollection on paginated set (or pre-resolve full dataset if feasible).
- projectBudgets / budgetOverview: reuse coordinator dataset where applicable.

---

## 12. References

- `Documentations/V2/Budgets/Dashboards/Coordinator/Coordinator_Dashboard_Architecture_Audit.md`
- `Documentations/V2/Budgets/Dashboards/Coordinator/Coordinator_Dashboard_Implementation_Roadmap.md` — Phase-wise implementation plan including province partitioning
- `Documentations/V2/Budgets/Dashboards/Provincial/Provincial_Dashboard_FY_Architecture_Implementation_Plan.md`
- `Documentations/V2/Budgets/Dashboards/Provincial/Phase5_ResolverBatch_Implementation.md`
- `Documentations/V2/Budgets/Dashboards/Provincial/Phase6_DashboardCache_Implementation.md`
- `app/Services/DatasetCacheService.php`
- `app/Services/ProjectQueryService.php`
- `app/Domain/Budget/ProjectFinancialResolver.php`
- `app/Services/ProjectAccessService.php`
