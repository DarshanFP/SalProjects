# Executor Budget Scope — Architecture Audit

**Date:** 2026-03-04  
**Scope:** Whether Executor dashboard financial totals should include **owned only** or **owned + in_charge** projects.  
**Method:** Static analysis only. No code was modified.

---

## 1. Current Executor Budget Scope

### 1.1 Financial aggregations in ExecutorController

All executor financial totals that use **projects** (not reports) are currently **owned-only**. In-charge projects are excluded from budget/financial aggregates.

| Method | Purpose | Project source | Scope |
|--------|---------|----------------|-------|
| `calculateBudgetSummariesFromProjects($projects, $request)` | Project Budgets Overview widget (Total Budget, expenses, remaining) | Caller passes `$approvedProjectsForSummary` | **Owned only** (see below) |
| `getChartData($user, $request)` | Budget Analytics widget (total_budget, budget_by_type, etc.) | `ProjectQueryService::getApprovedOwnedProjectsForUser($user, [...])` | **Owned only** |
| `getQuickStats($user)` | Quick Stats widget (total_budget, total_expenses, etc.) | `ProjectQueryService::getApprovedOwnedProjectsForUser($user, [...])` | **Owned only** |
| `enhanceProjectsWithMetadata($projects)` | Table columns (Budget, Expenses, Utilization) for Owned and In-Charge lists | Receives `$ownedProjects->items()` and `$inChargeProjects->items()` | Per-list: owned list uses owned scope; in-charge list uses in-charge scope (no aggregation) |

**Main dashboard flow (executorDashboard):**

```text
$approvedProjectsForSummary = ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy);
$budgetSummaries = $this->calculateBudgetSummariesFromProjects($approvedProjectsForSummary->all(), $request);
```

So the **Project Budgets Overview** widget (Total Budget / Total Funds) is driven by **owned, approved projects only**, optionally filtered by FY.

**Other executor pages (report-based, not project-based):**

- `reportList`, `pendingReports`, `approvedReports` use `ProjectQueryService::getProjectIdsForUser($user)` (owner **or** in-charge) to fetch reports, then `calculateBudgetSummaries($reports, $request)` (report-level totals from account details). So on those pages, the **budget summary is effectively owned + in_charge** (because it sums over all reports for projects where the user is owner or in-charge). This is an **inconsistency**: main dashboard = owned only; report list pages = owner + in_charge.

### 1.2 Project query services used for financial data

| Query / method | Filters applied | In-charge included? |
|----------------|-----------------|----------------------|
| `ProjectQueryService::getOwnedProjectsQuery($user)` | `province_id` (if set), `user_id = $user->id` | **No** |
| `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)` | Same as above + approved statuses + optional `inFinancialYear($fy)` | **No** |
| `ProjectQueryService::getInChargeProjectsQuery($user)` | `province_id`, `in_charge = $user->id`, `user_id != $user->id` | Defines in-charge set only; not used for budget totals |
| `ProjectQueryService::getProjectIdsForUser($user)` | `user_id = $user->id` **or** `in_charge = $user->id` | **Yes** (used for report list pages, not main dashboard budget) |

**Confirmed:** For all **dashboard** financial aggregations (budget summaries, chart data, quick stats), the project set is **getApprovedOwnedProjectsForUser** → **in_charge projects are excluded**.

---

## 2. Dataset Comparison (User 37 Example)

Using the same approved-project snapshot as in the User 37 forensic audit.

### 2.1 Approved projects for user 37 (owner or in-charge)

| project_id | user_id | in_charge | FY (derived) | opening_balance (DB) | Resolver opening_balance |
|------------|---------|-----------|--------------|----------------------|---------------------------|
| DP-0017    | **37**  | 26        | 2024-25      | (empty)              | 0                         |
| DP-0041    | **37**  | 29        | 2026-27      | 630000.00             | 630000                    |
| IIES-0060  | **37**  | 144       | 2026-27      | 16000.00             | 16000                     |
| DP-0002    | 28      | **37**    | 2025-26      | (empty)              | 0                         |
| DP-0004    | 27      | **37**    | 2025-26      | 595500.00             | 595500                    |
| DP-0024    | 27      | **37**    | 2025-26      | 1040000.00            | 1040000                   |
| DP-0025    | 27      | **37**    | 2025-26      | 1830000.00            | 1830000                   |

### 2.2 Dataset A — Owned only (current dashboard scope)

- **Projects:** DP-0017, DP-0041, IIES-0060 (3 projects).
- **Resolver total (opening_balance):** 0 + 630000 + 16000 = **646,000**.

### 2.3 Dataset B — Owned + in_charge

- **Projects:** All 7 above.
- **Resolver total (opening_balance):** 0 + 630000 + 16000 + 0 + 595500 + 1040000 + 1830000 = **4,111,500**.

### 2.4 Comparison

| Metric        | Dataset A (owned only) | Dataset B (owned + in_charge) |
|---------------|------------------------|------------------------------|
| Project count | 3                      | 7                            |
| Total Budget  | 646,000                | 4,111,500                    |

For user 37, including in-charge projects would increase the displayed Total Budget by a large factor because they are in-charge of several high-value projects they do not own. The choice between A and B is therefore **product/design**: “budget I own” vs “budget I own + budget I am in charge of.”

---

## 3. Dashboard Widget Consistency

### 3.1 Scope consistency (owned vs owned + in_charge)

- **Project Budgets Overview:** Owned only ✓ (aligned with current design).
- **Budget Analytics (getChartData):** Owned only ✓.
- **Quick Stats (getQuickStats):** Owned only ✓.
- **Owned projects table:** Owned only; per-row budget from resolver ✓.
- **In-Charge projects table:** In-charge only; per-row budget from resolver ✓ (no aggregation).
- **Report List / Pending Reports / Approved Reports:** Budget summary is report-based over **getProjectIdsForUser** → owner **or** in-charge. So these pages effectively use **owned + in_charge** for their totals, which is **inconsistent** with the main dashboard.

### 3.2 FY consistency

- **Project Budgets Overview:** Uses `$fy` (getApprovedOwnedProjectsForUser with `$fy`). **FY-aware.**
- **getChartData:** Uses `getApprovedOwnedProjectsForUser($user, [...])` **without** `$fy`. **Ignores FY.**
- **getQuickStats:** Same; no `$fy`. **Ignores FY.**
- **getActionItems:** Uses `getApprovedOwnedProjectsForUser($user)` (no `$fy`) for overdue logic. **Ignores FY.**
- **getUpcomingDeadlines:** Same; no `$fy`. **Ignores FY.**
- **ownedCount / inChargeCount:** From getOwnedProjectsQuery / getInChargeProjectsQuery with **no** FY filter. **Ignore FY.**

So within the executor dashboard, **only** the Project Budgets Overview and the project lists/tables are FY-scoped; Quick Stats, Budget Analytics, action items, and deadlines are not, which can make totals and counts look inconsistent when the user changes FY.

---

## 4. Resolver Compatibility

- **ProjectFinancialResolver::resolve(Project $project)** takes a single `Project` model and returns a fixed set of financial fields (e.g. `opening_balance`, `amount_sanctioned`). It does **not** take the current user or any “role” (owner vs in-charge).
- For **approved** projects it uses `$project->opening_balance` (and related DB fields). For **non-approved** it uses requested/forwarded/local logic. Ownership vs in-charge does not change how the resolver computes these values.
- **Conclusion:** Resolver usage is **compatible with both** owned-only and owned+in_charge datasets. No change is required in the resolver if the executor budget scope is extended to in-charge; the same `calculateBudgetSummariesFromProjects($projects, $request)` and per-project `$resolver->resolve($project)` work for any collection of projects.

---

## 5. Double Counting Risk

### 5.1 How other dashboards get projects

- **Provincial:** Uses `Project::accessibleByUserIds($accessibleUserIds)->approved()->inFinancialYear($fy)`. `accessibleByUserIds` is `whereIn('user_id', $ids)->orWhereIn('in_charge', $ids)`. So provincial sees each project **once** (all projects where owner or in-charge is in the provincial’s team). Aggregation is over this set; no per-executor sum.
- **Coordinator:** Uses `getVisibleProjectsQuery($coordinator, $fy)`. For coordinator role the query is unfiltered (all projects). No aggregation of “executor A’s total + executor B’s total.”
- **Executor:** Today each executor’s dashboard shows only **their** owned (or, if scope changed, owned+in_charge) total. There is no screen that sums “all executors’ totals” into one system-wide number.

### 5.2 If executor scope is changed to owned + in_charge

- The **same project** (e.g. owner user 27, in-charge user 37) would appear in:
  - Executor 27’s “Total Budget” (as owner),
  - Executor 37’s “Total Budget” (as in-charge).
- That is **two different dashboards**, each showing a per-user view. It is **not** double counting in a single rollup (e.g. provincial total still counts each project once).
- **Conclusion:** Adding in_charge to the executor budget scope would **not** inflate Provincial or Coordinator totals. The only “double exposure” is that one project’s budget would be visible on two executors’ dashboards by design, which is a product choice, not an accounting error.

---

## 6. Recommended Architecture

### 6.1 Scope decision (owned only vs owned + in_charge)

- **Current behaviour:** Dashboard financial totals = **owned only**. Report list–type pages = **owner + in_charge** (via report-based summaries).
- **Recommendation:** Decide at product level:
  - **Option A — Keep owned only:** “Total Budget” = budget of projects I **own**. Clear, avoids any ambiguity with in-charge. Align report list pages to the same definition (e.g. restrict report-list budget summary to owned projects only) for consistency.
  - **Option B — Switch to owned + in_charge:** “Total Budget” = budget of projects I own **or** am in charge of. Matches the fact that in-charge users already see those projects and reports; then make dashboard and report-list scope consistent (both owned + in_charge) and document that the same project can appear in two executors’ totals.

### 6.2 If scope remains owned only

- Add a single, documented “Executor financial scope = owned projects only” in architecture/requirements.
- **Align report list pages:** Use owned-only project set for budget summaries on Report List, Pending Reports, and Approved Reports (e.g. restrict to `getApprovedOwnedProjectsForUser` or equivalent when computing totals), so that “Total Budget” means the same everywhere for the executor.

### 6.3 If scope becomes owned + in_charge

- Introduce a single helper for “approved projects for executor dashboard” that returns **owned + in_charge** (e.g. approved projects where `user_id = $user->id` OR `in_charge = $user->id`), with same status and FY rules as today.
- Use this helper for:
  - Project Budgets Overview,
  - getChartData,
  - getQuickStats,
  - and (if desired) report-list budget summaries.
- Keep using the same `calculateBudgetSummariesFromProjects` and resolver; no resolver change needed.
- Document that a project can appear in two executors’ “Total Budget” (owner and in-charge) and that Provincial/Coordinator rollups are unaffected.

### 6.4 Cross-cutting: FY and widget consistency

- Pass **FY** into all financial and count widgets that should respect the dashboard FY filter: e.g. getChartData, getQuickStats, getActionItems, getUpcomingDeadlines, and optionally ownedCount/inChargeCount.
- Ensure the Project Budgets Overview form preserves the selected FY when submitting (e.g. hidden `fy` input) so changing filters does not reset FY.
- Optionally derive available FY from project data (or include next FY) so executors can select every FY in which they have projects.

---

**Audit performed without modifying any application code. Findings are from static analysis and existing audit data (including Dashboard_User37_Budget_Zero_Audit).**
