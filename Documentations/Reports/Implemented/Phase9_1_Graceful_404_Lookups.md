# Phase 9.1 — Graceful 404 Lookups

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

Report controllers used `firstOrFail()` for reports, projects, and photos. Invalid IDs raised `ModelNotFoundException`, which surfaced as **500** instead of a proper **404** page.

## Solution

Added `App\Support\Reports\ReportResourceLookup` with:

| Method | Use case |
|--------|----------|
| `findProject($projectId, $with = [])` | Direct project lookup |
| `findReport($reportId, $with = [])` | Direct report lookup |
| `firstReportOrAbort($query, $reportId, $logContext = [])` | After role-scoped query |
| `firstPhotoOrAbort($query, $photoId, $logContext = [])` | After role-scoped photo query |

Each missing record logs a warning and calls `abort(404, '… not found.')`.

## Files updated

| File | Replacements |
|------|--------------|
| `ReportController.php` | 12 (create, show, edit, update, review, revert, forward, approve, removePhoto) |
| `ExportReportController.php` | 2 (downloadPdf, downloadDoc) |
| `ReportAttachmentController.php` | 5 (update, testFileStructure, testCreateAttachment) |
| `MonthlyDevelopmentProjectController.php` | 2 (redirect create, legacy store) |
| `AggregatedQuarterlyReportController.php` | 2 |
| `AggregatedHalfYearlyReportController.php` | 2 |
| `AggregatedAnnualReportController.php` | 2 |

**New:** `app/Support/Reports/ReportResourceLookup.php`

## Manual test

1. Visit `reports/monthly/edit/INVALID-ID` while authenticated → **404**, not 500.
2. Check `storage/logs/laravel.log` for `Report not found` warning with `report_id`.
