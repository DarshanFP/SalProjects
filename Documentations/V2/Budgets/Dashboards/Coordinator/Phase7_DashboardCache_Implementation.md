# Phase 7 — Coordinator Dashboard Cache Implementation

**Date:** 2026-03-07  
**Status:** Complete  
**Reference:** Phase7_DashboardCache_Feasibility_Audit.md, Phase7_CacheInvalidation_Fix.md

---

## 1. Phase Overview

Phase 7 wraps the Coordinator dashboard controller pipeline in a cache layer that:

- Uses FY-based cache keys (with filter hash and analytics range)
- Reuses DatasetCacheService outputs (dashboard cache sits above dataset cache)
- Preserves existing architecture (DatasetCacheService, resolver batch, widget pipeline)
- Supports cache invalidation via `coordinator_dashboard` tag
- Improves dashboard response time on cache hits

**Architecture after Phase 7:**

```
Controller Cache (coordinator_dashboard tag)
        ↓
DatasetCacheService::getCoordinatorDataset
        ↓
ProjectFinancialResolver::resolveCollection
        ↓
Widget data preparation
```

---

## 2. Cache Architecture

### 2.1 Cache Hit Path

```
Request → coordinatorDashboard()
    ↓
Extract $fy, $filterData, $analyticsRange
    ↓
Build $cacheKey = coordinator_dashboard_{fy}_{filterHash}_{analyticsRange}
    ↓
Cache::tags(['coordinator_dashboard'])->remember($cacheKey, TTL, callback)
    ↓
HIT: callback not executed; payload returned from cache
    ↓
Compute pendingApprovalsData + systemActivityFeedData (always dynamic)
    ↓
Merge → view
```

### 2.2 Cache Miss Path

```
Request → coordinatorDashboard()
    ↓
Cache miss → callback executes buildCoordinatorDashboardPayload()
    ↓
DatasetCacheService::getCoordinatorDataset()
    ↓
ProjectFinancialResolver::resolveCollection()
    ↓
DPReport::whereIn() → load reports
    ↓
Build partitions: projectsByProvince, reportsByProvince, approvedProjectsByProvince
    ↓
Execute all widgets (shared dataset)
    ↓
Return cacheable payload (exclude pendingApprovalsData, systemActivityFeedData)
    ↓
Store in cache; compute live widgets; merge → view
```

### 2.3 Fallback (Tags Not Supported)

When cache driver does not support tags (e.g. file driver):

- `Cache::tags()->remember()` throws
- Fallback: call `buildCoordinatorDashboardPayload()` directly (no cache)
- Compute live widgets and return view
- No functional regression

---

## 3. Cache Key Strategy

### 3.1 Format

```
coordinator_dashboard_{$fy}_{$filterHash}_{$analyticsRange}
```

### 3.2 Components

| Component     | Source                                                       | Example   |
|--------------|--------------------------------------------------------------|-----------|
| fy           | `$request->input('fy', FinancialYearHelper::currentFY())`    | `2025-26` |
| filterHash   | `md5(json_encode(ksort($filterData)))`                       | 32-char   |
| analyticsRange | `$request->get('analytics_range', 30)`                     | `30`      |

### 3.3 Example Cache Keys

| Scenario                  | Example Key                                              |
|---------------------------|----------------------------------------------------------|
| FY 2025-26, no filters    | `coordinator_dashboard_2025-26_d41d8cd98f00b204e9800998ecf8427e_30` |
| FY 2024-25, province=X    | `coordinator_dashboard_2024-25_{hash}_30`                |
| FY 2025-26, analytics 90d | `coordinator_dashboard_2025-26_{hash}_90`                |

Changing FY produces a different cache entry (unique key per FY).

---

## 4. TTL Configuration

| Config Key                               | Default | Usage                            |
|------------------------------------------|---------|----------------------------------|
| `dashboard.coordinator_dashboard_cache_ttl_minutes` | 10      | Coordinator dashboard payload TTL |
| `dashboard.cache_ttl_minutes`             | 5       | Fallback if coordinator key not set |

Configured in `config/dashboard.php`:

```php
'coordinator_dashboard_cache_ttl_minutes' => 10,
```

---

## 5. Integration with DatasetCacheService

- `buildCoordinatorDashboardPayload()` calls `DatasetCacheService::getCoordinatorDataset($coordinator, $fy, $filters)` internally
- Dashboard cache does **not** bypass DatasetCacheService
- Dataset cache remains the first layer; dashboard cache is the second layer
- Both layers can be invalidated independently:
  - Dataset: `DatasetCacheService::clearCoordinatorDataset($fy)`
  - Dashboard: `Cache::tags(['coordinator_dashboard'])->flush()`

---

## 6. Cache Invalidation Compatibility

Phase 7 dashboard cache uses tag `coordinator_dashboard`. Invalidation:

```php
Cache::tags(['coordinator_dashboard'])->flush();
```

Invoked from `clearCoordinatorDashboardCache()` inside `invalidateDashboardCache()`. Called on:

- `refreshDashboard` (manual)
- `approveProject`, `revertProject`
- `approveReport`, `revertReport`
- `bulkReportAction`

No modifications to existing invalidation logic.

---

## 7. Performance Improvement Expectations

### 7.1 Query Count

| Scenario   | Queries | Components                                                          |
|------------|---------|---------------------------------------------------------------------|
| Cache miss | ~50–100+| Dataset, resolver, reports, filter options, widgets, DPAccountDetail, User, ActivityHistory |
| Cache hit  | ~5–10   | getPendingApprovalsData (Project, DPReport), getSystemActivityFeedData (ActivityHistory) |

### 7.2 Expected Improvement

- ~90%+ reduction in queries on cache hit
- ~10–30× faster response on cache hit (depending on data size)
- From ~200+ queries / multi-second render to ~5 queries / sub-second on cache hit

---

## 8. Safety Considerations

- No business logic changes; only cache wrapper added
- Query scopes unchanged
- Dataset structure unchanged
- Existing DatasetCacheService and resolver batch unchanged
- Real-time widgets (pending approvals, activity feed) never cached
- Blade templates unchanged; cached payload matches expected view variables

---

## 9. Files Modified

| File                                      | Changes                                                                 |
|-------------------------------------------|-------------------------------------------------------------------------|
| `app/Http/Controllers/CoordinatorController.php` | Cache wrapper using `Cache::tags()->remember()`, `buildCoordinatorDashboardPayload()` extraction |
| `config/dashboard.php`                    | Added `coordinator_dashboard_cache_ttl_minutes` => 10                   |
| `Documentations/.../Phase7_DashboardCache_Implementation.md` | Updated implementation report                                        |

---

## 10. Cached vs Non-Cached Data

### Cached (inside `buildCoordinatorDashboardPayload`)

- statistics
- budgetSummaries
- provincialOverviewData
- systemPerformanceData
- systemAnalyticsData
- systemBudgetOverviewData
- provinceComparisonData
- provincialManagementData
- systemHealthData
- provinces, centers, roles, parents, projectTypes
- allProjects, fy, availableFY

### Not Cached (always dynamic)

- pendingApprovalsData
- systemActivityFeedData
