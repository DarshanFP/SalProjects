# Phase 1.3 — MonthlyDevelopmentProject Store Snapshot Fix

**Date implemented:** 2026-06-13  
**Plan reference:** [`Reporting_System_Phase_Wise_Implementation_Plan.md`](../Reporting_System_Phase_Wise_Implementation_Plan.md) — Phase 1, Task 1.1 (alternate path)  
**Priority:** P0 — Same root cause as main monthly create  
**Status:** ✅ Implemented (pending staging verification)

---

## Problem

The alternate Development Projects monthly path (`MonthlyDevelopmentProjectController::store`) called:

```php
$report = DPReport::create($validatedData);
```

Issues:
1. **Same `society_id` NOT NULL failure** as `ReportController::createReport()` — no snapshot on INSERT.
2. `$validatedData` contained non-fillable / wrong keys (`reporting_period_from`, `reporting_period_month`, etc.) and **no `report_id`**, making the legacy store path unreliable.

Route: `monthly.developmentProject.store` → `developmentProject/reportform` flow.

---

## Solution

**File:** `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php`

Replaced `DPReport::create($validatedData)` with:

1. Load project via `Project::where('project_id', ...)->firstOrFail()`
2. Generate ID via `DPReport::generateNextReportId($project->project_id)`
3. Map validated + project fields to fillable attributes
4. Create via `DPReport::createWithProjectSnapshot($attributes, $project)`

Added `use Carbon\Carbon` for `report_month_year` construction.

---

## Attribute mapping

| DPReport field | Source |
|----------------|--------|
| `report_id` | `DPReport::generateNextReportId()` |
| `user_id` | Request / auth |
| `project_*` metadata | `$project` row |
| `report_month_year` | `reporting_period_year` + `reporting_period_month` |
| `amount_*` overview fields | Validated request |
| `society_id`, `society_name`, `province_id` | Project snapshot (model helper) |
| `status` | `DPReport::STATUS_DRAFT` |

---

## Files changed

| File | Change |
|------|--------|
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | `store()` report creation block |

**Depends on:** `DPReport::createWithProjectSnapshot()` from Phase 1.1

---

## Verification checklist

- [ ] Staging: Submit report via `developmentProject/reportform` for approved DP with `society_id`
- [ ] Confirm `DP_Reports` row created with snapshot fields
- [ ] Confirm objectives/photos/account details still save (downstream logic unchanged)

---

## Follow-up (not in Phase 1)

- Phase 7: Align this path with `BudgetCalculationService` (currently uses `max('phase')` budgets)
- Phase 7: Decide whether to deprecate this route in favor of unified `monthly.report.create`
