# Phase-Wise Implementation Plan — Reports Monitoring

**Scope:** All review and suggestions in `Documentations/V1/Reports/MONITORING/`  
**Sources:** Provincial_Monthly_Report_Monitoring_Guide.md, Report_View_Entered_Fields_Visual_Proposal.md  
**Version:** 1.0

---

## Summary

| Phase | Name | Status | Est. effort |
|-------|------|--------|-------------|
| **1** | Report view: “Entered in report” visual (Option E) | ✅ **Done** | — |
| **2** | Activity monitoring (scheduled/not reported, etc.) | ⏳ Pending | 4–6 h |
| **3** | Budget monitoring (overspend, negative, utilisation) | ⏳ Pending | 2–3 h |
| **4** | Project-type-specific monitoring (LDP, IGE, RST, CIC, Individual, Beneficiary) | ⏳ Pending | 6–8 h |
| **5** | Integration, testing, and remaining items | ⏳ Pending | 2–4 h |

---

## Phase 1 — Report View: “Entered in Report” Visual (Option E) — ✅ DONE

**Source:** Report_View_Entered_Fields_Visual_Proposal.md

### 1.1 Implemented

- **CSS** in `resources/views/reports/monthly/show.blade.php`:
  - `.report-value-entered`, `.report-cell-entered`: `border-left: 3px solid #05a34a;` and `background-color: rgba(5, 163, 74, 0.12);`, `padding-left: 0.5rem;`
  - `.report-view-legend .report-legend-sample` for the legend sample
- **Legend** in Basic Information: “Sample” with green accent = “Entered in report”; “No accent = From project”.
- **Basic Information:** `report-value-entered` on Report Month & Year, Total Beneficiaries.
- **Outlooks:** `report-value-entered` on Date, Action Plan for Next Month.
- **Objectives** (`partials/view/objectives.blade.php`): `report-value-entered` on What Did Not Happen, Why Not, Changes, Why Changes, Lessons Learnt, What Will Be Done Differently; per activity: Month, Summary of Activities, Qualitative & Quantitative Data, Intermediate Outcomes.
- **Statements of Account** (`view/statements_of_account/development_projects.blade.php`): `report-cell-entered` on Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount (body and total row).
- **Photos** (`partials/view/photos.blade.php`): `report-value-entered` on Description.
- **Attachments** (`partials/view/attachments.blade.php`): `report-value-entered` on the attachment block (`att-grp`).
- **LDP** (`partials/view/LivelihoodAnnexure.blade.php`): `report-value-entered` on all value `col-6` (S No., Name, Date, Nature, Amount, Monthly/Annual profit, Impact, Challenges).
- **IGE** (`partials/view/institutional_ongoing_group.blade.php`): `report-cell-entered` on Up to Previous Year, Present Academic Year (data, total, Grand Total).
- **RST** (`partials/view/residential_skill_training.blade.php`): `report-value-entered` on all number `report-value-col` (below_9, class_10_fail, class_10_pass, intermediate, above_intermediate, other_count, total).
- **CIC** (`partials/view/crisis_intervention_center.blade.php`): `report-cell-entered` on Number column (status rows, other_count, total per age, Grand Total).

### 1.2 Optional / not done

- **Individual SoA** (`individual_livelihood`, `individual_health`, `individual_education`, `individual_ongoing_education`): add `report-cell-entered` to the same columns as `development_projects` if the table layout matches.
- **Print/PDF:** `@media print` overrides for `.report-value-entered` / `.report-cell-entered` (e.g. `background: transparent` or `border-left-color: #666`).
- **Toggle:** “Highlight fields entered in report” to turn the green accent on/off.

---

## Phase 2 — Activity Monitoring (Scheduled / Not Reported, etc.)

**Source:** Provincial_Monthly_Report_Monitoring_Guide.md, §3–4

### 2.1 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 2.1 | Create `App\Services\ReportMonitoringService` | `app/Services/ReportMonitoringService.php` | New file |
| 2.2 | Implement `getActivitiesScheduledButNotReported(DPReport $report): array` | ReportMonitoringService | Use project `objectives.activities.timeframes`; `reportMonth` from `report_month_year`; compare with `DPActivity.project_activity_id` |
| 2.3 | Implement `getActivitiesReportedButNotScheduled(DPReport $report): array` | ReportMonitoringService | For each `DPActivity` with `project_activity_id`, check ProjectTimeframe for report month |
| 2.4 | Implement `getAdhocActivities(DPReport $report): array` | ReportMonitoringService | `DPActivity` where `project_activity_id` empty |
| 2.5 | In `ReportController::show()`: `$project->load(['objectives.activities.timeframes'])` | `app/Http/Controllers/Reports/Monthly/ReportController.php` | Before passing to view |
| 2.6 | In `show()`: `$reportMonth = (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');` and call the 3 methods; pass `activitiesScheduledNotReported`, `activitiesReportedNotScheduled`, `adhocActivities` to view | ReportController | |
| 2.7 | Create `partials/view/objectives_activity_monitoring.blade.php` | `resources/views/reports/monthly/partials/view/objectives_activity_monitoring.blade.php` | Three tables/lists; show only when arrays are non-empty and (optional) status in `submitted_to_provincial`, `forwarded_to_coordinator` |
| 2.8 | In `show.blade.php`, include `objectives_activity_monitoring` after Objectives (or before) | `resources/views/reports/monthly/show.blade.php` | `@include(..., ['activitiesScheduledNotReported' => $activitiesScheduledNotReported ?? [], ...])` |

### 2.2 Dependencies

- Project must have `objectives` with `activities` and `timeframes` (project plan). If a project has no objectives/activities, the activity checks will return empty arrays.

---

## Phase 3 — Budget Monitoring (Overspend, Negative, Utilisation)

**Source:** Provincial_Monthly_Report_Monitoring_Guide.md, §6–7

### 3.1 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 3.1 | In `ReportMonitoringService`: `getBudgetOverspendRows(DPReport $report): array` | ReportMonitoringService | Rows where `total_expenses > amount_sanctioned` (or `total_amount`) and `is_budget_row = 1` |
| 3.2 | `getNegativeBalanceRows(DPReport $report): array` | ReportMonitoringService | Rows where `balance_amount < 0`; optionally flag if `sum(balance_amount) < 0` |
| 3.3 | `getBudgetUtilisationSummary(DPReport $report): array` | ReportMonitoringService | `total_sanctioned`, `total_expenses`, `utilisation_percent`, `alerts` (e.g. `['high_utilization','negative_balance','overspend_row']`) |
| 3.4 | In `ReportController::show()`: call the 3 methods; pass `budgetOverspendRows`, `budgetNegativeBalanceRows`, `budgetUtilisation` to view | ReportController | |
| 3.5 | Create `partials/view/budget_monitoring.blade.php` | `resources/views/reports/monthly/partials/view/budget_monitoring.blade.php` | Tables/lists for overspend and negative balance; utilisation summary and one-line alerts |
| 3.6 | In `show.blade.php`, include `budget_monitoring` after Statements of Account (or inside the SoA card) | `resources/views/reports/monthly/show.blade.php` | `@include(..., ['budgetOverspendRows' => $budgetOverspendRows ?? [], ...])` |

### 3.2 Dependencies

- Uses existing `DPAccountDetail` and `report->accountDetails`. `amount_sanctioned_overview` or sum of `amount_sanctioned` for budget rows as total sanctioned.

---

## Phase 4 — Project-Type-Specific Monitoring

**Source:** Provincial_Monthly_Report_Monitoring_Guide.md, §9

### 4.1 Tasks

| # | Task | File(s) | Notes |
|---|------|---------|-------|
| 4.1 | In `ReportController::show()`: when LDP, load `report->annexures`; when IGE, load `report->rqis_age_profile`; when RST, load `report->rqst_trainee_profile`; when CIC, load `report->rqwd_inmate_profile` | ReportController | Eager-load or `$report->load([...])` by `$report->project_type` |
| 4.2 | `getLdpAnnexureChecks(DPReport $report): array` | ReportMonitoringService | Annexure present; support date in period; empty impact/amount; optional: sum vs budget, count vs ProjectLDPTargetGroup |
| 4.3 | `getIgeAgeProfileChecks(DPReport $report): array` | ReportMonitoringService | Grand Total; all 4 age groups; Grand Total vs `total_beneficiaries`; sub-totals |
| 4.4 | `getRstTraineeChecks(DPReport $report): array` | ReportMonitoringService | Total present; total = sum of categories; total vs `total_beneficiaries`; all categories |
| 4.5 | `getCicInmateChecks(DPReport $report): array` | ReportMonitoringService | Grand Total; all 4 age categories; sub-totals; Grand Total vs `total_beneficiaries` |
| 4.6 | `getIndividualBudgetChecks(DPReport $report, Project $project): array` | ReportMonitoringService | `total_beneficiaries` = 1; budget heads match project type; contribution; no duplicate particulars |
| 4.7 | `getBeneficiaryConsistencyChecks(DPReport $report, Project $project): array` | ReportMonitoringService | Report `total_beneficiaries` vs project; type-specific total vs `total_beneficiaries` |
| 4.8 | In `show()`: by `project_type`, call the type-specific methods; build `$typeSpecificChecks` (or distinct vars) and pass to view | ReportController | |
| 4.9 | Create `partials/view/type_specific_monitoring.blade.php` | `resources/views/reports/monthly/partials/view/type_specific_monitoring.blade.php` | By type: LDP, IGE, RST, CIC, Individual, Beneficiary. Render only when `typeSpecificChecks` is non-empty or `project_type` in [LDP, IGE, RST, CIC, Individual] |
| 4.10 | In `show.blade.php`, include `type_specific_monitoring` after type-specific sections (LDP/IGE/RST/CIC) or after Budget | `resources/views/reports/monthly/show.blade.php` | `@include(..., ['typeSpecificChecks' => $typeSpecificChecks ?? []])` |

### 4.2 Dependencies

- Project relations: LDP `ldpTargetGroup`, IGE `igeInstitutionInfo`, `igeNewBeneficiaries`, `igeOngoingBeneficiaries`, RST `rstTargetGroup`, `rstInstitutionInfo`, CIC `cicBasicInfo`, Individual budget models. Load in controller when calling type-specific methods.

---

## Phase 5 — Integration, Testing, and Remaining Items

### 5.1 Integration

| # | Task | Notes |
|---|------|-------|
| 5.1 | Ensure `ReportController::show()` passes all new variables to the view when the service exists | `activitiesScheduledNotReported`, `activitiesReportedNotScheduled`, `adhocActivities`, `budgetOverspendRows`, `budgetNegativeBalanceRows`, `budgetUtilisation`, `typeSpecificChecks` |
| 5.2 | Optional: show `objectives_activity_monitoring`, `budget_monitoring`, `type_specific_monitoring` only for `provincial` and `coordinator` (or `general`) | Blade `@if(in_array(auth()->user()->role, ['provincial','coordinator','general']))` |
| 5.3 | Optional: show only when report status is under review (e.g. `submitted_to_provincial`, `forwarded_to_coordinator`) | Reduces noise for approved/draft |

### 5.2 Report View Entered-Fields (Phase 1) — Remaining

| # | Task | Notes |
|---|------|-------|
| 5.4 | Add `report-cell-entered` to Individual SoA view partials | `view/statements_of_account/individual_livelihood`, `individual_health`, `individual_education`, `individual_ongoing_education` — same columns as development_projects if structure matches |
| 5.5 | `@media print` overrides for `.report-value-entered` / `.report-cell-entered` | In `show.blade.php` or `report-view-entities.css`: e.g. `background: transparent; border-left-color: #666;` |
| 5.6 | Optional: toggle “Highlight fields entered in report” | Button/checkbox; add `data-highlight-entries` or class on container; CSS applies only when enabled |

### 5.3 Testing

| # | Task | Notes |
|---|------|-------|
| 5.7 | Manual: View report with mixed empty/filled report-entered fields | Green accent on all report-entered; legend and hide-empty.js still work |
| 5.8 | Manual: Activity monitoring with project that has objectives/activities/timeframes | Scheduled-not-reported, reported-not-scheduled, ad-hoc; project without objectives → empty |
| 5.9 | Manual: Budget monitoring with overspend, negative balance, high utilisation | Correct flags and display |
| 5.10 | Manual: Type-specific for LDP, IGE, RST, CIC, one Individual type | Correct checks and display |
| 5.11 | Role/status: Provincial and Coordinator see monitoring blocks; Executor sees view (monitoring optional per 5.2–5.3) | |

### 5.4 Documentation

| # | Task | Notes |
|---|------|-------|
| 5.12 | Update `Report_View_Entered_Fields_Visual_Proposal.md` | Mark Phase 1 as implemented; add “Phase 1 implemented in Phase_Wise_Implementation_Plan” |
| 5.13 | Update `Provincial_Monthly_Report_Monitoring_Guide.md` | Add “Implementation: see Phase_Wise_Implementation_Plan.md” in §4, §7, §9.8 |

---

## File Checklist

### New files to create

| File | Phase |
|------|-------|
| `app/Services/ReportMonitoringService.php` | 2, 3, 4 |
| `resources/views/reports/monthly/partials/view/objectives_activity_monitoring.blade.php` | 2 |
| `resources/views/reports/monthly/partials/view/budget_monitoring.blade.php` | 3 |
| `resources/views/reports/monthly/partials/view/type_specific_monitoring.blade.php` | 4 |

### Files to modify

| File | Phase | Changes |
|------|-------|---------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | 2, 3, 4 | `show()`: load `project.objectives.activities.timeframes`; load type-specific relations by `project_type`; `$reportMonth`; call ReportMonitoringService methods; pass new vars to view |
| `resources/views/reports/monthly/show.blade.php` | 2, 3, 4 | `@include` objectives_activity_monitoring, budget_monitoring, type_specific_monitoring |
| `resources/views/reports/monthly/partials/view/statements_of_account/individual_*.blade.php` | 5 | Add `report-cell-entered` to Expenses Last Month, This Month, Total, Balance (if same table as development_projects) |
| `resources/views/reports/monthly/show.blade.php` (style) | 5 | `@media print` for `.report-value-entered`, `.report-cell-entered` (optional) |

---

## Execution Order

1. **Phase 2** (Activity monitoring) — service, controller, partial, include.
2. **Phase 3** (Budget monitoring) — add to same service, controller, new partial, include.
3. **Phase 4** (Type-specific) — add to service, controller (relations + calls), new partial, include.
4. **Phase 5** — Individual SoA `report-cell-entered`, print CSS, toggle (optional), testing, doc updates.

---

## References

- `Documentations/V1/Reports/MONITORING/Provincial_Monthly_Report_Monitoring_Guide.md`
- `Documentations/V1/Reports/MONITORING/Report_View_Entered_Fields_Visual_Proposal.md`
- `Documentations/V1/Reports/MONITORING/README.md`

---

**End of Phase-Wise Implementation Plan**
