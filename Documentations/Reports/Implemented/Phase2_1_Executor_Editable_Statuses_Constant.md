# Phase 2.1 — Centralized Executor Editable Statuses

**Date implemented:** 2026-06-13  
**Plan reference:** [`Reporting_System_Phase_Wise_Implementation_Plan.md`](../Reporting_System_Phase_Wise_Implementation_Plan.md) — Phase 2  
**Status:** ✅ Implemented

---

## Problem

Editable report statuses were defined in **four separate places** with inconsistent lists:

| Location | Status count |
|----------|--------------|
| `UpdateMonthlyReportRequest` | 3 (draft + 2 reverts) |
| `DPReport::isEditable()` | 9 |
| `ReportStatusService::submitToProvincial()` | 9 (duplicated array) |
| `ProvincialController` budget loop | 9 (duplicated array) |

Executors could **submit** after a granular revert (e.g. `reverted_to_executor`) but **could not update** the report via `monthly.report.update`.

---

## Solution

Added single source of truth on `DPReport`:

```php
public const EXECUTOR_EDITABLE_STATUSES = [ /* 9 statuses */ ];

public static function executorEditableStatuses(): array;

public function isEditable(): bool; // uses constant with strict in_array
```

**Aligned consumers:**
- `UpdateMonthlyReportRequest` → `$report->isEditable()`
- `ReportStatusService::submitToProvincial()` → `DPReport::EXECUTOR_EDITABLE_STATUSES`
- `ProvincialController` expense aggregation → same constant

---

## Editable statuses (9)

| Constant | Label |
|----------|-------|
| `draft` | Draft |
| `reverted_by_provincial` | Returned by Provincial |
| `reverted_by_coordinator` | Returned by Coordinator |
| `reverted_by_general_as_provincial` | General as Provincial revert |
| `reverted_by_general_as_coordinator` | General as Coordinator revert |
| `reverted_to_executor` | Granular revert to executor |
| `reverted_to_applicant` | Granular revert to applicant |
| `reverted_to_provincial` | Granular revert to provincial |
| `reverted_to_coordinator` | Granular revert to coordinator |

---

## Files changed

| File | Change |
|------|--------|
| `app/Models/Reports/Monthly/DPReport.php` | `EXECUTOR_EDITABLE_STATUSES`, `executorEditableStatuses()`, refactored `isEditable()` |
| `app/Services/ReportStatusService.php` | Uses constant for submit allowed list |
| `app/Http/Controllers/ProvincialController.php` | Uses constant in budget expense loop |

---

## Verification

- [ ] Revert report to `reverted_to_executor` → executor can open edit page and save
- [ ] Report in `submitted_to_provincial` → update returns 403 with log reason `status_not_editable`
- [ ] Submit and update allowed statuses match (grep `EXECUTOR_EDITABLE_STATUSES`)
