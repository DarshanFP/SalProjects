# Financial Year Propagation Refactor Report

**Date:** 2026-03-04  
**Scope:** System-wide safe refactor per `Financial_Year_Propagation_Consistency_Audit.md`  
**Objective:** Correct FY propagation gaps while maintaining system stability.

---

## 1. Summary of Changes

- **Critical runtime error fixed:** `prepareCenterComparisonData` and `calculateCenterPerformance` in ProvincialController now receive and use `$fy` consistently.
- **Resolver API aligned:** `ProjectFinancialResolver::resolve(Project $project, bool $force = false)` — optional second parameter added; existing callers unchanged.
- **Coordinator dashboard FY propagation:** All eight dashboard aggregation helpers now accept `string $fy` and scope queries/cache by FY.
- **Dashboard aggregation queries:** Applied `->inFinancialYear($fy)` to Project (and related report) queries in CoordinatorController widget methods and in `projectBudgets()`.
- **ProjectAccessService:** Callers that have FY in context now pass it: `projectList()` and `budgetOverview()` pass `$fy` to `getVisibleProjectsQuery($coordinator, $fy)`.
- **Cache keys:** All Coordinator dashboard widget cache keys now include `$fy` to prevent cross-FY data contamination.
- **projectBudgets:** Initializes `$fy` from request and applies `inFinancialYear($fy)` to approved projects and project_type distinct query; passes `fy` to view compact.

---

## 2. Controllers Updated

| Controller | Changes |
|------------|---------|
| **ProvincialController** | `prepareCenterComparisonData($provincial, string $fy)`; dashboard call passes `$fy`; internal `calculateCenterPerformance($provincial, $fy)`. |
| **CoordinatorController** | Dashboard calls pass `$fy` to all eight widget helpers; `getPendingApprovalsData(string $fy)` through `getSystemHealthData(string $fy)`; `getSystemAnalyticsData(string $fy, $timeRange = 30)`; `getSystemActivityFeedData(string $fy, $limit = 50)`; `projectBudgets()` and `projectList()` initialize `$fy` and use FY-scoped queries / `getVisibleProjectsQuery(..., $fy)`; `budgetOverview(Request $request)` added, gets `$fy` and passes to `getVisibleProjectsQuery(..., $fy)`. |
| **GeneralController** | No signature changes (has its own private helpers with different signatures; already uses `$fy` from request where needed). |
| **ExecutorController** | No changes (already initializes `$fy` and applies `inFinancialYear($fy)` to project queries). |
| **AdminController** | Not modified (per instructions). |

---

## 3. Methods Updated

### ProvincialController
- `prepareCenterComparisonData($provincial)` → `prepareCenterComparisonData($provincial, string $fy)`  
- Call site: `$this->prepareCenterComparisonData($provincial, $fy)`  
- Inside: `$this->calculateCenterPerformance($provincial, $fy)`

### CoordinatorController (signatures)
- `getPendingApprovalsData()` → `getPendingApprovalsData(string $fy)`
- `getProvincialOverviewData()` → `getProvincialOverviewData(string $fy)`
- `getSystemPerformanceData()` → `getSystemPerformanceData(string $fy)`
- `getSystemAnalyticsData($timeRange = 30)` → `getSystemAnalyticsData(string $fy, $timeRange = 30)`
- `getSystemActivityFeedData($limit = 50)` → `getSystemActivityFeedData(string $fy, $limit = 50)`
- `getProvinceComparisonData()` → `getProvinceComparisonData(string $fy)`
- `getProvincialManagementData()` → `getProvincialManagementData(string $fy)`
- `getSystemHealthData()` → `getSystemHealthData(string $fy)`
- `budgetOverview()` → `budgetOverview(Request $request)` (to read `$fy`)

### Domain
- `ProjectFinancialResolver::resolve(Project $project)` → `resolve(Project $project, bool $force = false)`

---

## 4. Queries Updated

- **CoordinatorController::getPendingApprovalsData:** Pending reports filtered by `project_id` in `Project::inFinancialYear($fy)`; pending projects use `Project::...->inFinancialYear($fy)`.
- **CoordinatorController::getProvincialOverviewData:** `withCount(['projects' => ...->approved()->inFinancialYear($fy)])`; team report counts and latest report/project use FY-scoped project IDs or `Project::...->inFinancialYear($fy)`.
- **CoordinatorController::getSystemPerformanceData:** `Project::inFinancialYear($fy)->with(...)->get()`; reports loaded via `whereIn('project_id', $systemReportProjectIds)`.
- **CoordinatorController::getSystemAnalyticsData:** `Project::inFinancialYear($fy)->with(...)->get()`; reports `whereIn('project_id', $fyProjectIds)`.
- **CoordinatorController::getSystemActivityFeedData:** Activities filtered by project in FY (project-type activities: `whereIn('related_id', $fyProjectIds)`; report-type: `whereHas('report', whereIn('project_id', $fyProjectIds))`).
- **CoordinatorController::getProvinceComparisonData:** `Project::inFinancialYear($fy)->...`; reports `whereIn('project_id', $fyProjectIds)`.
- **CoordinatorController::getProvincialManagementData:** Same pattern (projects FY-scoped; reports by project_id in FY).
- **CoordinatorController::getSystemHealthData:** Same pattern.
- **CoordinatorController::projectBudgets:** `Project::approved()->inFinancialYear($fy)->with('user')`; `Project::approved()->inFinancialYear($fy)->distinct()->pluck('project_type')`.
- **CoordinatorController::projectList:** Uses `getVisibleProjectsQuery($coordinator, $fy)` (which applies `inFinancialYear($fy)` when `$fy` is not null).
- **CoordinatorController::budgetOverview:** Uses `getVisibleProjectsQuery($coordinator, $fy)`.

---

## 5. Resolver API Update

- **File:** `app/Domain/Budget/ProjectFinancialResolver.php`
- **Change:** Method signature from `resolve(Project $project): array` to `resolve(Project $project, bool $force = false): array`.
- **Behaviour:** When `$force` is `false` (default), behaviour unchanged. Callers that passed a second argument (AdminCorrectionService, BudgetSyncService, BudgetReconciliationController) continue to pass `true` or `false`; no caller changes required.
- **Pipeline:** FY filtering is applied at the **query** layer (`->inFinancialYear($fy)` on Project queries). The resolver does **not** receive `$fy`; it resolves financials for a single project. Architecture: Project query → `inFinancialYear($fy)` → then `ProjectFinancialResolver::resolve($project)` per project.

---

## 6. Runtime Errors Resolved

- **ProvincialController:** "Too few arguments to function ... calculateCenterPerformance(), 1 passed, exactly 2 expected" — resolved by adding `string $fy` to `prepareCenterComparisonData` and passing `$fy` from dashboard and into `calculateCenterPerformance($provincial, $fy)`.

---

## 7. Remaining FY-Agnostic Areas (Intentional)

- **Single-project retrieval:** e.g. `Project::where('project_id', $id)->first()` — no FY filter (correct).
- **Validation / admin correction flows:** Resolver used with `resolve($project, true)` where needed; FY not passed to resolver.
- **GeneralController:** Keeps its own helper signatures (`getSystemPerformanceData($request)`, etc.); uses `$fy` from request internally where applicable; not changed to avoid scope creep.
- **ActivityHistoryHelper:** Not modified in this refactor; may still call `getVisibleProjectsQuery($user)` without `$financialYear` for activity context (optional follow-up).
- **AdminController dashboard:** No project aggregation; FY init not added.

---

## 8. Static Consistency Check (Post-Refactor)

- **No "Too few arguments" risks:** All call sites of `prepareCenterComparisonData` and `calculateCenterPerformance` pass two arguments. All Coordinator dashboard calls pass `$fy` to the eight helpers.
- **Resolver usage:** All calls to `ProjectFinancialResolver::resolve()` use one or two arguments; signature supports both.
- **FY initialization:** ProvincialController (line 55), CoordinatorController (lines 48, 479, 1235, 1288, 2010), GeneralController (lines 72, 3571, 4002), ExecutorController (line 23) initialize `$fy` where dashboard/aggregation is used.
- **FY filtering:** Dashboard/list aggregation queries in CoordinatorController and ProvincialController use `inFinancialYear($fy)`; resolver is not given `$fy`.

---

*Refactor performed surgically per audit; database structure and business logic outside FY propagation were not altered.*
