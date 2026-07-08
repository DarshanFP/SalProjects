# Phase 6.3 — Photos Section & Dead Code Cleanup

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Photos on create

`ReportAll.blade.php` now uses the activity-linked photos partial (same as edit flow):

```blade
@include('reports.monthly.partials.create.photos')
```

Features (from partial):
- Up to 3 photos per group
- Activity selector linked to objectives
- 2 MB per-file limit
- `ReportController::store()` already handles `photos[group][]` and `photo_activity_id`

Removed ~200 lines of commented legacy photo markup and duplicate JS (`addPhoto`, `validateFileInput` stubs) from `ReportAll.blade.php`.

## Files

- `resources/views/reports/monthly/ReportAll.blade.php`
- `resources/views/reports/monthly/partials/create/photos.blade.php` (unchanged — already correct)

## Manual test

1. Open create form for any project with objectives
2. Confirm Photos section visible with "Add More Photos"
3. Save draft with photo → verify `DP_Photos` row created
