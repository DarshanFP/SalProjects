# Executor Dashboard Implementation Plan — Feasibility Audit

**Date:** 2026-03-04  
**Document audited:** Executor_Dashboard_Scope_FY_Phased_Implementation_Plan.md  
**Method:** Static analysis of codebase; no application code modified.

---

## 1. Controller Compatibility

### 1.1 Method existence and signatures

| Method | Exists | Current signature | Notes |
|--------|--------|-------------------|-------|
| executorDashboard() | Yes | `executorDashboard(Request $request)` | Public; passes `$fy`, `$availableFY` to view |
| getChartData() | Yes | `getChartData($user, $request)` | Private; no FY/scope params |
| getQuickStats() | Yes | `getQuickStats($user)` | Private; no FY/scope params |
| getActionItems() | Yes | `getActionItems($user)` | Private; no FY/scope params |
| getUpcomingDeadlines() | Yes | `getUpcomingDeadlines($user)` | Private; no FY/scope params |
| calculateBudgetSummariesFromProjects() | Yes | `calculateBudgetSummariesFromProjects($projects, $request)` | Private; no resolved financials param |
| enhanceProjectsWithMetadata() | Yes | `enhanceProjectsWithMetadata($projects)` | Private; no resolved financials param |
| buildProjectChartData() | Yes | `buildProjectChartData($ownedFullProjects)` | Private; no resolver usage (counts only) |
| getProjectHealthSummary() | Yes | `getProjectHealthSummary($enhancedProjects)` | Private; receives enhanced array, no resolver |

### 1.2 Parameter introduction safety

- **FY and scope:** All widget methods are private and called only from `executorDashboard()`. Adding optional parameters (e.g. `?string $fy = null`, `?string $scope = null`) with defaults is backward-compatible.
- **Pre-resolved financials:** `calculateBudgetSummariesFromProjects` and `enhanceProjectsWithMetadata` can accept an optional third parameter `?array $resolvedFinancials = null`; when null, fall back to calling `$resolver->resolve($project)` per project. No breaking change.
- **getChartData / getQuickStats:** Already use local memoization (`$resolvedFinancials = []`) per call; adding optional pre-resolved param is compatible.

### 1.3 Dependencies

- All methods use `app(\App\Domain\Budget\ProjectFinancialResolver::class)` or `app(\App\Services\Budget\DerivedCalculationService::class)` via service container.
- No static/global state that would prevent scope or FY propagation.

**Verdict:** Plan assumptions for controller changes are valid. Parameters can be introduced safely.

---

## 2. Query Service Compatibility

### 2.1 Existing methods

| Method | Exists | Returns | FY support |
|--------|--------|---------|------------|
| getOwnedProjectsQuery($user) | Yes | Builder | No (caller applies inFinancialYear) |
| getInChargeProjectsQuery($user) | Yes | Builder | No |
| getProjectsForUserQuery($user) | Yes | Builder (user_id OR in_charge) | No |
| getApprovedOwnedProjectsForUser($user, $with, $financialYear) | Yes | Collection | Yes (optional) |
| getApprovedProjectsForUser($user, $with) | Yes | Collection | No |

### 2.2 Phase 1 additions

- **getApprovedInChargeProjectsForUser:** Does not exist. `getInChargeProjectsQuery` exists and returns `in_charge = user.id AND user_id != user.id`; new method would add status + FY filters. No existing caller would be affected.
- **getApprovedProjectsForUserInFinancialYear:** Does not exist. `getApprovedProjectsForUser` uses `getProjectsForUserQuery` (OR condition) but has no FY param. New method (or extending existing) is additive.
- **getApprovedProjectsForExecutorScope:** Would be a new helper; no impact on existing callers.

### 2.3 Owner + in-charge combined queries

`getProjectsForUserQuery($user)` already uses:

```php
$query->where(function ($q) use ($user) {
    $q->where('user_id', $user->id)->orWhere('in_charge', $user->id);
});
```

This is a single query with OR conditions. No merge of collections; each project row appears at most once. **Compatible with plan's dataset deduplication requirement.**

### 2.4 distinct() compatibility

- Laravel's `distinct()` on Builder produces `SELECT DISTINCT`. For `projects` table, each row has a unique `id`; with a simple `projects` query (no join), rows are inherently unique.
- When `applySearchFilter` is used, it adds `leftJoin('societies', ...)` and `select('projects.*')`. In that case, `distinct()` would deduplicate by full row; projects.id is unique, so no functional change.
- **Caveat:** `distinct('projects.id')` — Laravel's `distinct()` accepts column names. For MySQL, `SELECT DISTINCT projects.id` would require `select('projects.id')` or similar to avoid selecting `*`. Safer approach: use `->distinct()` (no args) which works with `select('projects.*')`, or ensure the base query (without search join) is used for `getApprovedProjectsForUserInFinancialYear` so no join complicates distinct.
- ProjectQueryService methods used for budget summary do not apply `applySearchFilter`; search is applied only to list queries (ownedBaseQuery, inChargeProjectsQuery). So `getApprovedProjectsForUserInFinancialYear` will not use the search join. `distinct()` is safe.

**Verdict:** Phase 1 methods can be added without breaking callers. Single OR-based query for owned_and_in_charge is already supported by `getProjectsForUserQuery`. distinct() is compatible; recommend `->distinct()` without column for simplicity.

---

## 3. Resolver Compatibility

### 3.1 resolve(Project $project) structure

**Signature:** `public function resolve(Project $project, bool $force = false): array`

**Output format:**
```php
[
    'overall_project_budget' => float,
    'amount_forwarded' => float,
    'local_contribution' => float,
    'amount_sanctioned' => float,
    'amount_requested' => float,
    'opening_balance' => float,
]
```

### 3.2 Controller state

- Resolver uses only `$project` (model) and injected `DerivedCalculationService`. No request, session, or user context.
- Strategies (PhaseBasedBudgetStrategy, etc.) use `$project->loadMissing('budgets')` — may trigger DB if `budgets` not eager-loaded. ExecutorController already eager-loads `budgets` for approved projects.

### 3.3 resolveCollection() feasibility

- A batch method `resolveCollection(Collection $projects): array` returning `[project_id => resolved_array]` can be implemented by iterating and calling existing `resolve($project)` for each. Output structure per project is identical.
- No shared state; each project resolves independently. Batch method is a thin wrapper.

**Verdict:** Plan's `resolveCollection()` is feasible. Output structure is stable. Ensure projects have `reports`, `reports.accountDetails`, `budgets` eager-loaded before resolution.

---

## 4. Blade View Compatibility

### 4.1 Current view usage

| View | Uses $fy | Uses $availableFY | Uses $scope |
|------|----------|-------------------|-------------|
| executor/index.blade.php | Yes (select, badges) | Yes (@foreach $availableFY) | No |
| executor/widgets/project-budgets-overview.blade.php | No (form does not pass fy) | N/A | No |
| executor/widgets/quick-stats.blade.php | No | N/A | No |
| executor/widgets/budget-analytics.blade.php | No | N/A | No |
| executor/widgets/action-items.blade.php | No | N/A | No |
| executor/widgets/upcoming-deadlines.blade.php | No | N/A | No |

### 4.2 Widget existence

All referenced widgets exist:

- project-budgets-overview
- quick-stats
- budget-analytics
- action-items
- upcoming-deadlines

### 4.3 Parameter addition

- **$scope:** Not used today. Adding `$scope` to `compact()` and a scope dropdown in the form is additive. Blade will receive it; no view currently expects it, so no break.
- **$fy, $availableFY:** Already passed. Plan preserves this.
- **Form preservation:** project-budgets-overview form currently has no hidden `fy` or `scope` inputs. Plan correctly identifies adding them.

**Verdict:** Views are compatible. Adding `$scope` and hidden inputs for `fy`/`scope` in project-budgets-overview is safe.

---

## 5. Dynamic FY Feasibility

### 5.1 projects.commencement_month_year

**Schema:** `$table->date('commencement_month_year')->nullable();`

- **Type:** DATE
- **Nullable:** Yes
- **Format:** Y-m-d (e.g. 2025-10-01)

### 5.2 Dynamic derivation

- `FinancialYearHelper::fromDate(Carbon $date)` accepts Carbon; `Carbon::parse($commencement_month_year)` works for date strings.
- `whereNotNull('commencement_month_year')` filters nulls.
- `->distinct()->pluck('commencement_month_year')` yields unique dates for the scoped query.

### 5.3 FinancialYearHelper

- `listAvailableFY()` is static; no params. Other controllers (Coordinator, Provincial, General) call it. Plan does not modify it; new `listAvailableFYFromProjects()` is additive.
- **Isolation:** ExecutorController would be the only caller of `listAvailableFYFromProjects()`. Other dashboards unaffected.

**Verdict:** Dynamic FY from project data is feasible. Schema and helper support it.

---

## 6. Dataset Deduplication Safety

### 6.1 Merge vs single query

- **Merge risk:** Merging `$owned->merge($inCharge)` can duplicate a project when `user_id = executor_id` AND `in_charge = executor_id`.
- **Single-query approach:** `getProjectsForUserQuery($user)` uses `WHERE (user_id = X OR in_charge = X)`. One row per project; no duplicate rows.
- **Conclusion:** Using `getApprovedProjectsForUserInFinancialYear` (based on getProjectsForUserQuery) for scope `owned_and_in_charge` satisfies the plan. No merge.

### 6.2 distinct() with eager loading

- `getApprovedProjectsForUserInFinancialYear` would use `$query->with($with)->get()`. Adding `->distinct()` before `get()`:
  - For MySQL, `SELECT DISTINCT projects.*` with eager loading: Laravel runs the main query with DISTINCT, then eager-loads in separate queries by id. No conflict.
  - Pagination: The plan uses this for the budget summary dataset, not for paginated lists. Pagination uses `ownedBaseQuery` / `inChargeProjectsQuery` separately. No pagination on the combined scope for budget summary.
- **Caveat:** If `applySearchFilter` (with leftJoin) is ever applied to the combined query, `distinct()` with `select('projects.*')` can be problematic if the join produces duplicate project rows. The budget summary path does not use search filter, so this is not an issue.

**Verdict:** Deduplication via single OR query is safe. distinct() does not break eager loading for this use case.

---

## 7. Performance Impact Analysis

### 7.1 Current resolver usage

- **calculateBudgetSummariesFromProjects:** N projects → N `resolve()` calls
- **getChartData:** N projects → N `resolve()` calls (memoized within the method)
- **getQuickStats:** N projects → N `resolve()` calls (memoized within the method)
- **enhanceProjectsWithMetadata:** Called for owned items + in-charge items + full owned. Three separate calls; each iterates and resolves. Total: `ownedPaginatedCount + inChargePaginatedCount + ownedFullCount` resolver calls, with overlap (owned appears in paginated and full).
- **buildProjectChartData:** No resolver; status/type counts only.
- **getProjectHealthSummary:** No resolver; consumes pre-enhanced data.

Approximate: For 50 owned + 20 in-charge, budget summary resolves 50, chart resolves 50, quick stats resolves 50, enhanceMetadata resolves 50+20+50 = 120 (with overlap). Realistic total ~250–350 resolver calls for a single dashboard load.

### 7.2 resolveCollection() effect

- Single pass over the union of projects needed for budget + charts + quick stats + metadata (effectively the approved project set for the scope).
- One resolve per project; result reused across methods.
- Expected reduction: from ~5–7× per project to 1×. **Plan's 5×–10× improvement estimate is reasonable.**

### 7.3 Strategy DB usage

- PhaseBasedBudgetStrategy calls `$project->loadMissing('budgets')`. If `budgets` is not loaded, this triggers a query. ExecutorController eager-loads `budgets`, so typically no extra queries.
- Recommendation: Ensure `resolveCollection()` receives projects with the same `$with` as current usage (`reports`, `reports.accountDetails`, `budgets`).

**Verdict:** Phase 2.6 optimization is feasible and will reduce resolver invocations significantly.

---

## 8. Regression Risk Assessment

### 8.1 Coordinator dashboard

- Uses `FinancialYearHelper::listAvailableFY()` only.
- Does not call ExecutorController or ProjectQueryService methods used for executor scope.
- **Risk:** None if `listAvailableFY()` is unchanged.

### 8.2 Provincial dashboard

- Uses `listAvailableFY()` only.
- Uses `Project::accessibleByUserIds()`, `calculateBudgetSummariesFromProjects` (its own controller method), etc. No ExecutorController or executor-specific ProjectQueryService.
- **Risk:** None.

### 8.3 General dashboard

- Uses `listAvailableFY()` only.
- **Risk:** None.

### 8.4 Report pages (reportList, pendingReports, approvedReports)

- Use `ProjectQueryService::getProjectIdsForUser($user)` for report fetching.
- Plan does not modify `getProjectIdsForUser` or report page logic.
- **Risk:** None.

### 8.5 ProjectController

- Uses `ProjectQueryService::getProjectsForUserQuery($user)`.
- Plan adds new methods; does not change `getProjectsForUserQuery`.
- **Risk:** None.

**Verdict:** Plan is isolated to Executor dashboard. No regression expected for other dashboards or report pages.

---

## 9. Recommended Adjustments to Implementation Plan

1. **distinct() usage:** Prefer `->distinct()` without column argument for `getApprovedProjectsForUserInFinancialYear`. Laravel/MySQL handles this cleanly. If `distinct('projects.id')` is used, verify behaviour with your Laravel and MySQL versions, as column-specific distinct can interact differently with selects and joins.

2. **Resolver strategies:** PhaseBasedBudgetStrategy uses `loadMissing('budgets')`. Ensure the project collection passed to `resolveCollection()` (and to all aggregation methods) is eager-loaded with `reports`, `reports.accountDetails`, and `budgets` so no N+1 occurs during resolution.

3. **buildProjectChartData:** Plan lists it as receiving `?array $resolvedFinancials`. In the current codebase, `buildProjectChartData` does not use resolver output; it only counts status and type. No need to pass resolved financials to it. Adjust Phase 2.6 to exclude `buildProjectChartData` from the pre-resolved data consumers, or leave it as-is since it does not use resolver.

4. **getProjectHealthSummary:** Receives output of `enhanceProjectsWithMetadata`, which uses resolver. Optimisation flows through `enhanceProjectsWithMetadata`; no change needed for `getProjectHealthSummary` itself.

5. **Project model table:** Confirm Project model uses `projects` table (Laravel default for `Project`). Migration defines `projects` table with `commencement_month_year`. Ensure `listAvailableFYFromProjects` receives a builder scoped to the correct table.

6. **Phase 2.6 execution order:** Phase 2.6 should run after Phase 2.5 (widgets receive FY) and before Phase 3 (scope). Pre-resolved data will initially be for the owned scope only; when Phase 3 introduces scope, the resolved dataset must match the scoped project set. No conflict if the same project collection used for budget summary is passed to `resolveCollection()` and then to aggregation methods.
