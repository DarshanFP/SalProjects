# Executor Dashboard – Architectural Audit

**Date:** 2026-02-18  
**Scope:** Read-only analysis. No assumptions. All findings traced to actual code.

---

## 1. Entry Points

| Item | Value |
|------|-------|
| **Route** | `GET /executor/dashboard` |
| **Route name** | `executor.dashboard` |
| **Route file** | `routes/web.php` (line 408) |
| **Controller** | `App\Http\Controllers\ExecutorController` |
| **Method** | `executorDashboard(Request $request)` |
| **View** | `resources/views/executor/index.blade.php` (extends `executor.dashboard`) |
| **Middleware** | `auth`, `role:executor,applicant` |

**Trace:** `routes/web.php` → `ExecutorController::executorDashboard` → `ProjectQueryService`, `DPReport` queries, internal helper methods → `view('executor.index', ...)`.

---

## 2. Data Displayed

### 2.1 Summary Cards

| Card Name | Variable | Source | Query Logic | Filters Applied |
|-----------|----------|--------|-------------|-----------------|
| Project Budgets Overview – Total Budget | `$budgetSummaries['total']['total_budget']` | `ExecutorController::calculateBudgetSummariesFromProjects` | `ProjectQueryService::getApprovedProjectsForUser` → resolver `opening_balance` | Province, owner/in_charge, approved statuses |
| Project Budgets Overview – Approved Expenses | `$budgetSummaries['total']['approved_expenses']` | Same | Same; only expenses from `report->isApproved()` | Same |
| Project Budgets Overview – Unapproved Expenses | `$budgetSummaries['total']['unapproved_expenses']` | Same | Same; expenses from non-approved reports | Same |
| Project Budgets Overview – Total Remaining | `$budgetSummaries['total']['total_remaining']` | Same | Derived from approved expenses only | Same |
| Quick Stats – Total Projects | `$quickStats['total_projects']` | `ExecutorController::getQuickStats` | `ProjectQueryService::getProjectsForUserQuery($user)->count()` | Province, owner/in_charge |
| Quick Stats – Active Projects | `$quickStats['active_projects']` | Same | `ProjectQueryService::getApprovedProjectsForUser($user)->count()` | Province, owner/in_charge, approved statuses |
| Quick Stats – Total Reports | `$quickStats['total_reports']` | Same | `DPReport::whereIn('project_id', $projectIds)->count()` | `$projectIds` from `ProjectQueryService::getProjectIdsForUser` |
| Quick Stats – Approved Reports | `$quickStats['approved_reports']` | Same | `DPReport::whereIn('project_id', $projectIds)->whereIn('status', DPReport::APPROVED_STATUSES)->count()` | Same |
| Quick Stats – Approval Rate | `$quickStats['approval_rate']` | Same | Computed from total/approved reports | Same |
| Quick Stats – Budget Utilization | `$quickStats['budget_utilization']` | Same | Derived from approved projects | Same |
| Quick Stats – Avg Project Budget | `$quickStats['average_project_budget']` | Same | Same | Same |
| Report Status Summary – Draft | `$reportStatusSummary['monthly'][STATUS_DRAFT]` | `ExecutorController::getReportStatusSummary` | `DPReport::whereIn('project_id', $projectIds)->groupBy('status')` | `$projectIds` from `ProjectQueryService::getProjectIdsForUser` |
| Report Status Summary – Submitted | `$reportStatusSummary['monthly'][STATUS_SUBMITTED_TO_PROVINCIAL]` | Same | Same | Same |
| Report Status Summary – Forwarded | `$reportStatusSummary['monthly'][STATUS_FORWARDED_TO_COORDINATOR]` | Same | Same | Same |
| Report Status Summary – Approved | `$reportStatusSummary['monthly'][STATUS_APPROVED_BY_COORDINATOR]` | Same | Same | Same |
| Report Status Summary – Reverted | Sum of REVERTED_BY_PROVINCIAL + REVERTED_BY_COORDINATOR | Same | Same | Same |
| Project Health – Good/Warning/Critical | `$projectHealthSummary['good']`, `['warning']`, `['critical']` | `ExecutorController::getProjectHealthSummary` | Derived from `$enhancedProjects` (health_level) | Based on paginated/filtered projects list |

### 2.2 Tables

| Table | Variable | Source | Query Logic | Filters Applied |
|-------|----------|--------|-------------|-----------------|
| My Projects | `$projects` | `ExecutorController::executorDashboard` | `ProjectQueryService::getProjectsForUserQuery` + status filter (`show`) + search + project_type + sort + paginate | Province, owner/in_charge, status by `show` (approved/needs_work/all), search, project_type, sort |
| Budget by Project Type | `$budgetSummaries['by_project_type']` | Same as 2.1 | From approved projects | Same as 2.1 |
| Report Overview – Recent Reports | `$reportStatusSummary` (no dedicated recent table) | Report Overview uses `$reportStatusSummary` | Same as Report Status Summary | Same |

### 2.3 Charts

| Chart | Variable | Source | Query Logic | Filters Applied |
|-------|----------|--------|-------------|-----------------|
| Project Status Distribution | `$projects` items, `$enhancedProjects` | Controller | From paginated `$projects` | Same as My Projects table |
| Project Type Distribution | Same | Same | Same | Same |
| Budget Utilization | `$chartData` | `ExecutorController::getChartData` | `ProjectQueryService::getApprovedProjectsForUser` + resolver + DPReport approved | Province, owner/in_charge, approved projects |
| Budget by Type | `$chartData['budget_by_type']` | Same | Same | Same |
| Monthly Expenses | `$chartData['monthly_expenses']` | Same | `DPReport::whereIn('project_id', $projectIds)->whereIn('status', APPROVED_STATUSES)` | Same |
| Report Status Distribution | `$reportChartData['status_distribution']` | `ExecutorController::getReportChartData` | `DPReport::whereIn('project_id', $projectIds)->groupBy('status')` | `$projectIds` |
| Report Submission Timeline | `$reportChartData['monthly_submission_timeline']` | Same | Same + `groupBy(month)` | Same |
| Report Completion Rate | `$reportChartData['completion_rate']` | Same | Total vs approved counts | Same |
| Project Health Donut | `$projectHealthSummary` | Same as 2.1 | Same | Same |

### 2.4 Action Items / Widgets

| Widget | Variable | Source | Query Logic | Filters Applied |
|--------|----------|--------|-------------|-----------------|
| Action Items – Pending Reports | `$actionItems['pending_reports']` | `ExecutorController::getActionItems` | `DPReport::whereIn('project_id', $projectIds)` + draft/reverted statuses | `$projectIds` |
| Action Items – Reverted Projects | `$actionItems['reverted_projects']` | Same | `ProjectQueryService::getRevertedProjectsForUser($user)` | Province, owner/in_charge, reverted statuses |
| Action Items – Overdue Reports | `$actionItems['overdue_reports']` | Same | Loop over `getApprovedProjectsForUser` + `DPReport::where` per project | Same |
| Upcoming Deadlines | `$upcomingDeadlines` | `ExecutorController::getUpcomingDeadlines` | Loop over `getApprovedProjectsForUser` + `DPReport::where` per project | Same |
| Projects Requiring Attention | `$projectsRequiringAttention` | `ExecutorController::getProjectsRequiringAttention` | `ProjectQueryService::getEditableProjectsForUser($user)` | Province, owner/in_charge, editable statuses |
| Reports Requiring Attention | `$reportsRequiringAttention` | `ExecutorController::getReportsRequiringAttention` | `DPReport::whereIn('project_id', $projectIds)` + draft/reverted | `$projectIds` |
| Recent Activity Feed | `$recentActivities` | `ExecutorHistoryService::getForExecutor($user)` | `ActivityHistory` where related_id in projectIds or reportIds | Owner/in_charge only (no province filter in ActivityHistoryService) |

---

## 3. Data Scope & Filters

### Province Filtering

- **Applied in:** `ProjectQueryService::getProjectsForUserQuery` (line 22–24)
- **Logic:** `if ($user->province_id !== null) { $query->where('province_id', $user->province_id); }`
- **Used by:** All project-based dashboard data that goes through `ProjectQueryService` (projects list, approved projects, editable, reverted, project IDs).
- **Not applied in:** `ActivityHistoryService::getForExecutor` — filters only by `user_id` / `in_charge` on Project, no province filter. Reports are filtered by `project_id` from `Project::where(user_id|in_charge)`, so reports inherit project scope; projects come from raw `Project::where` without province.

### Society Filtering

- **Projects:** No society-level filtering for Executor. Executor sees all projects where they are owner or in-charge (and within province when `province_id` is set).
- **Reports:** Filtered by `project_id` only. No direct society filter on reports for Executor dashboard.

### Approval Filtering

- **Project approvals:**
  - Approved: `ProjectStatus::APPROVED_BY_COORDINATOR`, `APPROVED_BY_GENERAL_AS_COORDINATOR`, `APPROVED_BY_GENERAL_AS_PROVINCIAL`
  - Editable (needs_work): `ProjectStatus::getEditableStatuses()` (draft, reverted_*, reverted_to_*)
  - Main list: controlled by `show` param (approved / needs_work / all)
- **Report approvals:** `DPReport::APPROVED_STATUSES` (approved_by_coordinator, approved_by_general_as_coordinator, approved_by_general_as_provincial). Budget aggregation uses only approved report expenses.

### Soft Delete Handling

- **Project model:** Uses `SoftDeletes` (`app/Models/OldProjects/Project.php`, line 67, 254).
- **Effect:** `Project::query()` excludes soft-deleted by default (global scope). All project queries via `ProjectQueryService` and `Project::query()` exclude trashed projects.
- **Trashed projects:** Do not appear on Executor dashboard.

---

## 4. Approval Domain Interaction

### Project Approvals

- **Visibility:** Approved projects shown when `show=approved` (default). Editable/reverted when `show=needs_work`. All when `show=all`.
- **Blocking visibility:** None. Executor sees projects in all statuses according to `show`; status controls what is shown, not whether the project is visible at all (within scope).
- **Editing:** `ProjectStatus::getEditableStatuses()` controls Edit button. Final statuses (approved, rejected) are not editable by Executor.

### Report Approvals

- **Visibility:** All reports for the user’s projects are visible (status affects grouping, not visibility).
- **Blocking visibility:** None.
- **Editing:** `DPReport::isEditable()` (draft, reverted statuses). Only approved report expenses are used for budget aggregation.

### Budget Approvals

- **Source:** `ProjectFinancialResolver` for opening_balance; expenses from `report->isApproved()`.
- **Counters:** Dashboard budget summaries use only approved report expenses. Unapproved expenses are shown separately and do not reduce remaining budget.

### Society Assignment

- **Projects:** Have `society_id` (from migrations). No society-based scoping for Executor in dashboard queries.
- **Reports:** `society_id`, `society_name`, `province_id` are snapshot fields; not used for Executor dashboard filtering.

---

## 5. Role & Guard Verification

### Middleware

- **Route group:** `Route::middleware(['auth', 'role:executor,applicant'])->group(...)` (routes/web.php, line 407).
- **Role check:** Spatie Laravel Permission `role` middleware; user must have `executor` or `applicant` role.
- **Applicant:** Uses same dashboard and routes as Executor (routes/web.php line 72).

### Policies

- **Executor dashboard:** No explicit Policy or Gate usage in `ExecutorController::executorDashboard`. Access control is route middleware + query scoping.

### Query Scoping

- **Projects:** `ProjectQueryService::getProjectsForUserQuery` — province (when set) + `(user_id = $user->id OR in_charge = $user->id)`.
- **Reports:** Always filtered by `project_id IN ($projectIds)` where `$projectIds = ProjectQueryService::getProjectIdsForUser($user)`.
- **Activity feed:** `ActivityHistoryService::getForExecutor` — project IDs from `Project::where(user_id|in_charge)` with no province filter.

### Risk Analysis

- **Cross-province projects:** If an Executor has `province_id` set, project queries restrict to that province. If `province_id` is null, no province filter is applied; Executor could see projects from any province where they are owner/in_charge.
- **Cross-society:** No society filter; Executor sees all projects they own or are in-charge of (within province when applicable).
- **Activity feed:** Does not use `ProjectQueryService`; uses direct `Project::where`. No province filter. If an Executor is owner/in_charge of projects in other provinces (e.g. data inconsistency), those activities could appear.

---

## 6. Performance Analysis

### N+1 Risks

| Location | Risk | Details |
|----------|------|---------|
| `ExecutorController::getActionItems` (lines 468–474) | **Medium** | `foreach ($approvedProjects as $project)` → `DPReport::where('project_id', $project->project_id)->...->first()` per project |
| `ExecutorController::getUpcomingDeadlines` (lines 617–652) | **Medium** | Same pattern: loop over approved projects, two `DPReport::where` queries per project |
| `ExecutorController::enhanceProjectsWithMetadata` (lines 530–564) | **Low** | Iterates over already-loaded projects; uses `$project->reports` (eager loaded) |
| `ExecutorController::calculateBudgetSummariesFromProjects` (lines 375–391) | **Low** | Projects have `reports` and `reports.accountDetails` eager loaded from `getApprovedProjectsForUser` |

### Heavy Queries

| Query | Location | Notes |
|-------|----------|-------|
| `getApprovedProjectsForUser` with relations | Multiple | Loads full collections; used for budget, charts, action items, deadlines |
| `DPReport::whereIn('project_id', $projectIds)` with groupBy | getReportStatusSummary, getReportChartData | `projectIds` can be large |
| `ProjectQueryService::applySearchFilter` with leftJoin | executorDashboard | Joins `societies` for search; `select('projects.*')` |

### Missing Indexes

| Table | Column(s) | Evidence |
|-------|-----------|----------|
| projects | province_id | Indexed (`2026_02_15_223440_production_phase3_add_projects_province_id.php`: `->index()`) |
| projects | user_id, in_charge | FK constraints (indexed by DB) |
| projects | status | No explicit index found in migrations |
| DP_Reports | project_id | FK constraint (indexed by DB) |
| DP_Reports | status | No explicit index found |

### Eager Loading Review

- **Main project list:** `$projectsQuery->with(['reports' => fn, 'reports.accountDetails', 'budgets', 'user'])` — adequate.
- **getApprovedProjectsForUser:** Passes `['reports' => fn, 'reports.accountDetails', 'budgets']` where used.
- **getActionItems – pending reports:** `->with('project')`.
- **getUpcomingDeadlines:** Uses `$approvedProjects` from `getApprovedProjectsForUser`; no extra relations loaded for the report checks (each project triggers separate DPReport queries).

---

## 7. Observed Architectural Gaps (If Any)

1. **ActivityHistoryService::getForExecutor** does not apply province filtering. It uses `Project::where(user_id|in_charge)` without `province_id`. All other Executor data uses `ProjectQueryService`, which does apply province. Inconsistency.

2. **getActionItems** and **getUpcomingDeadlines** use a loop with `DPReport::where('project_id', ...)` per project. For users with many approved projects, this produces N+1 queries.

3. **projects.status** has no explicit index in migrations. Status is used in `whereIn('status', ...)` filters; missing index may affect performance on large tables.

4. **report-overview** widget "Recent Reports" table: data is fetched in the Blade view itself (`report-overview.blade.php` lines 51–64) via `Project::where(user_id|in_charge)->pluck` and `DPReport::whereIn('project_id', $projectIds)`. Controller does not pass `recentReports`. View executes DB queries; does not use `ProjectQueryService` (no province filter).
