# M3 — Status Semantic Correction Strategy

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Task:** Status Semantic Correction Strategy Planning  
**Mode:** STRICTLY READ-ONLY (No Code Changes)  
**Generated:** 2025-02-15

---

## SECTION 1 — Negative Filtering Inventory

Searched for: `where('status', '!=',`, `where('status', '<>',`, `!= ProjectStatus::`, `!= DPReport::`, `!= STATUS_`, `whereNot('status',` (no app matches for whereNot).

### Application code (source of truth for fixes)

| File path | Line | Query | Context | Intended meaning | Severity |
|-----------|------|-------|---------|-------------------|----------|
| `app/Http/Controllers/Projects/ProjectController.php` | 299 | `->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` | Executor/applicant project index — exclude approved projects from list | **A) Not approved** (show editable/pending only) | **HIGH** — Workflow: excludes only one approved status; projects approved by General (as coordinator/provincial) still appear in executor list |
| `app/Helpers/ProjectPermissionHelper.php` | 159 | `$query->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` | `getProjectsForUserQuery` — exclude approved for executor/applicant | **A) Not approved** | **HIGH** — Same drift; should use `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)` |
| `app/Http/Controllers/CoordinatorController.php` | 2502 | `$provincials->where('status', '!=', 'active')` | Team stats — **User** status (active/inactive), not Project status | **C) Other** — User model | **LOW** — UI filtering only; different domain |
| `app/Http/Controllers/ExecutorController.php` | 622 | `->where('status', '!=', DPReport::STATUS_DRAFT)` | Report query — exclude draft when checking last month report | **C) Other** — DPReport draft filter | **LOW** — Report-specific; correct intent |
| `app/Http/Controllers/ExecutorController.php` | 652 | `->where('status', '!=', DPReport::STATUS_DRAFT)` | Report query — exclude draft for current month report | **C) Other** — DPReport draft filter | **LOW** — Report-specific; correct intent |

### Documentation / review (reference only; not application code)

| File path | Line | Note |
|-----------|------|------|
| `Documentations/REVIEW/5th Review/DASHBOARD/EXECUTOR APPLICANT/Dashboard_Enhancement_Suggestions.md` | 353 | `->where('status', '!=', 'draft')` — suggestion doc |
| `Documentations/REVIEW/5th Review/Report Views/expenses tracking/Approved_Unapproved_Expenses_Tracking_Analysis.md` | 128 | `->where('status', '!=', DPReport::STATUS_APPROVED_BY_COORDINATOR)` — analysis |
| `Documentations/REVIEW/5th Review/Applicant user Access/Implementation_Completion_Summary.md` | 40, 48 | Example snippets |
| `Documentations/REVIEW/5th Review/Applicant user Access/Applicant_Access_Implementation_Plan.md` | 107, 128 | Plan references |

**Summary — Negative filtering (app only):**

- **HIGH (2):** ProjectController.php:299, ProjectPermissionHelper.php:159 — both “not approved” intent but exclude only `APPROVED_BY_COORDINATOR`; causes workflow corruption (executor sees General-approved projects).
- **LOW (3):** CoordinatorController (User status), ExecutorController (DPReport draft) — correct or different domain.

---

## SECTION 2 — Approved Query Inventory

Searched for: `where('status', ProjectStatus::`, `whereIn('status', [`, `DPReport::where('status'`, `DPReport::whereIn('status'`.

### Project — single-status approved (should use APPROVED_STATUSES or ->approved())

*None* in app: Project-approved usage is via `->approved()` (scope) or `whereIn('status', ProjectStatus::APPROVED_STATUSES)` in the places we need. Single-status references are in **arrays** (e.g. BudgetReconciliationController, ExportController) or **workflow/role logic** (ProjectStatusService, ProjectPhaseService), not “all approved projects” queries.

### Project — correct approved usage (canonical)

| File | Line(s) | Query / usage | Classification |
|------|---------|----------------|----------------|
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | 56–59, 93–96 | `whereIn('status', [ProjectStatus::APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])` | Financial aggregation |
| `app/Http/Controllers/ExecutorController.php` | 35–38 | `$projectsQuery->whereIn('status', [ProjectStatus::APPROVED_BY_COORDINATOR, ...all three])` | Dashboard |
| `app/Http/Controllers/ProvincialController.php` | 1705, 1735, 1995, 2071 | `whereIn('status', ProjectStatus::APPROVED_STATUSES)` | Reporting / dashboard |
| `app/Http/Controllers/GeneralController.php` | 2076, 2082, 3285, 3381, 3587, 3616 | `->approved()` | Workflow / dashboard |
| `app/Http/Controllers/ProvincialController.php` | 96, 131, 1550, 1570, 1580, 1812, 2184 | `->approved()` | Workflow / dashboard |
| `app/Console/Commands/TestApplicantAccess.php` | 251, 259, 265 | `->approved()` | Validation / test |
| `app/Http/Controllers/CoordinatorController.php` | 1574 | `$query->approved()` | Workflow |
| `app/Services/ProjectQueryService.php` | 115–118 | `getApprovedProjectsForUser` uses array of all three | Validation / query service |

### DPReport — single-status approved (missing General-as-Provincial)

| File | Line(s) | Query | Classification |
|------|---------|-------|----------------|
| `app/Http/Controllers/CoordinatorController.php` | 573, 2055, 2063, 2085, 2093, 2122, 2130, 2161, 2169, 2203, 2228, 2248, 2931, 2957, 2968, 2994 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` or `STATUS_FORWARDED_TO_COORDINATOR` | Aggregation / dashboard / reporting |
| `app/Http/Controllers/GeneralController.php` | 3515, 3523 | `DPReport::where('status', $reportStatusApproved)` — passed as single status from caller (3648, 3657) | Financial aggregation |
| `app/Http/Controllers/GeneralController.php` | 3786, 3795 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` | Reporting layer |
| `app/Http/Controllers/ExecutorController.php` | 293, 308, 851, 961, 1008 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` | Dashboard / aggregation |
| `app/Http/Controllers/Admin/AdminReadOnlyController.php` | 62 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` | Reporting |
| `app/Http/Controllers/ProvincialController.php` | 2027 | `$teamReports->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` (approval rate calc) | Dashboard |
| `app/Console/Commands/TestApplicantAccess.php` | 318 | `->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` | Validation |
| `tests/Feature/Budget/CoordinatorAggregationParityTest.php` | 178 | `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` | Test |

### DPReport — whereIn with two approved statuses (missing third)

| File | Line(s) | Query | Classification |
|------|---------|-------|----------------|
| `app/Http/Controllers/GeneralController.php` | 1751–1754, 1757–1758, 1761–1762, 1922 | `$approvedStatuses = [STATUS_APPROVED_BY_COORDINATOR, STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR]`; used in `whereIn('status', $approvedStatuses)` | Reporting layer — **missing** `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL` |

### DPReport — whereIn correct (pending / reverted / multi-status)

| File | Line(s) | Query | Classification |
|------|---------|-------|----------------|
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | 56–59, 93–96 | Project status: all three approved (see above) | Financial aggregation |
| `app/Http/Controllers/ExecutorController.php` | 203–211, 219–227, 252–260, 267–276, 441–448, 535–544 | `whereIn('status', [...])` for pending/reverted/draft | Dashboard / filtering |
| `app/Http/Controllers/ProvincialController.php` | 1659, 1689, 1752–1755, 1775–1778, 1853, 1877, 2021–2025 | `whereIn('status', [...])` for pending/submitted | Dashboard / workflow |
| `app/Http/Controllers/GeneralController.php` | 1678, 1922 | `DPReport::whereIn('status', $pendingStatuses)` / `$approvedStatuses` (latter incomplete as above) | Reporting |

**Summary — Approved queries:**

- **Financial aggregation:** BudgetReconciliationController (canonical for Project); GeneralController `$calculateBudgetData` uses **single** report status (3648, 3657) → aggregation drift if reports approved by General-as-Provincial are excluded.
- **Workflow transition:** CoordinatorController pending reports (STATUS_FORWARDED_TO_COORDINATOR) — correct; Project uses `->approved()` where needed.
- **Reporting layer:** GeneralController approved reports list (1751–1754) missing third status; multiple DPReport single-status approved queries across controllers.
- **Dashboard:** ExecutorController, CoordinatorController, ProvincialController use single-status DPReport approved in many places → MEDIUM aggregation drift.
- **Validation:** TestApplicantAccess uses single-status DPReport approved.

---

## SECTION 3 — Cross-Model Approval Definitions

### ProjectStatus (`app/Constants/ProjectStatus.php`)

- **Approved group (APPROVED_STATUSES):**  
  `APPROVED_BY_COORDINATOR`, `APPROVED_BY_GENERAL_AS_COORDINATOR`, `APPROVED_BY_GENERAL_AS_PROVINCIAL`
- **Helpers:** `isApproved(string $status)`, `getEditableStatuses()`, `getSubmittableStatuses()`.

### DPReport (`app/Models/Reports/Monthly/DPReport.php`)

- **Approved (in `isApproved()`):**  
  `STATUS_APPROVED_BY_COORDINATOR`, `STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR`, `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL` (lines 281–284).
- **No public APPROVED_STATUSES array** — only inside `isApproved()`.
- **Semantic match:** Same three string values as ProjectStatus.

### AnnualReport (`app/Models/Reports/Annual/AnnualReport.php`)

- **Constants:** Same names as DPReport (STATUS_DRAFT, STATUS_APPROVED_BY_COORDINATOR, etc.) — lines 19–40.
- **No `isApproved()` method** in model; AnnualReportService uses single-status `STATUS_APPROVED_BY_COORDINATOR` (and HalfYearlyReport/QuarterlyReport single-status) at lines 170, 183, 198.

### QuarterlyReport (`app/Models/Reports/Quarterly/QuarterlyReport.php`)

- **Constants:** Same as DPReport (lines 19–40).
- **No central `isApproved()`**; QuarterlyReportService uses single-status at line 151.

### HalfYearlyReport (`app/Models/Reports/HalfYearly/HalfYearlyReport.php`)

- **Constants:** Same as DPReport (lines 19–40).
- **No central `isApproved()`**; HalfYearlyReportService uses single-status at lines 170, 186.

### Mismatches

| Area | Mismatch |
|------|----------|
| **Definition** | ProjectStatus has public `APPROVED_STATUSES`; DPReport has no equivalent constant array (only inside `isApproved()`). Annual/Quarterly/HalfYearly duplicate constants but no shared “approved list”. |
| **Semantic meaning** | No mismatch: all use the same three approved string values. |
| **Usage** | Many queries use single `STATUS_APPROVED_BY_COORDINATOR` instead of “all approved” → drift. |
| **Report services** | AnnualReportService, QuarterlyReportService, HalfYearlyReportService filter by single approved status → aggregation/reporting drift. |

---

## SECTION 4 — Risk Map

| Category | Files affected | Risk level | Impact | Recommended fix strategy |
|----------|----------------|------------|--------|--------------------------|
| **Negative filtering bug** | `app/Http/Controllers/Projects/ProjectController.php` (299), `app/Helpers/ProjectPermissionHelper.php` (159) | **High** | Executor/applicant see General-approved projects as editable; workflow corruption | Replace with `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)` or use `->notApproved()` if scope is applicable |
| **Single-status approved (Project)** | N/A in app for “all approved” queries | — | Project side already uses `->approved()` or full list where it matters | No change for Project “approved” list; keep ExportController/BudgetReconciliationController arrays as-is or align to constant |
| **Single-status DPReport approved** | CoordinatorController (many), GeneralController (3515, 3523, 3786, 3795), ExecutorController (293, 308, 851, 961, 1008), AdminReadOnlyController (62), ProvincialController (2027), TestApplicantAccess (318), test (178) | **Medium** | Aggregation drift: reports approved by General-as-Provincial excluded from counts/expenses | Use `whereIn('status', [all three DPReport approved])` or DPReport scope if added; align with DPReport::isApproved() |
| **Report layer drift (whereIn missing third)** | GeneralController (1751–1754, 1757–1758, 1761–1762, 1922) | **Medium** | Approved reports list and filters miss General-as-Provincial reports | Add `DPReport::STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL` to `$approvedStatuses` |
| **GeneralController budget calc single status** | GeneralController (3648, 3657) | **Medium** | Budget/expense aggregation uses one approved status per branch | Pass all three approved statuses (or use whereIn) in `$calculateBudgetData` for approved branch |
| **Report services single-status** | AnnualReportService (170, 183, 198), QuarterlyReportService (151), HalfYearlyReportService (170, 186) | **Medium** | Annual/quarterly/half-yearly aggregation may exclude some approved reports | Use whereIn with all three approved statuses per model |
| **Cross-model duplication** | DPReport, AnnualReport, QuarterlyReport, HalfYearlyReport each define same status constants | **Low** | Maintenance burden; no semantic mismatch | Optional: centralize approved list in one place (e.g. ProjectStatus or shared report constant) and reference in models |
| **Minor UI / other** | CoordinatorController (2502) User status; ExecutorController (622, 652) DPReport draft | **Low** | None for approval semantics | No change |

---

## SECTION 5 — Step-Wise Execution Plan

### Phase 1 — Critical workflow correction

- **Goal:** Fix “not approved” semantics so executor/applicant do not see any approved project as editable.
- **Exact change:** Replace `where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR)` with `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)` (or equivalent scope).
- **Files:**  
  - `app/Http/Controllers/Projects/ProjectController.php` (line 299)  
  - `app/Helpers/ProjectPermissionHelper.php` (line 159)
- **Regression risk:** Low if tests cover executor/applicant project list and permission helper.
- **Testing:** Integration test: as executor/applicant, create project → approve by General (as provincial/coordinator) → ensure project no longer appears in executor “my projects” list. Unit test for `getProjectsForUserQuery` with approved statuses.
- **Rollback:** Revert two files; no schema or data change.

---

### Phase 2 — Financial aggregation alignment

- **Goal:** All financial aggregation (budget, approved expenses) uses “all approved” for both Project and DPReport (and other report types where applicable).
- **Exact change:**  
  - GeneralController: (1) Add `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL` to `$approvedStatuses` (1751–1754) and any `whereIn('status', $approvedStatuses)` using it; (2) In budget calculation (3648, 3657), pass or use all three DPReport approved statuses instead of single `STATUS_APPROVED_BY_COORDINATOR`.  
  - Controllers that aggregate by approved reports: replace single `DPReport::where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)` with `whereIn('status', [all three])` (or DPReport scope) in: CoordinatorController, GeneralController, ExecutorController, AdminReadOnlyController, ProvincialController (2027).  
  - Report services: AnnualReportService, QuarterlyReportService, HalfYearlyReportService — use whereIn with all three approved statuses for the respective model.
- **Files:** GeneralController (1751–1754, 3515–3523, 3648, 3657, 3786, 3795), CoordinatorController (listed in Section 2), ExecutorController (293, 308, 851, 961, 1008), AdminReadOnlyController (62), ProvincialController (2027), AnnualReportService, QuarterlyReportService, HalfYearlyReportService.
- **Regression risk:** Medium — totals and approval rates may change; must align with resolver and existing BudgetReconciliationController behavior.
- **Testing:** Compare approved-expense and budget totals before/after (e.g. BudgetReconciliationController index, General dashboard, Executor dashboard); run existing budget/resolver tests.
- **Rollback:** Revert listed files; no schema or status value change.

---

### Phase 3 — Reporting layer alignment

- **Goal:** All reporting and dashboard “approved” filters use the same three approved statuses for reports.
- **Exact change:** Ensure every report list and filter that means “approved” uses `whereIn('status', [all three])` (or equivalent). Address any remaining single-status approved in CoordinatorController, GeneralController, ExecutorController, AdminReadOnlyController, ProvincialController, and TestApplicantAccess (318).
- **Files:** Same as Phase 2 for report queries; plus `app/Console/Commands/TestApplicantAccess.php` (318), and optionally `tests/Feature/Budget/CoordinatorAggregationParityTest.php` (178) to reflect new semantics.
- **Regression risk:** Low–medium; mainly counts and list contents.
- **Testing:** Manual or automated checks for approved report lists and counts (coordinator, general, executor, provincial, admin read-only).
- **Rollback:** Revert changes; no schema or status value change.

---

### Phase 4 — Optional status normalization refactor

- **Goal:** Reduce duplication and keep one source of truth for “approved” statuses for reports.
- **Exact change:** (Optional) Add `DPReport::APPROVED_STATUSES` (and optionally same for Annual/Quarterly/HalfYearly or a shared trait/constant) and use it in queries and in `isApproved()`. Do not change status values or schema.
- **Files:** DPReport, optionally AnnualReport, QuarterlyReport, HalfYearlyReport; then replace inline arrays in controllers/services with the constant.
- **Regression risk:** Low if constant matches current `isApproved()` list.
- **Testing:** Existing approval and aggregation tests.
- **Rollback:** Revert to inline arrays.

---

## SECTION 6 — Guardrails for Implementation

- **No financial formula change:** Do not alter formulas for budget, remaining balance, utilization, or approval rate; only change which records are included in “approved” sets.
- **No status value change:** Do not rename or change stored status strings (e.g. `approved_by_coordinator` remains as-is).
- **No schema change:** No migrations for status columns.
- **Documentation:** Create one MD file per phase under `Documentations/V2/FinalFix/M3/Status_Fix_Plan/` (e.g. `M3_Phase1_Workflow_Correction.md`) describing the exact edits and verification steps.
- **Testing:** Run integration tests (and relevant feature tests) after each phase; ensure budget reconciliation and executor/coordinator/general dashboards behave as expected.

---

*End of strategy document.*

**M3 Status Semantic Fix Strategy Drafted — No Code Changes Made**
