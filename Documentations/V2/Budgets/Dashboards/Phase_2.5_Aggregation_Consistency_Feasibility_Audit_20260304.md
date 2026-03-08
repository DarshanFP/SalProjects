# Phase 2.5 Aggregation Consistency Feasibility Audit

**Task:** Phase 2.5 Aggregation Consistency Feasibility Audit  
**Date:** 2026-03-04  
**Mode:** Audit (Read-only — no code or database modifications)

**Reference:** Financial_Year_Dashboard_Implementation_Plan_20260304.md — Phase 2.5

---

## PART 1 — RESOLVER DEPENDENCY SCAN

Search pattern: `ProjectFinancialResolver` (PHP files).

| File | Method | Resolver Usage |
|------|--------|----------------|
| app/Domain/Budget/ProjectFinancialResolver.php | (class) | Defines resolver; used by all callers. |
| app/Http/Controllers/Projects/ProjectController.php | show(), edit flow | `app(ProjectFinancialResolver::class)`; `$resolver->resolve($project)` for resolvedFundFields. |
| app/Http/Controllers/Projects/ExportController.php | (constructor), export flow | Injected `ProjectFinancialResolver $financialResolver`; `$this->financialResolver->resolve($project)` for DOC export. |
| app/Http/Controllers/CoordinatorController.php | calculateBudgetSummariesFromProjects | `app(ProjectFinancialResolver::class)`; resolve per project, sum opening_balance. |
| app/Http/Controllers/CoordinatorController.php | getSystemBudgetOverviewData | `app(ProjectFinancialResolver::class)` for pending total only; approved uses raw opening_balance. |
| app/Http/Controllers/CoordinatorController.php | getSystemPerformanceData | Resolver used; memoized `$resolvedFinancials`; sum opening_balance from resolver. |
| app/Http/Controllers/CoordinatorController.php | getSystemAnalyticsData | Resolver used; memoized; sum opening_balance from resolver. |
| app/Http/Controllers/CoordinatorController.php | getProvinceComparisonData | Resolver used; memoized; sum opening_balance from resolver. |
| app/Http/Controllers/CoordinatorController.php | (other methods) | approveProject, reportList/projectList flows use resolver where needed. |
| app/Http/Controllers/ProvincialController.php | calculateBudgetSummariesFromProjects | Resolver per project; sum opening_balance. |
| app/Http/Controllers/ProvincialController.php | calculateTeamPerformanceMetrics | Resolver; memoized; sum opening_balance from resolver. |
| app/Http/Controllers/ProvincialController.php | prepareChartDataForTeamPerformance | Resolver; memoized; opening_balance from resolver. |
| app/Http/Controllers/ProvincialController.php | calculateCenterPerformance | Resolver for pending only; approved uses raw opening_balance. |
| app/Http/Controllers/ProvincialController.php | calculateEnhancedBudgetData | Resolver; memoized; sum opening_balance from resolver. |
| app/Http/Controllers/ExecutorController.php | calculateBudgetSummariesFromProjects | Resolver per project; sum opening_balance. |
| app/Http/Controllers/GeneralController.php | listBudgets, finance analytics | Resolver; memoized; sum opening_balance from resolver. |
| app/Http/Controllers/Admin/AdminReadOnlyController.php | projectIndex | Resolver per project; calculated_budget from resolver. |
| app/Services/BudgetValidationService.php | calculateBudgetData (private) | `app(ProjectFinancialResolver::class)->resolve($project)` for all financial fields. |
| app/Services/Budget/ProjectFundFieldsResolver.php | resolve | Delegates to ProjectFinancialResolver. |
| app/Services/ProjectDataHydrator.php | (constructor) | Injects ProjectFinancialResolver. |
| app/Http/Controllers/Reports/Monthly/MonthlyDevelopmentProjectController.php | (methods) | Resolver for report/project financial context. |

**Conclusion:** The resolver is already the canonical source in most dashboard and export paths. CoordinatorController uses it in getSystemPerformanceData, getSystemAnalyticsData, getProvinceComparisonData, and calculateBudgetSummariesFromProjects; the only method in that controller using raw `opening_balance` for approved projects is getSystemBudgetOverviewData (and its breakdowns + topProjectsByBudget). ProvincialController uses resolver everywhere except calculateCenterPerformance (approved leg) and society statistics (raw DB).

---

## PART 2 — RAW AGGREGATION USAGE

| File | Method | Raw Field Used |
|------|--------|----------------|
| CoordinatorController | getSystemBudgetOverviewData | `$p->opening_balance` — totalBudget, typeBudget, provinceBudget, centerBudget, provincialBudget, topProjectsByBudget |
| ProvincialController | calculateCenterPerformance | `$p->opening_balance` — centerBudget (approved projects only) |
| ProvincialController | provincialDashboard (society stats) | `SUM(COALESCE(amount_sanctioned, 0))`; `SUM(GREATEST(0, overall_project_budget - amount_forwarded - local_contribution))` by society_id |
| ReportMonitoringService | getBudgetUtilisationSummary | `->sum('amount_sanctioned')` on **report** accountDetails (report-level, not project-level) |
| AnnualReportService / HalfYearlyReportService / QuarterlyReportService | (report aggregation) | `amount_sanctioned_overview`, `detail.amount_sanctioned` — report/monthly/quarterly aggregates, not project dashboard |

**Note:** No app code uses `sum('overall_project_budget')` on projects. Raw project-level aggregation in scope for Phase 2.5 is limited to CoordinatorController::getSystemBudgetOverviewData and ProvincialController::calculateCenterPerformance and society statistics.

---

## PART 3 — COORDINATOR CONTROLLER ANALYSIS

**Method:** `CoordinatorController::getSystemBudgetOverviewData()`

**1. How approved project totals are calculated**

- `$totalBudget = $approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0));`
- Same pattern in breakdowns: by project type, province, center, and provincial (lines 2069–2070, 2107, 2146, 2190).
- `topProjectsByBudget`: sort and map use `(float) ($p->opening_balance ?? 0)` and `$projectBudget = (float) ($p->opening_balance ?? 0)`.

**2. Resolver usage elsewhere in same controller**

- **Pending total in same method:** `$financialResolver = app(ProjectFinancialResolver::class)`; `$pendingTotal = $pendingProjects->sum(fn ($p) => ... $financialResolver->resolve($p)['amount_requested'] ...)`.
- **Other private methods:** getSystemPerformanceData, getSystemAnalyticsData, getProvinceComparisonData use resolver and memoized `$resolvedFinancials[$p->project_id]`; calculateBudgetSummariesFromProjects uses resolver per project.

**3. Memoized resolver arrays**

- getSystemBudgetOverviewData does **not** currently memoize resolver for approved projects; it only calls the resolver for pending. Approved totals use raw `$p->opening_balance` throughout.

**4. Safety of replacing raw opening_balance with resolver**

- For **approved** projects, `ProjectFinancialResolver::applyCanonicalSeparation()` sets `opening_balance` to `(float) ($project->opening_balance ?? 0)` (DB value). So resolver output matches current raw aggregation for approved projects under normal DB state.
- Replacing raw with resolver: (a) keeps behaviour identical for approved projects, (b) aligns with other coordinator methods, (c) future-proofs if resolver logic or DB sync ever change.
- **Implementation:** Resolve each approved project once (memoize in `$resolvedFinancials`), then use `$resolvedFinancials[$p->project_id]['opening_balance']` for all sums and for topProjectsByBudget. Resolver is already instantiated in the method for pending; reuse for approved.

| Risk | Assessment |
|------|------------|
| Semantic change for approved totals | **None** — resolver returns DB opening_balance for approved. |
| Performance | **Low** — one resolve per approved project; same pattern already used in getSystemPerformanceData/getSystemAnalyticsData with similar project sets. |
| Cache key / TTL | Unchanged; cache key is filter-based; no change needed. |
| Downstream views | No change to structure of returned array; only values may differ if DB and resolver ever diverge (currently they do not for approved). |

**Verdict:** Safe to implement Fix 1.

---

## PART 4 — PROVINCIAL CENTER PERFORMANCE ANALYSIS

**Method:** `ProvincialController::calculateCenterPerformance($provincial)`

**Raw aggregation**

- `$centerBudget = (float) ($approvedProjects->sum(fn ($p) => (float) ($p->opening_balance ?? 0)) ?? 0);`
- Pending already uses resolver: `$centerPendingBudget = (float) $pendingProjects->sum(fn ($p) => (float) (($resolver->resolve($p)['amount_requested'] ?? 0)));`

**Resolver usage elsewhere in same controller**

- calculateBudgetSummariesFromProjects, calculateTeamPerformanceMetrics, prepareChartDataForTeamPerformance, calculateEnhancedBudgetData all use resolver (with memoization where applicable). Only calculateCenterPerformance uses raw opening_balance for the approved leg.

**Downstream usage**

- `calculateCenterPerformance` is called from provincialDashboard (line 213) and from the view/context that shows center performance (line 2527). Return structure: `[ center => [ 'projects', 'budget', 'pending_budget', 'expenses', 'reports', ... ] ]`. Replacing raw `opening_balance` with resolver-derived budget only changes the numeric value of `budget`; keys and structure stay the same.

**Safety**

- Same as Coordinator: for approved projects, resolver returns DB opening_balance. Swap to resolver-based sum is behaviourally equivalent and aligns with other provincial methods.
- Resolver is already instantiated in the method; add a memoized resolve for each approved project per center (or resolve once per project across the loop if projects are not duplicated across centers). Centers are disjoint by user_id, so each project appears in one center only — resolving per project once per center is acceptable.

| Risk | Assessment |
|------|------------|
| Semantic change | **None** for approved — resolver uses DB opening_balance. |
| Center metrics / chart data | **No break** — same keys and structure; only budget value source changes. |
| Performance | **Low** — per-center project sets; resolver calls already used for pending in same method. |

**Verdict:** Safe to implement Fix 2.

---

## PART 5 — PROVINCIAL SOCIETY STATISTICS ANALYSIS

**Current logic (provincialDashboard, when `$enableSocietyBreakdown`)**

- **Approved totals:** `Project::where('province_id', $provinceId)->whereIn('status', APPROVED)->selectRaw('society_id, SUM(COALESCE(amount_sanctioned, 0)) as total')->groupBy('society_id')`.
- **Pending totals:** `Project::where('province_id', $provinceId)->whereNotIn('status', FINAL_STATUSES)->selectRaw('society_id, SUM(GREATEST(0, COALESCE(overall_project_budget, 0) - COALESCE(amount_forwarded, 0) - COALESCE(local_contribution, 0))) as total')->groupBy('society_id')`.
- **Reported totals:** DPReport + DP_AccountDetails join, sum total_expenses by society_id.

**1. Resolver vs DB sums**

- **Approved:** Resolver’s approved opening_balance equals DB opening_balance; for sanctioned amount, resolver uses `amount_sanctioned` from DB for approved. So sum of resolver `opening_balance` by society would match sum of DB opening_balance; sum of resolver `amount_sanctioned` would match sum of DB amount_sanctioned for approved. Phase-based and type-specific strategies can compute overall/sanctioned differently before overlay, but for approved the overlay forces opening_balance and amount_sanctioned from DB. So **approved** society totals from resolver should match current DB sums unless there is existing data inconsistency.
- **Pending:** Resolver uses `amount_requested` (computed); current query uses `overall_project_budget - amount_forwarded - local_contribution`. Resolver applies type/phase logic and canonical separation; so **pending** totals can differ from current raw formula, especially for phase-based or direct-mapped types.

**2. Performance**

- **Option A (resolver per project, then aggregate by society):** Load approved/pending projects for province (with society_id), resolve each once, group by society_id and sum. One query for projects plus N resolves (N = number of projects in province). For provinces with many projects this is more work than a single grouped SQL sum but is the same pattern as other provincial dashboard methods.
- **Option B (keep raw DB):** No change; current single-query aggregation. Possible divergence from resolver for pending and for any strategy-specific behaviour.

| Option | Consistency | Performance | Recommendation |
|--------|-------------|-------------|----------------|
| **A — Resolver aggregation** | Aligns with rest of app; type/phase and canonical semantics respected. | More work than raw: one project query + resolve per project; then in-memory group/sum by society. Acceptable for typical province size. | **Recommended** for Phase 2.5; implement and profile. |
| **B — Raw DB** | Society totals may diverge from resolver (especially pending). | Best: single aggregated query. | Use only if Option A shows a real performance issue; document divergence. |

**Conclusion:** Option A is feasible; resolver for approved matches DB in normal case; for pending, resolver is more correct. Performance impact is bounded by number of projects in the province and is consistent with other resolver-based dashboard code.

---

## PART 6 — EXPORT & REPORT DEPENDENCY CHECK

| Component | Depends on raw DB sums? | Depends on resolver? | Affected by Phase 2.5? |
|-----------|--------------------------|------------------------|-------------------------|
| **BudgetExportController** | No. exportPdf and prepareReportData use BudgetValidationService::getBudgetSummary($project), which uses ProjectFinancialResolver in calculateBudgetData. | Yes — via BudgetValidationService. | No — Phase 2.5 does not change BudgetExportController or BudgetValidationService. |
| **ExportController** (DOC) | No. Uses injected ProjectFinancialResolver and resolves per project for export. | Yes — `$this->financialResolver->resolve($project)`. | No — not modified in Phase 2.5. |
| **ReportMonitoringService** | getBudgetUtilisationSummary uses `report->accountDetails->sum('amount_sanctioned')` and sum('total_expenses') — **report-level** (DPAccountDetail), not project-level. | No project-level resolver. | No — report-level aggregation; Phase 2.5 only touches project-level dashboard aggregation. |

**Conclusion:** Phase 2.5 changes do not affect report exports, PDF/Excel generation, or ReportMonitoringService. Exports already rely on the resolver where project-level financials are needed.

---

## PART 7 — PERFORMANCE IMPACT ANALYSIS

| Scenario | Performance Impact |
|----------|--------------------|
| **Coordinator getSystemBudgetOverviewData** | Already loads all approved projects and pending projects; today no resolver for approved. After fix: one resolve per approved project (memoized). Same pattern as getSystemPerformanceData/getSystemAnalyticsData which already resolve all system projects. Impact: **Low** — added resolver calls on already-loaded collection; 15-min cache unchanged. |
| **Provincial calculateCenterPerformance** | Per-center loop; for each center, approved + pending projects. Already resolves pending; after fix: resolve approved per project (can memoize per project id across centers). Impact: **Low** — resolver calls proportional to number of projects. |
| **Provincial society statistics (Option A)** | Replace two grouped SQL queries with: load projects for province (by status), resolve each once, group by society_id and sum in memory. Impact: **Low–Medium** — depends on province size; typical provinces unlikely to have thousands of projects. If needed, can limit to Option B with documentation. |

**Overall:** Resolver is already used in similar or larger scopes (e.g. coordinator system-wide performance/analytics). Phase 2.5 aligns a few remaining dashboard paths with that pattern; no new N+1 or new query shape.

---

## PART 8 — PHASE 2.5 IMPLEMENTATION RISK

| Fix | Risk | Recommendation |
|-----|------|-----------------|
| **Fix 1 — Coordinator Budget Overview** | **Low.** Resolver for approved returns DB opening_balance; same controller already uses resolver with memoization elsewhere. Add memoized resolve for approved projects in getSystemBudgetOverviewData; use for totalBudget, all breakdowns (type, province, center, provincial), and topProjectsByBudget. | **Proceed.** |
| **Fix 2 — Provincial Center Performance** | **Low.** Same as Fix 1 for approved; structure and consumers unchanged. Use resolver for approved center budget with per-project memoization. | **Proceed.** |
| **Fix 3 — Society statistics strategy** | **Low–Medium.** Option A (resolver aggregation) is consistent and feasible; performance depends on province size. Option B (keep raw) is acceptable only with explicit documentation of divergence. | **Proceed with Option A;** profile after implementation; fall back to Option B only if profiling justifies it, with documentation. |

---

## REPORT STRUCTURE SUMMARY

- **Resolver dependency scan:** Resolver is widely used; only getSystemBudgetOverviewData and calculateCenterPerformance (approved leg) and society stats use raw project-level aggregation.
- **Raw aggregation usage:** Confined to CoordinatorController (getSystemBudgetOverviewData), ProvincialController (calculateCenterPerformance, society stats); report-level amount_sanctioned sums are out of scope.
- **Coordinator analysis:** Replacing raw opening_balance with resolver in getSystemBudgetOverviewData is safe and aligns with other coordinator methods.
- **Provincial center performance:** Same conclusion; no impact on center metrics structure or downstream.
- **Society statistics:** Option A (resolver) recommended; Option B acceptable with documentation if performance requires.
- **Export/report:** BudgetExportController, ExportController, ReportMonitoringService do not depend on the raw dashboard aggregations changed in Phase 2.5.
- **Performance:** Impact is low; pattern already used elsewhere with similar or larger data sets.
- **Implementation risk:** All three fixes are low risk with clear recommendations.

---

## FINAL VERDICT

**SAFE TO IMPLEMENT PHASE 2.5**

1. **Resolver behaviour:** For approved projects, resolver’s opening_balance is the DB value; replacing raw aggregation does not change semantics under current invariants.
2. **Consistency:** Fix 1 and Fix 2 align Coordinator and Provincial dashboards with existing resolver-based methods in the same controllers.
3. **Exports/reports:** No dependency on the modified aggregations; no change to export or report behaviour.
4. **Performance:** Resolver-per-project aggregation is already used in the same or larger scopes; impact is low.
5. **Society stats:** Option A is feasible and recommended; Option B remains available with documentation if needed.

Proceed with Phase 2.5 implementation per the plan (Fix 1, Fix 2, Fix 3 with Option A preferred). No code or database was modified during this audit.

---

## REPORT FILE PATH

```
/Applications/MAMP/htdocs/Laravel/SalProjects/Documentations/V2/Budgets/Dashboards/Phase_2.5_Aggregation_Consistency_Feasibility_Audit_20260304.md
```
