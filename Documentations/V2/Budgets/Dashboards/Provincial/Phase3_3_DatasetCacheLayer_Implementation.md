# Phase 3.3 — Dataset Cache Layer Implementation

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard Dataset Cache  
**Goal:** Introduce a caching layer for the Provincial dashboard project dataset to eliminate repeated heavy database queries.

---

## Summary

A `DatasetCacheService` was created to cache the Provincial dashboard project dataset. Provincial users receive cached data; General users bypass cache due to session-dependent scope. Cache TTL is 10 minutes.

---

## 1. Service Created

**File:** `app/Services/DatasetCacheService.php`

**Class:** `App\Services\DatasetCacheService`

### Methods

| Method | Purpose |
|--------|---------|
| `getProvincialDataset($provincial, string $fy)` | Returns project dataset (cached for provincial, direct for general) |
| `clearProvincialDataset(int $provincialId, string $fy)` | Invalidates cached dataset for the given provincial and FY |

---

## 2. Cache Key Design

| Component | Value |
|-----------|-------|
| Key format | `provincial_dataset_{provincialId}_{fy}` |
| Example | `provincial_dataset_42_2025-26` |
| Determinism | Provincial ID + FY uniquely identify the project dataset for Provincial role |

**General users:** Cache is bypassed; dataset is fetched directly. General scope depends on `session('province_filter_ids')` and `session('province_filter_all')`, so a cache key would not be deterministic without session context.

---

## 3. Controller Changes

**File:** `app/Http/Controllers/ProvincialController.php`

### Import added

```php
use App\Services\DatasetCacheService;
```

### Change in `provincialDashboard()`

**Before:**
```php
// Phase 3.2: Load shared dataset once for widget methods (all statuses in FY)
$teamProjectsInFy = $baseProjectsQuery->with(['user', 'reports.accountDetails'])->get();
```

**After:**
```php
// Phase 3.3: Load shared dataset via cache (provincial role); General bypasses cache
$teamProjectsInFy = DatasetCacheService::getProvincialDataset($provincial, $fy);
```

### Unchanged

- `$baseProjectsQuery` is still built via `ProjectQueryService::forProvincial()` and used for:
  - Filtered `$projects` (approved, center/role/project_type filters)
  - `$projectTypes`, `$userIdsWithProjectsInFy`, `$centers`
- All widget methods (`calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateCenterPerformance`, `calculateEnhancedBudgetData`, `prepareCenterComparisonData`) continue to receive `$teamProjectsInFy` and operate on the same data structure.

---

## 4. TTL Configuration

| Setting | Value |
|---------|-------|
| TTL | 10 minutes |
| Implementation | `now()->addMinutes(10)` |
| Rationale | Matches feasibility audit; provides safety net alongside future event-based invalidation |

---

## 5. Cache Behavior Verification

| Scenario | Behavior |
|----------|----------|
| **First dashboard load (Provincial)** | Cache miss → DB query → result stored in cache |
| **Subsequent loads within 10 min (Provincial)** | Cache hit → no project query |
| **General user** | Bypass cache → DB query every request |
| **FY change** | Different cache key → cache miss for new FY |

### Widget Compatibility

- Dataset structure is identical to the previous implementation (projects with `user`, `reports`, `reports.accountDetails`).
- Widget calculations (budget summaries, performance metrics, charts, center comparison) continue to work with the cached collection.

---

## 6. Implementation Checklist

| Item | Status |
|------|--------|
| `DatasetCacheService` created | ✓ |
| `getProvincialDataset` implemented | ✓ |
| `clearProvincialDataset` implemented | ✓ |
| General users bypass cache | ✓ |
| ProvincialController updated | ✓ |
| TTL set to 10 minutes | ✓ |
| PHP syntax verified | ✓ |

---

## 7. Invalidation (Future Work)

The feasibility audit listed events that should invalidate the cache:

- Project approval / revert / reject
- Project update
- Report approval / revert / forward
- Budget sync

To invalidate when such events affect a provincial's dataset, call:

```php
DatasetCacheService::clearProvincialDataset($provincialId, $fy);
```

Identifying the affected `$provincialId` and `$fy` per event and wiring these calls is a follow-up task. Until then, the 10-minute TTL limits staleness.
