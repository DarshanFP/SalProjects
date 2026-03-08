# Phase 3 Financial Year Dashboard Integration Audit

**Task:** Phase 3 Financial Year Dashboard Integration Audit  
**Date:** 2026-03-04  
**Mode:** Audit

---

## Controllers Modified

| Controller | Method(s) | Changes |
|------------|-----------|--------|
| **ExecutorController** | `executorDashboard()` | Added `FinancialYearHelper`, `$fy` from request (default current FY), `inFinancialYear($fy)` on owned/in-charge queries and project types; `getApprovedOwnedProjectsForUser(..., $fy)`; pass `fy`, `availableFY` to view. |
| **CoordinatorController** | `coordinatorDashboard()`, `getSystemBudgetOverviewData()` | Added `FinancialYearHelper`, `$fy` from request; `Project::approved()->inFinancialYear($fy)` and `Project::inFinancialYear($fy)` for stats; budget overview cache key and approved/pending queries include `inFinancialYear($fy)`; pass `fy`, `availableFY` to view. |
| **ProvincialController** | `provincialDashboard()`, `calculateTeamPerformanceMetrics()`, `prepareChartDataForTeamPerformance()`, `calculateCenterPerformance()`, `calculateEnhancedBudgetData()` | Added `FinancialYearHelper`, `$fy` from request; main projects query and society stats query use `inFinancialYear($fy)`; widget methods accept `$fy` and apply `inFinancialYear($fy)` to project queries; pass `fy`, `availableFY` to view. |
| **GeneralController** | `generalDashboard()`, `getBudgetOverviewData()`, `getSystemPerformanceData()` | Added `FinancialYearHelper`, `$fy` from request; coordinator/direct team project queries use `inFinancialYear($fy)`; `getBudgetOverviewData` and `getSystemPerformanceData` use `$fy` (from request) in cache key and project queries; pass `fy`, `availableFY` to view. |

---

## FY Filtering Verification

- **Executor:** `ProjectQueryService::getOwnedProjectsQuery()` / `getInChargeProjectsQuery()` cloned and filtered; `inFinancialYear($fy)` applied after status filter. Budget summary uses `getApprovedOwnedProjectsForUser($user, $with, $fy)`.
- **Coordinator:** `Project::approved()->inFinancialYear($fy)` and `Project::inFinancialYear($fy)` for main lists and statistics; `getSystemBudgetOverviewData()` applies `inFinancialYear($fy)` to approved and pending project queries; cache key includes `fy`.
- **Provincial:** `Project::accessibleByUserIds($ids)->approved()->inFinancialYear($fy)` for main projects; society breakdown and all widget methods (team performance, center performance, enhanced budget) use `inFinancialYear($fy)`.
- **General:** `projectsFromCoordinatorsQuery` and `projectsFromDirectTeamQuery` get `inFinancialYear($fy)` before `get()`; `getBudgetOverviewData` and `getSystemPerformanceData` apply `inFinancialYear($fy)` to project ID queries and use `$fy` in cache key.

---

## Default FY Behaviour

- All dashboards use `$fy = $request->input('fy', FinancialYearHelper::currentFY());` so the default is the **current financial year** when no `fy` is supplied.
- Dropdowns are populated with `FinancialYearHelper::listAvailableFY()` (newest first). Default selection is current FY.

---

## Dashboard Query Validation

- **Order of operations** is respected: role/access scope first, then status (e.g. approved), then `inFinancialYear($fy)`.
- No existing role or access logic was removed or reordered; FY scope was inserted after role filters as specified.

---

## Resolver Integrity Check

- **ProjectFinancialResolver** was not modified.
- **BudgetValidationService**, **ExportController**, **ReportMonitoringService** were not modified.
- Aggregation pipeline remains: project collection → ProjectFinancialResolver → aggregation. FY filtering only narrows the project set before resolution; resolver usage is unchanged.

---

## Role Scope Validation

- **Executor:** Owned and in-charge queries remain scoped by `ProjectQueryService` (user/role); FY applied after.
- **Coordinator:** No additional role scope in main dashboard (system-wide); FY applied after approved/notApproved.
- **Provincial:** `accessibleByUserIds($accessibleUserIds)` applied first; then `approved()`; then `inFinancialYear($fy)`.
- **General:** Coordinator hierarchy and direct team queries (user IDs) applied first; then filters; then `inFinancialYear($fy)`.

---

## Performance Impact

- FY filter adds `WHERE commencement_month_year BETWEEN ? AND ?` (and `whereNotNull('commencement_month_year')` in scope). Combined with existing indexes and filters, impact is expected to be low.
- Coordinator and General widget caches now keyed by `fy` (and existing filter hash where applicable), so cache entries per FY; no increase in query volume per request.

---

## Regression Risk

- **Low.** Changes are additive: new request parameter `fy`, new scope on existing queries, new dropdown in views. Default behaviour (current FY) preserves “current year” view.
- **Resolver and aggregation logic unchanged;** only the set of projects passed to the resolver is restricted by FY.
- **Role scope unchanged;** FY is applied after access control in all controllers.

---

## Final Verdict

**SAFE FOR PRODUCTION**

- Phase 3 FY integration is confined to dashboard controllers and views as specified.
- Financial year filtering is applied consistently after role/status filters; default FY is current; dropdowns and cache keys include FY.
- Resolver, BudgetValidationService, ExportController, and ReportMonitoringService are untouched; aggregation pipeline is preserved.
