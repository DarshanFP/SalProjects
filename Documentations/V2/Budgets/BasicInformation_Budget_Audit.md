# Basic Information Budget Audit

**Date:** February 9, 2026  
**Status:** Audit only — no code modifications  
**Scope:** Edit (Budget Section) vs View (Basic Information Section) — 6 budget fields

---

## 1. Executive Summary

Budget values displayed on the **Edit → Budget Section** diverge from **View → Basic Information Section** for the same project. The root cause is a **source-of-truth mismatch**: Edit computes `overall_project_budget` from the sum of budget rows (JavaScript); View uses `projects.overall_project_budget` (DB) when non-zero. When the DB value is stale, View shows incorrect values and all derived fields (amount_requested, amount_sanctioned, opening_balance) cascade incorrectly.

**Key findings:**
- 6 fields: overall_project_budget, amount_forwarded, local_contribution, amount_requested, amount_sanctioned, opening_balance
- Edit uses JS recomputation; View uses ProjectFundFieldsResolver or raw DB
- ProjectFundFieldsResolver trusts DB for Development types when overall ≠ 0; never recomputes from budget rows
- Blade inline arithmetic exists for amount_requested
- Controller arithmetic exists in CoordinatorController and GeneralController for approval flow
- Project-type-specific formulas differ (IIES/IES/ILP/IAH/IGE vs Development/LDP/RST/etc.)

---

## 2. Current Implementation Mapping

### 2.1 Mapping Table per Field

| Field | Stored in DB? | Computed? | Where computed? | Used in Edit? | Used in View? | JS involved? | Service involved? |
|-------|---------------|-----------|-----------------|---------------|---------------|--------------|------------------|
| **overall_project_budget** | Yes (`projects.overall_project_budget`) | Yes | JS: `scripts-edit.blade.php` `calculateProjectTotal()` (sum of `this_phase`); Resolver: `ProjectFundFieldsResolver::resolveDevelopment()` fallback when overall=0 | Yes | Yes | Yes | ProjectFundFieldsResolver, BudgetSyncService |
| **amount_forwarded** | Yes (`projects.amount_forwarded`) | No | User input only | Yes | Yes | Yes (reads input) | ProjectFundFieldsResolver |
| **local_contribution** | Yes (`projects.local_contribution` or type-specific) | Yes (type-specific) | Resolver: `resolveIIES()`, `resolveIES()`, `resolveILP()`, `resolveIAH()`, `resolveIGE()` from type tables | Yes | Yes | Yes (reads input) | ProjectFundFieldsResolver |
| **amount_requested** | No (ILP/IAH: type-specific) | Yes | Blade: `Show/general_info.blade.php` line 33 `max(0, $budget_overall - ($budget_forwarded + $budget_local))`; Edit: `amount_sanctioned_preview` via JS | Yes (as amount_sanctioned_preview) | Yes | Yes | ProjectFundFieldsResolver (amount_sanctioned) |
| **amount_sanctioned** | Yes (`projects.amount_sanctioned` on approval) | Yes | Resolver: `overall - (forwarded + local)`; JS: `calculateAmountSanctioned()`; CoordinatorController: `approveProject()`; GeneralController | Yes | Yes | Yes | ProjectFundFieldsResolver, AdminCorrectionService |
| **opening_balance** | Yes (`projects.opening_balance` on approval) | Yes | Resolver: `sanctioned + forwarded + local`; JS: `calculateAmountSanctioned()`; CoordinatorController; GeneralController | Yes | Yes | Yes | ProjectFundFieldsResolver, AdminCorrectionService |

### 2.2 Detailed Location by Layer

#### Model Logic

| Model | Path | Accessors/Mutators | Methods | Fields |
|-------|------|-------------------|---------|--------|
| `Project` | `app/Models/OldProjects/Project.php` | None for budget fields | None | overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance in `$fillable` |
| `ProjectBudget` | `app/Models/OldProjects/ProjectBudget.php` | None | `calculateTotalBudget()` → DerivedCalculationService; `calculateRemainingBalance()` → DerivedCalculationService | Row total, remaining |

#### Controller Arithmetic

| Controller | Path | Method | Line | Arithmetic |
|-------------|------|--------|------|------------|
| `CoordinatorController` | `app/Http/Controllers/CoordinatorController.php` | `approveProject()` | ~1113–1148 | `$amountSanctioned = $overallBudget - $combinedContribution`; `$openingBalance = $amountSanctioned + $combinedContribution` |
| `GeneralController` | `app/Http/Controllers/GeneralController.php` | (approval flow) | ~2542–2568 | Same formula |
| `GeneralInfoController` | `app/Http/Controllers/Projects/GeneralInfoController.php` | `update()` | — | No arithmetic; persists request |

#### Blade Inline Calculations

| Blade | Path | Line | Arithmetic |
|-------|------|------|------------|
| Show/general_info | `resources/views/projects/partials/Show/general_info.blade.php` | 33 | `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` |
| Oldprojects/pdf | `resources/views/projects/Oldprojects/pdf.blade.php` | 794 | `amount_sanctioned ?? max(0, overall - (forwarded + local))` |

#### JavaScript Calculations

| File | Path | Function | Lines | Arithmetic |
|------|------|----------|-------|------------|
| scripts-edit | `resources/views/projects/partials/scripts-edit.blade.php` | `calculateProjectTotal()` | ~1036–1072 | Sums `this_phase` from `.budget-rows tr`; sets `overall_project_budget` |
| scripts-edit | `resources/views/projects/partials/scripts-edit.blade.php` | `calculateAmountSanctioned()` | ~1079–1135 | `amountSanctioned = overallBudget - combined`; `openingBalance = amountSanctioned + combined` |
| scripts | `resources/views/projects/partials/scripts.blade.php` | Same | Same pattern | Same (create flow) |
| budget-calculations | `public/js/budget-calculations.js` | `calculateProjectTotal()`, `calculateAmountSanctioned()` | — | Sum; `overall - combined` |

#### Service Usage

| Service | Path | Method | Purpose |
|---------|------|--------|---------|
| `ProjectFundFieldsResolver` | `app/Services/Budget/ProjectFundFieldsResolver.php` | `resolve()`, `resolveDevelopment()`, `resolveIIES()`, etc. | Compute 5 fund fields per project type |
| `BudgetSyncService` | `app/Services/Budget/BudgetSyncService.php` | `syncFromTypeSave()` | Write overall, forwarded, local to projects |
| `AdminCorrectionService` | `app/Services/Budget/AdminCorrectionService.php` | `normalizeManualValues()` | `sanctioned = overall - (forwarded + local)`; `opening = sanctioned + forwarded + local` |
| `DerivedCalculationService` | `app/Services/Budget/DerivedCalculationService.php` | `calculateRowTotal()`, `calculateProjectTotal()`, `calculateRemainingBalance()` | Row/phase totals; remaining balance |
| `BudgetCalculationService` | `app/Services/Budget/BudgetCalculationService.php` | `getBudgetsForReport()`, `calculateAmountSanctioned()` | Reports; contribution per row |

#### Validation Logic

| Request | Path | Rules | Fields |
|---------|------|-------|--------|
| `UpdateProjectRequest` | `app/Http/Requests/Projects/UpdateProjectRequest.php` | `overall_project_budget` min:0; `amount_forwarded` + `local_contribution` ≤ overall | overall_project_budget, amount_forwarded, local_contribution |
| `StoreProjectRequest` | `app/Http/Requests/Projects/StoreProjectRequest.php` | Same | Same |
| `UpdateGeneralInfoRequest` | `app/Http/Requests/Projects/UpdateGeneralInfoRequest.php` | Same | Same |

---

## 3. Edit vs View Discrepancy Table

| Field | Edit Source | View Source | DB Value Used? | Recomputed? | Risk Level | Mismatch? |
|-------|-------------|-------------|----------------|-------------|------------|-----------|
| **overall_project_budget** | JS: `calculateProjectTotal()` sums `this_phase` from `budgetsForEdit` (current phase). Initial: `$project->overall_project_budget` | `ProjectFundFieldsResolver::resolveDevelopment()`: `project.overall_project_budget` when non-zero; `sum(phaseBudgets.this_phase)` only when overall=0. Fallback: `$project->overall_project_budget` when resolver disabled | Yes (View) | Edit: Yes (JS). View: No (uses DB when non-zero) | **HIGH** | **Yes** |
| **amount_forwarded** | `$project->amount_forwarded` (user input) | `resolvedFundFields['amount_forwarded']` or `$project->amount_forwarded` | Yes | No | LOW | No |
| **local_contribution** | `$project->local_contribution` (user input) | `resolvedFundFields['local_contribution']` or `$project->local_contribution`. Resolver: `project.local_contribution` (Development) or type-specific (IIES/IES/ILP/IAH/IGE) | Yes | Resolver for type-specific | MEDIUM | Possible (type-specific) |
| **amount_requested** | `amount_sanctioned_preview` = JS `overall - (forwarded + local)` | Blade: `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` | No | Both | HIGH | **Yes** (if overall differs) |
| **amount_sanctioned** | JS: `calculateAmountSanctioned()` = `overall - (forwarded + local)` | `resolvedFundFields['amount_sanctioned']` = resolver formula | Yes (on approval) | Both | HIGH | **Yes** (if overall differs) |
| **opening_balance** | JS: `calculateAmountSanctioned()` = `amountSanctioned + (forwarded + local)` | `resolvedFundFields['opening_balance']` = resolver formula | Yes (on approval) | Both | HIGH | **Yes** (if overall differs) |

### Edit Page Chain

| Layer | Controller | Blade | JS |
|-------|------------|-------|-----|
| **Route** | `GET /executor/projects/{id}/edit` | — | — |
| **Controller** | `ProjectController@edit` — `app/Http/Controllers/Projects/ProjectController.php` ~1093 | — | — |
| **Controller** | `Project::with('budgets')`; `$budgetsForEdit = $project->budgets->where('phase', current_phase)` | — | — |
| **Blade** | — | `projects.partials.Edit.budget` — `resources/views/projects/partials/Edit/budget.blade.php` | — |
| **Blade** | — | Hidden: `overall_project_budget`; inputs: `amount_forwarded`, `local_contribution`; readonly: `amount_sanctioned_preview`, `opening_balance_preview` | — |
| **JS** | — | — | `@include('projects.partials.scripts-edit')` — `DOMContentLoaded` → `calculateProjectTotal()` → `calculateAmountSanctioned()` |

### View Page Chain

| Layer | Controller | Blade | Service |
|-------|------------|-------|---------|
| **Route** | `GET /executor/projects/{id}` | — | — |
| **Controller** | `ProjectController@show` — `app/Http/Controllers/Projects/ProjectController.php` ~787 | — | — |
| **Controller** | `$resolver->resolve($project, true)` when `resolver_enabled` or project type in `$typesWithTypeSpecificBudget` | — | — |
| **Service** | — | — | `ProjectFundFieldsResolver::resolve()` → `resolveForType()` |
| **Blade** | — | `projects.partials.Show.general_info` — `resources/views/projects/partials/Show/general_info.blade.php` | — |
| **Blade** | — | `$budget_overall = $resolvedFundFields['overall_project_budget'] ?? $project->overall_project_budget`; `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` | — |

---

## 4. Project Type Matrix

| Project Type | overall_project_budget formula | amount_requested formula | amount_sanctioned formula | opening_balance formula | Service used? | Risk |
|--------------|-------------------------------|---------------------------|---------------------------|-------------------------|---------------|------|
| **Development Projects** | `project.overall_project_budget` when ≠0; else `sum(project_budgets.this_phase)` current phase | `overall - (forwarded + local)` | `overall - (forwarded + local)` | `sanctioned + forwarded + local` | When `resolver_enabled` | HIGH |
| **Livelihood Development Projects** | Same | Same | Same | Same | Same | HIGH |
| **Residential Skill Training Proposal 2** | Same | Same | Same | Same | Same | HIGH |
| **CIC, CCI, Rural-Urban-Tribal** | Same | Same | Same | Same | Same | HIGH |
| **Institutional Ongoing Group Educational (IGE)** | `sum(project_ige_budgets.total_amount)` | `overall - (forwarded + local)` | `sum(amount_requested)` | `overall` | Yes | MEDIUM |
| **Individual - Livelihood Application (ILP)** | `sum(ilp_budget.cost)` | `overall - (forwarded + local)` | `first(amount_requested)` | `overall` | Yes | MEDIUM |
| **Individual - Access to Health (IAH)** | `sum(iah_budget_details.amount)` | `overall - (forwarded + local)` | `first(amount_requested)` | `overall` | Yes | MEDIUM |
| **Individual - Initial Educational (IIES)** | `iies_expenses.iies_total_expenses` | N/A | `iies_balance_requested` | `overall` | Yes | MEDIUM |
| **Individual - Ongoing Educational (IES)** | `ies_expenses.total_expenses` | N/A | `balance_requested` | `overall` | Yes | MEDIUM |

### Bypasses and Controller Arithmetic

| Project Type | Bypasses DerivedCalculationService? | Controller-level arithmetic? |
|--------------|-----------------------------------|------------------------------|
| All | Yes — Basic Info uses ProjectFundFieldsResolver, not DerivedCalculationService | Yes — CoordinatorController, GeneralController compute sanctioned/opening on approval |
| Development/LDP/RST/etc. | Resolver uses `project.overall_project_budget`; no DerivedCalculationService for overall | Same |

---

## 5. Architectural Risk Assessment

| Issue | Description | Risk Level |
|-------|-------------|------------|
| **Source-of-truth mismatch** | View uses `projects.overall_project_budget`; Edit uses JS sum of budget rows. When DB stale, View and Edit diverge. | **HIGH** |
| **Resolver fallback rarely used** | `ProjectFundFieldsResolver::resolveDevelopment()` lines 80–96: only sums budget items when `overall == 0`. When non-zero, trusts DB regardless of budget rows. | **HIGH** |
| **Blade arithmetic** | `amount_requested` computed inline in `Show/general_info.blade.php` line 33. Duplicates resolver logic; no single source. | **MEDIUM** |
| **Controller arithmetic** | CoordinatorController `approveProject()` ~1113–1148; GeneralController ~2542–2568. Duplicate formula logic. | **MEDIUM** |
| **Dual persistence paths** | GeneralInfoController saves `overall_project_budget` from request; BudgetSyncService syncs from resolver. Resolver can perpetuate stale value. | **MEDIUM** |
| **Config-dependent resolver** | Development Projects only get resolver when `config('budget.resolver_enabled')` = true. Otherwise View uses raw DB. Inconsistent. | **MEDIUM** |
| **Duplicate arithmetic** | `overall - (forwarded + local)` in: Resolver, AdminCorrectionService, JS, Blade, CoordinatorController, GeneralController. | **MEDIUM** |
| **No server-side validation of overall** | GeneralInfoController accepts `overall_project_budget` from request without verifying against budget rows. | **HIGH** |
| **DB value reliance vs derived** | View relies on DB when overall ≠ 0; Edit derives from rows. Inconsistent. | **HIGH** |
| **JS/backend mismatch** | JS formula matches `public/js/budget-calculations.js`; but backend resolver uses different source (DB vs sum). | **HIGH** |
| **Phase scoping** | Edit: `budgetsForEdit` = current phase only; Resolver fallback: current phase only; Show.budget table: all phases. | **LOW** |

---

## 6. Phase-wise Standardization Plan

### PHASE A — Canonical Formula Definition

Define single source of truth per field:

| Field | Canonical Formula | Authority |
|-------|-------------------|-----------|
| **overall_project_budget** | `sum(project_budgets.this_phase)` for current phase (Development/LDP/RST/etc.) or type-specific sum | Derived from budget items when available |
| **amount_forwarded** | User input | Stored |
| **local_contribution** | User input (institutional) or type-specific aggregation (IIES/IES/ILP/IAH/IGE) | Stored / type-specific |
| **amount_requested** | `overall - (forwarded + local)` | Derived only; never stored |
| **amount_sanctioned** | `overall - (forwarded + local)` | Derived on display; stored on approval |
| **opening_balance** | `sanctioned + forwarded + local` (= overall when valid) | Derived on display; stored on approval |

### PHASE B — Edit/View Unification

1. **Remove Blade arithmetic:** Delete `$amount_requested = max(0, $budget_overall - ...)` from `Show/general_info.blade.php`; use resolver output instead.
2. **Remove controller arithmetic:** CoordinatorController and GeneralController approval calc → delegate to `AdminCorrectionService::normalizeManualValues()` or equivalent.
3. **Centralize via service:** All formula application in `ProjectFundFieldsResolver` or `DerivedCalculationService`; Blade and Controller only display/persist.
4. **Resolver change:** `ProjectFundFieldsResolver::resolveDevelopment()` — when budgets exist, compute `overall` from `sum(this_phase)` (current phase) instead of `project.overall_project_budget` when non-zero.

### PHASE C — Project-Type Strategy Normalization

1. **Propose strategy abstraction:** Extend `ProjectFundFieldsResolver` to use same strategies as `BudgetCalculationService` (DirectMappingStrategy, SingleSourceContributionStrategy, MultipleSourceContributionStrategy) for Basic Info resolution.
2. **Type-based rule mapping:** `config/budget.field_mappings` already defines `model`, `strategy`, `fields` per type. Add `basic_info_overall_source` or equivalent.
3. **Unify resolver:** Ensure `resolveForType()` returns values from same source Edit uses per type.

### PHASE D — Guard Tests

| Test Name | Purpose | Expected Assertions |
|-----------|---------|---------------------|
| `BasicInfoBudgetParityTest` | View and Edit show same values for same project | `resolvedFundFields['overall_project_budget']` == JS sum of Edit rows for Development |
| `ProjectFundFieldsResolverUsesBudgetSumTest` | Resolver computes overall from budgets when available | When budgets exist and sum ≠ project.overall_project_budget, resolver returns sum |
| `BudgetFormulaParityTest` (existing) | JS and PHP formulas match | Keep existing |
| `BudgetDomainIsolationTest` (existing) | No arithmetic outside DerivedCalculationService | Keep existing |

**DO NOT implement.** Only propose.

---

## 7. Recommended Domain Model Direction

### Stored vs Derived

| Field | Recommendation | Rationale |
|-------|----------------|-----------|
| **overall_project_budget** | **Hybrid:** Derive from budget items when displaying; store only as cached snapshot when synced after budget save | Eliminates stale View; Edit and View use same source |
| **amount_forwarded** | **Stored** | User input; no derivation |
| **local_contribution** | **Stored / type-specific** | User input (institutional); type-specific aggregation (individual) |
| **amount_requested** | **Derived only** | Same as amount_sanctioned pre-approval; never store in `projects` |
| **amount_sanctioned** | **Derived on display; stored on approval** | Approval snapshot preserved; pre-approval always derived |
| **opening_balance** | **Derived on display; stored on approval** | Same |

### Single Source of Truth

- **Display:** `ProjectFundFieldsResolver` is the canonical source for View Basic Info. Must compute `overall` from budget items when available, not from DB.
- **Edit:** JS remains for UX; hidden field must be populated from same source. Ensure sync runs after budget save and writes correct value.
- **Approval:** CoordinatorController/GeneralController compute and store; consider delegating to AdminCorrectionService.

### File Reference Summary

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
| Project Controller | `app/Http/Controllers/Projects/ProjectController.php` |
| Budget config | `config/budget.php` |

---

**Audit status:** Complete. No code modified. Audit and planning only.
