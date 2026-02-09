# Basic Information Budget Standardization Audit

**Date:** February 9, 2026  
**Status:** Audit and planning only — no code modifications  
**Scope:** Edit (Budget Section) vs View (Basic Information Section) — all 6 budget fields

---

## 1. Current Implementation Mapping

### 1.1 Field-by-Field Location Matrix

| Field | Computed Where | Stored Where | Read Where | Edit Uses | View Uses |
|-------|----------------|--------------|------------|-----------|-----------|
| **overall_project_budget** | JS: `calculateProjectTotal()` in scripts-edit.blade.php (sum of `this_phase` from rows); Resolver: `resolveDevelopment()` fallback when overall=0 | `projects.overall_project_budget` via GeneralInfoController | Blade: Edit/budget.blade.php (initial value); Show/general_info.blade.php; ExportController; CoordinatorController; etc. | DB value initially; JS overwrites with recomputed sum on DOMContentLoaded | `resolvedFundFields['overall_project_budget']` or `$project->overall_project_budget` |
| **amount_forwarded** | Not computed; user input | `projects.amount_forwarded` via GeneralInfoController | Blade: Edit/budget.blade.php; Show/general_info.blade.php; ProjectFundFieldsResolver | DB value (user-editable) | `resolvedFundFields['amount_forwarded']` or `$project->amount_forwarded` |
| **local_contribution** | Type-specific: IIES/IES/IAH/ILP/IGE from type-specific tables; Development from `projects.local_contribution` | `projects.local_contribution` via GeneralInfoController (institutional); type-specific tables (individual) | Blade: Edit/budget.blade.php; Show/general_info.blade.php; ProjectFundFieldsResolver | DB value (user-editable for institutional) | `resolvedFundFields['local_contribution']` or `$project->local_contribution` |
| **amount_requested** | Blade: `max(0, $budget_overall - ($budget_forwarded + $budget_local))` in Show/general_info.blade.php; Edit: `amount_sanctioned_preview` = same formula | Not stored in `projects`; ILP/IAH: `amount_requested` in type-specific tables | Show/general_info.blade.php; ExportController (ILP/IAH) | JS: `calculateAmountSanctioned()` → `amount_sanctioned_preview` (same as amount_sanctioned) | Inline PHP in general_info.blade.php |
| **amount_sanctioned** | Resolver: `overall - (forwarded + local)`; JS: `calculateAmountSanctioned()`; CoordinatorController: approval calc | `projects.amount_sanctioned` via CoordinatorController (approval); GeneralController (approval flow) | Blade: Edit/budget.blade.php (amount_sanctioned_preview); Show/general_info.blade.php; ExportController | JS recomputed; initial from DB | `resolvedFundFields['amount_sanctioned']` or `$project->amount_sanctioned` |
| **opening_balance** | Resolver: `sanctioned + forwarded + local`; JS: `calculateAmountSanctioned()`; CoordinatorController: approval calc | `projects.opening_balance` via CoordinatorController; GeneralController | Blade: Edit/budget.blade.php (opening_balance_preview); Show/general_info.blade.php; ExportController | JS recomputed; initial from DB | `resolvedFundFields['opening_balance']` or `$project->opening_balance` |

### 1.2 Calculation Sources

#### Models

| Model | Method | Arithmetic | Fields |
|-------|--------|------------|--------|
| `ProjectBudget` | `calculateTotalBudget()` | Delegates to `DerivedCalculationService::calculateRowTotal()` | Row total |
| `ProjectBudget` | `calculateRemainingBalance()` | Delegates to `DerivedCalculationService::calculateRemainingBalance()` | Remaining |
| `Project` | None | No accessors for budget fields | — |

#### Controllers

| Controller | Method | Arithmetic | Fields |
|------------|--------|------------|--------|
| `CoordinatorController` | `approveProject()` ~1113–1148 | `$amountSanctioned = $overallBudget - $combinedContribution`; `$openingBalance = $amountSanctioned + $combinedContribution` | amount_sanctioned, opening_balance |
| `GeneralController` | ~2542–2568 | `$amountSanctioned = $overallBudget - $combinedContribution`; `$openingBalance = $amountSanctioned + $combinedContribution` | amount_sanctioned, opening_balance |
| `GeneralInfoController` | `update()` | None; persists request values | overall_project_budget, amount_forwarded, local_contribution |
| `BudgetController` | `update()` | None; persists budget rows; calls `BudgetSyncService::syncFromTypeSave()` | — |

#### Blade Files

| File | Arithmetic | Fields |
|------|------------|--------|
| `resources/views/projects/partials/Show/general_info.blade.php` | Line 33: `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` | amount_requested |
| `resources/views/projects/Oldprojects/pdf.blade.php` | Line 794: `amount_sanctioned ?? max(0, overall - (forwarded + local))` | amount_sanctioned, amount_forwarded, local_contribution |

#### JavaScript

| File | Function | Arithmetic | Fields |
|------|----------|------------|--------|
| `resources/views/projects/partials/scripts-edit.blade.php` | `calculateProjectTotal()` ~1036–1072 | Sums `this_phase` from rows; sets `overall_project_budget` | overall_project_budget |
| `resources/views/projects/partials/scripts-edit.blade.php` | `calculateAmountSanctioned()` ~1079–1135 | `amountSanctioned = overallBudget - combined`; `openingBalance = amountSanctioned + combined` | amount_sanctioned_preview, opening_balance_preview |
| `resources/views/projects/partials/scripts.blade.php` | Same pattern | Same | Same (create flow) |
| `public/js/budget-calculations.js` | `calculateProjectTotal()`, `calculateAmountSanctioned()` | Sum; `overall - combined` | Used by scripts above |

#### Services

| Service | Method | Arithmetic | Fields |
|---------|--------|------------|--------|
| `ProjectFundFieldsResolver` | `resolveDevelopment()` | `overall = project.overall_project_budget` (or sum when 0); `sanctioned = overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` | All 5 |
| `ProjectFundFieldsResolver` | `resolveIIES()`, `resolveIES()`, `resolveILP()`, `resolveIAH()`, `resolveIGE()` | Type-specific formulas | All 5 |
| `AdminCorrectionService` | `normalizeManualValues()` | `sanctioned = overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` | All 5 |
| `BudgetSyncService` | `syncFromTypeSave()` | None; writes resolver output | overall_project_budget, amount_forwarded, local_contribution |
| `DerivedCalculationService` | `calculateRowTotal()`, `calculateProjectTotal()`, `calculateRemainingBalance()` | Row total; project total; remaining | Row totals, phase totals |
| `BudgetCalculationService` | `getBudgetsForReport()`, `calculateAmountSanctioned()` | Contribution per row; amount sanctioned per row | Used for reports, not Basic Info |

---

## 2. Edit vs View Discrepancy Table

| Field | Edit Source | View Source | Stored in DB? | Recomputed? | Difference |
|-------|-------------|-------------|---------------|-------------|------------|
| **overall_project_budget** | JS: `calculateProjectTotal()` sums `this_phase` from `budgetsForEdit` (current phase rows); initial from `$project->overall_project_budget` | `ProjectFundFieldsResolver`, then `$project->overall_project_budget` when resolver disabled. Resolver: `project.overall_project_budget` when non-zero; fallback `sum(phaseBudgets.this_phase)` when 0 | Yes | Edit: JS recomputes on load. View: Resolver uses DB when non-zero | **Yes** — Edit shows sum of budget rows; View shows DB when non-zero. DB can be stale. |
| **amount_forwarded** | `$project->amount_forwarded` (user input) | `resolvedFundFields['amount_forwarded']` or `$project->amount_forwarded`. Resolver: `project.amount_forwarded` | Yes | No | No (both use DB) |
| **local_contribution** | `$project->local_contribution` (user input) | `resolvedFundFields['local_contribution']` or `$project->local_contribution`. Resolver: `project.local_contribution` (Development) or type-specific (IIES/IES/ILP/IAH/IGE) | Yes (projects) or type-specific | Resolver recomputes for type-specific | **Possible** — IIES/IES/ILP/IAH/IGE: resolver uses type-specific source; Edit may use different source |
| **amount_requested** | Shown as `amount_sanctioned_preview` = `overall - (forwarded + local)` via JS | Blade: `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` | No | Both recompute | **Yes** — if overall differs, amount_requested differs |
| **amount_sanctioned** | JS: `calculateAmountSanctioned()` = `overall - (forwarded + local)` | `resolvedFundFields['amount_sanctioned']` = `overall - (forwarded + local)` via resolver | Yes (on approval) | Both recompute | **Yes** — if overall differs, amount_sanctioned differs |
| **opening_balance** | JS: `calculateAmountSanctioned()` = `amountSanctioned + (forwarded + local)` | `resolvedFundFields['opening_balance']` = `sanctioned + forwarded + local` via resolver | Yes (on approval) | Both recompute | **Yes** — if overall differs, opening_balance differs |

### Edit Page Chain

| Layer | Controller | Blade | JS |
|-------|------------|-------|-----|
| **Route** | `GET /executor/projects/{id}/edit` | — | — |
| **Controller** | `ProjectController@edit` | — | — |
| **Controller** | `->with('budgets')`; `$budgetsForEdit = $project->budgets->where('phase', current_phase)` | — | — |
| **Blade** | — | `projects.partials.Edit.budget` | — |
| **Blade** | — | `value="{{ old('overall_project_budget', $project->overall_project_budget) }}"` (hidden); `value="{{ old('amount_forwarded', $project->amount_forwarded) }}"`; etc. | — |
| **JS** | — | — | `DOMContentLoaded` → `calculateProjectTotal()` → `calculateAmountSanctioned()` |

### View Page Chain

| Layer | Controller | Blade | Service |
|-------|------------|-------|---------|
| **Route** | `GET /executor/projects/{id}` | — | — |
| **Controller** | `ProjectController@show` | — | — |
| **Controller** | `$resolver->resolve($project, true)` when `resolver_enabled` or project type in list | — | — |
| **Service** | — | — | `ProjectFundFieldsResolver::resolve()` → `resolveForType()` |
| **Blade** | — | `projects.partials.Show.general_info` | — |
| **Blade** | — | `$budget_overall = $resolvedFundFields['overall_project_budget'] ?? $project->overall_project_budget` | — |
| **Blade** | — | `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` | — |

---

## 3. Project Type Matrix

| Project Type | Overall Budget Source | Local Contribution Source | Amount Requested Formula | Amount Sanctioned | Opening Balance | Resolver Used? | Edit Budget Partial |
|--------------|----------------------|---------------------------|--------------------------|-------------------|-----------------|----------------|---------------------|
| **Development Projects** | `projects.overall_project_budget` or `sum(project_budgets.this_phase)` when overall=0 | `projects.local_contribution` | `overall - (forwarded + local)` | `overall - (forwarded + local)` | `sanctioned + forwarded + local` | When `resolver_enabled` or in type list (not in list) | Edit.budget |
| **Livelihood Development Projects** | Same as Development | Same | Same | Same | Same | Same | Edit.budget |
| **Residential Skill Training** | Same | Same | Same | Same | Same | Same | Edit.budget |
| **CIC, CCI, Rural-Urban-Tribal** | Same | Same | Same | Same | Same | Same | Edit.budget |
| **Institutional Ongoing Group Educational (IGE)** | `sum(project_ige_budgets.total_amount)` | `sum(scholarship_eligibility) + sum(family_contribution)` | `overall - (forwarded + local)` | `sum(amount_requested)` | `overall` | **Yes** (in list) | Edit.IGE.budget |
| **Individual - Livelihood Application (ILP)** | `sum(ilp_budget.cost)` | `first(beneficiary_contribution)` | `overall - (forwarded + local)` | `first(amount_requested)` | `overall` | **Yes** | Edit.ILP.budget |
| **Individual - Access to Health (IAH)** | `sum(iah_budget_details.amount)` | `first(family_contribution)` | `overall - (forwarded + local)` | `first(amount_requested)` | `overall` | **Yes** | Edit.IAH.budget_details |
| **Individual - Initial Educational (IIES)** | `iies_expenses.iies_total_expenses` | `expected_scholarship_govt + support_other_sources + beneficiary_contribution` | N/A | `iies_balance_requested` | `overall` | **Yes** | Edit.IIES.* (no Edit.budget) |
| **Individual - Ongoing Educational (IES)** | `ies_expenses.total_expenses` | `expected_scholarship_govt + support_other_sources + beneficiary_contribution` | N/A | `balance_requested` | `overall` | **Yes** | Edit.IES.* (no Edit.budget) |

### Resolver Usage by Project Type

| Project Type | In `$typesWithTypeSpecificBudget`? | Resolver runs when? |
|--------------|-----------------------------------|--------------------|
| IIES, IES, ILP, IAH, IGE | Yes | Always |
| Development Projects, LDP, RST, CIC, CCI, Edu-RUT | No | Only when `config('budget.resolver_enabled')` = true |

### Edit Budget Partial by Project Type

| Project Type | Uses Edit.budget? | Uses Type-Specific? |
|--------------|-------------------|---------------------|
| Development Projects, LDP, RST, CIC, CCI, Edu-RUT, IGE | Yes | IGE uses Edit.IGE.budget in addition |
| IIES, IES, ILP, IAH | No | Edit.IIES.*, Edit.IES.*, Edit.ILP.budget, Edit.IAH.budget_details |

---

## 4. Identified Architectural Risks

| Risk | Description | Severity |
|------|-------------|----------|
| **Source-of-truth mismatch** | View uses `projects.overall_project_budget`; Edit uses JS sum of budget rows. When DB is stale, View and Edit diverge. | High |
| **Resolver fallback rarely used** | `resolveDevelopment()` only sums budget items when `overall == 0`. When non-zero, it trusts DB regardless of budget rows. | High |
| **Dual persistence paths** | `overall_project_budget` saved by GeneralInfoController from request; BudgetSyncService syncs from resolver. Resolver can perpetuate stale value. | Medium |
| **Blade arithmetic** | `amount_requested` computed inline in general_info.blade.php. Duplicates resolver logic. | Medium |
| **Config-dependent resolver** | Development Projects only get resolver when `resolver_enabled`; otherwise View uses raw DB. Inconsistent behavior. | Medium |
| **Type-specific vs generic budget** | Individual types (IIES, IES, ILP, IAH) use type-specific partials; View Basic Info always uses same 6 fields. Resolver sources differ per type. | Medium |
| **Phase scoping** | Edit uses `budgetsForEdit` = current phase only; Resolver fallback uses current phase only; Show.budget table uses all phases. | Low |
| **No server-side validation of overall** | GeneralInfoController accepts `overall_project_budget` from request without verifying against budget rows. | High |

---

## 5. Phase-wise Correction Plan

### PHASE A — Truth Source Definition

| Field | Canonical Formula | Classification | Storage |
|-------|-------------------|----------------|---------|
| **overall_project_budget** | `sum(project_budgets.this_phase)` for current phase (Development/LDP/RST/etc.) or type-specific sum | **Derived** | Stored snapshot (cached) |
| **amount_forwarded** | User input | **Stored** | Stored |
| **local_contribution** | User input (institutional) or type-specific (IIES/IES/ILP/IAH/IGE) | **Hybrid** | Stored |
| **amount_requested** | `overall - (forwarded + local)` | **Derived only** | Not stored |
| **amount_sanctioned** | `overall - (forwarded + local)` | **Derived** | Stored snapshot (on approval) |
| **opening_balance** | `sanctioned + forwarded + local` (= overall when valid) | **Derived** | Stored snapshot (on approval) |

**Recommendation:** `overall_project_budget` should be **derived from budget items** when displaying; stored as **cached snapshot** only when synced from authoritative source.

### PHASE B — Remove View/Edit Divergence

1. **Resolver change:** In `ProjectFundFieldsResolver::resolveDevelopment()`, when budgets exist, compute `overall` from `sum(this_phase)` (current phase) instead of `project.overall_project_budget` when non-zero.
2. **Remove Blade arithmetic:** Move `$amount_requested` computation to resolver or shared helper; Blade only displays.
3. **Remove controller arithmetic:** CoordinatorController and GeneralController approval calc should delegate to a canonical service (e.g. `AdminCorrectionService::normalizeManualValues` or new `BudgetCalculationService` method).
4. **Delegate to service:** All formula application in `DerivedCalculationService` or `ProjectFundFieldsResolver`; no inline formulas in Blade or Controller.

### PHASE C — Project-Type Abstraction

1. **Strategy pattern:** Already exists in `BudgetCalculationService` (DirectMappingStrategy, SingleSourceContributionStrategy, MultipleSourceContributionStrategy). Extend `ProjectFundFieldsResolver` to use same strategies for Basic Info resolution.
2. **Type-based rule mapping:** `config/budget.field_mappings` already defines `model`, `strategy`, `fields` per type. Add `basic_info_resolver` or extend resolver to use `getBudgetsForReport` equivalent for overall.
3. **Unify resolver:** Ensure `resolveForType()` returns values from same source Edit uses per type.

### PHASE D — Freeze With Guard Tests

| Test Name | Purpose | Expected Assertions |
|-----------|---------|---------------------|
| `BasicInfoBudgetParityTest` | View and Edit show same values for same project | `resolvedFundFields['overall_project_budget']` == JS sum of Edit rows for Development |
| `ProjectFundFieldsResolverUsesBudgetSumTest` | Resolver computes overall from budgets when available | When budgets exist, `overall` != `project.overall_project_budget` when they differ |
| `BudgetFormulaParityTest` (existing) | JS and PHP formulas match | Keep existing |
| `BudgetDomainIsolationTest` (existing) | No arithmetic outside DerivedCalculationService | Keep existing |

---

## 6. Recommendation: Derived vs Stored Decision

### Overall Project Budget

| Option | Pros | Cons |
|--------|------|------|
| **A: Always derived** | Always correct; no DB drift | Requires budget rows loaded; more queries |
| **B: Stored snapshot** | Fast; single column read | Can become stale; sync must run after every budget save |
| **C: Hybrid (current)** | Fast when DB correct | Fails when DB stale; View shows wrong value |

**Recommendation:** **Hybrid with authoritative derivation:** Use `sum(budget items)` as canonical when budgets exist; store `cached` value only when `BudgetSyncService` runs after budget save. **View:** Always derive from budgets when available (resolver change). **Edit:** Continue JS recompute; ensure sync runs and writes correct value.

### Amount Sanctioned / Opening Balance

| Option | Pros | Cons |
|--------|------|------|
| **A: Always derived** | Consistent with overall | View and Edit both recompute |
| **B: Stored on approval** | Approval snapshot preserved | Pre-approval: derived |

**Recommendation:** **Derived on display; stored on approval.** CoordinatorController and GeneralController already compute and store on approval. Pre-approval: always derive from `overall - (forwarded + local)`.

### Amount Requested

**Recommendation:** **Always derived.** Same as amount_sanctioned pre-approval. Do not store in `projects`; compute in resolver and Blade from resolver output.

---

## 7. File Reference Summary

| Purpose | File Path |
|---------|-----------|
| View Basic Info | `resources/views/projects/partials/Show/general_info.blade.php` |
| Edit Budget Section | `resources/views/projects/partials/Edit/budget.blade.php` |
| Edit JS | `resources/views/projects/partials/scripts-edit.blade.php` |
| Create JS | `resources/views/projects/partials/scripts.blade.php` |
| Centralized JS formulas | `public/js/budget-calculations.js` |
| Resolver | `app/Services/Budget/ProjectFundFieldsResolver.php` |
| Budget Sync | `app/Services/Budget/BudgetSyncService.php` |
| Admin Correction | `app/Services/Budget/AdminCorrectionService.php` |
| Derived Calculation | `app/Services/Budget/DerivedCalculationService.php` |
| Budget Calculation | `app/Services/Budget/BudgetCalculationService.php` |
| General Info Controller | `app/Http/Controllers/Projects/GeneralInfoController.php` |
| Budget Controller | `app/Http/Controllers/Projects/BudgetController.php` |
| Project Controller (show/edit) | `app/Http/Controllers/Projects/ProjectController.php` |
| Budget config | `config/budget.php` |

---

**Audit status:** Complete. No code modified. Awaiting implementation approval.
