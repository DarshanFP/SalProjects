# Phase 6 — Dashboard Cache Layer Feasibility Audit

**Date:** 2026-03-05  
**Phase:** Phase 6 — Dashboard Cache Layer  
**Reference:** Provincial_Dashboard_FY_Architecture_Implementation_Plan.md  

---

## Executive Summary

Dashboard caching is **feasible with clear scope and invalidation rules**. The pipeline is: **QUERY → DATASET CACHE → RESOLVER BATCH → AGGREGATION → VIEW**. Cache key must include `province_id`, `fy`, and a **filter hash** (center, role, project_type) because `budgetSummaries` depends on filters. **Recommendation:** Cache final view data (Option A) with key `provincial_dashboard_{province_id}_{fy}_{filterHash}`; TTL 5–10 minutes; General users bypass cache; wire invalidation to the same events as DatasetCacheService::clearProvincialDataset.

---

## Step 1 — Current Dashboard Execution Flow

### Pipeline

```
Request (fy, center, role, project_type)
    → baseProjectsQuery (approved + filters)
    → $projects (for budgetSummaries)
    → DatasetCacheService::getProvincialDataset() → $teamProjects
    → ProjectFinancialResolver::resolveCollection($teamProjects) → $resolvedFinancials
    → calculateBudgetSummariesFromProjects($projects, ..., $resolvedFinancials)
    → calculateTeamPerformanceMetrics(..., $teamProjects, $resolvedFinancials)
    → prepareChartDataForTeamPerformance(...)
    → calculateCenterPerformance(...)
    → calculateEnhancedBudgetData(...)
    → prepareCenterComparisonData(...)
    → pendingData, teamMembers, approvalQueue, fyList, etc.
    → view('provincial.index', compact(...))
```

### Computed Dashboard Outputs

| Output | Source | Filter-Dependent? |
|--------|--------|-------------------|
| budgetSummaries | calculateBudgetSummariesFromProjects($projects) | **Yes** — uses $projects (filtered) |
| performanceMetrics | calculateTeamPerformanceMetrics($teamProjects) | No |
| chartData | prepareChartDataForTeamPerformance($teamProjects) | No |
| centerPerformance | calculateCenterPerformance($teamProjects) | No |
| budgetData | calculateEnhancedBudgetData($teamProjects) | No |
| centerComparison | prepareCenterComparisonData($teamProjects) | No |
| societyStats | getSocietyStats (when enableSocietyBreakdown) | No (province-wide) |

### Other View Variables (Not All Cacheable)

- **Filter options:** centers, allCenters, roles, projectTypes, fyList — derived from queries; can be recomputed or cached with province+fy.
- **Pending/approval data:** pendingProjects, pendingReports, approvalQueue — real-time; **do not cache** (or use very short TTL).
- **Team members:** teamMembers, teamStats — relatively stable; could be cached with province+fy.

---

## Step 2 — Cache Key Design

### Deterministic Key Structure

```
provincial_dashboard_{province_id}_{fy}_{filterHash}
```

Where `filterHash` = `md5(center|role|project_type)` for compact uniqueness. Empty filters → `md5('||')`.

**Examples:**
- `provincial_dashboard_5_2025-26_a1b2c3...` (with filters)
- `provincial_dashboard_5_2025-26_d4e5f6...` (no filters: center=, role=, type=)

### Filter Influence

| Filter | Affects | Cached? |
|--------|---------|---------|
| center | budgetSummaries ($projects filtered by user.center) | Must be in key |
| role | budgetSummaries ($projects filtered by user.role) | Must be in key |
| project_type | budgetSummaries ($projects filtered) | Must be in key |

**Conclusion:** Include all three filters in cache key; otherwise budgetSummaries would be incorrect on cache hit.

### Key Builder

```php
$filterHash = md5(
    ($request->input('center') ?? '') . '|' .
    ($request->input('role') ?? '') . '|' .
    ($request->input('project_type') ?? '')
);
$cacheKey = "provincial_dashboard_{$provinceId}_{$fy}_{$filterHash}";
```

---

## Step 3 — Cache Scope Analysis

### Scope Options

| Scope | Key | Reuse | Correctness |
|-------|-----|-------|-------------|
| A) Province + FY only | `{province_id}_{fy}` | High | Incorrect — budgetSummaries ignores filters |
| B) Province + FY + filters | `{province_id}_{fy}_{filterHash}` | Medium | Correct |
| C) Province + FY + user session | `{province_id}_{fy}_{sessionId}` | Low | Overly granular |

**Recommendation:** Scope B — Province + FY + filter hash.

### User Roles

- **Provincial:** province_id from user; cache key uses province_id. Multiple provincials in same province can share cache (same data).
- **General:** DatasetCacheService bypasses cache (session-dependent). Dashboard cache should **bypass** for general users to avoid incorrect scope.

---

## Step 4 — Cache Contents Design

### Option A — Cache Final Widget Data (Recommended)

Store the computed aggregates passed to the view:

```php
[
    'budgetSummaries' => [...],
    'performanceMetrics' => [...],
    'chartData' => [...],
    'centerPerformance' => [...],
    'budgetData' => [...],
    'centerComparison' => [...],
    'societyStats' => [...],
    'centers' => [...],
    'allCenters' => [...],
    'roles' => [...],
    'projectTypes' => [...],
    'fyList' => [...],
    'fy' => '2025-26',
    'enableSocietyBreakdown' => true,
    // Exclude: pendingProjects, pendingReports, approvalQueue, etc. (real-time)
]
```

**Pros:** Simple; one cache lookup returns view-ready data.  
**Cons:** Real-time data (pending, approval queue) must be recomputed on every request if excluded.

### Option B — Cache Resolved Financial Dataset

Cache `$teamProjects` + `$resolvedFinancials`; recompute widget aggregations on each request.

**Pros:** Smaller cache; invalidation only touches dataset.  
**Cons:** Aggregations still run; less performance gain. DatasetCacheService already caches teamProjects; would duplicate.

**Recommendation:** Option A — cache final widget data. Exclude pending/approval data; compute those on each request (lightweight).

---

## Step 5 — DatasetCacheService Integration

### Execution Order

```
1. Check dashboard cache (provincial_dashboard_{province_id}_{fy}_{filterHash})
   → HIT: merge with real-time data, return view
   → MISS: continue

2. DatasetCacheService::getProvincialDataset() → teamProjects
   (or cache hit for dataset)

3. ProjectFinancialResolver::resolveCollection(teamProjects) → resolvedFinancials

4. Widget aggregations

5. Store result in dashboard cache

6. Return view
```

### Relationship

| Layer | Key | TTL | Contents |
|-------|-----|-----|----------|
| DatasetCacheService | provincial_dataset_{provincial_id}_{fy} | 10 min | Project collection |
| Dashboard cache | provincial_dashboard_{province_id}_{fy}_{filterHash} | 5–10 min | Aggregated widget data |

Dashboard cache does **not** duplicate dataset storage; it stores **downstream** computed results. When dataset cache invalidates, dashboard cache should also invalidate (or expire via TTL).

---

## Step 6 — Cache TTL Strategy

| TTL | Freshness | Performance | Recommendation |
|-----|-----------|-------------|----------------|
| 5 min | Good | Good | Balanced |
| 10 min | OK | Better | Align with DatasetCacheService (10 min) |
| 15+ min | Stale risk | Best | Use only with robust invalidation |

**Recommendation:** 5–10 minutes. Make configurable via `config('dashboard.cache_ttl_minutes', 5)`.

---

## Step 7 — Cache Invalidation Strategy

### Events Requiring Invalidation

| Event | Impact |
|-------|--------|
| Project created/updated | Totals, counts, charts |
| Project approval status change | budgetSummaries, opening_balance |
| Report submitted/approved | Expenses, utilization |
| Budget modified | Resolver output, totals |

### Invalidation Options

| Option | Pros | Cons |
|--------|------|------|
| A) TTL only | Simple; no wiring | Stale data up to TTL |
| B) Manual invalidation | Explicit control | Must call from many places |
| C) Event-driven (observers) | Automatic | Requires wiring; cache key varies by province |

### Recommendation

- **Primary:** TTL (5–10 min).
- **Secondary:** Call `DashboardCacheService::clearForProvince(int $provinceId, string $fy)` (or similar) from the same code paths that call `DatasetCacheService::clearProvincialDataset()`. Clear all dashboard keys for that province+fy (all filter combinations). Pattern: `Cache::flush()` per-prefix, or iterate filter hashes (expensive); simpler: use cache tags if driver supports (Redis), or single TTL.

**Practical approach:** Rely on TTL for Phase 6; add explicit invalidation in a follow-up when wiring DatasetCacheService::clearProvincialDataset.

---

## Step 8 — Controller Integration Design

### Wrapper Pattern

```php
// Bypass for general users
if ($provincial->role === 'general') {
    // ... full computation, no dashboard cache ...
    return view('provincial.index', compact(...));
}

$provinceId = $provincial->province_id;
if (!$provinceId) {
    // ... full computation ...
    return view(...);
}

$filterHash = md5(
    ($request->input('center') ?? '') . '|' .
    ($request->input('role') ?? '') . '|' .
    ($request->input('project_type') ?? '')
);
$cacheKey = "provincial_dashboard_{$provinceId}_{$fy}_{$filterHash}";
$ttl = now()->addMinutes(config('dashboard.cache_ttl_minutes', 5));

$cached = Cache::get($cacheKey);
if ($cached !== null) {
    // Merge real-time data (pending, approval queue)
    $pendingData = $this->getPendingApprovalsForDashboard($provincial);
    // ... merge and return view ...
}

$dashboardData = Cache::remember($cacheKey, $ttl, function () use (...) {
    // Full dashboard computation
    // Return array for compact()
});

// Merge real-time data, return view
```

### Serialization

Laravel Cache::remember uses PHP serialize. Collections and arrays serialize correctly. Ensure no closures or non-serializable objects in cached data.

---

## Step 9 — Performance Impact Estimation

| Scale | Current (no dashboard cache) | With Dashboard Cache (hit) |
|-------|------------------------------|----------------------------|
| 100 projects | ~500–800 ms | ~50–100 ms (merge + view) |
| 500 projects | ~1.5–2.5 s | ~50–100 ms |
| 2,000 projects | ~5–10 s | ~50–100 ms |

**Benefits:**
- Near-instant repeated loads (cache hit)
- No dataset query, resolver, or widget aggregation on hit
- Reduced CPU and DB load

**Note:** Cache miss cost unchanged; first load per key pays full computation.

---

## Step 10 — Risk Analysis

| Risk | Severity | Mitigation |
|------|----------|------------|
| Stale dashboard values | Medium | TTL 5–10 min; optional explicit invalidation |
| Incorrect cache scope | High | Include province_id, fy, filterHash in key |
| Filter mismatch | High | Use same filter hash logic consistently |
| General user wrong cache | High | Bypass dashboard cache for general role |
| Memory pressure | Low | Widget data is compact; TTL limits retention |
| Serialization errors | Low | Avoid non-serializable objects (closures, resources) |
| Pending/approval staleness | Medium | Exclude from cache; compute on every request |

---

## Step 11 — Updated Phase 6 Implementation Plan

### Refinements

1. **Cache key:** `provincial_dashboard_{province_id}_{fy}_{filterHash}` where filterHash = md5(center|role|project_type).
2. **Scope:** Province + FY + filters. General users bypass cache.
3. **Contents:** Final widget data (budgetSummaries, performanceMetrics, chartData, centerPerformance, budgetData, centerComparison, societyStats, filter options, fyList). Exclude pending/approval data.
4. **TTL:** 5–10 minutes, configurable.
5. **Invalidation:** TTL primary; optional explicit clear aligned with DatasetCacheService.
6. **Integration:** Check dashboard cache first; on miss, run full pipeline; store result; merge real-time data before view.

---

## Verification Checklist

| # | Verification | Status |
|---|--------------|--------|
| 1 | All computed dashboard outputs identified | ✓ |
| 2 | Filter influence on budgetSummaries documented | ✓ |
| 3 | Cache key structure defined | ✓ |
| 4 | Cache scope (province + fy + filters) chosen | ✓ |
| 5 | Cache contents (widget data) defined | ✓ |
| 6 | General user bypass specified | ✓ |
| 7 | DatasetCacheService integration analyzed | ✓ |
| 8 | TTL strategy defined | ✓ |
| 9 | Invalidation strategy documented | ✓ |
| 10 | Controller integration pattern designed | ✓ |
| 11 | Performance impact estimated | ✓ |
| 12 | Risks and mitigations listed | ✓ |
