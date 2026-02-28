# Phase 3 – Scope Consistency Harmonization Implementation

## Objective

Eliminate merged-scope drift in dashboard metrics. Ensure all dashboard statistical widgets use owned-only scope. Authorization remains merged for access control; dashboard statistics use owned-only.

## Root Cause

Inline merged-scope queries inside dashboard widgets:
- Report Overview "Recent Reports" table used `Project::where(user_id)->orWhere(in_charge)` in blade
- Project types filter used `getProjectsForUserQuery` (merged scope)

## Controller Changes

- **executorDashboard()**: Added `$recentReports` fetched via `ProjectQueryService::getOwnedProjectIds($user)` and `DPReport::whereIn('project_id', $ownedProjectIds)->...->limit(5)->get()`. Passed to view.
- **executorDashboard()**: Changed `projectTypes` from `getProjectsForUserQuery` to `getOwnedProjectsQuery` for owned-only filter options.
- **executorDashboard()**: Added comment: "Activity feed: intentionally uses combined scope for visibility (owner + in-charge)."

## Widget Changes

- **report-overview.blade.php**: Removed inline `@php` block that queried `Project::where(user_id|in_charge)` and `DPReport::whereIn`. Replaced with controller-passed `$recentReports`.
- **report-overview.blade.php**: Status badge uses `$report->isApproved()` for approved display (covers all APPROVED_STATUSES).
- **report-overview.blade.php**: Summary cards already use `$reportStatusSummary` (Total, Pending, Approved) — unchanged, reconciles with Report Status Summary widget.

## Scope Rule After Phase 3

| Layer | Scope | Notes |
|-------|-------|-------|
| Dashboard statistics | Owned-only | All KPIs, Report Overview, project types filter |
| Authorization (report list, etc.) | Merged | Access to owned + in-charge projects |
| Activity feed | Merged | Intentionally combined for visibility; documented |

## Validation Performed

- No merged scope queries remain in dashboard controller (projectTypes and recentReports use owned scope).
- Report Overview Total = reportStatusSummary['total'].
- Report Overview Approved = reportStatusSummary['approved_count'] (sum of APPROVED_STATUSES).
- Report Overview Pending = reportStatusSummary['pending_count'].
- Activity feed documented: uses combined scope via ActivityHistoryService::getForExecutor.

## Risk Level

Low–Medium. Behavioral change for users with in-charge-only projects: Report Overview will show 0 recent reports (previously showed in-charge project reports). Project types filter shows owned-only types. Activity feed unchanged (still merged).
