# Phase 12.3 Implementation: Individual Edit SOA Field Mappings (C4)

**Date:** 2026-06-27  
**Goal:** Fix critical discrepancy C4 where the 4 individual project type edit SOA templates (IIES, IAH, ILP, and IES) referenced incorrect or raw model properties instead of calculated `amount_sanctioned` and correct item descriptions.

---

## Root Cause Analysis

When editing monthly reports, project budget data is prefetched via `BudgetCalculationService::getBudgetsForReport()`.
- For contribution-based individual strategies (`SingleSourceContributionStrategy` and `MultipleSourceContributionStrategy`), the net calculated budget after deductions is dynamically attached to returned objects as `amount_sanctioned`.
- In addition, specific field mappings (`iies_particular`, `particular`, `budget_desc`) define the true item description.

However, the 4 individual edit SOA templates contained property mismatches when rendering budget rows:
1. **IIES (`individual_education.blade.php`):** Referenced `$budget->name`, `$budget->study_proposed`, and `$budget->amount_requested`, which were all `null`.
2. **IAH (`individual_health.blade.php`):** Referenced non-existent `$budget->amount_requested`, defaulting to `0.00`.
3. **ILP (`individual_livelihood.blade.php`):** Referenced non-existent `$budget->amount_requested`, defaulting to `0.00`.
4. **IES (`individual_ongoing_education.blade.php`):** Referenced `$budget->amount` (raw unadjusted expense before contribution deduction).

---

## Changes Made

### 1. [`resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/individual_education.blade.php)
- Fixed comment label to `IIES project budgets`.
- Updated particulars to `$budget->iies_particular` (hidden input, text span, and `$lastExpenses` lookup key).
- Updated `amount_sanctioned` and `total_amount` inputs to `$budget->amount_sanctioned`.

### 2. [`resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/individual_health.blade.php)
- Updated `amount_sanctioned` and `total_amount` inputs to `$budget->amount_sanctioned`.

### 3. [`resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/individual_livelihood.blade.php)
- Updated `amount_sanctioned` and `total_amount` inputs to `$budget->amount_sanctioned`.

### 4. [`resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php`](file:///Applications/MAMP/htdocs/Laravel/SalProjects/resources/views/reports/monthly/partials/edit/statements_of_account/individual_ongoing_education.blade.php)
- Updated `amount_sanctioned` and `total_amount` inputs to `$budget->amount_sanctioned`.

---

## Verification

1. **Edit Form Parity:** Verified that when adding budget rows during edit mode for IIES, IAH, ILP, and IES, all particulars display correctly and sanctioned amounts accurately reflect net calculated figures matching Create mode.
