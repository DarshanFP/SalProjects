# Phase 2.4 – Derived Calculation Implementation & Architecture Hardening

**Completed:** February 2025  
**Status:** Implemented and tested

---

## Summary

This document records the implementation and testing completed for Phase 2.4 – Derived Calculation Integration and the subsequent architecture hardening steps. All budget arithmetic is centralized in `DerivedCalculationService`, and architectural guard tests enforce isolation and contract stability.

---

## 1. Derived Calculation Integration (Step 1)

### Objective
Delegate `ProjectBudget::calculateTotalBudget()` to `DerivedCalculationService`.

### Implementation
- **File:** `app/Models/OldProjects/ProjectBudget.php`
- **Change:** `calculateTotalBudget()` now delegates to `DerivedCalculationService::calculateRowTotal()` via `app(DerivedCalculationService::class)`.
- **Formula:** `rate_quantity × rate_multiplier × rate_duration` (unchanged).

### Test Results
| Test Suite | Result |
|------------|--------|
| DerivedCalculationServiceTest | 6 passed |
| DevelopmentBudgetPhaseFreezeTest | 1 passed |
| Phase1_Budget_ValidationTest | 1 passed |

---

## 2. Phase + Project Totals in DerivedCalculationService

### Objective
Centralize all budget arithmetic and remove arithmetic from models and controllers.

### Service Extensions (`app/Services/Budget/DerivedCalculationService.php`)

| Method | Signature | Purpose |
|--------|-----------|---------|
| `calculateRowTotal` | `(float $rateQuantity, float $rateMultiplier, float $rateDuration): float` | Row total: q × m × d |
| `calculatePhaseTotal` | `(iterable $rows): float` | Sum of row totals from iterable of rows (objects/arrays) |
| `calculateProjectTotal` | `(iterable $phases): float` | Sum of phase totals or phase row collections |
| `calculateRemainingBalance` | `(float $totalBudget, float $totalExpenses): float` | total − expenses |

### Model Changes
- **ProjectBudget:** `calculateTotalBudget()` and `calculateRemainingBalance()` delegate to service via `app(DerivedCalculationService::class)`.

### Controller Changes
- **ExportController:** `$budgets->sum('this_phase'/'next_phase')` → `calculateProjectTotal()`
- **CoordinatorController:** `$project->budgets->sum('this_phase')` → `calculateProjectTotal()`
- **GeneralController:** `$project->budgets->sum('this_phase')` → `calculateProjectTotal()`
- **DevelopmentProjectController:** `$budgets->sum('this_phase')` → `calculateProjectTotal()`

### Unit Tests Added
- **`tests/Unit/Budget/DerivedCalculationServicePhaseTest.php`**
  - Phase total with 3 rows
  - Phase total with empty array
  - Phase total with decimals
  - Project total from phase row collections
  - Project total from numeric phase totals
  - Phase total with row objects
  - Very large values boundary case

### Test Results
- Budget-related tests: 62+ assertions passed
- All freeze tests pass

---

## 3. Architecture Hardening – Step 1: Budget Domain Isolation

### Objective
Ensure budget arithmetic is only in `DerivedCalculationService`.

### Implementation
- **File:** `tests/Architecture/BudgetDomainIsolationTest.php`
- **Test:** `test_no_budget_arithmetic_outside_derived_calculation_service`

### Scanned Directories
- `app/Models`
- `app/Http/Controllers`
- `app/Services` (except `app/Services/Budget`)

### Patterns Detected (Violation)
- `rate_quantity *`, `rate_multiplier *`, `rate_duration *`
- `->sum('this_phase')`, `->sum('next_phase')`
- `$total +=`, `array_sum(`
- `* $this->rate_quantity`, `* $this->rate_multiplier`, `* $this->rate_duration`

### Excluded Paths
- `app/Services/Budget`, `vendor`, `tests`
- **Excluded files:** BudgetValidationService, IAHBudgetDetailsController, CoordinatorController, ExecutorController, InstitutionalSupportController (validation and non-budget array_sum usage)

### Fail Message
```
Budget arithmetic detected outside DerivedCalculationService at: {file}:{line}
```

---

## 4. Architecture Hardening – Step 2: Container Resolution

### Objective
Disallow direct instantiation of `DerivedCalculationService` with `new`.

### Implementation
- **Test:** `test_no_direct_instantiation_of_derived_calculation_service` (in `BudgetDomainIsolationTest.php`)

### Patterns Detected (Violation)
- `new DerivedCalculationService(`
- `new \App\Services\Budget\DerivedCalculationService(`

### Allowed Direct Instantiation
- `tests/Unit/Budget/DerivedCalculationServiceTest.php`
- `tests/Architecture/BudgetDomainIsolationTest.php`

### Production Usage
- All production code uses `app(DerivedCalculationService::class)`.

### Change
- **DerivedCalculationServicePhaseTest:** `new DerivedCalculationService()` → `app(DerivedCalculationService::class)` (extends `Tests\TestCase`).

### Fail Message
```
Direct instantiation of DerivedCalculationService is forbidden. Use container resolution.
```

---

## 5. Architecture Hardening – Step 3: Contract Freeze

### Objective
Stabilize the public API of `DerivedCalculationService`.

### Implementation
- **File:** `tests/Architecture/DerivedCalculationServiceContractTest.php`

### Allowed Public Methods
1. `calculateRowTotal`
2. `calculatePhaseTotal`
3. `calculateProjectTotal`
4. `calculateRemainingBalance`

### Assertions
- Only these four public methods exist
- All return `float`
- None are `static`

### Fail Message
```
Unauthorized public method added to DerivedCalculationService: {method}
```

---

## 6. Architecture Hardening – Step 4: CI

### Objective
Run architecture tests in CI.

### Implementation
- **File:** `composer.json`
- **Script:** `"test:architecture": "php artisan test tests/Architecture"`

### Usage
```bash
composer test:architecture
```

### Tests Run
- `BudgetDomainIsolationTest` (2 tests)
- `DerivedCalculationServiceContractTest` (3 tests)

---

## Final Test Results

```
composer test:architecture
```

```
> php artisan test tests/Architecture

   PASS  Tests\Architecture\BudgetDomainIsolationTest
  ✓ no budget arithmetic outside derived calculation service
  ✓ no direct instantiation of derived calculation service

   PASS  Tests\Architecture\DerivedCalculationServiceContractTest
  ✓ only allowed public methods exist
  ✓ all public methods return float
  ✓ all public methods are not static

  Tests:    5 passed (19 assertions)
  Duration: ~0.2s
```

---

## Verification

- **Arithmetic violation:** Introducing `rate_quantity *` in a controller causes `test:architecture` to fail.
- **Budget freeze tests:** All pass.
- **Production code:** Uses `app(DerivedCalculationService::class)` only.

---

## Files Touched

| Category | Files |
|----------|-------|
| **Service** | `app/Services/Budget/DerivedCalculationService.php` |
| **Model** | `app/Models/OldProjects/ProjectBudget.php` |
| **Controllers** | ExportController, CoordinatorController, GeneralController, DevelopmentProjectController |
| **Tests** | BudgetDomainIsolationTest, DerivedCalculationServiceContractTest, DerivedCalculationServicePhaseTest, DerivedCalculationServiceTest |
| **Config** | `composer.json` (test:architecture script) |

---

## Constraints

- No behavior changes
- No rounding changes
- No schema changes
- No new validation logic
- Service remains pure (no DB access, no logging)
