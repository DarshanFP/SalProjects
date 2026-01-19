# Phase 11: Test Script Runner - Automated Testing Guide

**Date:** January 2025  
**Status:** Ready for Execution  
**Purpose:** Automated and manual testing scripts for Report Views Enhancement

---

## Overview

This document provides automated test scripts and manual testing procedures to verify all Phase 11 requirements across all 12 project types.

---

## Test Scripts Available

### 1. PHPUnit Feature Tests (Backend)
**Location:** `tests/Feature/Reports/Monthly/ReportViewsIndexingTest.php`

**Run Command:**
```bash
# Run all report view tests
php artisan test --filter ReportViewsIndexing

# Run specific test
php artisan test tests/Feature/Reports/Monthly/ReportViewsIndexingTest.php

# Run with verbose output
php artisan test --filter ReportViewsIndexing --verbose
```

**Tests Included:**
- ✅ Page loads for all project types
- ✅ JavaScript functions exist in views
- ✅ Index badges present
- ✅ Activity cards present
- ✅ Report creation works
- ✅ Report editing works

---

### 2. Browser Console Tests (Frontend/JavaScript)
**Location:** `resources/js/test-phase11-browser-console.js`

**Usage:**
1. Open report create/edit page in browser
2. Open browser console (F12)
3. Copy entire contents of `test-phase11-browser-console.js`
4. Paste into console and press Enter
5. Run: `runAllTests()`

**Individual Tests:**
```javascript
// Test function existence
testFunctionExistence()

// Test outlook indexing
testOutlookIndexing()

// Test outlook add/remove
testOutlookAddRemove()

// Test statements of account indexing
testStatementsOfAccountIndexing()

// Test activity cards
testActivityCards()

// Test activity card toggle
testActivityCardToggle()

// Test photos indexing
testPhotosIndexing()

// Test attachments indexing
testAttachmentsIndexing()

// Run all tests
runAllTests()
```

---

## Quick Start Testing Guide

### Step 1: Environment Setup
```bash
# Navigate to project directory
cd /Applications/MAMP/htdocs/Laravel/SalProjects

# Ensure dependencies are installed
composer install
npm install

# Run migrations (if needed)
php artisan migrate

# Seed test data (create test projects for all types)
php artisan db:seed --class=ReportTestDataSeeder
```

### Step 2: Run PHPUnit Tests
```bash
# Run all tests
php artisan test --filter ReportViewsIndexing

# Check test results
# All tests should pass ✅
```

### Step 3: Run Browser Console Tests
1. Start Laravel development server:
   ```bash
   php artisan serve
   ```

2. Open browser and navigate to report create page for each project type

3. Open browser console (F12) and paste test script

4. Run comprehensive tests:
   ```javascript
   runAllTests()
   ```

---

## Comprehensive Test Checklist by Project Type

### Automated Test Script

Use this script to test all project types systematically:

```bash
#!/bin/bash
# test-all-project-types.sh
# Run this script to test all 12 project types

echo "=== Phase 11: Testing All Project Types ==="

# Array of all project types
declare -a project_types=(
    "Development Projects"
    "Livelihood Development Projects"
    "Institutional Ongoing Group Educational proposal"
    "Residential Skill Training Proposal 2"
    "PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER"
    "CHILD CARE INSTITUTION"
    "Rural-Urban-Tribal"
    "NEXT PHASE - DEVELOPMENT PROPOSAL"
    "Individual - Livelihood Application"
    "Individual - Access to Health"
    "Individual - Ongoing Educational support"
    "Individual - Initial - Educational support"
)

# Test each project type
for project_type in "${project_types[@]}"; do
    echo "Testing: $project_type"
    # Here you would run your test commands
    # Example: php artisan test --filter "$project_type"
done

echo "=== All Tests Complete ==="
```

---

## Manual Testing Procedure

### For Each Project Type:

#### 1. Create Report Page Testing

**Steps:**
1. Navigate to: `/reports/monthly/create/{project_id}`
2. Open browser console (F12)
3. Run browser console tests:
   ```javascript
   runAllTests()
   ```
4. Verify all tests pass
5. Test manually:
   - [ ] Add outlook entry → verify index updates
   - [ ] Remove outlook entry → verify reindexing
   - [ ] Add account row → verify index updates
   - [ ] Remove account row → verify reindexing
   - [ ] Add photo group → verify index updates
   - [ ] Add attachment → verify index updates
   - [ ] Click activity card → verify expand/collapse
   - [ ] Fill activity form → verify status badge updates

#### 2. Edit Report Page Testing

**Steps:**
1. Create a report first (or use existing)
2. Navigate to: `/reports/monthly/{report_id}/edit`
3. Run browser console tests:
   ```javascript
   runAllTests()
   ```
4. Verify:
   - [ ] Existing data displays with correct indexes
   - [ ] Activity cards work in edit mode
   - [ ] Add/remove operations work
   - [ ] Reindexing works correctly

#### 3. Form Submission Testing

**Steps:**
1. Fill in all required fields
2. Submit form
3. Verify in database:
   - [ ] Report created/updated
   - [ ] All outlook entries saved
   - [ ] All account rows saved
   - [ ] All activity data saved
   - [ ] Index numbers preserved

#### 4. Status Management Testing

**Steps:**
1. As Executor: Submit report
2. Verify status changes to `submitted_to_provincial`
3. As Provincial: Forward report
4. Verify status changes to `forwarded_to_coordinator`
5. As Coordinator: Approve report
6. Verify status changes to `approved_by_coordinator`
7. Test revert flow with reason

---

## Test Results Tracking

### Create Test Results File

Create a test results file to track testing progress:

**File:** `Documentations/REVIEW/5th Review/Report Views/Phase_11_Test_Results.md`

**Template:**
```markdown
# Phase 11: Test Results

## Test Execution Log

### Development Projects
- [ ] PHPUnit Tests: Pass/Fail
- [ ] Browser Console Tests: Pass/Fail
- [ ] Create Report: Pass/Fail
- [ ] Edit Report: Pass/Fail
- [ ] Form Submission: Pass/Fail
- [ ] Status Management: Pass/Fail

### Livelihood Development Projects
...

## Issues Found
1. Issue description
2. Issue description

## Summary
- Total Tests: X
- Passed: Y
- Failed: Z
```

---

## Automated Test Execution Script

Save this as `test-phase11.sh`:

```bash
#!/bin/bash

echo "=========================================="
echo "Phase 11: Automated Test Execution"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Run PHPUnit tests
echo "Step 1: Running PHPUnit Feature Tests..."
php artisan test --filter ReportViewsIndexing

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ PHPUnit tests passed${NC}"
else
    echo -e "${RED}❌ PHPUnit tests failed${NC}"
fi

echo ""
echo "Step 2: Browser Console Tests"
echo "Please open report page in browser and run:"
echo "  runAllTests()"
echo ""
echo "Step 3: Manual Testing"
echo "Please follow manual testing procedure in Phase_11_Test_Script_Runner.md"
echo ""

echo "=========================================="
echo "Test execution complete"
echo "=========================================="
```

Make executable:
```bash
chmod +x test-phase11.sh
./test-phase11.sh
```

---

## Testing Workflow

### 1. Pre-Testing Checklist
- [ ] Development environment running
- [ ] Test data seeded
- [ ] Test projects created for all 12 types
- [ ] Browser console ready (F12)

### 2. Automated Tests
- [ ] Run PHPUnit tests
- [ ] Review test results
- [ ] Fix any failing tests

### 3. Browser Console Tests
- [ ] Open report create page for each project type
- [ ] Run browser console tests
- [ ] Document results

### 4. Manual Testing
- [ ] Test add/remove operations
- [ ] Test form submission
- [ ] Test edit functionality
- [ ] Test status management

### 5. Documentation
- [ ] Document test results
- [ ] Document any issues found
- [ ] Update test checklist

---

## Troubleshooting

### Common Issues

#### PHPUnit Tests Fail
- Check database connection
- Verify test data exists
- Check model factories

#### Browser Console Tests Fail
- Verify JavaScript files loaded
- Check for console errors
- Verify page is fully loaded

#### Manual Tests Fail
- Clear browser cache
- Check for JavaScript errors
- Verify database state

---

## Test Coverage Summary

| Test Type | Coverage | Status |
|-----------|----------|--------|
| PHPUnit Feature Tests | Backend functionality | ✅ Ready |
| Browser Console Tests | JavaScript/frontend | ✅ Ready |
| Manual Testing | User workflows | ⏳ Pending |

---

## Next Steps

1. **Execute Automated Tests:**
   ```bash
   php artisan test --filter ReportViewsIndexing
   ```

2. **Execute Browser Console Tests:**
   - Open report pages
   - Run `runAllTests()`

3. **Execute Manual Testing:**
   - Follow manual testing procedure
   - Document results

4. **Fix Issues:**
   - Address any failing tests
   - Re-test after fixes

5. **Document Results:**
   - Update test results file
   - Mark Phase 11 as complete

---

**Last Updated:** January 2025  
**Status:** ✅ Test Scripts Ready for Execution
