# Executor Dashboard Integrity Stabilization Plan

**Phase-wise implementation plan to stabilize and validate statistical integrity**  
**Based on:** `Dashboard_Statistical_Integrity_Audit.md`  
**Date:** 2025-02-19  
**Status:** Planning (READ ONLY — no code changes)

---

## Scope & Constraints

### What This Plan Fixes

1. **Status completeness drift** — Report status summary/chart missing 8+ statuses  
2. **Pagination-based statistical distortion** — Health summary, project status/type charts use paginated items  
3. **Scope inconsistencies** — Report Overview widget uses merged scope vs owned  
4. **Widget data contract drift** — Report Overview approved count incomplete  
5. **Chart integrity inconsistencies** — Status distribution chart incomplete; project charts paginated  
6. **Minor financial/statistical edge cases** — Negative totals, duplicate report_month_year

### What This Plan Does NOT Change

- Financial layer (ProjectFinancialResolver, DerivedCalculationService)  
- Approval flow or status transitions  
- Authorization or middleware  
- Dashboard layout or widget structure

---

## Do Not Touch

| Component | Location | Reason |
|-----------|----------|--------|
| ProjectFinancialResolver | `app/Domain/Budget/ProjectFinancialResolver.php` | Financial logic validated; no redesign |
| DerivedCalculationService | `app/Services/Budget/DerivedCalculationService.php` | Math validated |
| ReportStatusService | Approval flow | Out of scope |
| ProjectQueryService base scope | `getOwnedProjectsQuery`, `getInChargeProjectsQuery`, `getOwnedProjectIds` | Authorization boundary |
| Authorization / middleware | Routes, policies | Out of scope |

---

## Statistical Reconciliation Verification

### Definitions

| Term | Definition | Source |
|------|------------|--------|
| **total_budget** | Sum of `ProjectFinancialResolver::resolve()['opening_balance']` for approved owned projects | `projects.opening_balance` + strategy |
| **approved_expenses** | Sum of `DP_AccountDetails.total_expenses` for reports in `DPReport::APPROVED_STATUSES` | `DP_AccountDetails.total_expenses` |
| **unapproved_expenses** | Sum of `DP_AccountDetails.total_expenses` for reports NOT in `DPReport::APPROVED_STATUSES` | Same table |
| **total_expenses** | `approved_expenses + unapproved_expenses` | Derived |
| **remaining** | `total_budget - approved_expenses` (per project, summed) | Derived |

### Formulas

```
remaining = total_budget - approved_expenses
total_expenses = approved_expenses + unapproved_expenses
utilization = (approved_expenses / total_budget) * 100   when total_budget > 0 else 0
SUM(by_project_type.total_budget) = total.total_budget
SUM(by_project_type.approved_expenses) = total.approved_expenses
SUM(by_project_type.total_remaining) = total.total_remaining
```

### Reconciliation Equations (Must Hold After Each Phase)

1. `budgetSummaries['total']['total_remaining'] === budgetSummaries['total']['total_budget'] - budgetSummaries['total']['approved_expenses']`  
2. `budgetSummaries['total']['total_expenses'] === budgetSummaries['total']['approved_expenses'] + budgetSummaries['total']['unapproved_expenses']`  
3. `array_sum(array_column($by_project_type, 'total_budget')) === $total['total_budget']`  
4. `quickStats['total_expenses'] === chartData['total_expenses']` (when both exist)  
5. `projectHealthSummary['total'] === count(all owned projects)` (after Phase 2)

---

## Global Risk Matrix

| Phase | Risk Area | Severity | Mitigation |
|-------|-----------|----------|------------|
| 1 | Status constant mismatch | Medium | Use DPReport constants only; unit test status set |
| 2 | New query performance | Medium | Index project_id; limit to owned; verify N+1 absent |
| 3 | Report Overview scope change | Low | Align with existing KPI; document UX change |
| 4 | Chart data contract break | Medium | Keep view variable names; add new controller vars |
| 5 | Math regression | Low | Automated reconciliation assertions |
| 6 | End-to-end regression | Medium | Full test matrix before freeze |

---

# Phase 1 — Status Domain Integrity

## Objective

Ensure all DPReport and Project statuses used in dashboard statistics are fully enumerated and counted. Report Status Summary and Report Status Distribution chart must reflect complete status set.

## Why This Phase Exists

**Audit finding:** `getReportStatusSummary` and `getReportChartData` initialize only 6 statuses. Reports with `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`, `reverted_by_general_as_*`, `reverted_to_*`, `rejected_by_coordinator` are uncounted. Total and distribution undercount.

**Reference:** Audit §3 Report Domain Validation; §10 Critical Risk #1.

## Code Areas Impacted

- `app/Http/Controllers/ExecutorController.php` — `getReportStatusSummary()`, `getReportChartData()`
- `resources/views/executor/widgets/report-status-summary.blade.php` — Approved/Reverted display
- `resources/views/executor/widgets/report-overview.blade.php` — Approved count display
- `resources/views/executor/widgets/report-analytics.blade.php` — Status chart (uses controller data)

## Implementation Steps (Ordered)

1. **Define canonical status set** — Create a single source (e.g. `DPReport::getDashboardStatusKeys()` or equivalent) returning all status keys to initialize for summary/chart. Include: `draft`, `submitted_to_provincial`, `forwarded_to_coordinator`, `approved_by_coordinator`, `approved_by_general_as_coordinator`, `approved_by_general_as_provincial`, `reverted_by_provincial`, `reverted_by_coordinator`, `reverted_by_general_as_provincial`, `reverted_by_general_as_coordinator`, `reverted_to_executor`, `reverted_to_applicant`, `reverted_to_provincial`, `reverted_to_coordinator`, `rejected_by_coordinator` (if applicable).

2. **Update getReportStatusSummary** — Replace hardcoded 6-status array with full set from canonical source. Iterate `$monthlyReports` and assign counts only for keys in canonical set. Compute `total` as `array_sum($statuses)`.

3. **Update getReportChartData** — Replace hardcoded 6-status array in `status_distribution` with same canonical set. Ensure `statusCounts` includes all keys before merging DB groupBy results.

4. **Update report-status-summary blade** — Display "Approved" as sum of all three approved statuses: `STATUS_APPROVED_BY_COORDINATOR` + `STATUS_APPROVED_BY_GENERAL_AS_COORDINATOR` + `STATUS_APPROVED_BY_GENERAL_AS_PROVINCIAL`. Display "Reverted" as sum of all reverted status keys. Ensure "Total" matches controller-passed `reportStatusSummary['total']`.

5. **Update report-overview blade** — Replace single `STATUS_APPROVED_BY_COORDINATOR` with sum of `DPReport::APPROVED_STATUSES`. Replace "Pending" sum to include all non-approved, non-final statuses per audit semantics (or use controller-passed value).

6. **Verification** — Add/run test: create reports with each status; assert status summary total equals actual report count for owned projects.

## Validation Checklist

- [ ] `getReportStatusSummary` returns array with keys for all 14+ statuses  
- [ ] `reportStatusSummary['total']` equals `DPReport::whereIn('project_id', ownedIds)->count()`  
- [ ] Report status distribution chart shows non-zero for any status that exists in DB  
- [ ] Report Overview "Approved" = sum of APPROVED_STATUSES  
- [ ] Report Overview "Total" aligns with reportStatusSummary total  
- [ ] No PHP notices for undefined array keys

## Regression Checklist

- [ ] Approval rate (approved/total) still correct  
- [ ] Action Items pending reports unchanged  
- [ ] Quick Stats total/approved reports unchanged  
- [ ] Report list pages unaffected

## Rollback Strategy

- Revert controller changes to restore 6-status arrays  
- Revert view changes to restore single approved status display  
- No DB migrations; no data loss

## Completion Gate

- All validation checks pass  
- Reconciliation: `reportStatusSummary['total']` === count of DPReport for owned project IDs  
- No new linter errors

---

# Phase 2 — Pagination Scope Separation

## Objective

Separate KPI/chart data from paginated list data. Health summary, project status chart, and project type chart must use **full filtered owned** project set, not `$ownedProjects->items()` (paginated subset).

### Scope Philosophy

KPIs reflect current filter context (show, search, project_type, status) but are **not paginated**. Pagination affects table display only.

## Why This Phase Exists

**Audit finding:** `getProjectHealthSummary($enhancedOwnedProjects)` receives `$ownedProjects->items()` (current page). Project status/type charts use `$ownedProjects->items()`. When user has >15 projects, health and charts reflect only current page.

**Reference:** Audit §5 Health Logic Validation; §6 Chart Consistency; §10 Critical Risks #2, #3.

## Code Areas Impacted

- `app/Http/Controllers/ExecutorController.php` — `executorDashboard()`: ownedBaseQuery; clone for pagination vs full KPI; pass full-scope data to views
- `resources/views/executor/widgets/project-health.blade.php` — Uses `$enhancedFullOwnedProjects` for Health Breakdown
- `resources/views/executor/widgets/project-status-visualization.blade.php` — Uses `$projectChartData` from full filtered scope

## Implementation Steps (Ordered)

1. **Build filtered owned base query** — `$ownedBaseQuery = ProjectQueryService::getOwnedProjectsQuery($user)`. Apply all filters: show, search, project_type, status. Do not paginate.

2. **Clone for pagination** — `$ownedPaginatedQuery = clone $ownedBaseQuery`. Apply eager load, orderBy, paginate. Used for table display.

3. **Clone for KPI** — `$ownedFullQuery = clone $ownedBaseQuery`. Apply same eager load, orderBy, `get()`. Used for health summary, status chart, type chart.

4. **Compute enhanced metadata** — `$enhancedFullOwnedProjects = enhanceProjectsWithMetadata($ownedFullProjects->all())`. Pass to `getProjectHealthSummary()` and Health Breakdown in blade.

5. **Build project chart data** — `buildProjectChartData($ownedFullProjects)` returns `status_distribution`, `type_distribution`, `total`. Pass as `$projectChartData` to project-status-visualization.

6. **Preserve pagination for table** — `$enhancedOwnedProjects` remains per-page for table rows. Health summary and charts use full filtered scope only.

7. **Performance check** — Same eager loading for both clones; no N+1.

## Validation Checklist

- [ ] User with 50 owned projects (filtered): health summary good+warning+critical = 50  
- [ ] User with 50 owned projects (filtered): project status chart total = 50  
- [ ] User with 50 owned projects (filtered): project type chart total = 50  
- [ ] Changing list page (pagination) does not change health summary or charts  
- [ ] Filters (show, search, project_type) affect both table and KPI widgets  
- [ ] List table still shows per-row health from `$enhancedOwnedProjects` (paginated)

## Regression Checklist

- [ ] Paginated list still works  
- [ ] Filters (show, search, project_type) still apply to list  
- [ ] Budget summary, Quick Stats unchanged  
- [ ] No duplicate queries for same data (clone once, get/paginate separately)

## Rollback Strategy

- Revert controller to pass `$ownedProjects->items()` to health summary and chart  
- Revert blade to use `$ownedProjects->items()`  
- No schema changes

## Completion Gate

- Health summary total === count(filtered owned projects)  
- Project status chart total === count(filtered owned projects)  
- Project type chart total === count(filtered owned projects)  
- Pagination unaffected  
- All reconciliation equations hold

---

# Phase 3 — Scope Consistency Harmonization

## Objective

Ensure **all dashboard statistical widgets** use owned-only scope. Align Report Overview widget with owned-only scope. Remove inline merged-scope logic from dashboard.

**Scope rule:** All dashboard statistical widgets use owned-only scope. Merged scope is restricted strictly to authorization layer (e.g. report list page access).

## Why This Phase Exists

**Audit finding:** Report Overview used `Project::where(user_id OR in_charge)` (merged scope) in inline blade query. Recent reports could include in-charge projects. Project types filter used merged scope.

**Reference:** Audit §10 Critical Risks #4, #5; Report Overview widget.

## Code Areas Impacted

- `app/Http/Controllers/ExecutorController.php` — Add `$recentReports` (owned scope); change `projectTypes` to owned-only; document activity feed scope
- `resources/views/executor/widgets/report-overview.blade.php` — Remove inline query; use controller-passed `$recentReports`

## Implementation Steps (Ordered)

1. **Add controller data for Report Overview** — Fetch recent reports using `ProjectQueryService::getOwnedProjectIds($user)`. Pass as `$recentReports` to view.

2. **Remove inline query from report-overview blade** — Delete `@php` block that queries `Project::where(user_id|in_charge)`. Use `@foreach($recentReports ?? [] as $report)`.

3. **projectTypes to owned-only** — Change `getProjectsForUserQuery` to `getOwnedProjectsQuery` for dashboard filter dropdown.

4. **Document activity feed** — Activity feed (`getRecentActivities` → `ActivityHistoryService::getForExecutor`) intentionally uses combined scope for visibility. Add controller comment.

5. **Reconciliation** — Report Overview Total = reportStatusSummary['total']; Approved = reportStatusSummary['approved_count']; Pending = reportStatusSummary['pending_count'].

## Validation Checklist

- [ ] Report Overview "Recent Reports" shows only reports from owned projects  
- [ ] Report Overview "Total" = reportStatusSummary['total']  
- [ ] Report Overview "Approved" = sum of APPROVED_STATUSES counts  
- [ ] Report Overview "Pending" = reportStatusSummary['pending_count']  
- [ ] User with in-charge only: Report Overview shows 0 recent reports (or empty)  
- [ ] No merged scope queries inside dashboard controller methods  
- [ ] Approved + Pending ≤ Total (pending = need-attention subset; total includes all statuses)

## Regression Checklist

- [ ] Report list page unchanged (authorization remains merged)  
- [ ] Report Status Summary widget unchanged (same source)  
- [ ] No new queries in view (all from controller)

## Rollback Strategy

- Restore inline query in report-overview blade  
- Remove `$recentReports` from controller  
- Revert projectTypes to getProjectsForUserQuery  
- No DB impact

## Completion Gate

- No merged scope queries inside dashboard controller  
- Report Overview totals reconcile with reportStatusSummary  
- Approved + Pending consistent with reportStatusSummary  
- Activity feed documented if merged

---

# Phase 4 — Chart & Widget Contract Alignment

## Objective

Ensure all charts and widgets receive data from correct scopes and that variable contracts are stable. Address budget filter inconsistency and monthly expense duplicate risk.

## Why This Phase Exists

**Audit finding:** Budget Overview project_type filter does not filter budget summary. Monthly expenses chart could double-count if duplicate report_month_year exists. Chart variable naming should be explicit.

**Reference:** Audit §2 Budget Domain; §6 Chart Consistency; §10 Medium Risks #7, #8.

## Code Areas Impacted

- `app/Http/Controllers/ExecutorController.php` — `getChartData()`, `calculateBudgetSummariesFromProjects()` (if filter applied)
- `resources/views/executor/widgets/project-budgets-overview.blade.php` — Filter behavior
- `resources/views/executor/widgets/budget-analytics.blade.php` — Uses `$chartData`
- `resources/views/executor/index.blade.php` — Widget inclusion conditions

## Implementation Steps (Ordered)

1. **Budget filter alignment** — Decide: (A) Budget summary respects project_type filter when provided, or (B) Budget summary remains unfiltered and add UI note. Recommendation: (A) for consistency. In `calculateBudgetSummariesFromProjects`, when `$request->filled('project_type')`, filter `$projects` by `project_type` before aggregation. Ensure `getApprovedOwnedProjectsForUser` result is filtered server-side.

2. **Monthly expenses deduplication** — In `getChartData` monthly_expenses loop, if duplicate (project_id, report_month_year) is possible, aggregate by (project_id, report_month_year) first, then sum expenses per month. Or document: "One report per project per month assumed; duplicates would double-count." Add optional deduplication: group by report_id (each report once) — already true since we iterate reports. If multiple reports per project per month exist, both contribute; document as known edge case or add DB uniqueness check.

3. **Chart data contract documentation** — Add docblock to `getChartData` return: keys expected by budget-analytics blade. Ensure `chartData` is always set (empty array when no owned projects) to avoid undefined variable.

4. **Widget visibility consistency** — Ensure `project-status-visualization` and `project-health` receive data; when owned count = 0, charts show empty state. No PHP errors when `$chartData` or `$projectChartData` is empty.

## Validation Checklist

- [ ] With project_type filter: budget summary by_type matches filtered subset  
- [ ] With project_type filter: SUM(by_type) = total for filtered set  
- [ ] Monthly expenses: no duplicate project+month in same aggregation (verify logic)  
- [ ] Budget analytics shows "No data" when chartData empty  
- [ ] All chart containers handle empty data without JS errors

## Regression Checklist

- [ ] Budget summary without filter unchanged  
- [ ] Charts render when data present  
- [ ] project_type filter on list still works

## Rollback Strategy

- Revert filter application to budget summary  
- Revert any monthly expense aggregation change  
- Restore previous chart data structure if changed

## Completion Gate

- Budget filter (if implemented) works end-to-end  
- Chart contracts documented  
- Empty states handled

---

# Phase 5 — Mathematical Reconciliation Hardening

## Objective

Add safeguards for negative totals, rounding consistency, and division-by-zero. Ensure reconciliation equations hold in production-like scenarios.

## Why This Phase Exists

**Audit finding:** No clamp for negative `total_expenses`. Rounding varies (1 vs 2 decimals). Edge case: negative accountDetails would skew totals.

**Reference:** Audit §2 Negative accountDetails; §9 Edge Cases; §10 Medium Risk #9.

## Code Areas Impacted

- `app/Http/Controllers/ExecutorController.php` — Expense aggregation (optional clamp)
- `app/Services/Budget/DerivedCalculationService.php` — **READ ONLY** — Do not modify; document that it expects non-negative
- `resources/views/executor/widgets/project-budgets-overview.blade.php` — Percentage calculation
- Optional: New `DashboardReconciliationTest` or similar

## Implementation Steps (Ordered)

1. **Document expected invariants** — Add comment block in ExecutorController above `calculateBudgetSummariesFromProjects`: "Invariants: remaining = budget - approved; total_expenses = approved + unapproved. DB must not have negative total_expenses."

2. **Optional: Clamp at aggregation** — When summing `accountDetails->sum('total_expenses')`, consider `max(0, sum(...))` only if business accepts clamping. Document decision. Recommendation: Do NOT clamp in controller; add DB constraint or validation at report edit if negatives are invalid. This phase = documentation only unless product approves clamp.

3. **Rounding consistency** — Standardize: utilization to 1 decimal, currency to 2. Audit current usage; document in view helpers if needed. No code change if current behavior acceptable per audit.

4. **Reconciliation test** — Add automated test (Feature or Unit): given fixture with known projects/reports, assert `total_remaining === total_budget - approved_expenses`, `total_expenses === approved + unapproved`, `SUM(by_type) === total`.

5. **Division-by-zero audit** — Verify all utilization/rate calculations use `budget > 0` check. Audit already confirmed DerivedCalculationService handles this. Spot-check view-side calculations.

## Validation Checklist

- [ ] Reconciliation test passes  
- [ ] No division by zero in any widget  
- [ ] Documented invariants match implementation

## Regression Checklist

- [ ] Budget display unchanged  
- [ ] Utilization display unchanged  
- [ ] DerivedCalculationService untouched

## Rollback Strategy

- Remove reconciliation test if it blocks CI  
- Revert clamp (if added)  
- Documentation changes are non-functional

## Completion Gate

- Reconciliation test green  
- Invariants documented  
- No new runtime errors

---

# Phase 6 — Final System Verification & Freeze

## Objective

Run full test matrix, verify dashboard trust certification, and freeze implementation with sign-off checklist.

## Why This Phase Exists

End-to-end verification before declaring dashboard statistically defensible. Ensure no regressions across user/portfolio scenarios.

## Code Areas Impacted

- All dashboard-related files (verification only)
- Test suite
- Documentation

## Implementation Steps (Ordered)

1. **Run test matrix** — Execute all scenarios in Test Matrix (see below).

2. **Statistical reconciliation** — For each scenario, verify: `remaining = budget - approved`, `total_expenses = approved + unapproved`, `health total = owned count`, `status chart total = owned count`, `report status total = report count`.

3. **Performance check** — Ensure no N+1; page load acceptable with 50+ owned projects.

4. **Dashboard Trust Certification** — Complete checklist (see section below).

5. **Documentation update** — Update Dashboard_Statistical_Integrity_Audit.md status: "Stabilization complete per Dashboard_Integrity_Stabilization_Plan.md Phase 6."

6. **Freeze** — Tag release; no further dashboard stat changes without new audit.

## Validation Checklist

- [ ] All Test Matrix scenarios pass  
- [ ] All reconciliation equations hold  
- [ ] Trust Certification checklist complete  
- [ ] No critical linter/static analysis issues

## Regression Checklist

- [ ] Provincial, Coordinator, Admin dashboards unaffected  
- [ ] Report submission/approval flows unchanged  
- [ ] Project CRUD unchanged

## Rollback Strategy

- Full revert of Phases 1–5 if systemic failure  
- Per-phase rollback as documented

## Completion Gate

- Sign-off from product/tech lead  
- Audit document updated  
- Release tagged

---

# Test Matrix

| Scenario | User Setup | Expected Behavior |
|----------|------------|-------------------|
| **Owned only** | User has owned projects, no in-charge | All KPIs from owned; Report Overview = owned reports; health/charts = owned |
| **Owned + in-charge** | User has both | KPIs and Report Overview = owned only; In-charge list separate; no in-charge in Report Overview |
| **In-charge only** | User has in-charge projects only | KPIs (budget, reports, health) = 0 or empty; Report Overview = 0; In-charge list shows projects |
| **No projects** | User has no owned, no in-charge | All zeros; empty charts; no errors |
| **Draft only** | All owned projects draft | Budget = 0; expenses = 0; health from draft; status chart shows draft |
| **Approved, no reports** | Owned projects approved, no reports | Budget shown; expenses = 0; utilization = 0; health factors = no reports |
| **Approved, pending reports** | Approved projects with draft/submitted reports | Unapproved expenses > 0; approved = 0 until reports approved; action items show pending |
| **Mixed statuses** | Owned projects: draft, approved, reverted | All status buckets non-zero where applicable; totals reconcile; status chart complete |

### Verification Per Scenario

- [ ] Budget summary totals correct  
- [ ] Report status summary total = report count  
- [ ] Health summary total = owned project count  
- [ ] Project status chart total = owned project count  
- [ ] Project type chart total = owned project count  
- [ ] Report Overview scope = owned  
- [ ] No PHP/JS errors

---

# Dashboard Trust Certification

**Post Phase 6 — Sign-off**

| Metric Area | Trustworthy? | Condition |
|-------------|--------------|-----------|
| Budget summary (total, approved, unapproved, remaining) | ✓ | After Phases 1–5 |
| Quick stats (projects, reports, approval rate) | ✓ | Unchanged |
| Report status summary | ✓ | After Phase 1 (full status set) |
| Report status distribution chart | ✓ | After Phase 1 |
| Project health summary | ✓ | After Phase 2 (full scope) |
| Project status/type charts | ✓ | After Phase 2 (full scope) |
| Report Overview | ✓ | After Phase 3 (owned scope) |
| Action items, deadlines, attention widgets | ✓ | Unchanged |
| Chart ↔ widget reconciliation | ✓ | After Phase 4 |

### Certification Checklist

- [ ] All phases 1–6 complete  
- [ ] Test matrix passed  
- [ ] Reconciliation equations hold  
- [ ] No known integrity gaps per audit  
- [ ] Documentation updated

**Certification:** Dashboard statistics are **statistically defensible** for owned-scope KPIs after Phase 6 completion.

---

*End of Plan*
