# Phase 1.1 — Society Snapshot Create Fix

**Date implemented:** 2026-06-13  
**Plan reference:** [`Reporting_System_Phase_Wise_Implementation_Plan.md`](../Reporting_System_Phase_Wise_Implementation_Plan.md) — Phase 1, Task 1.1  
**Priority:** P0 — Production blocker  
**Status:** ✅ Implemented (pending staging/production deploy verification)

---

## Problem

Monthly report creation failed in production with:

```
SQLSTATE[HY000]: General error: 1364 Field 'society_id' doesn't have a default value
```

**Root cause:** `ReportController::createReport()` called `DPReport::create()` without `society_id`, then attempted to set snapshot fields on a **second** `save()`. Migration `2026_02_18_145049_enforce_report_society_snapshot_not_null_and_fk.php` enforces `society_id NOT NULL`, so the first INSERT failed before snapshot assignment.

**Production impact:** 8 failures (Mar 6–13, 2026) for projects `DP-0009`, `DP-0006`.

---

## Solution

### 1. New model helpers on `DPReport`

**File:** `app/Models/Reports/Monthly/DPReport.php`

| Method | Purpose |
|--------|---------|
| `generateNextReportId(string $projectId): string` | Centralized report ID sequencing (e.g. `DP-0006-01`) |
| `createWithProjectSnapshot(array $attributes, Project $project): self` | Single INSERT with fillable attributes + non-fillable snapshot fields |

`society_id`, `society_name`, and `province_id` remain **outside** `$fillable` (Wave 6D immutability). They are assigned on the model instance before the first `save()`.

**Validation:** Throws `RuntimeException` if `$project->society_id` is empty.

### 2. Updated `ReportController::createReport()`

**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`

- Loads project first; throws if project missing.
- Uses `DPReport::createWithProjectSnapshot()` instead of two-step create/save.
- Logs `society_id` on successful create.
- Uses `DPReport::STATUS_DRAFT` constant instead of string `'draft'`.

### 3. Refactored `generateReportId()`

Delegates to `DPReport::generateNextReportId()` to avoid duplicated logic.

---

## Files changed

| File | Change |
|------|--------|
| `app/Models/Reports/Monthly/DPReport.php` | Added `generateNextReportId`, `createWithProjectSnapshot` |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | Fixed `createReport`, simplified `generateReportId` |

---

## Verification checklist

- [ ] Staging: Save draft monthly report for an approved DP project with valid `society_id`
- [ ] Confirm `DP_Reports` row has non-null `society_id`, `society_name`, `province_id`
- [ ] Confirm no `1364 society_id` errors in application log
- [ ] Regression: edit/show existing reports unchanged
- [ ] Production deploy + 24h log monitoring

---

## Rollback

Revert `DPReport` helper methods and restore previous `createReport()` two-step pattern **only if** migration is rolled back to nullable `society_id` (not recommended).

---

## Related

- [`Phase1_3_MonthlyDevelopmentProject_Store_Snapshot.md`](./Phase1_3_MonthlyDevelopmentProject_Store_Snapshot.md) — alternate DP create path
- Wave 6A society snapshot design in `DPReport::booted()` immutability guard
