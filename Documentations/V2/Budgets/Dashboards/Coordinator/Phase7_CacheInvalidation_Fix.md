# Phase 7 — Cache Invalidation Fix (Preparation)

**Date:** 2026-03-06  
**Scope:** Safe refactor of cache invalidation before Phase 7 Dashboard Cache implementation  
**Status:** Complete

---

## 1. Problem Summary

Phase 7 Feasibility Audit identified three issues blocking safe Phase 7 implementation:

| Issue | Description |
|-------|-------------|
| **Cache key misalignment** | `invalidateDashboardCache()` cleared keys without FY (e.g. `coordinator_pending_approvals_data`) while widgets use FY-scoped keys (e.g. `coordinator_pending_approvals_data_{$fy}`) |
| **Dataset cache not cleared** | `DatasetCacheService::clearCoordinatorDataset($fy)` existed but was never called on approval/revert |
| **Dashboard cache invalidation** | No logic to clear Phase 7 dashboard cache keys |

---

## 2. Cache Key Corrections Applied

### 2.1 Before (incorrect keys)

```php
Cache::forget('coordinator_pending_approvals_data');
Cache::forget('coordinator_provincial_overview_data');
Cache::forget('coordinator_system_activity_feed_data_50');
```

### 2.2 After (correct FY-scoped keys)

For each FY in `FinancialYearHelper::listAvailableFY()`:

```php
Cache::forget("coordinator_pending_approvals_data_{$fy}");
Cache::forget("coordinator_provincial_overview_data_{$fy}");
Cache::forget("coordinator_system_activity_feed_data_{$fy}_50");
```

### 2.3 Legacy Keys Retained

Legacy keys (without FY) are still cleared for backward compatibility:

- `coordinator_pending_approvals_data`
- `coordinator_provincial_overview_data`
- `coordinator_system_activity_feed_data_50`
- `coordinator_system_performance_data`
- `coordinator_system_budget_overview_data`
- `coordinator_province_comparison_data`
- `coordinator_provincial_management_data`
- `coordinator_system_health_data`
- `coordinator_system_analytics_data_{$range}` (7, 30, 90, 180, 365)
- `coordinator_report_list_filters`
- `coordinator_project_list_filters`

---

## 3. Dataset Cache Invalidation Wiring

**Method:** `DatasetCacheService::clearCoordinatorDataset(string $fy)`

**Integration:** Called inside `invalidateDashboardCache()` for each available FY:

```php
foreach ($availableFY as $fy) {
    // ...
    DatasetCacheService::clearCoordinatorDataset($fy);
}
```

**No changes to approval/revert handlers** — they already call `invalidateDashboardCache()`, which now triggers dataset cache clearance. Handlers that call `invalidateDashboardCache()`:

- `refreshDashboard()` — manual refresh
- `revertToProvincial()` — project revert
- `approveProject()` — project approval
- `revertProject()` — project revert
- `approveReport()` — report approval
- `revertReport()` — report revert
- `bulkReportAction()` — bulk approve/revert reports

---

## 4. Dashboard Cache Invalidation Logic

### 4.1 New Helper

```php
private function clearCoordinatorDashboardCache(): void
{
    try {
        Cache::tags(['coordinator_dashboard'])->flush();
    } catch (\Throwable $e) {
        Log::debug('clearCoordinatorDashboardCache: tags not supported (e.g. file driver)', [
            'message' => $e->getMessage(),
        ]);
    }
}
```

### 4.2 Behaviour

- **Redis driver:** Uses `Cache::tags(['coordinator_dashboard'])->flush()` to clear all Phase 7 dashboard cache entries.
- **Phase 7 requirement:** When Phase 7 implements the dashboard cache, it must store entries with `Cache::tags(['coordinator_dashboard'])->put(...)` so they can be invalidated.
- **File/database driver:** Tags are not supported; the helper logs and does nothing. Cache will expire by TTL.

### 4.3 Invocation

Called once from `invalidateDashboardCache()` (not per-FY, since the tag flush clears all dashboard entries).

---

## 5. Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/CoordinatorController.php` | Updated `invalidateDashboardCache()`, added `clearCoordinatorDashboardCache()` |

### 5.1 Diff Summary

- **invalidateDashboardCache():**
  - Iterate `FinancialYearHelper::listAvailableFY()` for FY-scoped widget keys
  - Add `DatasetCacheService::clearCoordinatorDataset($fy)` per FY
  - Add `clearCoordinatorDashboardCache()` call
  - Keep legacy key clearing for compatibility
- **clearCoordinatorDashboardCache():** New helper for Phase 7 dashboard cache invalidation via tags

---

## 6. Safety Verification Results

| Check | Result |
|-------|--------|
| No functional behaviour changes | ✓ Only cache invalidation logic updated |
| No controller method signatures changed | ✓ |
| Dashboard loads correctly | ✓ Unchanged request/response flow |
| Cache invalidation executes after approvals/reverts | ✓ Same call sites; logic enhanced |
| Backward compatibility | ✓ Legacy keys still cleared |
| Dataset cache clears on approval/revert | ✓ Now wired via `invalidateDashboardCache()` |

---

## 7. Phase 7 Implementation Notes

When implementing Phase 7 Dashboard Cache:

1. **Cache driver:** Prefer Redis so `Cache::tags(['coordinator_dashboard'])` can be used for storage and invalidation.
2. **Storage pattern:** Use `Cache::tags(['coordinator_dashboard'])->put($key, $payload, $ttl)` for dashboard payloads.
3. **Cache key format:** `coordinator_dashboard_{$fy}_{$filterHash}_{$analyticsRange}` as documented in Phase7_DashboardCache_Feasibility_Audit.md.

---

## 8. References

- `Phase7_DashboardCache_Feasibility_Audit.md`
- `Coordinator_Dashboard_Implementation_Roadmap.md` (Phase 7)
- `app/Services/DatasetCacheService.php`
