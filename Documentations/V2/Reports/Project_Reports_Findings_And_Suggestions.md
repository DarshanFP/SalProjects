# Project Reports — Findings and Suggestions

**Document Version:** 1.0  
**Date:** 2026-03-14  
**Scope:** Production log analysis, codebase scan, model/schema audit for report-related issues  
**Log File Analyzed:** `Prod-issue in report laravel-5.log`  
**Source:** Coordinator dashboard, General reports, Provincial/Executor report flows, DPReport/DPAccountDetail models, migrations

---

## 1. Executive Summary

This document captures all findings from:

- Production errors in the log related to projects and reports
- Codebase analysis of report controllers, models, and migrations
- Model/schema naming discrepancies and structural inconsistencies

**Critical issues** block CCI project editing and affect report flows. **Financial invariant warnings** indicate data integrity risks for approved projects shown in dashboards and reports. **Naming and schema discrepancies** may cause confusion and future bugs.

---

## 2. Log Analysis — Errors Related to Reports / Projects

### 2.1 CCI Statistics — `No query results for model [ProjectCCIStatistics]` (CRITICAL)

| Item | Value |
|------|-------|
| **Severity** | Critical (500 error, blocks CCI project edit) |
| **Occurrences** | 100+ in log |
| **Affected Projects** | CCI-0001, CCI-0002 |
| **Location** | `StatisticsController.php(60)` → `ProjectController@edit` |

**Error:**
```
Error editing CCI Statistics {"error":"No query results for model [App\\Models\\OldProjects\\CCI\\ProjectCCIStatistics]."}
ProjectController@edit - Error retrieving project data {"project_id":"CCI-0001",...}
```

**Root cause:** Production `StatisticsController::edit()` uses `firstOrFail()`. When no `ProjectCCIStatistics` record exists for a CCI project (e.g. migrated/legacy data), the query throws.

**Current codebase:** Local `app/Http/Controllers/Projects/CCI/StatisticsController.php` already has a fix: it uses `first()` and returns a new empty model when null (lines 59–66). Production likely has older code.

**Suggestion:**

1. Deploy the updated `StatisticsController::edit()` to production.
2. Optionally run a data fix: create missing `ProjectCCIStatistics` rows for CCI projects that have none (e.g. CCI-0001, CCI-0002).

---

### 2.2 Financial Invariant Violations (WARNING — Data Integrity)

| Item | Value |
|------|-------|
| **Severity** | High (data integrity) |
| **Occurrences** | Many per page load (executor dashboard) |
| **Invariants** | `amount_sanctioned > 0` for approved; `opening_balance == overall_project_budget` |

**Examples from log:**

```
Financial invariant violation: approved project must have amount_sanctioned > 0 
  {"project_id":"IOGEP-0006","amount_sanctioned":0.0}
Financial invariant violation: approved project must have opening_balance == overall_project_budget 
  {"project_id":"IAH-0002","opening_balance":0.0,"overall_project_budget":100000.0}
```

**Context:** These invariants are checked when resolving fund fields for projects (e.g. executor dashboard). Approved projects should have sanctioned amounts and correct opening balance; violations indicate legacy or inconsistent data.

**Impact:** Report creation/update and budget summaries may use incorrect financial data.

**Suggestion:**

1. Run a data audit for approved projects with `amount_sanctioned = 0` or `opening_balance != overall_project_budget`.
2. Define a repair strategy (e.g. backfill, correction script, or migration) and run it in a controlled way.
3. Consider `Phase2_Dry_Run_Repair_Simulation` (or similar) to validate repairs before applying to production.

---

### 2.3 IIES Family Working Members — No Data (INFO)

| Item | Value |
|------|-------|
| **Severity** | Low (cosmetic/informational) |
| **Occurrences** | When viewing IIES projects |
| **Log** | `No IIES Family Working Members found {"project_id":"IIES-0023"}` |

**Suggestion:** Treat as expected for projects without family members. No code change required unless business rules demand records for all IIES projects.

---

## 3. Codebase Scan — Report-Related Issues

### 3.1 Report Model / Table Naming Inconsistencies

| Area | Finding | Impact |
|------|---------|--------|
| **Tables** | `DP_Reports`, `DP_AccountDetails`, `DP_Photos`, `DP_Objectives`, `DP_Outlooks`, `DP_Activities` | PascalCase with underscore; differs from Laravel snake_case convention |
| **Primary key** | `report_id` (string) vs `id` (auto-increment) | `DPReport` uses `report_id` as primary key; `id` exists but is not primary; some code uses `id` for notifications |
| **Report comments** | `R_comment_id` in `report_comments` | CamelCase-style column naming vs typical snake_case |
| **Report tables** | `report_comments` vs `DP_Reports` | Mixed conventions (snake_case vs PascalCase) |

**Suggestion:** Keep current naming for compatibility. For new tables, prefer Laravel conventions. Document the existing naming scheme for maintainability.

---

### 3.2 Schema / Model Discrepancies

| Item | Migration | Model / Usage | Discrepancy |
|------|-----------|---------------|-------------|
| `commencement_month_year` | `date` | DPReport uses as string in PHPDoc | PHP often receives Carbon/date; no critical mismatch |
| `report_month_year` | `date` | Same as above | Same as above |
| `society_id`, `province_id` | Added in `add_society_snapshot_to_dp_reports_table` | Model has these; not in fillable (by design) | None |
| `DP_Reports` primary key | `id` (auto) + `report_id` (unique) | Model: `report_id` as primary key | Intentional; FK references `report_id` |

---

### 3.3 Report Controller `firstOrFail()` Usage

Several report controllers use `firstOrFail()`, which will throw if records are missing:

| Controller | Method / Context | Risk |
|------------|------------------|------|
| `ExportReportController` | `downloadPdf`, `downloadDoc` | High (user-facing error) |
| `ReportController` | `show`, `edit`, `review`, `forward`, `approve`, `removePhoto` | High |
| `ReportAttachmentController` | `downloadAttachment`, `remove`, etc. | High |
| `AggregatedQuarterlyReportController` | Project lookup | Medium |
| `AggregatedHalfYearlyReportController` | Project lookup | Medium |
| `AggregatedAnnualReportController` | Project lookup | Medium |

**Suggestion:** For user-facing flows, prefer `first()` with explicit error handling (e.g. 404) instead of relying on `firstOrFail()` to avoid generic 500 responses. Document which flows intentionally require existence and which can gracefully handle missing records.

---

### 3.4 Project–Report Relationship

- `Project::reports()` → `hasMany(DPReport::class, 'project_id', 'project_id')`
- `DPReport` → `belongsTo(Project::class)`
- Access patterns: `$project->reports` used in CoordinatorController, ProvincialController, ExecutorController, GeneralController, BudgetValidationService

**Observation:** Some code checks `$project->reports && $project->reports->count() > 0`; other uses assume the relation is always loaded. Eager-load `reports` where needed to avoid N+1.

---

## 4. Schema Naming Audit — Amen / Consistency

### 4.1 Table Naming Patterns

| Pattern | Examples | Notes |
|---------|----------|-------|
| PascalCase + underscore | `DP_Reports`, `DP_AccountDetails`, `DP_Photos` | Legacy reporting tables |
| Snake_case | `report_comments`, `report_attachments`, `quarterly_reports` | Laravel-style |
| Mixed | `project_LDP_need_analysis`, `project_RST_DP_beneficiaries_area` | Project-type-specific tables |

### 4.2 Column Naming

| Pattern | Examples | Notes |
|---------|----------|-------|
| Snake_case | `report_id`, `project_id`, `report_month_year` | Majority |
| CamelCase-style | `R_comment_id` | Exception in report_comments |
| Mixed | `LDP_need_analysis_id` | Project-type-specific IDs |

### 4.3 Suggestions

1. For new columns/tables, use snake_case consistently.
2. Add a short “naming conventions” section to project docs.
3. Do not rename existing columns without a migration plan and full impact analysis.

---

## 5. Summary of Suggestions

| # | Area | Suggestion | Priority |
|---|------|------------|----------|
| 1 | CCI Statistics | Deploy fixed `StatisticsController::edit()`; optionally backfill missing records | Critical |
| 2 | Financial invariants | Audit and repair approved projects with bad `amount_sanctioned` / `opening_balance` | High |
| 3 | Report controllers | Replace `firstOrFail()` with `first()` + explicit 404 handling where appropriate | Medium |
| 4 | Naming | Document conventions; avoid new non-standard naming | Low |
| 5 | Performance | Eager-load `reports` where project + reports are used together | Low |
| 6 | IIES | No action; “no family members” is expected for some projects | N/A |

---

## 6. Files Referenced

- `app/Http/Controllers/Projects/CCI/StatisticsController.php`
- `app/Http/Controllers/Projects/ProjectController.php` (edit flow)
- `app/Models/Reports/Monthly/DPReport.php`
- `app/Models/OldProjects/Project.php` (reports relationship)
- `app/Models/ReportComment.php`
- `database/migrations/2024_07_21_092111_create_dp_reports_table.php`
- `database/migrations/2024_08_18_130656_create_report_comments_table.php`
- `app/Http/Controllers/Reports/Monthly/ReportController.php`
- `app/Http/Controllers/Reports/Monthly/ExportReportController.php`
- `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php`
