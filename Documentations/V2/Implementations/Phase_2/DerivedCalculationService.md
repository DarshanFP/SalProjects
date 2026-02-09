---

# Precondition for Phase 2.4

Before implementing DerivedCalculationService, the following system guarantees are now frozen:

- Single active phase = `$project->current_phase`
- Phase is never calculated from request array index
- Delete and recreate operations are scoped to current phase only
- next_phase is nullable and not auto-zeroed
- Budget rows are phase-scoped
- `this_phase` aggregation is authoritative and regression-protected
- Numeric bounds are centralized via `BoundedNumericService`
- Decimal limits are defined in `config/decimal_bounds.php`

Phase 2.4 must assume this stabilized baseline and must not reintroduce:

- Multi-phase editing via request indexing
- Implicit next_phase fallback to 0
- Hardcoded decimal bounds
- Phase mutation during edit

---

# Phase 2.4 — DerivedCalculationService (Design)

**Date**: 2026-02-09  
**Status**: Design Only — No implementation. No code modifications.

---

## Purpose

### Why Derived Calculations Must Be Centralized

1. **Client trust risk**: Controllers that accept client-calculated totals (e.g. `this_phase`, `total_expenses`, `balance_requested`) without server-side recalculation are vulnerable to:
    - Tampered form submissions
    - JavaScript errors or disabled scripts producing wrong values
    - Precision drift between client and server

2. **Formula drift**: The same logical field (e.g. `this_phase`) is computed in multiple places:
    - JavaScript (scripts.blade.php, scripts-edit.blade.php)
    - Model (ProjectBudget::calculateTotalBudget)
    - Controllers (BudgetController trusts client; IAHBudgetDetailsController recalculates)
    - Strategies (BudgetCalculationService, SingleSourceContributionStrategy, MultipleSourceContributionStrategy)
    - Services (ProjectFundFieldsResolver, AdminCorrectionService)

3. **Inconsistency consequences**: When formulas diverge:
    - Reports show different totals than project views
    - Budget sync produces incorrect `amount_sanctioned`
    - Model accessors (e.g. `calculateTotalBudget`) return nonsensical values

### Drift Risk Between Controllers and Models

- **BudgetController**: Uses `$budget['this_phase']` from request; clamps only. No server-side formula.
- **ProjectBudget model**: `calculateTotalBudget()` uses `rate_quantity * rate_multiplier * rate_duration * rate_increase` — multiplies by `rate_increase`, which differs from the JS formula that uses only `q * m * d`.
- **IES/IIES controllers**: Store `total_expenses` and `balance_requested` directly from request; no recalculation.
- **IAHBudgetDetailsController**: Recalculates `total_expenses` and `amount_requested` server-side; but duplicates same values per row.

---

## Identified Derived Fields

### this_phase

| Attribute                  | Value                                                                                                                                |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Module                     | project_budgets / Development Projects                                                                                               |
| Current Location           | `app/Http/Controllers/Projects/BudgetController.php` store(), update()                                                               |
| Current Formula            | Uses `$budget['this_phase']` from request. No formula. Clamp only: `$bounded->clamp((float)($budget['this_phase'] ?? 0), $maxPhase)` |
| Client Trusted?            | Yes                                                                                                                                  |
| Clamped?                   | Yes (BoundedNumericService)                                                                                                          |
| Risk Level                 | High                                                                                                                                 |
| Proposed Canonical Formula | `rate_quantity * rate_multiplier * rate_duration`                                                                                    |
| Notes                      | JS (scripts.blade.php, scripts-edit.blade.php) uses `rateQuantity * rateMultiplier * rateDuration`. Controller does not recalculate. |

---

### next_phase

| Attribute                  | Value                                                                                                                                                                        |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | project_budgets / Development Projects                                                                                                                                       |
| Current Location           | `app/Http/Controllers/Projects/BudgetController.php` store(), update()                                                                                                       |
| Current Formula            | Uses `$budget['next_phase']` from request. No formula. Clamp only.                                                                                                           |
| Client Trusted?            | Yes                                                                                                                                                                          |
| Clamped?                   | Yes (BoundedNumericService)                                                                                                                                                  |
| Risk Level                 | High                                                                                                                                                                         |
| Proposed Canonical Formula | ⚠ Formula Drift — Requires Clarification. Documentation suggests `(rate_quantity + rate_increase) * rate_multiplier * rate_duration`. Not found in current JS or controller. |
| Notes                      | scripts-edit.blade.php has no next_phase calculation. scripts.blade.php sums row values; source of per-row next_phase unclear.                                               |

---

### calculateTotalBudget (ProjectBudget model)

| Attribute                  | Value                                                                                                                                                     |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | project_budgets / ProjectBudget model                                                                                                                     |
| Current Location           | `app/Models/OldProjects/ProjectBudget.php` calculateTotalBudget()                                                                                         |
| Current Formula            | `rate_quantity * rate_multiplier * rate_duration * rate_increase`                                                                                         |
| Client Trusted?            | N/A (model accessor)                                                                                                                                      |
| Clamped?                   | No                                                                                                                                                        |
| Risk Level                 | High                                                                                                                                                      |
| Proposed Canonical Formula | ⚠ Formula Drift — Requires Clarification. If `rate_increase` is additive (e.g. +100), multiplication is wrong. Canonical `this_phase` = `q * m * d` only. |
| Notes                      | Used by `calculateRemainingBalance()` and `budgets/view.blade.php`. Differs from JS and BudgetController flow.                                            |

---

### calculateRemainingBalance (ProjectBudget model)

| Attribute                  | Value                                                                            |
| -------------------------- | -------------------------------------------------------------------------------- |
| Module                     | project_budgets / ProjectBudget model                                            |
| Current Location           | `app/Models/OldProjects/ProjectBudget.php` calculateRemainingBalance()           |
| Current Formula            | `calculateTotalBudget() - sum(dpAccountDetails.total_expenses)`                  |
| Client Trusted?            | N/A                                                                              |
| Clamped?                   | No                                                                               |
| Risk Level                 | High                                                                             |
| Proposed Canonical Formula | `this_phase - sum(dpAccountDetails.total_expenses)` (if this_phase is canonical) |
| Notes                      | Inherits formula drift from calculateTotalBudget().                              |

---

### amount_sanctioned (SingleSourceContributionStrategy)

| Attribute                  | Value                                                                                                                           |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | ILP, IAH / BudgetCalculationService                                                                                             |
| Current Location           | `app/Services/Budget/Strategies/SingleSourceContributionStrategy.php` getBudgets()                                              |
| Current Formula            | `contributionPerRow = contribution / totalRows`; `amount_sanctioned = max(0, original_amount - contributionPerRow)`             |
| Client Trusted?            | No (recalculated from stored amounts)                                                                                           |
| Clamped?                   | Yes (via preventNegativeAmount / max(0, ...))                                                                                   |
| Risk Level                 | Low                                                                                                                             |
| Proposed Canonical Formula | `max(0, amount - (contribution / totalRows))`                                                                                   |
| Notes                      | Uses BudgetCalculationService::calculateContributionPerRow, calculateAmountSanctioned. Read-only for reports; does not persist. |

---

### amount_sanctioned (MultipleSourceContributionStrategy)

| Attribute                  | Value                                                                                                                                                                       |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | IIES, IES / BudgetCalculationService                                                                                                                                        |
| Current Location           | `app/Services/Budget/Strategies/MultipleSourceContributionStrategy.php` getBudgets()                                                                                        |
| Current Formula            | `totalContribution = sum(contribution_sources)`; `contributionPerRow = totalContribution / totalRows`; `amount_sanctioned = max(0, original_amount - contributionPerRow)`   |
| Client Trusted?            | No (recalculated from stored amounts)                                                                                                                                       |
| Clamped?                   | Yes (via preventNegativeAmount)                                                                                                                                             |
| Risk Level                 | Low                                                                                                                                                                         |
| Proposed Canonical Formula | `max(0, amount - (sum(contribution_sources) / totalRows))`                                                                                                                  |
| Notes                      | Config defines contribution_sources per type (e.g. IIES: iies_expected_scholarship_govt, iies_support_other_sources, iies_beneficiary_contribution). Read-only for reports. |

---

### total_expenses (IAH)

| Attribute                  | Value                                                                      |
| -------------------------- | -------------------------------------------------------------------------- |
| Module                     | project_IAH_budget_details                                                 |
| Current Location           | `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` store() |
| Current Formula            | `array_sum($amounts)`                                                      |
| Client Trusted?            | No (recalculated server-side)                                              |
| Clamped?                   | No                                                                         |
| Risk Level                 | Medium                                                                     |
| Proposed Canonical Formula | `sum(amount)` for all rows                                                 |
| Notes                      | Same value stored in every row (total_expenses duplicated per row).        |

---

### amount_requested (IAH)

| Attribute                  | Value                                                                                                                                              |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | project_IAH_budget_details                                                                                                                         |
| Current Location           | `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` store(), edit()                                                                 |
| Current Formula            | `totalExpenses - familyContribution`                                                                                                               |
| Client Trusted?            | No (recalculated server-side)                                                                                                                      |
| Clamped?                   | No                                                                                                                                                 |
| Risk Level                 | Medium                                                                                                                                             |
| Proposed Canonical Formula | `sum(amount) - family_contribution`                                                                                                                |
| Notes                      | store: `$totalExpenses - $familyContribution`. edit: `$budgetDetails->sum('amount') - $budgetDetails->first()->family_contribution`. Same per row. |

---

### iies_total_expenses

| Attribute                  | Value                                                                   |
| -------------------------- | ----------------------------------------------------------------------- |
| Module                     | project_IIES_expenses                                                   |
| Current Location           | `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` store() |
| Current Formula            | None. Uses `$validated['iies_total_expenses']` from request.            |
| Client Trusted?            | Yes                                                                     |
| Clamped?                   | No (validation only)                                                    |
| Risk Level                 | High                                                                    |
| Proposed Canonical Formula | `sum(iies_amount)` over expenseDetails                                  |
| Notes                      | Client JS calculates; server stores without recalculation.              |

---

### iies_balance_requested

| Attribute                  | Value                                                                                                                     |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Module                     | project_IIES_expenses                                                                                                     |
| Current Location           | `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` store()                                                   |
| Current Formula            | None. Uses `$validated['iies_balance_requested']` from request.                                                           |
| Client Trusted?            | Yes                                                                                                                       |
| Clamped?                   | No                                                                                                                        |
| Risk Level                 | High                                                                                                                      |
| Proposed Canonical Formula | `iies_total_expenses - (iies_expected_scholarship_govt + iies_support_other_sources + iies_beneficiary_contribution)`     |
| Notes                      | Client JS: `balanceRequested = totalExpenses - (scholarship + otherSources + contribution)`. Server does not recalculate. |

---

### total_expenses (IES)

| Attribute                  | Value                                                                        |
| -------------------------- | ---------------------------------------------------------------------------- |
| Module                     | project_IES_expenses                                                         |
| Current Location           | `app/Http/Controllers/Projects/IES/IESExpensesController.php` store()        |
| Current Formula            | None. Uses `$headerData['total_expenses']` from request (via fill).          |
| Client Trusted?            | Yes                                                                          |
| Clamped?                   | No                                                                           |
| Risk Level                 | High                                                                         |
| Proposed Canonical Formula | `sum(amount)` over expenseDetails                                            |
| Notes                      | Client JS calculates; server stores via fill($headerData). No recalculation. |

---

### balance_requested (IES)

| Attribute                  | Value                                                                                             |
| -------------------------- | ------------------------------------------------------------------------------------------------- |
| Module                     | project_IES_expenses                                                                              |
| Current Location           | `app/Http/Controllers/Projects/IES/IESExpensesController.php` store()                             |
| Current Formula            | None. Uses `$headerData['balance_requested']` from request.                                       |
| Client Trusted?            | Yes                                                                                               |
| Clamped?                   | No                                                                                                |
| Risk Level                 | High                                                                                              |
| Proposed Canonical Formula | `total_expenses - (expected_scholarship_govt + support_other_sources + beneficiary_contribution)` |
| Notes                      | Client JS calculates; server stores without recalculation.                                        |

---

### amount_sanctioned (ProjectFundFieldsResolver)

| Attribute                  | Value                                                                                                                                                                                       |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | projects (read-only resolution)                                                                                                                                                             |
| Current Location           | `app/Services/Budget/ProjectFundFieldsResolver.php` resolveDevelopment(), resolveIIES(), resolveIES(), etc.                                                                                 |
| Current Formula            | Development: `overall - (forwarded + local)`; IIES/IES: reads stored `iies_balance_requested` / `balance_requested`; ILP/IAH: reads stored `amount_requested`; IGE: `sum(amount_requested)` |
| Client Trusted?            | N/A (reads from DB)                                                                                                                                                                         |
| Clamped?                   | Yes (max(0, ...) and round(..., 2) in normalize())                                                                                                                                          |
| Risk Level                 | Low                                                                                                                                                                                         |
| Proposed Canonical Formula | Per-type; Development: `overall - (forwarded + local)`; others read stored derived fields                                                                                                   |
| Notes                      | Read-only. Does not persist. Logs discrepancy if resolved ≠ stored.                                                                                                                         |

---

### opening_balance (ProjectFundFieldsResolver)

| Attribute                  | Value                                                                                                                               |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Module                     | projects (read-only resolution)                                                                                                     |
| Current Location           | `app/Services/Budget/ProjectFundFieldsResolver.php` resolveDevelopment(), resolveIIES(), etc.                                       |
| Current Formula            | Development: `sanctioned + forwarded + local`; IIES/IES/ILP/IAH/IGE: `opening = overall` (same as overall when forwarded=0)         |
| Client Trusted?            | N/A                                                                                                                                 |
| Clamped?                   | Yes (normalize rounds and applies max(0, ...))                                                                                      |
| Risk Level                 | Low                                                                                                                                 |
| Proposed Canonical Formula | Development: `amount_sanctioned + amount_forwarded + local_contribution`; Individual/IGE: `overall_project_budget` when forwarded=0 |
| Notes                      | Read-only.                                                                                                                          |

---

### amount_sanctioned (AdminCorrectionService)

| Attribute                  | Value                                                                      |
| -------------------------- | -------------------------------------------------------------------------- |
| Module                     | projects (admin manual correction)                                         |
| Current Location           | `app/Services/Budget/AdminCorrectionService.php` normalizeManualValues()   |
| Current Formula            | `round(max(0, overall - (forwarded + local)), 2)`                          |
| Client Trusted?            | No (admin-entered values, then normalized)                                 |
| Clamped?                   | Yes                                                                        |
| Risk Level                 | Low                                                                        |
| Proposed Canonical Formula | `max(0, overall_project_budget - (amount_forwarded + local_contribution))` |
| Notes                      | Used for manual admin corrections. Validates forwarded + local ≤ overall.  |

---

### opening_balance (AdminCorrectionService)

| Attribute                  | Value                                                                    |
| -------------------------- | ------------------------------------------------------------------------ |
| Module                     | projects (admin manual correction)                                       |
| Current Location           | `app/Services/Budget/AdminCorrectionService.php` normalizeManualValues() |
| Current Formula            | `round(sanctioned + forwarded + local, 2)`                               |
| Client Trusted?            | No                                                                       |
| Clamped?                   | Values clamped via max(0, ...) before this                               |
| Risk Level                 | Low                                                                      |
| Proposed Canonical Formula | `amount_sanctioned + amount_forwarded + local_contribution`              |
| Notes                      | —                                                                        |

---

### DirectMappingStrategy

| Attribute        | Value                                                                                               |
| ---------------- | --------------------------------------------------------------------------------------------------- |
| Module           | BudgetCalculationService                                                                            |
| Current Location | `app/Services/Budget/Strategies/DirectMappingStrategy.php`                                          |
| Derived Logic    | No derived logic found. Fetches budgets from model (phase-based or direct). No formula computation. |

---

### BudgetCalculationService (helper methods)

| Attribute        | Value                                                                                                                                                                                                                                                                                                                                               |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module           | BudgetCalculationService                                                                                                                                                                                                                                                                                                                            |
| Current Location | `app/Services/Budget/BudgetCalculationService.php`                                                                                                                                                                                                                                                                                                  |
| Derived Logic    | Pure helpers: `calculateContributionPerRow(contribution, totalRows) = contribution / totalRows`; `calculateTotalContribution(sources) = array_sum(sources)`; `calculateAmountSanctioned(original, contributionPerRow) = max(0, original - contributionPerRow)`; `preventNegativeAmount(amount) = max(0, amount)`. Used by strategies; no DB access. |

---

## Formula Drift Summary

| Field                          | Locations                                                            | Inconsistency                                                                                                                |
| ------------------------------ | -------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| this_phase                     | BudgetController, ProjectBudget::calculateTotalBudget, JS            | BudgetController trusts client. Model uses `q*m*d*rate_increase`; JS uses `q*m*d`. ⚠ Formula Drift — Requires Clarification. |
| next_phase                     | BudgetController, JS                                                 | Controller trusts client. No canonical formula in code. Docs suggest `(q+rate_increase)*m*d`.                                |
| amount_sanctioned (report row) | SingleSourceContributionStrategy, MultipleSourceContributionStrategy | Both use `max(0, amount - contributionPerRow)`. Consistent.                                                                  |
| total_expenses (IAH)           | IAHBudgetDetailsController                                           | Recalculated server-side. Same value stored per row — design question.                                                       |
| amount_requested (IAH)         | IAHBudgetDetailsController                                           | Recalculated server-side. Consistent in store and edit.                                                                      |
| iies_total_expenses            | IIESExpensesController vs client JS                                  | Server trusts client. Canonical: sum of iies_amount.                                                                         |
| iies_balance_requested         | IIESExpensesController vs client JS                                  | Server trusts client. Canonical: total - (scholarship + other + contribution).                                               |
| total_expenses (IES)           | IESExpensesController vs client JS                                   | Server trusts client. Canonical: sum of amounts.                                                                             |
| balance_requested (IES)        | IESExpensesController vs client JS                                   | Server trusts client. Canonical: total - (scholarship + other + contribution).                                               |
| amount_sanctioned (project)    | ProjectFundFieldsResolver, AdminCorrectionService                    | Both use `overall - (forwarded + local)`. Consistent.                                                                        |
| opening_balance                | ProjectFundFieldsResolver, AdminCorrectionService                    | Both use `sanctioned + forwarded + local`. Consistent.                                                                       |

---

## Canonicalization Strategy (Design Proposal)

### Future DerivedCalculationService

A future service (not implemented in this phase) would:

1. **Provide pure functions only** — No database access. Inputs are scalar/array values; outputs are computed values.
2. **Define canonical formulas per field** — One expression per derived field, documented and tested.
3. **Use BoundedNumericService for clamping** — All outputs that are persisted to bounded columns (e.g. `project_budgets.this_phase`) pass through `BoundedNumericService::clamp()` or `calculateAndClamp()`.
4. **Be invoked by controllers before persistence** — Controllers must ignore client-submitted derived totals and call the service to recompute.

### Formula Contracts (Proposed)

| Field                            | Inputs                                                       | Output                                       | Clamp Target                                 |
| -------------------------------- | ------------------------------------------------------------ | -------------------------------------------- | -------------------------------------------- |
| this_phase                       | rate_quantity, rate_multiplier, rate_duration                | q _ m _ d                                    | project_budgets.this_phase                   |
| next_phase                       | rate_quantity, rate_multiplier, rate_duration, rate_increase | (q + rate_increase) _ m _ d                  | project_budgets.next_phase                   |
| amount_sanctioned (IAH/ILP row)  | amount, contribution, total_rows                             | max(0, amount - (contribution / total_rows)) | Per config                                   |
| amount_sanctioned (IIES/IES row) | amount, contribution_sources[], total_rows                   | max(0, amount - (sum(sources) / total_rows)) | Per config                                   |
| total_expenses (IAH)             | amounts[]                                                    | sum(amounts)                                 | —                                            |
| amount_requested (IAH)           | total_expenses, family_contribution                          | total_expenses - family_contribution         | —                                            |
| iies_total_expenses              | iies_amounts[]                                               | sum(amounts)                                 | project_IIES_expenses.iies_total_expenses    |
| iies_balance_requested           | total, scholarship, other, contribution                      | total - (scholarship + other + contribution) | project_IIES_expenses.iies_balance_requested |
| total_expenses (IES)             | amounts[]                                                    | sum(amounts)                                 | —                                            |
| balance_requested (IES)          | total, scholarship, other, contribution                      | total - (scholarship + other + contribution) | —                                            |
| amount_sanctioned (project)      | overall, forwarded, local                                    | max(0, overall - (forwarded + local))        | —                                            |
| opening_balance (project)        | sanctioned, forwarded, local                                 | sanctioned + forwarded + local               | —                                            |

### Controller Integration (Design Only)

- **BudgetController**: Before `ProjectBudget::create()`, call `DerivedCalculationService::thisPhase(rate_quantity, rate_multiplier, rate_duration)` and `nextPhase(...)` instead of using `$budget['this_phase']` / `$budget['next_phase']`.
- **IIESExpensesController**: Recompute `iies_total_expenses` from `iies_amounts` and `iies_balance_requested` from the canonical formula before save.
- **IESExpensesController**: Recompute `total_expenses` from `amounts` and `balance_requested` from the canonical formula before save.
- **IAHBudgetDetailsController**: Already recalculates; ensure formula matches canonical and consider separate `total_expenses` aggregate vs per-row.

### Architecture Principles

- **Single source of truth**: One function per derived field.
- **No DB in service**: Service receives values; returns values. Controllers handle persistence.
- **Bounds integration**: Service or controller calls `BoundedNumericService::clamp()` / `calculateAndClamp()` for fields in `config/decimal_bounds.php`.
- **Testability**: Pure functions are easily unit-tested.

---

## Out of Scope (Explicit)

- No UI changes
- No JS changes
- No DB schema changes
- No numeric bounds redesign
- No service implementation
- No controller modifications
- No model modifications
- No validation rule changes

---

## Appendix: Files Inspected

| File                                                                    | Derived Logic Found?                                                                                           |
| ----------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `app/Http/Controllers/Projects/BudgetController.php`                    | Clamp only; no formula. Trusts client for this_phase, next_phase.                                              |
| `app/Models/OldProjects/ProjectBudget.php`                              | Yes: calculateTotalBudget, calculateRemainingBalance                                                           |
| `app/Services/Budget/BudgetCalculationService.php`                      | Yes: calculateContributionPerRow, calculateTotalContribution, calculateAmountSanctioned, preventNegativeAmount |
| `app/Services/Budget/Strategies/DirectMappingStrategy.php`              | No derived logic found                                                                                         |
| `app/Services/Budget/Strategies/SingleSourceContributionStrategy.php`   | Yes: amount_sanctioned per row                                                                                 |
| `app/Services/Budget/Strategies/MultipleSourceContributionStrategy.php` | Yes: amount_sanctioned per row                                                                                 |
| `app/Http/Controllers/Projects/IES/IESExpensesController.php`           | No derived logic. Trusts client for total_expenses, balance_requested.                                         |
| `app/Http/Controllers/Projects/IIES/IIESExpensesController.php`         | No derived logic. Trusts client for iies_total_expenses, iies_balance_requested.                               |
| `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php`      | Yes: total_expenses, amount_requested                                                                          |
| `app/Services/Budget/ProjectFundFieldsResolver.php`                     | Yes: amount_sanctioned, opening_balance (read-only aggregation)                                                |
| `app/Services/Budget/AdminCorrectionService.php`                        | Yes: amount_sanctioned, opening_balance (normalizeManualValues)                                                |

**Note**: `MultipleSourceBudgetStrategy`, `SingleSourceBudgetStrategy`, `DirectMappingBudgetStrategy` were not found. The codebase uses `MultipleSourceContributionStrategy`, `SingleSourceContributionStrategy`, `DirectMappingStrategy` instead.

---

## Phase 2.4 — UI Technical Debt (rate_increase Legacy Display Logic)

**Date**: 2026-02-09  
**Status**: Documented as architectural debt. No code changes.

### Where the JS Reference Exists

| File | Function | Lines |
|------|----------|-------|
| `resources/views/projects/partials/scripts.blade.php` | `calculateBudgetTotals(phaseCard)` | 101–126 |

### What It Currently Does

- Line 114: `totalRateIncrease += parseFloat(row.querySelector('[name$="[rate_increase]"]').value) || 0;`
- Line 122: `phaseCard.querySelector('.total_rate_increase').value = totalRateIncrease.toFixed(2);`

The function sums `rate_increase` from each budget row and writes the total to a `.total_rate_increase` footer element.

### Why It Is Harmless

1. **No persistence impact**: The current create/edit forms (`budget.blade.php`, `Edit/budget.blade.php`) do not render `rate_increase` inputs. Form submission never includes `rate_increase`; BudgetController persists `$budget['rate_increase'] ?? 0`.

2. **No this_phase impact**: `this_phase` is calculated by `calculateBudgetRowTotals()` using `rateQuantity * rateMultiplier * rateDuration` only. `calculateBudgetTotals()` does not participate in that calculation.

3. **No backend totals impact**: Backend uses persisted `this_phase` and `$budgets->sum('this_phase')` for totals. No backend logic reads or sums `rate_increase` for budget totals.

4. **Effectively dead for current forms**: `calculateBudgetTotals(phaseCard)` requires a `phaseCard` element. The current budget form uses `#phases-container` with a single table and no `.phase-card`. No caller passes `phaseCard` to this function in the active create flow. `addBudgetRow()` and `calculateBudgetRowTotals()` invoke `calculateTotalAmountSanctioned()` instead, which does not use `rate_increase`.

### Why It Is Architectural Debt

- Leftover logic from a legacy multi-phase budget UI that had `rate_increase` and `next_phase` inputs and `.phase-card` containers.
- `rate_increase` is no longer in the canonical backend formula (`ProjectBudget::calculateTotalBudget()` = `q × m × d`) and has no active business rule.
- The code suggests `rate_increase` is still part of budget totals when it is not.
- `scripts-edit.blade.php` was already updated to a single-phase `calculateBudgetTotals()` that does not reference `rate_increase`; `scripts.blade.php` was not.

### Why It Is Not Being Fixed in This Phase

- Phase 2.4 is design-only; no JS changes are in scope.
- The logic is harmless and has no runtime impact on current forms.
- Remediation belongs in a dedicated Derived Calculation Consolidation pass that addresses all legacy JS and multi-phase remnants together.

### Recommendation

Remove during Phase 2.4 Derived Calculation Consolidation, when script consolidation and legacy cleanup are performed. Until then, leave as-is; no runtime impact.
