# Phase 8.2 — Approved Projects FY Filter UX

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

`ProjectController::approvedProjects()` defaulted to `FinancialYearHelper::currentFY()`. Projects with **null** `commencement_month_year` were excluded by `scopeInFinancialYear()` and never appeared on first visit — blocking "Write Report" for valid approved projects.

## Solution

1. **Default FY to empty (all years):**
   ```php
   $fy = $request->input('fy', '');
   ```
   When `$fy` is empty, no FY scope is applied — all approved projects show.

2. **UI hint** on `approved.blade.php` when a FY is selected:
   > Projects with no commencement date are hidden when a financial year is selected.

The dropdown already had "All Financial Years" (`value=""`); it is now the default selection on first load.

## Files

- `app/Http/Controllers/Projects/ProjectController.php` — `approvedProjects()`
- `resources/views/projects/Oldprojects/approved.blade.php`

## Manual test

1. Visit `/projects/approved` (or executor approved projects route) without `?fy=` — all approved projects visible
2. Select a specific FY — projects without commencement hidden; hint text appears
