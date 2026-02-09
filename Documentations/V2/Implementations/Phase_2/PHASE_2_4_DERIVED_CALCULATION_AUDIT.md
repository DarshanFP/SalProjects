# Phase 2.4 — Derived Calculation Stabilization (Audit Only)

**Date**: 2026-02-09  
**Scope**: Audit all derived numeric calculations in the codebase. No code modifications. No refactoring. No implementation suggestions yet.

---

## 1. Table of All Derived Numeric Formulas

| # | File | Formula | Clamping | Rounding | Same Formula Elsewhere | Risk Level |
|---|------|---------|----------|----------|------------------------|------------|
| 1 | `resources/views/projects/partials/scripts.blade.php` | `thisPhase = rateQuantity * rateMultiplier * rateDuration` | No | JS `.toFixed(2)` | #2 | **Medium** |
| 2 | `resources/views/projects/partials/scripts-edit.blade.php` | `thisPhase = rateQuantity * rateMultiplier * rateDuration` | No | JS `.toFixed(2)` | #1 | **Medium** |
| 3 | `app/Http/Controllers/Projects/BudgetController.php` | Uses `$budget['this_phase']` from request; clamps only | Yes (BoundedNumericService) | No | — | **High** |
| 4 | `app/Models/OldProjects/ProjectBudget.php` | `calculateTotalBudget()` = `rate_quantity * rate_multiplier * rate_duration * rate_increase` | No | No | — | **High** |
| 5 | `app/Http/Controllers/Reports/Monthly/ReportController.php` | `total_amount` = `amount_sanctioned` (if not provided) | No | No | — | Low |
| 6 | `app/Http/Controllers/Reports/Monthly/ReportController.php` | `total_expenses` = `expenses_last_month + expenses_this_month` | No | No | — | Low |
| 7 | `app/Http/Controllers/Reports/Monthly/ReportController.php` | `balance_amount` = `total_amount - total_expenses` | No | No | — | Low |
| 8 | `resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php` | `totalAmount = amountSanctioned`; `totalExpenses = expensesLastMonth + expensesThisMonth`; `balanceAmount = totalAmount - totalExpenses` | No | `.toFixed(2)` | #9 (partial) | Low |
| 9 | `resources/views/reports/monthly/developmentProject/reportform.blade.php` | `totalAmount = amountForwarded + amountSanctioned`; `totalExpenses = expensesLastMonth + expensesThisMonth`; `balanceAmount = totalAmount - totalExpenses` | No | `.toFixed(2)` | #8 (partial) | **Medium** |
| 10 | `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | `totalExpenses = array_sum($amounts)`; `amount_requested = totalExpenses - familyContribution` | No | No | — | **Medium** |
| 11 | `resources/views/projects/partials/Edit/IIES/estimated_expenses.blade.php` | `balanceRequested = totalExpenses - (scholarship + otherSources + contribution)` | No | `.toFixed(2)` | #12 | **High** |
| 12 | `resources/views/projects/partials/Edit/IES/estimated_expenses.blade.php` | `balanceRequested = totalExpenses - (scholarship + otherSources + contribution)` | No | `.toFixed(2)` | #11 | **High** |
| 13 | `resources/views/projects/partials/IIES/estimated_expenses.blade.php` | `totalExpenses = sum(iies_amount rows)`; `balanceRequested = totalExpenses - (scholarship + otherSources + contribution)` | No | `.toFixed(2)` | #14 | **High** |
| 14 | `resources/views/projects/partials/IES/estimated_expenses.blade.php` | `totalExpenses = sum(amounts rows)`; `balanceRequested = totalExpenses - (scholarship + otherSources + contribution)` | No | `.toFixed(2)` | #13 | **High** |
| 15 | `app/Services/Budget/AdminCorrectionService.php` | `sanctioned = overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` | Yes (round(max(0,...))) | `round(..., 2)` | — | Low |
| 16 | `app/Services/Budget/ProjectFundFieldsResolver.php` | `overall = sum(this_phase)` or type-specific sums; `sanctioned = overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` | `max(0, x)` | `round(..., 2)` | — | Low |
| 17 | `app/Services/ReportMonitoringService.php` | `utilisation_percent = (totalExpenses / totalSanctioned) * 100` | No | No | — | Low |
| 18 | `app/Http/Controllers/ProvincialController.php`, `CoordinatorController.php`, `GeneralController.php`, `ExecutorController.php`, `AdminReadOnlyController.php` | `utilization = (expenses / budget) * 100` | No (some cap at 100) | `round(..., 2)` in many | Yes (many files) | Low |
| 19 | `app/Http/Controllers/Projects/ILP/BudgetController.php` | `total_amount = budgets->sum('cost')` (read-only) | No | No | ExportController | Low |
| 20 | `app/Models/OldProjects/ProjectBudget.php` | `calculateRemainingBalance()` = `calculateTotalBudget() - sum(total_expenses)` | No | No | — | **High** (uses broken formula) |

---

## 2. Grouped Duplicated Formulas

### Group A: Budget `this_phase` (quantity × rate × duration)

| Location | Formula | Notes |
|----------|---------|-------|
| `scripts.blade.php` | `rateQuantity * rateMultiplier * rateDuration` | Client-side; no rate_increase |
| `scripts-edit.blade.php` | `rateQuantity * rateMultiplier * rateDuration` | Same as above |
| `BudgetController` | Trusts client; clamps only | **No server-side recalculation** |
| `ProjectBudget::calculateTotalBudget()` | `rate_quantity * rate_multiplier * rate_duration * rate_increase` | **Different formula** (multiplies by rate_increase) |
| `BoundedNumericService` docblock | `rate_quantity * rate_multiplier * rate_duration` | Intended formula |

**Inconsistency**: `ProjectBudget::calculateTotalBudget()` uses `* rate_increase`; JS and docs use `* rate_multiplier * rate_duration` without rate_increase for `this_phase`.

### Group B: Report `total_expenses` / `balance_amount`

| Location | total_expenses | balance_amount |
|----------|----------------|----------------|
| `ReportController.php` | `expenses_last_month + expenses_this_month` | `total_amount - total_expenses` |
| `development_projects.blade.php` | `expensesLastMonth + expensesThisMonth` | `totalAmount - totalExpenses` |
| `reportform.blade.php` (DP) | `expensesLastMonth + expensesThisMonth` | `totalAmount - totalExpenses` |
| `individual_*.blade.php` (edit) | Same | Same |
| `institutional_education.blade.php` (edit) | Same | Same |

**Inconsistency**: `reportform.blade.php` uses `totalAmount = amountForwarded + amountSanctioned`; `development_projects.blade.php` uses `totalAmount = amountSanctioned` only (no amount_forwarded). Different report types, different business rules.

### Group C: IES/IIES `balance_requested`

| Location | Formula |
|----------|---------|
| IES Edit, IIES Edit, IES Create, IIES Create | `totalExpenses - (scholarship + otherSources + contribution)` |

**Note**: Server stores submitted values; no server-side recalculation in IIESExpensesController / IESExpensesController.

### Group D: IAH `amount_requested`

| Location | Formula |
|----------|---------|
| IAHBudgetDetailsController store | `amount_requested = totalExpenses - familyContribution` |

**Note**: `totalExpenses = array_sum($amounts)`; per-row `total_expenses` is stored as same value for all rows (potential bug).

### Group E: Utilization / utilisation_percent

| Location | Formula |
|----------|---------|
| ReportMonitoringService | `(totalExpenses / totalSanctioned) * 100` |
| ProvincialController, CoordinatorController, GeneralController, ExecutorController, AdminReadOnlyController | `(expenses / budget) * 100` |

**Inconsistency**: Some round to 2 decimals; some round to 1; some cap at 100 (`min(100, ...)`).

---

## 3. Highlighted Inconsistencies

### 3.1 Critical: `this_phase` Formula Drift

| Source | Formula | Server-Side Recalc? |
|-------|---------|---------------------|
| JS (scripts.blade.php, scripts-edit.blade.php) | `quantity * multiplier * duration` | No |
| BudgetController | Trusts client value; clamps only | No |
| ProjectBudget::calculateTotalBudget() | `q * m * d * rate_increase` | N/A (model accessor) |
| BoundedNumericService::calculateAndClamp() | Designed for `q * m * d` | Not used by BudgetController |

**Risk**: BudgetController does **not** recalculate `this_phase` server-side. It trusts the client and only clamps. Phase 1A.2 doc says "recalculate server-side" but current implementation does not.

### 3.2 Critical: `next_phase` Formula

- **JS**: `scripts.blade.php` has `totalNextPhase` from summing row values; `next_phase` is a separate input (user-editable or calculated elsewhere).
- **scripts-edit.blade.php**: No `next_phase` calculation found; only `this_phase`.
- **Docs** (CreateProjectQuery_Documentation): `nextPhase = (rateQuantity + rateIncrease) * rateMultiplier * rateDuration`.
- **Server**: BudgetController clamps whatever is submitted; no formula.

**Risk**: `next_phase` formula is undocumented in current JS; drift between create vs edit flows.

### 3.3 Critical: ProjectBudget::calculateTotalBudget() Bug

```php
return $this->rate_quantity * $this->rate_multiplier * $this->rate_duration * $this->rate_increase;
```

- `rate_increase` is typically an additive amount (e.g. +100 for next phase), not a multiplier.
- Canonical `this_phase` = `q * m * d` only.
- Used in `budgets/view.blade.php` and `calculateRemainingBalance()`.

**Risk**: Produces nonsensical values when rate_increase is used as multiplier.

### 3.4 Medium: IAH `total_expenses` Stored Per Row

IAHBudgetDetailsController stores `total_expenses = array_sum($amounts)` in **every** row. Each row gets the same `total_expenses`. Likely intended: per-row amount vs total; current: total duplicated.

### 3.5 Medium: IES/IIES Trust Client for Derived Fields

- `iies_total_expenses` = sum of `iies_amounts` (client-calculated).
- `iies_balance_requested` = `total - (scholarship + other + contribution)` (client-calculated).
- Server stores without recalculation.

**Risk**: Tampered requests can store inconsistent values.

### 3.6 Low: Report `total_amount` Formula Divergence

- DP `reportform.blade.php`: `total_amount = amount_forwarded + amount_sanctioned`.
- Edit flow `development_projects.blade.php`: `total_amount = amount_sanctioned` (no amount_forwarded).
- ReportController fallback: `total_amount = amount_sanctioned` if not provided.

---

## 4. Risk Summary by Controller / Module

| Controller / Module | Derived Formulas | Clamping | Rounding | Server Recalc | Risk |
|--------------------|------------------|----------|----------|---------------|------|
| BudgetController | this_phase, next_phase | Yes | No | No | **High** |
| IAHBudgetDetailsController | total_expenses, amount_requested | No | No | Yes | **Medium** |
| IIESExpensesController | iies_total_expenses, iies_balance_requested | No | No | No | **High** |
| IESExpensesController | total_expenses, balance_requested | No | No | No | **High** |
| IGEBudgetController | total_amount, amount_requested | No | No | No (user input) | Low |
| ILPBudgetController | total_amount (sum of cost) | No | No | Read-only | Low |
| ReportController | total_amount, total_expenses, balance_amount | No | No | Yes (fallback) | Low |
| AdminCorrectionService | sanctioned, opening | Yes | Yes | Yes | Low |
| ProjectFundFieldsResolver | overall, sanctioned, opening | max(0) | Yes | Read-only | Low |
| ProjectBudget (model) | calculateTotalBudget, calculateRemainingBalance | No | No | N/A | **High** |

---

## 5. Safest Pilot Controller for Phase 2.4

**Recommendation: `BudgetController`**

**Reasons:**

1. **Already has BoundedNumericService** — Phase 2.3 adoption complete; clamping in place.
2. **Single, well-defined formula** — `this_phase = rate_quantity * rate_multiplier * rate_duration` is documented and used in JS.
3. **No server-side recalculation today** — Adding `calculateAndClamp()` would align with Phase 1A.2 intent without changing external behavior for valid inputs.
4. **Config-driven bounds** — `project_budgets.this_phase` and `next_phase` already in `decimal_bounds.php`.
5. **Controlled scope** — One controller, one model, one form flow. No dependency on IES/IIES/IAH/IGE/ILP.
6. **Existing tests** — `Phase1_Budget_ValidationTest` and related coverage.

**Avoid as first pilot:**

- **IIESExpensesController / IESExpensesController** — Multiple derived fields; frontend calculates total_expenses and balance_requested; no current bounds config.
- **IAHBudgetDetailsController** — Per-row duplication of total_expenses; needs design fix before stabilization.
- **ReportController** — Report flows are complex; multiple report types with different formulas.

---

## 6. Appendix: Quick Reference

### BoundedNumericService (Phase 2.3)

- `clamp(value, max, min)`: Used by BudgetController for this_phase, next_phase.
- `calculateAndClamp(formula, inputs, max)`: **Not used** by any controller; intended for derived fields.

### Config: `config/decimal_bounds.php`

- `project_budgets`: this_phase, next_phase, rate_quantity, rate_multiplier, rate_duration, rate_increase, amount_sanctioned.
- `project_IIES_expenses`: iies_total_expenses, iies_balance_requested, etc.
- `default`: min 0, max 99999999.99.

---

*Audit complete. No code modified.*
