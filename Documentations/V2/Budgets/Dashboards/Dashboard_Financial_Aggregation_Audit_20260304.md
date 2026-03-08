# Dashboard Financial Aggregation Audit

**Task:** Dashboard Financial Aggregation Audit (Preparation for FY Dashboard Phase 3)  
**Date:** 2026-03-04  
**Mode:** Audit (Read-only — no code or database modifications)

---

## PART 1 — DASHBOARD CONTROLLERS

| Controller | Method | Purpose |
|------------|--------|---------|
| ExecutorController | `executorDashboard()` | Executor-owned and in-charge project list, KPIs, budget summaries |
| CoordinatorController | `coordinatorDashboard()` | System-wide oversight; budget summaries; system performance/analytics |
| CoordinatorController | `getSystemBudgetOverviewData()` | Budget overview widget (approved/pending totals; by type, province, center, provincial) |
| CoordinatorController | `getSystemPerformanceData()` | System performance metrics; province-wise breakdown |
| CoordinatorController | `getSystemAnalyticsData()` | Budget utilization timeline; expense trends; province comparison |
| CoordinatorController | `getProvinceComparisonData()` | Province performance comparison; budget/expenses/utilization |
| ProvincialController | `provincialDashboard()` | Provincial jurisdiction dashboard; society stats; budget summaries |
| ProvincialController | `calculateTeamPerformanceMetrics()` | Team performance widget; total budget/expenses/utilization |
| ProvincialController | `prepareChartDataForTeamPerformance()` | Chart data by project type and center |
| ProvincialController | `calculateCenterPerformance()` | Center-wise performance; budget/pending/expenses |
| ProvincialController | `calculateEnhancedBudgetData()` | Enhanced budget widget for provincial scope |
| GeneralController | `generalDashboard()` | Combined coordinator hierarchy + direct team dashboard |
| GeneralController | `listBudgets()` | Budget list with summary; `calculated_budget`, `calculated_expenses`, etc. |
| GeneralController | (Finance/analytics methods ~3585+) | Coordinator hierarchy vs direct team budget breakdown; charts |
| AdminReadOnlyController | `projectIndex()` | Admin project list; per-project budget/expenses from resolver |

---

## PART 2 — FINANCIAL AGGREGATION QUERIES

| File | Query / Logic | Field Aggregated | Service Used |
|------|---------------|------------------|--------------|
| ExecutorController | `$resolver->resolve($project)`; `$projectBudget = (float) ($financials['opening_balance'] ?? 0)` | opening_balance | ProjectFinancialResolver |
| ExecutorController | `$report->accountDetails->sum('total_expenses')` | total_expenses | — (raw collection sum) |
| CoordinatorController | `$approvedProjects->sum(fn($p) => ... $resolvedFinancials[$p->project_id]['opening_balance'])` | opening_balance | ProjectFinancialResolver |
| CoordinatorController | `$approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0))` | opening_balance | Raw DB (getSystemBudgetOverviewData, by type/province/center/provincial) |
| CoordinatorController | `$pendingProjects->sum(... $financialResolver->resolve($p)['amount_requested'])` | amount_requested | ProjectFinancialResolver |
| CoordinatorController | `DPAccountDetail::whereIn(...)->sum('total_expenses')` | total_expenses | Raw DB |
| ProvincialController | `Project::where(...)->selectRaw('society_id, SUM(COALESCE(amount_sanctioned, 0))...')->groupBy('society_id')` | amount_sanctioned | Raw DB (society stats) |
| ProvincialController | `Project::where(...)->selectRaw('...SUM(GREATEST(0, overall_project_budget - amount_forwarded - local_contribution))...')` | overall_project_budget | Raw DB (society stats, pending) |
| ProvincialController | `DPReport::...->join('DP_AccountDetails')->selectRaw('...SUM(COALESCE(DP_AccountDetails.total_expenses, 0))...')` | total_expenses | Raw DB (society reported totals) |
| ProvincialController | `$resolver->resolve($project)`; sum of `opening_balance` per project | opening_balance | ProjectFinancialResolver |
| ProvincialController | `$approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0))` | opening_balance | Raw DB (calculateCenterPerformance) |
| ProvincialController | `$report->accountDetails->sum('total_expenses')` | total_expenses | Raw collection |
| GeneralController | `$projects->sum(fn($p) => (float) ($resolvedFinancials[$p->project_id]['opening_balance'] ?? 0))` | opening_balance | ProjectFinancialResolver |
| GeneralController | `$allProjects->sum('calculated_budget')`, `sum('calculated_expenses')` | calculated_* | Resolver-derived (per-project) |
| GeneralController | `DPAccountDetail::whereIn(...)->sum('total_expenses')` | total_expenses | Raw DB |
| AdminReadOnlyController | `$resolver->resolve($project)`; `$project->calculated_budget` | opening_balance | ProjectFinancialResolver |
| AdminReadOnlyController | `DPAccountDetail::whereIn(...)->sum('total_expenses')` | total_expenses | Raw DB |
| BudgetExportController | `BudgetValidationService::getBudgetSummary($project)` | opening_balance, total_expenses, etc. | BudgetValidationService → ProjectFinancialResolver |
| BudgetExportController | `$report->accountDetails->sum('total_expenses')` | total_expenses | Raw collection (prepareReportData) |
| ReportMonitoringService | `->filter(...)->sum('amount_sanctioned')` | amount_sanctioned | Report account details |
| ReportMonitoringService | `->sum('total_expenses')`, `->sum('expenses_this_month')` | total_expenses, etc. | Report account details |

---

## PART 3 — RESOLVER USAGE AUDIT

| Dashboard / Component | Resolver Used | Raw Sum Used |
|----------------------|---------------|--------------|
| Executor dashboard (budget summaries) | Yes (opening_balance via resolver per project) | Yes (report accountDetails->sum('total_expenses')) |
| Coordinator dashboard (calculateBudgetSummariesFromProjects) | Yes (opening_balance via resolver) | Yes (report accountDetails->sum('total_expenses')) |
| Coordinator getSystemBudgetOverviewData | Partial (pending: resolver amount_requested) | Yes (approved: raw `$p->opening_balance`; expenses: DPAccountDetail sum) |
| Coordinator getSystemPerformanceData | Yes (resolved opening_balance per project) | Yes (DPAccountDetail sum for expenses) |
| Coordinator getSystemAnalyticsData | Yes (resolved opening_balance per project) | Yes (DPAccountDetail sum) |
| Coordinator getProvinceComparisonData | Yes (resolved opening_balance per project) | Yes (DPAccountDetail sum) |
| Provincial dashboard (calculateBudgetSummariesFromProjects) | Yes (opening_balance via resolver) | Yes (report accountDetails->sum('total_expenses')) |
| Provincial society stats (approvedTotals, pendingTotals, reportedTotals) | No | Yes (amount_sanctioned, overall_project_budget - forwarded - local, total_expenses) |
| Provincial calculateTeamPerformanceMetrics | Yes (opening_balance via resolver) | Yes (report accountDetails->sum('total_expenses')) |
| Provincial prepareChartDataForTeamPerformance | Yes (opening_balance via resolver) | No |
| Provincial calculateCenterPerformance | Partial (pending: resolver amount_requested) | Yes (approved: raw opening_balance; expenses: report accountDetails sum) |
| Provincial calculateEnhancedBudgetData | Yes (opening_balance via resolver) | Yes (report accountDetails sum) |
| General dashboard (listBudgets, finance analytics) | Yes (opening_balance via resolver) | Yes (DPAccountDetail sum) |
| Admin project list | Yes (opening_balance via resolver) | Yes (DPAccountDetail sum) |

**Findings:**
- Several dashboards mix resolver (canonical) and raw DB sums.
- Coordinator `getSystemBudgetOverviewData` uses raw `$p->opening_balance` for approved projects (inconsistency with other coordinator methods that use resolver).
- Provincial society stats use raw DB (amount_sanctioned, overall_project_budget) — not resolver.

---

## PART 4 — AGGREGATION PIPELINE MAPPING

### Executor Dashboard
```
Controller (executorDashboard)
→ ProjectQueryService::getApprovedOwnedProjectsForUser
→ calculateBudgetSummariesFromProjects
   → ProjectFinancialResolver::resolve (per project)
   → opening_balance summed in memory
   → report->accountDetails->sum('total_expenses')
→ Aggregation: in-memory sum of per-project budget + expenses
```

### Coordinator Dashboard (calculateBudgetSummariesFromProjects)
```
Controller (coordinatorDashboard)
→ Project::approved()->with(...)->get() [no role filter — global]
→ calculateBudgetSummariesFromProjects
   → ProjectFinancialResolver::resolve (per project)
   → opening_balance summed in memory
   → report->accountDetails->sum('total_expenses')
→ Aggregation: in-memory sum
```

### Coordinator getSystemBudgetOverviewData
```
Controller
→ Project::approved()->with(...)->get()
→ Project::notApproved()->with(...)->get()
→ Pending: ProjectFinancialResolver::resolve (amount_requested)
→ Approved: raw $p->opening_balance summed (no resolver)
→ DPAccountDetail::whereIn(...)->sum('total_expenses')
→ Aggregation: mix of resolver (pending) and raw DB (approved)
```

### Coordinator getSystemPerformanceData / getSystemAnalyticsData / getProvinceComparisonData
```
Controller
→ Project::with(...)->get() [global]
→ ProjectFinancialResolver::resolve (per project, memoized)
→ sum(resolved['opening_balance'])
→ DPAccountDetail::whereIn(...)->sum('total_expenses')
→ Aggregation: resolver for budget; raw for expenses
```

### Provincial Dashboard
```
Controller (provincialDashboard)
→ ProjectAccessService::getAccessibleUserIds
→ Project::accessibleByUserIds(...)->approved()->get()
→ Society stats: raw Project/DPReport DB sums (amount_sanctioned, overall_project_budget, total_expenses)
→ calculateBudgetSummariesFromProjects
   → ProjectFinancialResolver::resolve (per project)
   → report->accountDetails->sum('total_expenses')
→ Aggregation: resolver for main summaries; raw for society breakdown
```

### General Dashboard (listBudgets, finance analytics)
```
Controller
→ ProjectQueryService::getProjectsForUsersQuery (coordinator hierarchy + direct team)
→ Project::approved()->with(...)->get()
→ ProjectFinancialResolver::resolve (per project, memoized)
→ sum(resolved['opening_balance'])
→ DPAccountDetail::whereIn(...)->sum('total_expenses')
→ Aggregation: resolver for budget; raw for expenses
```

### Admin Project List
```
Controller (projectIndex)
→ Project::with(...)->get() [no role filter — admin sees all]
→ ProjectFinancialResolver::resolve (per project)
→ DPAccountDetail::whereIn(...)->sum('total_expenses')
→ Aggregation: resolver for budget; raw for expenses
```

---

## PART 5 — ROLE FILTER VALIDATION

| Dashboard | Role Scope | Query Source |
|-----------|------------|--------------|
| Executor | Owner or in-charge (province-aware) | ProjectQueryService::getOwnedProjectsQuery, getInChargeProjectsQuery, getApprovedOwnedProjectsForUser |
| Coordinator | Global (all projects) | Project::approved(), Project::notApproved() — no ProjectAccessService; manual province/center/role filters |
| Provincial | accessibleUserIds (provincial scope) | Project::accessibleByUserIds($accessibleUserIds); ProjectAccessService::getAccessibleUserIds |
| General | Coordinator hierarchy + direct team | ProjectQueryService::getProjectsForUsersQuery($allUserIdsUnderCoordinators/directTeamIds, auth()->user()) |
| Admin | All projects | Project::with(...) — no role filter (admin-only route) |
| BudgetExportController generateReport | No role filter | Project::with(...) — filters: project_type, status, start_date, end_date only |

**Findings:**
- Executor: correctly scoped via ProjectQueryService.
- Provincial: correctly scoped via accessibleByUserIds.
- General: correctly scoped via ProjectQueryService (descendant users).
- Coordinator: uses Project::approved() globally; filters applied at query level (province, center, etc.) but no ProjectAccessService.
- BudgetExportController generateReport: no user/role scope — returns all projects matching filters.

---

## PART 6 — FY FILTER INTEGRATION POINT

| Dashboard | Recommended FY Integration Layer |
|-----------|----------------------------------|
| Executor | ProjectQueryService — pass FY to `getApprovedOwnedProjectsForUser($user, $with, $financialYear)` |
| Coordinator | Controller level — add FY filter to Project::approved()/notApproved() via `->inFinancialYear($fy)` or use ProjectAccessService::getVisibleProjectsQuery($user, $fy) for coordinator scope |
| Provincial | ProjectAccessService::getVisibleProjectsQuery($user, $fy) or apply `->inFinancialYear($fy)` in controller after accessibleByUserIds |
| General | ProjectQueryService — extend getProjectsForUsersQuery or add FY param to coordinated calls; or controller-level `->inFinancialYear($fy)` |
| Admin | Controller level — add optional FY filter to Project query |
| BudgetExportController | Controller level — add FY filter (e.g. `->inFinancialYear($request->fy)`) alongside start_date/end_date |

**Note:** ProjectAccessService::getVisibleProjectsQuery already supports optional `$financialYear` (Phase 2). ProjectQueryService::getApprovedOwnedProjectsForUser supports optional `$financialYear` (Phase 2). Phase 3 should propagate FY from request/session into these service calls where applicable.

---

## PART 7 — DUPLICATE AGGREGATION DETECTION

| Dashboard | Aggregation Method | Consistency Risk |
|-----------|--------------------|------------------|
| Executor | Resolver for opening_balance | Low — consistent |
| Coordinator (budget summaries) | Resolver for opening_balance | Low |
| Coordinator (getSystemBudgetOverviewData) | Raw opening_balance for approved | **Medium** — differs from other coordinator methods that use resolver |
| Coordinator (performance/analytics) | Resolver for opening_balance | Low |
| Provincial (main summaries) | Resolver for opening_balance | Low |
| Provincial (society stats) | Raw amount_sanctioned, overall_project_budget | **Medium** — bypasses resolver; may diverge for type-specific projects |
| Provincial (calculateCenterPerformance) | Raw opening_balance for approved | **Medium** — same as coordinator getSystemBudgetOverviewData |
| General | Resolver for opening_balance | Low |
| Admin | Resolver for opening_balance | Low |

**Recommendation:** Align getSystemBudgetOverviewData and calculateCenterPerformance to use resolver for approved projects (like getSystemPerformanceData) to ensure consistency across dashboards.

---

## PART 8 — EXPORT AND REPORT CHECK

| Export | FY Filtering Needed |
|--------|---------------------|
| BudgetExportController exportExcel | No (single project; FY filter not applicable at project level) |
| BudgetExportController exportPdf | No (single project) |
| BudgetExportController generateReport | **Yes** — aggregates across projects; should support optional FY filter (commencement_month_year) |
| ExportController (DOC/PDF) | Uses ProjectFinancialResolver per project; single-project export — FY filtering at report-generation level optional |
| Report views (monthly/quarterly PDF, statements of account) | Report-level sums (accountDetails->sum); FY scope at report selection level — not dashboard aggregation |

---

## PART 9 — PHASE 3 IMPLEMENTATION GUIDANCE

### Executor Dashboard
- Pass FY from request/session into `ProjectQueryService::getApprovedOwnedProjectsForUser($user, $with, $financialYear)`.
- Ensure `calculateBudgetSummariesFromProjects` receives projects already FY-filtered.
- No change to resolver usage — already correct.

### Coordinator Dashboard
- Add FY filter: `Project::approved()->inFinancialYear($fy)` (and similar for notApproved) when FY is selected.
- Unify getSystemBudgetOverviewData: use resolver for approved projects instead of raw `opening_balance`.
- Consider using ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy) if coordinator scope ever needs narrowing; currently coordinator is global.

### Provincial Dashboard
- Apply `->inFinancialYear($fy)` to project queries after `accessibleByUserIds`.
- Migrate society stats from raw amount_sanctioned/overall_project_budget to resolver-derived values per project, then aggregate — or document as intentional raw-DB for performance and accept divergence.

### General Dashboard
- Apply FY filter in project queries (ProjectQueryService or controller-level `->inFinancialYear($fy)`).
- Resolver usage is already consistent.

### BudgetExportController generateReport
- Add optional FY parameter; apply `->inFinancialYear($request->fy)` when provided.
- Ensure role/access scope is applied (currently missing — see Part 5).

**Principles:**
- Apply FY filter through services (ProjectQueryService, ProjectAccessService) where possible, not ad-hoc controller queries.
- Keep resolver as canonical source for project-level financials.
- Replace raw opening_balance sums with resolver where inconsistency exists.

---

## REPORT STRUCTURE SUMMARY

- **Dashboard Controllers:** Executor, Coordinator, Provincial, General, Admin — all identified with methods.
- **Financial Aggregation Queries:** All sum/aggregate patterns documented; mix of resolver and raw DB.
- **Resolver Usage:** Most dashboards use resolver for budget; some use raw opening_balance (getSystemBudgetOverviewData, calculateCenterPerformance, society stats).
- **Aggregation Pipeline Mapping:** Full flow documented for each dashboard.
- **Role-Based Financial Visibility:** Executor and Provincial correctly scoped; Coordinator global; BudgetExportController generateReport unscoped.
- **FY Integration Points:** ProjectQueryService and ProjectAccessService ready (Phase 2); controller-level application recommended.
- **Aggregation Consistency:** Inconsistencies in getSystemBudgetOverviewData, calculateCenterPerformance, and provincial society stats.
- **Export and Report:** generateReport needs FY filter and role scope.

---

## FINAL VERDICT

**READY FOR PHASE 3** with targeted adjustments:

1. **Phase 3 can proceed** — FY hooks exist in ProjectQueryService and ProjectAccessService; dashboards can pass FY through without architectural change.
2. **Recommended pre-Phase 3 or parallel fixes:**
   - Unify Coordinator getSystemBudgetOverviewData to use resolver for approved projects.
   - Unify Provincial calculateCenterPerformance to use resolver for approved projects.
   - Document or refactor Provincial society stats (raw DB vs resolver).
3. **Export:** Add FY filter and role scope to BudgetExportController::generateReport.

No code was modified. No database changes were made. This report is read-only.

---

## REPORT FILE PATH

```
/Applications/MAMP/htdocs/Laravel/SalProjects/Documentations/V2/Budgets/Dashboards/Dashboard_Financial_Aggregation_Audit_20260304.md
```
