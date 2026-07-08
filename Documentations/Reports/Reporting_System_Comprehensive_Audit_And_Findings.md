# Reporting System — Comprehensive Audit & Findings

**Date:** 2026-06-13  
**Scope:** Monthly, quarterly (legacy), and aggregated reporting across all 12 project types  
**Sources reviewed:** `Documentations/Reports`, `Documentations/V1/Reports`, `Documentations/V2/Reports`, related V1/V2/V2A implementation logs, `Documentations/REVIEW/project flow` reporting audits, production log `storage/logs/Prod-issue in report laravel-5.log`, and live codebase verification  
**Prior analysis:** Builds on `Executor_Report_Writing_And_Budget_Gaps_Analysis.md` (April 2026) with fresh code checks and log sampling

---

## 1. Executive Summary

The application has **three reporting streams** that are not fully aligned with each other or with documentation:

| Stream | Mechanism | Status |
|--------|-----------|--------|
| **Monthly (primary)** | Unified `ReportController` + `DPReport` model for all 12 types | Partially working; **P0 create failure** on production |
| **Quarterly (legacy)** | Five separate controllers/models (`DevelopmentProjectController`, etc.) | Exists for 5 institutional types; **routes lack auth middleware** |
| **Aggregated (Q/HY/Annual)** | `QuarterlyReportService` etc., built from approved monthly `DPReport` rows | Generally complete; depends on monthly data quality |

**Documentation vs reality:** V1 `COMPREHENSIVE_REPORTS_REVIEW.md` and `TASKS_AND_STATUS.md` describe **12/12 monthly types as complete**. Code and production logs show **multiple active blockers**: report create SQL failures, commented-out create partials, financial data not synced to `projects` table for individual/IGE types, workflow authorization mismatches, and society snapshot bugs.

**Highest-impact finding:** Monthly report **create fails in production** when `DP_Reports.society_id` is NOT NULL but the controller inserts the row before setting snapshot fields — confirmed 8 times in production logs (Mar 6–13, 2026) for projects `DP-0009` and `DP-0006`.

---

## 2. Methodology

1. Read all files in `Documentations/Reports/` and prioritized report docs across V1, V2, V2A, and REVIEW trees (~40 documents).
2. Cross-checked documented claims against:
   - `app/Http/Controllers/Reports/`
   - `resources/views/reports/`
   - `config/budget.php`, `BudgetCalculationService`
   - `routes/web.php`
   - `app/Http/Requests/Reports/Monthly/`
3. Sampled production log for report creation errors, financial invariant warnings, and project-type failures.
4. Ran `php artisan test --filter=FinancialInvariant` — 8 tests pass (unit logic works; production data still violates invariants).

**Test gap:** No feature or unit tests exist specifically for report CRUD, SOA calculations, or `BudgetCalculationService` in reports (autoload references `BudgetCalculationServiceTest` but file is missing).

---

## 3. Architecture Overview

### 3.1 Controllers

| Area | Location | Notes |
|------|----------|-------|
| Monthly (canonical) | `app/Http/Controllers/Reports/Monthly/ReportController.php` (~1987 lines) | All 12 types via `DPReport` |
| Monthly (alternate DP) | `MonthlyDevelopmentProjectController.php` | Activity-based `reportform`; **not linked** from standard "Write Report" |
| Monthly (type helpers) | `LivelihoodAnnexureController`, `InstitutionalOngoingGroupController`, `ResidentialSkillTrainingController`, `CrisisInterventionCenterController` | Annexure/profile data |
| Monthly (export) | `ExportReportController.php` | PDF/DOC; uses `getBudgetsForExport` (no contribution calc) |
| Quarterly (legacy) | `app/Http/Controllers/Reports/Quarterly/*` (5 controllers) | Separate old schema; manual budget fields |
| Aggregated | `AggregatedQuarterlyReportController`, `AggregatedHalfYearlyReportController`, `AggregatedAnnualReportController` | AI-assisted; pulls from monthly |
| Dead code | `PartialDevelopmentLivelihoodController.php` | No route references |

### 3.2 Budget resolution paths (misalignment)

| Path | Budget source | Risk |
|------|---------------|------|
| `ReportController` create/edit/show | `BudgetCalculationService::getBudgetsForReport($project, true)` | **Canonical** |
| `MonthlyDevelopmentProjectController` | `ProjectBudget::max('phase')` + `ProjectFinancialResolver` | Uses **highest phase**, not current phase |
| Legacy quarterly controllers | Old models + manual form fields | Completely separate from V2 budget service |
| Export | `getBudgetsForExport(..., false)` | No contribution distribution |
| Unknown project types | Fallback to `DirectMappingStrategy('Development Projects')` | Silent wrong strategy + warning log |

### 3.3 Society snapshot design vs implementation

**Design (Wave 6A):** `DPReport` stores immutable `society_id`, `society_name`, `province_id` from project at creation.

**Implementation bug** (`ReportController.php` lines 400–425):

```php
$report = DPReport::create([...]);  // society_id NOT included
if ($project) {
    $report->society_id = $project->society_id;
    ...
    $report->save();
}
```

When DB column `society_id` is NOT NULL with no default, the **first INSERT fails** before snapshot assignment runs. Production confirms this for `DP-0009-01` and `DP-0006-01`.

**Display mismatch:** Create form shows `$user->society_name` and `$user->center` (`ReportAll.blade.php` lines 37–41), but snapshot stores `$project->society_*` — user-facing values can differ from persisted report.

**Legacy quarterly:** Still accepts `society_name` from request with no snapshot pattern.

---

## 4. Critical Issues (Priority Order)

### P0 — Production blockers

| # | Issue | Evidence | Affected types |
|---|-------|----------|----------------|
| 1 | **Report create fails: `society_id` NOT NULL** | 8× `Failed to create report` in prod log (Mar 6–13); SQL error 1364 | All monthly (any project where insert runs before snapshot) |
| 2 | **Legacy quarterly routes unauthenticated** | `routes/web.php` lines 545–604 are **outside** `auth` middleware group (group closes at 543) | 5 legacy quarterly types |

### P1 — Functional / data integrity

| # | Issue | Evidence | Affected types |
|---|-------|----------|----------------|
| 3 | **Edit authorization narrower than submit** | `UpdateMonthlyReportRequest` allows only `draft`, `reverted_by_provincial`, `reverted_by_coordinator`; `ReportStatusService::submitToProvincial` also allows `reverted_to_executor`, `reverted_to_applicant`, etc. | All monthly |
| 4 | **No approval/ownership gate on create/store** | `StoreMonthlyReportRequest::authorize()` checks role only | All monthly |
| 5 | **`projects.amount_sanctioned = 0` on approved projects** | Prod warnings for IOGEP-0006, IAH-0002, ILA-0001; resolver returns 0 for overview fields | IGE, IAH, ILP, IES, IIES |
| 6 | **`NEXT PHASE - DEVELOPMENT PROPOSAL` missing from `config/budget.php`** | No `field_mappings` entry → Development fallback | NPD |
| 7 | **Wrong project type string on create SOA** | `ReportAll.blade.php:162` checks `'Institutional - Initial - Educational support'`; constant is `'Institutional Ongoing Group Educational proposal'` | IGE |
| 8 | **CCI project edit 500 when statistics missing** | Prod: `ProjectController@edit - CCI Statistics not found` (CCI-0001, CCI-0002) | CCI |
| 9 | **Dual budget path for Development Projects** | `MonthlyDevelopmentProjectController` bypasses `BudgetCalculationService` | DP |

### P2 — UX / documentation drift

| # | Issue | Evidence | Affected types |
|---|-------|----------|----------------|
| 10 | **Type-specific create partials commented out** | `ReportAll.blade.php` lines 90–112 | CCI, Edu-RUT, NPD, ILP, IAH, IES, IIES |
| 11 | **Photos section commented out on create** | `ReportAll.blade.php` lines 173+ | All monthly create |
| 12 | **Approved projects hidden by FY filter** | `ProjectController::approvedProjects` defaults current FY; null/out-of-range `commencement_month_year` excluded | All |
| 13 | **Duplicate key in view SOA router** | `view/statements_of_account.blade.php` line 10–11 duplicates IES key | IES (harmless but sloppy) |
| 14 | **V1 docs claim 12/12 complete; manual testing still pending** | `TASKS_AND_STATUS.md`, Phase 11 unchecked | All |
| 15 | **No report-specific automated tests** | `tests/` has no report CRUD tests | All |

---

## 5. Per Project Type Findings

Legend: **M** = Monthly, **Q-legacy** = Legacy quarterly controller, **Q-agg** = Aggregated from monthly

### 5.1 Institutional types

| Project type | M | Q-legacy | Q-agg | SOA partial | Budget strategy | Key issues |
|--------------|---|----------|-------|-------------|-------------------|------------|
| **Development Projects (DP)** | ✅ | ✅ `DevelopmentProjectController` | ✅ | `development_projects` | `DirectMappingStrategy` (current phase) | P0 create failure; dual create paths; alt path uses max phase not current |
| **Livelihood Development (LDP)** | ✅ + annexure | ✅ `DevelopmentLivelihoodController` | ✅ | `development_projects` + annexure | `DirectMappingStrategy` | Annexure active; legacy Q has no save-as-draft |
| **Residential Skill Training (RST)** | ✅ + trainee profiles | ✅ `SkillTrainingController` | ✅ | `development_projects` + RST partial | `DirectMappingStrategy` | Generally aligned |
| **Crisis Intervention Center (CIC)** | ✅ + inmate profiles | ✅ `WomenInDistressController` | ✅ | `development_projects` + CIC partial | `DirectMappingStrategy` | Prod: `amount_sanctioned=0` on CIC-0003 in resolver output |
| **CHILD CARE INSTITUTION (CCI)** | ✅ | ❌ no dedicated legacy Q | ✅ | `development_projects` (router) | `DirectMappingStrategy` | Create partial **commented out**; edit 500 if statistics missing |
| **Rural-Urban-Tribal (Edu-RUT)** | ✅ | ❌ unverified | ✅ | `development_projects` (router) | `DirectMappingStrategy` | Create partial **commented out** |
| **Institutional Ongoing Group Ed (IGE)** | ✅ + age profiles | ✅ `InstitutionalSupportController` | ✅ | `institutional_education` | `DirectMappingStrategy` on `ProjectIGEBudget` | **IOGEP-0006**: prod invariant violations; IGE field mapping flagged "may need verification" in config; wrong type string on create SOA line 162 |
| **NEXT PHASE - DEVELOPMENT (NPD)** | ✅ | ❌ unverified | ✅ | `development_projects` (fallback) | **Missing config** → Development fallback | Create partial **commented out**; `ProjectController@edit` warns "Unknown project type" |

### 5.2 Individual types

| Project type | M | Q-legacy | Q-agg | SOA partial | Budget strategy | Key issues |
|--------------|---|----------|-------|-------------|-------------------|------------|
| **Individual - Livelihood (ILP)** | ✅ | ❌ | ✅ | `individual_livelihood` | `SingleSourceContributionStrategy` | Create partial **commented out**; **ILA-0001** prod invariant warnings; overview `amount_sanctioned` from `projects` table = 0 |
| **Individual - Access to Health (IAH)** | ✅ | ❌ | ✅ | `individual_health` | `SingleSourceContributionStrategy` | Create partial **commented out**; **IAH-0002** prod invariant warnings |
| **Individual - Ongoing Ed (IES)** | ✅ | ❌ | ✅ | `individual_ongoing_education` | `MultipleSourceContributionStrategy` | Create partial **commented out**; row-level calc OK, overview from projects only |
| **Individual - Initial Ed (IIES)** | ✅ | ❌ | ✅ | `individual_education` | `MultipleSourceContributionStrategy` | Create partial **commented out**; most complex contribution logic at row level |

**Business gap (documented, unresolved):** Individual types have **no legacy quarterly** reporting. Aggregated quarterly/half-yearly/annual can still be generated from monthly data if monthlies exist and are approved.

---

## 6. Issue Categories (Detailed)

### 6.1 Budget & Statements of Account

**Two-layer problem:**

1. **Row-level SOA (per budget line):** `BudgetCalculationService` + strategy pattern — generally correct per `Budget_Calculation_Analysis_By_Project_Type.md`. Contribution distribution for individual types works at row level.

2. **Project-level overview fields** (`amount_sanctioned_overview`, `amount_in_hand`, etc.): Still read from `projects.amount_sanctioned`, `projects.opening_balance`, etc. For individual and IGE types, type-specific budget tables are **not synced** to `projects` on save/approval.

**Root cause (documented in `Approvals_And_Reporting_Budget_Integration_Recommendations.md`):** Approval and reporting paths do not use `ProjectFundFieldsResolver` to populate canonical `projects` fund fields from type-specific tables.

**Recommended fix (not implemented):**
- Enable `ProjectFundFieldsResolver` + sync on type-specific save and before approval (`config/budget.php` flags `sync_to_projects_on_type_save`, `sync_to_projects_before_approval` default **false**).
- Run Phase 2 financial invariant repair (dry-run command planned in `Project_Reports_Phase_Wise_Implementation.md`).

**Other budget issues:**
- `amount_forwarded` hardcoded 0 in report create and account details (documented in create data-flow doc).
- Export path skips contribution calculation — export totals may differ from edit/show.
- IGE config uses `'particular' => 'name'`, `'amount' => 'total_amount'` with comment "may need verification".

### 6.2 UI / Blade inconsistencies

| Location | Create | Edit | Show | Issue |
|----------|--------|------|------|-------|
| Type-specific sections | Partial chain + **commented block** | Explicit chain in `edit.blade.php` | N/A | Create missing CCI, Edu-RUT, NPD, all Individual top sections |
| SOA routing | Explicit `@if` in `ReportAll` + wrong IGE string | Router via `partials.edit` | Router via `partials.view` | Three different routing approaches |
| Photos | **Commented out** | Present | Present | Create cannot add photos inline |
| DP alternate form | `developmentProject/reportform.blade.php` exists | — | — | Not linked from dashboards |

### 6.3 Workflow & authorization

**Monthly lifecycle statuses:** `draft` → `submitted_to_provincial` → `forwarded_to_coordinator` → `approved` (+ revert variants).

**M3 fix (implemented):** `DPReport::APPROVED_STATUSES` + `scopeApproved()` — General-as-coordinator/provincial approvals now included in aggregation (`M3_Phase2_DPReport_Approved_Scope_Alignment.md`).

**Remaining gaps:**
- Update request editable statuses ⊂ submit allowed statuses (see P1 #3).
- Create/store does not verify project is approved or user is in-charge (see P1 #4).
- V2A `Workflow_Centralization_Audit.md`: project approve/revert may lack `ProjectPermissionHelper` checks — affects whether projects reach approved state for reporting.

**Legacy quarterly:** No save-as-draft; different status model; no integration with `ReportStatusService`.

### 6.4 Society & relational mapping

From `Reports_Relational_Readiness_Audit.md`:

| Write path | Society source |
|------------|----------------|
| Monthly create (form display) | `$user->society_name` |
| Monthly create (persisted snapshot) | `$project->society_id` / `$project->society_name` |
| Aggregated quarterly | `optional($project->society)->name ?? $project->society_name` |
| Legacy quarterly | Request input |

**Planned (not done):** Add `society_id` FK to report tables; backfill from `projects.society_id`; align monthly write to project society (Phase 5B5A).

**Blocker:** `projects.society_id` must be populated before report snapshot migration.

### 6.5 Data model & error handling

- Mixed naming: `DP_Reports` (PascalCase table) vs `report_comments` (snake_case); string PK `report_id`.
- Widespread `firstOrFail()` in report controllers → 500 instead of 404 when related records missing.
- N+1 on `$project->reports` in dashboard controllers (V2 Phase 5 plan).
- Activity rows: legacy "month-only" placeholder rows; newer logic deletes empty rows on edit.

### 6.6 Aggregated reporting dependency chain

```
Approved project → Monthly DPReport (approved) → QuarterlyReportService → Aggregated Q/HY/Annual
```

**Failure modes:**
- Monthly create fails (P0) → no monthly data → aggregation empty/blocked.
- Monthly not approved → excluded from aggregation (correct, but user confusion).
- Before M3 fix, General-approved monthlies were excluded (now fixed).
- Financial wrong data in monthly → propagates to aggregated reports and provincial monitoring.

---

## 7. Production Log Evidence (sampled Mar 2026)

### 7.1 Report create failures (ERROR)

```
[2026-03-13 16:07:08] Failed to create report
→ Field 'society_id' doesn't have a default value (DP-0006-01)

[2026-03-11 21:51:54] Failed to create report
→ Field 'society_id' doesn't have a default value (DP-0009-01)
```

8 occurrences total, Mar 6–13. Same root cause: two-step insert without `society_id`.

### 7.2 Financial invariant warnings (WARNING)

```
Financial invariant violation: approved project must have amount_sanctioned > 0
→ IOGEP-0006, IAH-0002, ILA-0001

Financial invariant violation: opening_balance == overall_project_budget
→ IOGEP-0006, DP-0009
```

These fire during project show, report views, and dashboard budget resolution — SOA overview shows Rs. 0.00 or wrong balances even when row-level budgets are correct.

### 7.3 Project edit blockers (prevent reaching report context)

```
ProjectController@edit - Unknown project type {"project_type":"NEXT PHASE - DEVELOPMENT PROPOSAL"}
ProjectController@edit - CCI Statistics not found (CCI-0001, CCI-0002)
```

### 7.4 Working cases (INFO)

```
Report retrieved successfully → DP-0008-01 (society_id:5 snapshotted)
ReportController@handleUpdateAttachments → DP-0023-02 draft updates
```

Confirms workflow works when create succeeds and data is intact.

---

## 8. Documentation Alignment Matrix

| Claim (source doc) | Code reality | Severity |
|--------------------|--------------|----------|
| "12 monthly types complete" (V1 COMPREHENSIVE, TASKS_AND_STATUS) | Create partials commented out for 7 types; photos commented out | **High** |
| "Reports only for approved projects" (manuals) | No enforcement on create/store | **High** |
| "BudgetCalculationService unified" (V2 budget docs) | Alternate DP path + legacy quarterly bypass service | **Medium** |
| "Edit after revert" (workflow docs) | Update request rejects granular revert statuses | **High** |
| "Society snapshot on reports" (Wave 6A) | Two-step insert causes SQL failure | **Critical** |
| "M3 approved scope aligned" (M3 Phase 2 doc) | Verified: `APPROVED_STATUSES` in use | ✅ Aligned |
| "Edit SoA UI standardized" (Edit_Budget doc) | 6 edit partials updated | ✅ Aligned (UI only) |
| "Activity store when user filled" (Create docs) | Implemented | ✅ Aligned |
| "Phase 11 manual testing pending" (V1) | Still pending; explains untested regressions | **Medium** |
| "ProjectFundFieldsResolver fixes overview" (Approvals doc) | **Not implemented** (`BUDGET_RESOLVER_ENABLED` default false) | **High** |

---

## 9. Recommended Remediation (Phased)

### Phase A — Stop the bleeding (1–2 days)

1. **Fix report create:** Include `society_id`, `society_name`, `province_id` in initial `DPReport::create()` array (or wrap in DB transaction with single insert). Validate `$project->society_id` is non-null before create.
2. **Wrap legacy quarterly routes in `auth` + role middleware** (`routes/web.php` 545–604).
3. **Align `UpdateMonthlyReportRequest` editable statuses** with `ReportStatusService::submitToProvincial` allowed statuses.

### Phase B — Data & budget integrity (1 week)

4. **Deploy CCI StatisticsController fix** (use `first()` + empty model, not `firstOrFail()`).
5. **Add `NEXT PHASE - DEVELOPMENT PROPOSAL` to `config/budget.php`** or confirm intentional Development fallback.
6. **Fix IGE type string** in `ReportAll.blade.php:162` → use `ProjectType::INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL`.
7. **Enable ProjectFundFieldsResolver sync** (or run one-time repair for IOGEP-0006, IAH-0002, ILA-0001, DP-0009).
8. **Uncomment or deliberately remove** type-specific create partials in `ReportAll.blade.php` — align with edit/show.

### Phase C — Consistency & security (1–2 weeks)

9. **Add create/store gates:** project must be approved; user must be executor/applicant in-charge or owner.
10. **Unify DP budget path:** deprecate or align `MonthlyDevelopmentProjectController` with `BudgetCalculationService`.
11. **Society alignment:** monthly form display should use `$project->society` not `$user->society_name`.
12. **Uncomment photos on create** or document intentional deferral to edit-only upload.
13. **Approved projects list:** show projects with null FY or add prominent "All Financial Years" default hint.

### Phase D — Quality & docs (ongoing)

14. **Add report feature tests:** create (with society snapshot), edit authorization by status, SOA row calc per type.
15. **Replace `firstOrFail()` with graceful handling** in report controllers.
16. **Update V1 COMPREHENSIVE_REPORTS_REVIEW** to reflect actual create UI state.
17. **Run V1 Phase 11 manual test matrix** across all 12 types.
18. **Society Phase 5B5A:** add `society_id` to report tables after `projects.society_id` backfill.

---

## 10. Test Results (2026-06-13)

```bash
php artisan test --filter=FinancialInvariant
# PASS: 8 tests, 11 assertions (FinancialInvariantServiceTest)
```

**Interpretation:** Invariant **detection** logic is correct. Production warnings confirm **data** violates invariants — the service is working as designed, not failing silently.

**Missing:** Report CRUD tests, BudgetCalculationService integration tests, SOA JavaScript calculation tests, authorization tests for revert statuses.

---

## 11. Source Document Index

| Document | Path | Relevance |
|----------|------|-----------|
| Executor gaps analysis | `Documentations/Reports/Executor_Report_Writing_And_Budget_Gaps_Analysis.md` | Primary prior audit |
| V1 comprehensive review | `Documentations/V1/Reports/COMPREHENSIVE_REPORTS_REVIEW.md` | Feature inventory (optimistic) |
| V1 tasks/status | `Documentations/V1/Reports/TASKS_AND_STATUS.md` | Checklist; Phase 11 pending |
| V2 findings | `Documentations/V2/Reports/Project_Reports_Findings_And_Suggestions.md` | Prod log analysis, CCI 500 |
| V2 phase plan | `Documentations/V2/Reports/Project_Reports_Phase_Wise_Implementation.md` | 6-phase remediation |
| Budget integration | `Documentations/V1/Basic Info fund Mapping Issue/Approvals_And_Reporting_Budget_Integration_Recommendations.md` | Root cause for Rs. 0 overview |
| Society audit | `Documentations/V2/Societies/Mapping/Reports/Reports_Relational_Readiness_Audit.md` | Society source split |
| M3 approved scope | `Documentations/V2/FInalFix/M3/Status_Fix_Plan/M3_Phase2_DPReport_Approved_Scope_Alignment.md` | Implemented fix |
| Budget by type | `Documentations/REVIEW/project flow/Budget_Calculation_Analysis_By_Project_Type.md` | Row-level formulas |
| Reporting audit | `Documentations/REVIEW/project flow/Reporting_Audit_Report.md` | Per-type section audit |
| Workflow audit | `Documentations/V2A/Workflow_Centralization_Audit.md` | Approve/revert permissions |
| Edit SoA UI | `Documentations/V1/Reports/Edit_Budget_Section_Statements_Of_Account_Implementation.md` | Edit partial standardization |
| Create data flow | `Documentations/V1/Reports/Create/Report_Create_DP-0002-02_Data_Flow_And_Tables.md` | DP create pipeline |
| Activity fix | `Documentations/V1/Reports/Create/Activity_Store_Only_When_User_Filled_And_Ignore_Month_Proposal.md` | Empty activity row fix |

---

## 12. Key Code References

| Concern | File | Lines (approx) |
|---------|------|----------------|
| Report create / society bug | `app/Http/Controllers/Reports/Monthly/ReportController.php` | 395–425 |
| Budget service integration | Same controller | `getBudgetDataByProjectType()` ~103 |
| Editable statuses (narrow) | `app/Http/Requests/Reports/Monthly/UpdateMonthlyReportRequest.php` | 32–36 |
| Submit allowed statuses (wider) | `app/Services/ReportStatusService.php` | 21–31 |
| Commented create partials | `resources/views/reports/monthly/ReportAll.blade.php` | 90–112, 173+ |
| Wrong IGE type string | Same file | 162 |
| SOA type router | `resources/views/reports/monthly/partials/statements_of_account.blade.php` | 4–16 |
| Budget config | `config/budget.php` | 50–200 |
| Project types | `app/Constants/ProjectType.php` | 7–21 |
| Unauthenticated quarterly routes | `routes/web.php` | 545–604 |
| Approved statuses (M3) | `app/Models/Reports/Monthly/DPReport.php` | `APPROVED_STATUSES`, `scopeApproved()` |

---

## 13. Conclusion

The reporting system has substantial **implemented functionality** (unified monthly model, aggregated AI reports, budget strategies, workflow service, provincial monitoring) but suffers from **critical production failures**, **documentation overstatement of completeness**, and **split-brain architecture** (three reporting stacks, two budget paths for DP, society source inconsistency).

The most urgent fix is the **`society_id` insert failure** blocking executors from creating any monthly report on production. Immediately after that, **authorization alignment**, **legacy route security**, and **project fund field sync** will address the majority of user-visible "cannot write report" and "wrong amounts" complaints.

This document should be treated as the **single consolidated reference** for reporting gaps until individual phase logs are updated with remediation status.
