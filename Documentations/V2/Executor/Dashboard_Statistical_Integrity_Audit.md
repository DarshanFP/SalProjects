# Dashboard Statistical Integrity Audit

**Executor Dashboard — Forensic Audit**  
**Date:** 2025-02-19  
**Scope:** All metrics, charts, action items, health, and budget statistics displayed on the Executor dashboard.

---

## 1. Metric Trace Table

| Metric | Controller Method | Data Source | Model Fields | Status Filter | Scope | Risk |
|--------|-------------------|-------------|--------------|---------------|-------|------|
| Total Budget | `calculateBudgetSummariesFromProjects` | `ProjectFinancialResolver::resolve` → `opening_balance` | `projects.opening_balance` (approved), budgets | Approved projects only | Owned | LOW |
| Approved Expenses | `calculateBudgetSummariesFromProjects` | `DPReport.accountDetails` | `DP_AccountDetails.total_expenses` | `report->isApproved()` (APPROVED_STATUSES) | Owned | LOW |
| Unapproved Expenses | `calculateBudgetSummariesFromProjects` | `DPReport.accountDetails` | `DP_AccountDetails.total_expenses` | Reports NOT in APPROVED_STATUSES | Owned | MEDIUM (includes draft, submitted, forwarded, reverted) |
| Total Remaining | `calculateBudgetSummariesFromProjects` | `DerivedCalculationService::calculateRemainingBalance` | `opening_balance - approved_expenses` | — | Owned | LOW |
| Budget Utilization % | View + `DerivedCalculationService::calculateUtilization` | `(expenses/budget)*100` | — | 0 when budget≤0 | Owned | LOW |
| Budget by Project Type | `calculateBudgetSummariesFromProjects` | Same as total | — | Same | Owned | LOW |
| Pending Reports | `getActionItems` | `DPReport` | status | DRAFT, REVERTED_* (7 statuses) | Owned | MEDIUM (excludes SUBMITTED_TO_PROVINCIAL) |
| Reverted Projects | `getActionItems` | `ProjectQueryService::getRevertedOwnedProjectsForUser` | status | 8 reverted statuses | Owned | LOW |
| Overdue Reports | `getActionItems` | `DPReport` + approved projects | `report_month_year`, status | No report OR draft for last month | Owned | MEDIUM (due date = endOfMonth) |
| Total Pending | `getActionItems` | Sum of above three | — | — | Owned | MEDIUM |
| Total Projects | `getQuickStats` | `ProjectQueryService::getOwnedProjectsQuery` | — | None | Owned | LOW |
| Active Projects | `getQuickStats` | `getApprovedOwnedProjectsForUser` | status | APPROVED_* | Owned | LOW |
| Total Reports | `getQuickStats` | `DPReport::whereIn(project_id)` | — | None | Owned | LOW |
| Approved Reports | `getQuickStats` | `DPReport` | status | APPROVED_STATUSES | Owned | LOW |
| Approval Rate | `getQuickStats` | `approved/total*100` | — | 0 when total=0 | Owned | LOW |
| New Projects This Month | `getQuickStats` | Owned projects | `created_at >= startOfMonth` | None | Owned | LOW |
| Budget Utilization (Quick Stats) | `getQuickStats` | Same as Budget Summary | — | Approved only | Owned | LOW |
| Average Project Budget | `getQuickStats` | `totalBudget/activeProjects` | — | 0 when active=0 | Owned | LOW |
| Total Budget/Expenses (Quick Stats) | `getQuickStats` | Approved projects | — | Approved | Owned | LOW |
| Report Status Summary | `getReportStatusSummary` | `DPReport::groupBy(status)` | status | None | Owned | **HIGH** (missing 8+ statuses) |
| Upcoming Deadlines | `getUpcomingDeadlines` | Approved projects + DPReport | `report_month_year` | No report OR draft | Owned | MEDIUM |
| Project Health Summary | `getProjectHealthSummary` | `$enhancedOwnedProjects` | — | — | **PAGINATED** | **HIGH** |
| Projects Requiring Attention | `getProjectsRequiringAttention` | `getEditableOwnedProjectsForUser` | status | Editable statuses | Owned | LOW |
| Reports Requiring Attention | `getReportsRequiringAttention` | `DPReport` | status | DRAFT, REVERTED_* (7) | Owned | LOW |
| Project Status Distribution Chart | `project-status-visualization` | `$ownedProjects->items()` | status | — | **PAGINATED** | **HIGH** |
| Project Type Distribution Chart | `project-status-visualization` | `$ownedProjects->items()` | project_type | — | **PAGINATED** | **HIGH** |
| Budget vs Expenses Chart | `getChartData` | `getApprovedOwnedProjectsForUser` | — | Approved | Owned | LOW |
| Monthly Expenses Chart | `getChartData` | `DPReport` (approved) | `DP_AccountDetails.total_expenses` | APPROVED_STATUSES | Owned | MEDIUM (possible duplicate report_month_year) |
| Report Status Distribution Chart | `getReportChartData` | `DPReport::groupBy(status)` | status | None | Owned | **HIGH** (missing statuses) |
| Report Submission Timeline | `getReportChartData` | `DPReport` | `report_month_year` | None | Owned | LOW |
| Completion Rate | `getReportChartData` | `approved/total*100` | — | — | Owned | LOW |
| Report Overview Widget | View (inline query) | `Project::where(user_id OR in_charge)` | — | — | **MERGED** | **HIGH** |

---

## 2. Budget Domain Validation

### Approved Expenses Definition

- **Statuses counted:** `DPReport::APPROVED_STATUSES` = `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`
- **AccountDetails:** All rows in `DP_AccountDetails` for the report; sum of `total_expenses`
- **Filter:** Reports only; no filtering of accountDetail rows by approval
- **Partial approvals:** Not applicable; report-level approval, not row-level

**Validation:** Correct. Only approved reports contribute; `report->isApproved()` gates inclusion.

### Unapproved Expenses Definition

- **Statuses:** All reports NOT in APPROVED_STATUSES (draft, submitted, forwarded, reverted, rejected)
- **Includes draft:** Yes
- **Includes reverted:** Yes (all revert variants)
- **Duplicates possible:** If a project has multiple reports for same period, could double-count if both unapproved; generally one report per project per month expected

**Validation:** Correct per business rule (unapproved = not yet coordinator-approved).

### Total Remaining

- **Formula:** `remaining = opening_balance - approved_expenses` (per project, then summed)
- **Unapproved excluded:** Yes, intentionally
- **Consistency:** Same formula in `calculateBudgetSummariesFromProjects`, `enhanceProjectsWithMetadata`, `getChartData`, `getQuickStats`

**Validation:** Mathematically coherent.

### Budget Utilization

- **Formula:** `DerivedCalculationService::calculateUtilization(expenses, openingBalance)` = `(expenses/openingBalance)*100` when `openingBalance > 0`, else `0`
- **Division safety:** Handled; returns 0 when budget ≤ 0
- **Rounding:** `round(utilization, 1)` or `round(utilization, 2)` in various places; slightly inconsistent but acceptable

**Validation:** Safe.

### Budget by Project Type

- **Reconciliation:** `SUM(by_type.total_budget)` = `total.total_budget` ✓
- **Grouping:** By `project->project_type`; DB stores string; no enum
- **Type consistency:** Project types are free-form strings; case variants (e.g. "Development Projects" vs "development projects") would create separate buckets

**Validation:** Totals reconcile. Project type case sensitivity is a known DB characteristic.

### Negative accountDetails

- **Handling:** No explicit clamp; `sum('total_expenses')` would include negatives
- **DB constraint:** Unknown; if negative values exist, totals could be incorrect

**Risk:** If `total_expenses` can be negative, budget math may be wrong.

---

## 3. Report Domain Validation

### DPReport Status Constants

| Constant | Value |
|----------|-------|
| STATUS_DRAFT | draft |
| STATUS_SUBMITTED_TO_PROVINCIAL | submitted_to_provincial |
| STATUS_FORWARDED_TO_COORDINATOR | forwarded_to_coordinator |
| STATUS_APPROVED_BY_COORDINATOR | approved_by_coordinator |
| STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR | approved_by_general_as_coordinator |
| STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL | approved_by_general_as_provincial |
| STATUS_REVERTED_BY_PROVINCIAL | reverted_by_provincial |
| STATUS_REVERTED_BY_COORDINATOR | reverted_by_coordinator |
| STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL | reverted_by_general_as_provincial |
| STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR | reverted_by_general_as_coordinator |
| STATUS_REVERTED_TO_EXECUTOR | reverted_to_executor |
| STATUS_REVERTED_TO_APPLICANT | reverted_to_applicant |
| STATUS_REVERTED_TO_PROVINCIAL | reverted_to_provincial |
| STATUS_REVERTED_TO_COORDINATOR | reverted_to_coordinator |

### Report Status Summary / Report Chart Data — Critical Gap

`getReportStatusSummary` and `getReportChartData` initialize only 6 statuses:

```php
$statuses = [
    STATUS_DRAFT => 0,
    STATUS_SUBMITTED_TO_PROVINCIAL => 0,
    STATUS_FORWARDED_TO_COORDINATOR => 0,
    STATUS_APPROVED_BY_COORDINATOR => 0,
    STATUS_REVERTED_BY_PROVINCIAL => 0,
    STATUS_REVERTED_BY_COORDINATOR => 0,
];
```

**Missing:** `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`, `reverted_by_general_as_*`, `reverted_to_*`, `rejected_by_coordinator`.

**Impact:** Reports with those statuses are excluded from the status summary and status distribution chart. `total` = sum of the 6 initialized keys only → **undercount**.

### Approval Rate

- **Formula:** `approved_reports / total_reports * 100` when `total_reports > 0`, else 0
- **Total reports:** Includes drafts, all statuses
- **Owned only:** Yes (projectIds from `getOwnedProjectIds`)
- **Quarterly/biannual/annual:** Executor dashboard uses `DPReport` (monthly) only; no explicit exclusion

**Validation:** Approval rate math is correct for monthly reports; status summary total is wrong.

---

## 4. Action Items Validation

### Pending Reports

- **Statuses:** DRAFT, REVERTED_BY_PROVINCIAL, REVERTED_BY_COORDINATOR, REVERTED_BY_GENERAL_AS_PROVINCIAL, REVERTED_BY_GENERAL_AS_COORDINATOR, REVERTED_TO_EXECUTOR, REVERTED_TO_APPLICANT
- **Excluded:** SUBMITTED_TO_PROVINCIAL, FORWARDED_TO_COORDINATOR (in pipeline, not actionable by executor for edit)
- **Ownership:** Owned projects only
- **Cross-project:** Scoped by `project_id in (owned project ids)`

**Validation:** Consistent with executor workflow.

### Overdue Logic

- **Due date:** End of current month (`$now->copy()->endOfMonth()`)
- **Check:** Last month's report (`report_month_year = lastMonth->format('Y-m')`) missing OR draft
- **Timezone:** Uses `Carbon::now()` (server timezone)
- **Past due:** `$now->gt($dueDate)` — becomes true on first day of next month

**Validation:** Logic is correct; January report due by end of February.

### Reverted Projects

- **Statuses:** All 8 reverted project statuses from `ProjectStatus`
- **Duplication:** One project can have only one status; no double-count
- **Owned scope:** Yes

**Validation:** Correct.

---

## 5. Health Logic Validation

### Health Calculation (`calculateProjectHealth`)

- **Starting score:** 100
- **Budget utilization deductions:**
  - >90%: -40
  - >75%: -20
  - >50%: -10
- **Report timeliness:**
  - >60 days since last report: -30
  - >30 days: -15
  - No reports: -25
- **Status (reverted):**
  - REVERTED_BY_COORDINATOR: -30
  - REVERTED_BY_PROVINCIAL: -15
  - Other reverted: -10
- **Levels:** good ≥80, warning ≥50, critical <50

### Expenses Used for Health

- **Source:** `enhanceProjectsWithMetadata` uses **approved reports only** for expenses
- **Utilization:** `calculateUtilization(totalExpenses, projectBudget)` where totalExpenses = sum of approved report accountDetails
- **Remaining:** `calculateRemainingBalance(projectBudget, totalExpenses)` — same approved-only expenses

**Validation:** Health uses approved expenses; consistent with budget remaining.

### Critical Bug: Health Summary Scope

- **Input:** `getProjectHealthSummary($enhancedOwnedProjects)` where `$enhancedOwnedProjects = enhanceProjectsWithMetadata($ownedProjects->items())`
- **`$ownedProjects->items()`** = current **page** of paginated results (default 15)
- **Impact:** Health summary (good/warning/critical counts) reflects only the current page, not all owned projects

**Risk:** HIGH — Health overview is misleading when user has many projects.

---

## 6. Chart Consistency Validation

| Chart | Data Source | Scope | Reconciles with Widgets? |
|-------|-------------|-------|---------------------------|
| Budget by Type | `getChartData` | Approved owned | Yes |
| Budget vs Expenses | `getChartData` | Approved owned | Yes |
| Monthly Expenses | `getChartData` | Approved reports | Yes |
| Budget Utilization Timeline | `getChartData` | Approved reports, cumulative | Yes |
| Project Status Distribution | `$ownedProjects->items()` | **Paginated owned** | **NO** |
| Project Type Distribution | `$ownedProjects->items()` | **Paginated owned** | **NO** |
| Report Status Distribution | `getReportChartData` | Owned | **NO** (missing statuses) |
| Report Submission Timeline | `getReportChartData` | Owned | Yes |
| Completion Rate | `getReportChartData` | Owned | Yes |
| Project Health Donut | `$projectHealthSummary` | **Paginated** | **NO** |

### Project Status / Type Charts

- Use `$ownedProjects->items()` — current page only
- Quick Stats "Total Projects" and project list use full count
- **Result:** Charts show distribution of ~15 projects while totals refer to all owned projects

### Monthly Expenses — Duplicate report_month_year

- If a project has multiple approved reports for the same `report_month_year`, both are summed
- No unique constraint on (project_id, report_month_year) verified
- **Risk:** Possible double-count of expenses for same period

---

## 7. Relation Integrity Validation

### Eager Loading

- **executorDashboard:** `$ownedProjectsQuery->with(['reports', 'reports.accountDetails', 'budgets', 'user'])`
- **calculateBudgetSummariesFromProjects:** Receives projects with `reports.accountDetails` preloaded
- **Fallback:** `if (!$project->relationLoaded('reports.accountDetails')) { $project->load('reports.accountDetails'); }`
- **getChartData / getQuickStats:** Use `getApprovedOwnedProjectsForUser` with `['reports.accountDetails', 'budgets']`

**Validation:** Eager loading is used; N+1 unlikely for budget calculations.

### Missing Relation

- If `accountDetails` is empty for a report, `sum('total_expenses')` returns 0 (or null coalesced to 0)
- Approved report with no accountDetails: contributes 0 to expenses — acceptable

**Validation:** No incorrect sums from missing relations.

---

## 8. Mathematical Reconciliation Results

### Expected Invariants

- `total_remaining = total_budget - approved_expenses` ✓
- `total_expenses = approved_expenses + unapproved_expenses` ✓
- `SUM(by_type.total_budget) = total.total_budget` ✓
- `SUM(by_type.approved_expenses) = total.approved_expenses` ✓
- `SUM(by_type.total_remaining) = total.total_remaining` ✓

### Controller Logic

- `calculateBudgetSummariesFromProjects` computes `remainingBudget = calc->calculateRemainingBalance(projectBudget, approvedExpenses)` per project
- Adds to both `by_project_type` and `total`
- **Deviation:** None; matches expected formula

### Budget Summary vs Quick Stats vs Chart Data

- All use `getApprovedOwnedProjectsForUser` (or equivalent) for financials
- Same resolver, same calculation service
- **Reconciliation:** Budget Summary total, Quick Stats total_budget/total_expenses, and chartData totals should match

---

## 9. Edge Case Findings

| Scenario | Expected | Actual | Risk |
|----------|----------|--------|------|
| User with no projects | Zeros, empty widgets | Handled; chartData empty when ownedProjects->total()==0 | LOW |
| User with only in-charge projects | KPIs show 0 (owned scope) | Correct; all KPIs use owned | LOW |
| Only draft projects | No approved projects → budget 0 | Correct | LOW |
| Approved project without reports | Budget shown, expenses 0 | Correct | LOW |
| Approved project with only draft reports | Expenses 0 (approved only) | Correct | LOW |
| Reports approved but no accountDetails | Expenses 0 | Correct (sum of empty = 0) | LOW |
| Negative accountDetails.total_expenses | Would reduce totals | No clamp; possible error | MEDIUM |
| Duplicate report_month_year per project | Possible double-count | Possible in monthly_expenses chart | MEDIUM |
| Reports with status approved_by_general_as_* | Should count as approved | Counted in approval rate; **not** in Report Status Summary | HIGH |
| Pagination: 100 owned projects, page 15 | Health/Status/Type charts | Only 15 projects in charts | HIGH |
| Report Overview widget | Same scope as other KPIs | Uses **merged** (owner OR in-charge) | HIGH |

---

## 10. Identified Gaps & Risk Classification

### Critical / High Risks

1. **Report Status Summary & Report Status Chart — Incomplete Status Set**  
   Only 6 of 14+ statuses are initialized. Reports with `approved_by_general_as_*`, `reverted_by_general_*`, `reverted_to_*` are uncounted. Total and distribution are wrong.

2. **Project Health Summary — Pagination Scope**  
   Health counts (good/warning/critical) are computed from `$ownedProjects->items()` (current page) instead of all owned projects. Misleading for users with many projects.

3. **Project Status & Type Charts — Pagination Scope**  
   Same issue: charts use `$ownedProjects->items()` instead of all owned projects. Distribution does not match overall counts.

4. **Report Overview Widget — Scope Inconsistency**  
   Uses `Project::where(user_id OR in_charge)` (merged scope) while all other KPIs use owned-only. Recent reports and counts can include in-charge projects.

5. **Report Overview "Approved" Count — Incomplete**  
   Displays only `STATUS_APPROVED_BY_COORDINATOR`; omits `approved_by_general_as_coordinator` and `approved_by_general_as_provincial`.

### Medium Risks

6. **Action Items "Pending Reports" vs Report Status Summary**  
   Pending reports exclude SUBMITTED_TO_PROVINCIAL; status summary has separate bucket. Semantically consistent but could confuse users.

7. **Budget Overview project_type Filter**  
   Form submits project_type to dashboard, but `calculateBudgetSummariesFromProjects` ignores request filters. Budget summary is always unfiltered even when project list is filtered.

8. **Duplicate report_month_year**  
   No check for multiple reports per project per month; monthly expense aggregation could double-count if duplicates exist.

9. **Negative total_expenses**  
   No validation; negative values would incorrectly reduce expense totals.

### Low Risks

10. **Rounding consistency** — Minor (1 vs 2 decimal places).
11. **Timezone** — Server timezone for deadlines; acceptable if documented.

---

## Classification

**Overall system classification:** **COHERENT_WITH_MINOR_GAPS**

### Rationale

- Budget and expense math is consistent and correctly separates approved vs unapproved.
- Ownership vs in-charge separation is applied consistently for main KPIs.
- Report approval domain is correct for financial calculations.
- Critical issues are confined to:
  - Incomplete report status handling (summary/charts)
  - Pagination incorrectly used for health and project charts
  - Report Overview using merged scope and incomplete approved count

### Financial Redesign Required?

**No.** Budget and expense logic does not require redesign. The issues are in:
- Status set completeness
- Chart/scoped data sources (pagination)
- Scope consistency (Report Overview)

### Dashboard Statistics Trustworthiness

- **Budget & expense figures:** Trustworthy for approved-owned scope.
- **Project counts (total, active):** Trustworthy.
- **Report counts (total, approved, approval rate):** Trustworthy.
- **Report status summary & distribution:** **Not trustworthy** — undercounts due to missing statuses.
- **Project health summary:** **Not trustworthy** when user has more than one page of projects.
- **Project status/type charts:** **Not trustworthy** — reflect current page only.
- **Action items, deadlines, projects/reports requiring attention:** Trustworthy for owned scope.

---

*End of Audit*
