# Financial Engine Consolidation Blueprint

**Document Version:** 1.0  
**Date:** 2025-02-09  
**Scope:** Read-only architectural analysis — no code changes.

---

## Executive Summary

The SAL Projects codebase currently operates **three independent financial engines** on the same project VIEW page:

1. **BudgetValidationService** → Budget Overview section
2. **ProjectFundFieldsResolver** → Basic Info section (when `resolver_enabled` or type-specific)
3. **Edit page logic** → Edit/budget.blade.php + scripts-edit.blade.php + budget-calculations.js

These engines compute overlapping financial fields with inconsistent phase handling, approval logic, and formula implementations. This blueprint provides a comprehensive consolidation plan to unify the financial domain.

---

## 1️⃣ Financial Engine Inventory

### Engine 1: BudgetValidationService

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Services/BudgetValidationService.php` |
| **Entry point** | `getBudgetSummary($project)` → `validateBudget()` → `calculateBudgetData()` |
| **Consumers** | `resources/views/projects/partials/Show/budget.blade.php` (Budget Overview) |
| **Fields computed** | `overall_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`, `total_expenses`, `approved_expenses`, `unapproved_expenses`, `remaining_balance`, `percentage_used`, `percentage_remaining`, `budget_items_total` |
| **Respects approval logic** | **No** — always recomputes `amount_sanctioned` and `opening_balance` from formula; does not use DB-stored values for approved projects |
| **Uses phase** | **No** — sums `budgets->sum('this_phase')` across **all phases** |
| **Uses DB stored values** | Partially — `overall_project_budget` when non-zero; `amount_forwarded`, `local_contribution` from `projects`; fallback `budgets->sum('this_phase')` when overall=0 |
| **Uses DerivedCalculationService** | **No** |
| **Risk classification** | **High** |
| **Duplication points** | `amount_sanctioned`, `opening_balance`, `remaining_balance`, `percentage_used`, `overall` formula |

---

### Engine 2: ProjectFundFieldsResolver

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Services/Budget/ProjectFundFieldsResolver.php` |
| **Entry point** | `resolve($project, $dryRun)` → `resolveForType()` → type-specific method |
| **Consumers** | Basic Info via `$resolvedFundFields` (passed from `ProjectController::show()`) when `resolver_enabled` OR project type in `$typesWithTypeSpecificBudget` |
| **Fields computed** | `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` |
| **Respects approval logic** | **Yes** — uses `BudgetSyncGuard::isApproved()`; for approved projects uses DB `amount_sanctioned` and `opening_balance` |
| **Uses phase** | **Yes** — Development: `budgets->where('phase', $currentPhase)`; `DerivedCalculationService::calculateProjectTotal($phaseBudgets->map('this_phase'))` |
| **Uses DB stored values** | Yes for approved (sanctioned, opening); for overall: `project.overall_project_budget` when non-zero; else sum of current-phase budgets |
| **Uses DerivedCalculationService** | **Yes** — for Development `calculateProjectTotal()`; other types use direct sums |
| **Risk classification** | **Medium** |
| **Duplication points** | `amount_sanctioned`/`opening_balance` formula when not approved; type-specific resolvers duplicate logic across IIES/IES/ILP/IAH/IGE |

---

### Engine 3: Edit Page Logic (Blade + JS)

| Attribute | Value |
|-----------|-------|
| **Location** | `resources/views/projects/partials/Edit/budget.blade.php`, `scripts-edit.blade.php`, `public/js/budget-calculations.js` |
| **Entry point** | DOM load + input events; `calculateProjectTotal()`, `calculateAmountSanctioned()` |
| **Consumers** | Edit project form (Development-type projects) |
| **Fields computed** | `overall_project_budget`, `amount_sanctioned_preview`, `opening_balance_preview`, row `this_phase`, `total_this_phase` |
| **Respects approval logic** | **No** — JS always recomputes; blade locks inputs when `budgetLockedByApproval` |
| **Uses phase** | **Effectively single phase** — Edit uses `phases[0]` only; `calculateProjectTotal()` sums all rows in DOM (equivalent to current phase rows) |
| **Uses DB stored values** | Initial values from `$project->*`; JS overwrites on load |
| **Uses DerivedCalculationService** | **No** — uses `window.BudgetCalculations` (JS parity module) |
| **Risk classification** | **Medium** |
| **Duplication points** | `amount_sanctioned = overall - (forwarded + local)`, `opening = sanctioned + combined`, `row total`, `project total` |

---

### Additional Budget-Related Services

| Service | Location | Role | Uses DerivedCalculationService |
|---------|----------|------|-------------------------------|
| **DerivedCalculationService** | `app/Services/Budget/DerivedCalculationService.php` | Row total, phase total, project total, remaining balance | N/A (is the engine) |
| **BudgetCalculationService** | `app/Services/Budget/BudgetCalculationService.php` | Strategy-based budget fetch for reports/exports | Indirect via strategies |
| **BudgetSyncGuard** | `app/Services/Budget/BudgetSyncGuard.php` | Approval checks, sync gates | No |
| **BudgetSyncService** | `app/Services/Budget/BudgetSyncService.php` | Sync type-specific to projects | No |
| **ProjectBudget model** | `app/Models/OldProjects/ProjectBudget.php` | `calculateTotalBudget()`, `calculateRemainingBalance()` | Yes |

---

## 2️⃣ Project Type Budget Format Mapping

| Project Type | Overall Source | Sanctioned Logic | Opening Logic | Uses Phase? | Special Rules |
|--------------|----------------|------------------|---------------|-------------|---------------|
| **Development Projects** | `project.overall_project_budget` or `sum(budgets.this_phase)` for current phase (Resolver); `budgets->sum('this_phase')` all phases (BVS) | Approved: DB; else `overall - (forwarded + local)` | Approved: DB; else `sanctioned + forwarded + local` | Yes (Resolver, Edit); BVS: no filter | Config: `phase_based: true`, `phase_selection: current` |
| **NEXT PHASE - DEVELOPMENT PROPOSAL** | Same as Development | Same | Same | Same | Same as Development |
| **Livelihood Development Projects** | `project_budgets` (DirectMapping) | Same formula | Same | Yes | Config: phase_based |
| **Residential Skill Training Proposal 2** | Same | Same | Same | Yes | Config: phase_based |
| **PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER** | Same | Same | Same | Yes | Config: phase_based |
| **CHILD CARE INSTITUTION** | Same | Same | Same | Yes | Config: phase_based |
| **Rural-Urban-Tribal** | Same | Same | Same | Yes | Config: phase_based |
| **Individual - Initial - Educational support (IIES)** | `iiesExpenses.iies_total_expenses` | `iies_balance_requested` | `overall` (Amount Forwarded = 0) | No | Type-specific: `ProjectIIESExpenses` |
| **Individual - Ongoing Educational support (IES)** | `iesExpenses.first().total_expenses` | `balance_requested` | `overall` | No | Type-specific: `ProjectIESExpenses` |
| **Individual - Livelihood Application (ILP)** | `ilpBudget->sum('cost')` | `first().amount_requested` | `overall` | No | Type-specific: `ProjectILPBudget` |
| **Individual - Access to Health (IAH)** | `iahBudgetDetails->sum('amount')` | `first().amount_requested` | `overall` | No | Type-specific: `ProjectIAHBudgetDetails` |
| **Institutional Ongoing Group Educational proposal (IGE)** | `igeBudget->sum('total_amount')` | `sum(amount_requested)` | `overall` | No | Type-specific: `ProjectIGEBudget` |

### Conditional Branching in Resolver / Controller

- **ProjectController::show()**: `$shouldResolveForShow = config('budget.resolver_enabled') || in_array($project->project_type, $typesWithTypeSpecificBudget)`
- **ProjectFundFieldsResolver**: `resolveForType()` → `resolveIndividualOrIge()` or `resolveDevelopment()` based on `project_type`
- **ProjectFundFieldsResolver::resolveDevelopment()**: If `phaseBudgets->isNotEmpty()` → use DerivedCalculationService; else `project.overall_project_budget`
- **BudgetValidationService**: Always Development-style; no type branching; uses `projects.local_contribution` for all types

---

## 3️⃣ Phase Usage Audit

### Locations Where Phase Is Used

| Location | Usage | Classification | Recommendation |
|----------|-------|----------------|----------------|
| `ProjectFundFieldsResolver::resolveDevelopment()` | `$project->current_phase`, `budgets->where('phase', $currentPhase)` | **Financial dependency** | Migrate to single-phase; treat all budgets as current |
| `ProjectController::edit()` | `$budgetsForEdit = $project->budgets->where('phase', (int) ($project->current_phase ?? 1))` | **Structural dependency** | Remove phase filter; use all budgets |
| `BudgetController` | `->where('phase', (int) ($project->current_phase ?? 1))`, `phase` in fillable | **Structural dependency** | Phase column retained for migration; filter removed from financial logic |
| `BudgetValidationService::calculateBudgetData()` | `budgets->sum('this_phase')` — **no phase filter** | **Financial** | Aligns with "no phase" but differs from Resolver/Edit |
| `scripts-edit.blade.php` | `phases[0]`, `current_phase` select (UI), `phaseIndex = 0` | **UI-only** | Remove phase select; simplify to single-phase form |
| `Edit/budget.blade.php` | `phases[0][budget]` | **UI-only** | Simplify to single-phase structure |
| `Show/budget.blade.php` footer | `$project->budgets->sum('this_phase')` | **Financial** | No phase filter; sums all |
| `ExportController` | `Amount Sanctioned in Phase {$phase}`; `budgets->map('this_phase')` | **UI / Export** | Migrate to single-phase wording |
| `DevelopmentProjectController` (Quarterly) | `->where('phase', $highestPhase)` | **Financial dependency** | Migrate to single-phase |
| `CoordinatorController::approveProject()` | `$project->budgets->map('this_phase')` — no phase filter | **Financial** | Keep; no phase filter |
| `GeneralController` | No direct phase filter in budget calc | — | — |
| `ProjectBudget` model | `phase` column | **Structural** | Retain for DB compatibility; ignore in financial engine |
| `config/budget.php` | `phase_based`, `phase_selection` per type | **Config** | Remove or set to false |
| `config/decimal_bounds.php` | `this_phase` bounds | **Validation** | Retain |

### Recommendation Summary

| Classification | Action |
|----------------|--------|
| **Structural dependency** | Phase column kept in DB; no financial filtering. Migration: treat all rows as phase 1 or "current". |
| **UI-only dependency** | Remove phase select, `phases[0]` → flat `budget[]` structure. |
| **Financial dependency** | Remove `where('phase', ...)` from all financial resolvers; use `budgets` unfiltered (or filter only for legacy display if needed). |

### What Can Be Safely Removed

- Phase select dropdown from Edit form
- `current_phase` from financial resolution logic
- `phase_selection` and `phase_based` from config (or set to false)

### What Must Be Migrated

- `ProjectFundFieldsResolver::resolveDevelopment()` — stop filtering by phase
- `ProjectController::edit()` — stop filtering `$budgetsForEdit` by phase
- `DevelopmentProjectController` — stop filtering by highest phase
- Export wording: "Amount Sanctioned in Phase X" → "Amount Sanctioned"

### What Must Be Rewritten

- Budget table structure in Edit form (phases array → flat budget rows)
- Any report/export that assumes multi-phase breakdown

---

## 4️⃣ Approval Logic Audit

### Canonical Approval Policy

**For approved projects (`ProjectStatus::isApproved($status)`):**

- `amount_sanctioned` and `opening_balance` are **stored in DB** at approval time and are the **source of truth**.
- These values must **not** be recomputed from formulas when displaying.
- Budget edits are locked (`budgetLockedByApproval`).

**For non-approved projects:**

- `amount_sanctioned` and `opening_balance` are **derived** from formulas:  
  `sanctioned = overall - (forwarded + local)`, `opening = sanctioned + forwarded + local`.

### Locations Where Approved Projects Behave Differently

| Location | Behavior | Violation? |
|----------|----------|------------|
| **ProjectFundFieldsResolver** | Uses `project->amount_sanctioned` and `project->opening_balance` when `BudgetSyncGuard::isApproved()` | **Correct** |
| **BudgetValidationService** | Always recomputes; never checks approval | **Violation** — can diverge from DB for approved projects |
| **Edit page JS** | Always recomputes; blade locks inputs when approved | **Partial** — no recompute if locked, but initial values may differ from resolver |
| **CoordinatorController::approveProject()** | Computes and saves sanctioned/opening on approval | **Correct** — writes at approval |
| **GeneralController** (approval flow) | Same pattern | **Correct** |

### DB Trust vs Recomputation

| Consumer | Trusts DB for approved? | Recomputes? |
|----------|-------------------------|-------------|
| Basic Info (Resolver) | Yes | No when approved |
| Budget Overview (BVS) | No | Always |
| Edit page | N/A (locked) | On load (before lock) |
| CoordinatorController reports | Uses `amount_sanctioned ?? overall_project_budget` | No |
| GeneralController reports | Same | No |
| ProvincialController | Same | No |
| ExecutorController | Same | No |

### Violations

1. **BudgetValidationService** ignores approval; always recomputes sanctioned and opening → can show different values than Basic Info and DB.
2. **Show/budget.blade.php** uses BVS for Budget Overview and Resolver (via Basic Info) for fund fields → **two different sources** on same page.

---

## 5️⃣ Formula Duplication Map

| Formula | File | Approx Line | Service/JS | Rounding |
|---------|------|-------------|------------|----------|
| `sanctioned = overall - (forwarded + local)` | BudgetValidationService.php | 57 | PHP | None (float) |
| | ProjectFundFieldsResolver.php | 110 | PHP | `round(..., 2)` in normalize |
| | scripts-edit.blade.php | 1109 | JS BudgetCalculations.calculateAmountSanctioned | `toFixed(2)` |
| | CoordinatorController.php | 1143-1144 | PHP | None |
| | GeneralController.php | 2564 | PHP | None |
| | Show/general_info.blade.php | 33 | Blade `$amount_requested = max(0, $budget_overall - ($budget_forwarded + $budget_local))` | format_indian_currency |
| `opening = sanctioned + forwarded + local` | BudgetValidationService.php | 59 | PHP | None |
| | ProjectFundFieldsResolver.php | 112 | PHP | `round(..., 2)` |
| | scripts-edit.blade.php | 1109 | JS | `toFixed(2)` |
| | CoordinatorController.php | 1152 | PHP | None |
| | GeneralController.php | 2565 | PHP | None |
| `remaining = opening - totalExpenses` | BudgetValidationService.php | 94 | PHP | None |
| | DerivedCalculationService.php | 75 | `calculateRemainingBalance()` | None |
| `utilization = (expenses / opening) * 100` | BudgetValidationService.php | 97 | PHP | NumberFormatHelper 1 dec |
| | CoordinatorController.php | 603, 1738, 1784, 2080, etc. | PHP | `round(..., 2)` or `round(..., 1)` |
| | GeneralController.php | 2233, 2263, 3534, etc. | PHP | `round(..., 2)` |
| | ProvincialController.php | 522, 2171 | PHP | varies |
| | ExecutorController.php | 722 | PHP | `round(..., 2)` |
| `overall = sum(budgets)` | BudgetValidationService.php | 51-52 | `budgets->sum('this_phase')` | None |
| | ProjectFundFieldsResolver.php | 101 | DerivedCalculationService::calculateProjectTotal | round in normalize |
| | scripts-edit.blade.php | 1013 | BudgetCalculations.calculateProjectTotal | `toFixed(2)` |
| | Show/budget.blade.php footer | 267 | Blade `$project->budgets->sum('this_phase')` | format_indian |
| | CoordinatorController.php | 1120 | calculationService->calculateProjectTotal | — |
| | GeneralController.php | 2549 | calculationService->calculateProjectTotal | — |
| | ExportController.php | 2512, 2559 | calculationService->calculateProjectTotal | formatIndianCurrency |

### Rounding Inconsistencies

- Utilization: 1 decimal (BudgetValidationService) vs 2 decimals (Coordinator, General, Provincial, Executor).
- Amounts: Mix of no rounding, `round(..., 2)`, and `toFixed(2)`.

---

## 6️⃣ Consolidation Architecture Plan

### Target Folder Structure

```
app/Domain/Budget/
├── ProjectFinancialResolver.php      # Single entry point
├── Strategies/
│   ├── DevelopmentBudgetStrategy.php
│   ├── IIESBudgetStrategy.php
│   ├── IESBudgetStrategy.php
│   ├── ILPBudgetStrategy.php
│   ├── IAHBudgetStrategy.php
│   └── IGEBudgetStrategy.php
├── DerivedCalculationService.php     # Moved/kept; only math engine
├── BudgetValidationAggregator.php    # Optional; aggregates for reports; no calculation
└── BudgetSyncGuard.php               # Approval/sync guards (unchanged)
```

### Component Responsibilities

| Component | Responsibility |
|-----------|----------------|
| **ProjectFinancialResolver** | Single entry point. Delegates to strategy by project type. Returns `resolvedFinancials` (overall, forwarded, local, sanctioned, opening, remaining, utilization). Uses DerivedCalculationService for all arithmetic. Respects approval (DB for sanctioned/opening when approved). No phase filter. |
| **Strategy (per project type)** | Knows where to read overall, forwarded, local from (DB, type tables). Calls DerivedCalculationService for sums and remaining. Returns normalized array. |
| **DerivedCalculationService** | **Only** math: `calculateRowTotal`, `calculatePhaseTotal`, `calculateProjectTotal`, `calculateRemainingBalance`, `calculateAmountSanctioned`, `calculateUtilization`. No project/DB logic. |
| **BudgetValidationAggregator** | Receives `resolvedFinancials` + expenses; produces validation warnings/errors. Does NOT compute financial fields. Can remain as thin wrapper over ProjectFinancialResolver + expense fetch. |
| **JS (budget-calculations.js)** | **Display/UX only**. Mirrors DerivedCalculationService formulas for Edit form live preview. Does NOT persist. On submit, backend recomputes from submitted values. |

### View Consumption

- All views receive `$project->resolvedFinancials` (or equivalent passed from controller).
- `ProjectController::show()` calls `ProjectFinancialResolver::resolve($project)` once; passes result to view.
- Show/budget.blade.php and Show/general_info.blade.php both use `resolvedFinancials`.
- Edit page: JS for live preview; on load, initial values come from `resolvedFinancials` or submitted data.

### Elimination of BudgetValidationService as Calculator

- BudgetValidationService’s `calculateBudgetData()` logic is removed.
- Budget overview receives data from `ProjectFinancialResolver` + expense aggregation.
- Validation rules (negative balance, totals match, over-budget) apply to `resolvedFinancials` + expenses; no separate calculation path.

---

## 7️⃣ Migration Roadmap (Conservative)

### Phase A — Strategy Introduction

| Step | Action | Risk | Mitigation | Test Coverage |
|------|--------|------|------------|---------------|
| A1 | Create `app/Domain/Budget/ProjectFinancialResolver` with strategy registry | Low | Feature flag; no consumer changes yet | Unit: resolver returns correct shape |
| A2 | Implement strategies for each project type (Development, IIES, IES, ILP, IAH, IGE) | Medium | Port logic from ProjectFundFieldsResolver; compare output | Feature: parity with ProjectFundFieldsResolver |
| A3 | Add `calculateAmountSanctioned` and `calculateUtilization` to DerivedCalculationService | Low | Pure functions | Unit: formula tests |
| A4 | Wire ProjectFinancialResolver to use DerivedCalculationService for all math | Low | No phase filter in new resolver | Unit + integration |

### Phase B — Resolver Delegation

| Step | Action | Risk | Mitigation | Test Coverage |
|------|--------|------|------------|---------------|
| B1 | In ProjectController::show(), call ProjectFinancialResolver when resolver_enabled or type-specific | Medium | Side-by-side: log both resolver outputs; compare | Feature: resolvedFinancials matches resolvedFundFields |
| B2 | Pass `resolvedFinancials` to view; Basic Info uses it | Medium | A/B test or gradual rollout | Feature: Basic Info unchanged |
| B3 | Deprecate ProjectFundFieldsResolver; redirect to ProjectFinancialResolver | Low | Alias or adapter | Regression |

### Phase C — BudgetValidationService Redirection

| Step | Action | Risk | Mitigation | Test Coverage |
|------|--------|------|------------|---------------|
| C1 | Add expense aggregation to ProjectFinancialResolver or separate service | Low | Reuse existing report/accountDetails logic | Unit: expense sum matches |
| C2 | BudgetValidationService::getBudgetSummary() calls ProjectFinancialResolver + expense agg; returns validation structure | High | Parity logging: compare old vs new budget_data | Feature: Budget Overview values unchanged |
| C3 | Remove calculateBudgetData() from BudgetValidationService | High | Only after C2 verified | Regression suite |

### Phase D — View Unification

| Step | Action | Risk | Mitigation | Test Coverage |
|------|--------|------|------------|---------------|
| D1 | Show/budget.blade.php consumes resolvedFinancials (or equivalent from controller) | Medium | Ensure expense breakdown still correct | Visual + snapshot |
| D2 | Show/general_info.blade.php uses same source | Low | Remove resolvedFundFields; use resolvedFinancials | Feature |
| D3 | Ensure Budget Overview and Basic Info show identical fund values when from same source | Low | Assert in test | Parity test |

### Phase E — Phase Removal

| Step | Action | Risk | Mitigation | Test Coverage |
|------|--------|------|------------|---------------|
| E1 | Remove phase filter from ProjectFinancialResolver strategies | Medium | All budgets treated as current | Feature: overall = sum(all this_phase) |
| E2 | ProjectController::edit() returns all budgets; remove phase filter | Medium | Edit form shows all rows | Feature |
| E3 | Simplify Edit form: phases[0] → budget[] | Medium | Form submission format change | E2E |
| E4 | Remove phase select from Edit UI | Low | Config or gradual | Visual |
| E5 | Update ExportController, DevelopmentProjectController to not filter by phase | Medium | Export/report wording | Feature |

### Phase F — Deletion of Legacy Duplication

| Step | Action | Risk | Mitigation | Test Coverage |
|------|--------|------|------------|---------------|
| F1 | Remove inline arithmetic from CoordinatorController, GeneralController approval flows | Medium | Delegate to ProjectFinancialResolver or DerivedCalculationService | Feature: approval still correct |
| F2 | Remove blade arithmetic from Show/general_info ($amount_requested) | Low | Use resolvedFinancials | Feature |
| F3 | Reduce JS to display-only; ensure submit uses backend recomputation | Medium | Validation tests on submitted data | E2E |
| F4 | Remove ProjectFundFieldsResolver (after full migration) | Low | Delete; update DI | Regression |
| F5 | Simplify BudgetValidationService to aggregator only | Low | Thin wrapper | Unit |

---

## 8️⃣ Post-Migration Invariants

The following rules MUST hold after consolidation:

| # | Invariant | Enforcement |
|---|-----------|-------------|
| 1 | **No controller arithmetic** | Controllers MUST NOT compute sanctioned, opening, remaining, utilization. They call ProjectFinancialResolver or services only. |
| 2 | **No blade arithmetic** | Blades MUST NOT contain formulas (e.g. `max(0, $a - $b)`). They display values from resolver. |
| 3 | **No JS as source of truth** | JavaScript computes for live preview only. Persisted values come from backend. |
| 4 | **No phase in financial engine** | ProjectFinancialResolver and DerivedCalculationService MUST NOT filter or branch on phase. |
| 5 | **Approval rule centralized** | BudgetSyncGuard::isApproved() (or equivalent) is the single gate. Approved projects use DB sanctioned/opening. |
| 6 | **DerivedCalculationService is the only math engine** | All arithmetic (row total, project total, remaining, sanctioned, utilization) goes through DerivedCalculationService. |
| 7 | **Single entry point for project financials** | ProjectFinancialResolver is the sole producer of resolved financial fields for display. |
| 8 | **Rounding consistency** | All monetary values: 2 decimals. Utilization: 2 decimals. Standardize in DerivedCalculationService and formatting helpers. |

---

## Appendix: Reference Files

| Purpose | Path |
|---------|------|
| BudgetValidationService | `app/Services/BudgetValidationService.php` |
| ProjectFundFieldsResolver | `app/Services/Budget/ProjectFundFieldsResolver.php` |
| DerivedCalculationService | `app/Services/Budget/DerivedCalculationService.php` |
| BudgetSyncGuard | `app/Services/Budget/BudgetSyncGuard.php` |
| Budget config | `config/budget.php` |
| Show/budget | `resources/views/projects/partials/Show/budget.blade.php` |
| Show/general_info | `resources/views/projects/partials/Show/general_info.blade.php` |
| Edit/budget | `resources/views/projects/partials/Edit/budget.blade.php` |
| scripts-edit | `resources/views/projects/partials/scripts-edit.blade.php` |
| budget-calculations.js | `public/js/budget-calculations.js` |
| ProjectController show | `app/Http/Controllers/Projects/ProjectController.php` (show, edit) |
| CoordinatorController approve | `app/Http/Controllers/CoordinatorController.php` |
| GeneralController | `app/Http/Controllers/GeneralController.php` |
| ProvincialController | `app/Http/Controllers/ProvincialController.php` |
| ExecutorController | `app/Http/Controllers/ExecutorController.php` |
| ProjectType | `app/Constants/ProjectType.php` |

---

*End of blueprint.*
