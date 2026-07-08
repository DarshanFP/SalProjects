# Phase 4.4 — IGE Budget Field Mapping Verification

**Status:** ✅ Verified  
**Date:** 2026-06-13

## Config (`config/budget.php`)

```php
'Institutional Ongoing Group Educational proposal' => [
    'model' => ProjectIGEBudget::class,
    'strategy' => DirectMappingStrategy::class,
    'fields' => [
        'particular' => 'name',
        'amount' => 'total_amount',
        'id' => 'IGE_budget_id',
    ],
],
```

## Model schema (`ProjectIGEBudget` / `project_IGE_budget`)

| Config key | DB column | Used by resolver |
|------------|-----------|------------------|
| `particular` → `name` | `name` | Report row labels (BudgetCalculationService) |
| `amount` → `total_amount` | `total_amount` | `overall_project_budget` sum |
| — | `scholarship_eligibility` | `local_contribution` (partial) |
| — | `family_contribution` | `local_contribution` (partial) |
| — | `amount_requested` | `amount_sanctioned` sum |

`DirectMappedIndividualBudgetStrategy::resolveIGE()` reads:
- `total_amount` → overall
- `scholarship_eligibility + family_contribution` → local
- `amount_requested` → sanctioned

## Live project check (IOGEP-0006)

Audit command derives **493,200.00** sanctioned from IGE budget rows — confirms mapping is correct for report/overview repair.

## Note on IOGEP-0004

Audit may show `derived_mismatch:overall_project_budget` when stored `overall_project_budget` on `projects` differs from sum of IGE rows. Repair command updates all five fund fields from type-derived values; review IOGEP-0004 manually before repair if stored overall was intentionally different.

## Ongoing sync (Phase 4 plan § 4.3)

Enable in `.env` **after staging validation**:

```env
BUDGET_RESOLVER_ENABLED=true
BUDGET_SYNC_ON_TYPE_SAVE=true
BUDGET_SYNC_BEFORE_APPROVAL=true
```

Defaults remain `false` in `config/budget.php` — do not auto-enable in production.
