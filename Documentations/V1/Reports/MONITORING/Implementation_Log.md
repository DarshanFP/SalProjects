# Implementation Log — Reports Monitoring and Report View

**Scope:** All implementations from the MONITORING documentation and related report-view work in this folder.  
**Location:** `Documentations/V1/Reports/MONITORING/`  
**Version:** 1.0

---

## 1. Overview

This document records **what has been implemented** for:

- **Report view “Entered in report” visual** (Option E)
- **Provincial monitoring:** Activity, Budget, Project-type-specific
- **Photos:** Unassigned vs grouped, activity-linked photos, `photo_location`
- **Objectives view:** Inline schedule-status badges, per-activity photos
- **Statements of Account (development_projects):** Table layout, Budget Row badge
- **Provincial actions:** Forward to Coordinator, Revert Report, Back to Reports by role

**Visibility rule (implemented):** Monitoring blocks and per-activity badges are shown only when **user role** is `provincial` or `coordinator` **and** **report status** is `submitted_to_provincial` or `forwarded_to_coordinator`.

---

## 2. Report View — “Entered in Report” Visual (Option E)

**Source:** `Report_View_Entered_Fields_Visual_Proposal.md`, `Phase_Wise_Implementation_Plan.md` Phase 1

### 2.1 CSS (`show.blade.php`)

- **`.report-value-entered`**, **`.report-cell-entered`**
  - `border-left: 3px solid #05a34a;`
  - `background-color: rgba(5, 163, 74, 0.12);`
  - `padding-left: 0.5rem;`
- **`.report-view-legend .report-legend-sample`** — used in the Basic Information legend.

### 2.2 Legend (Basic Information)

- Short block: green-accent “Sample” = “Entered in report”; “No accent = From project”.

### 2.3 Fields with `report-value-entered` or `report-cell-entered`

| Section | File | Fields / cells |
|---------|------|----------------|
| **Basic Information** | `show.blade.php` | Report Month & Year, Total Beneficiaries |
| **Outlooks** | `show.blade.php` | Date, Action Plan for Next Month |
| **Objectives** | `partials/view/objectives.blade.php` | What Did Not Happen, Why Not, Changes, Why Changes, Lessons Learnt, What Will Be Done Differently; per activity: Month, Summary of Activities, Qualitative & Quantitative Data, Intermediate Outcomes |
| **Statements of Account (development_projects)** | `partials/view/statements_of_account/development_projects.blade.php` | `report-cell-entered` on Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount (body and total row) |
| **Attachments** | `partials/view/attachments.blade.php` | Attachment block (`att-grp` with `report-value-entered`) |
| **LDP (LivelihoodAnnexure)** | `partials/view/LivelihoodAnnexure.blade.php` | All value `col-6` (S No., Name, Date, Nature, Amount, Monthly/Annual profit, Impact, Challenges) |
| **IGE** | `partials/view/institutional_ongoing_group.blade.php` | `report-cell-entered` on Up to Previous Year, Present Academic Year (data, total, Grand Total) |
| **RST** | `partials/view/residential_skill_training.blade.php` | All number `report-value-col` (below_9, class_10_fail, etc., total) |
| **CIC** | `partials/view/crisis_intervention_center.blade.php` | `report-cell-entered` on Number column |

### 2.4 Photos

- The **Photos** partial was refactored: it now prefers **`unassignedPhotos`** and no longer has a per-group **Description** field. The `report-value-entered` that was on Description was **removed** when switching to the unassigned-photos layout. Activity-linked photos are shown under each activity in **objectives.blade.php** (see §5.2).

### 2.5 Not done (optional)

- Individual SoA (`individual_livelihood`, `individual_health`, `individual_education`, `individual_ongoing_education`): `report-cell-entered` on same columns as `development_projects` if layout matches.
- `@media print` overrides for `.report-value-entered` / `.report-cell-entered`.
- Toggle to turn the green accent on/off.

---

## 3. Activity Monitoring

**Source:** `Provincial_Monthly_Report_Monitoring_Guide.md`, `Provincial_Monthly_Report_Monitoring_Implementation_Plan.md`, `Updates.md` §4 (refined design)

### 3.1 Inline badges (in Objectives, per reported activity)

- **File:** `partials/view/objectives.blade.php`
- **Where:** Activity block heading: `Activity X of Y: {activity name}`. For **provincial/coordinator** and status `submitted_to_provincial` or `forwarded_to_coordinator`, a badge is shown:
  - **`SCHEDULED – REPORTED`** (green `bg-success`) when `$reportedActivityScheduleStatus[$activity->activity_id] === 'scheduled_reported'`
  - **`NOT SCHEDULED – REPORTED`** (warning `bg-warning text-dark`) otherwise (e.g. `not_scheduled_reported`).
- **Data:** `$reportedActivityScheduleStatus` — map `activity_id => 'scheduled_reported'|'not_scheduled_reported'` passed from the controller (from `ReportMonitoringService` or equivalent).

### 3.2 Activity Monitoring block — “Scheduled but not reported”

- **File:** `partials/view/activity_monitoring.blade.php`
- **Placement in `show.blade.php`:** After Attachments, before Activity History (and Comments).
- **Logic:**
  - Shown only when: `role` in `['provincial','coordinator']`, `status` in `['submitted_to_provincial','forwarded_to_coordinator']`, and `$activitiesScheduledButNotReportedGroupedByObjective` has at least one objective.
  - Renders a card with **Objective** and a list of **activities** (scheduled for the report month but not reported). Note: “Check ‘What did not happen’ / ‘Why not’ for the relevant objective.”
- **Controller vars:** `$activitiesScheduledButNotReportedGroupedByObjective` (array of `[objective => string, activities => [...]]`).

### 3.3 Objectives include (show.blade.php)

- `@include('reports.monthly.partials.view.objectives', ['report' => $report, 'monitoringPerObjective' => $monitoringPerObjective ?? [], 'reportedActivityScheduleStatus' => $reportedActivityScheduleStatus ?? []])`
- **`monitoringPerObjective`** — if still used inside objectives for any legacy block; the refined design uses **`reportedActivityScheduleStatus`** for badges and **`activity_monitoring`** for “scheduled but not reported.”

### 3.4 Other activity checks (service only, not in view)

- `getActivitiesReportedButNotScheduled`, `getAdhocActivities` — implemented in `ReportMonitoringService`; the **refined design** does not show separate tables for these; the inline **NOT SCHEDULED – REPORTED** badge covers the “reported but not scheduled” case.

### 3.5 Partial `objectives_activity_monitoring.blade.php`

- **Exists** in `partials/view/`. The **show** view uses **`activity_monitoring`** (scheduled-but-not-reported only) instead. `objectives_activity_monitoring` can be kept for possible future use (e.g. “reported not scheduled” or “ad‑hoc” lists) or removed if not needed.

---

## 4. Budget Monitoring

**Source:** `Provincial_Monthly_Report_Monitoring_Guide.md` §6–7, `Phase_Wise_Implementation_Plan.md` Phase 3

### 4.1 Partial and placement

- **File:** `partials/view/budget_monitoring.blade.php`
- **Placement in `show.blade.php`:** After Statements of Account, before Photos.
- **Visibility:** Only when `role` in `['provincial','coordinator']` and `status` in `['submitted_to_provincial','forwarded_to_coordinator']` (card is always rendered when those hold; content depends on data).

### 4.2 Content

- **Utilisation summary:** `total_sanctioned`, `total_expenses`, `utilisation_percent` from `$budgetUtilisation`.
- **Alerts** (from `$budgetUtilisation['alerts']`): `high_utilization`, `negative_balance`, `overspend_row`, `high_expenses_this_month` — each mapped to a one-line message.
- **Overspend table:** `$budgetOverspendRows` — columns: Particulars, Amount sanctioned, Total expenses, Excess.
- **Negative balance table:** `$budgetNegativeBalanceRows` — Particulars, Balance amount.

### 4.3 Controller variables

- `$budgetOverspendRows`
- `$budgetNegativeBalanceRows`
- `$budgetUtilisation` → `['total_sanctioned','total_expenses','utilisation_percent','alerts']`

---

## 5. Project-Type-Specific Monitoring

**Source:** `Provincial_Monthly_Report_Monitoring_Guide.md` §9, `Provincial_Monthly_Report_Monitoring_Implementation_Plan.md` Phases 4–5

### 5.1 Partial and placement

- **File:** `partials/view/type_specific_monitoring.blade.php`
- **Placement in `show.blade.php`:** After `budget_monitoring`, before Photos.
- **Visibility:** When `role` in `['provincial','coordinator']`, `status` in `['submitted_to_provincial','forwarded_to_coordinator']`, **and** `$typeSpecificChecks` has at least one block with non‑empty `alerts`.

### 5.2 Structure of `$typeSpecificChecks`

- **`ldp`** → `['alerts' => [...], 'meta' => ['count' => ...]]`
- **`ige`** → `['alerts' => [...]]`
- **`rst`** → `['alerts' => [...], 'meta' => ['total' => ..., 'sum_categories' => ...]]`
- **`cic`** → `['alerts' => [...], 'meta' => ['grand_total' => ...]]`
- **`individual`** → `['alerts' => [...]]`
- **`development`** → `['alerts' => [...]]`
- **`beneficiary`** → `['alerts' => [...]]`

Each block is shown only when the corresponding `alerts` array is non‑empty.

---

## 6. Photos

**Source:** Changes in `partials/view/photos.blade.php` (from attached diff and current file)

### 6.1 Data and fallback

- **Preferred:** `$unassignedPhotos` — photos with `activity_id` null (or equivalent “unassigned” rule).
- **Legacy:** If `$unassignedPhotos` is not passed and `$groupedPhotos` is, `$groupedPhotos->flatten(1)` is used and the section is titled **“Photos”**.
- **`$isLegacy`:** `true` when only `groupedPhotos` is provided.

### 6.2 Show include

- `@include('reports.monthly.partials.view.photos', ['unassignedPhotos' => $unassignedPhotos])`
- The controller must provide `$unassignedPhotos` (e.g. `$report->photos->where('activity_id', null)` or a dedicated relation/query).

### 6.3 Display

- Flat list of photos (no per-group description). Each photo: image, “View Full Size” link, and **`photo_location`** (emoji or text) when `!empty($photo->photo_location)`.
- Header: **“Unassigned Photos”** (normal) or **“Photos”** (legacy). Empty: “No unassigned photos.” / “No photos available.”

### 6.4 “Entered in report” on photos

- The old **Description** field with `report-value-entered` was removed when switching to unassigned layout. `photo_location` is shown but does not use `report-value-entered` in the current implementation.

---

## 7. Objectives — Per-Activity Photos and Activity Heading

**Source:** Changes in `partials/view/objectives.blade.php`

### 7.1 Activity block heading

- **Format:** `Activity {i} of {n}: {activity name}`. For provincial/coordinator when status is under review: badge **SCHEDULED – REPORTED** or **NOT SCHEDULED – REPORTED** (see §3.1).
- **Classes:** `activity-block-heading`, `border-start border-2 border-primary`, `text-white`, `fw-bold`.

### 7.2 Per-activity photos

- **Data:** `$activity->photos` (relation or collection; ensure `DPActivity` loads `photos` in the controller).
- **Placement:** After the activity’s Intermediate Outcomes row, before the next activity.
- **Markup:** Row “Photos:” and a responsive grid of thumbnails, “View Full Size,” and `photo_location` when present. Reuses the same `openImageModal` and `#imageModal` as the main Photos section.

---

## 8. Statements of Account — Development Projects View

**Source:** Changes in `partials/view/statements_of_account/development_projects.blade.php`

### 8.1 Table layout

- Wrapper: **`div.table-responsive.budget-details-table-wrapper`**.
- Table: **`table.budget-details-table`**.
- **Columns:** `col-particulars` (Particulars), `col-numeric` (Amount Sanctioned, Total Amount, Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount).

### 8.2 Budget Row badge

- **Class:** `badge scheduled-months-badge` (replaces `badge bg-info`). Color: `#0f766e` (dark teal).
- **When:** `$accountDetail->is_budget_row` is true.

### 8.3 “Entered in report” and numeric alignment

- **`report-cell-entered`** on: Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount (body and total row).
- **`col-numeric`:** `text-align: right`; column width and wrapping tuned so the table fits the container.

### 8.4 CSS (in the partial)

- `.budget-details-table`: `font-size: 0.875rem`, `table-layout: fixed`, column widths (e.g. Particulars 22%, numeric 13% / 12% for last four).
- `.scheduled-months-badge`: `background-color: #0f766e !important; color: #fff;`

---

## 9. Provincial Actions: Forward, Revert, Back to Reports

**Source:** Changes in `show.blade.php`

### 9.1 Back to Reports

- **`$backToReportsUrl`:**
  - `provincial` → `route('provincial.report.list')`
  - `coordinator` → `route('coordinator.report.list')`
  - default → `route('monthly.report.index')`
- The “Back to Reports” button uses `$backToReportsUrl` instead of `monthly.report.index` for all roles.

### 9.2 Forward to Coordinator

- **`$canForward`:** `role === 'provincial'` and `status` in:
  - `STATUS_SUBMITTED_TO_PROVINCIAL`
  - `STATUS_REVERTED_BY_COORDINATOR`
  - `STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR`
  - `STATUS_REVERTED_TO_PROVINCIAL`
- **Button:** “Forward to Coordinator” opens modal `#forwardModalShow{report_id}`.
- **Modal:** Form `POST` to `route('provincial.report.forward', $report->report_id)`, `@csrf`. Body: confirmation, Report ID, Project. Submit: “Forward to Coordinator.”

### 9.3 Revert Report

- **`$canRevert`:** `role === 'provincial'` and `status` in:
  - `STATUS_SUBMITTED_TO_PROVINCIAL`
  - `STATUS_FORWARDED_TO_COORDINATOR`
  - `STATUS_REVERTED_BY_COORDINATOR`
- **Button:** “Revert Report” opens modal `#revertModalShow{report_id}`.
- **Modal:** Form `POST` to `route('provincial.report.revert', $report->report_id)`, `@csrf`. Body: Report ID, Project, **`revert_reason`** textarea (required). Submit: “Revert to Executor.”

### 9.4 Routes (expected)

- `provincial.report.forward` — `POST`; handled by ProvincialController or equivalent.
- `provincial.report.revert` — `POST`; `revert_reason` in request.
- `provincial.report.list`, `coordinator.report.list` — list views for each role.

---

## 10. Show View — Include Order and Variables

**File:** `resources/views/reports/monthly/show.blade.php`

### 10.1 Include order (main content)

1. Basic Information (with legend)
2. Type-specific sections (LDP, IGE, RST, CIC) when `project_type` matches
3. **Objectives** — `report`, `monitoringPerObjective`, `reportedActivityScheduleStatus`
4. Outlooks
5. **Statements of Account** — `budgets`, `project`
6. **budget_monitoring**
7. **type_specific_monitoring**
8. **Photos** — `unassignedPhotos`
9. Attachments
10. **activity_monitoring**
11. (Card footer: Download PDF, etc.)
12. Activity History, Comments

### 10.2 Variables the controller must pass (for monitoring and new UI)

- `monitoringPerObjective` (optional, if still used)
- `reportedActivityScheduleStatus`
- `activitiesScheduledButNotReportedGroupedByObjective`
- `budgetOverspendRows`, `budgetNegativeBalanceRows`, `budgetUtilisation`
- `typeSpecificChecks`
- `unassignedPhotos`
- For **objectives** / **DPActivity**: `activities` with `photos` relation loaded so `$activity->photos` is available.

---

## 11. Service and Controller (summary)

- **`App\Services\ReportMonitoringService`** (or equivalent) provides:
  - `getActivitiesScheduledButNotReported` (and grouped-by-objective variant for `activity_monitoring`)
  - `getReportedActivityScheduleStatus` or logic that builds `reportedActivityScheduleStatus` (scheduled_reported / not_scheduled_reported per `DPActivity`)
  - `getBudgetOverspendRows`, `getNegativeBalanceRows`, `getBudgetUtilisationSummary`
  - Type-specific: `getLdpAnnexureChecks`, `getIgeAgeProfileChecks`, `getRstTraineeChecks`, `getCicInmateChecks`, `getIndividualBudgetChecks`, `getBeneficiaryConsistencyChecks`, and any `getDevelopmentPhaseChecks` or similar merged into `typeSpecificChecks`.

- **`ReportController::show()`** (and any Provincial/Coordinator path that reuses it):
  - Loads `project.objectives.activities.timeframes`, `report->objectives.activities.photos` (or equivalent so `$activity->photos` exists), and type-specific relations when needed.
  - Builds `$unassignedPhotos` (e.g. from `$report->photos->whereNull('activity_id')` or report’s photo relation).
  - Calls the monitoring methods and passes the variables listed in §10.2.

---

## 12. Visibility Rule (Summary)

Monitoring blocks and activity badges are shown only when **all** of:

- **Role:** `provincial` or `coordinator`
- **Status:** `submitted_to_provincial` or `forwarded_to_coordinator`

**Applies to:**

- **`activity_monitoring`** partial
- **`budget_monitoring`** partial (card visibility; content still depends on data)
- **`type_specific_monitoring`** partial (and `$hasAny` from `typeSpecificChecks`)
- **Inline badges** in `objectives.blade.php` (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED)

**Reference:** `Provincial_Monthly_Report_Monitoring_Guide.md` §11 Implementation status.

---

## 13. Related Documents

| Document | Role |
|----------|------|
| **Provincial_Monthly_Report_Monitoring_Guide.md** | Logic, data model, manual checklist, implementation status |
| **Provincial_Monthly_Report_Monitoring_Implementation_Plan.md** | Phase-wise plan for the Provincial Guide |
| **Phase_Wise_Implementation_Plan.md** | Plan for all MONITORING (Report view Option E, Activity, Budget, Type-specific, Integration) |
| **Report_View_Entered_Fields_Visual_Proposal.md** | Option E and field list for “entered in report” |
| **Updates.md** | Refined Activity Monitoring design (§4), gaps, and data model notes |

---

**End of Implementation Log**
