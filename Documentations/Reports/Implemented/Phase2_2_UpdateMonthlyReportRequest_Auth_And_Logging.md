# Phase 2.2 — Update Authorization & Diagnostic Logging

**Date implemented:** 2026-06-13  
**Plan reference:** Phase 2 — Workflow status alignment  
**Status:** ✅ Implemented

---

## Problem

`UpdateMonthlyReportRequest::authorize()` used a hardcoded 3-status list and returned `false` without structured logging — making production debugging of "cannot fix report after revert" complaints difficult.

---

## Solution

### Authorization (`UpdateMonthlyReportRequest`)

- Uses `$report->isEditable()` (9 statuses via `DPReport::EXECUTOR_EDITABLE_STATUSES`)
- Eager-loads `project` for in-charge check
- Strict role check: `executor` | `applicant` only
- Ownership: report `user_id` OR project `in_charge`

### Structured log reasons

| `authFailureReason` | Log level | Meaning |
|---------------------|-----------|---------|
| `unauthenticated` | WARNING | No logged-in user |
| `report_not_found` | WARNING | Invalid `report_id` |
| `invalid_role` | WARNING | Not executor/applicant |
| `status_not_editable` | WARNING | Status outside editable set |
| `not_owner_or_in_charge` | WARNING | IDOR / wrong user |

**Success path:** `Log::info('Monthly report update authorized', ...)` with `authorized_via` = `owner` | `in_charge`.

**Failed FormRequest:** `failedAuthorization()` logs reason before 403.

### Validation logging

- Future month validation failure → WARNING with report_id and period
- Invalid date combination → WARNING with exception message

---

## Files changed

| File | Change |
|------|--------|
| `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php` | Full rewrite of `authorize()`, added `failedAuthorization()`, validation logs |

---

## How to grep logs during testing

```bash
# All update auth decisions
grep "Monthly report update" storage/logs/laravel.log

# Denials only
grep "Monthly report update denied" storage/logs/laravel.log

# Successful updates
grep "Monthly report update authorized" storage/logs/laravel.log
```

---

## Verification checklist

- [ ] Update draft report → INFO authorized
- [ ] Update as wrong user → WARNING `not_owner_or_in_charge`
- [ ] Update submitted report → WARNING `status_not_editable`
- [ ] Update after `reverted_to_executor` → INFO authorized
