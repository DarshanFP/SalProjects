# Phase 7.1 — NPD Budget Config

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

`NEXT PHASE - DEVELOPMENT PROPOSAL` (NPD) was missing from `config/budget.php` `field_mappings`. `BudgetCalculationService::getStrategyForProjectType()` logged:

> Unknown project type for budget calculation, using DirectMappingStrategy as fallback

## Solution

Added explicit entry mirroring Development Projects:

```php
'NEXT PHASE - DEVELOPMENT PROPOSAL' => [
    'model' => ProjectBudget::class,
    'strategy' => DirectMappingStrategy::class,
    'fields' => [
        'particular' => 'particular',
        'amount' => 'this_phase',
        'id' => 'id',
    ],
    'phase_based' => true,
    'phase_selection' => 'current',
],
```

NPD uses the same `project_budgets` table and phase model as DP (verified in `ProjectController`, `PhaseBasedBudgetStrategy`, `ProjectFinancialResolver`).

## Files

- `config/budget.php`

## Verification

```bash
php artisan tinker --execute="echo isset(config('budget.field_mappings')['NEXT PHASE - DEVELOPMENT PROPOSAL']) ? 'OK' : 'MISSING';"
```

Create/edit monthly report for an NPD project — SOA rows should load via `current_phase` (not highest-phase fallback).
