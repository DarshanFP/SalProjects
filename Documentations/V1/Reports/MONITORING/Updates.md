# Provincial Report View — Monitoring Gaps and Suggestions for Updates

**Audience:** Provincial users, developers  
**Scope:** Report view for project types with **objectives and activities** (e.g. Development Projects such as DP-0002-03), at  
`/provincial/reports/monthly/show/{report_id}`  
**Sources:** Provincial_Monthly_Report_Monitoring_Guide.md, Create docs, models, ReportController, ReportMonitoringService, view partials  
**Version:** 1.1  
**Location:** `Documentations/V1/Reports/MONITORING/Updates.md`

---

## 1. Summary

This document:

1. **Compares** project-side data (objectives, activities, timeframes) with report-side data (DPObjective, DPActivity).
2. **Identifies gaps** in the current monitoring for provincial users on the report view.
3. **Suggests** what could be included and how monitoring can be made easier for provincial users.
4. **Describes the refined design** for Activity Monitoring: inline badges (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED) next to each reported activity, and a single **Activity Monitoring** section (only “scheduled but not reported”, objective-wise) at the bottom, before Add Comment.

It focuses on project types that use **objectives and activities** (Development Projects, CHILD CARE INSTITUTION, Rural-Urban-Tribal, NEXT PHASE - DEVELOPMENT PROPOSAL, and others that share the same objectives/activities structure).

**Note on skipped objectives:** If the executor skips **all** activities of an objective (no user-filled Summary, Qualitative, Outcomes, or Add Other), the objective itself may be omitted or appear with no activities in the report. For the **report**, this is accepted. The **Activity Monitoring** section (see §4) will still list those scheduled-but-not-reported activities, **objective-wise**, including when the whole objective is missing from the report.

---

## 2. Data Model Comparison: Project vs Report

### 2.1 Project (Plan)

| Model              | Table                 | Key fields                                               | Purpose                         |
|--------------------|-----------------------|----------------------------------------------------------|---------------------------------|
| **ProjectObjective** | `project_objectives`  | `objective_id`, `project_id`, `objective`                | Project objective               |
| **ProjectActivity**  | `project_activities`  | `activity_id`, `objective_id`, `activity`, `verification`| Activity under an objective     |
| **ProjectTimeframe** | `project_timeframes`  | `timeframe_id`, `activity_id`, **`month`**, **`is_active`** | When an activity is scheduled   |

- **`ProjectTimeframe.month`:** 1–12 (January = 1, December = 12). **No year** — only calendar month.
- **`ProjectTimeframe.is_active`:** 1 = scheduled for that month; 0 = not scheduled.
- **Relation:** `Project` → `objectives` → `activities` → `timeframes`.

### 2.2 Report (What was submitted)

| Model        | Table           | Key fields                                                                 | Purpose                                      |
|--------------|-----------------|----------------------------------------------------------------------------|----------------------------------------------|
| **DPObjective** | `DP_Objectives` | `objective_id`, `report_id`, **`project_objective_id`**, `objective`, `not_happened`, `why_not_happened`, `changes`, `why_changes`, `lessons_learnt`, `todo_lessons_learnt` | Reported objective, linked to project        |
| **DPActivity**  | `DP_Activities` | `activity_id`, `objective_id`, **`project_activity_id`**, `activity`, **`month`**, `summary_activities`, `qualitative_quantitative_data`, `intermediate_outcomes` | Reported activity                            |

- **`DPObjective.project_objective_id`:** Links to `ProjectObjective.objective_id`. Can be null if executor adds an objective not in the project.
- **`DPActivity.project_activity_id`:** Links to `ProjectActivity.activity_id`; **empty for “Add Other Activity”.**
- **`DPActivity.month`:** Reporting month (1–12) chosen/synced for that activity.
- **`DPReport.report_month_year`:** Date for the report; report month = `Carbon::parse($report->report_month_year)->format('n')`.

### 2.3 Store logic (Create/Edit)

From `Activity_Store_Only_When_User_Filled_Phase_Wise_Implementation_Plan.md`:

- An activity is **stored** only when at least one **user-filled** field is present: `summary_activities`, `qualitative_quantitative_data`, `intermediate_outcomes`, or (Add Other and `activity`). **`month` is excluded** (filled by `report-period-sync.js`).
- If the user only sets `month` and does not fill Summary / Qualitative / Outcomes, the activity is **not** stored. It will correctly appear as “scheduled but not reported” when monitoring runs.

### 2.4 Mismatches and limits

| Aspect                         | Project                               | Report                                   | Implication                                                                 |
|--------------------------------|----------------------------------------|------------------------------------------|-----------------------------------------------------------------------------|
| **Timeframe year**             | `project_timeframes.month` only (1–12) | `report_month_year` (full date)          | “Scheduled for report month” uses month only. Same activity in Mar 2024 and Mar 2025 both match report month 3. Design choice: timeframes are month-only. |
| **Objective in report**        | All `ProjectObjective` for the project | Only `DPObjective` with `project_objective_id` (or ad‑hoc) | If executor omits an **entire objective**, there is no `DPObjective` for it. Current monitoring does not surface “project objective X with scheduled activities is missing from the report.” |
| **Activity in report**         | All `ProjectActivity` with timeframes  | Only `DPActivity` with `hasUserFilledData()` | View filters out activities with only `month`; consistent with store logic. |

---

## 3. Current Monitoring on the Report View

### 3.1 What is implemented (to be replaced for Activity Monitoring)

- **Activity Monitoring (per objective) — to be replaced**  
  - In `objectives.blade.php`, for each **report objective** (DPObjective), an “Activity Monitoring” block currently shows:
    - **SCHEDULED – REPORTED**, **SCHEDULED – NOT REPORTED**, **NOT SCHEDULED – REPORTED** together.
  - Source: `ReportMonitoringService::getMonitoringPerObjective(DPReport $report)`.
  - **Requirement:** This block is to be **removed** and replaced by the **refined design** in §4 (inline badges next to each reported activity + a single “Activity Monitoring” section at the bottom with only “scheduled but not reported”, objective-wise).

- **Budget Monitoring**  
  - `budget_monitoring.blade.php`: overspend, negative balance, utilisation summary, alerts (e.g. high utilisation, high_expenses_this_month).
  - Source: `getBudgetOverspendRows`, `getNegativeBalanceRows`, `getBudgetUtilisationSummary`.

- **Project-type-specific Monitoring**  
  - `type_specific_monitoring.blade.php`: LDP, IGE, RST, CIC, Individual, Development/CCI/Rural-Urban-Tribal/NEXT PHASE, Beneficiary.
  - For **Development Projects** (e.g. DP-0002-03): only **`development`** (e.g. total_beneficiaries vs project target) and **`beneficiary`** (type-specific total vs `total_beneficiaries`) when there are alerts.

- **“Entered in report” highlighting**  
  - Green accent on report-entered fields (Option E): Basic Info, Objectives, Outlooks, SoA, Photos, Attachments, type-specific sections.

### 3.2 What is **not** in the view (but exists in the service)

- **`getActivitiesScheduledButNotReported`** — project-wide list of activities scheduled for the report month but not reported (including when the **whole objective** is missing). Under the refined design (§4), this (or a variant grouped by objective) will be used for the **Activity Monitoring** section at the bottom.
- **`getActivitiesReportedButNotScheduled`** and **`getAdhocActivities`** — not used in the refined design; the **inline badges** (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED) next to each reported activity give the needed signal for reported work.

---

## 4. Refined Design: Activity Monitoring

This section defines the **required** design for activity-related monitoring on the report view. It **replaces** the current per‑objective “Activity Monitoring” block.

### 4.1 Inline badges next to each reported activity

- **Where:** Next to each **reported** activity in the Activities list (under each objective in `objectives.blade.php`).
- **Which activities:** Only activities for which the executor/applicant has **filled** at least one of: **Summary of Activities**, **Qualitative & Quantitative Data**, **Intermediate Outcomes** (or, for “Add Other”, the activity text). In practice this is all activities shown in the view, since the view already filters by `hasUserFilledData()`.
- **Badges:**
  - **SCHEDULED – REPORTED:** The reported activity was **scheduled for the report month** in the project plan.  
    - Logic: `DPActivity.project_activity_id` matches a `ProjectActivity` that has a `ProjectTimeframe` with `month = reportMonth` and `is_active = 1`.
  - **NOT SCHEDULED – REPORTED:** The reported activity was **not** scheduled for the report month.  
    - Covers: (a) **Ad‑hoc** (`project_activity_id` empty); (b) linked to a project activity that has no `ProjectTimeframe` for the report month with `is_active = 1`.
- **Placement:** e.g. beside the activity heading (“Activity 1 of N: …”) or in a small badge row under it. Same role/status rules as today: `provincial` or `coordinator` when status is `submitted_to_provincial` or `forwarded_to_coordinator`.

### 4.2 Remove the current per‑objective Activity Monitoring block

- **Remove** the block in `objectives.blade.php` that lists, per objective:
  - SCHEDULED – REPORTED, SCHEDULED – NOT REPORTED, NOT SCHEDULED – REPORTED.
- The **inline badges** (§4.1) replace the need for “SCHEDULED – REPORTED” and “NOT SCHEDULED – REPORTED” in that block.  
- “SCHEDULED – NOT REPORTED” is **not** shown next to activities (those activities are not in the report); it is **only** shown in the dedicated **Activity Monitoring** section (§4.3).

### 4.3 Activity Monitoring section: only “scheduled but not reported”

- **Purpose:** List **all** activities across all objectives that were **scheduled for the report month** in the project plan but **not reported** by the executor (no `DPActivity` with user‑filled data for that `project_activity_id`; or the whole objective is missing from the report).
- **Placement:** At the **bottom of the report view, before the Add Comment section**.
- **Content:**  
  - **Only** “scheduled but not reported” activities.  
  - **Grouped objective‑wise by project objective:** under each **project objective** (from the project plan), list the project activities that were scheduled for the report month but not reported. Use the **project** objective text (and order), since the report may have no `DPObjective` for that objective.  
  - Structure: e.g.  
    - **Objective 1:** &lt;project objective text&gt;  
      - Activity A  
      - Activity B  
    - **Objective 2:** &lt;project objective text&gt;  
      - Activity C  
  - Include objectives that are **entirely missing** from the report: if a project objective has no `DPObjective` in the report, all its activities that were scheduled for the month are listed under that project objective in this section.
- **Note for provincial:** e.g. “Check ‘What did not happen’ / ‘Why not’ for the relevant objective (if it exists in the report), or consider reverting so the executor can add the objective and explain.”
- **Visibility:** Same as today: `provincial` or `coordinator` when status is `submitted_to_provincial` or `forwarded_to_coordinator`. **Show the section only when the list is non‑empty**; if there are no “scheduled but not reported” activities, the Activity Monitoring section is hidden.

### 4.4 Service and data

- **For inline badges:** For each `DPActivity` in the report (with `hasUserFilledData()`), the view needs: whether it is **scheduled for the report month** (yes → SCHEDULED – REPORTED, no → NOT SCHEDULED – REPORTED). This can come from `getMonitoringPerObjective` (reused in a reduced form) or a small helper, e.g. `getReportedActivityScheduleStatus(DPReport $report): array<string, 'scheduled_reported'|'not_scheduled_reported'>` keyed by `activity_id`.
- **For Activity Monitoring section:** Use `getActivitiesScheduledButNotReported` or a new **`getActivitiesScheduledButNotReportedGroupedByObjective(DPReport $report): array`** returning structure:  
  `[ ['objective' => '...', 'objective_id' => '...', 'activities' => [ ['activity' => '...', 'activity_id' => '...'] ] ], ... ]`  
  so the view can render objective‑wise. The existing `getActivitiesScheduledButNotReported` already has `objective` and `activity`; it can be grouped in the view or by a new service method.

---

## 5. Identified Gaps

### 5.1 Objectives and activities

| # | Gap | Description |
|---|-----|-------------|
| **G1** | **Project objectives missing from the report** | If the executor does **not** add a DPObjective for a project objective that has activities **scheduled for the report month**, `getMonitoringPerObjective` never runs for it. There is no “Objective X (from project) is missing; the following activities were scheduled for this month: …”. **Addressed by the refined design (§4):** the **Activity Monitoring** section (only “scheduled but not reported”, objective‑wise) includes activities from objectives that are entirely missing from the report. |
| **G2** | **No global “Scheduled – Not reported” summary** | The current implementation only shows “Scheduled – Not reported” **per report objective**; when an objective is missing, it is invisible. **Addressed by the refined design (§4):** the **Activity Monitoring** section at the bottom lists **all** “scheduled but not reported” activities **across all objectives**, grouped objective‑wise. |
| **G3** | **No global “Ad‑hoc” summary** | Ad‑hoc activities are only shown inside “NOT SCHEDULED – REPORTED” per objective. A **standalone “Ad‑hoc activities (not in project plan)”** list across the report would make it easier for provincial to review them in one place. |
| **G4** | **Project plan not visible on the report view** | Provincial sees only what is in the report and the inline Activity Monitoring. There is **no** side panel, expandable block, or link to show the **project’s full objectives → activities → timeframes** for the report month. Comparison is entirely from memory or by opening the project in another tab. |
| **G5** | **Timeframe has no year** | `project_timeframes` stores only `month` (1–12). For multi‑year projects, “scheduled for March” cannot distinguish 2024 vs 2025. The code matches by `month === reportMonth`; this is consistent but limits precision. |
| **G6** | **Activity quality not monitored** | No checks for **empty or very short** `summary_activities`, `qualitative_quantitative_data`, or `intermediate_outcomes`. Provincial must scan manually. |
| **G7** | **Photos vs activities** | No monitoring such as “activity reported but no photo” or “photo not linked to any reported activity.” The logic would need business rules (e.g. “photos mandatory for activity X”). |

### 5.2 Budget (for project types with objectives, e.g. Development)

| # | Gap | Description |
|---|-----|-------------|
| **G8** | **Phase vs budget** | For Development/CCI/Rural-Urban-Tribal/NEXT PHASE, the Guide’s §9.6 mentions **phase consistency** (budget rows vs `current_phase` / phase). `getDevelopmentAndSimilarChecks` does **not** implement this; it only compares `total_beneficiaries` with the project target. |

### 5.3 Development Projects specifically (e.g. DP-0002-03)

| # | Gap | Description |
|---|-----|-------------|
| **G9** | **Type-specific only beneficiary-related** | For “Development Projects”, `type_specific_monitoring` only runs `getDevelopmentAndSimilarChecks` and `getBeneficiaryConsistencyChecks`. There are no LDP/IGE/RST/CIC-style blocks. The development block is light: mainly `total_beneficiaries` vs `Project.target_beneficiaries`. |
| **G10** | **No “project timeline” vs report month** | No display of the project’s **overall timeline** (e.g. start/end, phases) next to the report month, to help provincial assess whether the report month is within an active phase. |

### 5.4 Usability and workflow

| # | Gap | Description |
|---|-----|-------------|
| **G11** | **Monitoring only in “under review”** | Activity, budget, and type-specific monitoring are shown only when `status` is `submitted_to_provincial` or `forwarded_to_coordinator`. Provincial cannot use the same structured checks for **draft** or **reverted** reports when doing an early review. |
| **G12** | **No one‑page summary** | There is no single “Monitoring summary” (e.g. at the top of the page) with: report month, counts (scheduled–reported, scheduled–not reported, not scheduled–reported, ad‑hoc), budget alerts, and type-specific flags. Provincial must scroll through Objectives, Budget, and Project-Type sections. |
| **G13** | **“What did not happen” / “Why not” not tied to monitoring** | The Guide suggests: when an activity is “Scheduled – Not reported”, provincial should check “What did not happen” and “Why not” for that objective. **Partially addressed by the refined design (§4):** the **Activity Monitoring** section includes a note: "Check 'What did not happen' / 'Why not' for the relevant objective (if in the report), or consider reverting so the executor can add the objective and explain." |

---

## 6. Suggestions: What Could Be Included

### 6.1 Implement the refined design (§4) — primary

- **Inline badges** in `objectives.blade.php`: For each reported activity (with `hasUserFilledData()`), show **SCHEDULED – REPORTED** or **NOT SCHEDULED – REPORTED** next to the activity, using a helper or `getMonitoringPerObjective` / a new `getReportedActivityScheduleStatus` to determine which. Only for `provincial` / `coordinator` when status is `submitted_to_provincial` or `forwarded_to_coordinator`.
- **Remove** the current per‑objective Activity Monitoring block (SCHEDULED – REPORTED, SCHEDULED – NOT REPORTED, NOT SCHEDULED – REPORTED).
- **Activity Monitoring section** (new partial or block): **Only** “scheduled but not reported” activities, **objective‑wise**, placed **before the Add Comment section**. Use `getActivitiesScheduledButNotReported` or **`getActivitiesScheduledButNotReportedGroupedByObjective`** so the view can render by objective. Include a short note: “Check ‘What did not happen’ / ‘Why not’ for the relevant objective (if in the report), or consider reverting so the executor can add the objective and explain.” Show the section **only when the list is non‑empty**.
- **Effect:** Addresses **G1, G2** (scheduled but not reported, including when the whole objective is missing). The objective‑wise list covers both “objective in report but some activities skipped” and “objective missing from report, so all its scheduled activities are unreported”.

### 6.2 Project plan (objectives / activities / timeframes) on the report view

- **Option A — Collapsible “Project plan (report month)” in the report view:**  
  - Load `project->objectives.activities.timeframes` (already done in `show()`).  
  - Render a read‑only block: for the **report month**, list objectives and, under each, activities that have `ProjectTimeframe` with `month = reportMonth` and `is_active = 1`.  
  - Place it **above** the Objectives card or in a sidebar (if layout allows).
- **Option B — Link to project:**  
  - Add a clear “View project plan” link that opens the project’s objectives/activities/timeframes (e.g. in a new tab or in-app project show).  
- **Effect:** Reduces **G4**; makes it easier to compare plan vs report without leaving the page.

### 6.3 Activity quality checks

- **In `ReportMonitoringService`:** e.g. `getActivityQualityAlerts(DPReport $report): array`:
  - For each `DPActivity` with `hasUserFilledData()`, flag if `summary_activities` or `qualitative_quantitative_data` or `intermediate_outcomes` is empty or (e.g.) &lt; 20 characters.
  - Return `[activity_id, objective_id, activity name, missing_or_short: ['summary'|'qualitative'|'outcomes']]`.
- **In the view:** In the Activity Monitoring block (per objective or global), add a short list: “Activities with incomplete or very short Summary / Qualitative / Outcomes” with a link to the activity.
- **Effect:** Addresses **G6**; makes it easier to spot thin reporting.

### 6.4 Development Projects: phase and budget

- **In `ReportMonitoringService::getDevelopmentAndSimilarChecks` (or a dedicated method):**
  - Use `BudgetCalculationService::getBudgetsForReport` and project phase (e.g. `current_phase` or phase from `ProjectBudget`) to add alerts when:
    - Budget rows or amounts do not match the expected phase, or
    - Report’s `account_period` suggests a phase change that is not reflected in the SoA.
- **In the view:** `type_specific_monitoring` already has a “Development / CCI / Rural-Urban-Tribal / NEXT PHASE” block; extend it to show phase-related alerts.
- **Effect:** Partially closes **G8**.

### 6.5 One‑page monitoring summary

- **New partial, e.g. `monitoring_summary.blade.php`:**
  - One card at the **top** of the report view (below Basic Information, above type-specific and Objectives), only for `provincial` / `coordinator` and optionally only when status is under review (or always for provincial for drafts too).
  - Content:
    - Report month & year.
    - **Activity:** counts — Scheduled–Reported, Scheduled–Not reported, Not scheduled–Reported, Ad‑hoc (from `getMonitoringPerObjective` and/or the global methods).
    - **Budget:** utilisation %, and one‑line alerts (e.g. “Overspend”, “Negative balance”, “High utilisation”).
    - **Type-specific:** one line per project type with any alert (e.g. “LDP: no annexure”, “Beneficiary: type-specific ≠ total”).
  - Collapsible or always visible; avoid duplication with the detailed blocks below.
- **Effect:** Addresses **G12**; faster triage.

### 6.6 Optional: show monitoring for draft / reverted

- **Config or feature flag:** e.g. `config('reports.monitoring.show_for_draft_reverted', false)`.
- If `true`, show the same Activity, Budget, and Type-specific monitoring blocks also when status is `draft` or `reverted_by_provincial` / `reverted_by_coordinator`, so provincial can use them during an early or post‑revert review.
- **Effect:** Reduces **G11** for teams that want it.

### 6.7 Timeframe and year (G5)

- **Data model:** `project_timeframes` has no `year`. Adding a `year` (or `phase`) would require a migration and changes in project create/edit and in `ReportMonitoringService`.  
- **Short term:** Document in the Monitoring Guide and in `Updates.md` that “scheduled for report month” is **month-only**; for multi‑year projects, matching can be imprecise.  
- **Long term:** If the product needs year-aware scheduling, extend `project_timeframes` and the monitoring logic.

### 6.8 Photos vs activities (G7)

- **Option:** In `ReportMonitoringService`, add e.g. `getPhotoActivityAlerts(DPReport $report): array`:
  - Reported activities (with `hasUserFilledData()`) that have **no** linked `DPPhoto` → “Activity reported but no photo” (if business rules say photos are expected).
  - `DPPhoto` with `activity_id` not in any reported activity → “Photo linked to an activity not in this report” (could be legacy or data issue).
- **View:** New subsection under Activity Monitoring or under Photos.  
- **Effect:** Starts to address **G7** once business rules are fixed.

---

## 7. How Monitoring Can Be Made Easier for Provincial Users

### 7.1 Refined Activity Monitoring design (§4)

- **Inline badges** next to each reported activity (SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED) give an immediate signal without a separate block. Provincial sees compliance at a glance while reading the Objectives.
- **Activity Monitoring section** at the bottom (only “scheduled but not reported”, objective‑wise): one place to review **all** gaps for the month, including when the executor skipped an **entire objective**. The note points to “What did not happen” / “Why not” when the objective exists in the report. Show only when non‑empty.

### 7.2 At-a-glance

- **Monitoring summary at the top** (§6.5): One card with report month, activity counts, budget alerts, and type-specific one‑liners. Reduces scrolling and speeds up “approve / revert / ask for changes” decisions.

### 7.3 Plan vs report

- **Project plan for the report month** (§6.2): Visible in the same view (or one click away). Provincial can check “what was supposed to happen this month” without opening the project elsewhere.

### 7.4 Focus on problems

- **Highlight only when there is an issue:**  
  - **Activity Monitoring** section: show **only when** there is at least one “scheduled but not reported” activity.  
  - Budget and Type-specific blocks: only when there are alerts or non‑empty lists.  
  - For a **summary** (§6.5), if there are no alerts, a short “No monitoring alerts” line is enough.
- **Activity quality** (§6.3): Surfaces activities with weak Summary / Qualitative / Outcomes so provincial can revert or comment with a clear ask.

### 7.5 Development Projects (e.g. DP-0002-03)

- **Phase and budget** (§6.4): For Development/CCI/Rural-Urban-Tribal/NEXT PHASE, add phase-related checks so provincial can see phase vs budget mismatches in the same place as other type-specific alerts.
- **Project timeline** (§6.2): If the project has start/end or phase dates, showing them next to the report month helps provincial assess whether the report belongs to an active period.

### 7.6 When to show

- **Draft / reverted** (§6.6): Optional earlier use of the same monitoring for draft or reverted reports supports “review before submit” or “quick check after revert” without changing the main under-review workflow.

---

## 8. Suggested Implementation Order

| Priority | Item | Effort | Closes / helps |
|----------|------|--------|-----------------|
| **1** | **Refined Activity Monitoring (§4):** (a) Inline badges SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED next to each reported activity; (b) Remove per‑objective Activity Monitoring block; (c) **Activity Monitoring** section at the **bottom, before Add Comment**, only “scheduled but not reported”, **objective‑wise**, using `getActivitiesScheduledButNotReported` or `getActivitiesScheduledButNotReportedGroupedByObjective`; show only when non‑empty | Medium | G1, G2 |
| 2 | Collapsible “Project plan (report month)” or “View project plan” link | Medium | G4 |
| 3 | One‑page “Monitoring summary” card at the top | Medium | G12 |
| 4 | `getActivityQualityAlerts` + display | Medium | G6 |
| 5 | Phase vs budget in `getDevelopmentAndSimilarChecks` (or new method) | Medium | G8 |
| 6 | Optional: show monitoring for draft/reverted | Low | G11 |
| 7 | (Later) Photos vs activities; timeframe year | — | G7, G5 |

---

## 9. Files to Touch (for the refined design, §4 and priority 1 in §8)

- **Controller:** `app/Http/Controllers/Reports/Monthly/ReportController.php` — in `show()`: (a) pass `activitiesScheduledButNotReportedGroupedByObjective` (or `getActivitiesScheduledButNotReported` and group in the view); (b) pass data needed for **inline badges** (e.g. `getReportedActivityScheduleStatus` or a suitable slice of `getMonitoringPerObjective` keyed by `activity_id` with `scheduled_reported` / `not_scheduled_reported`).
- **View:** `resources/views/reports/monthly/show.blade.php` — include the **Activity Monitoring** partial **before the Add Comment section** (i.e. before `@include('reports.monthly.partials.comments')`). Pass the grouped “scheduled but not reported” list; render only when non‑empty and when role/status allows.
- **Objectives partial:** `resources/views/reports/monthly/partials/view/objectives.blade.php` — (a) **Remove** the current per‑objective “Activity Monitoring” block (SCHEDULED – REPORTED, SCHEDULED – NOT REPORTED, NOT SCHEDULED – REPORTED). (b) **Add** an inline badge (SCHEDULED – REPORTED or NOT SCHEDULED – REPORTED) next to each reported activity’s heading, using the status passed from the controller. Only for `provincial` / `coordinator` when status is `submitted_to_provincial` or `forwarded_to_coordinator`.
- **New partial:** `resources/views/reports/monthly/partials/view/activity_monitoring.blade.php` (or similar) — **Activity Monitoring** section: **only** “scheduled but not reported” activities, **grouped objective‑wise**. Structure: for each objective, list its activities. Include the note: “Check ‘What did not happen’ / ‘Why not’ for the relevant objective (if in the report), or consider reverting so the executor can add the objective and explain.” Same role/status rules; show only when the list is non‑empty.
- **Service:** `app/Services/ReportMonitoringService.php` — (a) ensure `getActivitiesScheduledButNotReported` returns `objective` so the view can group; or (b) add `getActivitiesScheduledButNotReportedGroupedByObjective(DPReport $report): array` returning `[ ['objective' => '...', 'objective_id' => '...', 'activities' => [ ['activity' => '...', 'activity_id' => '...'] ] ], ... ]`. (c) Add or reuse a method for **inline badges**: for each `DPActivity` with `hasUserFilledData()`, return `scheduled_reported` or `not_scheduled_reported` (keyed by `activity_id`). This can be derived from `getMonitoringPerObjective` or a dedicated `getReportedActivityScheduleStatus`.

---

## 10. References

- `Documentations/V1/Reports/MONITORING/Updates_Phase_Wise_Implementation_Plan.md` — **Phase-wise implementation plan for the refined Activity Monitoring design (§4).**
- `Documentations/V1/Reports/MONITORING/Provincial_Monthly_Report_Monitoring_Guide.md`
- `Documentations/V1/Reports/MONITORING/Provincial_Monthly_Report_Monitoring_Implementation_Plan.md`
- `Documentations/V1/Reports/Create/Activity_Store_Only_When_User_Filled_Phase_Wise_Implementation_Plan.md`
- `Documentations/V1/Reports/Create/Report_Create_DP-0002-02_Data_Flow_And_Tables.md`
- `app/Services/ReportMonitoringService.php`
- `app/Models/OldProjects/ProjectObjective.php`, `ProjectActivity.php`, `ProjectTimeframe.php`
- `app/Models/Reports/Monthly/DPObjective.php`, `DPActivity.php`

---

## 11. Implementation status (Refined design §4)

**Phase-wise plan:** `Updates_Phase_Wise_Implementation_Plan.md`

| Phase | Description | Status |
|-------|-------------|--------|
| 1 | Service: `getActivitiesScheduledButNotReportedGroupedByObjective`, `getReportedActivityScheduleStatus` | ✅ Done |
| 2 | Controller: pass `activitiesScheduledButNotReportedGroupedByObjective`, `reportedActivityScheduleStatus` to view | ✅ Done |
| 3 | Objectives partial: remove per‑objective Activity Monitoring block; add inline badges; pass `reportedActivityScheduleStatus` in `@include` | ✅ Done |
| 4 | Create `activity_monitoring.blade.php`; include in `show.blade.php` before Add Comment | ✅ Done |
| 5 | Integration, testing, documentation | ✅ Done |

**Manual testing:** As provincial/coordinator, view a report with status `submitted_to_provincial` or `forwarded_to_coordinator`. Check: (a) inline badges SCHEDULED – REPORTED / NOT SCHEDULED – REPORTED next to each reported activity; (b) Activity Monitoring section at the bottom (only when there are “scheduled but not reported” activities), grouped by objective; (c) per‑objective Activity Monitoring block is removed.

---

**End of Updates**
