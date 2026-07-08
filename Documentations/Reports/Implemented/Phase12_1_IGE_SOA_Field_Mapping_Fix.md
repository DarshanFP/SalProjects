# Phase 12.1 Implementation: IGE SOA Field Mapping Fix (C1)

**Date:** 2026-06-27  
**Goal:** Fix critical discrepancy C1 where Institutional Ongoing Group Educational (IGE) Statement of Account (SOA) templates referenced IIES model property names (`iies_particular`, `iies_amount`) instead of IGE model property names (`name`, `total_amount`), resulting in blank particulars and zero sanctioned amounts on budget rows.

---

## Root Cause Analysis

In `ProjectIGEBudget` model (`app/Models/OldProjects/IGE/ProjectIGEBudget.php`), the actual database columns for expense description and total sanctioned amount are `name` and `total_amount`.

However, both the create and edit SOA templates for IGE (`institutional_education.blade.php`) contained legacy copy-paste code from IIES (`individual_education.blade.php`) that attempted to read:
- `$budget->iies_particular` (evaluated to `null`)
- `$budget->iies_amount` (evaluated to `null`)

This caused new and edited IGE report forms to render blank particulars, zero sanctioned amounts, and broke prior-month expense carry-forward lookups.

---

## Changes Made

### 1. [`resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/statements_of_account/institutional_education.blade.php)
- Updated line 185 comment to identify as `IGE project budgets`.
- Replaced `$budget->iies_particular` with `$budget->name` for particulars input value and `$lastExpenses` lookup key.
- Replaced `$budget->iies_amount` with `$budget->total_amount` for `amount_sanctioned` and `total_amount` input values.

### 2. [`resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/institutional_education.blade.php)
- Updated line 194 comment to identify as `IGE project budgets`.
- Replaced `$budget->iies_particular` with `$budget->name` for hidden particulars input, text span display, and `$lastExpenses` lookup key.
- Replaced `$budget->iies_amount` with `$budget->total_amount` for `amount_sanctioned` and `total_amount` input values.

---

## Verification

1. **Create Form:** Opened monthly report creation form for an IGE project type. Verified that budget rows populate with exact item names from `ProjectIGEBudget` (`name`) and amounts from `total_amount`.
2. **Edit Form:** Verified that adding/rendering budget rows in edit mode preserves particulars and amounts.
3. **Carry-Forward Expenses:** Verified that `$lastExpenses[$budget->name]` correctly maps prior month spending by item name.
