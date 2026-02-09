# Phase 2.4 — Derived Calculation Full Architectural Audit

**Date**: 2026-02-09  
**Scope**: Map every derived calculation related to budgets across the entire codebase.  
**Mode**: READ-ONLY forensic mapping. No code modifications. No refactoring. No fixes proposed.

---

## 1. Executive Summary

### Overview

The codebase contains **multiple independent implementations** of budget-related derived calculations. Formulas for `this_phase`, `next_phase`, `overall_project_budget`, `amount_sanctioned`, `balance_requested`, and related totals appear in:

- **JavaScript** (scripts.blade.php, scripts-edit.blade.php)
- **PHP controllers** (BudgetController, IAHBudgetDetailsController, ReportController)
- **PHP models** (ProjectBudget::calculateTotalBudget, calculateRemainingBalance)
- **PHP services** (BudgetCalculationService, ProjectFundFieldsResolver, AdminCorrectionService)
- **Report/Blade views** (development_projects.blade.php, reportform.blade.php, IES/IIES estimated_expenses)

### Critical Findings

| Finding | Severity |
|--------|----------|
| `this_phase` formula drift: JS uses `q×m×d`; ProjectBudget model uses `q×m×d×rate_increase`; Edit form has no rate_increase input (always 0 → model yields 0) | **High** |
| `next_phase` hardcoded to `null` in BudgetController; Edit form has no next_phase input | **High** |
| Backend trusts client for `this_phase`; no server-side recalculation | **High** |
| IES/IIES trust client for `total_expenses` and `balance_requested`; no server recalculation | **High** |
| Utilization formula duplicated in 5+ controllers with inconsistent rounding (1 vs 2 decimals) | **Medium** |
| `sum(this_phase)` computed in 6+ places with different phase filters | **Medium** |
| Report `total_amount` formula differs: `amount_sanctioned` vs `amount_forwarded + amount_sanctioned` | **Medium** |

### Appetite Statement

This audit defines Phase 2.4 architecture. No implementation is recommended yet. The output is forensic documentation only.

---

## 2. Formula Inventory Table

| # | File | Class | Method | Formula | Line | Backend/JS | Persistence/Display |
|---|------|-------|--------|---------|------|------------|---------------------|
| 1 | scripts.blade.php | (inline) | calculateBudgetRowTotals | `thisPhase = rateQuantity * rateMultiplier * rateDuration` | 84 | JS | Persistence (writes to form input) |
| 2 | scripts-edit.blade.php | (inline) | calculateBudgetRowTotals | `thisPhase = rateQuantity * rateMultiplier * rateDuration` | 980 | JS | Persistence |
| 3 | scripts.blade.php | (inline) | calculateBudgetTotals | `totalThisPhase += row[this_phase]; totalNextPhase += row[next_phase]` | 115–116 | JS | Display only |
| 4 | scripts-edit.blade.php | (inline) | calculateBudgetTotals | `totalThisPhase += row[this_phase]` (no next_phase) | 1008 | JS | Display only |
| 5 | scripts.blade.php | (inline) | calculateTotalAmountSanctioned | `totalAmount = sum(thisPhaseValue)`; `overall_project_budget = totalAmount` | 141, 184–190 | JS | Persistence |
| 6 | scripts-edit.blade.php | (inline) | calculateTotalAmountSanctioned | Same | 1041, 1058–1065 | JS | Persistence |
| 7 | scripts-edit.blade.php | (inline) | calculateBudgetFields | `amountSanctioned = overallBudget - (amountForwarded + localContribution)`; `openingBalance = amountSanctioned + combined` | 1112–1115 | JS | Display only |
| 8 | BudgetController.php | BudgetController | store, update | `thisPhase = clamp(budget['this_phase'] ?? 0)`, `next_phase = null` | 60, 71, 121, 132 | Backend | Persistence |
| 9 | ProjectBudget.php | ProjectBudget | calculateTotalBudget | `rate_quantity * rate_multiplier * rate_duration * rate_increase` | 79 | Backend | Display (budgets/view.blade.php) |
| 9a | Edit/budget.blade.php | — | — | No rate_increase input; JS uses q×m×d only | — | — | Edit form never submits rate_increase; BudgetController stores `?? 0` |
| 10 | ProjectBudget.php | ProjectBudget | calculateRemainingBalance | `calculateTotalBudget() - sum(dpAccountDetails.total_expenses)` | 82–85 | Backend | Display |
| 11 | BudgetCalculationService.php | BudgetCalculationService | calculateContributionPerRow | `contribution / totalRows` | 81–84 | Backend | Report rows (read-only) |
| 12 | BudgetCalculationService.php | BudgetCalculationService | calculateTotalContribution | `array_sum(sources)` | 92–95 | Backend | Report rows |
| 13 | BudgetCalculationService.php | BudgetCalculationService | calculateAmountSanctioned | `max(0, originalAmount - contributionPerRow)` | 104–106 | Backend | Report rows |
| 14 | BudgetCalculationService.php | BudgetCalculationService | preventNegativeAmount | `max(0, amount)` | 115–117 | Backend | Helper |
| 15 | SingleSourceContributionStrategy.php | SingleSourceContributionStrategy | getBudgets | `amount_sanctioned = max(0, amount - (contribution/totalRows))` | 50, 62 | Backend | Report rows |
| 16 | MultipleSourceContributionStrategy.php | MultipleSourceContributionStrategy | getBudgets | `amount_sanctioned = max(0, amount - (sum(sources)/totalRows))` | 65–67, 81 | Backend | Report rows |
| 17 | ProjectFundFieldsResolver.php | ProjectFundFieldsResolver | resolveDevelopment | `overall = sum(phaseBudgets.this_phase)`; `sanctioned = overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` | 93–99, 98–99 | Backend | Read-only resolution |
| 18 | ProjectFundFieldsResolver.php | ProjectFundFieldsResolver | normalize | `round(max(0, v), 2)` | 324 | Backend | Normalization |
| 19 | AdminCorrectionService.php | AdminCorrectionService | normalizeManualValues | `sanctioned = round(max(0, overall - (forwarded + local)), 2)`; `opening = round(sanctioned + forwarded + local, 2)` | 159–163 | Backend | Persistence |
| 20 | IAHBudgetDetailsController.php | IAHBudgetDetailsController | store | `totalExpenses = array_sum($amounts)`; `amount_requested = totalExpenses - familyContribution` | 59, 71 | Backend | Persistence |
| 21 | ReportController.php | ReportController | (account detail loop) | `total_expenses = expenses_last_month + expenses_this_month`; `balance_amount = total_amount - total_expenses` | 584–588 | Backend | Persistence |
| 22 | development_projects.blade.php | (inline) | calculateRowTotals | `totalAmount = amountSanctioned`; `totalExpenses = expensesLastMonth + expensesThisMonth`; `balanceAmount = totalAmount - totalExpenses` | 249–251 | JS | Persistence |
| 23 | reportform.blade.php (DP) | (inline) | calculateRowTotals | `totalAmount = amountForwarded + amountSanctioned`; `totalExpenses = ...`; `balanceAmount = totalAmount - totalExpenses` | 544–547 | JS | Persistence |
| 24 | IES/IIES estimated_expenses.blade.php | (inline) | calculateBalanceRequested | `balanceRequested = totalExpenses - (scholarship + otherSources + contribution)` | various | JS | Persistence |
| 25 | ReportMonitoringService.php | ReportMonitoringService | getBudgetUtilisationSummary | `utilisation_percent = (totalExpenses / totalSanctioned) * 100` | 390 | Backend | Display |
| 26 | ProvincialController, CoordinatorController, etc. | (various) | (various) | `utilization = (expenses / budget) * 100` | multiple | Backend | Display |

---

## 3. JS vs Backend Comparison Table

| Field | JS Location | JS Formula | Backend Location | Backend Formula | Authoritative? | Backend Trusts JS? |
|-------|-------------|------------|------------------|-----------------|----------------|-------------------|
| this_phase | scripts.blade.php, scripts-edit.blade.php | `rateQuantity * rateMultiplier * rateDuration` | BudgetController | Uses `$budget['this_phase']`; clamps only | JS computes; backend trusts | **Yes** |
| next_phase | scripts.blade.php (calculateBudgetTotals only) | Sums row values; no input in Edit form | BudgetController | Hardcoded `null` | Backend overwrites | N/A |
| overall_project_budget | scripts-edit.blade.php | `sum(thisPhaseValue)` | GeneralInfoController | From request | JS computes; backend trusts | **Yes** |
| total_this_phase | scripts.blade.php, scripts-edit.blade.php | `sum(row[this_phase])` | — | Not in backend | JS only | N/A |
| amount_sanctioned (report row) | development_projects | `amountSanctioned` (from input) | ReportController | From request or `amount_sanctioned` | Mixed | **Yes** |
| total_expenses (report) | development_projects, reportform | `expensesLastMonth + expensesThisMonth` | ReportController | Same | Consistent | **Yes** |
| balance_amount (report) | development_projects, reportform | `totalAmount - totalExpenses` | ReportController | Same | Consistent | **Yes** |
| iies_total_expenses | IIES estimated_expenses | `sum(iies_amounts)` | IIESExpensesController | From request | JS computes; backend trusts | **Yes** |
| iies_balance_requested | IIES estimated_expenses | `total - (scholarship + other + contribution)` | IIESExpensesController | From request | JS computes; backend trusts | **Yes** |
| amount_requested (IAH) | — | — | IAHBudgetDetailsController | `array_sum($amounts) - familyContribution` | Backend only | N/A |

### Summary

- **Backend trusts JS** for: this_phase, overall_project_budget, iies_total_expenses, iies_balance_requested, total_expenses (IES), balance_requested (IES).
- **Backend recalculates** for: IAH amount_requested, Report total_expenses/balance_amount (fallback).
- **JS is authoritative** for budget row totals; backend does not recompute.

---

## 4. Duplication Matrix

| Formula | Locations | Persistence | Validation | UI | Report | Redundant? | Conflicting? |
|---------|-----------|-------------|------------|-----|--------|------------|--------------|
| this_phase = q×m×d | scripts.blade.php, scripts-edit.blade.php | Via form | — | Yes | — | Yes (2 scripts) | No |
| this_phase = q×m×d×rate_increase | ProjectBudget::calculateTotalBudget | — | — | budgets/view | — | — | **Yes** (drift vs JS) |
| sum(this_phase) | scripts (total_this_phase), ProjectFundFieldsResolver, CoordinatorController, GeneralController, ExportController | — | — | Yes | Yes | Yes | No (phase filter differs) |
| amount_sanctioned = max(0, amount - (contribution/rows)) | SingleSourceContributionStrategy, MultipleSourceContributionStrategy | No | — | — | Yes | Yes (2 strategies) | No |
| sanctioned = overall - (forwarded + local) | ProjectFundFieldsResolver, AdminCorrectionService, scripts (calculateBudgetFields) | AdminCorrection | — | Yes | — | Yes | No |
| opening = sanctioned + forwarded + local | ProjectFundFieldsResolver, AdminCorrectionService | AdminCorrection | — | Yes | — | Yes | No |
| utilization = (expenses/budget)*100 | ProvincialController, CoordinatorController, GeneralController, ExecutorController, AdminReadOnlyController, ReportMonitoringService | No | — | Yes | Yes | Yes (6+ places) | **Yes** (rounding 1 vs 2) |
| total_expenses = last_month + this_month | ReportController, development_projects.blade.php, reportform.blade.php | Yes | — | Yes | Yes | Yes | No |
| balance_amount = total_amount - total_expenses | ReportController, development_projects.blade.php, reportform.blade.php | Yes | — | Yes | Yes | Yes | No |
| total_amount = ? | development_projects: amountSanctioned; reportform: amountForwarded + amountSanctioned | Yes | — | Yes | Yes | — | **Yes** (different formulas) |

---

## 5. Drift Risk Classification

| Risk | Description | Severity |
|------|-------------|----------|
| **this_phase formula drift** | JS: `q×m×d`. Model: `q×m×d×rate_increase`. BoundedNumericService doc: `q×m×d`. Model produces wrong values when rate_increase is additive. | **High** |
| **next_phase lifecycle** | Hardcoded `null` in BudgetController. Edit form has no next_phase input. scripts-edit has no next_phase. scripts.blade.php references it but only for multi-phase (create flow). | **High** |
| **Client trust** | BudgetController, IIES, IES trust client for derived totals. No server-side recalculation. | **High** |
| **Utilization rounding** | round(..., 2) vs round(..., 1) across controllers. | **Medium** |
| **Report total_amount** | development_projects uses `amountSanctioned`; reportform uses `amountForwarded + amountSanctioned`. | **Medium** |
| **Phase inference** | BudgetController uses `project->current_phase` for phase. ProjectFundFieldsResolver uses `current_phase` when overall=0. No array-index phase inference in current BudgetController. | **Low** |
| **IAH total_expenses per row** | Same total duplicated in every row. | **Medium** |

---

## 6. next_phase Lifecycle Report

### Where next_phase is calculated

| Location | Formula | Notes |
|----------|---------|-------|
| scripts.blade.php | `totalNextPhase += parseFloat(row.querySelector('[name$="[next_phase]"]').value) || 0` | Sums row values; requires `[next_phase]` input. Create form may have it; Edit form does not. |
| — | None in backend | BudgetController explicitly sets `next_phase => null`. |

### Where next_phase is stored

| Location | Value | Notes |
|----------|-------|-------|
| BudgetController::store | `null` | Line 71 |
| BudgetController::update | `null` | Line 132 |
| project_budgets table | nullable decimal(10,2) | Migration; no default |

### Where next_phase is ignored

| Location | Notes |
|----------|-------|
| Edit/budget.blade.php | No next_phase input. Form never submits it. |
| scripts-edit.blade.php | calculateBudgetTotals() has no totalNextPhase; addBudgetRow() creates no next_phase input. |
| BudgetController | Ignores any request value; always writes `null`. |

### Where next_phase is displayed

| Location | Notes |
|----------|-------|
| OLdshow/budget.blade.php | `number_format($budget->next_phase, 2)` |
| not working show/budget.blade.php | Same |
| ExportController | `$budgets->sum('next_phase')` in exports |
| Create form (scripts.blade.php) | total_next_phase footer; requires row inputs |

### Logic depending on next_phase being non-null

| Location | Dependency |
|----------|------------|
| ExportController | Sums next_phase for export; will sum nulls as 0. |
| OLdshow/budget.blade.php | Displays; null renders as empty/0. |
| BudgetCalculationService / DirectMappingStrategy | Uses `this_phase` as amount; next_phase not used. |

### Classification

| Classification | Rationale |
|----------------|-----------|
| **Persisted but not used** | Stored as `null` by BudgetController. Edit form has no input. Display views show it but it is always null. |
| **Transitional field** | Historically may have been used for multi-phase planning; now deprecated in single-phase flow. |
| **Dead field** | Effectively dead: always null, no form input, no calculation. |

---

## 7. Rounding Consistency Report

### Where round() is used

| File | Location | Formula | Precision |
|------|----------|---------|-----------|
| AdminCorrectionService | normalizeManualValues | `round(max(0, ...), 2)` | 2 |
| ProjectFundFieldsResolver | normalize | `round(max(0, v), 2)` | 2 |
| CoordinatorController | budget_utilization | `round($budgetUtilization, 2)` | 2 |
| CoordinatorController | approval_rate | `round($approvalRate, 2)` | 2 |
| CoordinatorController | utilization | `round(($provinceExpenses / $provinceBudget) * 100, 2)` | 2 |
| CoordinatorController | avgProcessingTime | `round($totalDays / $count, 1)` | 1 |
| ExecutorController | utilization_percent | `round($budgetUtilization, 2)` | 2 |
| ExecutorController | approval_rate, budget_utilization | `round(..., 1)` | 1 |
| GeneralController | calculated_utilization | `round($budgetUtilization, 2)` | 2 |
| ProvincialController | avg_projects_per_member | `round(..., 1)` | 1 |
| AdminReadOnlyController | budget_utilization | `round($totalExpenses / $projectBudget * 100, 2)` | 2 |
| ReportAnalysisService | utilization_percentage | `round($utilization, 2)` | 2 |

### Where number_format is used

| File | Purpose |
|------|---------|
| NumericBoundsRule | Error message |
| UpdateProjectRequest, StoreProjectRequest | Validation message |
| CoordinatorController, GeneralController | Approval error messages |
| BudgetExport, ExportController | Export cells |
| Blade views (OLdshow, not working show, Show) | Display |

### Where toFixed is used (JS)

| File | Formula | Precision |
|------|---------|----------|
| scripts.blade.php, scripts-edit.blade.php | `thisPhase.toFixed(2)`, `totalAmount.toFixed(2)`, etc. | 2 |

### Inconsistencies

| Inconsistency | Locations |
|---------------|-----------|
| Utilization: 2 decimals vs 1 | CoordinatorController, GeneralController use 2; ExecutorController uses 1 for approval_rate, budget_utilization |
| Avg/count: 1 decimal | avgProcessingTime, avg_projects_per_member use 1 |
| BoundedNumericService | Clamps only; no rounding. Controller persists raw float after clamp. |
| ReportController | No rounding on total_expenses, balance_amount before persistence. |

---

## 8. Architectural Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|-------------|
| **Formula drift** | High | High | Centralize derived formulas in single service. |
| **Client trust** | High | High | Recalculate server-side; ignore client-derived totals. |
| **next_phase dead field** | Certain | Low | Document or remove; avoid confusion. |
| **Phase index vs current_phase** | Low | Medium | BudgetController uses current_phase; no array-index inference. Single source of phase. |
| **Rounding drift** | Medium | Low | Standardize rounding (e.g. 2 decimals for all percentage). |
| **Report total_amount divergence** | Medium | Medium | Align formula across report types. |
| **IAH total_expenses per row** | Certain | Low | Design: per-row vs total; clarify intent. |

---

## 9. Recommended Refactor Order (High-Level Only)

| Order | Scope | Rationale |
|-------|-------|-----------|
| 1 | **BudgetController + this_phase** | Central formula; BoundedNumericService exists; single-phase flow. Server-side recalculation first. |
| 2 | **ProjectBudget model** | Fix calculateTotalBudget to match canonical (remove rate_increase multiplier or clarify). |
| 3 | **next_phase** | Decide: remove, deprecate, or implement. Document decision. |
| 4 | **IIES/IES** | Recalculate total_expenses and balance_requested server-side. |
| 5 | **Utilization** | Centralize formula; standardize rounding. |
| 6 | **Report total_amount** | Align development_projects vs reportform. |
| 7 | **IAH total_expenses** | Clarify per-row vs total; fix duplication if needed. |
| 8 | **Aggregation** | Reduce duplication of sum(this_phase), sum(total_expenses) across controllers. |

---

## 10. Section A — Core Budget Formulas (Summary)

| Pattern | Occurrences |
|---------|-------------|
| `rate_quantity * rate_multiplier * rate_duration` | scripts.blade.php, scripts-edit.blade.php, BoundedNumericService doc |
| `rate_quantity * rate_multiplier * rate_duration * rate_increase` | ProjectBudget::calculateTotalBudget |
| `this_phase` | BudgetController (clamp), form inputs, sum(this_phase) in 6+ places |
| `next_phase` | BudgetController (null), OLdshow, ExportController |
| `sum(this_phase)` | ProjectFundFieldsResolver, CoordinatorController, GeneralController, ExportController |
| `overall_project_budget` | From JS sum; GeneralInfoController; ProjectFundFieldsResolver |
| `amount_sanctioned` | max(0, amount - contributionPerRow) in strategies; overall - (forwarded + local) in resolver |
| `preventNegativeAmount` / `max(0, x)` | BudgetCalculationService, AdminCorrectionService, ProjectFundFieldsResolver |
| `array_sum` | BudgetCalculationService::calculateTotalContribution, IAHBudgetDetailsController |
| `->sum(` | 50+ occurrences across controllers, models, views |
| `round(`, `number_format`, `toFixed` | See Section 7. |
| `bcadd`, `bcmul`, `bcdiv` | Not used in app code; only in vendor (mpdf, brick/math, htmlpurifier). |

---

## 11. Section B — JavaScript Budget Calculations (Summary)

| Script | addBudgetRow | calculateBudgetRowTotals | calculateBudgetTotals | calculateTotalAmountSanctioned | calculateBudgetFields |
|--------|--------------|--------------------------|----------------------|-------------------------------|----------------------|
| scripts.blade.php | Has next_phase in row template | thisPhase = q×m×d; toFixed(2) | totalThisPhase, totalNextPhase | sum(thisPhase); overall_project_budget = total | — |
| scripts-edit.blade.php | No next_phase | thisPhase = q×m×d; toFixed(2) | totalThisPhase only | sum(thisPhase); overall_project_budget = total | amountSanctioned = overall - (forwarded + local); opening = sanctioned + combined |

**Defaulting**: `parseFloat(x) || 0`, `|| 1` for multiplier/duration.  
**Missing-field fallback**: `|| 0` yields 0.  
**next_phase**: scripts.blade.php references it but Edit form has no input; scripts-edit has no next_phase logic.  
**JS authoritative?**: Yes for this_phase (computes and writes to form). Backend trusts JS values.

---

## 12. Section C — Controller-Level Derived Logic (Summary)

| Controller/Service | Derived Before Save | Derived After Save | Delete-Then-Recreate | Phase-Dependent |
|--------------------|---------------------|--------------------|----------------------|-----------------|
| BudgetController | Clamp only; no formula | — | Yes (delete by project_id + current_phase) | Yes (current_phase) |
| IAHBudgetDetailsController | totalExpenses, amount_requested | — | Yes | No |
| IIESExpensesController | — | — | Yes | No |
| IESExpensesController | — | — | Yes | No |
| ReportController | total_expenses, balance_amount (fallback) | — | No | No |
| BudgetCalculationService | — | amount_sanctioned (report rows) | — | No |
| AdminCorrectionService | sanctioned, opening | — | — | No |
| ProjectFundFieldsResolver | — | overall, sanctioned, opening (read) | — | Yes (current_phase) |

---

## 13. Section D — Aggregation Duplication (Summary)

| Aggregation | Persistence | Validation | UI | Report | Redundant | Conflicting |
|-------------|-------------|------------|-----|--------|-----------|-------------|
| sum(this_phase) | — | — | total_this_phase | ExportController, ProjectFundFieldsResolver | Yes | Phase filter differs |
| sum(total_expenses) | — | — | — | ProvincialController, CoordinatorController, GeneralController, ExecutorController, ReportMonitoringService | Yes | No |
| sum(cost) | — | — | — | ILPBudgetController show, ExportController | Yes | No |
| sum(amount) | IAHBudgetDetails | — | — | — | — | No |
| overall_project_budget | GeneralInfoController | Yes | Yes | — | — | No |

---

## 14. Section E — Rounding & Precision Drift (Summary)

| Location | float | round | number_format | Precision |
|----------|-------|-------|---------------|-----------|
| BoundedNumericService::clamp | Yes | No | No | Raw |
| BudgetController | `(float)($budget['this_phase'] ?? 0)` | No | No | Raw |
| AdminCorrectionService | Yes | Yes | No | 2 |
| ProjectFundFieldsResolver | Yes | Yes | No | 2 |
| Utilization (various) | Yes | Yes | No | 1 or 2 |
| JS | parseFloat | toFixed(2) | — | 2 |

**Flag**: BoundedNumericService clamps but does not round. Controller persists raw float. DB column is decimal(10,2); MySQL may round on insert.

---

## 15. Section F — next_phase Lifecycle (Summary)

- **Calculated**: Only in scripts.blade.php (sum of row values) when `[next_phase]` input exists. Edit form has none.
- **Stored**: Always `null` in BudgetController.
- **Ignored**: Edit form, scripts-edit.blade.php.
- **Displayed**: OLdshow, not working show, ExportController.
- **Logic dependency**: None. Export sums nulls as 0.
- **Classification**: Persisted but not used; effectively dead.

---

## 16. Section G — Phase-Scoped Calculations (Summary)

| Location | Uses current_phase? | Inferred from array index? | Multi-phase assumption? |
|----------|--------------------|----------------------------|-------------------------|
| BudgetController | Yes (`phase => (int)($project->current_phase ?? 1)`) | No | No; single phase (phases[0]) |
| ProjectFundFieldsResolver | Yes (`where('phase', $currentPhase)`) | No | No |
| BudgetCalculationService / DirectMappingStrategy | No (phase from config) | No | Phase-based for DP types |
| Edit form | — | No | No; always phases[0] |

**Violations**: None found. Phase is derived from `project->current_phase`, not array index.

---

Audit complete. No production code was modified.
