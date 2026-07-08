# Phase 8.1 — Create Form Basic Info Display

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

`ReportAll.blade.php` showed executor profile fields instead of project snapshot sources:

| Field | Was | Stored on save |
|-------|-----|----------------|
| Place | `$user->center` | Form `place` → `DP_Reports.place` |
| Society | `$user->society_name` | `createWithProjectSnapshot()` from **project** |

Users saw society/place that did not match the persisted report row.

## Solution

Added `DPReport::basicInfoForCreateForm(Project $project, ?User $user)`:

```php
'place' => $project->place ?? $user->center
'society_name' => $project->society_name ?? $project->society->name ?? $user->society_name
```

**Wired in:**
- `ReportController::create()` — passes `$reportBasicInfo`, eager-loads `society`
- `ReportAll.blade.php` — readonly inputs use `$reportBasicInfo`
- `ReportCommonForm.blade.php` — inline helper call (legacy/alternate form)

**Defensive:** `createReport()` falls back to `$project->place` when form `place` is empty.

## Files

- `app/Models/Reports/Monthly/DPReport.php`
- `app/Http/Controllers/Reports/Monthly/ReportController.php`
- `resources/views/reports/monthly/ReportAll.blade.php`
- `resources/views/reports/monthly/ReportCommonForm.blade.php`

## Manual test

1. Open create form for a project where `project.place` ≠ executor `center`
2. Confirm Place shows **project.place**
3. Save draft → `DP_Reports.place` and `society_name` match form / project snapshot
