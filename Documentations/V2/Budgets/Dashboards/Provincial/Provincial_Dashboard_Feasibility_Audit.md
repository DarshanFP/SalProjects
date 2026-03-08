# Provincial Dashboard Feasibility Audit

**Date:** 2026-03-05  
**Scope:** `/provincial/dashboard` — architectural upgrade feasibility  
**Objective:** Determine whether Executor dashboard improvements can be safely extended to Provincial dashboards.

---

## 1. Controller Architecture

### 1.1 Method Serving `/provincial/dashboard`

| Attribute | Value |
|-----------|-------|
| **Controller** | `App\Http\Controllers\ProvincialController` |
| **Method** | `provincialDashboard(Request $request)` |
| **Route** | `Route::get('/provincial/dashboard', [ProvincialController::class, 'provincialDashboard'])->name('provincial.dashboard')` |
| **View** | `provincial.index` (extends `provincial.dashboard` layout) |

### 1.2 Queries Used

- **Main project set (approved, FY-filtered):**  
  `Project::accessibleByUserIds($accessibleUserIds)->approved()->inFinancialYear($fy)` with optional `center`, `role`, `project_type` filters via `whereHas('user', ...)` and `where()`.
- **Society breakdown (when province has > 1 society):**  
  `Project::where('province_id', $provinceId)->whereNotNull('society_id')->inFinancialYear($fy)->get()`.
- **Filter options:**  
  `User::whereIn('id', $accessibleUserIds)->...->pluck('center')`; `Project::accessibleByUserIds(...)->approved()->distinct()->pluck('project_type')`.
- **Pending approvals:** via `getPendingApprovalsForDashboard()` (separate queries).
- **Approval queue:** via `getApprovalQueueForDashboard()` (separate queries).
- **Team metrics:** via `calculateTeamPerformanceMetrics()`, `prepareChartDataForTeamPerformance()`, `calculateCenterPerformance()`, `calculateEnhancedBudgetData()`, `prepareCenterComparisonData()` — each performs its own project/report queries.

### 1.3 Services Used

| Service | Usage |
|---------|-------|
| `ProjectAccessService` | Injected via constructor; `getAccessibleUserIds($provincial)` for project/report scope |
| `ProjectFinancialResolver` | Per-project `resolve()` in loops; **not** `resolveCollection()` |
| `DerivedCalculationService` | `calculateRemainingBalance()`, `calculateUtilization()` |
| `FinancialYearHelper` | `currentFY()`, `listAvailableFY()` (static, config-driven) |

### 1.4 ProjectQueryService Usage

**Provincial dashboard does NOT use ProjectQueryService.** All project queries use direct model access:

- `Project::accessibleByUserIds($accessibleUserIds)`
- `Project::where('province_id', $provinceId)`

Executor dashboards use `ProjectQueryService::getOwnedProjectsQuery()`, `getInChargeProjectsQuery()`, `getApprovedProjectsForExecutorScope()`, etc.

---

## 2. Project Dataset Retrieval

### 2.1 Main Dataset

- **Scope:** Projects where `user_id` or `in_charge` is in `accessibleUserIds` (executors/applicants under the provincial).
- **Access:** `Project::accessibleByUserIds($accessibleUserIds)` (model scope).
- **Status:** Only approved projects.
- **FY:** `->inFinancialYear($fy)` (uses `commencement_month_year`).

### 2.2 Society Breakdown Dataset

- **Scope:** `Project::where('province_id', $provinceId)->whereNotNull('society_id')`.
- **FY:** `->inFinancialYear($fy)`.
- **Use:** Society-level financial totals when province has more than one active society.

### 2.3 Widget Datasets (Separate Queries)

| Widget | Query Pattern |
|--------|----------------|
| Budget summary cards/tables | Uses main `$projects` from controller |
| Team Performance | `Project::accessibleByUserIds(...)->inFinancialYear($fy)->get()` |
| Chart data | Same as Team Performance |
| Center Performance | Per-center: `Project::whereIn('user_id', $centerUsers)->inFinancialYear($fy)->get()` |
| Enhanced Budget Data | `Project::accessibleByUserIds(...)->approved()->inFinancialYear($fy)->get()` and `->notApproved()->...->get()` |

No shared project collection; each widget method performs its own query.

---

## 3. Financial Aggregation Method

### 3.1 Current Approach

- **Resolver usage:** `$resolver->resolve($project)` in loops — one call per project.
- **`resolveCollection()` usage:** **None.** Executor uses `ProjectFinancialResolver::resolveCollection()` once and reuses the map; Provincial does not.
- **Budget summaries:** `calculateBudgetSummariesFromProjects()` loops over `$projects` and calls `$resolver->resolve($project)` for each.
- **Society totals:** Separate loop over society projects, one `$resolver->resolve($project)` per project.
- **Widget methods:** `calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateCenterPerformance`, `calculateEnhancedBudgetData` all loop and call `$resolver->resolve($project)` per project.

### 3.2 Resolver Call Count (Per Page Load)

| Source | Resolver Calls |
|--------|----------------|
| Main budget summary | N (N = approved projects in main dataset) |
| Society breakdown | M (M = projects in society query, when enabled) |
| Team Performance | N1 (approved in team projects) |
| Chart data | N2 (approved in team projects) |
| Center Performance | Per-center: sum of approved + pending (pending resolved again per project) |
| Enhanced Budget Data | Approved count + pending count |

Same project can be resolved multiple times across these methods (no shared `resolvedFinancials` map).

---

## 4. Financial Year Filtering

### 4.1 FY Support

- **Present:** Yes. All relevant project queries use `->inFinancialYear($fy)`.
- **Source of `$fy`:** `$request->input('fy', FinancialYearHelper::currentFY())`.
- **Scopes:** Main project query, society breakdown, `calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateCenterPerformance`, `calculateEnhancedBudgetData`.

### 4.2 FY Dropdown Source

- **Current:** `FinancialYearHelper::listAvailableFY()` (fixed 10-year window; no DB).
- **Executor dynamic option:** `FinancialYearHelper::listAvailableFYFromProjects($projectQuery)` (derived from project `commencement_month_year`).
- **Provincial:** Uses `listAvailableFY()`. Not yet using `listAvailableFYFromProjects()`.

### 4.3 FY Dropdown Feasibility

Feasible to introduce a dynamic FY list. The provincial scope is:

- `Project::accessibleByUserIds($accessibleUserIds)` (optionally with approved filter).

Provincial could call:

```php
$baseQuery = Project::accessibleByUserIds($accessibleUserIds);
$availableFY = FinancialYearHelper::listAvailableFYFromProjects($baseQuery);
```

If the result is empty, fallback to `FinancialYearHelper::listAvailableFY()` as in Executor.

---

## 5. Widget Architecture

### 5.1 Widgets Shown

| Widget | Data Source | Recomputes? |
|--------|-------------|-------------|
| Budget Summary & Details | `$budgetSummaries` (from main `$projects`) | No (controller) |
| Society Financial Breakdown | `$societyStats` (from separate society query) | No (controller) |
| Pending Approvals | `$pendingProjects`, `$pendingReports`, etc. | No (controller) |
| Approval Queue | `$approvalQueue`, `$approvalQueueProjects`, `$approvalQueueReports` | No (controller) |
| Team Overview | `$teamMembers`, `$teamStats` | No (controller) |
| Team Performance Summary | `$performanceMetrics`, `$chartData`, `$centerPerformance` | Yes — each from its own method with separate queries |
| Team Budget Overview | `$budgetData` | Yes — from `calculateEnhancedBudgetData()` |
| Center Comparison | `$centerComparison` | Yes — from `prepareCenterComparisonData()` (calls `calculateCenterPerformance`) |
| Team Activity Feed | `$teamActivities` | No (controller) |

### 5.2 Shared vs Recomputed

- **Shared:** Main budget summary (by project type, center, total) uses the main `$projects` collection.
- **Recomputed:** Team Performance, Chart Data, Center Performance, Enhanced Budget Data, Center Comparison each fetch their own projects and resolve financials independently. There is no single pre-resolved dataset shared across widgets like on the Executor dashboard.

---

## 6. Performance Risk Assessment

### 6.1 Query Multiplicity

| Data Set | Approx. Query Count per Page Load |
|----------|-----------------------------------|
| Main approved projects | 1 |
| Society breakdown projects | 1 (when enabled) |
| Team Performance | 2 (projects + reports) |
| Chart data | 2 (projects + reports) |
| Center Performance | 1 + N (N = centers; per-center project query) |
| Enhanced Budget Data | 2 (approved + pending) |
| Pending approvals | 2 (projects + reports) |
| Approval queue | 2 (projects + reports) |

Total: on the order of 10+ project/report queries per load, plus user/center/society lookups.

### 6.2 Nested Loops and Resolver Calls

- Per-project loops with `$resolver->resolve()` in each widget method.
- No batch resolution; same project may be resolved multiple times across methods.
- Center Performance: per-center loop, each with project query + resolver loops.

### 6.3 Indexing

- **Existing indexes (from `2026_03_05_071759_add_project_query_indexes`):**  
  `(user_id, status)`, `(in_charge, status)`, `commencement_month_year`.
- **Province:** `province_id` has an index (from `2026_02_15_173841_add_province_id_to_projects_table`).
- **Society breakdown:** Filters by `province_id`, `society_id`, `commencement_month_year` — indexed.
- **Main query:** Uses `whereIn('user_id', ...) or whereIn('in_charge', ...)` — benefits from user_id and in_charge indexes.

### 6.4 Scalability at Higher Project Counts

| Project Count | Risk | Notes |
|---------------|------|-------|
| 1000+ | Medium | Multiple full loads of project collections; resolver loops; center-per-query pattern |
| 5000+ | High | Same patterns; no pagination on widget queries; repeated resolution |
| 10000+ | High | Significant load from 10+ queries and per-project resolution across widgets |

Main mitigations would be: centralize project fetch, use `resolveCollection()` once, and share the result across widgets (as on Executor).

---

## 7. Feasibility of Executor Architecture Reuse

### 7.1 Comparison

| Feature | Executor | Provincial |
|---------|----------|------------|
| Project query service | ProjectQueryService (owned/in-charge/scoped) | Direct model (`accessibleByUserIds`) |
| Batch financial resolution | `resolveCollection()` once, reuse map | Per-project `resolve()` in multiple places |
| Shared dataset for widgets | Single approved set, pre-resolved | Separate queries per widget |
| FY dropdown | Can use `listAvailableFYFromProjects` | `listAvailableFY` only |
| FY filtering | All queries | All queries |
| Scope filter | owned / in_charge / owned_and_in_charge | center, role, project_type (no scope filter) |

### 7.2 Reuse Opportunities

1. **ProjectQueryService**
   - Provincial scope = `accessibleByUserIds($ids)`.
   - ProjectQueryService has `getProjectsForUsersQuery($userIds, $currentUser)` which applies province boundary when `$currentUser` has `province_id`.
   - Provincial can adopt: `ProjectQueryService::getProjectsForUsersQuery($accessibleUserIds->toArray(), $provincial)` and chain status/FY filters. This would centralize and align with Executor patterns.

2. **resolveCollection() and shared dataset**
   - Fetch one approved (and optionally pending) project set for the dashboard.
   - Call `ProjectFinancialResolver::resolveCollection($projects)` once.
   - Pass the map to all budget/chart/center methods instead of resolving per project. Same approach as Executor.

3. **Dynamic FY dropdown**
   - Use `FinancialYearHelper::listAvailableFYFromProjects($baseQuery)` with a base query like `Project::accessibleByUserIds($accessibleUserIds)`.
   - Fallback to `listAvailableFY()` when empty.

4. **Dataset caching**
   - Consider request-level caching of the main project set and resolved financials to avoid duplicate queries within the same request (especially if widgets are loaded separately in future).

---

## 8. Recommended Provincial Dashboard Improvements

### 8.1 Short-Term (Low Effort)

1. **Use `resolveCollection()` for main budget summary**
   - Fetch approved projects once.
   - Call `ProjectFinancialResolver::resolveCollection($projects)`.
   - Update `calculateBudgetSummariesFromProjects()` to accept pre-resolved financials and avoid per-project `resolve()`.
   - Extend the same map to society totals when applicable.

2. **Consolidate society and main project resolution**
   - If society breakdown uses a superset of main projects (or overlapping set), resolve once and reuse.

### 8.2 Medium-Term (Moderate Effort)

3. **Centralize project dataset**
   - Single base query: `Project::accessibleByUserIds($accessibleUserIds)->inFinancialYear($fy)`.
   - One fetch for approved (and optionally pending) projects.
   - Pass this collection and pre-resolved financials to all widget methods.

4. **Introduce ProjectQueryService**
   - Replace direct `Project::accessibleByUserIds()` with `ProjectQueryService::getProjectsForUsersQuery($accessibleUserIds->toArray(), $provincial)`.
   - Apply status, FY, center, role, project_type on top. Keeps province boundary logic in one place.

5. **Dynamic FY dropdown**
   - `$baseQuery = Project::accessibleByUserIds($accessibleUserIds);`
   - `$availableFY = FinancialYearHelper::listAvailableFYFromProjects($baseQuery);`
   - Fallback to `listAvailableFY()` when empty.

### 8.3 Longer-Term (Higher Effort)

6. **Remove per-widget project queries**
   - Refactor `calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateCenterPerformance`, `calculateEnhancedBudgetData` to accept a shared project collection and resolved financials map.
   - Reduces queries from ~10+ to ~2–3 per page.

7. **Add composite indexes if needed**
   - If queries filter heavily by `province_id` + `commencement_month_year`, consider `(province_id, commencement_month_year)`.
   - Measure before adding; existing indexes may suffice for current scale.

8. **Optional scope filter (owned / in_charge)**
   - Provincial sees team projects; Executor has owned vs in-charge. If provincial needs similar nuance, extend ProjectQueryService/ProjectAccessService to support provincial-level scope.

---

## Summary

| Aspect | Current State | Feasible Upgrade |
|--------|---------------|------------------|
| Controller | Direct model queries, multiple data methods | Use ProjectQueryService, centralize dataset |
| Project dataset | `accessibleByUserIds`, per-widget queries | Single base query, shared collection |
| Financial aggregation | Per-project `resolve()` in multiple places | `resolveCollection()` once, reuse map |
| FY filtering | Present and consistent | Keep as is |
| FY dropdown | Static `listAvailableFY()` | Dynamic `listAvailableFYFromProjects()` |
| Widget data | Recomputed per widget | Shared dataset and pre-resolved financials |
| Performance | 10+ queries, repeated resolution | Fewer queries, single resolution pass |

The Executor dashboard architecture (centralized project set, `resolveCollection()`, shared widget data, dynamic FY) can be applied to the Provincial dashboard with moderate refactoring. The main changes are: centralizing the project dataset, replacing per-project resolution with `resolveCollection()`, and optionally adopting ProjectQueryService and a dynamic FY dropdown.
