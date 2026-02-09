# Financial Domain v2 Architecture

**Document Version:** 1.0  
**Date:** 2025-02-09  
**Scope:** Official architecture documentation — reflects current implementation.

---

## 1. Overview

### Why v2 Was Created

The legacy codebase operated **three independent financial engines** with overlapping responsibilities:

1. **BudgetValidationService** — Budget Overview section (summed all phases, ignored approval)
2. **ProjectFundFieldsResolver** — Basic Info section (type-specific branching)
3. **Edit page logic** — Blade + `budget-calculations.js` (duplicated formulas)

These engines computed `amount_sanctioned`, `opening_balance`, `remaining_balance`, and utilization inconsistently. Different logic paths produced different results for the same project.

### Problems with Legacy Arithmetic

- **Fallback sprawl**: `$project->amount_sanctioned ?? $project->overall_project_budget` repeated across controllers and views
- **Inline math**: `$budget - $expenses`, `($expenses / $budget) * 100` scattered in aggregation loops
- **Direct column sums**: `->sum('amount_sanctioned')`, `->sum('overall_project_budget')` bypassing canonical logic
- **No approval awareness**: Approved projects should use DB-stored values; legacy often recomputed from formula

### Phase-Based Budgeting Removal

Phase-based filtering (`where('phase', $currentPhase)`) was retained in `PhaseBasedBudgetStrategy` for backward compatibility, but the goal is centralization. Future migration may treat all budget rows as current phase. The phase column remains in the DB for structural compatibility.

### Centralization Goal

- **Single source of truth** for project-level fund fields (`overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance`)
- **Pure math engine** (`DerivedCalculationService`) for remaining balance and utilization
- **Zero arithmetic** in controllers, Blade, or aggregation closures
- **Approval-safe behavior**: Approved projects use DB values; non-approved use derived formulas

---

## 2. Core Components

### 2.1 ProjectFinancialResolver

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Domain/Budget/ProjectFinancialResolver.php` |
| **Namespace** | `App\Domain\Budget` |
| **Responsibility** | Single entry point for resolving project-level fund fields |

**Design Principles:**

- **No arithmetic** — Contains zero calculations; delegates to strategies
- **Delegation only** — Selects strategy by `project_type`, delegates `resolve($project)`
- **Single source of truth** — All consumers must use this resolver for budget/opening values
- **Normalization** — Ensures all returned values are non-negative floats rounded to 2 decimals

**Returns:**

```php
array{
    overall_project_budget: float,
    amount_forwarded: float,
    local_contribution: float,
    amount_sanctioned: float,
    opening_balance: float
}
```

**Strategy selection:**

- Phase-based types → `PhaseBasedBudgetStrategy`
- Direct-mapped individual types → `DirectMappedIndividualBudgetStrategy`
- Default/unknown → `PhaseBasedBudgetStrategy`

---

### 2.2 Financial Strategies

| File | Strategy | Project Types |
|------|----------|---------------|
| `app/Domain/Budget/Strategies/ProjectFinancialStrategyInterface.php` | Interface | — |
| `app/Domain/Budget/Strategies/PhaseBasedBudgetStrategy.php` | Phase-based | Development Projects, NEXT PHASE, Livelihood, RST, CIC, CCI, Rural-Urban-Tribal |
| `app/Domain/Budget/Strategies/DirectMappedIndividualBudgetStrategy.php` | Direct-mapped | IIES, IES, ILP, IAH, IGE |

**ProjectFinancialStrategyInterface**

- Defines `resolve(Project $project): array` with the five canonical keys
- Does NOT handle expenses, utilization, or remaining balance

**PhaseBasedBudgetStrategy**

- Uses `project->budgets` filtered by `current_phase`
- **Approved**: Uses DB `amount_sanctioned` and `opening_balance` via `BudgetSyncGuard::isApproved()`
- **Non-approved**: Derives sanctioned = overall − (forwarded + local), opening = sanctioned + forwarded + local
- All arithmetic via `DerivedCalculationService` where applicable

**DirectMappedIndividualBudgetStrategy**

- Uses type-specific tables (IIES, IES, ILP, IAH, IGE)
- Resolves from `iiesExpenses`, `iesExpenses`, `ilpBudget`, `iahBudgetDetails`, `igeBudget`
- No phase logic

**Strategy Selection Logic**

- Resolver's `getStrategyForProject()` selects by `project_type`
- **No controller branching** — Controllers never branch on project type for financial resolution; they always call the resolver

---

### 2.3 DerivedCalculationService

| Attribute | Value |
|-----------|-------|
| **Location** | `app/Services/Budget/DerivedCalculationService.php` |
| **Namespace** | `App\Services\Budget` |
| **Role** | Pure math engine; no DB calls |

**Public API (frozen):**

| Method | Purpose |
|--------|---------|
| `calculateRowTotal($rateQuantity, $rateMultiplier, $rateDuration)` | Row total: q × m × d |
| `calculatePhaseTotal(iterable $rows)` | Sum of row totals |
| `calculateProjectTotal(iterable $phases)` | Sum of phase totals or row collections |
| `calculateRemainingBalance($totalBudget, $totalExpenses)` | `totalBudget - totalExpenses` |
| `calculateUtilization($expenses, $openingBalance)` | `(expenses / openingBalance) * 100` when opening > 0; else 0 |

**Rounding rules:** Values are rounded to 2 decimals where applicable. `calculateUtilization` returns 0 when `openingBalance <= 0`.

**No DB calls** — Service is stateless and receives all inputs as parameters.

---

## 3. Data Flow

```
Controller
    ↓
ProjectFinancialResolver
    ↓
Financial Strategy (PhaseBasedBudgetStrategy | DirectMappedIndividualBudgetStrategy)
    ↓
DerivedCalculationService (for row/phase totals, remaining, utilization)
```

### Approval-Safe Behavior

- **Approved projects** (via `BudgetSyncGuard::isApproved()`): Strategy returns DB `amount_sanctioned` and `opening_balance`; no formula recomputation
- **Non-approved projects**: Strategy derives sanctioned and opening from type-specific logic and `DerivedCalculationService`

### Opening Balance Logic

- **Phase-based**: Approved → DB `opening_balance`; Non-approved → sanctioned + forwarded + local
- **Direct-mapped**: From type-specific table (e.g., `iies_total_expenses` for IIES)

---

## 4. Aggregation Pattern (Wave 2B)

For controller methods that aggregate budgets across projects (e.g., `getSystemPerformanceData`, `getSystemAnalyticsData`):

### Single Load of Projects

- Load all relevant projects once: `Project::with(['user', 'user.parent', 'budgets'])->get()`
- Never re-query inside province/type/month loops

### Eager Loading Requirements

- `user`, `user.parent`, `budgets` — Required for resolver strategy logic and grouping by province

### Resolver Memoization

```php
$resolvedFinancials = [];
foreach ($allProjects as $project) {
    $resolvedFinancials[$project->project_id] = $resolver->resolve($project);
}
```

- Use `$project->project_id` as key (not `$project->id`)
- Resolve each project exactly once

### Never Resolve Inside Grouping Closures

- Do NOT call `$resolver->resolve($project)` inside `groupBy`, `map`, or `filter` callbacks
- Always use the memoized array: `$resolvedFinancials[$project->project_id]['opening_balance']`

### Use $calc for Remaining and Utilization

```php
$remaining = $calc->calculateRemainingBalance($budget, $expenses);
$utilization = $calc->calculateUtilization($expenses, $budget);
```

### Preserve Cache::remember

- Keep existing `Cache::remember($cacheKey, $ttl, fn() => ...)` wrappers
- Do not change cache TTL or key structure

---

## 5. Controller Rules (Hard Requirements)

### Controllers Must NOT

- Compute sanctioned/opening via inline formulas
- Use fallback logic: `$p->amount_sanctioned ?? $p->overall_project_budget`
- Compute remaining inline: `$budget - $expenses`
- Compute utilization inline: `($expenses / $budget) * 100`
- Sum DB columns directly: `->sum('amount_sanctioned')`, `->sum('overall_project_budget')`

### Controllers Must

- Instantiate resolver once per method: `$resolver = app(\App\Domain\Budget\ProjectFinancialResolver::class)`
- Memoize `resolve()` results in `$resolvedFinancials[$project->project_id]`
- Use `DerivedCalculationService` for remaining and utilization: `$calc->calculateRemainingBalance()`, `$calc->calculateUtilization()`

---

## 6. Testing Guarantees

| Test | Purpose |
|------|---------|
| **ProjectFinancialResolverParityTest** | Validates `ProjectFinancialResolver` matches `ProjectFundFieldsResolver` for all supported scenarios (phase-based, IIES, edge cases) |
| **FirstTimeApprovalRegressionTest** | Ensures first-time approval flow persists correct `amount_sanctioned` and `opening_balance` via resolver; phase-based and IIES |
| **CoordinatorAggregationParityTest** | Verifies `CoordinatorController::getSystemPerformanceData()` totals match manual aggregation using resolver + DerivedCalculationService |
| **DerivedCalculationFreezeTest** | Freezes row/phase/project total formulas; ensures backend does not alter submitted values; validates bounds |
| **BudgetDomainIsolationTest** | Scans app for budget arithmetic patterns; ensures only `DerivedCalculationService` and excluded files contain them |
| **BudgetDomainBoundaryTest** | Same for PHP and JS; ensures no arithmetic in models/controllers/views outside canonical modules |
| **DerivedCalculationServiceContractTest** | Freezes public API: only allowed methods exist; all return float; none static |

---

## 7. Performance Considerations

- **Eager loading**: Always load `user`, `user.parent`, `budgets` when aggregating; strategies require these relations
- **Resolver called once per project**: Memoize in `$resolvedFinancials`; never call inside loops or closures
- **Aggregation memoization**: Use pre-grouped collections; avoid re-querying per province/type/month
- **Cache::remember**: Dashboard aggregation methods use cache (10–15 min TTL); preserve wrappers
- **No nested Project queries**: Load projects once; group in memory with `groupBy()`

---

## 8. Anti-Patterns (Forbidden)

The following patterns are **explicitly forbidden** outside the resolver and DerivedCalculationService:

| Pattern | Forbidden |
|---------|-----------|
| `$p->amount_sanctioned ?? $p->overall_project_budget` | Yes |
| `->sum('amount_sanctioned')` | Yes |
| `->sum('overall_project_budget')` | Yes |
| `$remaining = $budget - $expenses` | Yes |
| `$utilization = ($expenses / $budget) * 100` | Yes |
| Arithmetic in Blade | Yes |
| Arithmetic in JS as source of truth | Yes (JS parity module exists for Edit form preview only) |

---

## 9. Migration History Summary

| Phase | Description |
|-------|-------------|
| **Phase 1** | Centralization of row/phase/project totals into `DerivedCalculationService` |
| **Phase 2** | Boundary enforcement via `BudgetDomainIsolationTest`, `BudgetDomainBoundaryTest` |
| **Wave 2A** | Single-project refactor: resolver + strategies for project-level fund fields; approval-safe logic |
| **Wave 2B** | Aggregation refactor: memoization pattern, `$calc` for remaining/utilization |
| **Phase C1** | Query consolidation in CoordinatorController: single load, group in memory, eager loading |
| **Phase C2** | Financial consolidation in CoordinatorController: replace fallbacks with resolver; use `$calc` |

---

## References

- [FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md](./FINANCIAL_ENGINE_CONSOLIDATION_BLUEPRINT.md) — Original analysis
- [RESOLVER_IMPLEMENTATION_TODO.md](./RESOLVER_IMPLEMENTATION_TODO.md) — Implementation checklist
- [WAVE2B_AGGREGATION_SEMANTIC_MAP.md](./WAVE2B_AGGREGATION_SEMANTIC_MAP.md) — Aggregation semantic map
