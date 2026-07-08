# Phase 5.2 — StoreMonthlyReportRequest Authorization

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Before

`authorize()` only checked role (`executor` / `applicant`) — any project_id could be posted (IDOR).

## After

1. Load project by `project_id` from request body
2. Delegate to `MonthlyReportCreateAuthorization::check()`
3. `failedAuthorization()` logs `auth_failure_reason` (matches Phase 2 update pattern)

## Duplicate period (§ 5.4)

`withValidator()` rejects when a `DP_Reports` row already exists for the same `project_id` + `report_month_year` month/year — applies to **draft and full submit** when month/year are present.

Future-date validation still skipped for draft saves only.

## Log grep

```
Monthly report store denied
Monthly report store authorization failed
auth_failure_reason
```

## Files

- `app/Http/Requests/Reports/Monthly/StoreMonthlyReportRequest.php`
