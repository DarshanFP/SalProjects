# Phase 6 — Dashboard Cache Layer Implementation

**Date:** 2026-03-05  
**Phase:** Phase 6 — Dashboard Cache Layer  
**Reference:** Phase6_DashboardCache_Feasibility_Audit.md  

---

## 1. Phase Overview

Phase 6 adds a **dashboard cache layer** that stores final aggregated widget data for a short duration. Repeated dashboard loads within the TTL are served from cache, skipping the dataset query, resolver batch, and widget aggregations. Real-time approval data (pending projects/reports, approval queue) is always recomputed. Provincial users with province_id use the cache; General users and users without province_id bypass it.

---

## 2. Dashboard Cache Architecture

### Execution Flow

```
Request
  → Check eligibility (provincial + province_id)
  → Build cache key (province_id + fy + filterHash)
  → Cache::get($cacheKey)
  → HIT: getRealtimeDashboardData() → merge → return view
  → MISS:
      → Full pipeline (DatasetCacheService → Resolver → Aggregations)
      → Cache::put($cacheKey, $dashboardCacheData, $ttl)
      → return view
```

### Cache Layers

| Layer | Key | TTL | Contents |
|-------|-----|-----|----------|
| DatasetCacheService | provincial_dataset_{id}_{fy} | 10 min | Project collection |
| Dashboard cache | provincial_dashboard_{province_id}_{fy}_{filterHash} | 5 min | Aggregated widget data |

---

## 3. Cache Key Design

```
provincial_dashboard_{province_id}_{fy}_{filterHash}
```

- **province_id:** From user
- **fy:** Financial year from request (e.g. "2025-26")
- **filterHash:** md5(center|role|project_type) from request

Filter changes produce a new cache key and cause a cache miss.

---

## 4. Cache Scope (Province + FY + Filters)

- **Provincial users with province_id:** Use cache
- **General users:** Bypass cache (session-dependent scope)
- **Users without province_id:** Bypass cache

---

## 5. Cache Contents

**Cached:**
- budgetSummaries, performanceMetrics, chartData, centerPerformance, budgetData, centerComparison
- societyStats, enableSocietyBreakdown
- centers, roles, projectTypes, fyList, fy
- teamMembers, teamStats, teamActivities

**Not cached (recomputed every request):**
- pendingProjects, pendingReports, approvalQueue, approvalQueueProjects, approvalQueueReports
- teamMembersForQueue
- pendingProjectsCount, pendingReportsCount, totalPendingCount
- urgentCount, normalCount, urgentProjectsCount, normalProjectsCount
- allCenters (centers + approvalQueueCenters)

---

## 6. Controller Changes

### provincialDashboard()

- Early check: `$useDashboardCache = $provincial->role !== 'general' && $provincial->province_id !== null`
- On eligible: build filterHash, cacheKey; attempt Cache::get
- On hit: call `getRealtimeDashboardData($provincial, $cachedDashboard['centers'])`, merge with cached data, return view
- On miss: run full pipeline unchanged
- After aggregations: if `$useDashboardCache`, build `$dashboardCacheData`, Cache::put
- Final return: view with merged data

### getRealtimeDashboardData($provincial, $centers)

New private method that returns:
- Pending and approval queue data
- Count and urgency fields
- allCenters (centers merged with approvalQueueCenters)

---

## 7. DatasetCacheService Integration

- Dashboard cache wraps the controller flow; it does not replace or modify DatasetCacheService
- On cache miss, DatasetCacheService::getProvincialDataset() is called as before
- On cache hit, neither DatasetCacheService nor the resolver runs

---

## 8. TTL Configuration

**File:** `config/dashboard.php`

```php
'cache_ttl_minutes' => 5,
```

Used: `config('dashboard.cache_ttl_minutes', 5)`

---

## 9. Real-time Data Handling

- `getRealtimeDashboardData()` is called on every request (cache hit and miss)
- On hit: provides pending, approval queue, counts, allCenters
- On miss: full pipeline computes these; same values passed to view

---

## 10. Performance Impact Analysis

| Scenario | Before Phase 6 | After Phase 6 (cache hit) |
|----------|----------------|---------------------------|
| 100 projects | ~500–800 ms | ~50–100 ms |
| 500 projects | ~1.5–2.5 s | ~50–100 ms |
| 2,000 projects | ~5–10 s | ~50–100 ms |

Cache hit skips: dataset query, DatasetCacheService lookup, resolveCollection, all widget aggregations.

---

## 11. Files Modified

| File | Changes |
|------|---------|
| `config/dashboard.php` | **Created** — cache_ttl_minutes |
| `app/Http/Controllers/ProvincialController.php` | Cache wrapper, cache key, getRealtimeDashboardData |

---

## 12. Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | Cache key includes province_id, fy, filterHash | ✓ |
| 2 | General users bypass dashboard cache | ✓ |
| 3 | Users without province_id bypass cache | ✓ |
| 4 | Cache hit skips dataset + resolver + aggregation | ✓ |
| 5 | Pending/approval data always recomputed | ✓ |
| 6 | allCenters derived from real-time approval queue | ✓ |
| 7 | Filter changes create new cache keys | ✓ |
| 8 | DatasetCacheService unchanged | ✓ |
| 9 | TTL configurable via config/dashboard.php | ✓ |
| 10 | Cached data serializable (arrays, collections, scalars) | ✓ |
