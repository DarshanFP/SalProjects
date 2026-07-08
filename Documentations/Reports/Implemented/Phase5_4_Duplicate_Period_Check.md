# Phase 5.4 — Duplicate Reporting Period Check

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Rule

One report per `project_id` per calendar month (`report_month_year`).

## Implementation

- `MonthlyReportCreateAuthorization::reportExistsForPeriod()` — shared helper
- `StoreMonthlyReportRequest::withValidator()` — validation error on `report_month`
- `MonthlyDevelopmentProjectController::store()` — returns validation error on `reporting_period_month`

Query:

```php
DPReport::where('project_id', $projectId)
    ->whereYear('report_month_year', $year)
    ->whereMonth('report_month_year', $month)
    ->exists();
```

Applies regardless of report status (draft, submitted, approved).

## User message

> A report already exists for this project and reporting period.
