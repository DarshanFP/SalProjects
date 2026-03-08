# Blade Date Safety Audit — diffForHumans() Null-Safety

**Date:** 2026-03-06  
**Goal:** Prevent crashes from calling `diffForHumans()` on null dates  
**Scope:** All Blade templates using `->diffForHumans()`

---

## 1. Summary

| Metric | Count |
|--------|-------|
| Total `diffForHumans()` usages scanned | 20 |
| Unsafe patterns fixed | 14 |
| Already safe (guarded) | 6 |

---

## 2. Pattern Replaced

**Unsafe pattern:**
```blade
{{ $object->created_at->diffForHumans() }}
{{ $model->updated_at->diffForHumans() }}
```

**Safe pattern:**
```blade
{{ optional($object->created_at)->diffForHumans() ?? '—' }}
{{ optional($model->updated_at)->diffForHumans() ?? '—' }}
```

- `optional()` returns null for method calls when the wrapped value is null.
- `?? '—'` displays an em dash when the result is null.

---

## 3. Files Fixed

| File | Line(s) | Property | Change |
|------|---------|----------|--------|
| `resources/views/coordinator/index.blade.php` | 240 | `$project->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/notifications/index.blade.php` | 74 | `$notification->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/coordinator/widgets/system-activity-feed.blade.php` | 64 | `$activity->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/projects/partials/Show/status_history.blade.php` | 30 | `$history->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/reports/monthly/partials/view/activity_history.blade.php` | 33 | `$activity->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/executor/widgets/activity-feed.blade.php` | 104 | `$activity->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/executor/widgets/projects-requiring-attention.blade.php` | 56, 116 | `$project->updated_at` | Added `optional()` and `?? '—'` |
| `resources/views/executor/widgets/reports-requiring-attention.blade.php` | 69, 143 | `$report->updated_at` | Added `optional()` and `?? '—'` |
| `resources/views/provincial/widgets/team-activity-feed.blade.php` | 99 | `$activity->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/general/widgets/activity-feed.blade.php` | 52 | `$activity->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/components/notification-dropdown.blade.php` | 76 | `$notification->created_at` | Added `optional()` and `?? '—'` |
| `resources/views/activity-history/partials/activity-table.blade.php` | 23 | `$activity->created_at` | Added `optional()` and `?? '—'` |

---

## 4. Files Already Safe (No Change)

These usages were already guarded and left unchanged:

| File | Line(s) | Guard |
|------|---------|-------|
| `resources/views/provincial/widgets/team-overview.blade.php` | 79 | `$member->updated_at ? $member->updated_at->diffForHumans() : 'Never'` |
| `resources/views/general/widgets/coordinator-overview.blade.php` | 152-154 | `@if($coordinator->last_activity)` + `Carbon::parse()` |
| `resources/views/coordinator/widgets/provincial-overview.blade.php` | 126-128 | `@if($provincial->last_activity)` + `Carbon::parse()` |
| `resources/views/coordinator/widgets/provincial-management.blade.php` | 167-168 | `@if($provincial['last_activity'])` + `Carbon::parse()` |
| `resources/views/general/widgets/direct-team-overview.blade.php` | 167-168 | `@if($member->last_activity)` + `Carbon::parse()` |
| `resources/views/executor/index.blade.php` | 499, 631 | `@if($metadata && $metadata['last_report_date'])` |

---

## 5. Root Cause of Coordinator Dashboard Crash

The coordinator dashboard crash (`Call to a member function diffForHumans() on null`) at `coordinator/index.blade.php:240` was caused by:

1. `DatasetCacheService::getCoordinatorDataset()` using a lightweight `$select` that omits `created_at`.
2. The view calling `$project->created_at->diffForHumans()` when `created_at` was null.

**Resolution:** The Blade view was hardened with `optional($project->created_at)->diffForHumans() ?? '—'`. For full correctness, consider adding `created_at` to the coordinator dataset `$select` array so sorting and display use the actual timestamp.

---

## 6. References

- Laravel `optional()` helper: https://laravel.com/docs/helpers#method-optional
- Coordinator Phase 6 shared dataset: `Phase6_SharedDataset_Remediation.md`
