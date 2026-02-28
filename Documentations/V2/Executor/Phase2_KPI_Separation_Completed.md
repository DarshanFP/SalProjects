# Phase 2 – KPI Separation (Completed)

**Target:** `app/Http/Controllers/ExecutorController.php` only  
**Rule:** Modify only ExecutorController. No changes to ProjectQueryService, permissions, ReportController, ProjectController, routes, or authorization. Main projects list query stays merged; only KPI-producing queries use owned scope.

---

## Objective

All **responsibility metrics** (KPIs, dashboard widgets, charts, summaries) must use **owned scope** only.

**Combined (merged) scope remains for:**

- Main projects list (table on executor dashboard)
- General visibility (report list, report edit access)
- Permissions and authorization

---

## What “Owned” vs “Merged” Means Here

| Scope | Source | Used for |
|--------|--------|----------|
| **Owned** | `getOwnedProjectsQuery`, `getOwnedProjectIds`, `getApprovedOwnedProjectsForUser`, etc. | Budget summaries, quick stats, action items, report status summary, charts, deadlines, projects/reports requiring attention |
| **Merged** | `getProjectsForUserQuery`, `getProjectIdsForUser`, `getApprovedProjectsForUser`, etc. | Main project list, project type filter options, report list, submit report, pending reports, approved reports |

---

## Methods Modified (Summary)

| # | Method | Purpose of change |
|---|--------|-------------------|
| 1 | `executorDashboard()` | Budget summary dataset → owned only |
| 2 | `getQuickStats()` | All counts and budget stats → owned only |
| 3 | `getActionItems()` | Pending reports, reverted projects, overdue reports → owned project IDs and owned approved/reverted |
| 4 | `getReportStatusSummary()` | Report status counts → owned project IDs |
| 5 | `getChartData()` | Budget/chart data → owned project IDs and owned approved projects |
| 6 | `getReportChartData()` | Report chart data → owned project IDs |
| 7 | `getUpcomingDeadlines()` | Deadlines list → owned approved projects |
| 8 | `getProjectsRequiringAttention()` | Editable (draft/reverted) projects → owned only |
| 9 | `getReportsRequiringAttention()` | Reports needing work → owned project IDs |

---

## Step-by-Step Changes (Detail)

### Step 1 — executorDashboard()

**Goal:** Use owned scope only for the dataset that drives **budget summaries**. Do not change the main projects table query.

**Change:**

- **Before:** `$approvedProjectsForSummary = ProjectQueryService::getApprovedProjectsForUser($user, [...])`
- **After:** `$approvedProjectsForSummary = ProjectQueryService::getApprovedOwnedProjectsForUser($user, [...])`

**Left unchanged:**

- `$projectsQuery = ProjectQueryService::getProjectsForUserQuery($user)` — main list and filters
- `$projectTypes = ProjectQueryService::getProjectsForUserQuery($user)->distinct()->pluck('project_type')` — filter dropdown
- All other logic (pagination, search, sorting, enhanceProjectsWithMetadata, etc.)

---

### Step 2 — getQuickStats()

**Goal:** All quick stats (counts, budget, trends) use owned scope. Same calculations, variable names, and return structure.

**Replacements:**

| Location | Before | After |
|----------|--------|--------|
| Project IDs for report counts | `getProjectIdsForUser($user)` | `getOwnedProjectIds($user)` |
| Total projects count | `getProjectsForUserQuery($user)->count()` | `getOwnedProjectsQuery($user)->count()` |
| Active (approved) projects count | `getApprovedProjectsForUser($user)->count()` | `getApprovedOwnedProjectsForUser($user)->count()` |
| New projects this month | `getProjectsForUserQuery($user)->where('created_at', '>=', $thisMonth)->count()` | `getOwnedProjectsQuery($user)->where(...)->count()` |
| Total budget (approved projects) | `getApprovedProjectsForUser($user, [...])` | `getApprovedOwnedProjectsForUser($user, [...])` |
| Projects last month (trend) | `getProjectsForUserQuery($user)->whereBetween(...)->count()` | `getOwnedProjectsQuery($user)->whereBetween(...)->count()` |

**Unchanged:** `$totalReports`, `$approvedReports`, `$approvalRate`, `$projectIds` usage for report queries, and the returned array structure.

---

### Step 3 — getActionItems()

**Goal:** Action items (pending reports, reverted projects, overdue reports) are based on owned projects only.

**Replacements:**

| Data | Before | After |
|------|--------|--------|
| Project IDs (pending reports, etc.) | `getProjectIdsForUser($user)` | `getOwnedProjectIds($user)` |
| Reverted projects | `getRevertedProjectsForUser($user)` | `getRevertedOwnedProjectsForUser($user)` |
| Approved projects (for overdue logic) | `getApprovedProjectsForUser($user)` | `getApprovedOwnedProjectsForUser($user)` |

**Unchanged:** Structure of `pending_reports`, `reverted_projects`, `overdue_reports`, `total_pending`; no other logic.

---

### Step 4 — getReportStatusSummary()

**Goal:** Report status counts (draft, submitted, forwarded, approved, reverted) use owned project IDs only.

**Replacement:**

- **Before:** `$projectIds = ProjectQueryService::getProjectIdsForUser($user);`
- **After:** `$projectIds = ProjectQueryService::getOwnedProjectIds($user);`

**Unchanged:** Rest of method (DPReport queries, status keys, return shape).

---

### Step 5 — getChartData()

**Goal:** Chart data (budget by type, expenses, utilization timeline) uses owned projects and owned approved projects only.

**Replacements:**

| Data | Before | After |
|------|--------|--------|
| Project IDs (monthly expenses, etc.) | `getProjectIdsForUser($user)` | `getOwnedProjectIds($user)` |
| Approved projects (budget/expenses by type) | `getApprovedProjectsForUser($user, [...])` | `getApprovedOwnedProjectsForUser($user, [...])` |

**Unchanged:** Return structure (`budget_by_type`, `expenses_by_type`, `budget_vs_expenses`, `monthly_expenses`, `budget_utilization_timeline`, totals).

---

### Step 6 — getReportChartData()

**Goal:** Report chart (status distribution, submission timeline, completion rate) uses owned project IDs only.

**Replacement:**

- **Before:** `$projectIds = ProjectQueryService::getProjectIdsForUser($user);`
- **After:** `$projectIds = ProjectQueryService::getOwnedProjectIds($user);`

**Unchanged:** All DPReport queries and return structure.

---

### Step 7 — getUpcomingDeadlines()

**Goal:** Deadlines (this month, next month, overdue) are computed from owned approved projects only.

**Replacement:**

- **Before:** `$approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user);`
- **After:** `$approvedProjects = ProjectQueryService::getApprovedOwnedProjectsForUser($user);`

**Unchanged:** Logic for last-month/current-month reports, due dates, and return keys (`this_month`, `next_month`, `overdue`, `total`).

---

### Step 8 — getProjectsRequiringAttention()

**Goal:** “Projects requiring attention” (draft, reverted) limited to owned projects.

**Replacement:**

- **Before:** `$projects = ProjectQueryService::getEditableProjectsForUser($user, ['user'])`
- **After:** `$projects = ProjectQueryService::getEditableOwnedProjectsForUser($user, ['user'])`

**Unchanged:** Grouping by draft/reverted, return structure.

---

### Step 9 — getReportsRequiringAttention()

**Goal:** “Reports requiring attention” (draft, reverted) limited to reports of owned projects.

**Replacement:**

- **Before:** `$projectIds = ProjectQueryService::getProjectIdsForUser($user);`
- **After:** `$projectIds = ProjectQueryService::getOwnedProjectIds($user);`

**Unchanged:** DPReport query, grouping, return structure.

---

## What Was Not Changed (Intentional)

### Main projects list and filters

- **executorDashboard()**  
  - `$projectsQuery = ProjectQueryService::getProjectsForUserQuery($user)` — still merged (owner or in-charge).  
  - `$projectTypes = ProjectQueryService::getProjectsForUserQuery($user)->distinct()->pluck('project_type')` — still merged.

### Report list and report access (merged scope)

- **reportList()** — still uses `getProjectIdsForUser($user)` for which reports to show.
- **submitReport()** — still uses `getProjectIdsForUser($user)` for authorization.
- **pendingReports()** — still uses `getProjectIdsForUser($user)`.
- **approvedReports()** — still uses `getProjectIdsForUser($user)`.

### Other files and layers

- **ProjectQueryService** — not modified.
- **Permissions, routes, authorization** — not modified.
- **ReportController, ProjectController** — not modified.
- No raw `Project::query()` / `Project::where` logic added or changed.

---

## Diff-Style Replacement Summary

```diff
# executorDashboard() — budget summary only
- $approvedProjectsForSummary = ProjectQueryService::getApprovedProjectsForUser($user, [
+ $approvedProjectsForSummary = ProjectQueryService::getApprovedOwnedProjectsForUser($user, [

# getActionItems()
- $projectIds = ProjectQueryService::getProjectIdsForUser($user);
+ $projectIds = ProjectQueryService::getOwnedProjectIds($user);
- $revertedProjects = ProjectQueryService::getRevertedProjectsForUser($user)
+ $revertedProjects = ProjectQueryService::getRevertedOwnedProjectsForUser($user)
- $approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user);
+ $approvedProjects = ProjectQueryService::getApprovedOwnedProjectsForUser($user);

# getProjectsRequiringAttention()
- $projects = ProjectQueryService::getEditableProjectsForUser($user, ['user'])
+ $projects = ProjectQueryService::getEditableOwnedProjectsForUser($user, ['user'])

# getReportsRequiringAttention()
- $projectIds = ProjectQueryService::getProjectIdsForUser($user);
+ $projectIds = ProjectQueryService::getOwnedProjectIds($user);

# getReportStatusSummary()
- $projectIds = ProjectQueryService::getProjectIdsForUser($user);
+ $projectIds = ProjectQueryService::getOwnedProjectIds($user);

# getUpcomingDeadlines()
- $approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user);
+ $approvedProjects = ProjectQueryService::getApprovedOwnedProjectsForUser($user);

# getChartData()
- $projectIds = ProjectQueryService::getProjectIdsForUser($user);
+ $projectIds = ProjectQueryService::getOwnedProjectIds($user);
- $projects = ProjectQueryService::getApprovedProjectsForUser($user, ...);
+ $projects = ProjectQueryService::getApprovedOwnedProjectsForUser($user, ...);

# getReportChartData()
- $projectIds = ProjectQueryService::getProjectIdsForUser($user);
+ $projectIds = ProjectQueryService::getOwnedProjectIds($user);

# getQuickStats()
- $projectIds = ProjectQueryService::getProjectIdsForUser($user);
+ $projectIds = ProjectQueryService::getOwnedProjectIds($user);
- $totalProjects = ProjectQueryService::getProjectsForUserQuery($user)->count();
+ $totalProjects = ProjectQueryService::getOwnedProjectsQuery($user)->count();
- $activeProjects = ProjectQueryService::getApprovedProjectsForUser($user)->count();
+ $activeProjects = ProjectQueryService::getApprovedOwnedProjectsForUser($user)->count();
- $newProjectsThisMonth = ProjectQueryService::getProjectsForUserQuery($user)->where(...)
+ $newProjectsThisMonth = ProjectQueryService::getOwnedProjectsQuery($user)->where(...)
- $approvedProjects = ProjectQueryService::getApprovedProjectsForUser($user, ...);
+ $approvedProjects = ProjectQueryService::getApprovedOwnedProjectsForUser($user, ...);
- $projectsLastMonth = ProjectQueryService::getProjectsForUserQuery($user)->whereBetween(...)
+ $projectsLastMonth = ProjectQueryService::getOwnedProjectsQuery($user)->whereBetween(...)
```

---

## Verification

- **Syntax:** `php -l` on `ExecutorController.php` passes.
- **Linter:** No issues reported on `ExecutorController.php`.
- **Main projects table:** Still uses `getProjectsForUserQuery($user)` (merged scope).
- **Report list/routes:** Still use `getProjectIdsForUser($user)` (merged scope).
- **Method signatures:** None changed; only the called ProjectQueryService methods were swapped.
- **Chart/view contracts:** Return structures and variable names unchanged; only the data source is owned-scoped.

---

## Summary Table: Where Each Scope Is Used (After Phase 2)

| Feature | Scope | ProjectQueryService usage |
|--------|--------|----------------------------|
| Dashboard main project list | Merged | `getProjectsForUserQuery` |
| Dashboard project type filter | Merged | `getProjectsForUserQuery` |
| Budget summaries (dashboard) | **Owned** | `getApprovedOwnedProjectsForUser` |
| Quick stats | **Owned** | `getOwnedProjectIds`, `getOwnedProjectsQuery`, `getApprovedOwnedProjectsForUser` |
| Action items | **Owned** | `getOwnedProjectIds`, `getRevertedOwnedProjectsForUser`, `getApprovedOwnedProjectsForUser` |
| Report status summary | **Owned** | `getOwnedProjectIds` |
| Chart data (budget/expenses) | **Owned** | `getOwnedProjectIds`, `getApprovedOwnedProjectsForUser` |
| Report chart data | **Owned** | `getOwnedProjectIds` |
| Upcoming deadlines | **Owned** | `getApprovedOwnedProjectsForUser` |
| Projects requiring attention | **Owned** | `getEditableOwnedProjectsForUser` |
| Reports requiring attention | **Owned** | `getOwnedProjectIds` |
| Report list page | Merged | `getProjectIdsForUser` |
| Submit report / Pending / Approved reports | Merged | `getProjectIdsForUser` |

---

## Related docs

- `Phase1_Infrastructure_Completed.md` — Phase 1 (ProjectQueryService owned/in-charge methods)
- `OwnedVsInChargePhasePlan.md` — overall phase plan
- `OwnerVsInChargeResponsibilityAudit.md` — responsibility audit
- `ExecutorDashboardAudit.md` — executor dashboard audit
