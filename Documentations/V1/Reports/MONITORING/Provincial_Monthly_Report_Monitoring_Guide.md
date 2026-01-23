# Provincial Monthly Report Monitoring & Analysis Guide

**Audience:** Provincial users  
**Purpose:** Monitor and analyse monthly reports submitted by Executors/Applicants — especially **Objectives & Activities** and **Budget**  
**Version:** 1.0  
**Location:** `Documentations/V1/Reports/MONITORING/`

---

## 1. Introduction

This guide helps Provincial users:

1. **Objectives & Activities**
   - Identify activities **scheduled for the report month but not reported**.
   - Identify activities **not scheduled for the report month but still reported**.
   - Use existing **“What Did Not Happen”** and **“Why Not Happen”** fields for context.

2. **Budget**
   - Check **utilisation** (spent vs sanctioned).
   - Spot **overspend** (per row or total) and **negative balances**.
   - Assess whether spending aligns with the project and report period.

3. **Project-type-specific (Section 9)**
   - **LDP:** Annexure (impact) completeness, support dates, amounts vs budget.
   - **IGE:** Age profile (Grand Total, all age groups, consistency with `total_beneficiaries`).
   - **RST:** Trainee education totals and consistency.
   - **CIC:** Inmate profile (Grand Total, age categories, sub-totals).
   - **Individual (ILP, IAH, IES, IIES):** Beneficiary count, budget heads, contribution logic.
   - **Development, CCI, Rural-Urban-Tribal, NEXT PHASE:** Phase, `total_beneficiaries` vs project.
   - **All types:** Beneficiary consistency (report vs project, type-specific total vs `total_beneficiaries`).

It is based on the current **models, controllers, views, and JavaScript** used for projects and monthly reports.

---

## 2. Data Model — Objectives & Activities

### 2.1 Project (Plan)

| Model | Table | Key fields | Purpose |
|-------|-------|------------|---------|
| **ProjectObjective** | `project_objectives` | `objective_id`, `project_id`, `objective` | Project objective |
| **ProjectActivity** | `project_activities` | `activity_id`, `objective_id`, `activity`, `verification` | Activity under an objective |
| **ProjectTimeframe** | `project_timeframes` | `timeframe_id`, `activity_id`, **`month`**, **`is_active`** | When an activity is scheduled |

- **`ProjectTimeframe.month`:** 1–12 (January = 1, December = 12).
- **`ProjectTimeframe.is_active`:** 1 = that month is **scheduled** for the activity; 0 = not scheduled.
- **Relation:** `Project` → `objectives` → `activities` → `timeframes`.

**Relevant code:**
- `app/Models/OldProjects/ProjectObjective.php`
- `app/Models/OldProjects/ProjectActivity.php`
- `app/Models/OldProjects/ProjectTimeframe.php`
- `app/Models/OldProjects/Project.php` → `objectives()`

### 2.2 Report (What was submitted)

| Model | Table | Key fields | Purpose |
|-------|-------|------------|---------|
| **DPObjective** | `DP_Objectives` | `objective_id`, `report_id`, **`project_objective_id`**, `objective`, `not_happened`, `why_not_happened`, `changes`, `why_changes`, `lessons_learnt`, `todo_lessons_learnt` | Reported objective, linked to project |
| **DPActivity** | `DP_Activities` | `activity_id`, `objective_id`, **`project_activity_id`**, `activity`, **`month`**, `summary_activities`, `qualitative_quantitative_data`, `intermediate_outcomes` | Reported activity |

- **`DPObjective.project_objective_id`:** Links to `ProjectObjective.objective_id`.
- **`DPActivity.project_activity_id`:** Links to `ProjectActivity.activity_id` (empty for “Add Other Activity”).
- **`DPActivity.month`:** Reporting month (1–12) chosen by the executor for that activity.
- **`DPReport.report_month_year`:** Date (e.g. first of month) for the report.  
  - **Report month (1–12):** `\Carbon\Carbon::parse($report->report_month_year)->month`

**Relevant code:**
- `app/Models/Reports/Monthly/DPObjective.php`
- `app/Models/Reports/Monthly/DPActivity.php` (relation `timeframes` → `ProjectTimeframe` via `project_activity_id`)
- `app/Models/Reports/Monthly/DPReport.php`

### 2.3 How the report show view gets data

- **Report:** `DPReport::with(['objectives.activities.timeframes', 'accountDetails', ...])`
- **Project:** `Project::with(['user','budgets'])` — **objectives, activities, timeframes are not loaded** in `show()`.
- **Report month (int):** Can be derived as:  
  `$reportMonth = (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');`

For **automated** activity checks, the **Project** must also be loaded with:  
`$project->load(['objectives.activities.timeframes']);`

---

## 3. Activity Checks — Logic for Provincial Monitoring

Use the **report month** as the reference:  
`$reportMonth = (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');`

### 3.1 Check 1: Scheduled for this month but NOT reported

**Meaning:** In the **project plan**, the activity was scheduled for the report month, but there is **no** corresponding reported activity in this report.

**Logic (conceptual):**

```
For the report's project:
  Load: project.objectives.activities.timeframes (only where is_active = 1)

For each ProjectActivity in the project:
  scheduled_for_report_month = exists ProjectTimeframe
    where activity_id = ProjectActivity.activity_id
    and month = reportMonth
    and is_active = 1

  If scheduled_for_report_month:
    reported_in_this_report = exists DPActivity in this report
      where project_activity_id = ProjectActivity.activity_id

    If NOT reported_in_this_report:
      → ADD to list: "Scheduled for this month but NOT reported"
      → Store: Objective text, Activity text, Activity ID
```

**Important:**  
- Match by `project_activity_id` = `ProjectActivity.activity_id`.  
- If the executor used “Add Other Activity”, `project_activity_id` is empty; those do **not** satisfy “reported” for a **specific** project activity.  
- **DPObjective** has `not_happened` and `why_not_happened`. If an activity is in “Scheduled but not reported”, the Provincial should check whether it is explained there.

**Suggested display (when automated):**

- Table: Objective | Activity (from project) | Notes  
- Note: “Not reported this month. Check ‘What did not happen’ / ‘Why not’ for this objective.”

---

### 3.2 Check 2: NOT scheduled for this month but still reported

**Meaning:** The executor reported an activity that, in the **project plan**, was **not** scheduled for the report month.

**Logic (conceptual):**

```
For each DPActivity in the report where project_activity_id is NOT empty:

  Load ProjectActivity(project_activity_id) and its timeframes.

  scheduled_for_report_month = exists ProjectTimeframe
    where activity_id = DPActivity.project_activity_id
    and month = reportMonth
    and is_active = 1

  If NOT scheduled_for_report_month:
    → ADD to list: "Not scheduled for this month but reported"
    → Store: Objective, Activity, Reported month (DPActivity.month), Scheduled months (from timeframes)
```

**Edge:**  
- If `project_activity_id` is empty (“Add Other Activity”), the activity is **not** in the project plan. You can treat it as a separate category: **“Reported but not in project plan (ad‑hoc)”**.

**Suggested display (when automated):**

- Table: Objective | Activity | Reported for month | Planned months | Note  
- Note: “Planned for other months. Confirm if done in advance or reporting month chosen incorrectly.”

---

### 3.3 Check 3: Ad‑hoc activities (not in project plan)

**Meaning:** Executor used **“Add Other Activity”** — no `project_activity_id`, so it is not linked to any `ProjectActivity`.

**Logic (conceptual):**

```
For each DPActivity in the report:
  If project_activity_id is empty or null:
    → ADD to list: "Ad-hoc activity (not in project plan)"
    → Store: Activity text, Reported month, Objective (from DPObjective)
```

**Provincial use:**  
- Decide if these are acceptable (e.g. context‑driven) or if they should have been included in the project plan.

---

## 4. Where this logic can be implemented

### 4.1 Backend (recommended)

- **Service (new):** e.g. `App\Services\ReportMonitoringService` with methods:
  - `getActivitiesScheduledButNotReported(DPReport $report): array`
  - `getActivitiesReportedButNotScheduled(DPReport $report): array`
  - `getAdhocActivities(DPReport $report): array`
- **ReportController::show()** (and/or `ProvincialController::showReport` which delegates to it):
  - Load project:  
    `$project->load(['objectives.activities.timeframes']);`  
    (or pass `objectives.activities.timeframes` in the initial `Project::with(...)`.)
  - Derive:  
    `$reportMonth = (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');`
  - Call the service and pass:  
    `'activitiesScheduledNotReported'`, `'activitiesReportedNotScheduled'`, `'adhocActivities'` to the view.

### 4.2 Views

- **Show view:** `resources/views/reports/monthly/show.blade.php`
- **New partial (suggested):**  
  `resources/views/reports/monthly/partials/view/objectives_activity_monitoring.blade.php`  
  - Rendered only when the report is in a status the Provincial reviews (e.g. `submitted_to_provincial`, `forwarded_to_coordinator`) and when the monitoring arrays are present.
- **Existing objectives partial:**  
  `resources/views/reports/monthly/partials/view/objectives.blade.php`  
  - Keeps showing “What did not happen”, “Why not”, etc. The new block adds the **monitoring** summary above or below it.

### 4.3 JavaScript

- **Objectives/activities:**  
  `resources/views/reports/monthly/partials/create/objectives.blade.php` (inline) and `resources/views/reports/monthly/partials/edit/objectives.blade.php`  
  - Used for **create/edit** (executor). For **Provincial monitoring** we only need **read‑only** display of the three lists; no new JS is required if the backend fills the arrays and the partial only loops and displays them.

---

## 5. Data Model — Budget

### 5.1 Project budget

- **Source:** Depends on project type.  
  - E.g. Development Projects: `ProjectBudget` (`project_budgets`: `particular`, `this_phase`, `phase`, …).  
  - Others: `BudgetCalculationService::getBudgetsForReport($project)` and project‑type‑specific strategies.
- **Output:** Rows with a **particular** (or equivalent) and an **amount_sanctioned** (or `this_phase`).

**Relevant:**  
- `app/Models/OldProjects/ProjectBudget.php`  
- `app/Services/Budget/BudgetCalculationService.php`  
- `config('budget.field_mappings')` and strategy classes.

### 5.2 Report budget (Statements of Account)

| Model | Table | Key fields |
|-------|-------|------------|
| **DPAccountDetail** | `DP_AccountDetails` | `report_id`, **`particulars`**, `amount_sanctioned`, `amount_forwarded`, `total_amount`, `expenses_last_month`, `expenses_this_month`, **`total_expenses`**, **`balance_amount`**, `is_budget_row` |

- **`particulars`:** Matches the budget head (from project/`getBudgetsForReport`); for “Add Additional Expense Row” it can be free text.
- **`is_budget_row`:** 1 = from project budget; 0 = additional row.
- **`total_expenses`** = `expenses_last_month` + `expenses_this_month` (in current JS).
- **`balance_amount`** = `total_amount` − `total_expenses` (per row).

**Relevant:**  
- `app/Models/Reports/Monthly/DPAccountDetail.php`  
- `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php` (create/edit)  
- `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php` (view)  
- `resources/views/reports/monthly/partials/view/statements_of_account.blade.php` (dispatcher by `project_type`)

### 5.3 View logic (already in place)

- **View:** Total budget, total expenses, approved vs unapproved, remaining, utilisation % and progress bar.  
- **JS (create/edit):** `calculateRowTotals`, `calculateTotal`, `updateBudgetSummaryCards`, `updateBalanceColor` (turns red when balance &lt; 0).

---

## 6. Budget Checks — Logic for Provincial Monitoring

### 6.1 Check 1: Per‑row overspend

**Meaning:** For a budget row, **total_expenses > amount_sanctioned** (or `total_amount` when it represents the sanctioned amount for that row).

**Logic (conceptual):**

```
For each DPAccountDetail in the report where is_budget_row = 1:
  If (total_expenses > amount_sanctioned) or (total_expenses > total_amount):
    → ADD to list: "Overspend on budget head"
    → Store: particulars, amount_sanctioned, total_expenses, excess
```

**Note:** Some flows use `total_amount` = `amount_sanctioned`; confirm in your `statements_of_account` logic which to use. The idea is: spending **above** the sanctioned amount for that head.

---

### 6.2 Check 2: Negative balance (per row or total)

**Meaning:** `balance_amount < 0` for a row, or total balance &lt; 0.

**Logic (conceptual):**

```
For each DPAccountDetail:
  If balance_amount < 0:
    → ADD to list: "Negative balance for head"
    → Store: particulars, balance_amount

If sum(balance_amount) < 0:
  → Flag: "Overall balance is negative"
```

The existing JS `updateBalanceColor` already highlights negative balances in red in create/edit. The view can show total balance; the same check can be done in a service for the monitoring block.

---

### 6.3 Check 3: Overall utilisation

**Meaning:** Total expenses vs total sanctioned (at report or project level).

**Logic (conceptual):**

```
total_sanctioned = report.amount_sanctioned_overview
  OR sum(DPAccountDetail.amount_sanctioned) for budget rows (depending on what is authoritative).

total_expenses = sum(DPAccountDetail.total_expenses) for this report.

project_total_expenses = sum of total_expenses across all APPROVED reports of the project (and optionally + this report if pending).

Utilisation = (project_total_expenses / total_sanctioned) * 100
```

**Provincial use:**
- &gt; 90%: high; confirm no overspend and that remaining months are planned.
- &gt; 100%: overspend at project level (if total_sanctioned is the cap).
- Very low with many months elapsed: possible under‑utilisation; discuss with executor.

The **view** `development_projects.blade.php` (view) already computes utilisation and approved/unapproved; a **monitoring** block can reuse or mirror that and add short “alert” messages (e.g. “Utilisation &gt; 90%”, “Negative balance on one or more heads”).

---

### 6.4 Check 4: Expenses vs report period

**Meaning:** `expenses_this_month` should be coherent with the report month (and possibly `account_period_start` / `account_period_end`).  
- No automatic “schedule” for spending per month; this is a **reasonableness** check: e.g. very large `expenses_this_month` compared to previous months or to sanctioned, might warrant a question.

**Logic (optional, simple):**

```
For this report:
  this_month = sum(DPAccountDetail.expenses_this_month)
  last_month = sum(DPAccountDetail.expenses_last_month)

If this_month >> last_month (e.g. > 2x or 3x) and this_month is large in absolute terms:
  → Flag: "Unusually high spend this month vs last month — please confirm."
```

---

## 7. Where budget checks can be implemented

### 7.1 Backend

- In **ReportMonitoringService** (or a `ReportBudgetMonitoringService`):
  - `getBudgetOverspendRows(DPReport $report): array`
  - `getNegativeBalanceRows(DPReport $report): array`
  - `getBudgetUtilisationSummary(DPReport $report): array`  
    (total_sanctioned, total_expenses, utilisation %, and a simple “alert” list: e.g. `['high_utilization','negative_balance','overspend_row']`.)

- **ReportController::show()** (or Provincial proxy):
  - Call these and pass to the view:  
    `'budgetOverspendRows'`, `'budgetNegativeBalanceRows'`, `'budgetUtilisation'`.

### 7.2 Views and JS

- **View (read‑only):**  
  - `view/statements_of_account/development_projects.blade.php` (and other project‑type view partials) already show totals and per‑row data.  
  - A **new** partial, e.g. `view/budget_monitoring.blade.php`, can:
    - Loop `budgetOverspendRows` and `budgetNegativeBalanceRows`.
    - Show `budgetUtilisation` and short messages (e.g. “Utilisation above 90%”, “Negative balance on one or more heads”).
- **JS:** No extra JS needed for read‑only monitoring; create/edit JS remains as is.

---

## 8. Manual Checklist for Provincial (before or without automation)

Use this when the automated “Monitoring” block is not yet implemented, or to double‑check.

### 8.1 Objectives and activities

1. **Report month**  
   - Note: “Report Month & Year” in Basic Information (e.g. “May 2024” → month = 5).

2. **Project plan**  
   - Open the **Project** (from project_id) and go to the **Objectives / Activities / Timeframes** section.  
   - For the report month (e.g. 5), list activities where the **scheduled months** include May.

3. **Scheduled but not reported**  
   - For each such activity: Is there a **reported activity** in this report that clearly refers to it?  
   - If **no**: note as “Scheduled for this month but not reported.”  
   - Check the objective’s **“What did not happen”** and **“Why some activities could not be undertaken”** for explanation.

4. **Reported but not scheduled**  
   - For each **reported activity** in the report:  
     - If it is linked to a project activity (you can infer from the text or, when available, `project_activity_id`):  
       - In the project, is the **report month** in that activity’s scheduled months?  
     - If **no**: note as “Reported but not scheduled for this month.”  
   - For activities that look **new** (e.g. “Add Other Activity”): note as “Ad‑hoc; not in project plan.”

5. **Quality**  
   - Are **Summary of activities**, **Qualitative & quantitative data**, and **Intermediate outcomes** adequately filled for reported activities?

---

### 8.2 Budget

1. **Overall**  
   - Total budget (Amount Sanctioned), Total expenses, Remaining balance, Utilization % (from the Budget Summary / progress bar).  
   - Is utilisation consistent with the project’s phase and the report period?

2. **Per row (Budget details table)**  
   - For each **budget head** (Particulars):  
     - `Total expenses` vs `Amount sanctioned` (or `Total amount` if that’s the cap): any **overspend**?  
     - `Balance amount`: any **negative**?

3. **Additional rows**  
   - “Add Additional Expense Row”: do the **Particulars** and amounts look justified and within the overall project scope?

4. **Consistency**  
   - `Expenses this month` vs previous months: any **unusually large** jump? If yes, consider asking for a short justification.

---

### 8.3 Project-type-specific (manual)

- **LDP:** Is the Annexure (impact) block filled? At least one impact? Support date within the report period? Amount sanctioned and impact text present?
- **IGE:** In Age profile: is there a Grand Total? All four age groups? Does Grand Total (present academic year) match Total beneficiaries in basic info?
- **RST:** In Trainees: is the Total = sum of education categories? Does it match Total beneficiaries?
- **CIC:** In Inmates: is there a Grand Total? All four age categories? Do sub-totals add up?
- **Individual:** Is Total beneficiaries = 1? Do budget rows match what you expect for that individual type (ILP/IAH/IES/IIES)?
- **All:** Does Total beneficiaries look consistent with the project and with any type-specific total (Annexure count, Grand Total, Trainee total, Inmate total)?

---

## 9. Project-Type-Specific Monitoring (Additional Checks)

The following checks apply **only** when the report’s `project_type` matches. Use them in addition to the common **Objectives & Activities** and **Budget** checks.

---

### 9.1 Livelihood Development Projects (LDP)

**Report section:** Annexure — *Project’s impact in the life of the beneficiaries*  
**Model:** `QRDLAnnexure` (`qrdl_annexure`)  
**Relation:** `DPReport::annexures`

| Field | Purpose |
|-------|---------|
| `dla_beneficiary_name` | Name of beneficiary |
| `dla_support_date` | Date support was given |
| `dla_self_employment` | Nature of self-employment |
| `dla_amount_sanctioned` | Amount sanctioned for this beneficiary |
| `dla_monthly_profit`, `dla_annual_profit` | Profits |
| `dla_impact` | Project’s impact |
| `dla_challenges` | Challenges faced |

**Monitoring checks:**

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Annexure present** | For LDP, `report->annexures` must be loaded. If count = 0, flag: “LDP report has no impact annexure entries.” | Ensure at least one impact is reported where expected. |
| **Support date in report period** | `dla_support_date` within `report->account_period_start` and `account_period_end` (or at least in the report month). If outside, flag: “Support date outside account period.” | Verify timing of reported support. |
| **Empty impact or amount** | If `dla_impact` is empty or `dla_amount_sanctioned` is 0 or null for an annexure row → “Incomplete impact entry.” | Request completion. |
| **Sum of `dla_amount_sanctioned` vs budget** | Sum(annexures.`dla_amount_sanctioned`) can be compared with LDP-specific budget heads (e.g. livelihood support) in `DPAccountDetail`—if such a head exists. Large mismatch → “Annexure amounts vs budget head — please confirm.” | Reasonableness only; mapping by head may need business rules. |
| **Project LDP target group (optional)** | Project: `ProjectLDPTargetGroup`. Count of target-group beneficiaries vs count of annexure entries. Not a strict match (support can be staggered) but big gap → “Number of impact entries vs project target group — please confirm.” | Context for scale of reporting. |

**Views:** `partials/view/LivelihoodAnnexure.blade.php`, `partials/create/LivelihoodAnnexure.blade.php`, `partials/edit/LivelihoodAnnexure.blade.php`  
**Controller:** `LivelihoodAnnexureController`

---

### 9.2 Institutional Ongoing Group Educational proposal (IGE)

**Report section:** Age profile of children in the institution  
**Model:** `RQISAgeProfile` (`rqis_age_profiles`)  
**Relation:** `DPReport::rqis_age_profile`

| Field | Purpose |
|-------|---------|
| `age_group` | One of: *Children below 5 years*, *Children between 6 to 10 years*, *Children between 11 to 15 years*, *16 and above*, *All Categories* |
| `education` | e.g. Bridge course, Primary school, Secondary school, Higher secondary, Total, Grand Total |
| `up_to_previous_year` | Count up to previous year |
| `present_academic_year` | Count in present academic year |

**Monitoring checks:**

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Grand Total present** | `age_group = 'All Categories'` and `education = 'Grand Total'`. If missing → “Age profile: Grand Total missing.” | Ensure overall count is reported. |
| **All four age groups** | At least one row for each: *Children below 5 years*, *Children between 6 to 10 years*, *Children between 11 to 15 years*, *16 and above*. If any missing → “Age profile: missing age group &lt;name&gt;.” | Completeness. |
| **Grand Total vs `total_beneficiaries`** | Grand Total `present_academic_year` vs `report->total_beneficiaries`. If both present and different, flag: “Grand Total (present academic year) ≠ Total beneficiaries in basic info.” | Consistency. |
| **Grand Total vs project (optional)** | Project: `ProjectIGEInstitutionInfo.previous_year_beneficiaries`, `ProjectIGENewBeneficiaries`, `ProjectIGEOngoingBeneficiaries`. Compare `up_to_previous_year` / `present_academic_year` with project figures where comparable. | Context. |
| **Sub-totals** | For each age group, a row with `education = 'Total'`. Check that sum of education rows (excluding Total) ≈ Total for that group. | Arithmetic consistency. |

**Views:** `partials/view/institutional_ongoing_group.blade.php`, `partials/create/institutional_ongoing_group.blade.php`, `partials/edit/institutional_ongoing_group.blade.php`  
**Controller:** `InstitutionalOngoingGroupController`

---

### 9.3 Residential Skill Training Proposal 2 (RST)

**Report section:** Information about the trainees  
**Model:** `RQSTTraineeProfile` (`rqst_trainee_profile`)  
**Stored as:** `report->education` (array) built in `ReportController::show()` from `rqst_trainee_profile`: `below_9`, `class_10_fail`, `class_10_pass`, `intermediate`, `above_intermediate`, `other`, `other_count`, `total`.

**Monitoring checks:**

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Trainee total present** | `education['total']` exists and is numeric. If 0 and project expects trainees → “RST: trainee total is 0.” | Completeness. |
| **Total = sum of categories** | `total` = below_9 + class_10_fail + class_10_pass + intermediate + above_intermediate + other_count. If mismatch → “RST: trainee total does not match sum of education categories.” | Arithmetic. |
| **Total vs `total_beneficiaries`** | `education['total']` vs `report->total_beneficiaries`. If both set and different → “Trainee total ≠ Total beneficiaries.” | Consistency. |
| **Total vs project (optional)** | Project: `ProjectRSTTargetGroup.tg_no_of_beneficiaries` (sum) or `ProjectRSTInstitutionInfo.beneficiaries_last_year`. Compare with `total` or `up_to_previous_year` equivalents if available. | Context. |
| **All categories present** | Each of: Below 9th standard, 10th class failed, 10th class passed, Intermediate, Intermediate and above, (Other). Missing or null → treat as 0; flag only if *all* are 0 but total &gt; 0. | Data quality. |

**Views:** `partials/view/residential_skill_training.blade.php`, `partials/create/residential_skill_training.blade.php`, `partials/edit/residential_skill_training.blade.php`  
**Controller:** `ResidentialSkillTrainingController`

---

### 9.4 PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (CIC)

**Report section:** Profile of inmates for the last four months  
**Model:** `RQWDInmatesProfile` (`rqwd_inmates_profiles`)  
**Relation:** `DPReport::rqwd_inmate_profile`

| Field | Purpose |
|-------|---------|
| `age_category` | *Children below 18 yrs*, *Women between 18 – 30 years*, *Women between 31 – 50 years*, *Women above 50*, *All Categories* |
| `status` | unmarried, married, divorcee, deserted, total, or other |
| `number` | Count |
| `total` | Sub-total for the category |

**Monitoring checks:**

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Grand Total present** | Row(s) with `age_category = 'All Categories'` and `status = 'total'` (or equivalent). Sum or `total` = Grand Total. If missing → “CIC: inmate Grand Total missing.” | Completeness. |
| **All four age categories** | At least one row per: *Children below 18 yrs*, *Women between 18 – 30 years*, *Women between 31 – 50 years*, *Women above 50*. If any missing → “CIC: missing age category &lt;name&gt;.” | Completeness. |
| **Sub-totals vs category sum** | For each age category, sum of `number` over status rows (excluding Total) ≈ `total` for that category. If not → “CIC: sub-total mismatch for &lt;age category&gt;.” | Arithmetic. |
| **Grand Total vs `total_beneficiaries`** | Grand Total vs `report->total_beneficiaries`. If both set and different → “Inmate Grand Total ≠ Total beneficiaries.” | Consistency. |

**Views:** `partials/view/crisis_intervention_center.blade.php`, `partials/create/crisis_intervention_center.blade.php`, `partials/edit/crisis_intervention_center.blade.php`  
**Controller:** `CrisisInterventionCenterController`

---

### 9.5 Individual Types (ILP, IAH, IES, IIES)

**Individual - Livelihood Application (ILP), Individual - Access to Health (IAH), Individual - Initial - Educational support (IIES), Individual - Ongoing Educational support (IES)**

- **Budget:**  
  - ILP: `ProjectILPBudget` — `budget_desc`, `cost`, `beneficiary_contribution` (SingleSourceContribution).  
  - IAH: `ProjectIAHBudgetDetails` — `particular`, `amount`, `family_contribution` (SingleSourceContribution).  
  - IIES: `ProjectIIESExpenses` + `expenseDetails` — `iies_particular`, `iies_amount`, contribution from `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution` (MultipleSourceContribution).  
  - IES: `ProjectIESExpenses` + `expenseDetails` — `particular`, `amount`, contribution from `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution` (MultipleSourceContribution).  
- **Statements of account:** `individual_livelihood`, `individual_health`, `individual_education`, `individual_ongoing_education` — structure mirrors `development_projects` (particulars, amount sanctioned, expenses, balance) but with project-type-specific budget sources.

**Monitoring checks:**

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Total beneficiaries** | For individual projects, `report->total_beneficiaries` is usually 1. If not 1 and project is single-beneficiary → “Individual project: total beneficiaries is not 1.” | Consistency. |
| **Budget rows match project** | `BudgetCalculationService::getBudgetsForReport($project)` gives expected rows. Compare `DPAccountDetail.particulars` (or mapped labels) with expected. Missing or extra heads → “Budget heads do not match project type structure.” | Correct application of ILP/IAH/IIES/IES budget. |
| **Contribution vs amount** | For ILP/IAH: amount_sanctioned = cost/amount − contribution. For IIES/IES: amount from MultipleSourceContribution. If `DPAccountDetail.amount_sanctioned` is inconsistent with project-type logic → “Sanctioned amount vs contribution — please confirm.” | Budget logic. |
| **No duplicate particulars** | Duplicate `particulars` in `DPAccountDetail` for same report → “Duplicate budget head.” | Data quality. |

**Views:** `partials/view/statements_of_account/individual_livelihood.blade.php` (and edit/create), same for `individual_health`, `individual_education`, `individual_ongoing_education`.  
**Config:** `config/budget.php` → `field_mappings` for each individual type.

---

### 9.6 Development Projects, CHILD CARE INSTITUTION, Rural-Urban-Tribal, NEXT PHASE - DEVELOPMENT PROPOSAL

- **Budget:** `ProjectBudget` — `particular`, `this_phase`, `phase` (DirectMapping, phase-based).  
- **Statements of account:** `development_projects` (view/edit/create).  
- **No extra report section** beyond Objectives, Activities, Outlook, Statements, Photos, Attachments.

**Monitoring checks (in addition to Sections 5–7):**

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Phase consistency** | Budget rows from `getBudgetsForReport` use `current_phase` or max(phase). If report’s `account_period` or project dates imply a phase change, confirm `amount_sanctioned` in rows aligns with the intended phase. | Phase-based budget correctness. |
| **Total beneficiaries vs project** | `Project.target_beneficiaries` (text or number). If numeric and `report->total_beneficiaries` is set, large difference → “Total beneficiaries vs project target — please confirm.” | Consistency. |
| **Beneficiary trend (optional)** | Compare `total_beneficiaries` with previous report(s) for the project. Sudden large change → “Noticeable change in total beneficiaries — please confirm.” | Trend. |

**Config:** `config/budget.php` → `field_mappings` for these types.

---

### 9.7 Beneficiary Consistency (All Project Types)

| Check | Logic | Provincial use |
|-------|--------|----------------|
| **Report `total_beneficiaries` vs project** | If `Project.target_beneficiaries` is numeric or can be parsed: `report->total_beneficiaries` vs that. For individual types, typically 1. For institutional/group types, use type-specific totals (e.g. IGE Grand Total, RST trainee total, CIC Grand Total) where available. | Consistency with project and type. |
| **Type-specific total vs `total_beneficiaries`** | IGE: Grand Total present_academic_year; RST: education total; CIC: inmate Grand Total; LDP: can use annexure count as a proxy. If type-specific total exists and differs from `total_beneficiaries` → “Type-specific count ≠ Total beneficiaries.” | Cross-check within report. |

---

### 9.8 Where Project-Type-Specific Logic Can Be Implemented

- **ReportMonitoringService (or a dedicated service):**  
  - `getLdpAnnexureChecks(DPReport $report): array`  
  - `getIgeAgeProfileChecks(DPReport $report): array`  
  - `getRstTraineeChecks(DPReport $report): array`  
  - `getCicInmateChecks(DPReport $report): array`  
  - `getIndividualBudgetChecks(DPReport $report, Project $project): array`  
  - `getBeneficiaryConsistencyChecks(DPReport $report, Project $project): array`  

- **Controller:** In `show()`, load type-specific relations when needed:  
  - LDP: `report->annexures`  
  - IGE: `report->rqis_age_profile`  
  - RST: `report->rqst_trainee_profile` (and build `education` if not on `report`)  
  - CIC: `report->rqwd_inmate_profile`  

  Call the above methods only when `report->project_type` matches, and pass the results into the view (e.g. `typeSpecificChecks` or separate vars).

- **View:** In `show.blade.php` or a monitoring partial, add a **“Project-type checks”** block that:
  - Renders only when `report->project_type` is one of LDP, IGE, RST, CIC, or Individual types.
  - Shows tables or bullets for each check that has a flag (e.g. “LDP: no annexure”, “IGE: Grand Total missing”, “RST: total ≠ sum of categories”, “CIC: missing age category”, “Individual: total_beneficiaries ≠ 1”, “Beneficiary: type-specific ≠ total_beneficiaries”).

---

### 9.9 Models and Config for Project-Type-Specific Monitoring

| Project type | Report models / data | Project models / config |
|--------------|----------------------|--------------------------|
| Livelihood Development Projects | `QRDLAnnexure` | `ProjectLDPTargetGroup`, `config/budget` (DirectMapping, ProjectBudget) |
| Institutional Ongoing Group Educational proposal | `RQISAgeProfile` | `ProjectIGEInstitutionInfo`, `ProjectIGENewBeneficiaries`, `ProjectIGEOngoingBeneficiaries`, `ProjectIGEBudget` |
| Residential Skill Training Proposal 2 | `RQSTTraineeProfile`, `report->education` | `ProjectRSTTargetGroup`, `ProjectRSTInstitutionInfo`, `ProjectBudget` |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | `RQWDInmatesProfile` | `ProjectCICBasicInfo`, `ProjectBudget` |
| Individual - Livelihood Application | `DPAccountDetail` | `ProjectILPBudget` |
| Individual - Access to Health | `DPAccountDetail` | `ProjectIAHBudgetDetails` |
| Individual - Initial - Educational support | `DPAccountDetail` | `ProjectIIESExpenses` + `expenseDetails` |
| Individual - Ongoing Educational support | `DPAccountDetail` | `ProjectIESExpenses` + `expenseDetails` |
| Development Projects, CHILD CARE INSTITUTION, Rural-Urban-Tribal, NEXT PHASE | `DPAccountDetail` | `ProjectBudget`, `Project.target_beneficiaries` |

---

## 10. Files Reference

### 10.1 Models

| File | Role |
|------|------|
| `app/Models/OldProjects/Project.php` | `objectives()`, `target_beneficiaries` |
| `app/Models/OldProjects/ProjectObjective.php` | `activities()` |
| `app/Models/OldProjects/ProjectActivity.php` | `timeframes()` |
| `app/Models/OldProjects/ProjectTimeframe.php` | `month`, `is_active` |
| `app/Models/OldProjects/ProjectBudget.php` | Budget rows (e.g. `particular`, `this_phase`) |
| `app/Models/Reports/Monthly/DPReport.php` | `report_month_year`, `total_beneficiaries`, `objectives`, `accountDetails`, `annexures`, `rqis_age_profile`, `rqst_trainee_profile`, `rqwd_inmate_profile` |
| `app/Models/Reports/Monthly/DPObjective.php` | `project_objective_id`, `not_happened`, `why_not_happened`, `activities` |
| `app/Models/Reports/Monthly/DPActivity.php` | `project_activity_id`, `month`, `timeframes` (→ ProjectTimeframe) |
| `app/Models/Reports/Monthly/DPAccountDetail.php` | `particulars`, `amount_sanctioned`, `total_expenses`, `balance_amount`, `is_budget_row` |
| `app/Models/Reports/Monthly/QRDLAnnexure.php` | LDP: `dla_beneficiary_name`, `dla_support_date`, `dla_amount_sanctioned`, `dla_impact`, etc. |
| `app/Models/Reports/Monthly/RQISAgeProfile.php` | IGE: `age_group`, `education`, `up_to_previous_year`, `present_academic_year` |
| `app/Models/Reports/Monthly/RQSTTraineeProfile.php` | RST: `education_category`, `number` |
| `app/Models/Reports/Monthly/RQWDInmatesProfile.php` | CIC: `age_category`, `status`, `number`, `total` |
| `app/Models/OldProjects/LDP/ProjectLDPTargetGroup.php` | LDP project: target group |
| `app/Models/OldProjects/IGE/ProjectIGEInstitutionInfo.php` | IGE: `previous_year_beneficiaries` |
| `app/Models/OldProjects/IGE/ProjectIGENewBeneficiaries.php`, `ProjectIGEOngoingBeneficiaries.php` | IGE: beneficiary lists |
| `app/Models/OldProjects/RST/ProjectRSTTargetGroup.php` | RST: `tg_no_of_beneficiaries` |
| `app/Models/OldProjects/RST/ProjectRSTInstitutionInfo.php` | RST: `beneficiaries_last_year` |
| `app/Models/OldProjects/ILP/ProjectILPBudget.php` | ILP: `budget_desc`, `cost`, `beneficiary_contribution` |
| `app/Models/OldProjects/IAH/ProjectIAHBudgetDetails.php` | IAH: `particular`, `amount`, `family_contribution` |
| `app/Models/OldProjects/IIES/ProjectIIESExpenses.php`, `ProjectIESExpenses.php` | IIES/IES: multi-source contribution |

### 10.2 Controllers

| File | Methods | Note |
|------|---------|------|
| `app/Http/Controllers/Reports/Monthly/ReportController.php` | `show()`, `create()`, `store()`, `edit()`, `update()` | show does not load `project.objectives.activities.timeframes` |
| `app/Http/Controllers/ProvincialController.php` | `showReport()` | Delegates to `ReportController::show()` |
| `app/Http/Controllers/Reports/Monthly/LivelihoodAnnexureController.php` | LDP annexure |
| `app/Http/Controllers/Reports/Monthly/InstitutionalOngoingGroupController.php` | IGE age profile |
| `app/Http/Controllers/Reports/Monthly/ResidentialSkillTrainingController.php` | RST trainee profile |
| `app/Http/Controllers/Reports/Monthly/CrisisInterventionCenterController.php` | CIC inmate profile |

### 10.3 Views

| File | Role |
|------|------|
| `resources/views/reports/monthly/show.blade.php` | Main report show; includes objectives, statements_of_account, type-specific sections, etc. |
| `resources/views/reports/monthly/partials/view/objectives.blade.php` | Objectives and activities (read‑only) |
| `resources/views/reports/monthly/partials/create/objectives.blade.php` | Create: objectives, activities, timeframes, “Reporting Month”, “Add Other Activity”; inline JS |
| `resources/views/reports/monthly/partials/edit/objectives.blade.php` | Edit: same structure as create |
| `resources/views/reports/monthly/partials/view/statements_of_account.blade.php` | Dispatcher by `project_type` |
| `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php` | Budget summary, table, utilisation |
| `resources/views/reports/monthly/partials/view/statements_of_account/individual_*.blade.php` | Individual types: budget summary, table |
| `resources/views/reports/monthly/partials/statements_of_account/development_projects.blade.php` | Create/edit: account rows, `calculateRowTotals`, `updateBudgetSummaryCards`, etc. |
| `resources/views/reports/monthly/partials/view/LivelihoodAnnexure.blade.php` | LDP: impact annexure (read‑only) |
| `resources/views/reports/monthly/partials/view/institutional_ongoing_group.blade.php` | IGE: age profile (read‑only) |
| `resources/views/reports/monthly/partials/view/residential_skill_training.blade.php` | RST: trainee education (read‑only) |
| `resources/views/reports/monthly/partials/view/crisis_intervention_center.blade.php` | CIC: inmate profile (read‑only) |

### 10.4 JS (inline in blades)

| Location | Functions | Role |
|----------|-----------|------|
| `partials/create/objectives.blade.php` | `toggleObjectiveCard`, `toggleActivityCard`, `addActivity`, `removeActivity`, `reindexActivities`, `updateActivityStatus` | Activity cards, indexing, status |
| `partials/statements_of_account/development_projects.blade.php` | `calculateRowTotals`, `calculateTotal`, `updateBalanceColor`, `updateAllBalanceColors`, `addAccountRow`, `removeAccountRow`, `reindexAccountRows`, `updateBudgetSummaryCards` | Budget rows and summary |
| `partials/create/LivelihoodAnnexure.blade.php` | `dla_addImpactGroup`, `dla_removeImpactGroup`, `dla_updateImpactGroupIndexes` | LDP impact groups |
| `partials/create/institutional_ongoing_group.blade.php` | `calculateAgeTotals` | IGE age profile |
| `partials/create/residential_skill_training.blade.php` | `updateCounts` | RST trainee total |
| `partials/create/crisis_intervention_center.blade.php` | `updateCounts` | CIC inmate totals |

### 10.5 Services and config

| File | Role |
|------|------|
| `app/Services/Budget/BudgetCalculationService.php` | `getBudgetsForReport($project)` — budget rows by project type |
| `config/budget.php` | `field_mappings` per project type: DirectMapping (ProjectBudget, ProjectIGEBudget), SingleSourceContribution (ILP, IAH), MultipleSourceContribution (IIES, IES) |

---

## 11. Implementation Outline (for developers)

To add a **Provincial monitoring** block to the report show page:

1. **Service**  
   - Create `ReportMonitoringService` with:
     - **Common:** `getActivitiesScheduledButNotReported(DPReport $report)`, `getActivitiesReportedButNotScheduled(DPReport $report)`, `getAdhocActivities(DPReport $report)`, `getBudgetOverspendRows(DPReport $report)`, `getNegativeBalanceRows(DPReport $report)`, `getBudgetUtilisationSummary(DPReport $report)`.
     - **Project-type-specific (Section 9):** `getLdpAnnexureChecks(DPReport $report)`, `getIgeAgeProfileChecks(DPReport $report)`, `getRstTraineeChecks(DPReport $report)`, `getCicInmateChecks(DPReport $report)`, `getIndividualBudgetChecks(DPReport $report, Project $project)`, `getBeneficiaryConsistencyChecks(DPReport $report, Project $project)`.

2. **Controller**  
   - In `ReportController::show()` (or the Provincial path that uses it):
     - Ensure `$project->load(['objectives.activities.timeframes'])` (or equivalent).
     - `$reportMonth = (int) \Carbon\Carbon::parse($report->report_month_year)->format('n');`
     - Call the common service methods and pass the 6 results into the view.
     - For project-type-specific checks: load `report->annexures` (LDP), `report->rqis_age_profile` (IGE), `report->rqst_trainee_profile` (RST), `report->rqwd_inmate_profile` (CIC) as needed; call the type-specific methods when `report->project_type` matches, and pass `typeSpecificChecks` (or similar) into the view.

3. **View**  
   - In `show.blade.php`, after Objectives and before or after Statements of Account, add:
     - `@include('reports.monthly.partials.view.objectives_activity_monitoring', [...])`
     - `@include('reports.monthly.partials.view.budget_monitoring', [...])`
     - `@include('reports.monthly.partials.view.type_specific_monitoring', ['typeSpecificChecks' => $typeSpecificChecks ?? []])` — only when `typeSpecificChecks` is non-empty or when `report->project_type` is one of LDP, IGE, RST, CIC, or Individual.
   - Optionally show only for `provincial` (and `coordinator` if desired) and when status is under review.

4. **Partials**  
   - `objectives_activity_monitoring.blade.php`: three tables/lists (scheduled not reported, reported not scheduled, ad‑hoc) and short instructions.  
   - `budget_monitoring.blade.php`: overspend rows, negative balance rows, utilisation summary and one‑line alerts.  
   - `type_specific_monitoring.blade.php`: project-type-specific checks (Section 9)—LDP annexure, IGE age profile, RST trainees, CIC inmates, Individual budget/beneficiary, beneficiary consistency—rendered when the report’s project type has checks.

**Phase-wise implementation plan:** See `Provincial_Monthly_Report_Monitoring_Implementation_Plan.md` in this folder for a step-by-step plan in six phases (Foundation; Activity; Budget; Type-specific LDP/IGE/RST/CIC; Type-specific Individual/Development/Beneficiary; Integration and testing).

**Implementation status:** The plan has been implemented. Monitoring blocks (`objectives_activity_monitoring`, `budget_monitoring`, `type_specific_monitoring`) are shown only when the user role is `provincial` or `coordinator` and when report status is `submitted_to_provincial` or `forwarded_to_coordinator`. For manual testing, use the checklist in Section 8.

---

## 12. Summary for Provincial Users

- **Objectives & activities:**  
  - Use the **report month** and the **project’s activities and timeframes** to spot:  
    - Scheduled but not reported,  
    - Reported but not scheduled,  
    - Ad‑hoc activities.  
  - Cross‑check with **“What did not happen”** and **“Why not”**.

- **Budget:**  
  - Use **Amount sanctioned**, **Total expenses**, **Balance** (per row and total) and **Utilisation** to spot overspend, negative balances, and odd patterns.  
  - The existing view and create/edit JS already support most of this; the monitoring block can make it explicit and scannable.

- **Project-type-specific (Section 9):**  
  - **LDP:** Annexure present, support date in period, empty impact/amount, sum of annexure amounts vs budget (optional), count vs project target group (optional).  
  - **IGE:** Grand Total and all age groups in age profile; Grand Total vs `total_beneficiaries`; sub-totals.  
  - **RST:** Trainee total = sum of education categories; total vs `total_beneficiaries`; vs project (optional).  
  - **CIC:** Grand Total and all age categories; sub-totals; Grand Total vs `total_beneficiaries`.  
  - **Individual (ILP, IAH, IES, IIES):** `total_beneficiaries` usually 1; budget heads match project type; contribution logic; no duplicate particulars.  
  - **Development, CCI, Rural-Urban-Tribal, NEXT PHASE:** Phase consistency; `total_beneficiaries` vs project target; beneficiary trend (optional).  
  - **All types:** Beneficiary consistency (report vs project, type-specific total vs `total_beneficiaries`).

- **Manual checklist:**  
  - Section 8 can be printed or used on a second screen while reviewing a report, even before the automated block exists.

---

**End of Provincial Monthly Report Monitoring & Analysis Guide**
