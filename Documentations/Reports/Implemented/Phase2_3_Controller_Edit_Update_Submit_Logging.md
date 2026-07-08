# Phase 2.3 — Controller Edit/Update/Submit Guards & Logging

**Date implemented:** 2026-06-13  
**Plan reference:** Phase 2 — defense in depth + test diagnostics  
**Status:** ✅ Implemented

---

## Problem

Even after FormRequest alignment, `ReportController::edit()` did not block non-editable statuses for executors (form could load but update might fail inconsistently). Submit used `firstOrFail()` without pre-check logging. Update error logs lacked `report_id` and user context.

---

## Solution

### `edit($report_id)`

For `executor` / `applicant`:
- After loading report, calls `$report->isEditable()`
- If false → `Log::warning('Report edit page denied: status not editable', ...)` + HTTP 403
- If true → `Log::info('Report edit page authorized for executor/applicant', ...)`

Provincial/coordinator paths unchanged (they may review; not executor edit flow).

### `update($report_id)`

After finding report (post role filter):
- Logs `report_status`, `is_editable`
- Second guard for executor/applicant if `!isEditable()` → WARNING + 403
- Enhanced catch blocks with `report_id`, `user_id`, validation errors

### `submit($report_id)`

- Logs role denial, not-found, status pre-check
- Uses `first()` instead of `firstOrFail()` with explicit 404 log
- Pre-check `isEditable()` before calling `ReportStatusService`
- Logs success with `previous_status` → `new_status`
- Enhanced error log with `report_status`

### `ReportStatusService::submitToProvincial()`

- Uses `DPReport::EXECUTOR_EDITABLE_STATUSES`
- WARNING log on status denial with full context
- INFO log when status check passes

---

## Log messages reference

| Message | When |
|---------|------|
| `Report edit page denied: status not editable` | GET edit blocked |
| `Report edit page authorized for executor/applicant` | GET edit allowed |
| `Report found for update` | POST update started |
| `Report update blocked in controller: status not editable` | POST update second guard |
| `Monthly report update validation failed` | ValidationException |
| `Failed to update report` | General exception |
| `Report submit denied: *` | Submit auth/status failures |
| `Report submit to provincial denied: status not allowed` | Service layer |
| `Report submitted to provincial successfully` | Submit OK |

---

## Files changed

| File | Change |
|------|--------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | `edit`, `update`, `submit` guards + logging |
| `app/Services/ReportStatusService.php` | Submit status logging |

---

## Verification checklist

- [ ] Reverted report: edit page loads, update saves, submit works
- [ ] Submitted report: edit returns 403 with log
- [ ] Logs contain `report_id`, `report_status`, `allowed_statuses` on denials
