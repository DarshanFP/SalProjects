# Reports Relational Readiness Audit

**Audit Date:** 2026-02-16  
**Purpose:** Pre Phase 5B5A planning — assess report tables for relational purity (Option B)  
**Scope:** Planning + analysis only. No schema change. No migration. No backfill.

---

## Environment Confirmation

| Item | Value |
|------|-------|
| **Database Name** | `projectsReports` |
| **Environment** | `local` (APP_ENV=local) |
| **DB Connection** | MySQL |
| **Production Check** | **NOT connected to production** ✓ |

**.env excerpt:**
- APP_ENV=local
- DB_DATABASE=projectsReports
- DB_HOST=127.0.0.1

**Conclusion:** Safe to run read-only audit. No production risk.

---

## Report Tables Identified

### Primary report tables (store `society_name`)

| Table | Columns (key) | society_name | project_id | user_id | Row count |
|-------|---------------|--------------|------------|---------|-----------|
| **DP_Reports** | id, report_id, project_id, user_id, project_title, project_type, place, **society_name**, commencement_month_year, in_charge, total_beneficiaries, report_month_year, goal, account_period_*, amount_*, status, ... | Yes (nullable) | Yes (FK) | Yes (user_id, nullable) | 5 |
| **quarterly_reports** | id, report_id, project_id, generated_by_user_id, quarter, year, period_*, project_title, project_type, place, **society_name**, commencement_month_year, in_charge, total_beneficiaries, goal, account_period_*, amount_*, status, ... | Yes (nullable) | Yes (FK) | Yes (generated_by_user_id, nullable) | 0 |
| **half_yearly_reports** | id, report_id, project_id, generated_by_user_id, half_year, year, period_*, project_title, project_type, place, **society_name**, commencement_month_year, in_charge, total_beneficiaries, goal, account_period_*, amount_*, status, ... | Yes (nullable) | Yes (FK) | Yes (generated_by_user_id, nullable) | 0 |
| **annual_reports** | id, report_id, project_id, generated_by_user_id, year, period_*, project_title, project_type, place, **society_name**, commencement_month_year, in_charge, total_beneficiaries, goal, account_period_*, amount_*, status, ... | Yes (nullable) | Yes (FK) | Yes (generated_by_user_id, nullable) | 0 |

### Detail / child tables (no society_name, link to parent report)

| Table | Columns (key) | society_name | project_id | user_id | Row count |
|-------|---------------|--------------|------------|---------|-----------|
| quarterly_report_details | id, quarterly_report_id, particulars, opening_balance, amount_*, total_expenses, closing_balance, expenses_by_month | No | No (via quarterly_report_id) | No | N/A |
| annual_report_details | id, annual_report_id, particulars, opening_balance, amount_*, total_expenses, closing_balance, expenses_by_* | No | No | No | N/A |
| half_yearly_report_details | id, half_yearly_report_id, particulars, opening_balance, amount_*, total_expenses, closing_balance, expenses_by_quarter | No | No | No | N/A |

### Related tables (report data, no society_name)

| Table | Columns (key) | society_name | References |
|-------|---------------|--------------|------------|
| report_attachments | id, attachment_id, report_id (FK to DP_Reports), file_path, file_name, description | No | DP_Reports.report_id |
| report_comments | id, R_comment_id, report_id (FK to DP_Reports), user_id, comment | No | DP_Reports.report_id |
| ai_report_insights | id, report_type, report_id, executive_summary, key_achievements, ... | No | quarterly/half_yearly/annual report_id |
| aggregated_report_objectives | id, report_type, report_id, objective_text, ... | No | quarterly/half_yearly/annual reports |
| aggregated_report_photos | id, report_type, report_id, photo_path, source_monthly_report_id, ... | No | quarterly/half_yearly/annual + DP_Reports |

### Legacy / non-DP tables

- **projects**: has `society_name` (legacy) and `society_id` (migrations present)
- **users**: has `society_name` (legacy) and `society_id` (migrations present)
- **old_development_projects**: has `society_name` (not null)
- **RQ*** tables (e.g. rqwd_inmates_profiles, rqis_age_profiles, qrdl_annexure): link to DP_Reports via report_id; no society_name

---

## Report Write Paths

### Monthly reports (DP_Reports)

| File | Method | How society_name is assigned |
|------|--------|------------------------------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | `createReport()` (≈392) | `society_name` => `$validatedData['society_name'] ?? ''` |
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | `updateReport()` (≈1553) | `society_name` => `$validatedData['society_name'] ?? ''` |

**Request source:** Form submits `society_name` from the report create/edit views.

**Form pre-fill source:**

| View | Pre-fill |
|------|----------|
| `resources/views/reports/monthly/ReportAll.blade.php` | `value="{{ $user->society_name }}"` (readonly) |
| `resources/views/reports/monthly/ReportCommonForm.blade.php` | `value="{{ $user->society_name }}"` (readonly) |
| `resources/views/reports/monthly/developmentProject/reportform.blade.php` | `value="{{ $user->society_name }}"` (readonly) |
| `resources/views/reports/monthly/edit.blade.php` | `value="{{ $report->society_name }}"` (readonly) |

So monthly reports persist `society_name` from the authenticated **user**, not from the project. Validation: `'society_name' => 'nullable|string|max:255'` (ReportController::validateRequest, StoreMonthlyReportRequest).

### Quarterly / half-yearly / annual reports

| File | Method | How society_name is assigned |
|------|--------|------------------------------|
| `app/Services/Reports/QuarterlyReportService.php` | `generateQuarterlyReport()` (≈69) | `'society_name' => optional($project->society)->name ?? $project->society_name` |
| `app/Services/Reports/HalfYearlyReportService.php` | `generateHalfYearlyReport()` (≈82) | Same |
| `app/Services/Reports/AnnualReportService.php` | `generateAnnualReport()` (≈91) | Same |

These use the **project** as source: `project->society->name` when available, else `project->society_name`.

### Other report-related controllers

| File | Usage |
|------|-------|
| `app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php` | Validates `society_name` (nullable) |
| `app/Http/Controllers/Reports/Quarterly/InstitutionalSupportController.php` | Validates `society_name` (nullable) |
| `app/Http/Controllers/Reports/Quarterly/DevelopmentLivelihoodController.php` | Validates `society_name` (nullable) |
| `app/Http/Controllers/Reports/Quarterly/WomenInDistressController.php` | Validates `society_name` (nullable) |
| `app/Http/Controllers/Reports/Quarterly/SkillTrainingController.php` | Validates `society_name` (nullable) |

These controllers handle legacy quarterly report types (RQIS, RQDL, RQWD, RQST). Their forms may submit society_name; persistence path not traced in this audit.

---

## Report Read Paths

### Controllers

| File | Query / usage |
|------|---------------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | DPReport::where('project_id'|'report_id'), user/role scope via Project::where(user_id/parent_id) |
| `app/Http/Controllers/Reports/Monthly/ExportReportController.php` | `$report->society_name` (line 432): `$section->addText("Society Name: {$report->society_name}")` |
| `app/Http/Controllers/Reports/Aggregated/ReportComparisonController.php` | QuarterlyReport/HalfYearlyReport/AnnualReport::where status, project_id, user scope; no society_name filter |
| `app/Http/Controllers/Reports/Aggregated/AggregatedQuarterlyReportController.php` | Filter by project_id, quarter, year, user scope |
| `app/Http/Controllers/Reports/Aggregated/AggregatedHalfYearlyReportController.php` | Filter by project_id, half_year, year, user scope |
| `app/Http/Controllers/Reports/Aggregated/AggregatedAnnualReportController.php` | Filter by project_id, year, user scope |

No report-layer queries use `where('society_name', ...)` or `groupBy('society_name')`. Filtering is by project_id and user/role scope.

### Blade views (display society_name)

| View | Usage |
|------|-------|
| `reports/monthly/show.blade.php` | `{{ $report->society_name }}` |
| `reports/monthly/edit.blade.php` | `value="{{ $report->society_name }}"` |
| `reports/monthly/pdf.blade.php` | `{{ $report->society_name }}` |
| `reports/monthly/PDFReport.blade.php` | `{{ $report->society_name }}` |
| `reports/quarterly/developmentProject/show.blade.php` | `{{ $report->society_name }}` |
| `reports/quarterly/developmentLivelihood/show.blade.php` | `{{ $report->society_name }}` |
| `reports/quarterly/institutionalSupport/show.blade.php` | (via list) |
| `reports/quarterly/skillTraining/show.blade.php` | `{{ $report->society_name }}` |
| `reports/quarterly/womenInDistress/show.blade.php` | `{{ $report->society_name }}` |
| `reports/quarterly/*/list.blade.php` | `{{ $report->society_name }}` (developmentProject, developmentLivelihood, institutionalSupport, skillTraining, womenInDistress) |

### Report exports

- **ExportReportController::downloadDoc()** (line 432): Word export includes `"Society Name: {$report->society_name}"`.
- Exports read stored report fields; no COALESCE with societies table.

---

## Backfill Feasibility Matrix

| Table | project_id | society_name | Backfill strategy | Classification |
|-------|------------|--------------|-------------------|----------------|
| **DP_Reports** | Yes (FK to projects) | Yes | project_id → projects.society_id | **Backfillable via project relation** |
| **quarterly_reports** | Yes (FK to projects) | Yes | project_id → projects.society_id | **Backfillable via project relation** |
| **half_yearly_reports** | Yes (FK to projects) | Yes | project_id → projects.society_id | **Backfillable via project relation** |
| **annual_reports** | Yes (FK to projects) | Yes | project_id → projects.society_id | **Backfillable via project relation** |
| quarterly_report_details | No (quarterly_report_id) | No | Via quarterly_reports → project_id → projects.society_id | N/A (derive from parent) |
| annual_report_details | No | No | Via annual_reports | N/A |
| half_yearly_report_details | No | No | Via half_yearly_reports | N/A |
| report_attachments | No (report_id → DP_Reports) | No | Via DP_Reports.project_id | N/A |
| report_comments | No | No | Via DP_Reports | N/A |
| ai_report_insights | No (report_id to QR/HY/AR) | No | Via parent report → project_id | N/A |
| aggregated_report_objectives | No | No | Via parent report | N/A |
| aggregated_report_photos | No | No | Via parent report | N/A |

**Note:** projects must have `society_id` populated before report backfill. Migrations for projects.society_id exist (e.g. `add_society_id_to_projects_table`, `production_phase4_add_projects_society_id`).

**Name-match fallback:** If any report row has a project_id whose project has null society_id (e.g. orphan or pre-migration data), `society_name` could be used to match `societies.name`. Ambiguous if multiple societies share a name or names have drifted.

---

## Risk Assessment

| Risk | Severity | Description |
|------|----------|-------------|
| **Historical drift** | Medium | Monthly reports store `$user->society_name`; quarterly/half-yearly/annual use `optional($project->society)->name ?? $project->society_name`. If user.society_name ≠ project society, monthly reports can diverge from project-level society. |
| **Ambiguous mappings** | Low | Name-match backfill is ambiguous when society names are duplicated or changed. Current tables all have project_id; project-based backfill is preferred. |
| **Rename impact** | Medium | If societies.name is renamed, stored society_name in reports becomes stale. Read paths show society_name directly; no FK to societies yet. |
| **Performance** | Low | No report queries filter by society_name. Adding society_id and indexing would not harm current queries; could improve future society-scoped reports. |

---

## Recommendation

### Is Phase 5B5A safe?

**Conditionally yes**, with these prerequisites:

1. **projects.society_id** must be populated (and enforced) before report backfill.
2. **users.society_id** (and users.society_name alignment) should be reviewed for monthly report consistency.
3. No schema changes or migrations in this audit; Phase 5B5A implementation should add `society_id` to report tables and backfill via project relation.

### Blockers

| Blocker | Status |
|---------|--------|
| projects.society_id nullable / not backfilled | Must be resolved in prior phases |
| Report tables lack society_id | Planned for Phase 5B5A |
| Monthly report source (user vs project) | Documented; consider aligning monthly report society to project in Phase 5B5A |

### Next steps (for Phase 5B5A planning)

1. Add `society_id` (nullable, FK to societies) to DP_Reports, quarterly_reports, half_yearly_reports, annual_reports.
2. Backfill from projects: `UPDATE report_table r JOIN projects p ON r.project_id = p.project_id SET r.society_id = p.society_id WHERE p.society_id IS NOT NULL`.
3. Update write paths: monthly report to use `$project->society_id` (or project.society->id) instead of user.society_name; aggregated services already use project.
4. Update read paths (views, exports): optional display via `$report->society?->name ?? $report->society_name` for read-switch.
5. Plan eventual deprecation of society_name after society_id is fully adopted.

---

**End of Audit**
