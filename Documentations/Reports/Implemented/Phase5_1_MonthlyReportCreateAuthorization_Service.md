# Phase 5.1 — Monthly Report Create Authorization Service

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Rule

Only **approved** projects that the user **owns or is in-charge of** (executor/applicant, same province) may create monthly reports.

## Service

`App\Services\Reports\MonthlyReportCreateAuthorization`

| Method | Purpose |
|--------|---------|
| `check($user, $project)` | Returns `allowed`, `reason`, `message` + structured logs |
| `authorize($user, $project)` | Boolean shorthand |
| `abortUnlessAllowed($user, $project)` | `abort(403)` with user-facing message |
| `reportExistsForPeriod($projectId, $month, $year)` | Duplicate period guard |

**Denial reasons (log grep):**
- `invalid_role`
- `project_not_approved`
- `not_owner_or_in_charge`
- `province_mismatch`

**Success log:** `Monthly report create authorized`

## Wired in

- `ReportController::create()` — `abortUnlessAllowed()` after project load
- `StoreMonthlyReportRequest::authorize()` — see Phase 5.2
- `MonthlyDevelopmentProjectController` — see Phase 5.3

## Files

- `app/Services/Reports/MonthlyReportCreateAuthorization.php`
