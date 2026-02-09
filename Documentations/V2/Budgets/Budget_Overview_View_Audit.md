# Budget Overview – View Page Audit

**Date:** February 9, 2026  
**Scope:** Development Project VIEW page – Budget Overview section  
**Read-only audit — no code changes**

---

## Summary

The Budget Overview section on the Development Project VIEW page (`resources/views/projects/partials/Show/budget.blade.php`) receives all values from `BudgetValidationService::getBudgetSummary($project)`. The service computes values in `calculateBudgetData()` and does **not** use `ProjectFundFieldsResolver`, `DerivedCalculationService`, or `resolvedFundFields`. This creates parity gaps with the Basic Information section and the Edit page.

**Controller:** `ProjectController::show()` loads `$project` with `budgets`, `reports.accountDetails`; no direct budget computation in controller.

**Primary data source:** `app/Services/BudgetValidationService.php` → `getBudgetSummary()` → `validateBudget()` → `calculateBudgetData()`.

---

## Field: Total Budget (Budget Summary card)

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` → `$openingBalance` (displayed as "Total Budget" / "Available funds") |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 88 |
| **Controller** | None – service called directly from Blade |
| **Calculation** | `$openingBalance = $amountSanctioned + $amountForwarded + $localContribution` (BudgetValidationService line 59) |
| **DB column used** | Indirectly: `projects.amount_forwarded`, `projects.local_contribution`; sanctioned computed |
| **Derived service used** | None |
| **Note** | Card label "Total Budget" but note says "Available funds" — semantically this is **Opening Balance** |
| **Recomputed live** | Yes – on each page load |
| **Trusted from DB** | Partially; forwarded and local from DB; sanctioned computed |
| **Depends on approval** | No (BudgetValidationService does not branch on approval) |
| **Depends on phase** | No |
| **Matches Edit page** | Edit has no "Total Budget" card; Edit shows Opening Balance separately |
| **Risk level** | **Medium** – naming confusion (Total Budget vs Opening Balance) |

---

## Field: Total Expenses

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` lines 70–84 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 95 |
| **Controller** | None |
| **Calculation** | `$totalExpenses = sum(report->accountDetails->total_expenses)` over all `$project->reports` |
| **DB column used** | `DP_AccountDetails.total_expenses` via `reports.accountDetails` |
| **Derived service used** | None |
| **Uses** | `Collection->sum('total_expenses')` on accountDetails |
| **Recomputed live** | Yes |
| **Trusted from DB** | Yes – reads from DB |
| **Depends on approval** | No (sum of all reports) |
| **Depends on phase** | No – all reports, no phase filter |
| **Matches Edit page** | Edit page has no Total Expenses; Edit is project-level only |
| **Risk level** | **Low** |

---

## Field: Approved Expenses

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` lines 70–82 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 103 |
| **Controller** | None |
| **Calculation** | Sum of `accountDetails->sum('total_expenses')` for reports where `report->status === STATUS_APPROVED_BY_COORDINATOR` |
| **DB column used** | `DP_AccountDetails.total_expenses`, `DP_Reports.status` |
| **Derived service used** | None |
| **Uses** | `Collection->sum()` |
| **Recomputed live** | Yes |
| **Trusted from DB** | Yes |
| **Depends on approval** | Yes – only reports with approved status |
| **Depends on phase** | No |
| **Matches Edit page** | Edit has no Approved Expenses |
| **Risk level** | **Low** |

---

## Field: Unapproved Expenses

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` lines 70–82 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 109 |
| **Controller** | None |
| **Calculation** | Sum of `accountDetails->sum('total_expenses')` for reports where `report->status !== STATUS_APPROVED_BY_COORDINATOR` |
| **DB column used** | `DP_AccountDetails.total_expenses`, `DP_Reports.status` |
| **Derived service used** | None |
| **Uses** | `Collection->sum()` |
| **Recomputed live** | Yes |
| **Trusted from DB** | Yes |
| **Depends on approval** | Yes |
| **Depends on phase** | No |
| **Matches Edit page** | Edit has no Unapproved Expenses |
| **Risk level** | **Low** |

---

## Field: Remaining Balance

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` line 94 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 116 |
| **Controller** | None |
| **Calculation** | `$remainingBalance = $openingBalance - $totalExpenses` (inline) |
| **DB column used** | Indirect (derived from opening and expenses) |
| **Derived service used** | None (DerivedCalculationService has `calculateRemainingBalance()` but not used) |
| **Uses** | Inline arithmetic |
| **Recomputed live** | Yes |
| **Trusted from DB** | No – computed |
| **Depends on approval** | Indirectly via opening balance |
| **Depends on phase** | No |
| **Matches Edit page** | Edit has no Remaining Balance (no expenses on Edit) |
| **Matches DerivedCalculationService** | Formula matches `calculateRemainingBalance(totalBudget, totalExpenses)` but service not used |
| **Risk level** | **Medium** – duplicated logic; DerivedCalculationService not used |

---

## Field: Utilization %

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` lines 96–97 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` lines 124, 133 |
| **Controller** | None |
| **Calculation** | `$percentageUsed = $openingBalance > 0 ? ($totalExpenses / $openingBalance) * 100 : 0` |
| **DB column used** | None |
| **Derived service used** | None |
| **Uses** | Inline arithmetic |
| **Recomputed live** | Yes |
| **Trusted from DB** | No |
| **Depends on approval** | Indirectly |
| **Depends on phase** | No |
| **Matches Edit page** | Edit has no Utilization % |
| **Risk level** | **Medium** – utilization formula duplicated across 5+ controllers (see PHASE_2_4_DERIVED_CALCULATION_AUDIT_FULL.md) |

---

## Field: Overall Project Budget

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` lines 50–53 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 213 |
| **Controller** | None |
| **Calculation** | `$overallBudget = $project->overall_project_budget ?? 0`; if 0 and budgets loaded: `$project->budgets->sum('this_phase')` |
| **DB column used** | `projects.overall_project_budget`; fallback: `project_budgets.this_phase` |
| **Derived service used** | None |
| **Uses** | `Collection->sum('this_phase')` when overall=0; **no phase filter** – sums ALL phases |
| **Recomputed live** | Only when `overall == 0`; otherwise trusts DB |
| **Trusted from DB** | Yes when `overall_project_budget != 0` |
| **Depends on approval** | No |
| **Depends on phase** | **No** – sums all phases (mismatch with Edit and ProjectFundFieldsResolver) |
| **Matches Edit page** | **No** – Edit uses JS `calculateProjectTotal()` on `budgetsForEdit` (current_phase only) |
| **Matches Basic Info** | **No** – Basic Info uses `ProjectFundFieldsResolver` when `resolvedFundFields` present; resolver filters by `current_phase` and uses DerivedCalculationService |
| **Risk level** | **High** – different source than Basic Info and Edit; no phase filter; trusts stale DB when non-zero |

---

## Field: Amount Forwarded

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` line 56 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 216 |
| **Controller** | None |
| **Calculation** | `$amountForwarded = $project->amount_forwarded ?? 0` |
| **DB column used** | `projects.amount_forwarded` |
| **Derived service used** | None |
| **Uses** | Hard-coded DB column |
| **Recomputed live** | No – read from DB |
| **Trusted from DB** | Yes |
| **Depends on approval** | No |
| **Depends on phase** | No |
| **Matches Edit page** | Yes – Edit reads same DB field |
| **Matches Basic Info** | When `resolvedFundFields` used, Basic Info uses resolver; otherwise same DB |
| **Risk level** | **Low** |

---

## Field: Local Contribution

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` line 57 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 221 |
| **Controller** | None |
| **Calculation** | `$localContribution = $project->local_contribution ?? 0` |
| **DB column used** | `projects.local_contribution` |
| **Derived service used** | None |
| **Uses** | Hard-coded DB column |
| **Recomputed live** | No |
| **Trusted from DB** | Yes |
| **Depends on approval** | No |
| **Depends on phase** | No |
| **Matches Edit page** | Yes |
| **Matches Basic Info** | When `resolvedFundFields` used, Basic Info uses resolver (type-specific for IIES/IES/ILP/IAH/IGE) |
| **Risk level** | **Low** for Development; **Medium** for type-specific projects (BudgetValidationService always uses `projects.local_contribution`) |

---

## Field: Amount Sanctioned

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` line 58 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 225 |
| **Controller** | None |
| **Calculation** | `$amountSanctioned = $overallBudget - $amountForwarded - $localContribution` (inline) |
| **DB column used** | Indirect |
| **Derived service used** | None |
| **Uses** | Inline arithmetic |
| **Recomputed live** | Yes |
| **Trusted from DB** | No – always recomputed |
| **Depends on approval** | **No** – BudgetValidationService does not use `projects.amount_sanctioned` for approved projects |
| **Depends on phase** | No |
| **Matches Edit page** | Edit uses JS `calculateAmountSanctioned(overallBudget, combined)` – same formula |
| **Matches Basic Info** | **No** – Basic Info resolver uses `projects.amount_sanctioned` for approved projects |
| **Risk level** | **High** – for approved projects, resolver uses DB-stored sanctioned; BudgetValidationService recomputes and can diverge |

---

## Field: Opening Balance

| Attribute | Value |
|-----------|-------|
| **Source** | `BudgetValidationService::calculateBudgetData()` line 59 |
| **View file** | `resources/views/projects/partials/Show/budget.blade.php` line 229 |
| **Controller** | None |
| **Calculation** | `$openingBalance = $amountSanctioned + $amountForwarded + $localContribution` |
| **DB column used** | Indirect |
| **Derived service used** | None |
| **Uses** | Inline arithmetic |
| **Recomputed live** | Yes |
| **Trusted from DB** | No |
| **Depends on approval** | **No** – BudgetValidationService does not use `projects.opening_balance` for approved projects |
| **Depends on phase** | No |
| **Matches Edit page** | Edit uses JS `openingBalance = amountSanctioned + combined` – same formula |
| **Matches Basic Info** | **No** – Basic Info resolver uses `projects.opening_balance` for approved projects |
| **Risk level** | **High** – same as Amount Sanctioned; approved projects should use DB values |

---

## Cross-Controller Duplication

| Formula | Locations | Notes |
|---------|-----------|-------|
| **Utilization** `(totalExpenses / budget) * 100` | BudgetValidationService, CoordinatorController, GeneralController, ProvincialController, ExecutorController | Inconsistent rounding (1 vs 2 decimals) |
| **Remaining balance** `opening - totalExpenses` | BudgetValidationService (inline), DerivedCalculationService (`calculateRemainingBalance`) | Service exists but not used here |
| **Amount sanctioned** `overall - (forwarded + local)` | BudgetValidationService, ProjectFundFieldsResolver, Edit JS, CoordinatorController, GeneralController | Multiple implementations |
| **Opening balance** `sanctioned + forwarded + local` | BudgetValidationService, ProjectFundFieldsResolver, Edit JS | Multiple implementations |
| **Overall from budgets** `sum(this_phase)` | BudgetValidationService (all phases), ProjectFundFieldsResolver (current_phase), Edit JS (current_phase) | Phase filter differs |

---

## Approval Flow Influence

| Aspect | Budget Overview (BudgetValidationService) | Basic Info (ProjectFundFieldsResolver) |
|--------|------------------------------------------|--------------------------------------|
| **Approved project** | Always recomputes sanctioned and opening from overall - forwarded - local | Uses `projects.amount_sanctioned` and `projects.opening_balance` from DB |
| **Effect** | Budget Overview can show different sanctioned/opening than Basic Info when project is approved and overall is derived differently |
| **Risk** | **High** – two sections on same page can show different values for sanctioned and opening |

---

## Phase Influence

| Component | Phase filter | Notes |
|-----------|--------------|-------|
| **BudgetValidationService** | None | Uses `$project->budgets->sum('this_phase')` – **all phases** |
| **ProjectFundFieldsResolver** | `current_phase` | `$project->budgets->where('phase', $currentPhase)` |
| **Edit page** | `current_phase` | `$budgetsForEdit = $project->budgets->where('phase', current_phase)` |
| **Budget table footer** | None | `$project->budgets->sum('this_phase')` – all phases |
| **Risk** | **High** | Multi-phase projects: Overall in Budget Overview can differ from Basic Info and Edit |

---

## Discrepancy Summary

| Discrepancy | Severity |
|-------------|----------|
| Budget Overview does not use ProjectFundFieldsResolver; Basic Info does (when resolver_enabled) | **High** |
| Budget Overview uses BudgetValidationService which sums all phases; Edit and Resolver use current_phase | **High** |
| Budget Overview recomputes sanctioned/opening for approved projects; Resolver uses DB | **High** |
| Budget Overview does not use DerivedCalculationService | **Medium** |
| "Total Budget" card displays Opening Balance (naming confusion) | **Medium** |
| Utilization formula duplicated across controllers | **Medium** |
| Budget table shows all phases; no current_phase filter | **Medium** |

---

## Risk Classification

### High Risk

1. **Overall Project Budget**: Different source than Basic Info and Edit; no phase filter; trusts DB when non-zero.
2. **Amount Sanctioned / Opening Balance**: For approved projects, BudgetValidationService recomputes while Basic Info uses DB; can diverge.
3. **Data path split**: Budget Overview and Basic Info use different services; same page can show conflicting values.

### Medium Risk

1. **Total Budget card**: Label says "Total Budget" but shows Opening Balance ("Available funds").
2. **Phase filter**: BudgetValidationService sums all phases; Edit and Resolver use current_phase.
3. **DerivedCalculationService**: Not used; Remaining Balance and Utilization computed inline.
4. **Utilization**: Formula duplicated; rounding inconsistent across controllers.

### Low Risk

1. **Total Expenses, Approved Expenses, Unapproved Expenses**: Computed from reports; no known conflicts.
2. **Amount Forwarded, Local Contribution**: Read from DB; consistent with Edit for Development projects.

---

## File Reference

| Purpose | Path |
|---------|------|
| Budget Overview View | `resources/views/projects/partials/Show/budget.blade.php` |
| Data source | `app/Services/BudgetValidationService.php` |
| Controller | `app/Http/Controllers/Projects/ProjectController.php` (show) |
| Basic Info (comparison) | `resources/views/projects/partials/Show/general_info.blade.php` |
| ProjectFundFieldsResolver | `app/Services/Budget/ProjectFundFieldsResolver.php` |
| DerivedCalculationService | `app/Services/Budget/DerivedCalculationService.php` |
| Edit Budget | `resources/views/projects/partials/Edit/budget.blade.php` |
| Edit JS | `resources/views/projects/partials/scripts-edit.blade.php` |
| Budget calculations JS | `public/js/budget-calculations.js` |
