# Phase 2.4 — Budget Domain Boundary Guard

**Date:** February 9, 2025

## Purpose

This test enforces that:

- All multiplication is centralized in DerivedCalculationService (PHP) and budget-calculations.js (JS)
- All phase/project totals use calculatePhaseTotal / calculateProjectTotal
- No inline arithmetic exists in models or controllers
- JS arithmetic exists only inside budget-calculations.js

## Protected Areas

- **PHP:** Models, Controllers, Services (except canonical calculation service)
- **JS:** resources/js, resources/views (Blade inline scripts)

## Forbidden Patterns

### PHP (app/Models, app/Http/Controllers, app/Services)

| Pattern | Description |
|---------|-------------|
| `rate_quantity *` | rate_quantity multiplication |
| `rate_multiplier *` | rate_multiplier multiplication |
| `rate_duration *` | rate_duration multiplication |
| `* rate_quantity` | multiplication by rate_quantity |
| `* rate_multiplier` | multiplication by rate_multiplier |
| `* rate_duration` | multiplication by rate_duration |
| `->sum('this_phase')` | sum(this_phase) |
| `->sum("this_phase")` | sum(this_phase) |
| `->sum('next_phase')` | sum(next_phase) |
| `->sum("next_phase")` | sum(next_phase) |
| `array_sum(` | array_sum |
| `$total +=` | manual total accumulation |

### JS (resources/js, resources/views — excluding budget-calculations.js)

| Pattern | Description |
|---------|-------------|
| `* rateQuantity` | rateQuantity multiplication |
| `* rateMultiplier` | rateMultiplier multiplication |
| `* rateDuration` | rateDuration multiplication |
| `total +=` | total accumulation |
| `parseFloat(...) * parseFloat(...)` | inline multiplication via parseFloat |

## Excluded Paths

- **PHP:** app/Services/Budget/DerivedCalculationService.php, vendor, tests, storage
- **PHP (audited):** BudgetValidationService, BudgetCalculationService, BoundedNumericService, CoordinatorController, IAHBudgetDetailsController, ExecutorController, InstitutionalSupportController
- **JS:** budget-calculations.js, vendor, storage
- **JS (audited):** ILP/budget.blade.php, Edit/ILP/budget.blade.php (ILP cost-field arithmetic)

## Why This Matters

This prevents:

- Architectural drift
- Silent regression
- Future developers reintroducing inline arithmetic
- JS/backend formula mismatch

## Test File

`tests/Architecture/BudgetDomainBoundaryTest.php`

Run with: `php artisan test tests/Architecture/BudgetDomainBoundaryTest.php`

## Status

Phase 2.4 Architecture Locked.
