# Wave 2 Display Arithmetic Audit Report

**Date:** 2025-02-09  
**Phase:** 1 — Audit Only (No Modifications)  
**Scope:** Display-level controller methods in CoordinatorController, GeneralController, ProvincialController, ExecutorController, AdminReadOnlyController

---

# Revised Wave 2 Execution Plan

Wave 2 is split into two sub-waves for safer, incremental delivery:

## Wave 2A — Single-Project Arithmetic Only

**Scope:**
- Categories A, B, C, D
- Replace fallback patterns (`amount_sanctioned ?? overall_project_budget`)
- Replace remaining computation (`budget - expenses`)
- Replace utilization computation (`(expenses / budget) * 100`)
- Replace direct sanctioned/opening reads
- Instantiate resolver once per method
- **NO aggregation changes**
- **NO sum() replacements**
- **NO province/team totals**
- **NO analytics grouping**

## Wave 2B — Aggregation Refactor

**Scope:**
- Category F (aggregation)
- `sum('amount_sanctioned')`
- `sum('overall_project_budget')`
- Province/team totals
- Analytics totals
- Cross-project grouping
- Requires performance review
- May require resolver caching

> **Note:** Wave 2A must be completed and stabilized before Wave 2B begins.

---

## Executive Summary

Display-level methods across five controllers contain inline financial arithmetic that should be replaced with `ProjectFinancialResolver` and `DerivedCalculationService`. This report catalogs all occurrences by controller, method, line, category, and suggested replacement.

**Excluded from Wave 2:**
- Approval methods (already refactored in Wave 1)
- ExportController, BudgetExportController, DevelopmentProjectController
- ReportController

---

## 1. CoordinatorController

| File | Method | Line(s) | Category | Formula Detected | Suggested Replacement |
|------|--------|---------|----------|------------------|------------------------|
| CoordinatorController.php | coordinatorDashboard | 151–152 | A | `$projects->where('amount_sanctioned', '>', 0)`, `where('overall_project_budget', '>', 0)` | Filter/count only; no formula. Use resolver if filtering by resolved budget. Optional: resolve per project for filtering. |
| CoordinatorController.php | calculateBudgetSummariesFromProjects | 285 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0)` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` |
| CoordinatorController.php | calculateBudgetSummariesFromProjects | 302 | C | `$remainingBudget = $projectBudget - $totalExpenses` | `$calc->calculateRemainingBalance($projectBudget, $totalExpenses)` |
| CoordinatorController.php | projectList | 576 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0)` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` |
| CoordinatorController.php | projectList | 587–588 | D | `$budgetUtilization = ($totalExpenses / $projectBudget) * 100`; `$remainingBudget = $projectBudget - $totalExpenses` | `$calc->calculateUtilization()`; `$calc->calculateRemainingBalance()` |
| CoordinatorController.php | budgetOverview | 1335, 1339 | C | `$remaining = $report->accountDetails->sum('balance_amount')` | Report-level; uses accountDetails. Leave as-is unless report resolver introduced. |
| CoordinatorController.php | getSystemPerformanceData | 1665, 1698 | B | `return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0` | `$resolver->resolve($p)['opening_balance']` inside loop |
| CoordinatorController.php | getSystemPerformanceData | 1722–1723 | C, D | `'total_remaining' => $totalBudget - $totalExpenses`, `'budget_utilization' => round($budgetUtilization, 2)` | `$calc->calculateRemainingBalance()`, `$calc->calculateUtilization()` |
| CoordinatorController.php | getSystemAnalyticsData | 1758, 1769, 1790, 1803 | B, D | Budget selection; `$utilization = ($expensesByMonth / $budgetByMonth) * 100` | Resolver for budget; `$calc->calculateUtilization()` |
| CoordinatorController.php | getSystemActivityFeedData | 1875 | B | `return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0` | `$resolver->resolve($p)['opening_balance']` |
| CoordinatorController.php | getSystemBudgetOverviewData | 2041, 2071, 2110, 2151, 2197 | B | `return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0` | `$resolver->resolve($p)['opening_balance']` |
| CoordinatorController.php | getSystemBudgetOverviewData | 2090–2110, 2129–2180 | C | `remaining = budget - expenses` (various) | `$calc->calculateRemainingBalance()` |
| CoordinatorController.php | getProvinceComparisonData | 2214, 2244–2246, 2262 | B, C | Budget selection; `'remaining' => $projectBudget - $projectExpenses` | Resolver; `$calc->calculateRemainingBalance()` |
| CoordinatorController.php | getProvincialManagementData | 2311, 2339 | B, C | Budget selection; `'remaining' => $provinceBudget - $provinceExpenses` | Resolver; `$calc->calculateRemainingBalance()` |
| CoordinatorController.php | getProvincialManagementData | 2411, 2461 | B, C | Budget selection; `'remaining' => $teamBudget - $teamExpenses` | Resolver; `$calc->calculateRemainingBalance()` |
| CoordinatorController.php | getSystemHealthData | 2507, 2550, 2559, 2615 | B, D | Budget selection; `'budget_utilization'`, health factor | Resolver; `$calc->calculateUtilization()` |

---

## 2. GeneralController

| File | Method | Line(s) | Category | Formula Detected | Suggested Replacement |
|------|--------|---------|----------|------------------|------------------------|
| GeneralController.php | listProjects (enhance loop) | 2220, 2226, 2232 | B, C | `$projectBudget = ... ?? overall`; `$remainingBudget = $projectBudget - $totalExpenses`; `$project->calculated_remaining` | `$resolver->resolve($project)`; `$calc->calculateRemainingBalance()` |
| GeneralController.php | listProjects (enhance loop) | 2250, 2256, 2262 | B, C | Same pattern (second enhance block) | Same replacement |
| GeneralController.php | getBudgetOverviewData | 3507, 3549, 3568, 3685, 3695, 3707, 3717, 3735, 3748 | B, C | Budget selection; `'remaining' => $typeBudget - $typeApprovedExpenses` etc. | Resolver; `$calc->calculateRemainingBalance()` |
| GeneralController.php | getBudgetOverviewData | 3648, 3672 | C | `remaining` aggregation | Sum of `$calc->calculateRemainingBalance()` per project |
| GeneralController.php | getSystemPerformanceData | 3953 | B | `return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0` | `$resolver->resolve($p)['opening_balance']` |
| GeneralController.php | getSystemPerformanceData | 3979 | D | `'budget_utilization' => round($budgetUtilization, 2)` | `$calc->calculateUtilization()` |
| GeneralController.php | getSystemAnalyticsData | 4318, 4323 | B | Budget selection | Resolver |
| GeneralController.php | getContextComparisonData | 4374, 4384 | D | `'budget_utilization' => ($expenses / $budget) * 100` | `$calc->calculateUtilization()` |
| GeneralController.php | getSystemActivityFeedData | 4595 | B | Budget selection | Resolver |
| GeneralController.php | getSystemHealthData | 4615, 4623, 4676 | D | `'budget_utilization'`, health factor | `$calc->calculateUtilization()` |

---

## 3. ProvincialController

| File | Method | Line(s) | Category | Formula Detected | Suggested Replacement |
|------|--------|---------|----------|------------------|------------------------|
| ProvincialController.php | calculateBudgetSummariesFromProjects | 275 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0)` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` |
| ProvincialController.php | calculateBudgetSummariesFromProjects | 337 | C | `$remainingBudget = $projectBudget - $approvedExpenses` | `$calc->calculateRemainingBalance($projectBudget, $approvedExpenses)` |
| ProvincialController.php | projectList | 511 | A | `$projectBudget = $project->amount_sanctioned ?? 0` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` or `['amount_sanctioned']` per context |
| ProvincialController.php | projectList | 533 | D | `$project->budget_utilization = $utilization` (from `($expenses / $budget) * 100`) | `$calc->calculateUtilization($expenses, $budget)` |
| ProvincialController.php | calculateTeamPerformanceMetrics | 1992 | F | `$totalBudget = $approvedProjects->sum('amount_sanctioned')` | Loop: `$resolver->resolve($p)['opening_balance']` and sum |
| ProvincialController.php | prepareChartDataForTeamPerformance | 2064, 2075, 2111, 2157 | F, A | `$budgetByProjectType[$type] += $project->amount_sanctioned`; `->sum('amount_sanctioned')` | Resolver per project; sum `$financials['opening_balance']` |
| ProvincialController.php | calculateEnhancedBudgetData | 2180, 2191, 2201, 2212, 2224, 2235 | B, C | `$project->amount_sanctioned ?? 0`; `remaining += (amount_sanctioned) - expenses` | Resolver; `$calc->calculateRemainingBalance()` |
| ProvincialController.php | calculateEnhancedBudgetData | 2254, 2256 | B, C | Same | Same |
| ProvincialController.php | calculateEnhancedBudgetData | 2293, 2295 | C, D | `'remaining_percentage' => (remaining / totalBudget) * 100` | Use `$calc->calculateUtilization()` or derive from remaining/total |

---

## 4. ExecutorController

| File | Method | Line(s) | Category | Formula Detected | Suggested Replacement |
|------|--------|---------|----------|------------------|------------------------|
| ExecutorController.php | calculateBudgetSummariesFromProjects | 367 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0)` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` |
| ExecutorController.php | calculateBudgetSummariesFromProjects | 407 | C | `$remainingBudget = $projectBudget - $approvedExpenses` | `$calc->calculateRemainingBalance($projectBudget, $approvedExpenses)` |
| ExecutorController.php | enhanceProjectsWithMetadata | 694 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0)` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` |
| ExecutorController.php | enhanceProjectsWithMetadata | 713, 721 | C | `$remainingBudget = $projectBudget - $totalExpenses`; `'remaining' => $remainingBudget` | `$calc->calculateRemainingBalance()` |
| ExecutorController.php | getChartData | 830 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? ...)` | Resolver |
| ExecutorController.php | getChartData | 878, 894, 907 | C | `'remaining' => $budget - $expenses`; `$totalBudget - $runningExpenses` | `$calc->calculateRemainingBalance()` |
| ExecutorController.php | getProjectHealthSummary | 1026 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? ...)` | Resolver |
| ExecutorController.php | getProjectHealthSummary | 1060 | D | `'budget_utilization' => round($budgetUtilization, 1)` | `$calc->calculateUtilization()` |

---

## 5. AdminReadOnlyController

| File | Method | Line(s) | Category | Formula Detected | Suggested Replacement |
|------|--------|---------|----------|------------------|------------------------|
| AdminReadOnlyController.php | index | 58 | B | `$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0)` | `$financials = $resolver->resolve($project)`; use `$financials['opening_balance']` |
| AdminReadOnlyController.php | index | 65–66 | C, D | `$project->calculated_remaining = $projectBudget - $totalExpenses`; `$project->budget_utilization = ($totalExpenses / $projectBudget) * 100` | `$calc->calculateRemainingBalance()`; `$calc->calculateUtilization()` |

---

## Category Legend

| Category | Description | Replacement |
|----------|-------------|-------------|
| A | Direct sanctioned/opening read from project | `$resolver->resolve($project)` |
| B | Fallback `amount_sanctioned ?? overall_project_budget` | `$financials['opening_balance']` or `['amount_sanctioned']` |
| C | Remaining computation `budget - expenses` | `$calc->calculateRemainingBalance($budget, $expenses)` |
| D | Utilization `(expenses / budget) * 100` | `$calc->calculateUtilization($expenses, $budget)` |
| E | `budgets->sum('this_phase')` | `$financials['overall_project_budget']` |
| F | Aggregation across projects | Loop with resolver; sum resolved values |

---

## Replacement Patterns (Summary)

### Single project in loop
```php
$resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
$calc = app(\App\Services\Budget\DerivedCalculationService::class);
foreach ($projects as $project) {
    $financials = $resolver->resolve($project);
    $projectBudget = (float) ($financials['opening_balance']);
    $remaining = $calc->calculateRemainingBalance($projectBudget, $totalExpenses);
    $utilization = $calc->calculateUtilization($totalExpenses, $projectBudget);
    // ...
}
```

### Budget selection (Pattern B)
```php
// BEFORE
$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0);

// AFTER
$financials = $resolver->resolve($project);
$projectBudget = (float) ($financials['opening_balance']);
```

### Aggregation (Pattern F)
```php
// BEFORE
$totalBudget = $approvedProjects->sum('amount_sanctioned');

// AFTER
$totalBudget = $approvedProjects->sum(fn($p) => (float) ($resolver->resolve($p)['opening_balance'] ?? 0));
```

---

## Notes

1. **coordinatorDashboard lines 151–152**: Filter counts on `amount_sanctioned` and `overall_project_budget` columns. These are filter/count only; resolver would require resolving each project for filtering. May leave as-is or use resolver if filtering by resolved budget is required.

2. **budgetOverview (CoordinatorController)**: Uses `$report->accountDetails->sum('balance_amount')` for report-level remaining. This is report-specific, not project financial. Excluded unless report resolver is introduced.

3. **Performance**: Instantiate resolver once per method; call `resolve()` inside loops.

---

*End of Phase 1 Audit. Proceed to Phase 2 (Modification) only after confirmation.*
