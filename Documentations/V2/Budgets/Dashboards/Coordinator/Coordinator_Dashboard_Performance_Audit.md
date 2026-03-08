# Coordinator Dashboard — Deep Performance & Architecture Audit

**Date:** 2026-03-07  
**Route:** `GET /coordinator/dashboard`  
**Controller:** `CoordinatorController::coordinatorDashboard()`  
**View:** `resources/views/coordinator/index.blade.php`  
**Mode:** Audit only — no code modifications.

---

## 1. Current Dashboard Architecture

### 1.1 Pipeline Overview (Post Phase 7)

```
Request → coordinatorDashboard()
    ↓
Phase 7: Cache check (coordinator_dashboard_{fy}_{filterHash}_{analyticsRange})
    ↓
HIT: Return cached payload + pendingApprovals + activityFeed
    ↓
MISS: buildCoordinatorDashboardPayload()
    ├─ DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)
    ├─ ProjectFinancialResolver::resolveCollection($teamProjects)
    ├─ DPReport::whereIn(project_id, ...)->with('user')->get()
    ├─ Partitions: projectsByProvince, reportsByProvince, approvedProjectsByProvince
    ├─ provinces, centers, parents (User queries)
    ├─ statistics, budgetSummaries, projectTypes
    ├─ getRecentActivity($allProjects)
    ├─ getProvincialOverviewData($fy)
    ├─ getSystemPerformanceData(...)
    ├─ getSystemAnalyticsData(...)
    ├─ getSystemBudgetOverviewData(...)
    ├─ getProvinceComparisonData(...)
    ├─ getProvincialManagementData(...)
    └─ getSystemHealthData(...)
    ↓
Real-time (always): getPendingApprovalsData($fy), getSystemActivityFeedData($fy)
    ↓
View
```

### 1.2 Implemented Optimizations

| Layer | Status | Notes |
|-------|--------|-------|
| DatasetCacheService | ✓ Implemented | getCoordinatorDataset cached by FY |
| resolveCollection | ✓ Used once | Single batch resolution |
| Shared dataset | ✓ Implemented | teamProjects passed to all widgets |
| Province partitions | ✓ Implemented | projectsByProvince, reportsByProvince, approvedProjectsByProvince |
| Dashboard cache | ✓ Phase 7 | coordinator_dashboard tag, 10 min TTL |
| Pending/Activity | Dynamic | Computed outside cache (never cached) |

---

## 2. Query Analysis

### 2.1 Data Pipeline (buildCoordinatorDashboardPayload)

| Step | Source | Queries |
|------|--------|---------|
| Dataset | DatasetCacheService::getCoordinatorDataset | 1 (or cache hit) |
| Resolver | ProjectFinancialResolver::resolveCollection | 0 (in-memory) |
| Reports | DPReport::whereIn | 1 |
| Filter options | User::whereIn (provinces) | 1 |
| Filter options | User::whereIn (centers) | 1 |
| Filter options | User::where role provincial | 1 |
| statistics | In-memory from teamProjects | 0 |
| budgetSummaries | calculateBudgetSummariesFromProjects | 0 (uses resolvedFinancials) |
| projectTypes | In-memory | 0 |

### 2.2 Widget Method Queries

| Widget | Queries | Notes |
|--------|---------|-------|
| getRecentActivity | 1 | ProjectStatusHistory::with('project','changedBy') — **not FY-scoped** |
| getProvincialOverviewData | Cached | Inside cache: User::withCount (1) + per-provincial loop: User (N), Project (N), DPReport (4N), Project (N) |
| getSystemPerformanceData | 1 + P | DPAccountDetail::whereIn (1 global + P per province) |
| getSystemAnalyticsData | ~5 + 4×months | DPAccountDetail per month (budget timeline, expense trends, approval trends, report timeline) + per-province |
| getSystemBudgetOverviewData | ~15+ | DPAccountDetail: 2 global + per project type + per province + per center + per provincial + 6 months expense + 10 top projects |
| getProvinceComparisonData | 1 + P | User::whereNotNull (1) + DPAccountDetail per province (P) |
| getProvincialManagementData | 1 + P | User::where role provincial (1) + DPAccountDetail per provincial (P) |
| getSystemHealthData | 3 | DPAccountDetail (1), ActivityHistory (1), User count (1) |

P = number of provinces/provincials.

### 2.3 Real-Time Widgets (Always Run)

| Widget | Queries |
|--------|---------|
| getPendingApprovalsData | Project::inFinancialYear (1), DPReport (1), Project (1) |
| getSystemActivityFeedData | Project::inFinancialYear (1), ActivityHistory (1) |

---

## 3. Duplicate Query Patterns

| Query Pattern | Used In | Count | Impact | Suggested Fix |
|---------------|---------|-------|--------|---------------|
| DPAccountDetail::whereIn(sum) | getSystemPerformanceData | 1 + provinces | Medium | Pre-aggregate expenses by report_id once |
| DPAccountDetail::whereIn(sum) | getSystemAnalyticsData | 4×months + provinces | High | Single query: report_id, sum(total_expenses) grouped; use in-memory |
| DPAccountDetail::whereIn(sum) | getSystemBudgetOverviewData | 2 + types + provinces + centers + provincials + 6 months + 10 projects | High | Pre-compute report_id → total_expenses map |
| DPAccountDetail::whereIn(sum) | getProvinceComparisonData | provinces | Medium | Use pre-aggregated map |
| DPAccountDetail::whereIn(sum) | getProvincialManagementData | provincials | Medium | Use pre-aggregated map |
| DPAccountDetail::whereIn(sum) | getSystemHealthData | 1 | Low | Could use map |
| User::whereIn/distinct | buildPayload, getProvinceComparisonData | 4+ | Low | Cache or consolidate |
| Project::inFinancialYear | getPendingApprovalsData, getSystemActivityFeedData | 2 | Low | Could share fyProjectIds if passed |

---

## 4. Resolver Analysis

| Aspect | Status |
|--------|--------|
| resolveCollection | ✓ Called once in buildCoordinatorDashboardPayload |
| Per-project resolve() in loops | ✗ None — all widgets use $resolvedFinancials map |
| Resolver call count | 1 per cache miss |

**Conclusion:** Resolver usage is optimized. No changes needed.

---

## 5. DPAccountDetail Expense Aggregation

**Current:** ~20+ separate `DPAccountDetail::whereIn('report_id', $ids)->sum('total_expenses')` calls.

**Recommendation:** Single pre-aggregation:

```php
$allApprovedReportIds = $allReports->whereIn('status', DPReport::APPROVED_STATUSES)->pluck('report_id');
$expensesByReport = DPAccountDetail::whereIn('report_id', $allApprovedReportIds)
    ->selectRaw('report_id, SUM(total_expenses) as total')
    ->groupBy('report_id')
    ->pluck('total', 'report_id');
```

Then aggregate in memory: `$expenses = $reportIds->sum(fn($id) => $expensesByReport[$id] ?? 0)`.

**Impact:** ~15–20 fewer queries per dashboard load.

---

## 6. getProvincialOverviewData

**Current:** Cached 5 min. Inside cache:
- `User::where('role','provincial')->withCount([...])->get()` — 1 query
- Per provincial: `User::where parent_id`, `Project::whereIn`, `DPReport` (4 queries), `Project` (1)
- Total: 1 + N×(2 + 4 + 1) = 1 + 7N queries where N = provincial count

**Issue:** Per-provincial queries inside cache; not using shared dataset.

**Recommendation:** Refactor to use shared teamProjects, allReports, and partitions. Derive provincial metrics from partitions + teamProjects filtered by `user->parent_id`.

---

## 7. getRecentActivity

**Current:** `ProjectStatusHistory::with('project','changedBy')->orderBy('created_at')->take(5)`.

**Issues:**
- Not scoped to FY or teamProjects
- Returns global recent status changes

**Recommendation:** Filter by projects in teamProjects: `whereIn('project_id', $teamProjects->pluck('project_id'))`.

---

## 8. Province Partitioning

**Current:** Partitions built once:
- projectsByProvince
- reportsByProvince
- approvedProjectsByProvince

Widgets receive and use these. Province-based loops iterate over partitions; no per-province project queries.

**Gap:** DPAccountDetail is still queried per province/type/center. Pre-aggregation would eliminate that.

---

## 9. FY Propagation

| Component | FY Applied |
|-----------|------------|
| DatasetCacheService | ✓ |
| getPendingApprovalsData | ✓ |
| getSystemActivityFeedData | ✓ |
| getProvincialOverviewData | ✓ |
| getRecentActivity | ✗ Not scoped |

---

## 10. Controller Complexity

| Metric | Value |
|--------|-------|
| coordinatorDashboard() | ~65 lines |
| buildCoordinatorDashboardPayload() | ~105 lines |
| Total widget methods | 10 |
| Total CoordinatorController | ~3200+ lines |

**Observation:** Controller is large. Widget methods could be moved to a dedicated service for testability and clarity. Not critical for performance.

---

## 11. Caching Layers

| Layer | Status | TTL |
|-------|--------|-----|
| Dashboard (Phase 7) | ✓ | 10 min |
| Dataset (DatasetCacheService) | ✓ | 10 min |
| getPendingApprovalsData | ✓ | 2 min |
| getSystemActivityFeedData | ✓ | 2 min |
| getProvincialOverviewData | ✓ | 5 min |

---

## 12. Query Count Estimate

| Scenario | Estimated Queries |
|----------|-------------------|
| Cache HIT | ~5–8 (pending, activity, session, etc.) |
| Cache MISS | 50–150+ (depends on provinces, project types, months) |

Breakdown (cache miss):
- Dataset: 1
- DPReport: 1
- User: 4
- ProjectStatusHistory: 1
- getProvincialOverviewData (cached, but first load): 1 + 7×provincials
- DPAccountDetail: 20+
- ActivityHistory (getSystemHealthData): 1
- Pending/Activity: 4

---

## 13. Target Architecture (Recommended)

```
Request
    ↓
Dashboard cache (Phase 7) — HIT: return
    ↓ MISS
buildCoordinatorDashboardPayload
    ├─ DatasetCacheService::getCoordinatorDataset
    ├─ resolveCollection
    ├─ DPReport::whereIn (reports)
    ├─ Pre-aggregate: DPAccountDetail → report_id → total_expenses (1 query)
    ├─ Partitions
    ├─ Filter options (provinces, centers, parents) — consider cache
    ├─ getRecentActivity (scoped to teamProjects)
    ├─ getProvincialOverviewData — refactor to use partitions
    └─ Widget methods — use $expensesByReport map instead of DPAccountDetail queries
    ↓
Pending + Activity (real-time)
    ↓
View
```

---

## 14. Recommendations

### Critical

1. **Pre-aggregate DPAccountDetail:** Single query for all approved report expenses; build `report_id => total_expenses` map; use in-memory aggregation in all widgets.
2. **Scope getRecentActivity to teamProjects:** Filter ProjectStatusHistory by project_id in teamProjects.

### High Impact

3. **Refactor getProvincialOverviewData:** Use shared teamProjects, allReports, and partitions instead of per-provincial DB queries.
4. **Cache filter options:** provinces, centers, parents — these rarely change; 5–10 min cache.

### Medium Impact

5. **Share fyProjectIds:** Pass to getPendingApprovalsData and getSystemActivityFeedData to avoid redundant Project::inFinancialYear()->pluck().
6. **Extract CoordinatorDashboardService:** Move buildCoordinatorDashboardPayload and widget logic to a dedicated service for testability.

### Optional

7. **Batch ActivityHistory for health widget:** If ActivityHistory query is slow, consider caching or scoping.
8. **Lazy-load heavy widgets:** Consider async loading for analytics charts if they remain slow after expense pre-aggregation.

---

## 15. Expected Performance Improvements

| Change | Query Reduction | Est. Impact |
|--------|-----------------|-------------|
| DPAccountDetail pre-aggregation | 15–20 | 30–40% fewer queries |
| getProvincialOverviewData refactor | 7×provincials | 20–30% fewer |
| getRecentActivity scope | 0 (same count) | Correctness |
| Filter options cache | 3 | Minor |
| **Total (cache miss)** | **~25–35** | **~40–50%** fewer queries |

On cache hit: ~5–8 queries (mostly pending + activity). No change needed for hit path.

---

## 16. Suggested Implementation Phases

### Phase A: Expense Pre-Aggregation (High ROI)
- Add single DPAccountDetail aggregation query in buildCoordinatorDashboardPayload.
- Build `$expensesByReport` map.
- Pass to widgets; replace all `DPAccountDetail::whereIn()->sum()` with in-memory aggregation.
- **Files:** CoordinatorController (buildCoordinatorDashboardPayload, getSystemPerformanceData, getSystemAnalyticsData, getSystemBudgetOverviewData, getProvinceComparisonData, getProvincialManagementData, getSystemHealthData).

### Phase B: getRecentActivity FY Scope
- Filter ProjectStatusHistory by project_id in teamProjects.
- **Files:** CoordinatorController::getRecentActivity.

### Phase C: getProvincialOverviewData Refactor
- Derive provincial metrics from teamProjects, allReports, and partitions.
- Remove per-provincial DB queries.
- **Files:** CoordinatorController::getProvincialOverviewData.

### Phase D: Filter Options Cache
- Cache provinces, centers, parents with 5–10 min TTL.
- **Files:** CoordinatorController::buildCoordinatorDashboardPayload.

### Phase E: Optional Service Extraction
- Create CoordinatorDashboardDatasetService / CoordinatorDashboardMetricsService.
- **Files:** New services, CoordinatorController.
