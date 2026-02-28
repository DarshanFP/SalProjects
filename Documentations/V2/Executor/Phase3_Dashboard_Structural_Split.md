# Phase 3 – Dashboard Structural Split

## Objective

Separate the executor dashboard into two distinct sections:

1. **Section 1 — My Projects (Owned)**  
   Projects where `projects.user_id = current user`. Default/first section. Full actions: View, Edit, Create Report (when approved).

2. **Section 2 — Assigned Projects (In-Charge)**  
   Projects where the user is in-charge but not owner (`projects.in_charge = user` and `projects.user_id != user`). View-only for reporting: View and Edit links kept; **Create Report** button is not shown (reporting responsibility stays with owner).

KPI metrics (budget summaries, quick stats, action items, charts, etc.) remain **owned-only** (unchanged from Phase 2). The main list is no longer a single merged list: it is split into two lists (owned and in-charge), each with its own pagination. Merged scope is still used where required (e.g. project type filter options, report list routes).

---

## Controller Changes

**File:** `app/Http/Controllers/ExecutorController.php`  
**Method:** `executorDashboard()`

### Variables added

- `$ownedProjectsQuery` — `ProjectQueryService::getOwnedProjectsQuery($user)`
- `$inChargeProjectsQuery` — `ProjectQueryService::getInChargeProjectsQuery($user)`
- `$ownedProjects` — paginated result for owned list (query param `owned_page`)
- `$inChargeProjects` — paginated result for in-charge list (query param `incharge_page`)
- `$ownedCount` — `ProjectQueryService::getOwnedProjectsQuery($user)->count()` (unfiltered total for header)
- `$inChargeCount` — `ProjectQueryService::getInChargeProjectsQuery($user)->count()` (unfiltered total for header)
- `$enhancedOwnedProjects` — `enhanceProjectsWithMetadata($ownedProjects->items())`
- `$enhancedInChargeProjects` — `enhanceProjectsWithMetadata($inChargeProjects->items())`

### Queries added

- Owned base query: `getOwnedProjectsQuery($user)`
- In-charge base query: `getInChargeProjectsQuery($user)`

### Filters duplicated

The same filters are applied to **both** queries where appropriate:

- **Show type:** `approved` / `needs_work` / `all` (status whereIn or no status filter)
- **Search:** `ProjectQueryService::applySearchFilter()` when `request('search')` is filled
- **Project type:** `where('project_type', ...)` when filled
- **Status:** `where('status', ...)` when filled
- **Sort:** same `sort_by` and `sort_order`, same allowed fields
- **Eager load:** same `with([...])` for reports, accountDetails, budgets, user

### Pagination

- `$ownedProjects = $ownedProjectsQuery->paginate($perPage, ['*'], 'owned_page')->appends($request->query());`
- `$inChargeProjects = $inChargeProjectsQuery->paginate($perPage, ['*'], 'incharge_page')->appends($request->query());`

So each list has independent pagination via query params `owned_page` and `incharge_page`.

### Counts added

- `$ownedCount` — total owned projects (no filters), for “My Projects (Owned)” header badge
- `$inChargeCount` — total in-charge projects (no filters), for “Assigned Projects (In-Charge)” header badge

### Removed / replaced

- The single merged `$projectsQuery` / `$projects` used for the main table was replaced by the two separate queries and paginators above. Merged scope is **not** removed from the app: it is still used for `$projectTypes` (filter dropdown) and in report list/submit/pending/approved actions.

---

## View Changes

**File:** `resources/views/executor/index.blade.php`

### Sections added

1. **My Projects (Owned)**  
   - Card header: “My Projects (Owned)” with badge showing `$ownedCount`.  
   - Single shared filter block (Approved / Needs Work / All, Filters collapse, Reset) applies to both sections.  
   - Table: same columns as before (Project ID, Title, Type, Budget, Expenses, Utilization, Health, Last Report, Status, Actions).  
   - Actions: View, Edit (when editable), **Create Report** (when approved).  
   - Pagination: `$ownedProjects->links()` (uses `owned_page`).  
   - Results summary: “Showing X to Y of Z owned projects”.

2. **Assigned Projects (In-Charge)**  
   - Card header: “Assigned Projects (In-Charge)” with badge showing `$inChargeCount`.  
   - Same columns as owned table.  
   - Actions: **View** and **Edit** only; **Create Report** is not rendered (comment: “No Create Report for in-charge (view-only for reporting responsibility)”).  
   - Pagination: `$inChargeProjects->links()` (uses `incharge_page`).  
   - Results summary: “Showing X to Y of Z in-charge projects”.

### Tables rendered

- First table: `$ownedProjects` with `$enhancedOwnedProjects` for metadata (budget, utilization, health, last report).  
- Second table: `$inChargeProjects` with `$enhancedInChargeProjects` for the same metadata.

### Buttons preserved/removed

- **Preserved:** View, Edit (for both sections).  
- **Preserved for Owned only:** Create Report (when project is approved).  
- **Removed in In-Charge section:** Create Report (not shown in Assigned table).

### Widget visibility

- Widget conditions that previously used `$projects` (e.g. project-health, project-status-visualization) now use `$ownedProjects` so visibility is based on owned count.

### Filter links

- Approved / Needs Work / All links use `request()->except(['show', 'page', 'owned_page', 'incharge_page'])` so switching show type resets both paginations.

---

## Data Contract Changes

### New variables passed to view

- `ownedProjects` — LengthAwarePaginator (owned list)
- `inChargeProjects` — LengthAwarePaginator (in-charge list)
- `ownedCount` — int
- `inChargeCount` — int
- `enhancedOwnedProjects` — array keyed by project_id (metadata for owned table)
- `enhancedInChargeProjects` — array keyed by project_id (metadata for in-charge table)

### Removed from view contract

- `projects` — replaced by `ownedProjects` and `inChargeProjects`
- `enhancedProjects` — replaced by `enhancedOwnedProjects` and `enhancedInChargeProjects`

All other variables (e.g. `budgetSummaries`, `projectTypes`, `actionItems`, `reportStatusSummary`, `upcomingDeadlines`, `chartData`, `reportChartData`, `quickStats`, `recentActivities`, `projectHealthSummary`, `projectsRequiringAttention`, `reportsRequiringAttention`, `showType`) are unchanged.

---

## What Was Not Modified

- **KPI logic** — All KPI/widget logic remains owned-only (getApprovedOwnedProjectsForUser, getOwnedProjectIds, getOwnedProjectsQuery, getEditableOwnedProjectsForUser, getRevertedOwnedProjectsForUser, getApprovedOwnedProjectsForUser for deadlines, etc.). No change to getActionItems, getQuickStats, getChartData, getReportChartData, getReportStatusSummary, getUpcomingDeadlines, getProjectsRequiringAttention, getReportsRequiringAttention.
- **Permissions** — No permission or authorization code changed.
- **Merged scope** — Still used: `getProjectsForUserQuery($user)` for `$projectTypes`; `getProjectIdsForUser($user)` in reportList, submitReport, pendingReports, approvedReports. Not removed from the application.
- **Routes** — No route changes; dashboard still loads at the same URL, owned section is first by layout order.
- **ProjectQueryService** — Not modified.

---

## Risk Assessment

**Level: Low**

- Only ExecutorController and the executor dashboard view were modified.  
- Same filters and eager loading as before, applied to two queries instead of one.  
- Pagination is independent per section; no change to permission or route behavior.  
- Backward compatibility: same route, same filters; only the structure of the list is split into two sections.  
- In-charge table intentionally does not show Create Report; View/Edit remain for transparency and edit rights where applicable.
