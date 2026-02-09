# Budget Service Injection Refactor

**Date:** February 9, 2025  
**Phase:** 2.4 — Architectural Refactor

## Summary

All inline `app(DerivedCalculationService::class)` resolution has been replaced with constructor injection (controllers, services) or `resolve()` (models). No arithmetic, validation, or business logic was changed.

## Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/GeneralController.php` | Added constructor injection; replaced `app()` with `$this->calculationService` |
| `app/Http/Controllers/CoordinatorController.php` | Added constructor injection; replaced `app()` with `$this->calculationService` |
| `app/Http/Controllers/Reports/Quarterly/DevelopmentProjectController.php` | Added constructor injection; replaced `app()` with `$this->calculationService` |
| `app/Http/Controllers/Projects/ExportController.php` | Added `$calculationService` to constructor; replaced 3× `app()` with `$this->calculationService` |
| `app/Models/OldProjects/ProjectBudget.php` | Replaced `app()` with `resolve()` (models use resolve per spec) |
| `tests/Unit/Budget/BudgetFormulaParityTest.php` | Replaced `app()` with `resolve()` |
| `tests/Unit/Budget/DerivedCalculationServicePhaseTest.php` | Replaced `app()` with `resolve()` |

## Before/After Examples

### Controllers (Constructor Injection)

**Before:**
```php
class GeneralController extends Controller
{
    public function someMethod()
    {
        $overallBudget = app(DerivedCalculationService::class)->calculateProjectTotal($project->budgets->map(...));
    }
}
```

**After:**
```php
class GeneralController extends Controller
{
    public function __construct(
        private readonly DerivedCalculationService $calculationService
    ) {
    }

    public function someMethod()
    {
        $overallBudget = $this->calculationService->calculateProjectTotal($project->budgets->map(...));
    }
}
```

### Model (resolve)

**Before:**
```php
return app(DerivedCalculationService::class)->calculateRowTotal(...);
```

**After:**
```php
return resolve(DerivedCalculationService::class)->calculateRowTotal(...);
```

### ExportController (Existing Constructor)

**Before:**
```php
$section->addText(... app(DerivedCalculationService::class)->calculateProjectTotal(...) ...);
```

**After:**
```php
$section->addText(... $this->calculationService->calculateProjectTotal(...) ...);
```

## Confirmation: No Inline Container Resolution Remains

```bash
grep -r "app(DerivedCalculationService::class)" app/ tests/ --include="*.php"
# No matches (excluding documentation)
```

All production code now uses either:
- **Constructor injection** (controllers)
- **resolve()** (models, per spec: "DO NOT inject into Eloquent models via constructor")

## Test Results Summary

| Test Suite | Result |
|------------|--------|
| `tests/Unit/Budget/*` | 14 passed |
| `tests/Architecture/*` | 7 passed |
| `tests/Feature/Budget/*` | 6 passed |
| `tests/Unit/Services/Budget/*` | All passed |

**Total:** 25+ budget/architecture tests passed. No failures introduced by this refactor.

*(Full suite shows pre-existing failures in LogHelperTest, ProjectPermissionHelperTest, ReportViewsIndexingTest, TextareaAutoResizeTest — unrelated to this refactor.)*

## Confirmation: No Arithmetic Changed

- No changes to `DerivedCalculationService` formulas
- No changes to method signatures of calculation methods
- No changes to validation rules
- No changes to database schema
- All call sites now use the same service instance via DI or resolve

## Architectural Guarantee

- **Controllers:** Use constructor injection; Laravel resolves `DerivedCalculationService` automatically
- **Models:** Use `resolve()` for service access; no constructor injection in Eloquent models
- **Tests:** Use `resolve()` for service access in test setup
