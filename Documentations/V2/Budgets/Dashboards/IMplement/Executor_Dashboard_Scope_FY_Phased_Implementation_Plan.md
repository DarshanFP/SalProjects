# Executor Dashboard Scope & Dynamic FY — Phased Implementation Plan

**Date:** 2026-03-04  
**Based on:** Executor_Dashboard_FY_Budget_Audit, Executor_Budget_Scope_Architecture_Audit, Executor_Dashboard_Scope_Change_Impact_Audit, Dynamic_FY_Selector_Feasibility_Audit  
**Status:** Planning — no code modified

---

## 1. Background

The Executor dashboard currently displays financial totals (Total Budget, Total Funds, expenses, remaining) for **owned projects only**, with a static FY dropdown derived from the current date (last 10 years). Audits identified:

- Executors with no owned projects in the default FY see Total Budget = 0, even when they have in-charge projects or owned projects in other FYs.
- The FY list does not include future FYs (e.g. 2026-27), so executors cannot select FYs where they have upcoming projects.
- Report list pages (reportList, pendingReports, approvedReports) use **owner + in-charge** scope for budget summaries, while the main dashboard uses **owned only**, causing inconsistency.
- Several dashboard widgets (Quick Stats, Budget Analytics, Action Items, Deadlines) ignore FY, so totals can disagree with the Project Budgets Overview when the user changes FY.

---

## 2. Problem Summary

| Issue | Current behaviour | Desired behaviour |
|-------|-------------------|-------------------|
| Budget scope | Owned only | Scope selector: Owned / In-Charge / Owned + In-Charge (default: Owned) |
| FY list | Static (10 years from today) | Data-driven from project `commencement_month_year` |
| FY adaptation | Same list for all users | FY list adapts to selected scope dataset |
| Widget FY | Only Project Budgets Overview + tables use FY | All financial widgets respect FY and scope |
| Form preservation | Project Budgets Overview form drops `fy` | All forms preserve `fy` and `scope` |

---

## 3. Architecture Goals

1. **Scope selector:** Allow executors to view financial totals for Owned, In-Charge, or Owned + In-Charge projects; default remains Owned.
2. **Dynamic FY:** Derive FY dropdown from project data (`commencement_month_year`) scoped to the selected dataset.
3. **Consistency:** All dashboard widgets use the same scope and FY for financial and project-related data.
4. **Backward compatibility:** Default scope = Owned and default FY = `currentFY()`; existing behaviour unchanged unless user selects differently.
5. **Isolation:** Changes apply to Executor dashboard only; Provincial, Coordinator, General dashboards remain unchanged.

---

## 4. Feasibility Confirmation

The implementation plan was validated against the current codebase in *Executor_Dashboard_Implementation_Plan_Feasibility_Audit.md* and confirmed compatible with:

- **ExecutorController architecture** — All referenced methods exist; optional FY, scope, and pre-resolved financials parameters can be introduced without breaking changes.
- **ProjectQueryService structure** — Phase 1 methods can be added safely; `getProjectsForUserQuery` already uses OR conditions for owner + in-charge; no merge required.
- **Financial resolver design** — `resolve(Project $project)` has a stable output format; `resolveCollection()` can reuse it; strategies rely on project data only.
- **Blade view layout** — `$fy` and `$availableFY` are already in use; adding `$scope` and hidden inputs is compatible.
- **Project schema** — `commencement_month_year` is `date`, nullable; dynamic FY derivation from project data is feasible.

---

## 5. Implementation Principles

- **Default scope = Owned:** No behavioural change unless the user selects another scope.
- **Dynamic FY:** Derive FY list from project data; fallback to static list (or include current FY) when no projects exist.
- **Resolver unchanged:** `ProjectFinancialResolver` and aggregation logic stay as-is; they are scope-agnostic.
- **No impact on other dashboards:** Coordinator, Provincial, General continue using `listAvailableFY()`.
- **Backward compatibility:** Existing URLs, request params, and view contracts remain valid.

---

## 6. Phase-Wise Implementation Plan

---

### Phase 1 — Scope Architecture Preparation

**Objective:** Add ProjectQueryService methods for in-charge and combined approved datasets with FY support, without changing ExecutorController behaviour.

**Files affected:**
- `app/Services/ProjectQueryService.php`

**Implementation steps:**
1. Add `getApprovedInChargeProjectsForUser(User $user, array $with = [], ?string $financialYear = null)`:
   - Base: `getInChargeProjectsQuery($user)`
   - Filter: `whereIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])`
   - Optional: `->inFinancialYear($financialYear)` when `$financialYear !== null`
   - Optional: `->with($with)` when non-empty
   - Return: `$query->get()`
2. Add `getApprovedProjectsForUserInFinancialYear(User $user, array $with = [], ?string $financialYear = null)` (or extend `getApprovedProjectsForUser` with optional `$financialYear`):
   - Base: `getProjectsForUserQuery($user)` (single query with `user_id = X OR in_charge = X`)
   - Filter: `whereIn('status', [...approved statuses...])`
   - Enforce uniqueness: `->distinct()` to prevent duplicate rows when a project matches both owner and in-charge. Laravel handles DISTINCT safely when selecting `projects.*` and eager-loading relations; no need for column-specific distinct.
   - Optional: `->inFinancialYear($financialYear)` when `$financialYear !== null`
   - Optional: `->with($with)`
   - Return: `$query->get()`
3. Add (optional) `getApprovedProjectsForExecutorScope(User $user, string $scope, array $with = [], ?string $fy = null)`:
   - `$scope = 'owned'` → `getApprovedOwnedProjectsForUser($user, $with, $fy)`
   - `$scope = 'in_charge'` → `getApprovedInChargeProjectsForUser($user, $with, $fy)`
   - `$scope = 'owned_and_in_charge'` → `getApprovedProjectsForUserInFinancialYear($user, $with, $fy)`
   - Return unified collection for aggregation

**Dataset Deduplication Guarantee**

Owned + In-Charge datasets must guarantee unique project IDs. A project can theoretically appear in both sets when `user_id = executor_id` AND `in_charge = executor_id`, since this situation is not prevented by database constraints. If datasets are merged using collection operations such as `$owned->merge($inCharge)`, the same project may appear twice, causing financial aggregation methods to double-count values.

**Preferred implementation:** Use a single query with OR conditions instead of merging two collections:

```php
Project::query()
    ->where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhere('in_charge', $user->id);
    })
```

Ensure uniqueness by applying `->distinct()` so each project appears only once in the result set. Laravel handles DISTINCT safely when selecting `projects.*` and eager-loading relations. The existing `getProjectsForUserQuery()` and `getApprovedProjectsForUserInFinancialYear()` follow this pattern and must be used for scope `owned_and_in_charge` — do **not** implement combined scope by merging `$owned->merge($inCharge)`.

**Risks:** Low. New methods only; no change to existing call paths.

**Validation checklist:**
- [ ] Unit tests for new methods with FY and without FY
- [ ] Verify no duplicate projects when scope = owned_and_in_charge (owner and in-charge are mutually exclusive for a project)
- [ ] Province filter applied correctly for all scopes

---

### Phase 2 — Dynamic FY Infrastructure

**Objective:** Add `FinancialYearHelper::listAvailableFYFromProjects()` to derive FY list from a project query, with fallback.

**Files affected:**
- `app/Support/FinancialYearHelper.php`

**Implementation steps:**
1. Add static method `listAvailableFYFromProjects(\Illuminate\Database\Eloquent\Builder $projectQuery): array`:
   - Apply `$projectQuery->whereNotNull('commencement_month_year')` (or ensure it is in the passed query)
   - `->distinct()->pluck('commencement_month_year')`
   - For each date: `FinancialYearHelper::fromDate(Carbon::parse($date))`
   - Collect unique FY strings, sort descending (newest first)
   - Return as array
2. Add fallback logic:
   - If result is empty: merge with `listAvailableFY()` or include at least `currentFY()`
   - Ensure selected FY is always in the list when it was previously valid
3. **Do not modify** `listAvailableFY()` — keep it unchanged for other dashboards

**Risks:** Low. New method; existing `listAvailableFY()` untouched.

**Validation checklist:**
- [ ] Unit test with empty query → fallback includes current FY
- [ ] Unit test with sample dates → correct FY derivation and sort order
- [ ] Verify Coordinator, Provincial, General still use `listAvailableFY()` only

---

### Phase 2.5 — Widget FY Consistency Fix

**Objective:** Pass `$fy` (and prepare for `$scope`) into widget methods so all financial and project-related widgets respect the selected FY.

**Files affected:**
- `app/Http/Controllers/ExecutorController.php`

**Implementation steps:**
1. Update `getChartData($user, $request)` to accept `?string $fy = null`:
   - When `$fy !== null`, use `getApprovedOwnedProjectsForUser($user, $with, $fy)` instead of without FY
   - Keep existing aggregation logic
2. Update `getQuickStats($user)` to accept `?string $fy = null`:
   - Same pattern: pass `$fy` into `getApprovedOwnedProjectsForUser` when provided
3. Update `getActionItems($user)` to accept `?string $fy = null`:
   - Pass `$fy` into `getApprovedOwnedProjectsForUser` for overdue/approved project logic when provided
4. Update `getUpcomingDeadlines($user)` to accept `?string $fy = null`:
   - Same pattern
5. In `executorDashboard()`, pass `$fy` into all four methods: `getChartData($user, $request, $fy)`, `getQuickStats($user, $fy)`, `getActionItems($user, $fy)`, `getUpcomingDeadlines($user, $fy)`
6. Optional: Pass `$fy` into ownedCount/inChargeCount for section badges (or document decision to keep all-time counts)

**Risks:** Low. Additive parameter; default null preserves previous behaviour where callers do not pass FY.

**Validation checklist:**
- [ ] Changing FY in dropdown updates Quick Stats, Budget Analytics
- [ ] Action Items and Deadlines reflect FY-filtered project set
- [ ] No regression when `$fy` is default (current FY)

---

### Phase 2.6 — Financial Resolver Optimization

**Objective:** Improve executor dashboard scalability by ensuring that `ProjectFinancialResolver` is executed only once per project per request. Currently multiple dashboard widgets independently resolve financial data for the same projects, which can lead to 5–7× repeated resolver execution when project counts grow. Resolve financial data once and reuse it across all widgets.

**Files potentially affected:**
- `app/Domain/Budget/ProjectFinancialResolver.php`
- `app/Http/Controllers/ExecutorController.php`
- (Optional new) `app/Services/FinancialResolutionService.php`

**Implementation approach:**

1. **Introduce batch resolution method** such as:
   ```php
   ProjectFinancialResolver::resolveCollection(Collection $projects): array
   ```
   Returns a map keyed by `project_id`:
   ```php
   [
       'project_id' => ['opening_balance' => ..., 'amount_sanctioned' => ..., ...],
       ...
   ]
   ```

2. **Resolve financials once** for the project dataset before running dashboard aggregations. In `executorDashboard()`, call the batch method immediately after fetching the approved project set and pass the result into aggregation methods. **Note:** The project collection passed to `resolveCollection()` must already eager-load `reports`, `reports.accountDetails`, and `budgets`. This prevents resolver strategies (e.g. PhaseBasedBudgetStrategy) from triggering additional database queries via `loadMissing('budgets')`.

3. **Update dashboard aggregation methods** to accept and reuse resolved financial data instead of calling `resolve()` repeatedly. (Exclude `buildProjectChartData` — it does not use ProjectFinancialResolver; it only counts status and type.)
   - `calculateBudgetSummariesFromProjects($projects, $request, ?array $resolvedFinancials = null)`
   - `getChartData($user, $request, $fy, $scope, ?array $resolvedFinancials = null)`
   - `getQuickStats($user, $fy, $scope, ?array $resolvedFinancials = null)`
   - `enhanceProjectsWithMetadata($projects, ?array $resolvedFinancials = null)` (feeds `projectHealthSummary` and table display)
   - **Do not** pass resolved financials to `buildProjectChartData` — it does not use ProjectFinancialResolver; it only counts status and type.
   - When `$resolvedFinancials` is provided, use `$resolvedFinancials[$project->project_id]` instead of `$resolver->resolve($project)`; when null, fall back to per-project resolution for backward compatibility.

4. **Ensure resolver output structure remains unchanged** so existing aggregation logic (e.g. `opening_balance`, `amount_sanctioned`) can adapt safely. The returned array per project must match the structure of `resolve(Project $project)`.

**Performance analysis**

| Scenario | Current resolver calls | Optimized resolver calls | Expected improvement |
|----------|------------------------|--------------------------|----------------------|
| N projects | N × 5–7 (per widget) | N × 1 (single pass) | 5×–10× fewer calls |
| 50 projects | ~250–350 | 50 | 5×–7× faster |
| 100 projects | ~500–700 | 100 | 5×–7× faster |

- **Current:** Each of `calculateBudgetSummariesFromProjects`, `getChartData`, `getQuickStats`, and `enhanceProjectsWithMetadata` may call `$resolver->resolve($project)` for each project in their dataset. (`buildProjectChartData` does not use the resolver.)
- **Optimized:** One call to `resolveCollection()` per request; all aggregation methods receive and reuse the pre-resolved map.
- **Expected improvement:** 5×–10× faster dashboard rendering for large project datasets.

**Risks:** Low. Output structure unchanged; aggregation logic adapts via optional parameter with fallback.

**Validation checklist:**
- [ ] `resolveCollection()` returns same structure per project as `resolve()`
- [ ] All aggregation methods correctly use pre-resolved data when provided
- [ ] Fallback to per-project resolution when `$resolvedFinancials` is null
- [ ] No regression in budget totals or chart/stats values
- [ ] Unit test: resolveCollection output matches repeated resolve() calls

---

### Phase 3 — Executor Scope Selector Backend

**Objective:** Read `scope` from request, default to `owned`; use scope-aware dataset for budget summary, chart data, quick stats; keep project tables (Owned / In-Charge) structurally unchanged.

**Files affected:**
- `app/Http/Controllers/ExecutorController.php`

**Implementation steps:**
1. Add `$scope = $request->input('scope', 'owned')`
2. Validate: `$scope` must be one of `['owned', 'in_charge', 'owned_and_in_charge']`; otherwise default to `owned`
3. Replace budget summary dataset:
   - Current: `$approvedProjectsForSummary = ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $fy)`
   - New: `$approvedProjectsForSummary = ProjectQueryService::getApprovedProjectsForExecutorScope($user, $scope, $with, $fy)` (or equivalent switch on scope)
4. Pass `$scope` into `getChartData($user, $request, $fy, $scope)`, `getQuickStats($user, $fy, $scope)`; update these methods to use scope-aware project set
5. Pass `$scope` into `getActionItems($user, $fy, $scope)`, `getUpcomingDeadlines($user, $fy, $scope)` if product decision is to scope these; otherwise keep owned-only for action items (document choice)
6. Update `$projectTypes` to use scope-aware query:
   - For `owned`: `getOwnedProjectsQuery($user)->inFinancialYear($fy)`
   - For `in_charge`: `getInChargeProjectsQuery($user)->inFinancialYear($fy)`
   - For `owned_and_in_charge`: `getProjectsForUserQuery($user)->inFinancialYear($fy)`
7. Pass `$scope` to view in `compact(...)`

**Note for scope = owned_and_in_charge:** The dataset must be fetched using a single query with OR conditions (e.g. `getApprovedProjectsForUserInFinancialYear` which uses `getProjectsForUserQuery`) rather than merging owned and in-charge collections. This guarantees that each project appears only once in financial aggregation pipelines and prevents double-counting when a project has both `user_id = executor_id` and `in_charge = executor_id`.

**Risks:** Low. Default `scope=owned` preserves current behaviour.

**Validation checklist:**
- [ ] Default scope = owned; budget totals match pre-change behaviour
- [ ] Scope = in_charge shows only in-charge project totals
- [ ] Scope = owned_and_in_charge shows combined totals (no double count per project)
- [ ] Resolver and aggregation produce correct results for each scope

---

### Phase 4 — Executor Scope Selector UI Integration

**Objective:** Add scope dropdown to executor dashboard filters; ensure `fy` and `scope` are preserved across form submissions.

**Files affected:**
- `resources/views/executor/index.blade.php`
- `resources/views/executor/widgets/project-budgets-overview.blade.php`

**Implementation steps:**
1. In main dashboard filter form (executor index), add Scope selector:
   - `<select name="scope" id="scope" class="form-select" onchange="this.form.submit()">`
   - Options: `owned` (Owned), `in_charge` (In-Charge), `owned_and_in_charge` (Owned + In-Charge)
   - Selected: `($scope ?? 'owned') == $optionValue` for each option (e.g. `owned`, `in_charge`, `owned_and_in_charge`)
2. Ensure main form preserves `fy` when `scope` changes (both in same form)
3. In Project Budgets Overview widget form:
   - Add hidden input: `<input type="hidden" name="fy" value="{{ request('fy', $fy ?? '') }}">`
   - Add hidden input: `<input type="hidden" name="scope" value="{{ request('scope', $scope ?? 'owned') }}">`
   - Ensure "Apply Filters" and "Reset" do not drop `fy` or `scope`
4. Update "Active filters" display to show scope when non-default
5. Ensure "Reset" link/button either preserves scope/fy or explicitly clears to defaults (document intended behaviour)

**Risks:** Low. UI addition; fallback to defaults when params missing.

**Validation checklist:**
- [ ] Scope dropdown appears and submits correctly
- [ ] Changing scope updates budget summary, charts, quick stats
- [ ] Project Budgets Overview "Apply Filters" does not clear fy/scope
- [ ] Active filters badge shows scope when selected

---

### Phase 5 — Dashboard Widget Scope Integration

**Objective:** Ensure all financial widgets use scope (and FY); optionally scope action items and deadlines; ensure report status and project health use consistent scope where appropriate.

**Files affected:**
- `app/Http/Controllers/ExecutorController.php`
- Optionally: `resources/views/executor/widgets/*.blade.php` if labels need to reflect scope

**Implementation steps:**
1. Confirm `getChartData`, `getQuickStats` receive and use `$scope` (from Phase 3)
2. Confirm `getActionItems`, `getUpcomingDeadlines` receive `$fy` and `$scope`; use scope-aware approved project set for overdue and deadline logic
3. `reportStatusSummary` and `reportChartData`: decide whether to scope by owned only or by selected scope; implement and document
4. `recentReports`: currently uses `getOwnedProjectIds`; consider whether to scope by selection (e.g. owned vs in-charge vs both)
5. `projectHealthSummary`, `projectChartData`: currently from `$ownedFullProjects`; for scope=in_charge, may need `$inChargeFullProjects` or combined; implement per product decision
6. Document which widgets are scope-aware and which remain owned-only (e.g. "Projects Requiring Attention" may stay owned)

**Risks:** Medium. More call sites; ensure no widget uses wrong dataset.

**Validation checklist:**
- [ ] All financial widgets reflect selected scope and FY
- [ ] Action items and deadlines are scoped correctly (or explicitly owned-only per product choice)
- [ ] Project health and charts use correct scope
- [ ] No N+1 from additional queries

---

### Phase 6 — Validation & Regression Audit

**Objective:** Validate full flow; ensure no regression for default scope and FY; confirm other dashboards unaffected.

**Files affected:**
- Test suite
- Manual test scenarios

**Implementation steps:**
1. Run existing Executor dashboard tests; add tests for scope and dynamic FY
2. Manual test: User 37 (or similar) with owned and in-charge projects:
   - Default scope=owned, FY=current → Total Budget matches pre-implementation
   - Scope=in_charge → Totals reflect in-charge projects only
   - Scope=owned_and_in_charge → Totals reflect combined; FY list includes 2024-25, 2025-26, 2026-27
   - Dynamic FY: Select 2026-27 → Budget reflects projects in that FY
3. Verify Coordinator, Provincial, General dashboards still use static FY; no errors
4. Verify report list pages (reportList, pendingReports, approvedReports) still work; no changes to their scope
5. Verify Project Budgets Overview form preserves fy/scope when applying project type filter
6. **Duplicate aggregation test:** Create or identify a project where `user_id = executor_id` AND `in_charge = executor_id`. With scope = owned_and_in_charge, verify that:
   - Total Budget is not double-counted
   - Quick Stats (total_budget, total_expenses) are correct
   - Budget Analytics charts show correct values
   - Project Budgets Overview summary does not inflate
   - The project appears only once in the underlying dataset

**Risks:** Low if phases 1–5 completed correctly.

**Validation checklist:**
- [ ] All executor dashboard tests pass
- [ ] User 37 scenario validates scope and FY behaviour
- [ ] Other dashboards unaffected
- [ ] Report pages unaffected
- [ ] Form preservation verified
- [ ] No double-count when project has user_id = in_charge = executor_id

---

## 7. Phase Execution Tracking Table

| Phase | Objective | Status | Notes |
|-------|-----------|--------|-------|
| 1 | Scope architecture (ProjectQueryService) | Pending | Add getApprovedInChargeProjectsForUser, getApprovedProjectsForUserInFinancialYear |
| 2 | Dynamic FY (FinancialYearHelper) | Pending | Add listAvailableFYFromProjects with fallback |
| 2.5 | Widget FY consistency | Pending | Pass $fy into getChartData, getQuickStats, getActionItems, getUpcomingDeadlines |
| 2.6 | Financial resolver optimization | Pending | resolveCollection(); reuse pre-resolved data across widgets |
| 3 | Scope selector backend | Pending | Read scope; use scope-aware dataset for budget/charts/stats |
| 4 | Scope selector UI | Pending | Add scope dropdown; preserve fy/scope in forms |
| 5 | Widget scope integration | Pending | All widgets use scope and FY |
| 6 | Validation & regression | Pending | Tests and manual verification |

---

## 8. Testing Strategy

- **Unit tests:** ProjectQueryService new methods (with/without FY; each scope); FinancialYearHelper listAvailableFYFromProjects (empty, single FY, multiple FYs)
- **Feature tests:** Executor dashboard request with scope/fy params; verify response contains correct budget totals
- **Integration:** User 37 (or fixture) with owned + in-charge projects; assert totals per scope
- **Regression:** Coordinator, Provincial, General dashboard smoke tests; ensure no change to their FY source
- **Manual:** Form submission from Project Budgets Overview; verify fy/scope preserved

---

## 9. Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Default behaviour changes | Default scope=owned, fy=currentFY(); no change unless user selects otherwise |
| Wrong totals for combined scope | Use getApprovedProjectsForUserInFinancialYear (owner OR in_charge); no duplicate projects per definition |
| Empty FY list | Fallback to listAvailableFY() or include currentFY() when derived list is empty |
| Other dashboards broken | Do not modify listAvailableFY(); Executor-only use of listAvailableFYFromProjects |
| Form drops fy/scope | Add hidden inputs in Project Budgets Overview and any other forms that submit to dashboard |
| N+1 queries | Reuse existing eager loading ($with) in new ProjectQueryService methods |
| Duplicate aggregation when combining owned and in-charge datasets | Ensure unique project IDs through OR-based query and `distinct('projects.id')`; do not merge owned and in-charge collections |

---

## 10. Future Improvements

- **Financial resolution caching layer:** Cache `resolveCollection()` output per (user_id, scope, fy) with short TTL to avoid repeated resolution across requests for the same dashboard view.
- **Optional executor dashboard caching:** If executor dashboard is cached later, use cache key including `user_id`, `scope`, `fy` (e.g. `executor_dashboard_{$userId}_{$scope}_{$fy}`).
- **Index recommendation for commencement_month_year:** Add index on `projects.commencement_month_year` if the distinct query for dynamic FY derivation becomes slow at scale.
- **Report list FY:** Optionally add FY filter to report list pages for consistency with dashboard.
- **ownedCount / inChargeCount:** Optionally scope by FY if product wants section badges to match FY filter.

---

**Document created for planning only. No application code has been modified.**
