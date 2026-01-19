# Budget Standardization - Testing Guide

**Date:** January 2025  
**Status:** 📋 **TESTING PHASE**  
**Purpose:** Comprehensive testing guide for budget standardization implementation

---

## Testing Overview

This guide provides step-by-step instructions for testing the budget standardization implementation. Testing is divided into three phases:

1. **Unit Tests** - Automated tests for service and strategy classes
2. **Integration Tests** - Manual testing of controllers and report generation
3. **Side-by-Side Comparison** - Verification against old implementation

---

## Phase 1: Unit Tests

### Running Unit Tests

```bash
# Run all budget-related unit tests
php artisan test --filter BudgetCalculation

# Run specific test class
php artisan test tests/Unit/Services/Budget/BudgetCalculationServiceTest.php

# Run with coverage (if configured)
php artisan test --coverage --filter BudgetCalculation
```

### Test Files Created

1. ✅ `tests/Unit/Services/Budget/BudgetCalculationServiceTest.php`
   - Tests helper methods (contribution calculation, amount calculation)
   - Tests service routing to strategies
   - Tests logging functions

2. ✅ `tests/Unit/Services/Budget/Strategies/DirectMappingStrategyTest.php`
   - Tests phase-based budget retrieval (Development Projects)
   - Tests fallback to highest phase
   - Tests direct budget retrieval (IGE)
   - Tests empty collection handling

3. ✅ `tests/Unit/Services/Budget/Strategies/SingleSourceContributionStrategyTest.php`
   - Tests ILP budget calculation with contribution
   - Tests IAH budget calculation with contribution
   - Tests export mode (no calculation)
   - Tests negative amount prevention

4. ✅ `tests/Unit/Services/Budget/Strategies/MultipleSourceContributionStrategyTest.php`
   - Tests IIES budget calculation with multiple contributions
   - Tests IES budget calculation with multiple contributions
   - Tests null contribution handling
   - Tests export mode (no calculation)

### Expected Test Results

All tests should pass. If any test fails:

1. Check the error message
2. Verify the configuration in `config/budget.php`
3. Check model relationships
4. Review the test expectations

---

## Phase 2: Integration Testing (Manual)

### Prerequisites

- Access to the application
- Test projects of each type with budget data
- Access to create/edit monthly reports

### Test Checklist

#### 2.1 Development Projects (DP, LDP, RST, CIC, CCI, Edu-RUT)

**Test Steps:**

1. **Select a Development Project**
   - Navigate to a project with type "Development Projects"
   - Note the `current_phase` value
   - Note existing budget data

2. **Create Monthly Report**
   - Go to Reports → Create Monthly Report
   - Select the project
   - Verify budget data appears in "Statements of Account"
   - Check that `amount_sanctioned` values match `this_phase` values
   - Verify phase filtering works (only budgets for current phase shown)

3. **Verify Calculations**
   - Check that `total_amount = amount_forwarded + amount_sanctioned`
   - Check that `balance_amount = total_amount - total_expenses`
   - Verify all calculations are correct

4. **Export PDF**
   - Click "Export PDF"
   - Verify budget data appears correctly
   - Check that amounts match the report view

5. **Export Word**
   - Click "Export Word"
   - Verify budget data appears correctly
   - Check that amounts match the report view

**Expected Results:**
- ✅ Budget data loads correctly
- ✅ Only budgets for current phase are shown
- ✅ Amount sanctioned = this_phase value
- ✅ PDF/Word exports match report view

**Test Projects Needed:**
- At least one project of each type: DP, LDP, RST, CIC, CCI, Edu-RUT
- Projects with multiple phases
- Projects with current_phase set
- Projects with current_phase = null (should use max phase)

---

#### 2.2 Individual - Livelihood Application (ILP)

**Test Steps:**

1. **Select an ILP Project**
   - Navigate to an ILP project
   - Note existing budget data
   - Note `beneficiary_contribution` value

2. **Create Monthly Report**
   - Go to Reports → Create Monthly Report
   - Select the ILP project
   - Verify budget data appears
   - Check `amount_sanctioned` calculation:
     - `contribution_per_row = beneficiary_contribution / total_rows`
     - `amount_sanctioned = max(0, cost - contribution_per_row)`

3. **Verify Calculations**
   - Count total budget rows
   - Calculate expected contribution per row
   - Verify each row's `amount_sanctioned` is correct
   - Verify no negative amounts

4. **Export PDF/Word**
   - Export and verify data matches

**Expected Results:**
- ✅ Contribution distributed equally across all rows
- ✅ Amount sanctioned = cost - (contribution / total_rows)
- ✅ No negative amounts
- ✅ Calculations match old implementation

**Test Projects Needed:**
- ILP project with multiple budget rows
- ILP project with beneficiary_contribution > 0
- ILP project with beneficiary_contribution = 0

---

#### 2.3 Individual - Access to Health (IAH)

**Test Steps:**

1. **Select an IAH Project**
   - Navigate to an IAH project
   - Note existing budget data
   - Note `family_contribution` value

2. **Create Monthly Report**
   - Go to Reports → Create Monthly Report
   - Select the IAH project
   - Verify budget data appears
   - Check `amount_sanctioned` calculation:
     - `contribution_per_row = family_contribution / total_rows`
     - `amount_sanctioned = max(0, amount - contribution_per_row)`

3. **Verify Calculations**
   - Count total budget rows
   - Calculate expected contribution per row
   - Verify each row's `amount_sanctioned` is correct
   - Verify no negative amounts

4. **Export PDF/Word**
   - Export and verify data matches

**Expected Results:**
- ✅ Contribution distributed equally across all rows
- ✅ Amount sanctioned = amount - (contribution / total_rows)
- ✅ No negative amounts
- ✅ Calculations match old implementation

**Test Projects Needed:**
- IAH project with multiple budget rows
- IAH project with family_contribution > 0
- IAH project with family_contribution = 0

---

#### 2.4 Institutional Ongoing Group Educational proposal (IGE)

**Test Steps:**

1. **Select an IGE Project**
   - Navigate to an IGE project
   - Note existing budget data

2. **Create Monthly Report**
   - Go to Reports → Create Monthly Report
   - Select the IGE project
   - Verify budget data appears
   - Check that budgets are returned directly (no calculation)
   - Verify field mappings (name → particular, total_amount → amount)

3. **Verify Data**
   - Check that all budget fields are present
   - Verify no calculation errors
   - Check that data matches database

4. **Export PDF/Word**
   - Export and verify data matches

**Expected Results:**
- ✅ Budget data loads correctly
- ✅ No calculation applied (direct mapping)
- ✅ All fields present
- ✅ Data matches database

**Test Projects Needed:**
- IGE project with budget data
- Verify field mappings are correct (may need adjustment)

---

#### 2.5 Individual - Initial - Educational support (IIES)

**Test Steps:**

1. **Select an IIES Project**
   - Navigate to an IIES project
   - Note existing expense data
   - Note contribution values:
     - `iies_expected_scholarship_govt`
     - `iies_support_other_sources`
     - `iies_beneficiary_contribution`

2. **Create Monthly Report**
   - Go to Reports → Create Monthly Report
   - Select the IIES project
   - Verify expense details appear
   - Check `amount_sanctioned` calculation:
     - `total_contribution = sum of all 3 sources`
     - `contribution_per_row = total_contribution / total_rows`
     - `amount_sanctioned = max(0, iies_amount - contribution_per_row)`

3. **Verify Calculations**
   - Count total expense detail rows
   - Calculate expected total contribution
   - Calculate expected contribution per row
   - Verify each row's `amount_sanctioned` is correct
   - Verify no negative amounts

4. **Export PDF/Word**
   - Export and verify data matches

**Expected Results:**
- ✅ All 3 contribution sources combined
- ✅ Contribution distributed equally across all rows
- ✅ Amount sanctioned = iies_amount - (total_contribution / total_rows)
- ✅ No negative amounts
- ✅ Calculations match old implementation

**Test Projects Needed:**
- IIES project with multiple expense detail rows
- IIES project with all 3 contribution sources > 0
- IIES project with some null contributions

---

#### 2.6 Individual - Ongoing Educational support (IES)

**Test Steps:**

1. **Select an IES Project**
   - Navigate to an IES project
   - Note existing expense data
   - Note contribution values:
     - `expected_scholarship_govt`
     - `support_other_sources`
     - `beneficiary_contribution`

2. **Create Monthly Report**
   - Go to Reports → Create Monthly Report
   - Select the IES project
   - Verify expense details appear
   - Check `amount_sanctioned` calculation:
     - `total_contribution = sum of all 3 sources`
     - `contribution_per_row = total_contribution / total_rows`
     - `amount_sanctioned = max(0, amount - contribution_per_row)`

3. **Verify Calculations**
   - Count total expense detail rows
   - Calculate expected total contribution
   - Calculate expected contribution per row
   - Verify each row's `amount_sanctioned` is correct
   - Verify no negative amounts

4. **Export PDF/Word**
   - Export and verify data matches

**Expected Results:**
- ✅ All 3 contribution sources combined
- ✅ Contribution distributed equally across all rows
- ✅ Amount sanctioned = amount - (total_contribution / total_rows)
- ✅ No negative amounts
- ✅ Calculations match old implementation

**Test Projects Needed:**
- IES project with multiple expense detail rows
- IES project with all 3 contribution sources > 0
- IES project with some null contributions

---

## Phase 3: Side-by-Side Comparison

### Purpose

Verify that the new implementation produces **exactly the same results** as the old implementation.

### Method 1: Git Comparison

1. **Checkout Old Code**
   ```bash
   git stash  # Save current changes
   git checkout <commit-before-standardization>
   ```

2. **Test Old Implementation**
   - Create a report for each project type
   - Note the `amount_sanctioned` values
   - Export PDF/Word
   - Save results

3. **Checkout New Code**
   ```bash
   git checkout <current-branch>
   git stash pop  # Restore changes
   ```

4. **Test New Implementation**
   - Create a report for the same projects
   - Compare `amount_sanctioned` values
   - Verify they match exactly

### Method 2: Database Query Comparison

1. **Get Project IDs**
   - Select one project of each type
   - Note their `project_id` values

2. **Run Old Logic (from git history)**
   - Copy old `getBudgetDataByProjectType` method
   - Create a temporary test script
   - Run for each project
   - Save results

3. **Run New Logic**
   - Use `BudgetCalculationService::getBudgetsForReport()`
   - Run for same projects
   - Compare results

### Comparison Checklist

For each project type, verify:

- ✅ Same number of budget rows
- ✅ Same `amount_sanctioned` values (exact match)
- ✅ Same field values (particular, amounts, etc.)
- ✅ Same collection structure
- ✅ Same export output

---

## Common Issues & Solutions

### Issue 1: Configuration Not Found

**Error:** `Budget configuration not found for project type: X`

**Solution:**
- Check `config/budget.php` has entry for project type
- Verify project type name matches exactly (case-sensitive)
- Clear config cache: `php artisan config:clear`

---

### Issue 2: Strategy Class Not Found

**Error:** `Strategy class not found: X`

**Solution:**
- Check strategy class exists in `app/Services/Budget/Strategies/`
- Verify namespace is correct
- Check autoload: `composer dump-autoload`

---

### Issue 3: Field Mapping Errors

**Error:** Field not found or null values

**Solution:**
- Verify field names in `config/budget.php` match database
- Check model relationships
- Verify field exists in database schema
- Test with actual project data

---

### Issue 4: Phase Selection Issues

**Error:** Wrong phase budgets shown

**Solution:**
- Check `current_phase` value in project
- Verify fallback to `max('phase')` works
- Check phase values in budget table
- Review logs for phase selection

---

### Issue 5: Calculation Mismatch

**Error:** Amount sanctioned doesn't match expected

**Solution:**
- Verify contribution values in database
- Check calculation formula
- Review logs for calculation details
- Compare with old implementation step-by-step

---

## Test Data Requirements

### Minimum Test Data Needed

1. **Development Projects (6 types)**
   - 1 project of each type
   - Projects with `current_phase` set
   - Projects with `current_phase = null`
   - Budgets in multiple phases

2. **ILP**
   - 1 project with multiple budget rows
   - `beneficiary_contribution > 0`

3. **IAH**
   - 1 project with multiple budget rows
   - `family_contribution > 0`

4. **IGE**
   - 1 project with budget data
   - Verify field mappings

5. **IIES**
   - 1 project with expense details
   - All 3 contribution sources > 0

6. **IES**
   - 1 project with expense details
   - All 3 contribution sources > 0

---

## Test Execution Log

### Template

```
Date: [Date]
Tester: [Name]
Project Type: [Type]
Project ID: [ID]

Test Steps:
1. [Step]
2. [Step]
...

Results:
- [Result]
- [Result]
...

Issues Found:
- [Issue]
...

Status: ✅ PASS / ❌ FAIL
```

---

## Success Criteria

### All Tests Must Pass

- ✅ All unit tests pass
- ✅ All project types load budgets correctly
- ✅ All calculations match old implementation
- ✅ PDF/Word exports work correctly
- ✅ No errors in logs
- ✅ No negative amounts
- ✅ Phase selection works correctly
- ✅ Export mode returns data without calculation

---

## Next Steps After Testing

1. **If All Tests Pass:**
   - Proceed to Phase 5 (Documentation & Cleanup)
   - Update implementation status document
   - Mark testing as complete

2. **If Tests Fail:**
   - Document issues
   - Fix bugs
   - Re-test
   - Update implementation plan

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Ready for Testing

---

**End of Testing Guide**
