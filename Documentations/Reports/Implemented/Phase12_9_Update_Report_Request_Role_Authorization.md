# Phase 12.9 Implementation: Update Report Request Role Authorization (M2)

**Date:** 2026-06-27  
**Goal:** Fix medium discrepancy M2 where `UpdateMonthlyReportRequest::authorize()` hard-gated on `executor` and `applicant` roles only, causing all update POST requests submitted by `provincial` and `coordinator` roles to be blocked with an HTTP 403 error before reaching `ReportController@update`.

---

## Root Cause Analysis

In `ReportController@update`, conditional branching explicitly handles updates by `provincial` users (for reports submitted by child executors) and `coordinator` users.

However, `UpdateMonthlyReportRequest::authorize()` enforced a strict check:
`if (!in_array($user->role, ['executor', 'applicant'], true)) { return false; }`

This made the provincial and coordinator update handling code in `ReportController` unreachable dead code whenever request validation occurred through `UpdateMonthlyReportRequest`.

---

## Changes Made

### [`app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php)
- Updated `authorize()` to eager-load the `user` relationship on `DPReport`.
- Added explicit permission checks for `coordinator` and `provincial` roles matching controller expectations:
  - **Coordinators:** Authorized for all reports.
  - **Provincials:** Authorized if the report creator's `parent_id` matches the provincial user ID (`$report->user->parent_id === $user->id`).
  - **Executors / Applicants:** Retained status editability check (`isEditable()`) and owner / `in_charge` authorization.

---

## Verification

1. **Provincial Update Test:** Authenticated as a provincial user updating a report belonging to a managed executor. Verified that request validation passes and updates process cleanly.
2. **Coordinator Update Test:** Authenticated as a coordinator updating a report. Verified that authorization succeeds.
