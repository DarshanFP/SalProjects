# M3.5.2 — Remove app() Resolver Usage

**Milestone:** M3 — Resolver Parity & Financial Stability  
**Step:** M3.5.2 — Remove app() Resolver Usage  
**Mode:** Architectural Consistency  
**Date:** 2025-02-15

---

## Why Container Call Removed

`app(ProjectFinancialResolver::class)->resolve($project)` was used in ExportController to resolve financial fields for Word export. Service location via `app()`:

- Hides dependency — constructor does not declare the dependency
- Reduces testability — harder to mock or replace in unit tests
- Inconsistent — other controllers inject dependencies via constructor
- Violates explicit dependencies — callers cannot see what the controller needs

---

## Why Injection Preferred

1. **Explicit dependencies** — Constructor declares `ProjectFinancialResolver`; dependencies are visible
2. **Testability** — Tests can inject a mock or stub without touching the container
3. **Consistency** — Aligns with ProjectDataHydrator, BudgetValidationService, and other resolvers that use constructor injection
4. **Single pattern** — No mixed use of `app()` and constructor injection for the same concern

---

## Files Updated

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ExportController.php` | Added `protected ProjectFinancialResolver $financialResolver`; injected via constructor; replaced `app(ProjectFinancialResolver::class)->resolve($project)` with `$this->financialResolver->resolve($project)` |

---

## Risk Reduction

| Risk | Before | After |
|------|--------|-------|
| Hidden dependency | Resolver usage not visible in constructor | Dependency declared and injected |
| Test brittleness | Tests rely on container bindings | Tests can inject mock resolver |
| Pattern inconsistency | ExportController used `app()`; others use injection | All use constructor injection |
| Refactor fragility | Changing resolver binding could break silently | Compile-time check; explicit contract |

---

## Implementation Detail

```php
// ExportController
protected ProjectFinancialResolver $financialResolver;

public function __construct(
    // ... other params ...
    ProjectDataHydrator $projectDataHydrator,
    ProjectFinancialResolver $financialResolver
) {
    // ...
    $this->financialResolver = $financialResolver;
}

// Usage (downloadDoc)
$resolvedFundFields = $this->financialResolver->resolve($project);
```

- No financial logic changes
- Only container call replaced with injection
- Behavior unchanged

---

**M3.5.2 Complete — Resolver Injection Standardized**
