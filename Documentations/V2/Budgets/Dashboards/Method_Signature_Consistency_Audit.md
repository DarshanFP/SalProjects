# Method Signature Consistency Audit

**Date:** 2026-03-04  
**System Version:** Laravel application (SalProjects)  
**Scope:** `app/`, `app/Http/Controllers/`, `app/Services/`, `app/Domain/`, `app/Helpers/`, `app/Models/`, `app/Traits/`

---

## Executive Summary

| Metric | Count |
|--------|--------|
| **Total Methods Scanned** | 400+ (across Controllers, Services, Domain, Models, Traits) |
| **Total Calls Analyzed** | 100+ (focus on dashboard, resolver, FY-related, and cross-layer calls) |
| **Critical Issues** | 1 |
| **High Risk Issues** | 2 |
| **Medium Risk Issues** | 2 |
| **FY Propagation Gaps** | 4 |
| **Controller → Service Chain Breaks** | 1 (resolver second parameter) |

**Summary:** One **critical** runtime error was identified: `calculateCenterPerformance($provincial, string $fy)` is called with one argument inside `prepareCenterComparisonData()`, causing "Too few arguments" when the Provincial dashboard Center Comparison widget is used. Additional **high**-risk issues include missing `$fy` propagation to `prepareCenterComparisonData` and `ProjectFinancialResolver::resolve()` being invoked with a second argument that the method does not accept (logic/API mismatch). FY propagation gaps and coordinator widget consistency are documented for follow-up.

---

## Critical Issues (Immediate Runtime Errors)

### CRI-1: `calculateCenterPerformance()` called with too few arguments

| Field | Value |
|-------|--------|
| **File** | `app/Http/Controllers/ProvincialController.php` |
| **Method** | `calculateCenterPerformance($provincial, string $fy)` |
| **Call Location** | Line 2555, inside `prepareCenterComparisonData()` |
| **Problem** | Method requires 2 parameters (`$provincial`, `string $fy`). It is called as `$this->calculateCenterPerformance($provincial)` with only 1 argument. This triggers a PHP fatal error: "Too few arguments to function ... exactly 2 expected". |
| **Recommended Fix** | 1) Add a second parameter `string $fy` to `prepareCenterComparisonData($provincial, string $fy)`. 2) At line 2555, call `$this->calculateCenterPerformance($provincial, $fy)`. 3) At the call site (line 239), pass `$fy`: `$this->prepareCenterComparisonData($provincial, $fy)`. |

**Current Code (line 2555):**
```php
$centerPerformance = $this->calculateCenterPerformance($provincial);
```

**Suggested Fix:**
```php
$centerPerformance = $this->calculateCenterPerformance($provincial, $fy);
```

And update the method signature and controller call:
- `private function prepareCenterComparisonData($provincial, string $fy)`
- `$centerComparison = $this->prepareCenterComparisonData($provincial, $fy);`

---

## High Risk Issues

### HIGH-1: `prepareCenterComparisonData()` does not accept or pass `$fy`

| Field | Value |
|-------|--------|
| **File** | `app/Http/Controllers/ProvincialController.php` |
| **Method** | `prepareCenterComparisonData($provincial)` |
| **Call Location** | Line 239 in `index()` |
| **Problem** | Controller has `$fy` in scope (line 55) but calls `prepareCenterComparisonData($provincial)` without passing it. The method therefore cannot pass `$fy` to `calculateCenterPerformance()`, contributing to CRI-1 and to center comparison data not being scoped by financial year. |
| **Recommended Fix** | Add second parameter `string $fy` to `prepareCenterComparisonData` and pass `$fy` from the controller. Then pass `$fy` into `calculateCenterPerformance()` as in CRI-1. |

---

### HIGH-2: `ProjectFinancialResolver::resolve()` called with 2 arguments (method accepts 1)

| Field | Value |
|-------|--------|
| **File** | `app/Domain/Budget/ProjectFinancialResolver.php` |
| **Method** | `public function resolve(Project $project): array` (1 parameter only) |
| **Call Locations** | See table below. |
| **Problem** | Callers pass a second argument (`true` or `false`). The resolver signature does not define a second parameter, so the extra argument is ignored in PHP. This is an API/logic mismatch: callers may expect different behaviour (e.g. force recalc or skip validation). |
| **Recommended Fix** | Either (a) add an optional second parameter to `resolve()` (e.g. `bool $force = false`) and implement the intended behaviour, or (b) remove the second argument from all call sites if it was added by mistake. |

**Call sites passing 2 arguments:**

| File | Line | Current Call |
|------|------|--------------|
| `app/Services/Budget/AdminCorrectionService.php` | 43 | `$this->resolver->resolve($project, true)` |
| `app/Services/Budget/BudgetSyncService.php` | 76 | `$this->resolver->resolve($project, false)` |
| `app/Services/Budget/BudgetSyncService.php` | 116 | `$this->resolver->resolve($project, false)` |
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | 76 | `$this->resolver->resolve($project, true)` |
| `app/Http/Controllers/Admin/BudgetReconciliationController.php` | 123 | `$this->resolver->resolve($project, true)` |

---

## Medium Risk Issues

### MED-1: Coordinator dashboard widgets not consistently FY-scoped

| Field | Value |
|-------|--------|
| **Observation** | CoordinatorController has `$fy` in scope (line 48). `getSystemBudgetOverviewData($request)` derives `$fy` from the request and applies `inFinancialYear($fy)`. In contrast, `getSystemPerformanceData()` has no parameters, uses a non-FY cache key, and loads `Project::with(...)->get()` and `DPReport::with(...)->get()` with no FY filter. |
| **Impact** | System Performance widget reflects all-time data; changing the FY dropdown does not affect it. Inconsistent behaviour across dashboard widgets. |
| **Recommended Fix** | Consider adding an optional `$request` or `$fy` parameter to `getSystemPerformanceData()` and using `inFinancialYear($fy)` (and including `$fy` in the cache key) so the widget respects the selected FY. |

---

### MED-2: `getVisibleProjectsQuery()` called without FY where FY may be intended

| Field | Value |
|-------|--------|
| **File** | `app/Http/Controllers/CoordinatorController.php` |
| **Method** | `ProjectAccessService::getVisibleProjectsQuery(User $user, ?string $financialYear = null)` |
| **Call Locations** | Lines 481, 1288: `$this->projectAccessService->getVisibleProjectsQuery($coordinator)` |
| **Observation** | The second parameter is optional; no runtime error. Coordinator dashboard has `$fy` in scope but does not pass it, so those queries are not filtered by FY. |
| **Recommended Fix** | If coordinator views should be FY-scoped, pass `$fy`: `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)`. |

---

## FY Propagation Gaps

Locations where `$fy` is available but not passed to downstream methods or queries.

| # | File | Context | Gap | Recommended Action |
|---|------|---------|-----|--------------------|
| 1 | `ProvincialController.php` | Line 239 | `prepareCenterComparisonData($provincial)` called without `$fy`; method does not accept or pass `$fy` | Add `$fy` to signature and all call sites; pass `$fy` into `calculateCenterPerformance()` (see CRI-1/HIGH-1). |
| 2 | `ProvincialController.php` | Line 2555 | `calculateCenterPerformance($provincial)` called without `$fy` | Pass `$fy` (requires `prepareCenterComparisonData` to accept and forward `$fy`). |
| 3 | `CoordinatorController.php` | Lines 167, 1648 | `getSystemPerformanceData()` called with no args; method does not use `$fy` | Add `$fy` (or `$request`) to method and scope projects/reports by FY if product requirement is FY-specific widget. |
| 4 | `CoordinatorController.php` | Lines 481, 1288 | `getVisibleProjectsQuery($coordinator)` called without `$fy` | Pass `$fy` when coordinator scope should be FY-filtered. |

---

## Controller → Service Chain Breaks

### Resolver second parameter not propagated

- **Definition:** `ProjectFinancialResolver::resolve(Project $project): array` (single parameter).
- **Callers:** AdminCorrectionService, BudgetSyncService, BudgetReconciliationController pass a second argument; it is ignored.
- **Chain:** Controller/Service → `ProjectFinancialResolver::resolve($project, $bool)`. The resolver does not support the second parameter, so any intended behaviour (e.g. force/skip) is not implemented. Fix by either extending the resolver API or removing the extra argument at call sites.

### FY chain (Provincial dashboard)

- **Controller:** `ProvincialController::index()` has `$fy` and correctly passes it to `calculateCenterPerformance($provincial, $fy)`, `calculateTeamPerformanceMetrics($provincial, $fy)`, `prepareChartDataForTeamPerformance($provincial, $fy)`, `calculateEnhancedBudgetData($provincial, $fy)`.
- **Break:** `prepareCenterComparisonData($provincial)` is called without `$fy` and internally calls `calculateCenterPerformance($provincial)` without `$fy`, breaking the FY chain for the Center Comparison widget and causing the critical argument count error.

---

## Suggested Fix Strategy

1. **Fix critical runtime error (CRI-1)**
   - In `ProvincialController.php`:
     - Change `prepareCenterComparisonData($provincial)` to `prepareCenterComparisonData($provincial, string $fy)`.
     - Inside `prepareCenterComparisonData`, replace `$this->calculateCenterPerformance($provincial)` with `$this->calculateCenterPerformance($provincial, $fy)`.
     - At line 239, change to `$centerComparison = $this->prepareCenterComparisonData($provincial, $fy);`
   - Re-test Provincial dashboard, especially Center Performance Comparison widget.

2. **Fix resolver API mismatch (HIGH-2)**
   - Decide intended semantics for the second argument (e.g. force recalc, skip validation).
   - Either add an optional second parameter to `ProjectFinancialResolver::resolve()` and implement it, or remove the second argument from AdminCorrectionService, BudgetSyncService, and BudgetReconciliationController.
   - Re-test budget correction and sync flows.

3. **Optional: FY consistency (MED-1, MED-2, FY gaps)**
   - Coordinator: Add `$fy` (or `$request`) to `getSystemPerformanceData()` and use `inFinancialYear($fy)` (and FY in cache key) if the widget should be FY-scoped.
   - Consider passing `$fy` into `getVisibleProjectsQuery($coordinator, $fy)` where coordinator views should be FY-filtered.
   - Re-test coordinator and general dashboards after changes.

4. **Regression**
   - Run existing tests (e.g. `FinancialYearHelperTest`, `FYQueryIntegrationTest`).
   - Manually test Provincial and Coordinator dashboards (filter by FY, switch FY, verify Center Comparison and budget/performance widgets).

---

## Architectural Observations

1. **FY refactor consistency**  
   Financial year support was added to several dashboard methods (`calculateCenterPerformance`, `calculateTeamPerformanceMetrics`, `prepareChartDataForTeamPerformance`, `calculateEnhancedBudgetData`). `prepareCenterComparisonData` was not updated to accept or pass `$fy`, leading to the critical signature mismatch and broken FY scope for that widget.

2. **Resolver API**  
   `ProjectFinancialResolver::resolve()` is the single public entry for project-level financial resolution. Multiple call sites pass a second argument that is not part of the current API. Clarifying and aligning the resolver signature with caller expectations will avoid silent misuse.

3. **Dashboard widget parity**  
   Coordinator dashboard mixes FY-aware widgets (e.g. budget overview via `getSystemBudgetOverviewData($request)`) and FY-agnostic ones (e.g. `getSystemPerformanceData()`). Defining a consistent rule (“all widgets respect FY” vs “only some”) will guide future changes and avoid inconsistent UX.

4. **Traits and error handling**  
   `HandlesErrors::handleException()` and related methods are called with the correct number and order of arguments in the audited call sites (e.g. ProjectController). No signature mismatch was found in the traits reviewed.

5. **Scope and strategy methods**  
   `Project::scopeInFinancialYear($query, string $fy)` and other Eloquent scopes are used correctly as `->inFinancialYear($fy)`. `FinancialYearHelper::listAvailableFY(int $yearsBack = 10)` is called with 0 or 1 argument appropriately. No issues identified in these patterns.

---

## Appendix: Methods Index (Sample – FY and dashboard-related)

| Class | Method | File | Required Parameters | Optional | Full Signature |
|-------|--------|------|---------------------|----------|----------------|
| ProvincialController | calculateCenterPerformance | ProvincialController.php | $provincial, string $fy | — | `private function calculateCenterPerformance($provincial, string $fy)` |
| ProvincialController | prepareCenterComparisonData | ProvincialController.php | $provincial | — | `private function prepareCenterComparisonData($provincial)` |
| ProvincialController | calculateTeamPerformanceMetrics | ProvincialController.php | $provincial, string $fy | — | `private function calculateTeamPerformanceMetrics($provincial, string $fy)` |
| ProvincialController | prepareChartDataForTeamPerformance | ProvincialController.php | $provincial, string $fy | — | `private function prepareChartDataForTeamPerformance($provincial, string $fy)` |
| ProvincialController | calculateEnhancedBudgetData | ProvincialController.php | $provincial, string $fy | — | `private function calculateEnhancedBudgetData($provincial, string $fy)` |
| Project (Model) | scopeInFinancialYear | Project.php | $query, string $fy | — | `public function scopeInFinancialYear($query, string $fy)` |
| ProjectFinancialResolver | resolve | ProjectFinancialResolver.php | Project $project | — | `public function resolve(Project $project): array` |
| ProjectAccessService | getVisibleProjectsQuery | ProjectAccessService.php | User $user | ?string $financialYear = null | `public function getVisibleProjectsQuery(User $user, ?string $financialYear = null)` |
| FinancialYearHelper | listAvailableFY | FinancialYearHelper.php | — | int $yearsBack = 10 | `public static function listAvailableFY(int $yearsBack = 10): array` |

---

*Audit performed without modifying any code. All recommendations are for human review and implementation.*
