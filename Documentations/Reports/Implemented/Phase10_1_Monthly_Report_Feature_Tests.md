# Phase 10.1 — Monthly Report Feature Tests

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## File

`tests/Feature/MonthlyReportTest.php`

## Tests

| Test | Covers |
|------|--------|
| `test_executor_can_create_draft_report_for_approved_project` | Phase 1 + 5 — draft store, `society_id` snapshot |
| `test_create_fails_without_society_id_on_project` | Phase 1 — `createWithProjectSnapshot()` guard |
| `test_create_rejected_for_unapproved_project` | Phase 5 — `MonthlyReportCreateAuthorization` |
| `test_executor_can_edit_reverted_to_executor_report` | Phase 2 — editable status |
| `test_executor_cannot_edit_submitted_report` | Phase 2 — 403 on non-editable status |
| `test_unauthenticated_cannot_access_quarterly_routes` | Phase 1 — quarterly auth middleware |

## Fixtures

- `tests/Concerns/CreatesMonthlyReportTestData.php` — executor + approved project + `createTestReport()` via snapshot helper
- Uses `DatabaseTransactions` (rolls back after each test)
- **Note:** `Project` auto-generates `project_id` on create; tests must use `$project->project_id`, not a preset ID

## Run

```bash
php artisan test tests/Feature/MonthlyReportTest.php
```
