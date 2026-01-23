# Phase-Wise Implementation Plan — Provincial Monthly Report Monitoring Guide

**Source:** `Provincial_Monthly_Report_Monitoring_Guide.md`  
**Purpose:** Break the Guide’s implementation into ordered phases with concrete tasks, files, and section references.  
**Version:** 1.0

---

## Overview

| Phase | Name | Guide sections | Est. effort |
|-------|------|----------------|-------------|
| **1** | Foundation: data loading and service | §2.3, §4.1 | 1–2 h |
| **2** | Activity monitoring | §3, §4 | 3–4 h |
| **3** | Budget monitoring | §5, §6, §7 | 2–3 h |
| **4** | Project-type-specific: LDP, IGE, RST, CIC | §9.1–9.4, §9.8, §9.9 | 4–5 h |
| **5** | Project-type-specific: Individual, Development/CCI/Rural-Urban-Tribal/NEXT PHASE, Beneficiary | §9.5–9.8, §9.9 | 3–4 h |
| **6** | Integration, visibility, and testing | §4.2, §7.2, §8, §11 | 1–2 h |

---

## Phase 1 — Foundation: Data Loading and Service

**Guide: §2.3, §4.1**

### 1.1 Goals

- Load **project objectives, activities, timeframes** in `show()` so activity checks can run.
- Derive **report month** (1–12) from `report_month_year`.
- Create **ReportMonitoringService** and wire it into the controller.

### 1.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 1.1 | Create `App\Services\ReportMonitoringService` | `app/Services/ReportMonitoringService.php` | Empty class or with a single placeholder method; constructor can receive no deps or only ones needed later. |
| 1.2 | In `ReportController::show()`: ensure `$project` is loaded; add `$project->load(['objectives.activities.timeframes'])` (or add to initial `Project::with(...)`) | `app/Http/Controllers/Reports/Monthly/ReportController.php` | §2.3: *"objectives, activities, timeframes are not loaded in show()"*. |
| 1.3 | In `show()`: `$reportMonth = (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');` | `ReportController` | For use in Phase 2. |
| 1.4 | (Optional) If `ProvincialController::showReport` has its own query, ensure it also receives a project with `objectives.activities.timeframes` when it delegates to or replicates `ReportController::show` logic | `app/Http/Controllers/ProvincialController.php` | Only if Provincial path does not use `ReportController::show()`. |

### 1.3 Dependencies

- **Models:** `Project`, `ProjectObjective`, `ProjectActivity`, `ProjectTimeframe` (see §10.1).  
- **Report:** `DPReport` with `report_month_year`.

### 1.4 Output

- `ReportMonitoringService` exists.
- `show()` loads `project.objectives.activities.timeframes` and sets `$reportMonth` (can be passed to view or only used in service calls).

---

## Phase 2 — Activity Monitoring

**Guide: §3, §4**

### 2.1 Goals

- Implement the three activity checks: **scheduled but not reported**, **reported but not scheduled**, **ad‑hoc**.
- Add **objectives_activity_monitoring** partial and include it in the show view.

### 2.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 2.1 | `getActivitiesScheduledButNotReported(DPReport $report): array` | `ReportMonitoringService` | §3.1. Use `report->project` (with `objectives.activities.timeframes`). For each `ProjectActivity` with a `ProjectTimeframe` where `month = $reportMonth` and `is_active = 1`, check there is no `DPActivity` with `project_activity_id = ProjectActivity.activity_id`. Return list of `[objective, activity, activity_id]`. |
| 2.2 | `getActivitiesReportedButNotScheduled(DPReport $report): array` | `ReportMonitoringService` | §3.2. For each `DPActivity` with non‑empty `project_activity_id`, check `ProjectTimeframe` for that activity and report month; if none with `is_active=1`, add to list. Return `[objective, activity, reported_month, planned_months]`. |
| 2.3 | `getAdhocActivities(DPReport $report): array` | `ReportMonitoringService` | §3.3. `DPActivity` where `project_activity_id` is null or empty. Return `[activity, month, objective]`. |
| 2.4 | In `show()`: instantiate (or resolve) `ReportMonitoringService`; call the three methods; pass `activitiesScheduledNotReported`, `activitiesReportedNotScheduled`, `adhocActivities` to the view | `ReportController` | §4.1. |
| 2.5 | Create `partials/view/objectives_activity_monitoring.blade.php` | `resources/views/reports/monthly/partials/view/objectives_activity_monitoring.blade.php` | §4.2. Three tables/lists: (1) Scheduled but NOT reported — columns: Objective, Activity, Notes (“Check ‘What did not happen’ / ‘Why not’ for this objective”); (2) NOT scheduled but reported — Objective, Activity, Reported month, Planned months, Note; (3) Ad‑hoc — Activity, Month, Objective. Render only when at least one array is non‑empty. |
| 2.6 | In `show.blade.php`, `@include` `objectives_activity_monitoring` after the Objectives section | `resources/views/reports/monthly/show.blade.php` | §4.2, §11. Pass the three arrays. |
| 2.7 | (Optional) Show this partial only when `in_array($report->status, [STATUS_SUBMITTED_TO_PROVINCIAL, STATUS_FORWARDED_TO_COORDINATOR])` | `show.blade.php` or inside the partial | §4.2. |

### 2.3 Logic Summary (from Guide)

- **Report month:** `(int) \Carbon\Carbon::parse($report->report_month_year)->format('n')`.
- **Scheduled for report month:** `ProjectTimeframe` with `activity_id`, `month = reportMonth`, `is_active = 1`.
- **Reported for a project activity:** `DPActivity.project_activity_id = ProjectActivity.activity_id`.
- **Ad‑hoc:** `DPActivity.project_activity_id` empty or null.

### 2.4 Dependencies

- Phase 1 done (project objectives/activities/timeframes loaded; `ReportMonitoringService` exists).

### 2.5 Output

- Three service methods implemented and used in `show()`.
- `objectives_activity_monitoring` partial created and included; three lists displayed when data exists.

---

## Phase 3 — Budget Monitoring

**Guide: §5, §6, §7**

### 3.1 Goals

- Implement **per‑row overspend**, **negative balance**, and **overall utilisation** (and optional **expenses vs report period**).
- Add **budget_monitoring** partial and include it in the show view.

### 3.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 3.1 | `getBudgetOverspendRows(DPReport $report): array` | `ReportMonitoringService` | §6.1. For each `DPAccountDetail` with `is_budget_row = 1`: if `total_expenses > amount_sanctioned` or `total_expenses > total_amount`, add `[particulars, amount_sanctioned, total_expenses, excess]`. Confirm which field is the cap in your SoA logic. |
| 3.2 | `getNegativeBalanceRows(DPReport $report): array` | `ReportMonitoringService` | §6.2. For each `DPAccountDetail` with `balance_amount < 0`: `[particulars, balance_amount]`. Optionally detect `sum(balance_amount) < 0` for an “overall negative” flag. |
| 3.3 | `getBudgetUtilisationSummary(DPReport $report): array` | `ReportMonitoringService` | §6.3. Return e.g. `[total_sanctioned, total_expenses, utilisation_percent, alerts]`. `alerts`: e.g. `['high_utilization']` if >90%, `['negative_balance']` if any negative, `['overspend_row']` if any overspend. Use `report->amount_sanctioned_overview` or `sum(DPAccountDetail.amount_sanctioned)` for budget rows as per your logic. |
| 3.4 | (Optional) `getBudgetExpensesVsPeriodAlerts(DPReport $report): array` or fold into `getBudgetUtilisationSummary` | `ReportMonitoringService` | §6.4. If `sum(expenses_this_month) >> sum(expenses_last_month)` (e.g. >2–3×) and large in absolute terms → add an alert. |
| 3.5 | In `show()`: call the three (or four) methods; pass `budgetOverspendRows`, `budgetNegativeBalanceRows`, `budgetUtilisation` to the view | `ReportController` | §7.1. |
| 3.6 | Create `partials/view/budget_monitoring.blade.php` | `resources/views/reports/monthly/partials/view/budget_monitoring.blade.php` | §7.2, §11. Tables/lists for overspend and negative balance; utilisation summary; one‑line messages from `alerts` (e.g. “Utilisation above 90%”, “Negative balance on one or more heads”, “Overspend on one or more budget heads”). |
| 3.7 | In `show.blade.php`, `@include` `budget_monitoring` after Statements of Account (or inside the SoA card, as preferred) | `show.blade.php` | §11. Pass the three (or four) vars. |

### 3.3 Data

- **DPAccountDetail:** `particulars`, `amount_sanctioned`, `total_amount`, `total_expenses`, `balance_amount`, `is_budget_row` (§5.2).  
- **Report:** `amount_sanctioned_overview` if used as total sanctioned (§6.3).

### 3.4 Dependencies

- `DPReport` with `accountDetails` loaded (usual `show()` already does this).

### 3.5 Output

- Three (or four) budget methods in `ReportMonitoringService`; controller passes results to the view.
- `budget_monitoring` partial created and included; overspend, negative balance, and utilisation alerts shown.

---

## Phase 4 — Project-Type-Specific: LDP, IGE, RST, CIC

**Guide: §9.1–9.4, §9.8, §9.9**

### 4.1 Goals

- Implement checks for **LDP** (annexure), **IGE** (age profile), **RST** (trainees), **CIC** (inmates).
- Load type‑specific report relations in `show()` when `project_type` matches.
- Add **type_specific_monitoring** partial with blocks for these four types.

### 4.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 4.1 | In `show()`: when `project_type` = LDP, ensure `$report->load('annexures')` or `annexures` in `DPReport::with()` | `ReportController` | §9.8. `DPReport::annexures` → `QRDLAnnexure`. |
| 4.2 | In `show()`: when `project_type` = IGE, ensure `$report->load('rqis_age_profile')` or equivalent | `ReportController` | §9.8. `DPReport::rqis_age_profile` → `RQISAgeProfile`. |
| 4.3 | In `show()`: when `project_type` = RST, ensure `$report->load('rqst_trainee_profile')`; build `report->education` if not already set | `ReportController` | §9.8. `education` is built from `rqst_trainee_profile` in current `show()`. |
| 4.4 | In `show()`: when `project_type` = CIC, ensure `$report->load('rqwd_inmate_profile')` | `ReportController` | §9.8. `DPReport::rqwd_inmate_profile` → `RQWDInmatesProfile`. |
| 4.5 | `getLdpAnnexureChecks(DPReport $report): array` | `ReportMonitoringService` | §9.1. Flags: annexure count=0; `dla_support_date` outside `account_period_start/end` (or report month); empty `dla_impact` or `dla_amount_sanctioned` 0/null; (optional) sum of `dla_amount_sanctioned` vs LDP budget head; (optional) annexure count vs `ProjectLDPTargetGroup`. Return e.g. `['alerts' => [...], 'meta' => [...]]`. |
| 4.6 | `getIgeAgeProfileChecks(DPReport $report): array` | `ReportMonitoringService` | §9.2. Flags: Grand Total missing (`age_group='All Categories'`, `education='Grand Total'`); missing age group (all four); Grand Total `present_academic_year` ≠ `report->total_beneficiaries`; (optional) sub‑totals. |
| 4.7 | `getRstTraineeChecks(DPReport $report): array` | `ReportMonitoringService` | §9.3. Flags: trainee total 0 when project expects trainees; total ≠ sum of categories; total ≠ `total_beneficiaries`; (optional) all categories 0 but total>0. Use `report->education`. |
| 4.8 | `getCicInmateChecks(DPReport $report): array` | `ReportMonitoringService` | §9.4. Flags: Grand Total missing; missing age category (all four); sub‑total mismatch per category; Grand Total ≠ `total_beneficiaries`. |
| 4.9 | In `show()`: by `project_type`, call the four methods; merge or structure results into `$typeSpecificChecks` (e.g. `['ldp' => ..., 'ige' => ..., 'rst' => ..., 'cic' => ...]`) and pass to the view | `ReportController` | §9.8. |
| 4.10 | Create `partials/view/type_specific_monitoring.blade.php` | `resources/views/reports/monthly/partials/view/type_specific_monitoring.blade.php` | §9.8, §11. For LDP, IGE, RST, CIC: render a block only when the corresponding `typeSpecificChecks` key exists and has flags. Show bullets or a small table per check. |
| 4.11 | In `show.blade.php`, `@include` `type_specific_monitoring` after the type‑specific sections (LDP/IGE/RST/CIC) or after Budget | `show.blade.php` | §11. Pass `typeSpecificChecks`. |

### 4.3 Models and Relations (from §9.9, §10.1)

| Type | Report data | Project (optional for Phase 4) |
|------|-------------|---------------------------------|
| LDP | `QRDLAnnexure`, `report->annexures` | `ProjectLDPTargetGroup` (optional) |
| IGE | `RQISAgeProfile`, `report->rqis_age_profile` | `ProjectIGEInstitutionInfo`, etc. (optional in Phase 4) |
| RST | `RQSTTraineeProfile`, `report->education` | — |
| CIC | `RQWDInmatesProfile`, `report->rqwd_inmate_profile` | — |

### 4.4 Dependencies

- `DPReport` relations: `annexures`, `rqis_age_profile`, `rqst_trainee_profile`, `rqwd_inmate_profile`; `report->education` for RST.

### 4.5 Output

- Four type‑specific methods in `ReportMonitoringService`; controller loads the relations and passes `typeSpecificChecks` (LDP, IGE, RST, CIC) to the view.
- `type_specific_monitoring` partial with LDP, IGE, RST, CIC blocks.

---

## Phase 5 — Project-Type-Specific: Individual, Development/CCI/Rural-Urban-Tribal/NEXT PHASE, Beneficiary

**Guide: §9.5–9.8, §9.9**

### 5.1 Goals

- Implement **Individual** (ILP, IAH, IES, IIES) budget and beneficiary checks.
- Implement **Development, CCI, Rural-Urban-Tribal, NEXT PHASE** checks (phase consistency, `total_beneficiaries` vs project, beneficiary trend).
- Implement **Beneficiary consistency** for all types.
- Extend **type_specific_monitoring** for these groups.

### 5.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 5.1 | In `show()`: for Individual types, ensure `$project` has budget relations: ILP `ilpBudget`, IAH `iahBudgetDetails`, IIES `iiesExpenses.expenseDetails`, IES `iesExpenses.expenseDetails` (or equivalent from `getBudgetsForReport`) | `ReportController` | §9.5. `BudgetCalculationService::getBudgetsForReport($project)` and `config/budget.php` define structure. |
| 5.2 | In `show()`: for Development, CCI, Rural-Urban-Tribal, NEXT PHASE: ensure `$project` has `budgets`, `target_beneficiaries`; for beneficiary trend, load `$project->reports` (or previous reports) if needed | `ReportController` | §9.6. |
| 5.3 | `getIndividualBudgetChecks(DPReport $report, Project $project): array` | `ReportMonitoringService` | §9.5. Flags: `total_beneficiaries` ≠ 1 for single‑beneficiary individual; budget rows (particulars) vs `getBudgetsForReport`; `amount_sanctioned` vs contribution logic (ILP/IAH/IIES/IES); duplicate `particulars` in `DPAccountDetail`. |
| 5.4 | `getDevelopmentPhaseChecks(DPReport $report, Project $project): array` or fold into a “common” method | `ReportMonitoringService` | §9.6. Flags: phase consistency (budget rows vs `current_phase` / phase from project); `total_beneficiaries` vs `Project.target_beneficiaries`; (optional) beneficiary trend vs previous reports. Name can be `getDevelopmentAndSimilarChecks` to cover CCI, Rural-Urban-Tribal, NEXT PHASE. |
| 5.5 | `getBeneficiaryConsistencyChecks(DPReport $report, Project $project): array` | `ReportMonitoringService` | §9.7, §9.8. Flags: `report->total_beneficiaries` vs `Project.target_beneficiaries`; type‑specific total (IGE Grand Total, RST total, CIC Grand Total, LDP annexure count) vs `total_beneficiaries` when available. |
| 5.6 | In `show()`: when `project_type` is ILP, IAH, IIES, IES: call `getIndividualBudgetChecks` and `getBeneficiaryConsistencyChecks`; when Development, CCI, Rural-Urban-Tribal, NEXT PHASE: call `getDevelopmentPhaseChecks` (or the common method) and `getBeneficiaryConsistencyChecks`; when LDP, IGE, RST, CIC: also call `getBeneficiaryConsistencyChecks` and merge into `typeSpecificChecks` | `ReportController` | §9.8. |
| 5.7 | Extend `type_specific_monitoring.blade.php` with blocks for: Individual (ILP/IAH/IIES/IES), Development/CCI/Rural-Urban-Tribal/NEXT PHASE, and a “Beneficiary consistency” block that can apply to all types | `type_specific_monitoring.blade.php` | §9.8. Render when the corresponding keys in `typeSpecificChecks` exist and have flags. |

### 5.3 Project Types and Methods

| Project type | Methods to call |
|--------------|-----------------|
| ILP, IAH, IIES, IES | `getIndividualBudgetChecks`, `getBeneficiaryConsistencyChecks` |
| Development, CCI, Rural-Urban-Tribal, NEXT PHASE | `getDevelopmentPhaseChecks` (or similar), `getBeneficiaryConsistencyChecks` |
| LDP, IGE, RST, CIC | (Phase 4 methods) + `getBeneficiaryConsistencyChecks` (merge beneficiary flags) |

### 5.4 Dependencies

- `BudgetCalculationService::getBudgetsForReport($project)`, `config/budget.php` (§10.5).  
- Project models: `ProjectILPBudget`, `ProjectIAHBudgetDetails`, `ProjectIIESExpenses`, `ProjectIESExpenses`, `ProjectBudget`, `Project.target_beneficiaries` (§9.9, §10.1).

### 5.5 Output

- `getIndividualBudgetChecks`, `getDevelopmentPhaseChecks` (or equivalent), and `getBeneficiaryConsistencyChecks` in `ReportMonitoringService`.
- `typeSpecificChecks` in the view includes Individual, Development/…, and Beneficiary.
- `type_specific_monitoring` renders all blocks per project type.

---

## Phase 6 — Integration, Visibility, and Testing

**Guide: §4.2, §7.2, §8, §11**

### 6.1 Goals

- Control **who** sees the monitoring blocks (e.g. provincial, coordinator) and **when** (e.g. status under review).
- Align with the **Implementation outline** in §11.
- Provide a **testing** checklist based on §8.

### 6.2 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 6.1 | (Optional) Show `objectives_activity_monitoring`, `budget_monitoring`, `type_specific_monitoring` only when `in_array(auth()->user()->role, ['provincial','coordinator'])` | `show.blade.php` or inside each partial | §11: *"Optionally show only for provincial (and coordinator if desired)"*. |
| 6.2 | (Optional) Show only when `in_array($report->status, [STATUS_SUBMITTED_TO_PROVINCIAL, STATUS_FORWARDED_TO_COORDINATOR])` | `show.blade.php` or each partial | §4.2. |
| 6.3 | Ensure all six (or more) monitoring arrays are passed from `show()` also when the service returns empty arrays, so the partials do not throw | `ReportController` | Use `$activitiesScheduledNotReported ?? []`, etc. |
| 6.4 | Manual test: Activity monitoring with a project that has objectives/activities/timeframes; run “scheduled not reported”, “reported not scheduled”, “ad‑hoc” scenarios | — | §8.1. |
| 6.5 | Manual test: Budget monitoring with overspend, negative balance, high utilisation | — | §8.2. |
| 6.6 | Manual test: Type‑specific for LDP, IGE, RST, CIC, one Individual type, and one Development (or similar) type | — | §8.3. |
| 6.7 | Add a short “Implementation” or “For developers” note in `Provincial_Monthly_Report_Monitoring_Guide.md` pointing to this plan | `Provincial_Monthly_Report_Monitoring_Guide.md` | E.g. at the end of §11. |

### 6.3 Manual Checklist (from §8)

Use §8.1–8.3 while testing:

- **§8.1 Objectives and activities:** report month, project plan, scheduled but not reported, reported but not scheduled, ad‑hoc, quality of Summary / Qualitative & quantitative / Intermediate outcomes.
- **§8.2 Budget:** overall totals and utilisation, per‑row overspend and negative balance, additional rows, consistency of expenses this month vs last.
- **§8.3 Project-type-specific:** LDP annexure, IGE age profile, RST trainees, CIC inmates, Individual `total_beneficiaries` and budget, and overall beneficiary consistency.

### 6.4 Output

- Visibility and status gating (if adopted); safe defaults for missing monitoring vars.
- Manual test pass for activities, budget, and type‑specific checks.
- Guide updated with a link to this implementation plan.

---

## File Checklist

### New files

| File | Phase |
|------|-------|
| `app/Services/ReportMonitoringService.php` | 1 |
| `resources/views/reports/monthly/partials/view/objectives_activity_monitoring.blade.php` | 2 |
| `resources/views/reports/monthly/partials/view/budget_monitoring.blade.php` | 3 |
| `resources/views/reports/monthly/partials/view/type_specific_monitoring.blade.php` | 4, 5 |

### Modified files

| File | Phase | Changes |
|------|-------|---------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 1–5 | `show()`: `$project->load(['objectives.activities.timeframes'])`; `$reportMonth`; load type‑specific relations by `project_type`; call all ReportMonitoringService methods; pass `activitiesScheduledNotReported`, `activitiesReportedNotScheduled`, `adhocActivities`, `budgetOverspendRows`, `budgetNegativeBalanceRows`, `budgetUtilisation`, `typeSpecificChecks` to the view. |
| `app/Http/Controllers/ProvincialController.php` | 1 | Only if it does not use `ReportController::show()`: same project and report loading. |
| `resources/views/reports/monthly/show.blade.php` | 2, 3, 4, 5, 6 | `@include` objectives_activity_monitoring, budget_monitoring, type_specific_monitoring; optional role/status checks. |
| `Documentations/V1/Reports/MONITORING/Provincial_Monthly_Report_Monitoring_Guide.md` | 6 | Add reference to this implementation plan (e.g. in §11). |

---

## Execution Order

1. **Phase 1** — Foundation: `ReportMonitoringService`, controller load of `objectives.activities.timeframes`, `$reportMonth`.
2. **Phase 2** — Activity: three service methods, `objectives_activity_monitoring` partial, include in show.
3. **Phase 3** — Budget: three (or four) service methods, `budget_monitoring` partial, include in show.
4. **Phase 4** — Type-specific (LDP, IGE, RST, CIC): four methods, controller loading of type relations, `type_specific_monitoring` with four blocks, include in show.
5. **Phase 5** — Type-specific (Individual, Development/…, Beneficiary): three methods, controller logic by project type, extend `type_specific_monitoring`.
6. **Phase 6** — Integration: optional role/status visibility, default empty arrays, manual tests, doc update.

---

## Guide Section Map

| Phase | Primary sections | Other |
|-------|------------------|-------|
| 1 | §2.3, §4.1 | §10.1, §10.2 |
| 2 | §3, §4 | §10.1, §10.2, §10.3 |
| 3 | §5, §6, §7 | §10.1, §10.2, §10.3, §10.5 |
| 4 | §9.1–9.4, §9.8, §9.9 | §10.1, §10.2, §10.3 |
| 5 | §9.5–9.8, §9.9 | §10.1, §10.5 |
| 6 | §4.2, §7.2, §8, §11 | — |

---

**End of Phase-Wise Implementation Plan — Provincial Monthly Report Monitoring Guide**
