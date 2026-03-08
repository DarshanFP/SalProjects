# Financial Year Propagation Consistency Audit

**Date:** 2026-03-04  
**Scope:** Full application — Financial Year ($fy) propagation and filtering consistency  
**Audit Type:** Analysis and documentation only (no code changes)

---

## 1. Executive Summary

The application recently introduced **Financial Year (FY)** based filtering (India: April 1 → March 31). Key mechanisms are:

- **`FinancialYearHelper::currentFY()`** — current FY label (e.g. `"2024-25"`).
- **`Project::scopeInFinancialYear($fy)`** — filters projects by `commencement_month_year` within the FY range.

**Overall consistency:** **Needs correction.** FY is correctly initialized and propagated in **ProvincialController**, **ExecutorController**, **GeneralController** (main dashboard and several widgets), and **CoordinatorController** (main dashboard and budget overview). However:

- **One critical runtime risk** exists: `calculateCenterPerformance($provincial, string $fy)` is called with one argument inside `prepareCenterComparisonData()`, causing "Too few arguments" when the Provincial Center Comparison widget is used.
- **Controller → helper chain breaks** occur where the controller has `$fy` but does not pass it (e.g. `prepareCenterComparisonData($provincial)`).
- **CoordinatorController** has several dashboard aggregation methods that do **not** accept or use `$fy` (e.g. `getSystemPerformanceData()`, `getPendingApprovalsData()`, `getProvincialOverviewData()`), so those widgets show all-time data and do not respond to the FY dropdown.
- **CoordinatorController::projectBudgets()** and one other route build `Project::approved()` queries **without** `->inFinancialYear($fy)`.
- **ProjectFinancialResolver::resolve()** is defined with a single parameter but is called with two arguments in three places (second argument is ignored).
- **ActivityHistoryHelper** and some Coordinator flows call `getVisibleProjectsQuery($user)` without the optional `$financialYear` argument, so activity and project lists can include all FYs.

Single-project and request-validation flows (e.g. `Project::where('project_id', $id)->first()`) are intentionally out of scope for FY filtering; the audit focuses on dashboards, list views, and aggregation pipelines.

---

## 2. Critical Runtime Risks

Method signature mismatches that cause or can cause **immediate runtime errors** (e.g. "Too few arguments to function ...").

| # | File | Method | Expected Signature | Actual Call | Line(s) |
|---|------|--------|--------------------|-------------|--------|
| 1 | `app/Http/Controllers/ProvincialController.php` | `calculateCenterPerformance` | `calculateCenterPerformance($provincial, string $fy)` (2 required params) | `$this->calculateCenterPerformance($provincial)` (1 arg) | **2555** (inside `prepareCenterComparisonData`) |

**Impact:** When a provincial user loads the dashboard and the **Center Performance Comparison** widget is rendered, PHP throws: *"Too few arguments to function App\Http\Controllers\ProvincialController::calculateCenterPerformance(), 1 passed, exactly 2 expected"*.

**Root cause:** `prepareCenterComparisonData($provincial)` does not accept `$fy` and does not pass it to `calculateCenterPerformance()`. The controller calls `prepareCenterComparisonData($provincial)` at line 239 without passing `$fy`.

---

## 3. FY Propagation Breakpoints

Controller → helper/service call chains where the controller has `$fy` in scope but **does not pass it** to a downstream method that needs or should use it.

| # | Controller | Location | Break Description |
|---|------------|----------|--------------------|
| 1 | **ProvincialController** | Line 239 | `$centerComparison = $this->prepareCenterComparisonData($provincial);` — Controller has `$fy` (line 55) but does not pass it. `prepareCenterComparisonData` then calls `calculateCenterPerformance($provincial)` without `$fy`, causing the critical error and wrong FY scope. |
| 2 | **CoordinatorController** | Lines 165–179 | Dashboard calls `getPendingApprovalsData()`, `getProvincialOverviewData()`, `getSystemPerformanceData()`, `getSystemAnalyticsData($timeRange)`, `getSystemActivityFeedData(50)`, `getProvinceComparisonData()`, `getProvincialManagementData()`, `getSystemHealthData()` with **no** `$fy` (or `$request`). Controller has `$fy` at line 48. These helpers neither receive nor use FY. |
| 3 | **CoordinatorController** | Lines 481, 1288 | `$this->projectAccessService->getVisibleProjectsQuery($coordinator)` — Optional second parameter `$financialYear` is not passed. Coordinator has `$fy` in dashboard context but not in these methods; if these views should be FY-scoped, `$fy` should be passed. |
| 4 | **GeneralController** | Lines 173–191 | `getPendingApprovalsData()`, `getCoordinatorOverviewData()`, `getDirectTeamOverviewData()` called with no args; `getSystemPerformanceData($request)` and `getSystemActivityFeedData(...)` do receive `$request` (and General’s `getSystemPerformanceData` uses `$fy` from request). Pending/overview widgets may not be FY-scoped depending on implementation. |
| 5 | **ActivityHistoryHelper** | ~Line 85 | `getVisibleProjectsQuery($user)` called without second argument. Activity feed for admin/coordinator is not restricted by FY. |

---

## 4. Controllers Missing FY Initialization

Dashboard entry points checked for:

```php
$fy = $request->input('fy', FinancialYearHelper::currentFY());
```
(or equivalent derivation from `$request`).

| Controller | Has FY initialization? | Notes |
|------------|-------------------------|--------|
| **ProvincialController** | Yes | Line 55: `$fy = $request->input('fy', FinancialYearHelper::currentFY());` |
| **ExecutorController** | Yes | Line 23: same pattern |
| **GeneralController** | Yes | Line 72: same pattern (main dashboard) |
| **CoordinatorController** | Yes | Line 48: same pattern (main dashboard); line 1986 inside `getSystemBudgetOverviewData($request)` |
| **AdminController** | **N/A** | `adminDashboard()` only returns `view('admin.dashboard')` with no project/report queries. No FY dropdown or aggregation; FY init not required unless dashboard content is extended. |
| **ApplicantController** | **Not found** | No dedicated `ApplicantController`; applicant flows appear to use **ExecutorController** (or shared routes). ExecutorController has FY init. |

**Summary:** All main dashboard controllers that perform FY-dependent aggregation **do** initialize `$fy` from the request. **AdminController** does not need FY init for the current minimal dashboard.

---

## 5. Queries Missing FY Scope

Queries that fetch **projects for dashboards or list/aggregation** but do **not** use `->inFinancialYear($fy)` where FY filtering would be expected. Single-project lookups (e.g. `Project::where('project_id', $id)->first()`) are excluded.

| File | Line(s) | Query / Snippet | Context |
|------|--------|------------------|--------|
| **CoordinatorController.php** | 1236 | `Project::approved()->with('user')` | `projectBudgets(Request $request)` — no `$fy` in method; query is not FY-scoped. |
| **CoordinatorController.php** | 1278 | `Project::approved()->distinct()->pluck('project_type')` | Same method; filter options are all-time. |
| **CoordinatorController.php** | 1497 | `Project::where('status', ProjectStatus::FORWARDED_TO_COORDINATOR)->with(...)->get()` | `getPendingApprovalsData()` — no FY; pending projects are all-time. |
| **CoordinatorController.php** | 1656–1601 | `$query->approved()` in `withCount(['projects' => ...])`; `Project::whereIn('user_id', $teamUserIds)->...` | `getProvincialOverviewData()` — provincial project counts and “latest project” are not FY-scoped. |
| **CoordinatorController.php** | 1657, 1658 | `Project::with(['user', 'user.parent', 'budgets'])->get()`; `DPReport::with(['user'])->get()` | `getSystemPerformanceData()` — loads all projects and all reports with no FY filter. |

**Note:** GeneralController dashboard and budget/performance helpers that were sampled (e.g. `getSystemPerformanceData($request)`) use `$fy` from request and apply `->inFinancialYear($fy)` to project queries; those are **not** listed as missing.

---

## 6. Resolver Signature Inconsistencies

**Definition:** `ProjectFinancialResolver::resolve(Project $project): array` — **one** parameter.

**Incorrect usages:** Calls that pass a **second** argument. In PHP the extra argument is ignored, but the API contract is broken and any intended behaviour (e.g. force/skip) is not implemented.

| File | Line | Current Call |
|------|------|--------------|
| **app/Services/Budget/AdminCorrectionService.php** | 43 | `$this->resolver->resolve($project, true)` |
| **app/Services/Budget/BudgetSyncService.php** | 76 | `$this->resolver->resolve($project, false)` |
| **app/Services/Budget/BudgetSyncService.php** | 116 | `$this->resolver->resolve($project, false)` |
| **app/Http/Controllers/Admin/BudgetReconciliationController.php** | 76 | `$this->resolver->resolve($project, true)` |
| **app/Http/Controllers/Admin/BudgetReconciliationController.php** | 123 | `$this->resolver->resolve($project, true)` |

All other audited call sites pass a single argument: `resolve($project)`.

---

## 7. Dashboard Aggregation Risks

Methods that compute **financial totals, center/coordinator statistics, or project/report aggregations** but do **not** accept `$fy` (or equivalent), so they cannot scope by financial year.

| File | Method | Current Signature | Risk |
|------|--------|-------------------|------|
| **ProvincialController.php** | `prepareCenterComparisonData` | `prepareCenterComparisonData($provincial)` | Does not accept `$fy`; calls `calculateCenterPerformance($provincial)` without it → runtime error and wrong FY. |
| **CoordinatorController.php** | `getPendingApprovalsData` | `getPendingApprovalsData()` | No params; pending reports/projects are all-time. |
| **CoordinatorController.php** | `getProvincialOverviewData` | `getProvincialOverviewData()` | No params; provincial counts and latest activity are all-time. |
| **CoordinatorController.php** | `getSystemPerformanceData` | `getSystemPerformanceData()` | No params; system-wide metrics are all-time; cache key has no FY. |
| **CoordinatorController.php** | `getSystemAnalyticsData` | `getSystemAnalyticsData($timeRange = 30)` | No `$fy`; analytics not FY-scoped. |
| **CoordinatorController.php** | `getSystemActivityFeedData` | `getSystemActivityFeedData($limit = 50)` | No `$fy`; activity feed not FY-scoped. |
| **CoordinatorController.php** | `getProvinceComparisonData` | `getProvinceComparisonData()` | No params; comparison data not FY-scoped. |
| **CoordinatorController.php** | `getProvincialManagementData` | `getProvincialManagementData()` | No params; management data not FY-scoped. |
| **CoordinatorController.php** | `getSystemHealthData` | `getSystemHealthData()` | No params; health metrics not FY-scoped. |

**Contrast:** ProvincialController methods such as `calculateCenterPerformance($provincial, string $fy)`, `calculateTeamPerformanceMetrics($provincial, string $fy)`, `prepareChartDataForTeamPerformance($provincial, string $fy)`, and `calculateEnhancedBudgetData($provincial, string $fy)` **do** accept `$fy`. GeneralController’s `getSystemPerformanceData($request = null)` derives `$fy` from `$request` and uses it.

---

## 8. Financial Year Leakage Risks

Cases where **projects** are (or could be) filtered by FY but **related data** (reports, budgets, or counts) is not, or where the reverse applies.

| # | Location | Description |
|---|----------|-------------|
| 1 | **CoordinatorController::getProvincialOverviewData()** | Uses `User::withCount(['projects' => fn($q) => $q->approved()])` with no `->inFinancialYear($fy)`. Project counts per provincial are all-time. Reports (e.g. team_reports_pending, team_reports_approved) are by `user_id` and status only — not restricted to projects in a given FY. So if a report belongs to a project in another FY, it is still counted. |
| 2 | **CoordinatorController::getPendingApprovalsData()** | Pending projects and reports are loaded without FY. If the product intent is “pending in current FY”, both project and report queries would need to be restricted (e.g. projects in FY, and reports for those projects). |
| 3 | **CoordinatorController::getSystemPerformanceData()** | Loads all projects and all reports globally. No FY on either; no leakage in the “projects in FY vs reports not” sense, but metrics are all-time. |
| 4 | **GeneralController (various)** | Where project IDs are obtained with `->inFinancialYear($fy)`, report queries using `whereIn('project_id', $projectIds)` are implicitly FY-scoped for that widget. No leakage identified in the sampled flows. |
| 5 | **ProvincialController** | Once `prepareCenterComparisonData` is fixed to pass `$fy` into `calculateCenterPerformance`, center projects and resolver usage are FY-consistent. Currently the internal call is broken (too few args). |

---

## 9. Architecture Health Score

| Rating | Description |
|--------|-------------|
| **Excellent** | FY consistently initialized and passed through all dashboard and aggregation paths; no signature mismatches; resolver API aligned with callers. |
| **Good** | FY used in most dashboard paths; minor gaps or optional FY in a few helpers. |
| **Needs correction** | FY used in main flows but clear gaps: missing propagation, aggregation methods without FY, and/or resolver misuse. **← Current state.** |
| **Critical** | Multiple runtime errors or major dashboard data wrong due to FY. |

**Verdict:** **Needs correction.** One critical runtime bug (missing `$fy` in `calculateCenterPerformance` call), several propagation gaps, coordinator widgets not FY-scoped, and resolver called with an extra parameter. Main Provincial, Executor, and General dashboard entry points and several of their helpers do use FY correctly.

---

## 10. Recommended Fix Strategy

**Do not implement fixes in this audit.** The following is a high-level plan for human-led changes.

1. **Fix critical runtime error (ProvincialController)**  
   - Add a second parameter to `prepareCenterComparisonData`: e.g. `prepareCenterComparisonData($provincial, string $fy)`.  
   - Inside `prepareCenterComparisonData`, call `$this->calculateCenterPerformance($provincial, $fy)`.  
   - At the dashboard call site (line 239), pass `$fy`: `$this->prepareCenterComparisonData($provincial, $fy)`.  
   - Re-test Provincial dashboard and Center Comparison widget.

2. **Align resolver API**  
   - Either add an optional second parameter to `ProjectFinancialResolver::resolve()` and implement the intended behaviour, or remove the second argument from all call sites (AdminCorrectionService, BudgetSyncService, BudgetReconciliationController).  
   - Re-test budget correction and sync flows.

3. **CoordinatorController FY propagation**  
   - For widgets that should respect the FY dropdown: add `$fy` (or `$request`) to the method signature and include `$fy` in cache keys where applicable.  
   - Apply `->inFinancialYear($fy)` to Project (and where appropriate report) queries in:  
     - `getSystemPerformanceData`,  
     - `getPendingApprovalsData`,  
     - `getProvincialOverviewData`,  
     - and any other aggregation methods that drive FY-sensitive widgets.  
   - Ensure `projectBudgets(Request $request)` initializes `$fy` from the request and uses `->inFinancialYear($fy)` on the approved project query and on the `project_type` distinct query.

4. **Optional: Activity and project list FY**  
   - Where activity feed or project lists should be FY-scoped, pass `$fy` into `ProjectAccessService::getVisibleProjectsQuery($user, $financialYear)` (e.g. from CoordinatorController or ActivityHistoryHelper when context includes a selected FY).

5. **Regression and product checks**  
   - Run existing tests (e.g. FinancialYearHelper, FY query integration).  
   - Manually verify Provincial, Coordinator, General, and Executor dashboards with FY dropdown and confirm no “Too few arguments” or similar errors.  
   - Confirm with product whether “pending approvals” and “provincial overview” (and similar) should be FY-scoped; if yes, implement in step 3.

---

*Audit performed without modifying any code. All recommendations are for human review and implementation.*
