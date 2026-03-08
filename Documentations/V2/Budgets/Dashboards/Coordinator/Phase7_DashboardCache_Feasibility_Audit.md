# Phase 7 — Coordinator Dashboard Cache Feasibility Audit

**Date:** 2026-03-06  
**Scope:** Full Coordinator Dashboard Cache layer  
**Objective:** Verify feasibility and safety of implementing a dashboard cache without breaking data correctness, cache invalidation, or real-time widgets.

---

## 1. Current Dashboard Architecture Summary

### 1.1 Pipeline (Phases 1–6)

```
coordinatorDashboard(Request $request)
    │
    ├── $fy = request('fy') ?? FinancialYearHelper::currentFY()
    ├── $filters = [province, center, role, parent_id, project_type]
    │
    ├── DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)
    │       → Cache: coordinator_dataset_{fy} (10 min TTL)
    │       → Filters applied in-memory after retrieval
    │
    ├── ProjectFinancialResolver::resolveCollection($teamProjects)
    │       → Single batch resolution
    │
    ├── DPReport::whereIn('project_id', $teamProjects->pluck('project_id'))->with('user')->get()
    │       → Reports loaded once per request
    │
    ├── Partitions built once:
    │       $projectsByProvince, $reportsByProvince, $approvedProjectsByProvince
    │
    └── Widgets receive shared data (no internal Project/DPReport queries in audited widgets)
```

### 1.2 Dataset Cache Design

| Aspect | Implementation |
|--------|----------------|
| Key | `coordinator_dataset_{$fy}` |
| TTL | 10 minutes |
| Filter handling | In-memory via `applyCoordinatorFilters()` |
| Clear method | `DatasetCacheService::clearCoordinatorDataset(string $fy)` |

### 1.3 Widget Data Sources (Phase 6 Validated)

| Widget | Data Source | Project/DPReport Queries |
|--------|-------------|--------------------------|
| calculateBudgetSummariesFromProjects | $projects, $resolvedFinancials | 0 |
| getSystemPerformanceData | $teamProjects, $resolvedFinancials, partitions | 0 |
| getSystemAnalyticsData | $teamProjects, $resolvedFinancials, partitions | 0 |
| getSystemBudgetOverviewData | $teamProjects, $resolvedFinancials, $approvedProjectsByProvince, $allReports | 0 |
| getProvinceComparisonData | $teamProjects, $resolvedFinancials, partitions | 0 |
| getProvincialManagementData | $teamProjects, $resolvedFinancials, $allReports | 0 |
| getSystemHealthData | $teamProjects, $resolvedFinancials, $allReports | 0 |

---

## 2. Dashboard Payload Structure

### 2.1 Full Payload Passed to View

| Block | Source | Cacheable? |
|-------|--------|------------|
| budgetSummaries | calculateBudgetSummariesFromProjects | Yes |
| provinces | User::whereIn('role', ...) | Yes |
| centers | User::whereIn(...) | Yes |
| roles | Static array | Yes |
| parents | User::where('role', 'provincial') | Yes |
| projectTypes | $projects->pluck('project_type') | Yes |
| statistics | $allProjects + getRecentActivity | **Partially** (see 3.2) |
| allProjects | $teamProjects | Yes |
| pendingApprovalsData | getPendingApprovalsData($fy) | **No — Real-time** |
| provincialOverviewData | getProvincialOverviewData($fy) | Yes (has own 5 min cache) |
| systemPerformanceData | getSystemPerformanceData | Yes |
| systemAnalyticsData | getSystemAnalyticsData | Yes |
| systemActivityFeedData | getSystemActivityFeedData($fy) | **No — Real-time** |
| systemBudgetOverviewData | getSystemBudgetOverviewData | Yes |
| provinceComparisonData | getProvinceComparisonData | Yes |
| provincialManagementData | getProvincialManagementData | Yes |
| systemHealthData | getSystemHealthData | Yes |
| fy | Request / helper | Yes |
| availableFY | FinancialYearHelper::listAvailableFY() | Yes |

### 2.2 Request-Dependent Parameters

- **fy:** From request; must be in cache key
- **filters:** province, center, role, parent_id, project_type; must be in cache key (filter hash)
- **analytics_range:** Default 30; passed to getSystemAnalyticsData; must be in cache key

---

## 3. Real-Time Widget Exclusions

### 3.1 Must NOT Be Cached

| Widget | Reason |
|--------|--------|
| **getPendingApprovalsData** | Pending reports/projects awaiting coordinator action; status changes frequently; users expect immediate visibility |
| **getSystemActivityFeedData** | Activity history; new approvals/reverts/submissions should appear quickly |

### 3.2 Partially Real-Time (Design Decision)

| Block | Detail |
|-------|--------|
| **statistics.recent_activity** | Uses `getRecentActivity($allProjects)` which queries `ProjectStatusHistory`. Mix of shared data (recent projects) and live status changes. **Recommendation:** Include in cache; 5 min staleness acceptable for "recent activity" UX. |

### 3.3 Already Independently Cached

| Widget | Cache Key | TTL |
|--------|-----------|-----|
| getPendingApprovalsData | coordinator_pending_approvals_data_{$fy} | 2 min |
| getSystemActivityFeedData | coordinator_system_activity_feed_data_{$fy}_{$limit} | 2 min |
| getProvincialOverviewData | coordinator_provincial_overview_data_{$fy} | 5 min |

These widgets run their own queries and do **not** consume the shared dataset. Dashboard cache will skip the full pipeline; real-time widgets will still need fresh data on each request.

---

## 4. Cache Key Design

### 4.1 Recommended Cache Key

```
coordinator_dashboard_{$fy}_{$filterHash}_{$analyticsRange}
```

- **fy:** Financial year (e.g. "2025-26")
- **filterHash:** `md5(json_encode(array_filter([province, center, role, parent_id, project_type])))` — sort keys for stability
- **analyticsRange:** Request `analytics_range` (default 30)

### 4.2 Filter Hash Stability

Ensure consistent ordering of filter keys before hashing (e.g. `ksort`) so `['province'=>'X','center'=>'Y']` and `['center'=>'Y','province'=>'X']` produce the same hash.

### 4.3 Independence from Dataset Cache

- Dataset cache key: `coordinator_dataset_{$fy}` (no filter hash)
- Dashboard cache key: `coordinator_dashboard_{$fy}_{$filterHash}_{$analyticsRange}` (includes filter hash)

Different filter combinations produce different dashboard payloads but may share the same underlying dataset.

---

## 5. Cache Invalidation Strategy

### 5.1 Current State

| Method | Status |
|--------|--------|
| `clearCoordinatorDataset(string $fy)` | Implemented in DatasetCacheService; **not wired** to any event |
| `invalidateDashboardCache()` | Implemented in CoordinatorController; called on approval/revert actions; **key misalignment** |

### 5.2 invalidateDashboardCache() Key Misalignment

**Current keys cleared:**
- `coordinator_pending_approvals_data` (no FY)
- `coordinator_provincial_overview_data` (no FY)
- `coordinator_system_performance_data`
- `coordinator_system_activity_feed_data_50` (hardcoded limit)
- `coordinator_system_budget_overview_data`
- `coordinator_province_comparison_data`
- `coordinator_provincial_management_data`
- `coordinator_system_health_data`
- `coordinator_system_analytics_data_{range}` (all ranges)
- `coordinator_report_list_filters`, `coordinator_project_list_filters`

**Actual widget keys in use:**
- `coordinator_pending_approvals_data_{$fy}`
- `coordinator_provincial_overview_data_{$fy}`
- `coordinator_system_activity_feed_data_{$fy}_{$limit}`

**Finding:** invalidateDashboardCache clears keys without FY; widgets use FY in key. **Keys are misaligned**; some caches may not invalidate correctly.

### 5.3 Invalidation Events (Recommended)

| Event | Action |
|-------|--------|
| Project approval/revert | `clearCoordinatorDataset($fy)`; clear dashboard keys for that FY |
| Report approval/revert | Same |
| Budget sync / project updates | Same |

### 5.4 Implementation Gap

- `clearCoordinatorDataset` is **not** called from any approval/revert/budget handler
- `invalidateDashboardCache` is called from CoordinatorController (approve report, revert report, approve project, etc.) but uses outdated keys
- Phase 7 implementation must: (1) add `clearCoordinatorDataset($fy)` to invalidation path, (2) add dashboard cache key clearing, (3) fix key alignment for existing widget caches

---

## 6. Filter Handling Verification

### 6.1 Current Behavior

- **Dataset:** Loaded with `getCoordinatorDataset($coordinator, $fy, $filters)`
- **Filters:** Applied **in-memory** via `applyCoordinatorFilters()` after dataset retrieval
- **Dataset cache key:** `coordinator_dataset_{$fy}` — no filter hash; filters applied post-retrieval
- **Result:** Correct. Same FY dataset supports all filter combinations.

### 6.2 Dashboard Cache Implication

Different filter combinations produce different:
- `$teamProjects` (filtered in-memory)
- `$allReports` (derived from filtered projects)
- All downstream widget outputs

Therefore the **dashboard cache key must include the filter hash**.

---

## 7. Duplicate Queries Inside Widgets

Phase 6 audit confirmed **no** Project:: or DPReport:: queries inside the seven audited widgets. Widgets consume shared dataset, partitions, and resolved financial map only.

**Exception:** getProvincialManagementData calls `User::where('role', 'provincial')` and getProvinceComparisonData calls `User::whereNotNull('province')` — these are metadata queries for provincial/user counts, not project/report data. Acceptable for dashboard cache; data changes infrequently.

---

## 8. Resolver Architecture Verification

- `resolveCollection($teamProjects)` is called **once** in coordinatorDashboard (line 71)
- All widgets receive `$resolvedFinancials` and use `$resolvedFinancials[$project->project_id]`
- No per-project `$resolver->resolve()` calls in dashboard flow

---

## 9. Province Partition Reuse Verification

- Partitions built once: `$projectsByProvince`, `$reportsByProvince`, `$approvedProjectsByProvince`
- All widgets that need province breakdown receive these partitions; no internal `groupBy('province')` in audited widgets

---

## 10. Cache Placement Point

**Recommended location:** `CoordinatorController::coordinatorDashboard()`

**Structure:**
```
1. $fy, $filters, $analyticsRange from request
2. Build cache key: coordinator_dashboard_{fy}_{filterHash}_{analyticsRange}
3. $cached = Cache::get($cacheKey)
4. if ($cached):
       $pendingApprovalsData = getPendingApprovalsData($fy)
       $systemActivityFeedData = getSystemActivityFeedData($fy, 50)
       Merge $cached with real-time data
       return view(..., merged)
5. else:
       Run full pipeline (dataset → resolver → reports → partitions → widgets)
       $cacheablePayload = [...all except pendingApprovalsData, systemActivityFeedData]
       Cache::put($cacheKey, $cacheablePayload, TTL)
       $pendingApprovalsData = getPendingApprovalsData($fy)
       $systemActivityFeedData = getSystemActivityFeedData($fy, 50)
       return view(..., full payload)
```

---

## 11. Performance Impact Estimate

### 11.1 Current (Cache Miss) — Approximate Query Count

| Component | Queries |
|-----------|---------|
| DatasetCacheService (hit) | 0 (or 1 on miss) |
| resolveCollection | 0 (in-memory; may trigger lazy loads) |
| DPReport::whereIn | 1 |
| Filter options (provinces, centers, parents) | 3–4 |
| getPendingApprovalsData | 2–3 (Project, DPReport) |
| getProvincialOverviewData | Multiple (User, Project, DPReport) |
| getSystemActivityFeedData | 2 (Project, ActivityHistory) |
| getRecentActivity | 1 (ProjectStatusHistory) |
| Widgets (7) | 0 (shared data) |
| User queries inside widgets | ~2–3 |
| DPAccountDetail | Multiple (per aggregation) |

**Rough total:** 50–100+ queries depending on data size.

### 11.2 After Dashboard Cache (Cache Hit)

| Component | Queries |
|-----------|---------|
| Cache::get | 0 (memory/redis read) |
| getPendingApprovalsData | 2–3 |
| getSystemActivityFeedData | 2 |
| **Total** | ~4–5 |

**Expected reduction:** ~90%+ on cache hit for coordinator dashboard.

---

## 12. Implementation Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Dataset mutation | Low | Widgets treat collections as read-only; no transform/push in pipeline |
| Cache pollution | Medium | TTL (5–10 min) limits staleness; invalidation on approval/revert |
| Incorrect filter handling | Low | Filter hash in cache key; filters applied in-memory consistently |
| Missing invalidation hooks | **High** | `clearCoordinatorDataset` and dashboard keys not wired to events; must add |
| Key misalignment in invalidateDashboardCache | **High** | Current keys don't match widget keys; fix before or during Phase 7 |
| Serialization of Eloquent models | Medium | Cache stores Collections; ensure Laravel cache driver handles model serialization (Redis/array fine; file cache may have issues) |

---

## 13. Cache Feasibility Verdict

### **READY** (with pre-implementation adjustments)

The Coordinator dashboard architecture **is ready** for Phase 7 dashboard cache implementation, provided:

1. **Cache invalidation is aligned:** Update `invalidateDashboardCache()` to use FY-specific keys and to call `DatasetCacheService::clearCoordinatorDataset($fy)`; add clearing of dashboard cache keys for affected FY.
2. **Wire clearCoordinatorDataset:** Call `clearCoordinatorDataset($fy)` from the same approval/revert/budget handlers that call `invalidateDashboardCache()`.
3. **Real-time exclusions:** Exclude `pendingApprovalsData` and `systemActivityFeedData` from cached payload; recompute on every request.
4. **Cache key design:** Use `coordinator_dashboard_{$fy}_{$filterHash}_{$analyticsRange}`.

---

## 14. Implementation Recommendations

1. **Pre-Phase 7: Fix invalidateDashboardCache**
   - Align keys with actual widget cache keys (include `{$fy}` where used)
   - Add `DatasetCacheService::clearCoordinatorDataset($fy)` (call with current FY or iterate available FYs if event has no FY context)
   - Add dashboard cache key prefix clear: `coordinator_dashboard_*` (if using Redis SCAN/tags) or iterate known FYs

2. **Phase 7: Dashboard Cache Wrapper**
   - Implement cache check at start of `coordinatorDashboard`
   - Build cache key from `$fy`, `$filterHash`, `$analyticsRange`
   - On hit: load cached payload; compute `pendingApprovalsData` and `systemActivityFeedData`; merge; return view
   - On miss: run full pipeline; build cacheable payload (exclude real-time); `Cache::put`; return view

3. **Config**
   - Add `coordinator_dashboard_cache_ttl_minutes` to `config/dashboard.php` (e.g. 5–10 min)

4. **Validation**
   - Verify cache hit path returns identical view output (except real-time blocks)
   - Verify filter change causes cache miss
   - Verify FY change causes cache miss
   - Verify approval/revert clears cache and next load is fresh

---

## 15. References

- `Coordinator_Dashboard_Implementation_Roadmap.md`
- `Phase6_SharedDataset_Audit.md`
- `Phase6_SharedDataset_Remediation.md`
- `Phase4_ProvincePartition_Implementation.md`
- `Phase5_ResolverBatch_Implementation.md`
- `Provincial/Phase6_DashboardCache_Implementation.md` (reference pattern)
