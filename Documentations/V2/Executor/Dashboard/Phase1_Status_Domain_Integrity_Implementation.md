# Phase 1 – Status Domain Integrity Implementation

## Objective

Replace hardcoded 6-status initialization with a canonical status set derived from the DPReport model. Previously, the Executor dashboard used only six statuses (draft, submitted, forwarded, approved_by_coordinator, reverted_by_provincial, reverted_by_coordinator), causing reports in other statuses (e.g., approved_by_general_as_coordinator, approved_by_general_as_provincial, reverted_to_executor) to be dropped from counts. This led to undercounts and mismatched totals across widgets.

## Root Cause

Hardcoded 6-status initialization in `getReportStatusSummary()` and `getReportChartData()`:
- Only statuses in the fixed array were counted.
- Reports in statuses outside that set were ignored.
- Total did not match `DPReport::whereIn(project_id, ownedIds)->count()`.

## Changes Made

### 1. DPReport Model

- Added `getDashboardStatusKeys()` returning all report statuses used in the system:
  - draft, submitted_to_provincial, forwarded_to_coordinator
  - approved_by_coordinator, approved_by_general_as_coordinator, approved_by_general_as_provincial
  - reverted_by_provincial, reverted_by_coordinator, reverted_by_general_as_provincial, reverted_by_general_as_coordinator
  - reverted_to_executor, reverted_to_applicant, reverted_to_provincial, reverted_to_coordinator
  - rejected_by_coordinator

### 2. ExecutorController

- **getReportStatusSummary()**: Initializes `$statuses` with `array_fill_keys(DPReport::getDashboardStatusKeys(), 0)`, merges DB groupBy results, and returns `monthly`, `total`, `approved_count`, `reverted_count`, `pending_count`.
- **getReportChartData()**: Initializes `$statusCounts` with `array_fill_keys(DPReport::getDashboardStatusKeys(), 0)`, merges grouped results, derives `total_reports` from `array_sum($statusCounts)`, and derives `approved_reports` from the sum over `DPReport::APPROVED_STATUSES`.

### 3. Blade Updates

- **report-status-summary.blade.php**: Approved uses `$reportStatusSummary['approved_count']` (sum of `DPReport::APPROVED_STATUSES`). Reverted uses `$reportStatusSummary['reverted_count']` (all statuses starting with `reverted_`).
- **report-overview.blade.php**: Approved uses `$reportStatusSummary['approved_count']`. Pending uses `$reportStatusSummary['pending_count']` (draft + submitted + forwarded + all reverted). Total uses `$reportStatusSummary['total']`.

## Status Set Now Used

Keys returned by `getDashboardStatusKeys()`:

- draft
- submitted_to_provincial
- forwarded_to_coordinator
- approved_by_coordinator
- approved_by_general_as_coordinator
- approved_by_general_as_provincial
- reverted_by_provincial
- reverted_by_coordinator
- reverted_by_general_as_provincial
- reverted_by_general_as_coordinator
- reverted_to_executor
- reverted_to_applicant
- reverted_to_provincial
- reverted_to_coordinator
- rejected_by_coordinator

## Validation Performed

- Report count matches summary: `reportStatusSummary['total']` equals `array_sum($statuses)` where `$statuses` includes all canonical keys plus any DB statuses merged in.
- Chart total matches summary: `reportChartData['total_reports']` is derived from `array_sum($statusCounts)`.
- No hardcoded status strings remain in the controller; all status keys come from `DPReport::getDashboardStatusKeys()` or `DPReport::APPROVED_STATUSES`.

## Risk Level

Low
