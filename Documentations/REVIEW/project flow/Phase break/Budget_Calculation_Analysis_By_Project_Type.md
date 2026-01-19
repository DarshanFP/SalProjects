# Budget Calculation Analysis by Project Type - Reporting System

**Date:** January 2025  
**Status:** 📋 **ANALYSIS COMPLETE**  
**Purpose:** Document project-type-specific budget logic in reporting system

---

## Executive Summary

This document analyzes the budget calculation logic for each project type in the reporting system. Different project types have different budget structures and calculation requirements, which must be preserved to maintain accuracy and compliance with each project type's specific needs.

**Key Finding:** All project types use the same **8-column table structure** in reports, but the **"Amount Sanctioned"** calculation differs significantly based on project type requirements.

---

## Common Report Table Structure

All project types use the same table structure in monthly reports:

| Column | Field Name | Description |
|--------|------------|-------------|
| 1. Particulars | `particulars[]` | Budget item description |
| 2. Amount Forwarded | `amount_forwarded[]` | Amount carried forward from previous period |
| 3. Amount Sanctioned | `amount_sanctioned[]` | **CALCULATED DIFFERENTLY PER PROJECT TYPE** |
| 4. Total Amount | `total_amount[]` | Column 2 + Column 3 (calculated) |
| 5. Expenses Last Month | `expenses_last_month[]` | Cumulative expenses up to last month |
| 6. Expenses This Month | `expenses_this_month[]` | Expenses for current month |
| 7. Total Expenses | `total_expenses[]` | Column 5 + Column 6 (calculated) |
| 8. Balance Amount | `balance_amount[]` | Column 4 - Column 7 (calculated) |

**Common Calculations (All Project Types):**
- `total_amount = amount_forwarded + amount_sanctioned`
- `total_expenses = expenses_last_month + expenses_this_month`
- `balance_amount = total_amount - total_expenses`

---

## Project Type Categories

### Category 1: Development Projects (Institutional)

**Project Types:**
- Development Projects (DP)
- Livelihood Development Projects (LDP)
- Residential Skill Training Proposal 2 (RST)
- PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (CIC)
- CHILD CARE INSTITUTION (CCI)
- Rural-Urban-Tribal (Edu-RUT)

**Budget Source Table:** `project_budgets`

**Calculation Logic:**

```php
// Get budgets for highest phase
$highestPhase = ProjectBudget::where('project_id', $project->project_id)->max('phase');
$budgets = ProjectBudget::where('project_id', $project->project_id)
    ->where('phase', $highestPhase)
    ->get();
```

**Amount Sanctioned Calculation:**
- **Direct from budget:** `amount_sanctioned = $budget->this_phase`
- No subtraction or adjustment needed
- Uses the `this_phase` field from the budget table

**Budget Fields Used:**
- `particular` → `particulars` (in report)
- `this_phase` → `amount_sanctioned` (in report)
- `amount_forwarded` → `amount_forwarded` (in report)

**Special Features:**
- Phase-based budgeting (uses highest phase)
- Budget rows are marked with `is_budget_row = 1`
- Users can add additional non-budget rows

**Code Location:**
- `ReportController::getDevelopmentProjectBudgets()`
- Lines 129-137

**Status:** ✅ **CORRECT** - Uses highest phase as intended

---

### Category 2: Individual Livelihood Projects (ILP)

**Project Type:** Individual - Livelihood Application (ILP)

**Budget Source Table:** `project_ilp_budgets`

**Calculation Logic:**

```php
$budgets = ProjectILPBudget::where('project_id', $project->project_id)->get();

// Get beneficiary contribution (same for all rows)
$beneficiaryContribution = $budgets->first()->beneficiary_contribution ?? 0;
$totalRows = $budgets->count();

// Distribute contribution across all rows
$contributionPerRow = $totalRows > 0 ? $beneficiaryContribution / $totalRows : 0;

// Calculate amount_sanctioned for each row
foreach ($budgets as $budget) {
    $cost = $budget->cost ?? 0;
    $amount_sanctioned = max(0, $cost - $contributionPerRow);
}
```

**Amount Sanctioned Calculation:**
- **Formula:** `amount_sanctioned = max(0, cost - (beneficiary_contribution / total_rows))`
- Subtracts beneficiary contribution proportionally from each row
- Ensures amount doesn't go negative

**Budget Fields Used:**
- `budget_desc` → `particulars` (in report)
- `cost` → Original cost (before contribution)
- `beneficiary_contribution` → Total contribution (distributed across rows)
- `amount_sanctioned` → Calculated: `cost - contribution_per_row`

**Special Features:**
- Beneficiary contribution is distributed equally across all budget rows
- Each row's sanctioned amount is reduced by its share of the contribution
- Prevents negative amounts

**Code Location:**
- `ReportController::getILPBudgets()`
- Lines 142-191

**Status:** ✅ **CORRECT** - Properly distributes beneficiary contribution

---

### Category 3: Individual Access to Health (IAH)

**Project Type:** Individual - Access to Health (IAH)

**Budget Source Table:** `project_iah_budget_details`

**Calculation Logic:**

```php
$budgets = ProjectIAHBudgetDetails::where('project_id', $project->project_id)->get();

// Get family contribution (same for all rows)
$familyContribution = $budgets->first()->family_contribution ?? 0;
$totalRows = $budgets->count();

// Distribute contribution across all rows
$contributionPerRow = $totalRows > 0 ? $familyContribution / $totalRows : 0;

// Calculate amount_sanctioned for each row
foreach ($budgets as $budget) {
    $amount = $budget->amount ?? 0;
    $amount_sanctioned = max(0, $amount - $contributionPerRow);
}
```

**Amount Sanctioned Calculation:**
- **Formula:** `amount_sanctioned = max(0, amount - (family_contribution / total_rows))`
- Subtracts family contribution proportionally from each row
- Ensures amount doesn't go negative

**Budget Fields Used:**
- `particular` → `particulars` (in report)
- `amount` → Original amount (before contribution)
- `family_contribution` → Total contribution (distributed across rows)
- `amount_sanctioned` → Calculated: `amount - contribution_per_row`

**Special Features:**
- Family contribution is distributed equally across all budget rows
- Each row's sanctioned amount is reduced by its share of the contribution
- Similar logic to ILP but uses `family_contribution` instead of `beneficiary_contribution`

**Code Location:**
- `ReportController::getIAHBudgets()`
- Lines 196-246

**Status:** ✅ **CORRECT** - Properly distributes family contribution

---

### Category 4: Institutional Group Education (IGE)

**Project Type:** Institutional Ongoing Group Educational proposal (IGE)

**Budget Source Table:** `project_ige_budgets`

**Calculation Logic:**

```php
$budgets = ProjectIGEBudget::where('project_id', $project->project_id)->get();
// No special calculation - direct mapping
```

**Amount Sanctioned Calculation:**
- **Direct from budget:** Uses budget field directly (no subtraction)
- **Note:** Field name in IGE budget table needs verification
- No contribution subtraction required

**Budget Fields Used:**
- Budget table fields → Report fields (direct mapping)
- **Note:** Exact field mapping needs verification

**Special Features:**
- Simplest calculation - direct mapping
- No contribution adjustments
- No phase-based logic

**Code Location:**
- `ReportController::getIGEBudgets()`
- Lines 251-254

**Status:** ⚠️ **NEEDS VERIFICATION** - Direct mapping, but exact field names need confirmation

---

### Category 5: Individual Initial Educational Support (IIES)

**Project Type:** Individual - Initial - Educational support (IIES)

**Budget Source Table:** `project_iies_expenses` (parent) + `project_iies_expense_details` (child)

**Calculation Logic:**

```php
$iiesExpenses = ProjectIIESExpenses::where('project_id', $project->project_id)->first();
$expenseDetails = $iiesExpenses->expenseDetails;

// Get contributions from parent table
$expectedScholarshipGovt = $iiesExpenses->iies_expected_scholarship_govt ?? 0;
$supportOtherSources = $iiesExpenses->iies_support_other_sources ?? 0;
$beneficiaryContribution = $iiesExpenses->iies_beneficiary_contribution ?? 0;

// Total contribution from all sources
$totalContribution = $expectedScholarshipGovt + $supportOtherSources + $beneficiaryContribution;
$totalRows = $expenseDetails->count();

// Distribute contribution across all rows
$contributionPerRow = $totalRows > 0 ? $totalContribution / $totalRows : 0;

// Calculate amount_sanctioned for each row
foreach ($expenseDetails as $detail) {
    $amount = $detail->iies_amount ?? 0;
    $amount_sanctioned = max(0, $amount - $contributionPerRow);
}
```

**Amount Sanctioned Calculation:**
- **Formula:** `amount_sanctioned = max(0, iies_amount - (total_contribution / total_rows))`
- **Total Contribution:** Sum of 3 sources:
  1. Expected Scholarship from Government
  2. Support from Other Sources
  3. Beneficiary Contribution
- Subtracts total contribution proportionally from each row
- Ensures amount doesn't go negative

**Budget Fields Used:**
- `iies_particular` → `particulars` (in report)
- `iies_amount` → Original amount (before contribution)
- `iies_expected_scholarship_govt` → Government scholarship
- `iies_support_other_sources` → Other sources support
- `iies_beneficiary_contribution` → Beneficiary contribution
- `amount_sanctioned` → Calculated: `iies_amount - contribution_per_row`

**Special Features:**
- Uses parent-child table relationship
- Three sources of contribution combined
- Contribution distributed equally across all expense detail rows
- Most complex contribution calculation

**Code Location:**
- `ReportController::getIIESBudgets()`
- Lines 259-322

**Status:** ✅ **CORRECT** - Properly combines and distributes all contribution sources

---

### Category 6: Individual Ongoing Educational Support (IES)

**Project Type:** Individual - Ongoing Educational support (IES)

**Budget Source Table:** `project_ies_expenses` (parent) + `project_ies_expense_details` (child)

**Calculation Logic:**

```php
$iesExpenses = ProjectIESExpenses::where('project_id', $project->project_id)->first();
$expenseDetails = $iesExpenses->expenseDetails;

// Get contributions from parent table
$expectedScholarshipGovt = $iesExpenses->expected_scholarship_govt ?? 0;
$supportOtherSources = $iesExpenses->support_other_sources ?? 0;
$beneficiaryContribution = $iesExpenses->beneficiary_contribution ?? 0;

// Total contribution from all sources
$totalContribution = $expectedScholarshipGovt + $supportOtherSources + $beneficiaryContribution;
$totalRows = $expenseDetails->count();

// Distribute contribution across all rows
$contributionPerRow = $totalRows > 0 ? $totalContribution / $totalRows : 0;

// Calculate amount_sanctioned for each row
foreach ($expenseDetails as $detail) {
    $amount = $detail->amount ?? 0;
    $amount_sanctioned = max(0, $amount - $contributionPerRow);
}
```

**Amount Sanctioned Calculation:**
- **Formula:** `amount_sanctioned = max(0, amount - (total_contribution / total_rows))`
- **Total Contribution:** Sum of 3 sources:
  1. Expected Scholarship from Government
  2. Support from Other Sources
  3. Beneficiary Contribution
- Subtracts total contribution proportionally from each row
- Ensures amount doesn't go negative

**Budget Fields Used:**
- `particular` → `particulars` (in report)
- `amount` → Original amount (before contribution)
- `expected_scholarship_govt` → Government scholarship
- `support_other_sources` → Other sources support
- `beneficiary_contribution` → Beneficiary contribution
- `amount_sanctioned` → Calculated: `amount - contribution_per_row`

**Special Features:**
- Uses parent-child table relationship
- Three sources of contribution combined
- Contribution distributed equally across all expense detail rows
- Similar to IIES but different field names

**Code Location:**
- `ReportController::getIESBudgets()`
- Lines 327-390

**Status:** ✅ **CORRECT** - Properly combines and distributes all contribution sources

---

## Summary Table

| Project Type | Budget Table | Amount Sanctioned Calculation | Contribution Logic |
|-------------|--------------|-------------------------------|---------------------|
| **Development Projects** (DP, LDP, RST, CIC, CCI, Edu-RUT) | `project_budgets` | `this_phase` (direct) | None |
| **ILP** | `project_ilp_budgets` | `cost - (beneficiary_contribution / rows)` | Single source, distributed |
| **IAH** | `project_iah_budget_details` | `amount - (family_contribution / rows)` | Single source, distributed |
| **IGE** | `project_ige_budgets` | Direct mapping | None (needs verification) |
| **IIES** | `project_iies_expenses` + details | `iies_amount - (total_contribution / rows)` | Three sources, combined & distributed |
| **IES** | `project_ies_expenses` + details | `amount - (total_contribution / rows)` | Three sources, combined & distributed |

---

## Calculation Patterns

### Pattern 1: Direct Mapping (Development Projects, IGE)
- No contribution subtraction
- Direct field mapping
- Simplest logic

### Pattern 2: Single Source Contribution (ILP, IAH)
- One contribution source
- Distributed equally across rows
- Formula: `original_amount - (contribution / total_rows)`

### Pattern 3: Multiple Source Contribution (IIES, IES)
- Multiple contribution sources (3)
- Combined into total contribution
- Distributed equally across rows
- Formula: `original_amount - (total_contribution / total_rows)`

---

## Common Calculation Logic (All Types)

### Row-Level Calculations
```javascript
// Client-side (JavaScript)
total_amount = amount_forwarded + amount_sanctioned
total_expenses = expenses_last_month + expenses_this_month
balance_amount = total_amount - total_expenses
```

```php
// Server-side (PHP - Fallback)
$totalAmount = $amountForwarded + $amountSanctioned;
$totalExpenses = $expensesLastMonth + $expensesThisMonth;
$balanceAmount = $totalAmount - $totalExpenses;
```

### Summary Calculations
```javascript
// Totals across all rows
total_forwarded = sum(amount_forwarded[])
total_sanctioned = sum(amount_sanctioned[])
total_amount_total = sum(total_amount[])
total_expenses_last_month = sum(expenses_last_month[])
total_expenses_this_month = sum(expenses_this_month[])
total_expenses_total = sum(total_expenses[])
total_balance = sum(balance_amount[])
```

---

## Issues and Recommendations

### ✅ Working Correctly

1. **Development Projects:** Uses highest phase correctly
2. **ILP:** Properly distributes beneficiary contribution
3. **IAH:** Properly distributes family contribution
4. **IIES:** Properly combines and distributes 3 contribution sources
5. **IES:** Properly combines and distributes 3 contribution sources
6. **Common Calculations:** All row-level and summary calculations are consistent

### ⚠️ Needs Verification

1. **IGE Budget Fields:**
   - Exact field names in `project_ige_budgets` table
   - Which field maps to `amount_sanctioned` in reports
   - Verification needed to ensure correct mapping

### 📋 Recommendations

1. **Documentation:**
   - ✅ This document provides comprehensive documentation
   - Document field mappings for IGE

2. **Code Consistency:**
   - ✅ All project types follow consistent patterns
   - ✅ Error handling is consistent (max(0, ...) prevents negatives)
   - ✅ Logging is comprehensive

3. **Testing:**
   - Test each project type's budget calculation
   - Verify contribution distributions are correct
   - Verify totals match expected values

4. **Future Enhancements:**
   - Consider adding validation to ensure contribution totals match
   - Add warnings if contribution exceeds original amount
   - Add unit tests for each calculation type

---

## Code References

### Controller Methods
- `ReportController::getBudgetDataByProjectType()` - Routes to correct method
- `ReportController::getDevelopmentProjectBudgets()` - Development projects
- `ReportController::getILPBudgets()` - ILP projects
- `ReportController::getIAHBudgets()` - IAH projects
- `ReportController::getIGEBudgets()` - IGE projects
- `ReportController::getIIESBudgets()` - IIES projects
- `ReportController::getIESBudgets()` - IES projects

### View Files
- `resources/views/reports/monthly/partials/create/statements_of_account.blade.php` - Create form
- `resources/views/reports/monthly/partials/view/statements_of_account/development_projects.blade.php` - View DP
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_livelihood.blade.php` - View ILP
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_health.blade.php` - View IAH
- `resources/views/reports/monthly/partials/view/statements_of_account/individual_education.blade.php` - View IES/IIES
- `resources/views/reports/monthly/partials/view/statements_of_account/institutional_education.blade.php` - View IGE

### JavaScript Calculations
- `calculateRowTotals()` - Calculates row-level totals
- `calculateTotal()` - Calculates summary totals
- `calculateAllRowTotals()` - Initializes all rows on page load

---

## Conclusion

The budget calculation system is **well-designed** and **project-type-specific**, which is appropriate given the different requirements of each project type. The system:

1. ✅ Maintains project-type-specific logic
2. ✅ Handles contributions correctly for individual projects
3. ✅ Uses consistent table structure across all types
4. ✅ Provides proper fallback calculations
5. ✅ Prevents negative amounts
6. ✅ Logs calculations for debugging

**Key Takeaway:** The different calculation methods are **intentional and necessary** to meet each project type's specific requirements. Standardization would not be appropriate here, as each project type has unique contribution structures.

---

**Document Status:** ✅ **ANALYSIS COMPLETE**  
**Last Updated:** January 2025  
**Next Steps:** Verify IGE field mappings, add unit tests
