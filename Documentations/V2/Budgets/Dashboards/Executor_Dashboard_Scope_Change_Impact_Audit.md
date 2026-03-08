# Executor Dashboard Scope Selector & Dynamic FY — Impact Audit

**Date:** 2026-03-04  
**Objective:** Evaluate feasibility and impact of introducing (1) Executor Dashboard **Scope Selector** (Owned / In-Charge / Owned + In-Charge) with default Owned, and (2) **Data-driven FY selector** derived from project data.  
**Method:** Static analysis only. No application code was modified.

---

## 1️⃣ Current Executor Dashboard Architecture

### 1.1 Data flow: Controller → View

```
ExecutorController::executorDashboard(Request $request)
    │
    ├─ $fy = $request->input('fy', FinancialYearHelper::currentFY())
    ├─ $ownedBaseQuery = ProjectQueryService::getOwnedProjectsQuery($user)
    ├─ $inChargeProjectsQuery = ProjectQueryService::getInChargeProjectsQuery($user)
    │     → status filter (show), inFinancialYear($fy), search, project_type, status
    │
    ├─ $ownedProjects = paginate($ownedBaseQuery)
    ├─ $inChargeProjects = paginate($inChargeProjectsQuery)
    ├─ $ownedFullProjects = $ownedBaseQuery->get()  // full set, no pagination
    │
    ├─ $ownedCount = getOwnedProjectsQuery($user)->count()           // no FY
    ├─ $inChargeCount = getInChargeProjectsQuery($user)->count()   // no FY
    │
    ├─ $approvedProjectsForSummary = getApprovedOwnedProjectsForUser($user, $with, $fy)
    ├─ $budgetSummaries = calculateBudgetSummariesFromProjects($approvedProjectsForSummary->all(), $request)
    │     → ProjectFinancialResolver::resolve($project) per project; sum opening_balance, expenses
    │
    ├─ $enhancedOwnedProjects = enhanceProjectsWithMetadata($ownedProjects->items())   // resolver per project
    ├─ $enhancedInChargeProjects = enhanceProjectsWithMetadata($inChargeProjects->items())
    ├─ $enhancedFullOwnedProjects = enhanceProjectsWithMetadata($ownedFullItems)
    │
    ├─ $projectTypes = getOwnedProjectsQuery($user)->inFinancialYear($fy)->distinct()->pluck('project_type')
    ├─ $actionItems = getActionItems($user)           // getApprovedOwnedProjectsForUser, no FY
    ├─ $reportStatusSummary = getReportStatusSummary($user)
    ├─ $upcomingDeadlines = getUpcomingDeadlines($user)  // getApprovedOwnedProjectsForUser, no FY
    ├─ $chartData = getChartData($user, $request)   // getApprovedOwnedProjectsForUser, no FY
    ├─ $reportChartData = getReportChartData($user, $request)
    ├─ $quickStats = getQuickStats($user)           // getApprovedOwnedProjectsForUser, no FY
    ├─ $recentReports = DPReport::whereIn('project_id', getOwnedProjectIds($user))->...
    ├─ $recentActivities = ActivityHistoryService::getForExecutor($user)
    ├─ $projectHealthSummary = getProjectHealthSummary($enhancedFullOwnedProjects)
    ├─ $projectChartData = buildProjectChartData($ownedFullProjects)
    ├─ $projectsRequiringAttention = getProjectsRequiringAttention($user)
    ├─ $reportsRequiringAttention = getReportsRequiringAttention($user)
    │
    ├─ $availableFY = FinancialYearHelper::listAvailableFY()
    │
    └─ return view('executor.index', compact(..., 'budgetSummaries', 'fy', 'availableFY', ...))
```

### 1.2 Where each metric is calculated

| Metric | Method(s) | Dataset used | FY used? |
|--------|-----------|--------------|----------|
| **Total Budget** | `calculateBudgetSummariesFromProjects` | `getApprovedOwnedProjectsForUser(..., $fy)` | Yes |
| **Total Expenses / Remaining** | Same | Same | Yes |
| **Budget Analytics** (charts) | `getChartData` | `getApprovedOwnedProjectsForUser($user, [...])` | No |
| **Quick Stats** (total_budget, etc.) | `getQuickStats` | `getApprovedOwnedProjectsForUser($user, [...])` | No |
| **Action Items** | `getActionItems` | `getApprovedOwnedProjectsForUser($user)`, getOwnedProjectIds, getRevertedOwnedProjectsForUser | No |
| **Deadlines** | `getUpcomingDeadlines` | `getApprovedOwnedProjectsForUser($user)` | No |
| **Project table Budget/Utilization** | `enhanceProjectsWithMetadata` | Paginated owned / in-charge items (already split) | Via list FY filter |
| **Project Budgets Overview widget** | `$budgetSummaries` | Owned only, FY-scoped | Yes |

### 1.3 Methods involved (summary)

- **ProjectQueryService:** getOwnedProjectsQuery, getInChargeProjectsQuery, getApprovedOwnedProjectsForUser, getOwnedProjectIds, getInChargeProjectIds, getRevertedOwnedProjectsForUser, getEditableOwnedProjectsForUser, applySearchFilter.
- **ExecutorController (private):** calculateBudgetSummariesFromProjects, enhanceProjectsWithMetadata, getActionItems, getUpcomingDeadlines, getChartData, getReportChartData, getQuickStats, getProjectHealthSummary, buildProjectChartData, getProjectsRequiringAttention, getReportsRequiringAttention, getReportStatusSummary.
- **Resolver:** ProjectFinancialResolver::resolve(Project $project).
- **Helper:** FinancialYearHelper::currentFY(), listAvailableFY().

---

## 2️⃣ Dataset Scope Options (Owned / In-Charge / Combined)

### 2.1 ProjectQueryService filters (existing)

| Method | user_id | in_charge | province_id | Approved status | FY |
|--------|---------|-----------|-------------|-----------------|-----|
| getOwnedProjectsQuery($user) | = user.id | — | if set | — | — |
| getInChargeProjectsQuery($user) | != user.id | = user.id | if set | — | — |
| getApprovedOwnedProjectsForUser($user, $with, $fy) | = user.id | — | if set | yes | optional |
| getProjectIdsForUser($user) | = user.id **or** in_charge = user.id | — | if set | — | — |
| getProjectsForUserQuery($user) | = user.id or in_charge = user.id | — | if set | — | — |
| getApprovedProjectsForUser($user, $with) | owner or in_charge | — | if set | yes | **no** |

### 2.2 Deriving the three scope datasets

| Dataset | Description | Existing support | Gap |
|---------|-------------|------------------|-----|
| **A — Owned** | user_id = user.id, approved, optional FY | `getApprovedOwnedProjectsForUser($user, $with, $fy)` | None. |
| **B — In-Charge** | in_charge = user.id, user_id != user.id, approved, optional FY | `getInChargeProjectsQuery($user)` exists; no approved+FY helper. | New method needed, e.g. `getApprovedInChargeProjectsForUser($user, $with, $fy)`, or apply status + inFinancialYear on getInChargeProjectsQuery in controller. |
| **C — Owned + In-Charge** | (user_id = user.id or in_charge = user.id), approved, optional FY | `getApprovedProjectsForUser($user, $with)` gives approved owner or in-charge but **no FY parameter**. | Add optional `$financialYear` to getApprovedProjectsForUser, or add `getApprovedProjectsForUserInFinancialYear($user, $with, $fy)` that uses getProjectsForUserQuery + status + inFinancialYear. |

**Conclusion:** Dataset A is fully supported. B and C are derivable from existing query builders; one or two small service methods (approved + FY for in-charge and/or combined) would keep the controller simple and reusable.

---

## 3️⃣ Financial Aggregation Impact

### 3.1 calculateBudgetSummariesFromProjects($projects, $request)

- **Input:** Any collection of `Project` models (with relations loadable).
- **Logic:** Loops over `$projects`, calls `$resolver->resolve($project)`, uses `opening_balance` for total_budget; sums expenses from `$project->reports` (approved vs unapproved). No assumption about ownership.
- **Conclusion:** Works for any project set (owned, in-charge, or combined). **No change needed** if the controller passes the correct collection per scope.

### 3.2 getChartData($user, $request)

- **Current:** `$projects = ProjectQueryService::getApprovedOwnedProjectsForUser($user, [...])`; then resolver per project; no FY.
- **For scope selector:** Would need to receive scope (and FY) and load the appropriate set (A, B, or C). Aggregation logic (resolver, by project_type, monthly expenses from reports) is scope-agnostic.
- **Conclusion:** Works for in-charge or combined if passed the right project collection; requires controller to pass scope + FY and use a shared “approved projects for executor” helper that returns the chosen dataset.

### 3.3 getQuickStats($user)

- **Current:** Uses getOwnedProjectsQuery / getApprovedOwnedProjectsForUser for counts and budget totals; no FY.
- **Conclusion:** Same as getChartData: switch to a scope-aware dataset (and FY) in the controller; aggregation and resolver usage remain valid.

### 3.4 enhanceProjectsWithMetadata($projects)

- **Current:** Receives already-fetched items (owned list or in-charge list); calls resolver per project.
- **Conclusion:** Resolver depends only on project; works for owned, in-charge, or any mix. No change to this method for scope; only the source of the lists (and optional scope-driven totals) would change.

**Summary:** All financial aggregations use resolver and project-level data only; none assume “current user is owner.” They will work correctly for in-charge and combined scope provided the controller supplies the right project set per scope (and FY).

---

## 4️⃣ Resolver Compatibility

- **Signature:** `ProjectFinancialResolver::resolve(Project $project, bool $force = false): array`.
- **Input:** Single `Project`; no user or role parameter.
- **Logic:** Strategy by project_type; canonical separation uses `$project->isApproved()`, `$project->opening_balance`, `$project->amount_sanctioned`, etc. All from the project model.
- **Conclusion:** Resolver is **independent of ownership**. Results are correct for owned projects, in-charge projects, and combined datasets. **No resolver change** required for scope selector or dynamic FY.

---

## 5️⃣ Report Page Consistency

### 5.1 Report pages and dataset

| Page | Project/report source | Scope |
|------|------------------------|--------|
| reportList | `getProjectIdsForUser($user)` → reports for those projects → `calculateBudgetSummaries($reports)` | **Owner or in-charge** |
| pendingReports | Same | **Owner or in-charge** |
| approvedReports | Same | **Owner or in-charge** |

`getProjectIdsForUser($user)` returns project IDs where `user_id = $user->id` **or** `in_charge = $user->id`. So report list budget summaries are **already** owned + in-charge (report-based totals).

### 5.2 Inconsistency with main dashboard

- **Main dashboard:** Financial totals = **owned only** (and FY-scoped for budget overview).
- **Report list pages:** Budget summaries = **owned + in-charge** (report-level; no FY filter on project set).

Introducing a scope selector on the dashboard (Owned / In-Charge / Owned + In-Charge) would **align** the dashboard with the report pages when “Owned + In-Charge” is selected. Keeping default “Owned” preserves current dashboard behaviour while allowing consistency when users choose combined scope. No change to report pages is required for the new selector; optional follow-up could add FY to report-list summaries if desired.

---

## 6️⃣ FY Filtering Coverage

### 6.1 Where inFinancialYear($fy) is applied

- **executorDashboard:**  
  - `$ownedBaseQuery->inFinancialYear($fy)`  
  - `$inChargeProjectsQuery->inFinancialYear($fy)`  
  - `getApprovedOwnedProjectsForUser($user, ..., $fy)`  
  - `getOwnedProjectsQuery($user)->inFinancialYear($fy)` for `$projectTypes`

### 6.2 Widget / data respect for FY

| Widget / data | Uses FY? | Notes |
|---------------|----------|--------|
| **Project Budgets Overview** | Yes | Fed by getApprovedOwnedProjectsForUser(..., $fy). |
| **Project tables (Owned / In-Charge)** | Yes | Built from queries that have inFinancialYear($fy). |
| **Quick Stats** | No | getApprovedOwnedProjectsForUser($user) with no $fy. |
| **Budget Analytics (getChartData)** | No | Same. |
| **Action Items** | No | getApprovedOwnedProjectsForUser($user), no $fy. |
| **Deadlines (getUpcomingDeadlines)** | No | Same. |
| **ownedCount / inChargeCount** | No | Raw getOwnedProjectsQuery / getInChargeProjectsQuery count, no FY. |
| **projectTypes** | Yes | InFinancialYear($fy) applied. |

**Conclusion:** Scope selector and dynamic FY should be implemented together with passing `$fy` (and scope) into getChartData, getQuickStats, getActionItems, getUpcomingDeadlines, and optionally into count queries, so that all widgets respect the selected FY (and scope).

---

## 7️⃣ Dynamic FY Selector Feasibility

### 7.1 Current FY list

- **Source:** `FinancialYearHelper::listAvailableFY(int $yearsBack = 10)`.
- **Logic:** Purely date-based: current FY then previous 9 years (e.g. 2025-26 down to 2016-17). No DB read. No project data.
- **Used by:** ExecutorController, ProvincialController, CoordinatorController, GeneralController (each calls `listAvailableFY()` for dropdowns).

### 7.2 Data-driven FY from projects

- **Field:** `projects.commencement_month_year` (nullable; stored as date or date string, e.g. Y-m-d).
- **Approach:**  
  - Query distinct `commencement_month_year` for the relevant project set (e.g. owned, in-charge, or both, depending on scope).  
  - For each value, derive FY with `FinancialYearHelper::fromDate(Carbon::parse($commencement_month_year))`.  
  - Deduplicate FY labels and sort (e.g. newest first).  
- **SQL option:** No single “DISTINCT FY” in SQL without application logic; typically select distinct `commencement_month_year` (or distinct year/month), then map to FY in PHP.  
- **Scope:** For executor, the “relevant” set depends on scope (owned, in-charge, or both). So dynamic FY list may depend on scope and user (e.g. `listAvailableFYForExecutor($user, $scope)` or similar).

### 7.3 Backward compatibility

- Other dashboards (Provincial, Coordinator, General) use `listAvailableFY()` with no parameters. They do **not** depend on executor-specific scope.
- **Options:**  
  - **A:** Add a new method, e.g. `FinancialYearHelper::listAvailableFYFromProjects(Builder $projectQuery)` or `listAvailableFYForUser(User $user, string $scope)`, and use it only in ExecutorController. Other controllers keep using `listAvailableFY()`.  
  - **B:** Add an optional parameter to `listAvailableFY($yearsBack = 10, ?Builder $projectQuery = null)` and when `$projectQuery` is provided, derive FY from query result; otherwise keep current behaviour.  
- **Conclusion:** Dynamic FY for the executor is **feasible** without breaking other dashboards, by either a new helper or an optional parameter. Executor would pass the scoped project query (owned, in-charge, or combined) so the dropdown only shows FYs that have at least one project in the selected scope.

---

## 8️⃣ Blade UI Integration Feasibility

### 8.1 Current layout

- **Main view:** `resources/views/executor/index.blade.php` (extends `executor.dashboard`).
- **Filters:** Collapsible “Filters” panel with a **GET** form to `route('executor.dashboard')` containing:
  - **Financial Year:** `<select name="fy" id="fy">` with `@foreach($availableFY ?? [] as $year)`.
  - Search, project_type, sort_by, sort_order, per_page, show (approved/needs_work/all).
- **Project Budgets Overview widget:** Its own form with project_type filter only; **does not** currently pass `fy` (or scope), so submitting it can drop FY.

### 8.2 Adding Scope Selector

- **Placement:** Same form as FY (e.g. next to FY in the filters row), or a dedicated “Scope” dropdown.
- **Options:** `owned` | `in_charge` | `owned_and_in_charge` (or equivalent). Default `owned`.
- **Implementation:** Add `<select name="scope" id="scope">` with three options; preserve `scope` in request (e.g. hidden input in widget form). Controller reads `$scope = $request->input('scope', 'owned')` and uses it to choose dataset A, B, or C for budget summary, chart data, quick stats, and optionally for project lists if product wants lists to follow scope (currently lists are fixed: one owned, one in-charge).
- **Conclusion:** Layout already supports dropdowns and GET forms; adding a scope selector is **feasible** with minimal layout change. Existing pattern (FY, project_type, etc.) can be replicated.

### 8.3 Dynamic FY in Blade

- **Current:** Blade iterates `$availableFY` from controller.  
- **With dynamic FY:** Controller passes `$availableFY` derived from project data (and scope) instead of `listAvailableFY()`. Blade remains `@foreach($availableFY ?? [] as $year)`.  
- **Conclusion:** No blade change required beyond ensuring `$availableFY` is always an array (e.g. fallback to current FY if query returns none).

### 8.4 Partials

- Widgets live under `resources/views/executor/widgets/`. They receive data from the controller; they do not choose scope or FY.  
- **Conclusion:** No structural change to partials; they will display whatever totals and charts the controller sends. Ensuring widget forms preserve `fy` and `scope` (e.g. hidden inputs) is sufficient.

---

## 9️⃣ Cache Layer Impact

### 9.1 Current usage

- **ExecutorController:** No `cache()->remember()` or `Cache::` usage. Dashboard data is computed on every request.
- **Other controllers:** GeneralController, CoordinatorController, ProvincialController, and AdminReadOnlyController use cache for various filters and heavy data; keys sometimes include `$fy` or user context. Executor is not among them.

### 9.2 If cache is introduced later

- Any future cache for executor dashboard should include in the key at least:
  - **user_id** (or auth id),
  - **fy** (selected financial year),
  - **scope** (owned / in_charge / owned_and_in_charge).
- **Example:** `executor_dashboard_{$userId}_{$scope}_{$fy}` (and optionally other filter params if cached).  
- **Conclusion:** No cache exists today; adding scope (and keeping FY in key) when cache is introduced will avoid serving wrong scope/FY. No immediate code change; document the key pattern for future use.

---

## 🔟 Performance Considerations

### 10.1 Query impact of scope options

- **Owned only (current):** One main project set from getApprovedOwnedProjectsForUser (with optional FY).  
- **In-charge only:** One set from getInChargeProjectsQuery + approved + FY; similar complexity.  
- **Owned + In-charge:** One set from getProjectsForUserQuery + approved + FY; same filters, broader set (union of owner and in-charge).  
- **Conclusion:** Same number of “main” project queries per request; only the predicate (user_id vs in_charge vs both) changes. Combined scope may return more rows; aggregation (resolver per project) scales with collection size, not with extra queries. No inherent N+1 from adding scope.

### 10.2 Resolver and N+1

- **Current:** Controller loads projects with eager loading (`reports`, `reports.accountDetails`, `budgets`). `calculateBudgetSummariesFromProjects` and `enhanceProjectsWithMetadata` iterate and call `$resolver->resolve($project)`. Resolver does not run additional DB queries; it uses project attributes and strategy logic.  
- **Conclusion:** No N+1 from resolver. Eager loading remains important for reports/accountDetails when aggregating expenses; same pattern applies to any scope. If a new “approved projects for scope” helper is used, ensure it receives the same `$with` (e.g. reports.accountDetails, budgets) so that expense aggregation does not trigger lazy loads.

### 10.3 Dynamic FY query

- **Extra cost:** One additional query per request to derive distinct commencement_month_year (or similar) for the scoped project set, then PHP loop to map to FY.  
- **Mitigation:** Query can be a simple distinct select on an indexed column; result set is small (handful of years). Acceptable for dashboard load. Optional: cache the list per user/scope for a short TTL if needed.

---

## 11️⃣ Implementation Risk Assessment

| Risk | Level | Mitigation |
|------|--------|------------|
| Breaking existing “owned only” default | Low | Default `scope=owned` and use same getApprovedOwnedProjectsForUser path as today. |
| Resolver or aggregation wrong for in-charge | Low | Resolver is project-only; aggregations are collection-agnostic; verified in this audit. |
| Report pages inconsistent with dashboard | Low | Report pages already use owner+in-charge; scope selector makes dashboard able to match. No change required to report pages for scope. |
| Other dashboards broken by FY change | Low | Keep listAvailableFY() unchanged; add new helper or optional param for executor-only dynamic FY. |
| Blade form dropping fy/scope | Medium | Add hidden inputs for `fy` and `scope` in Project Budgets Overview (and any other) widget form that submits to dashboard. |
| Widgets ignoring FY/scope | Medium | Pass $fy and scope into getChartData, getQuickStats, getActionItems, getUpcomingDeadlines (and optionally counts) so all widgets respect selection. |
| Missing service method for B/C | Low | Add getApprovedInChargeProjectsForUser(..., $fy) and/or extend getApprovedProjectsForUser with optional $fy (or new method). Document and test. |

---

## 12️⃣ Recommended Implementation Strategy

1. **Scope selector (backend)**  
   - Add request parameter `scope` with default `owned`.  
   - Introduce (or reuse) a single helper that returns the **approved project collection** for the executor for the chosen scope and FY, e.g.:
     - `getApprovedProjectsForExecutorScope(User $user, string $scope, array $with = [], ?string $fy = null)`  
     - implementing A via getApprovedOwnedProjectsForUser, B via new getApprovedInChargeProjectsForUser, C via getProjectsForUserQuery + approved + inFinancialYear($fy).  
   - Use this helper for: budget summary, getChartData, getQuickStats, and optionally action items/deadlines so they all respect scope and FY.

2. **Scope selector (frontend)**  
   - Add a scope dropdown in the existing filters form (same row as FY), with values e.g. `owned`, `in_charge`, `owned_and_in_charge`.  
   - Preserve `scope` (and `fy`) in all GET forms that submit to the dashboard (including Project Budgets Overview widget).

3. **Dynamic FY (backend)**  
   - Add a method that returns FY list from project data for the current user and scope, e.g. `FinancialYearHelper::listAvailableFYFromProjectQuery(Builder $query)` or `ProjectQueryService::getAvailableFYForExecutor(User $user, string $scope)`.  
   - Ensure the query uses the same scope as the dashboard (owned, in-charge, or both) and filters by province_id where applicable.  
   - In ExecutorController, call this instead of `listAvailableFY()` when building `$availableFY`; fallback to current FY (or static list) if the result is empty.

4. **Dynamic FY (frontend)**  
   - No change: continue to bind `$availableFY` and `$fy` in the same way; only the source of `$availableFY` changes.

5. **Widget consistency**  
   - Pass `$fy` and `$scope` into getChartData, getQuickStats, getActionItems, getUpcomingDeadlines.  
   - Optionally scope ownedCount/inChargeCount by FY (or leave as all-time counts and document).  
   - Ensure projectTypes (and any other filter options) are derived from the same scoped + FY-filtered set if they should match the selected scope.

6. **Cache (future)**  
   - If dashboard caching is added, use cache keys that include `user_id`, `scope`, and `fy` (e.g. `executor_dashboard_{$userId}_{$scope}_{$fy}`).

7. **Testing and docs**  
   - Test all three scopes with a user who has both owned and in-charge projects; verify totals and FY list.  
   - Document that default remains “Owned” and that “Owned + In-Charge” aligns with report list pages’ effective scope.

---

**Audit performed without modifying any application code. Findings are from static analysis and existing code paths.**  

**Summary:** Introducing a scope selector (Owned / In-Charge / Owned + In-Charge) and a data-driven FY selector is **feasible** and **compatible** with the current architecture. Resolver and aggregations require no change; the main work is request handling (scope + FY), one or two project-query helpers, dynamic FY derivation, and ensuring all widgets and forms respect scope and FY.
