# Monthly Report Generation Audit — Validated Findings & Comprehensive Discrepancy Matrix
**Date:** 2026-06-27  
**Scope:** Full monthly report lifecycle (create → store → edit → view → PDF/DOC), budget/SOA paths, authorization, legacy routes, annexure handlers, and aggregated report dependencies across all 12 project types.  
**Validation status:** All findings below were cross-referenced against actual source files in this repository.

---

## Architecture Overview

Monthly reporting is **unified** through `ReportController` + `DPReport` for all **12 project types** defined in [`ProjectType`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Constants/ProjectType.php). Type-specific annexures use helper controllers (`LivelihoodAnnexureController`, `InstitutionalOngoingGroupController`, `ResidentialSkillTrainingController`, `CrisisInterventionCenterController`).

```
Canonical Path:
  ReportController@create → ReportController@store → ReportController@edit
  → ReportController@update → ReportController@show → ExportReportController (PDF/DOC)

Legacy Path:
  MonthlyDevelopmentProjectController@createForm → redirect → monthly.report.create  ✓
  MonthlyDevelopmentProjectController@store      → ACTIVE, bypasses validation        ✗
```

| Stage | Data source |
|---|---|
| **Create / Edit forms** | Live project budgets via `BudgetCalculationService::getBudgetsForReport()` |
| **Store / Update** | Persisted to `DPAccountDetail` (snapshot) |
| **View** | `$report->accountDetails` (persisted snapshot) |
| **PDF/DOC export** | `$report->accountDetails` — confirmed correct |

---

## Project Types & Report Paths

**`ProjectType` constants (12 types)** — `app/Constants/ProjectType.php`:

| Constant | Value |
|---|---|
| `DEVELOPMENT_PROJECTS` | `Development Projects` |
| `NEXT_PHASE_DEVELOPMENT_PROPOSAL` | `NEXT PHASE - DEVELOPMENT PROPOSAL` |
| `CHILD_CARE_INSTITUTION` | `CHILD CARE INSTITUTION` |
| `RURAL_URBAN_TRIBAL` | `Rural-Urban-Tribal` |
| `LIVELIHOOD_DEVELOPMENT_PROJECTS` | `Livelihood Development Projects` |
| `RESIDENTIAL_SKILL_TRAINING` | `Residential Skill Training Proposal 2` |
| `CRISIS_INTERVENTION_CENTER` | `PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER` |
| `INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL` | `Institutional Ongoing Group Educational proposal` |
| `INDIVIDUAL_LIVELIHOOD_APPLICATION` | `Individual - Livelihood Application` |
| `INDIVIDUAL_ACCESS_TO_HEALTH` | `Individual - Access to Health` |
| `INDIVIDUAL_ONGOING_EDUCATIONAL` | `Individual - Ongoing Educational support` |
| `INDIVIDUAL_INITIAL_EDUCATIONAL` | `Individual - Initial - Educational support` |

**SOA routing coverage by stage** (validated from blade files):

| Project type | Create SOA | View SOA | Edit SOA |
|---|---|---|---|
| Development Projects | `development_projects` | ✓ | ✓ |
| NEXT PHASE - DEVELOPMENT PROPOSAL | `development_projects` | ✓ | ✓ (Fixed Phase 12.2) |
| CHILD CARE INSTITUTION | `development_projects` | ✓ | ✓ (Fixed Phase 12.2) |
| Rural-Urban-Tribal | `development_projects` | ✓ | ✓ (Fixed Phase 12.2) |
| Livelihood Development Projects | `development_projects` | ✓ | ✓ (Fixed Phase 12.2) |
| Residential Skill Training Proposal 2 | `development_projects` | ✓ | ✓ (Fixed Phase 12.2) |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | `development_projects` | ✓ | ✓ (Fixed Phase 12.2) |
| Institutional Ongoing Group Educational (IGE) | `institutional_education` | ✓ | ✓ (Fixed Phase 12.1) |
| Individual - Livelihood (ILP) | `individual_livelihood` | ✓ | ✓ (Fixed Phase 12.3) |
| Individual - Access to Health (IAH) | `individual_health` | ✓ | ✓ (Fixed Phase 12.3) |
| Individual - Ongoing Educational (IES) | `individual_ongoing_education` | ✓ | ✓ (Fixed Phase 12.3) |
| Individual - Initial Educational (IIES) | `individual_education` | ✓ | ✓ (Fixed Phase 12.3) |

---

## Critical Discrepancies (C1 – C5)

### C1 — IGE SOA uses IIES field names (empty budget rows on create/edit)
**Status: ✅ CONFIRMED**

**Root cause:** [`ProjectIGEBudget`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Models/OldProjects/IGE/ProjectIGEBudget.php) has columns `name` and `total_amount`. Both IGE blade files incorrectly reference `$budget->iies_particular` and `$budget->iies_amount` — IIES-specific field names that do not exist on the IGE model.

**Affected files:**
- [`partials/statements_of_account/institutional_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php) (L190–193)
- [`partials/edit/statements_of_account/institutional_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php) (L199–202)

**Impact:** New IGE monthly reports show **blank particulars and zero amounts** on all budget rows. Prior-month expense carry-forward keyed by `$lastExpenses[$budget->iies_particular]` also fails (see M5).

---

### C2 — Edit SOA fallback breaks 6 project types
**Status: ✅ CONFIRMED**

**Root cause:** The SOA routing block in [`edit.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/edit.blade.php) (L159–174) only maps 6 of 12 types to typed partials. 6 institutional types fall through to the **generic fallback** [`partials/edit/statements_of_account.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account.blade.php).

**Affected types:** NPD, CCI, RUT, LDP, RST, CIC.

**Impact:**
1. Renders stale `amount_forwarded[]` column (removed from create path).
2. Throws a JavaScript `null` reference error on row calculation (`#total_forwarded` element missing).
3. Missing budget summary cards.

---

### C3 — Legacy store route still active
**Status: ✅ CONFIRMED**

[`routes/web.php` L476](file:///Applications/MAMP/htdocs/Laravel/SalProjects/routes/web.php#L476): `Route::post('development-project/store', [MonthlyDevelopmentProjectController::class, 'store'])`.

**Impact:** Bypasses `StoreMonthlyReportRequest` validation, duplicate period checks, attachment parity (`new_attachment_files`), and type-specific annexure handlers.

---

### C4 — Edit mode budget field name discrepancies for 4 Individual project types (IIES, IAH, ILP, IES)
**Status: ✅ NEWLY IDENTIFIED & CONFIRMED**

In addition to IGE (C1), a detailed audit of the 4 individual edit partials in [`resources/views/reports/monthly/partials/edit/statements_of_account/`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/) revealed that their budget row pre-fill logic references incorrect or uncalculated model properties, creating severe discrepancies between Create and Edit modes:

1. **IIES (`individual_education.blade.php` L199–202):**
   - Create mode uses `$budget->iies_particular` and `$budget->amount_sanctioned`.
   - Edit mode uses `$budget->name . ' - ' . $budget->study_proposed`, `$budget->amount_requested`, and `$lastExpenses[$budget->name]`.
   - *Impact:* In Edit mode, `$budget->name` and `$budget->amount_requested` are `null` on objects returned by `BudgetCalculationService`. Budget rows in Edit mode render with **blank particulars and 0.00 amounts**.
2. **IAH (`individual_health.blade.php` L200–201):**
   - Create mode uses `$budget->amount_sanctioned`.
   - Edit mode uses `$budget->amount_requested ?? 0.00`.
   - *Impact:* `SingleSourceContributionStrategy` calculates `amount_sanctioned` (subtractions applied); `amount_requested` does not exist on the object. Edit mode renders **0.00 sanctioned amounts**.
3. **ILP (`individual_livelihood.blade.php` L200–201):**
   - Create mode uses `$budget->amount_sanctioned`.
   - Edit mode uses `$budget->amount_requested ?? 0.00`.
   - *Impact:* Edit mode renders **0.00 sanctioned amounts**.
4. **IES (`individual_ongoing_education.blade.php` L200–201):**
   - Create mode uses `$budget->amount_sanctioned`.
   - Edit mode uses `$budget->amount ?? 0.00`.
   - *Impact:* `amount` is the raw unadjusted expense. Edit mode renders **raw unadjusted expenses instead of net sanctioned amounts** after scholarship/contribution deductions.

---

### C5 — PDF export completely ignores annexures for all 4 annexure project types (LDP, IGE, RST, CIC)
**Status: ✅ NEWLY IDENTIFIED & CONFIRMED**

**Root cause:** [`ExportReportController@downloadPdf`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/ExportReportController.php#L108-L135) correctly fetches `$annexures`, `$ageProfiles`, `$traineeProfiles`, and `$inmateProfiles` based on project type and passes them into `$data` for [`PDFReport.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/PDFReport.blade.php).

However, [`PDFReport.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/PDFReport.blade.php) contains **zero code** to render any of these variables!

**Impact:** Every exported PDF for LDP (Livelihood Annexure), IGE (Age Profiles), RST (Trainee Profiles), and CIC (Inmate Profiles) is **missing its type-specific annexure sections entirely**, printing only the standard basic info, objectives, SOA, and photos.

---

## Medium Discrepancies (M1 – M8)

### M1 — Create vs edit SOA architecture split
**Status: ✅ CONFIRMED**  
Create/view use a unified router template (`partials/statements_of_account.blade.php`). Edit uses an incomplete `@if/@elseif` chain in `edit.blade.php` (L159–174).

### M2 — Update authorization mismatch
**Status: ✅ CONFIRMED**  
[`UpdateMonthlyReportRequest::authorize()`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php#L50-L55) hard-gates on `executor`/`applicant` role only. Any `provincial` or `coordinator` update POST is blocked before controller role logic can run.

### M3 — Export auth excludes in-charge executors
**Status: ✅ CONFIRMED**  
[`ExportReportController`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/ExportReportController.php) (L62, L275) only checks `$report->user_id === $user->id` for executors. Executors who are `in_charge` of a project get `403` on download.

### M4 — Dual budget engines for IGE overview vs rows
**Status: ✅ CONFIRMED (Scaffolding note)**  
Row prefetch uses `DirectMappingStrategy`; `ProjectFinancialResolver` places IGE in `DIRECT_MAPPED_INDIVIDUAL_TYPES`. (Note: `ProjectFinancialResolver` is not yet wired).

### M5 — `getLastExpenses()` keyed by particulars string
**Status: ✅ CONFIRMED**  
[`ReportController::getLastExpenses()`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/ReportController.php#L140) keys by `particulars`. Corrupted empty particulars from C1/C4 cause `expenses_last_month = 0` on subsequent months.

### M6 — DOC export schema mismatch for RST/CIC
**Status: ✅ CONFIRMED**  
[`ExportReportController`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/ExportReportController.php) DOC generation references `$profile->trainee_name` (L534) and `$profile->inmate_name` (L567). Monthly report tables [`RQSTTraineeProfile`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Models/Reports/Monthly/RQSTTraineeProfile.php) and [`RQWDInmatesProfile`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Models/Reports/Monthly/RQWDInmatesProfile.php) store aggregate category counts (`education_category`, `age_category`, `number`), not per-person names. All DOC rows render as `N/A`.

### M7 — View shows persisted SOA but loads live budgets for monitoring
**Status: ✅ CONFIRMED (By Design)**  
`ReportController@show` loads live budgets for monitoring while display uses persisted snapshots.

### M8 — LDP Annexure updates do not prune deleted or renamed records (`QRDLAnnexure`)
**Status: ✅ NEWLY IDENTIFIED & CONFIRMED**  
[`LivelihoodAnnexureController@handleLivelihoodAnnexure`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/LivelihoodAnnexureController.php#L44-L58) uses `QRDLAnnexure::updateOrCreate` matched on `['report_id' => $report_id, 'dla_beneficiary_name' => $beneficiaryName]`. If an executor edits a beneficiary's name or removes a beneficiary row during edit, the old record is never deleted from `rqdl_annexures`. Duplicate or orphan rows persist in the database and appear in subsequent views/exports. (Contrast with `InstitutionalOngoingGroupController` which calls `delete()` first before inserting).

---

## Low / Hygiene Issues (L1 – L8)

| ID | Issue | Status |
|---|---|---|
| L1 | `ReportCommonForm.blade.php` and `partials/create/statements_of_account.blade.php` are orphan views | ✅ Confirmed |
| L2 | Unknown project type silently falls back to `DirectMappingStrategy('Development Projects')` in `BudgetCalculationService` | ✅ Confirmed |
| L3 | UI typo: `"4. Statements of Account this "` in 4 blade files | ✅ Confirmed |
| L4 | `monthly.report.show` resolves to `/show/{report_id}` outside the `reports/monthly/` route group | ✅ Confirmed |
| L5 | Budget sync flags default off in `config/budget.php` | ✅ Confirmed |
| L6 | Prior audit doc claim that export uses `getBudgetsForExport()` is outdated | ✅ Confirmed |
| L7 | No Excel export for monthly reports | ✅ Confirmed |
| L8 | Aggregated/Quarterly reports cover only 6 of 12 types | ✅ Confirmed |

---

## Aggregated Reports (Quarterly / Half-Yearly / Annual)

These roll up from **approved monthly reports**. Key findings:

- Only a subset of project types have dedicated quarterly form controllers under `Reports/Quarterly/`
- `QuarterlyReportService` (`app/Services/Reports/QuarterlyReportService.php`) maps 6 types (DP, LDP, RST, CIC, CCI, EduRUT) but not the 4 individual types or IGE
- Aggregated generation depends on monthly SOA data being correct at source — IGE (C1) and edit-fallback (C2) bugs **propagate upward** into quarterly aggregates
- No per-type test matrix for aggregation

---

## Test Coverage Gaps

Existing tests (`MonthlyReportTest`, `BudgetCalculationServiceReportTest`) cover:
- DP draft create, society_id, unapproved block, edit status gating
- Budget math for DP, ILP, IIES only

**Not covered (confirmed gaps):**

| Gap | Related finding |
|---|---|
| All 12 types end-to-end (create → store → edit → view) | C1, C2, C4 |
| IGE & Individual field mapping in blades | C1, C4 |
| Edit SOA routing for NPD/CCI/RUT/LDP/RST/CIC | C2 |
| Export auth (in-charge executor, 403 scenario) | M3 |
| PDF vs DOC content parity and annexure rendering | C5, M6 |
| Legacy `monthly.developmentProject.store` bypass | C3 |
| Duplicate period validation | C3 bypass |
| Provincial/coordinator update paths | M2 |
| Annexure record pruning on edit | M8 |

---

## Recommended Fix Priority

| Priority | ID | Fix | Effort |
|---|---|---|---|
| **P0** | C1 | Fix IGE blades: replace `$budget->iies_particular` → `$budget->name`, `$budget->iies_amount` → `$budget->total_amount` | Low |
| **P0** | C2 | Unify edit SOA routing in `edit.blade.php`: map NPD/CCI/RUT/LDP/RST/CIC to `development_projects` edit partial | Low |
| **P0** | C4 | Fix Edit SOA field mappings for IIES, IAH, ILP, and IES to match Create mode (`amount_sanctioned`, correct particulars) | Low |
| **P0** | C5 | Add annexure rendering blocks to `PDFReport.blade.php` for LDP, IGE, RST, and CIC | Medium |
| **P0** | C3 | Deactivate or 410-redirect `MonthlyDevelopmentProjectController::store()` | Low |
| **P1** | M3 | Align export auth with edit: check owner OR `in_charge` in `ExportReportController` | Low |
| **P1** | M8 | Fix `LivelihoodAnnexureController`: prune/delete outdated records before updating | Low |
| **P1** | M6 | Align DOC export schema for RST/CIC with aggregate table structures | Medium |
| **P1** | M2 | Resolve `UpdateMonthlyReportRequest` role restriction | Medium |
| **P2** | L1-L3 | Delete orphan views, fix typos ("Statements of Account this") | Trivial |

---

## Summary

The monthly report system is architecturally sound: single controller, unified `DPReport` model, and persisted SOA snapshots are used correctly for exports. However, a deep code audit across all 12 project types identified critical functional failures:

1. **C1 & C4 (Blade property mismatches):** IGE, IIES, IAH, ILP, and IES all suffer from property name mismatches between backend strategy outputs and Blade template expectations, causing blank particulars or 0.00 amounts in create or edit forms.
2. **C2 (Edit SOA fallback):** 6 of 8 institutional project types render a broken generic edit form with stale columns and JavaScript calculation crashes.
3. **C5 (Missing PDF Annexures):** PDF exports completely ignore type-specific annexures (LDP, IGE, RST, CIC) due to missing template rendering blocks.
4. **C3 (Legacy Store Bypass) & M8 (LDP Orphan Retention):** Legacy routes bypass core validation and LDP annexure updates fail to delete removed/renamed records.

---

## File Reference Index

| File | Relevance |
|---|---|
| [`app/Constants/ProjectType.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Constants/ProjectType.php) | All 12 project type constants |
| [`app/Http/Controllers/Reports/Monthly/ReportController.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/ReportController.php) | Main monthly report controller |
| [`app/Http/Controllers/Reports/Monthly/ExportReportController.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/ExportReportController.php) | PDF/DOC export controller (C5, M3, M6) |
| [`app/Http/Controllers/Reports/Monthly/LivelihoodAnnexureController.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/app/Http/Controllers/Reports/Monthly/LivelihoodAnnexureController.php) | LDP Annexure handler (M8) |
| [`resources/views/reports/monthly/edit.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/edit.blade.php) | Edit SOA router (C2) |
| [`resources/views/reports/monthly/PDFReport.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/PDFReport.blade.php) | PDF template missing annexures (C5) |
| [`resources/views/reports/monthly/partials/edit/statements_of_account/`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/) | Edit SOA partials with field bugs (C1, C4) |
| [`resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php) | C1 bug location — create/view SOA (L190–193) |
| [`resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php) | C1 bug location — edit SOA (L199–202) |
| [`routes/web.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/routes/web.php) | Route definitions — legacy store (L476), show URL shape (L541) |
