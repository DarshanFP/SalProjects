# Phase 9.2 — Debug Routes Gated Behind APP_DEBUG

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

Test/diagnostic routes were registered in production:

- `/test-expenses/{project_id}` → `ReportController::testFetchLatestTotalExpenses`
- `reports/monthly/test-structure/{report_id}` → `ReportAttachmentController::testFileStructure`
- `reports/monthly/test-create-attachment/{report_id}` → `ReportAttachmentController::testCreateAttachment`

## Solution

Wrapped all three in `if (config('app.debug')) { … }` in `routes/web.php`.

When `APP_DEBUG=false` (production), routes are not registered.

## Manual test

1. Set `APP_DEBUG=false`, run `php artisan route:list --path=test` → no test routes.
2. Set `APP_DEBUG=true` → test routes appear for local debugging.
