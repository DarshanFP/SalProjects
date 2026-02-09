# Controller Arithmetic Audit Report

**Date:** 2025-02-09  
**Phase:** 1 — Analysis Only (No Modifications)  
**Scope:** All controllers in `app/Http/Controllers/`

---

## Executive Summary

Controllers contain **inline financial arithmetic** that violates the invariant:

> Controllers are orchestration only. All financial truth comes from ProjectFinancialResolver. All arithmetic comes from DerivedCalculationService.

This report catalogs all occurrences and recommends replacements.

---

## 1. CoordinatorController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **approveProject** | 1113–1121 | `$overallBudget = $project->overall_project_budget ?? 0`; fallback `calculateProjectTotal($project->budgets->map('this_phase'))` | `$financials = app(ProjectFinancialResolver::class)->resolve($project)`; use `$financials['overall_project_budget']`, `['amount_forwarded']`, `['local_contribution']` |
| **approveProject** | 1144 | `$amountSanctioned = $overallBudget - $combinedContribution` | Use `$financials['amount_sanctioned']` |
| **approveProject** | 1153 | `$openingBalance = $amountSanctioned + $combinedContribution` | Use `$financials['opening_balance']` |
| **projectBudgets** (or similar) | 285, 302 | `$projectBudget = $project->amount_sanctioned ?? $project->overall_project_budget`; `$remainingBudget = $projectBudget - $totalExpenses` | Resolve per project; use `$financials['opening_balance']` for budget; `$calc->calculateRemainingBalance($opening, $expenses)` |
| **projectBudgets** (or similar) | 576, 588, 602–603 | Same pattern; `$budgetUtilization = ... (expenses / budget) * 100` | Resolver + `$calc->calculateUtilization($expenses, $openingBalance)` |
| **Various dashboard/report methods** | 1679–1681, 1712–1714, 1772–1774, 1804–1806, 1817–1819, 1889–1891, 2055–2057, 2085–2087, 2124–2126, 2165–2167, 2211–2213, 2259–2261, 2325–2327, 2425–2427, 2521–2523 | `return $p->amount_sanctioned ?? $p->overall_project_budget ?? 0` (budget selection) | For each project in loop: `$financials = $resolver->resolve($p)`; use `$financials['opening_balance']` (or `amount_sanctioned` per context) |
| **Various** | 1726, 1737–1738, 1784, 2080, 2115–2116, 2154–2155, 2195–2196, 2229–2230, 2277–2278, 2288–2289, 2354–2355, 2476–2477 | `utilization = (expenses / budget) * 100`; `remaining = budget - expenses` | `$calc->calculateUtilization()`, `$calc->calculateRemainingBalance()` |

---

## 2. GeneralController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **Executor project list / enhance** | 2220, 2226, 2232–2233 | `$projectBudget = $project->amount_sanctioned ?? $project->overall_project_budget`; `$remainingBudget = $projectBudget - $totalExpenses`; `$budgetUtilization = ...` | Resolver per project; DerivedCalculationService for remaining/utilization |
| **Executor project list / enhance** | 2250, 2256, 2262–2263 | Same pattern | Same replacement |
| **approveAsCoordinator** (or similar) | 2542–2568 | `$overallBudget = $project->overall_project_budget ?? 0`; fallback `calculateProjectTotal($project->budgets->map('this_phase'))`; `$amountSanctioned = max(0, $overallBudget - $combinedContribution)`; `$openingBalance = $amountSanctioned + $combinedContribution` | `$financials = app(ProjectFinancialResolver::class)->resolve($project)`; use `$financials['amount_sanctioned']`, `['opening_balance']`; persist those to project |
| **Budget breakdown / reports** | 3512–3514, 3534, 3540–3541, 3554–3556, 3574–3575, 3659, 3683, 3690–3692, 3701–3702, 3712–3714, 3723–3724, 3740–3742, 3754–3755 | `$totalBudget = $projects->sum(fn($p) => $p->amount_sanctioned ?? $p->overall_project_budget)`; `utilization = (expenses / budget) * 100`; `remaining = budget - expenses` | Resolve each project; sum `$financials['opening_balance']`; use DerivedCalculationService for utilization/remaining |
| **Various** | 4323–4325, 4328–4330, 4380, 4390, 4599–4601, 4621, 4664, 4682 | Same budget selection and utilization patterns | Same replacements |

---

## 3. ProvincialController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **Project list enhance** | 275 | `$projectBudget = $project->amount_sanctioned ?? $project->overall_project_budget` | Resolver per project; use `$financials['opening_balance']` |
| **Project health / utilization** | 511, 522, 533 | `$projectBudget = $project->amount_sanctioned ?? 0`; `$utilization = (expenses / budget) * 100` | Resolver; `$calc->calculateUtilization()` |
| **Budget summaries** | 1992, 2064, 2075, 2111, 2157, 2180–2181, 2191, 2201–2202, 2212, 2224–2225, 2235–2236, 2254–2256 | `$totalBudget = $approvedProjects->sum('amount_sanctioned')`; `$byProjectType[$type]['budget'] += $project->amount_sanctioned`; `remaining += (amount_sanctioned) - expenses` | Resolve each project; use `$financials['opening_balance']` or `['amount_sanctioned']` per context; DerivedCalculationService for remaining |

---

## 4. ExecutorController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **Project list enhance** | 367 | `$projectBudget = $project->amount_sanctioned ?? $project->overall_project_budget` | Resolver per project |
| **enhanceProjectsWithMetadata** (or similar) | 694, 711–722, 830 | Same; `$remainingBudget = $projectBudget - $totalExpenses`; `$budgetUtilization = (expenses / budget) * 100` | Resolver + DerivedCalculationService |
| **Budget timeline** | 890 | `$utilization = (expenses / budget) * 100` | `$calc->calculateUtilization()` |
| **Health / utilization** | 1026, 1060 | Same patterns | Resolver + DerivedCalculationService |

---

## 5. ExportController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **addGeneralInformationSection** (or similar) | 977–987 | Direct DB read: `$project->overall_project_budget`, `amount_forwarded`, `amount_sanctioned`, `opening_balance` | `$financials = app(ProjectFinancialResolver::class)->resolve($project)`; use `$financials['overall_project_budget']`, `['amount_forwarded']`, etc. |
| **addBudgetSection** (or similar) | 2512 | `$this->calculationService->calculateProjectTotal($budgets->map(fn ($b) => $b->this_phase))` for "Amount Sanctioned in Phase X" | For phase-based export: keep per-phase sum via DerivedCalculationService, or use resolver `opening_balance` for single overall display. User said: "Replace sanctioned computation with resolver output. Keep wording unchanged for now." |
| **addBudgetSection** | 2559 | Same `calculateProjectTotal($budgets->map('this_phase'))` | Already uses DerivedCalculationService; acceptable. For consistency with resolver, consider whether phase-level totals should align with resolver’s overall. |

---

## 6. DevelopmentProjectController (Quarterly)

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **create** (or **edit**) | 43 | `$amountSanctionedOverview = $this->calculationService->calculateProjectTotal($budgets->map('this_phase'))` | Use `$financials = app(ProjectFinancialResolver::class)->resolve($project)`; `$financials['amount_sanctioned']` or `['overall_project_budget']` for overview. Or keep phase-specific sum if report is phase-scoped. |

---

## 7. AdminReadOnlyController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **index** (projects list) | 58, 64–65 | `$projectBudget = $project->amount_sanctioned ?? $project->overall_project_budget`; `$project->calculated_remaining = $projectBudget - $totalExpenses`; `$project->budget_utilization = (expenses / budget) * 100` | Resolver per project; DerivedCalculationService for remaining and utilization |

---

## 8. ProjectController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **show** (NPD phases) | 399 | `'amount_sanctioned' => $phase->sum('amount')` | NPD phase structure; if this is project financial data, use resolver. If phase-specific, may need resolver or phase-scoped logic. |
| **show** (JSON/data) | 482 | `'overall_project_budget' => $project->overall_project_budget` | Use resolver `$financials['overall_project_budget']` for consistency |

---

## 9. BudgetExportController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **exportPdf** (view data) | 209–224, 246–248 | `variance_percentage = (remaining_balance / opening_balance) * 100`; `percentage_of_budget = (total_expenses / opening_balance) * 100`; totals from budgetData | Use `DerivedCalculationService::calculateUtilization($totalExpenses, $openingBalance)` for percentage_of_budget; add or use percentage method for variance_percentage. DO NOT modify BudgetValidationService. |

---

## 10. ReportController (Reports/Monthly)

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **create** | 87 | `$amountSanctioned = $project->amount_sanctioned ?? 0.00` | Use resolver `$financials['amount_sanctioned']` for project display |
| **show** | 1196–1198 | `$projectAmountSanctioned`, `$projectOpeningBalance` from project; discrepancy check vs report | Use resolver for project-level values; keep discrepancy logic (comparison only, no formula) |
| **edit** | 1317–1326 | Same; `$amountSanctioned = $project->amount_sanctioned`; discrepancy note | Use resolver for project values |

---

## 11. MonthlyDevelopmentProjectController

| Method | Line(s) | Formula Detected | Suggested Replacement |
|--------|---------|------------------|------------------------|
| **create** (or similar) | 32, 50 | `$amountSanctioned = $project->amount_sanctioned ?? 0` | Use resolver `$financials['amount_sanctioned']` |

---

## 12. Excluded (Not Financial Formula Computation)

| Controller | Line(s) | Reason |
|------------|---------|--------|
| GeneralInfoController | 38, 70, 149, 166 | Validation rules and field names only |
| DevelopmentProjectController | 90–91, 190–191, 269–270, 407–408 | Request validation rules |
| BudgetReconciliationController | 180–181, 195–197 | Request validation / input handling |
| ReportController | 350–351, 351, 377, 412, 575, 596, 1561 | Request validation rules; report-level `amount_sanctioned_overview` |
| BudgetController | 55, 60, 70, 116, 121, 131 | Bounds/validation for `this_phase` |
| ILP/IAH BudgetControllers | 105, 130, 165 | Type-specific budget table sums (cost); not project financial resolver scope |
| CoordinatorController 151–152 | Filter/count on DB columns | Read-only filters; no formula |
| Various `accountDetails->sum()` | Expense aggregation | Allowed; not project financial formula |
| ProvincialController 281, 323, 446–448, etc. | Report `accountDetails` sums | Expense/report aggregation |
| ExportReportController | 665, 692 | Report-level `amount_sanctioned_overview` and account detail; not project financial |
| WomenInDistressController, SkillTrainingController | 85, 68 | Report-specific `amount_in_hand`; report data, not project resolver scope |
| InstitutionalSupportController, DevelopmentLivelihoodController | Validation rules | Request validation only |

---

## 13. Replacement Patterns Summary

### Pattern A: Single-project approval (CoordinatorController, GeneralController)
```
// BEFORE
$overallBudget = $project->overall_project_budget ?? 0;
if ($overallBudget == 0 && $project->budgets...) {
    $overallBudget = $this->calculationService->calculateProjectTotal(...);
}
$amountSanctioned = $overallBudget - ($amountForwarded + $localContribution);
$openingBalance = $amountSanctioned + $amountForwarded + $localContribution;

// AFTER
$financials = app(\App\Domain\Budget\ProjectFinancialResolver::class)->resolve($project);
$amountSanctioned = $financials['amount_sanctioned'];
$openingBalance = $financials['opening_balance'];
// Then: $project->amount_sanctioned = ...; $project->opening_balance = ...; $project->save();
```

### Pattern B: Per-project budget for display/aggregation
```
// BEFORE
$projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0);

// AFTER
$financials = app(\App\Domain\Budget\ProjectFinancialResolver::class)->resolve($project);
$projectBudget = (float) ($financials['opening_balance']); // or amount_sanctioned per context
```

### Pattern C: Remaining balance
```
// BEFORE
$remainingBudget = $projectBudget - $totalExpenses;

// AFTER
$calc = app(\App\Services\Budget\DerivedCalculationService::class);
$remainingBudget = $calc->calculateRemainingBalance($openingBalance, $totalExpenses);
```

### Pattern D: Utilization
```
// BEFORE
$utilization = $budget > 0 ? ($expenses / $budget) * 100 : 0;

// AFTER
$calc = app(\App\Services\Budget\DerivedCalculationService::class);
$utilization = $calc->calculateUtilization($expenses, $budget);
```

### Pattern E: Aggregation across projects
```
// BEFORE
$totalBudget = $projects->sum(fn($p) => $p->amount_sanctioned ?? $p->overall_project_budget ?? 0);

// AFTER
$resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class);
$totalBudget = $projects->sum(fn($p) => (float) ($resolver->resolve($p)['opening_balance'] ?? 0));
```

---

## 14. Risk Notes

1. **Performance**: Resolving each project in large collections adds N resolver calls. Consider caching or batch resolution if needed.
2. **Approval flow**: At approval, resolver returns derived values; these are then persisted. Ensure status/approval handling in resolver is correct.
3. **ExportController**: General info section uses DB fields; switching to resolver aligns exports with Basic Info and Budget Overview.
4. **DevelopmentProjectController**: Quarterly report uses phase-specific `amount_sanctioned_overview`. Resolver returns project-level values; phase-level logic may require a different approach or alignment with resolver.

---

## 15. File Count Summary

| Controller | Methods Affected | Formula Count (approx) |
|------------|------------------|------------------------|
| CoordinatorController | 3+ | ~25 |
| GeneralController | 5+ | ~30 |
| ProvincialController | 4+ | ~15 |
| ExecutorController | 3+ | ~8 |
| ExportController | 2 | 5 |
| DevelopmentProjectController | 1 | 1 |
| AdminReadOnlyController | 1 | 3 |
| ProjectController | 2 | 2 |
| BudgetExportController | 1 | 3 |
| ReportController (Monthly) | 3 | 4 |
| MonthlyDevelopmentProjectController | 1 | 2 |

---

*End of Phase 1 Audit Report. Proceed to Phase 2 (Modification) only after confirmation.*
