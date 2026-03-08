# Coordinator Dashboard Architecture Audit

**Date:** 2026-03-06  
**Scope:** Coordinator role — system-wide architectural audit  
**Objective:** Identify opportunities to implement a scalable Coordinator dashboard architecture reusing the optimized Provincial pipeline.  
**Mode:** Audit only — no code modifications.

---

## 1. Coordinator Role Overview

### 1.1 Role Definition

| Attribute | Value |
|-----------|-------|
| **Role** | `coordinator` (and `general` shares coordinator routes) |
| **Access Level** | Top-level oversight; **global read access** across all provinces |
| **Hierarchy** | None — Coordinator does not use `getAccessibleUserIds` or parent_id logic |
| **Province Scope** | Unfiltered — sees all projects in the system |
| **Documentation** | `ProjectAccessService` explicitly states: "Coordinator: Top-level oversight role. No hierarchy. Global read access." |

### 1.2 Entry Points

| Route | Controller Method | Purpose |
|-------|-------------------|---------|
| `/coordinator/dashboard` | `CoordinatorController::coordinatorDashboard()` | Main dashboard |
| `/coordinator/projects-list` | `CoordinatorController::projectList()` | Project list with filters |
| `/coordinator/approved-projects` | `CoordinatorController::approvedProjects()` | Approved projects view |
| `/coordinator/report-list` | `CoordinatorController::reportList()` | Report list |
| `/coordinator/budgets` | `CoordinatorController::projectBudgets()` | Budget view |
| `/coordinator/budget-overview` | `CoordinatorController::budgetOverview()` | Budget overview |

### 1.3 Controllers

- **CoordinatorController** — sole controller for coordinator dashboards, project/report management, approvals, provincial management.
- **ProjectAccessService** — used in `projectList()` via `getVisibleProjectsQuery($coordinator, $fy)`; for coordinator this returns an **unfiltered** query.
- **General role** — shares coordinator routes; `role:coordinator,general` middleware; treated identically for coordinator-level actions.

### 1.4 Data Scope Definition

- **Access:** `ProjectAccessService::getVisibleProjectsQuery($user, $fy)` for coordinator returns `Project::query()` with optional `inFinancialYear($fy)` — **no user/province restriction**.
- **Filters (UI-level):** Province, center, role, parent_id (provincial), project_type, status — applied via `whereHas('user', ...)` on request parameters. These are **filter-only**, not access-control.
- **Province:** Coordinator uses `user.province` (string column on users table) for grouping and filtering, not `projects.province_id` for access.

---

## 2. Current Coordinator Query Architecture

### 2.1 Main Dashboard (`coordinatorDashboard`)

| Query Purpose | Current Pattern | ProjectQueryService | DatasetCacheService |
|---------------|-----------------|---------------------|---------------------|
| Approved projects (main list) | `Project::approved()->inFinancialYear($fy)->with(...)` + optional filters | **No** | **No** |
| All projects (statistics) | `Project::inFinancialYear($fy)->with('user')` + same filters | **No** | **No** |
| Filter options | `User::whereIn('role', ...)->pluck('province')`, `Project::approved()->inFinancialYear($fy)->distinct()->pluck('project_type')` | **No** | **No** |
| Budget summaries | `calculateBudgetSummariesFromProjects($projects)` — loops `$resolver->resolve($project)` per project | **No** | **No** |

### 2.2 Widget Methods — Duplicate Queries

Each widget performs **independent project queries**; no shared dataset.

| Widget Method | Cache Key | TTL | Query Pattern |
|---------------|-----------|-----|---------------|
| `getPendingApprovalsData($fy)` | `coordinator_pending_approvals_data_{$fy}` | 2 min | `Project::inFinancialYear($fy)->pluck('project_id')`; `Project::where('status', FORWARDED)->inFinancialYear($fy)`; `DPReport::where(...)->whereIn('project_id', $fyProjectIds)` |
| `getProvincialOverviewData($fy)` | `coordinator_provincial_overview_data_{$fy}` | 5 min | `User::where('role','provincial')` with `projects_count`; per-provincial: `Project::whereIn('user_id', $teamUserIds)->inFinancialYear($fy)`; `DPReport::whereIn(...)` |
| `getSystemPerformanceData($fy)` | `coordinator_system_performance_data_{$fy}` | 15 min | `Project::inFinancialYear($fy)->with(...)->get()`; `DPReport::whereIn('project_id', $fyProjectIds)` |
| `getSystemAnalyticsData($fy, $timeRange)` | `coordinator_system_analytics_data_{$fy}_{$timeRange}` | 15 min | `Project::inFinancialYear($fy)->with(...)->get()`; `DPReport::whereIn(...)` |
| `getSystemBudgetOverviewData($request)` | `coordinator_system_budget_overview_data_{$filterHash}` | 15 min | `Project::approved()->inFinancialYear($fy)`; `Project::notApproved()->inFinancialYear($fy)` — **separate queries** |
| `getProvinceComparisonData($fy)` | `coordinator_province_comparison_data_{$fy}` | 15 min | `Project::inFinancialYear($fy)->with(...)->get()`; `DPReport::whereIn(...)` |
| `getProvincialManagementData($fy)` | `coordinator_provincial_management_data_{$fy}` | 15 min | Similar project/report queries |
| `getSystemHealthData($fy)` | `coordinator_system_health_data_{$fy}` | 15 min | Similar project/report queries |
| `getSystemActivityFeedData($fy, $limit)` | `coordinator_system_activity_feed_data_{$fy}_{$limit}` | 2 min | `Project::inFinancialYear($fy)->pluck('project_id')`; `ActivityHistory::where(...)` |

### 2.3 Project List (`projectList`)

| Attribute | Value |
|-----------|-------|
| **Base Query** | `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` — unfiltered for coordinator |
| **ProjectQueryService** | **No** |
| **DatasetCacheService** | **No** |
| **Resolver** | Per-project `$resolver->resolve($project)` in `map()` — **no** `resolveCollection()` |
| **Pagination** | 100 per page; query runs per page |

### 2.4 Summary: Queries That Should Reuse ProjectQueryService

| Location | Current | Recommendation |
|----------|---------|----------------|
| `coordinatorDashboard` main projects | `Project::approved()->inFinancialYear($fy)` | `ProjectQueryService::forCoordinator($coordinator, $fy)->approved()` |
| `coordinatorDashboard` all projects | `Project::inFinancialYear($fy)` | Same base query with status filter |
| `getSystemBudgetOverviewData` | `Project::approved()->inFinancialYear($fy)`; `Project::notApproved()->inFinancialYear($fy)` | Single `ProjectQueryService::forCoordinator($coordinator, $fy)`; filter approved/pending in memory |
| `getSystemPerformanceData` | `Project::inFinancialYear($fy)->get()` | Use shared coordinator dataset |
| `getSystemAnalyticsData` | `Project::inFinancialYear($fy)->get()` | Use shared coordinator dataset |
| `getProvinceComparisonData` | `Project::inFinancialYear($fy)->get()` | Use shared coordinator dataset |
| `getProvincialManagementData` | Per-provincial queries | Use shared coordinator dataset, group in memory |
| `getSystemHealthData` | Similar pattern | Use shared coordinator dataset |
| `projectList` | `getVisibleProjectsQuery` | Align with `ProjectQueryService::forCoordinator` for consistency |
| `projectBudgets` | `Project::approved()->inFinancialYear($fy)` | Use shared coordinator dataset |

---

## 3. Dataset Compatibility Analysis

### 3.1 Current DatasetCacheService

- **Scope:** Provincial only — `getProvincialDataset($provincial, $fy)`.
- **Source:** `ProjectQueryService::forProvincial($provincial, $fy)` which uses `ProjectAccessService::getAccessibleUserIds($provincial)`.
- **Coordinator:** `getAccessibleUserIds` is **not** used for coordinator; coordinator has global access.

### 3.2 Compatibility Assessment

| Aspect | Feasibility | Notes |
|--------|-------------|-------|
| **Add `getCoordinatorDataset`** | **Yes** | Coordinator scope = all projects in FY; no user/province restriction. Query: `Project::inFinancialYear($fy)` with optional filters. |
| **Per-province vs per-coordinator** | Per-coordinator | Coordinator sees all provinces; dataset is global. One dataset per FY (+ filter hash if filters applied). |
| **Filter support** | **Yes** | Coordinator dashboard supports province, center, role, parent_id, project_type filters. Dataset can be cached per filter combination (like budget overview filter hash) or filter applied post-fetch. |
| **General role** | Consider | General user in coordinator context may have province filter (session). If so, similar to provincial: either bypass cache or cache per session filter. |

### 3.3 Proposed Dataset Architecture

```
DatasetCacheService::getCoordinatorDataset(User $coordinator, string $fy, ?array $filters = null)
```

- **Implementation:** Use `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` as base (returns unfiltered for coordinator), apply optional filters (`province`, `center`, `role`, `parent_id`, `project_type`), select same lightweight columns as provincial, eager-load `user`, `reports.accountDetails`, `budgets`.
- **Cache key:** `coordinator_dataset_{$coordinator->id}_{$fy}_{$filterHash}` — or `coordinator_dataset_{$fy}_{$filterHash}` if coordinator ID is irrelevant (all coordinators see same data).
- **Invalidation:** Same events as provincial: project/report approval, revert, budget changes. Add `clearCoordinatorDataset(?string $fy)` to invalidate coordinator caches.

### 3.4 Lightweight Project Columns (Phase 4.5 Alignment)

Reuse the same `$select` array as `getProvincialDataset`:
`id`, `project_id`, `province_id`, `society_id`, `project_type`, `user_id`, `in_charge`, `commencement_month_year`, `opening_balance`, `amount_sanctioned`, `amount_forwarded`, `local_contribution`, `overall_project_budget`, `status`, `current_phase`, `project_title`.

---

## 4. Resolver Compatibility Analysis

### 4.1 Current Resolver Usage

| Location | Pattern | resolveCollection? |
|----------|---------|-------------------|
| `calculateBudgetSummariesFromProjects` | Loop: `$resolver->resolve($project)` per project | **No** |
| `getSystemBudgetOverviewData` | Loop: `$resolvedFinancials[$p->project_id] = $resolver->resolve($project)`; pending: `$resolver->resolve($p)` in sum | **No** |
| `getProvinceComparisonData` | Loop: `$resolver->resolve($project)` per project | **No** |
| `getSystemPerformanceData` | Loop: `$resolver->resolve($project)` per project | **No** |
| `getSystemAnalyticsData` | Loop: `$resolver->resolve($project)` per project | **No** |
| `projectList` map | `$resolver->resolve($project)` per project in paginated set | **No** |

### 4.2 Provincial vs Coordinator

| Role | resolveCollection | Resolver Calls per Request |
|------|-------------------|----------------------------|
| Provincial | **Yes** — single call; map passed to widgets | ~1 (N projects resolved once) |
| Executor | **Yes** — single call | ~1 |
| Coordinator | **No** — each widget/method resolves independently | **~5–8× N** (N = projects in FY) |

### 4.3 Recommendation

- Replace all per-project `$resolver->resolve($project)` loops with **single** `ProjectFinancialResolver::resolveCollection($coordinatorProjects)`.
- Pass the resulting map to all widget methods that need financial data.
- Same pattern as Provincial: one resolve per request; widgets lookup `$resolvedFinancials[$project_id]`.

---

## 5. Dashboard Cache Opportunities

### 5.1 Current Coordinator Caching

| Layer | Exists? | Key Pattern | TTL |
|-------|---------|-------------|-----|
| Widget-level | **Yes** | Per-widget keys (e.g. `coordinator_system_budget_overview_data_{hash}`) | 2–15 min |
| Full dashboard | **No** | N/A | N/A |
| Dataset | **No** | N/A | N/A |

### 5.2 Provincial Dashboard Cache (Phase 6)

- **Key:** `provincial_dashboard_{$province_id}_{$fy}_{$filterHash}`
- **Scope:** Full rendered view data (minus real-time approval data).
- **Bypass:** General users (session-dependent scope).
- **Pipeline:** Query → DatasetCacheService → resolveCollection → Aggregations → Cache → View.

### 5.3 Coordinator Dashboard Cache — Feasibility

| Aspect | Assessment |
|--------|------------|
| **Cache key** | `coordinator_dashboard_{$fy}_{$filterHash}` (coordinator_id optional; all coordinators see same scope) |
| **Filter hash** | Include: province, center, role, parent_id, project_type, analytics_range |
| **Invalidation** | Same as widget caches: project/report approval, revert, budget sync. Use cache tags if driver supports (Redis) for wildcard invalidation. |
| **Real-time data** | Pending approvals (projects/reports) should remain real-time or short TTL (2 min) — same as Provincial. |
| **Benefit** | Skip dataset fetch, resolveCollection, and all widget aggregations on cache hit. Coordinator dashboard has **many** widgets; full cache would significantly reduce load. |

### 5.4 Cache Invalidation Gap (Current)

`invalidateDashboardCache()` clears fixed keys:
- `coordinator_pending_approvals_data` — but widget uses `coordinator_pending_approvals_data_{$fy}`.
- `coordinator_system_activity_feed_data_50` — but widget uses `coordinator_system_activity_feed_data_{$fy}_{$limit}`.
- Budget overview uses filter hash; invalidation relies on TTL (no wildcard delete).

**Recommendation:** Align cache keys with invalidation, or use cache tags (Redis) for coordinator caches to support bulk invalidation by prefix.

---

## 6. Province Aggregation Strategy

### 6.1 Current Approach

- **Grouping key:** `user.province` (string on users table) — legacy column.
- **Usage:** `$projects->groupBy(fn($p) => $p->user->province ?? 'Unknown')`.
- **Metrics:** Budget (resolver `opening_balance`), expenses (DPAccountDetail sum), utilization, approval rate, project/report counts.

### 6.2 Province Comparison Widget

- `getProvinceComparisonData` loads all FY projects, groups by province, computes per-province metrics.
- Rankings by approval rate, budget, utilization, etc.
- **Efficiency:** Single dataset + in-memory grouping — good. But dataset is loaded **again** per widget (no shared dataset).

### 6.3 Recommended Aggregation Structure

1. **Single dataset:** `DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)`.
2. **Single resolveCollection:** Produce `$resolvedFinancials` map.
3. **Province aggregation:** `$dataset->groupBy(fn($p) => $p->user->province ?? 'Unknown')`; iterate groups; sum from `$resolvedFinancials`; compute expenses from `DPAccountDetail` (batch query by report IDs).
4. **Reuse:** Same grouped data for `getProvinceComparisonData`, `getSystemBudgetOverviewData` (by_province), `getSystemPerformanceData` (province_metrics), `getSystemAnalyticsData` (province comparison).

---

## 7. Recommended Coordinator Architecture

### 7.1 Target Pipeline

```
ProjectQueryService::forCoordinator($coordinator, $fy)
    ↓ (with optional filters)
DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)
    ↓
ProjectFinancialResolver::resolveCollection($teamProjects)
    ↓
Widget aggregations (receive $teamProjects + $resolvedFinancials)
    ↓
Dashboard cache (optional, Phase 6–style)
```

### 7.2 New / Extended Components

| Component | Action |
|-----------|--------|
| **ProjectQueryService** | Add `forCoordinator(User $coordinator, string $fy): Builder` — returns `Project::inFinancialYear($fy)` (coordinator = no user filter). Alternatively, delegate to `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)`. |
| **DatasetCacheService** | Add `getCoordinatorDataset($coordinator, $fy, $filters)`, `clearCoordinatorDataset($fy)`. |
| **CoordinatorController** | Refactor `coordinatorDashboard` to: (1) get dataset via DatasetCacheService, (2) call resolveCollection once, (3) pass dataset + map to all widget methods. |
| **Widget methods** | Change signatures to accept `$teamProjects` and `$resolvedFinancials`; remove internal project queries and resolver loops. |
| **Dashboard cache** | Optional: cache full dashboard payload with key `coordinator_dashboard_{$fy}_{$filterHash}`. |

### 7.3 Filter Handling

- **Request filters:** province, center, role, parent_id, project_type.
- **Option A:** Apply filters in query before DatasetCacheService (smaller dataset, more cache keys).
- **Option B:** Fetch full FY dataset, filter in memory (simpler cache, larger payload). Given coordinator scope = all projects, Option B may be acceptable if project count is manageable; Option A preferred for large datasets.

### 7.4 General Role Consideration

- General user in coordinator context may have `province_filter_ids` (session). If coordinator dashboard is used with province filter, treat like Provincial: either bypass coordinator dataset cache or use filter in cache key.
- Document: General acting as coordinator uses same pipeline; scope may be filtered by session.

---

## 8. Implementation Roadmap

### Phase 1 — ProjectQueryService Extension
- Add `ProjectQueryService::forCoordinator($coordinator, $fy)`.
- Implement using `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` or direct `Project::inFinancialYear($fy)`.

### Phase 2 — DatasetCacheService Extension
- Add `DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)`.
- Same lightweight select and eager load as provincial.
- Add `clearCoordinatorDataset($fy)` and wire to approval/revert/budget events.

### Phase 3 — Resolver Batch Optimization
- In `coordinatorDashboard`, call `resolveCollection($teamProjects)` once.
- Pass `$resolvedFinancials` to `calculateBudgetSummariesFromProjects` and all widget methods.
- Refactor widget methods to accept and use the map; remove per-project resolver calls.

### Phase 4 — Shared Dataset
- Replace all widget-internal project queries with the single dataset from DatasetCacheService.
- Ensure filters (province, center, etc.) are applied consistently (query or in-memory).

### Phase 5 — Widget Method Refactoring
- Update `getSystemBudgetOverviewData`, `getProvinceComparisonData`, `getSystemPerformanceData`, `getSystemAnalyticsData`, `getProvincialManagementData`, `getSystemHealthData` to accept `$teamProjects` and `$resolvedFinancials`.
- Remove duplicate queries; use shared dataset and map.

### Phase 6 — Dashboard Cache (Optional)
- Implement full coordinator dashboard cache with key `coordinator_dashboard_{$fy}_{$filterHash}`.
- Exclude or short-TTL pending approvals.
- Align `invalidateDashboardCache` with new cache keys; consider cache tags for wildcard invalidation.

### Phase 7 — Project List and Budget Pages
- Apply same dataset + resolveCollection pattern to `projectList` and `projectBudgets`.
- Reuse coordinator dataset where possible (e.g. project list first page); pagination may require separate query with resolveCollection on paginated set.

---

## 9. Risk and Compatibility Notes

| Risk | Mitigation |
|------|------------|
| Coordinator dataset size | All FY projects; may be large. Consider pagination for project list; dashboard aggregates can use in-memory filtering. |
| Cache invalidation | Wire `clearCoordinatorDataset` to same events as `clearProvincialDataset`; ensure approval/revert/budget handlers call both. |
| General role scope | If General has province filter in coordinator view, treat as Provincial (bypass or filter-scoped cache). |
| Legacy user.province | Keep using for grouping; no change to aggregation logic. |
| Backward compatibility | Refactor incrementally; preserve cache key structure for existing widgets during transition. |

---

## 10. Summary

| Area | Current State | Recommended |
|------|---------------|-------------|
| **ProjectQueryService** | Not used | Add `forCoordinator($coordinator, $fy)` |
| **DatasetCacheService** | Provincial only | Add `getCoordinatorDataset` |
| **Resolver** | Per-project in loops | Single `resolveCollection` per request |
| **Shared dataset** | None — each widget queries | Single dataset per request; widgets consume |
| **Dashboard cache** | Widget-level only | Optional full dashboard cache |
| **Province aggregation** | Per-widget queries | In-memory grouping on shared dataset |
| **projectList** | getVisibleProjectsQuery + per-project resolve | Use forCoordinator + resolveCollection |

The Coordinator dashboard can be aligned with the Provincial pipeline by introducing `ProjectQueryService::forCoordinator`, `DatasetCacheService::getCoordinatorDataset`, and a single `resolveCollection` call. Widget methods should be refactored to consume a shared dataset and financial map, eliminating duplicate queries and redundant resolver executions.
