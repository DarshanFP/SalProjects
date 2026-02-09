# Phase 2.4 — Derived Calculation Consolidation (Design)

**Date**: 2026-02-09  
**Status**: Design Only — No implementation. No production code modifications.  
**Mode**: Documentation only.

---

## Precondition

Phase 2.4 assumes the following stabilized baseline (Phase 2.3 and prior):

- Single active phase = `$project->current_phase`
- Numeric bounds centralized via `BoundedNumericService`; `config/decimal_bounds.php`
- Budget row formula: `rate_quantity × rate_multiplier × rate_duration`
- Phase logic stabilized; no array-index phase inference

---

## 1. Current Derived Calculation Map

### Where Row Totals Are Calculated

| Layer | File | Function / Location | Formula |
|-------|------|--------------------|---------|
| JS | scripts.blade.php | calculateBudgetRowTotals | `rateQuantity × rateMultiplier × rateDuration` |
| JS | scripts-edit.blade.php | calculateBudgetRowTotals | `rateQuantity × rateMultiplier × rateDuration` |
| Backend (Model) | ProjectBudget.php | calculateTotalBudget | `rate_quantity × rate_multiplier × rate_duration` |

### Where Phase Totals Are Calculated

| Layer | File | Function / Location | Formula |
|-------|------|--------------------|---------|
| JS | scripts.blade.php | calculateTotalAmountSanctioned | `sum(thisPhaseValue)` over rows |
| JS | scripts-edit.blade.php | calculateTotalAmountSanctioned | `sum(thisPhaseValue)` over rows |
| Backend | ProjectFundFieldsResolver | resolveDevelopment | `sum(phaseBudgets.this_phase)` |
| Backend | ExportController | addBudgetSection | `$budgets->sum('this_phase')` |
| Backend | GeneralInfoController | — | Uses `overall_project_budget` from request |

### Where Sanctioned Totals Are Calculated

| Layer | File | Function / Location | Formula |
|-------|------|--------------------|---------|
| JS | scripts-edit.blade.php | calculateBudgetFields | `amountSanctioned = overallBudget - (amountForwarded + localContribution)` |
| Backend | ProjectFundFieldsResolver | resolveDevelopment | `sanctioned = overall - (forwarded + local)` |
| Backend | AdminCorrectionService | normalizeManualValues | `sanctioned = round(max(0, overall - (forwarded + local)), 2)` |
| Backend | SingleSourceContributionStrategy | getBudgets | `amount_sanctioned = max(0, amount - (contribution/totalRows))` per row |
| Backend | MultipleSourceContributionStrategy | getBudgets | `amount_sanctioned = max(0, amount - (sum(sources)/totalRows))` per row |

### Where Exports Derive Totals

| Layer | File | Location | Formula |
|-------|------|----------|---------|
| Backend | ExportController | addBudgetSection | `$budgets->sum('this_phase')`, `$budgets->sum('rate_quantity')`, etc. |
| Backend | ExportController | addBudgetSection | Per-phase: `$budgets->sum('this_phase')` |
| Backend | BudgetExportController | — | Aggregates from project data |

### Duplication Points

| Formula | Duplicated In | Risk |
|---------|---------------|------|
| Row total (q×m×d) | JS (2 scripts), ProjectBudget model | Formula drift if one changes |
| Phase total (sum of this_phase) | JS, ProjectFundFieldsResolver, ExportController | Phase filter may differ |
| amount_sanctioned (overall - forwarded - local) | JS, ProjectFundFieldsResolver, AdminCorrectionService | Rounding and clamping differ |
| amount_sanctioned per row (contribution split) | SingleSourceContributionStrategy, MultipleSourceContributionStrategy | Consistent today; shared logic desired |

---

## 2. Architectural Risks

### Client Manipulation Risk

- **BudgetController**: Accepts `this_phase` from request; clamps only. No server-side recalculation.
- **GeneralInfoController**: Accepts `overall_project_budget` from request.
- **IIESExpensesController**: Accepts `iies_total_expenses`, `iies_balance_requested` from request.
- **IESExpensesController**: Accepts `total_expenses`, `balance_requested` from request.

**Impact**: Tampered form submissions can persist incorrect derived totals. No server verification.

### Divergence Between JS and Backend

- JS computes `this_phase` as `q×m×d`; backend trusts submitted value.
- Model `calculateTotalBudget` now matches JS formula; but controller never uses it for persistence.
- If JS is disabled or fails, user can submit arbitrary values; backend does not recompute.

### Report Drift Risk

- Export uses `$budgets->sum('this_phase')` — reads persisted values.
- ProjectFundFieldsResolver uses `sum(phaseBudgets.this_phase)` — same source but different phase filter.
- CoordinatorController, GeneralController, ExecutorController compute utilization from `sum(total_expenses)` / budget — multiple implementations.

### Multi-Layer Duplication Risk

- Same formula in JS, model, and (implicitly) controller expectations.
- Same aggregation logic in 6+ controllers and services.
- Rounding and clamping applied inconsistently (1 vs 2 decimals; different max(0,...) locations).

---

## 3. Canonical Authority Decision

### Option A — Backend as Canonical Authority

Controllers and models compute derived values directly. No shared service.

**Pros**: Simple; no new abstraction.  
**Cons**: Duplication remains; no single source of truth; controllers already diverge.

### Option B — Shared Service as Canonical Authority

A single `DerivedCalculationService` provides pure functions. All layers (controllers, models, exporters, resolvers) delegate to it.

**Pros**:
- Single source of truth for every derived formula
- Pure functions; no DB access; easily unit-tested
- Controllers stop trusting client; call service before persistence
- Export and reports use same formulas
- Integrates with BoundedNumericService for clamping

**Cons**: New abstraction; requires controller and model changes.

### Chosen Approach: **Option B — Shared Service as Canonical Authority**

**Justification**:

1. **Eliminates client trust**: Controllers must ignore client-submitted derived totals and call the service. Tampering and JS failures no longer affect persisted values.

2. **Eliminates formula drift**: One function per derived field. JS can be aligned last; backend is authoritative for persistence.

3. **Aligns with Phase 2.3**: BoundedNumericService and `config/decimal_bounds.php` already centralize bounds. DerivedCalculationService complements this by centralizing formulas; clamping remains the responsibility of the caller or a thin wrapper.

4. **Testability**: Pure functions with scalar/array inputs and outputs are trivial to unit-test. No database or HTTP required.

5. **Migration path**: Introduce service; delegate model; enforce controller recalculation; align JS last — without breaking existing behavior if tests are frozen first.

---

## 4. Proposed DerivedCalculationService Contract (Design Only)

**Namespace**: `App\Services\DerivedCalculation\DerivedCalculationService` (or equivalent)  
**Responsibility**: Pure computation only. No database access. No persistence.

### Method Signatures and Responsibilities

| Method | Signature | Responsibility |
|--------|-----------|----------------|
| **calculateRowTotal** | `calculateRowTotal(float $rateQuantity, float $rateMultiplier, float $rateDuration): float` | Returns `q × m × d` for a single budget row. Does not clamp. |
| **calculatePhaseTotal** | `calculatePhaseTotal(iterable $rows): float` | Accepts rows with `rate_quantity`, `rate_multiplier`, `rate_duration`. Returns sum of `calculateRowTotal` for each row. |
| **calculateSanctionedTotal** | `calculateSanctionedTotal(iterable $rows): float` | For Development: sum of `this_phase` over rows. For project-level: `overall - (forwarded + local)`. Clarify scope per project type. |
| **validateClientSubmittedRowTotal** | `validateClientSubmittedRowTotal(float $submitted, float $rateQuantity, float $rateMultiplier, float $rateDuration, float $tolerance = 0.01): bool` | Returns true if `submitted` is within `tolerance` of canonical `q×m×d`. Used for optional client-side validation warning; not for rejection. |
| **clampIfRequired** | `clampIfRequired(float $value, string $table, string $field): float` | Delegates to BoundedNumericService. Returns clamped value for persistence. Integrates Phase 2.3 bounds from `config/decimal_bounds.php`. |

### Additional Methods (Design Only)

| Method | Signature | Responsibility |
|--------|-----------|----------------|
| **calculateNextPhase** | `calculateNextPhase(float $rateQuantity, float $rateMultiplier, float $rateDuration, float $rateIncrease): float` | Returns `(q + rate_increase) × m × d` if next_phase is ever reinstated. Document as deferred. |
| **calculateContributionPerRow** | `calculateContributionPerRow(float $contribution, int $totalRows): float` | Returns `contribution / totalRows`. Used by contribution strategies. |
| **calculateAmountSanctionedPerRow** | `calculateAmountSanctionedPerRow(float $originalAmount, float $contributionPerRow): float` | Returns `max(0, originalAmount - contributionPerRow)`. |

### Design Principles

- **Stateless**: No instance state. All methods pure.
- **No DB**: Service receives values; returns values. Controllers handle persistence.
- **Bounds integration**: `clampIfRequired` calls BoundedNumericService; formula methods do not clamp unless documented.
- **Precision**: Outputs use 2 decimal places where applicable; rounding strategy documented.

---

## 5. Integration Plan (No Code Yet)

### Model Delegation Strategy

- **ProjectBudget**: `calculateTotalBudget()` delegates to `DerivedCalculationService::calculateRowTotal()`. No formula in model.
- **ProjectBudget**: `calculateRemainingBalance()` uses `this_phase` (persisted) minus `sum(dpAccountDetails.total_expenses)`. If `this_phase` is server-recalculated before persistence, no model formula change needed for remaining balance.

### Controller Validation Strategy

- **BudgetController**: Before `ProjectBudget::create()` / `update()`:
  1. Read `rate_quantity`, `rate_multiplier`, `rate_duration` from request.
  2. Call `DerivedCalculationService::calculateRowTotal()`.
  3. Call `DerivedCalculationService::clampIfRequired()` with `project_budgets.this_phase`.
  4. Persist the result as `this_phase`; ignore `$budget['this_phase']` for persistence.

- **GeneralInfoController**: Recompute `overall_project_budget` from sum of budget rows (or from phase totals) via service before persistence; do not trust client.

- **IIESExpensesController**, **IESExpensesController**: Recompute `total_expenses` / `balance_requested` from row amounts via service before persistence.

### Export Integration

- **ExportController**: When building budget tables, use `$budgets->sum('this_phase')` (persisted values). No change required if controller already recalculates before save. Export reads DB; DB is correct after controller changes.

### Report Integration

- **ProjectFundFieldsResolver**: May call `DerivedCalculationService::calculatePhaseTotal()` when resolving `overall` from DB rows, for consistency. Or continue reading persisted `this_phase`; both are valid if persistence is correct.

- **Utilization**: Centralize utilization formula in service or a dedicated helper; standardize rounding (e.g. 2 decimals).

### JS Alignment Plan (Last)

- **Order**: After backend is authoritative, align JS.
- **Behavior**: JS `calculateBudgetRowTotals` continues to compute `q×m×d` for UX (instant feedback). Form still submits inputs; backend ignores `this_phase` and recomputes.
- **Optional**: Add `validateClientSubmittedRowTotal` equivalent in JS for client-side warning if submitted total differs from computed (e.g. tampering).
- **Cleanup**: Remove legacy `calculateBudgetTotals` rate_increase logic during consolidation.

---

## 6. Migration Strategy

### Step 1 — Freeze Tests

- Run and record all budget-related tests: DevelopmentBudgetPhaseFreezeTest, Phase1_Budget_ValidationTest, any export/report tests.
- Ensure green baseline. No behavior changes in this step.

### Step 2 — Introduce Service

- Create `DerivedCalculationService` with `calculateRowTotal`, `calculatePhaseTotal`, `calculateSanctionedTotal`, `validateClientSubmittedRowTotal`, `clampIfRequired`.
- Add unit tests for each method. No integration yet.

### Step 3 — Delegate Model

- Change `ProjectBudget::calculateTotalBudget()` to call `DerivedCalculationService::calculateRowTotal()`.
- Ensure model tests pass. No controller changes yet.

### Step 4 — Enforce Controller Recalculation

- Change BudgetController to recompute `this_phase` from inputs; ignore client value.
- Persist service result. Re-run tests; fix regressions.

### Step 5 — Extend to Other Controllers

- Apply same pattern to GeneralInfoController, IIESExpensesController, IESExpensesController.

### Step 6 — Align JS (Last)

- Ensure JS formula matches service. Remove dead rate_increase logic.
- JS remains for UX; backend remains authoritative for persistence.

---

## 7. Non-Goals

Phase 2.4 Derived Calculation Consolidation does **NOT**:

- Modify database schema or migrations
- Change validation rules (NumericBoundsRule, StoreBudgetRequest, UpdateBudgetRequest)
- Refactor JS beyond alignment with canonical formula
- Remove or change `rate_increase` column (schema unchanged)
- Implement multi-phase editing or next_phase logic
- Change BoundedNumericService or config/decimal_bounds.php
- Modify ExportController layout or report structure
- Touch IAH, ILP, IGE budget flows beyond documented scope
- Create new routes or API endpoints
- Change project type detection or phase inference logic
