# Phase 6.2 — SOA Router Unification & IGE Fix

**Status:** ✅ Implemented  
**Date:** 2026-06-13

## Problem

`ReportAll.blade.php` used a long `@if/@elseif` chain for Statements of Account with two bugs:

1. **Wrong IGE type string:** `'Institutional - Initial - Educational support'` (invalid) instead of `'Institutional Ongoing Group Educational proposal'`
2. **Incomplete coverage:** CCI, NPD, RST, CIC fell through to generic create partial instead of typed SOA

## Solution

Replaced inline chain with shared router:

```blade
@include('reports.monthly.partials.statements_of_account', [...])
```

Router maps all 12 types to typed partials under `partials/statements_of_account/`.

### Router updates

**File:** `partials/statements_of_account.blade.php`

- Added `NEXT PHASE - DEVELOPMENT PROPOSAL` → `development_projects`

**File:** `partials/view/statements_of_account.blade.php`

- Removed duplicate `'Individual - Ongoing Educational support'` map key
- Added NPD mapping

**Also updated:** `ReportCommonForm.blade.php` (alternate DP create path) to use the same router.

### Typo fix

`partials/create/statements_of_account.blade.php` header: "Statements of Account **a**" → "Statements of Account"

## Files

- `resources/views/reports/monthly/ReportAll.blade.php`
- `resources/views/reports/monthly/ReportCommonForm.blade.php`
- `resources/views/reports/monthly/partials/statements_of_account.blade.php`
- `resources/views/reports/monthly/partials/view/statements_of_account.blade.php`
- `resources/views/reports/monthly/partials/create/statements_of_account.blade.php`

## Manual test

Create report for **IOGEP** project → SOA section should use institutional education layout (not generic fallback with wrong type string).
