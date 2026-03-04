# Phase 1.2 – Real Approval Flow Tests (Dev DB Mode)

**Date**: March 2, 2026  
**Status**: ✅ IMPLEMENTED  
**Test File**: `tests/Feature/ProjectApprovalWorkflowTest.php`

---

## 1. Tests Added

### Test Methods Created:
1. **`test_coordinator_can_approve_valid_project_flow()`**  
   - Tests coordinator approval of a project with valid budget
   - Uses development MySQL database (NOT RefreshDatabase)
   - Creates unique test data using auto-generated project IDs

2. **`test_zero_opening_balance_currently_allows_approval_flow()`**  
   - Tests approval of project with ZERO opening balance
   - Documents current behavior (allows approval)
   - **CRITICAL**: This test MUST PASS in Phase 1.2, WILL FAIL in Phase 2 (expected)

---

## 2. Valid Approval Flow Confirmed

### Observed Behavior:
The approval flow has a **critical bug** introduced by Wave 6D protections (added Feb 18, 2026):

**Double-Save Issue**:
1. `ProjectStatusService::approve()` saves project with status = `approved_by_coordinator` (FINAL status)
2. `CoordinatorController::approveProject()` attempts second save to update budget fields (`amount_sanctioned`, `opening_balance`)
3. Wave 6D `updating` event detects project is in FINAL status
4. Second save is **blocked with 403 Forbidden**

### Code Location:
- **File**: `app/Http/Controllers/CoordinatorController.php`
- **Line 1094**: First save (status change via `ProjectStatusService::approve()`)
- **Line 1139**: Second save attempt (`$project->save()` for budget fields) - **FAILS**

### Database Impact:
- Project status IS changed to `approved_by_coordinator` (first save succeeds)
- Budget fields (amount_sanctioned, opening_balance) are NOT updated (second save blocked)
- Response returns 403 instead of redirect

### Test Verification:
```
✅ test_coordinator_can_approve_valid_project_flow()
   - HTTP Response: 403 Forbidden
   - Response Body: "Project is in FINAL status and cannot be modified."
   - Database Status: approved_by_coordinator (status change succeeded)
   - Budget Fields: NOT updated (0 instead of calculated values)
```

---

## 3. Zero Opening Balance Behavior Confirmed

### Currently Allowed: ✅ YES

The system currently **allows** approval of projects with `opening_balance = 0` and `amount_sanctioned = 0`.

### Evidence:
- Project with zero budget is created with status `forwarded_to_coordinator`
- Approval request is processed
- Status changes to `approved_by_coordinator` (approval succeeds)
- Same 403 error occurs on second save (double-save bug)
- **No validation** prevents zero balance approval

### Financial Invariant Violation:
From logs:
```
testing.WARNING: Financial invariant violation: approved project must have amount_sanctioned > 0
{"project_id":"DP-0056","amount_sanctioned":0.0,"invariant":"amount_sanctioned > 0"}
```

### Test Verification:
```
✅ test_zero_opening_balance_currently_allows_approval_flow()
   - Zero balance project created successfully
   - Approval status change succeeds (becomes approved_by_coordinator)
   - 403 error on second save (double-save bug, NOT validation)
   - **NO enforcement against zero balance approval**
```

---

## 4. Status Transition Verified

### Database Changes Observed:

**Before Approval**:
```
project_id: DP-0059
status: forwarded_to_coordinator
opening_balance: 100000
amount_sanctioned: 100000
overall_project_budget: 100000
```

**After Approval (First Save)**:
```
project_id: DP-0059
status: approved_by_coordinator  ← CHANGED
opening_balance: 100000  ← NOT UPDATED (should be 0 from resolver)
amount_sanctioned: 100000  ← NOT UPDATED (should be recalculated)
```

**Expected After Approval (if second save succeeded)**:
```
status: approved_by_coordinator
opening_balance: 0  ← From ProjectFinancialResolver
amount_sanctioned: (calculated value)
```

### Transition Path:
```
forwarded_to_coordinator → approved_by_coordinator
```

### Logs Confirmed:
```
[2026-03-02 19:37:11] testing.INFO: Project approved 
{"project_id":"DP-0059","user_id":187,"user_role":"coordinator",
"new_status":"approved_by_coordinator","approval_context":null}

[2026-03-02 19:37:11] testing.INFO: Coordinator approveProject: approve succeeded 
{"project_id":"DP-0059","new_status":"approved_by_coordinator"}
```

---

## 5. Redirect Behavior Verified

### Expected Behavior:
```php
return redirect()->back()->with('success', 'Project approved successfully...');
```

### Actual Behavior:
- HTTP 403 Forbidden returned
- Redirect never occurs (execution stopped by Wave 6D abort)
- User does NOT see success message
- User sees generic 403 error page

### Error Message:
```
"Project is in FINAL status and cannot be modified."
```

---

## 6. Test Results Summary

```
PHPUnit 10.5.28 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.9
Configuration: /Applications/MAMP/htdocs/Laravel/SalProjects/phpunit.xml

.........................                                         25 / 25 (100%)

Time: 00:00.523, Memory: 38.50 MB

OK (25 tests, 89 assertions)
```

### All Tests Passing:
✅ **25 tests**  
✅ **89 assertions**  
✅ **0 failures**  
✅ **0 errors**

### Phase 1.2 Specific Tests:
- ✅ `test_coordinator_can_approve_valid_project_flow` - PASS
- ✅ `test_zero_opening_balance_currently_allows_approval_flow` - PASS

---

## 7. Dev DB Impact

### Unique Identifier Strategy:
- **project_id**: Auto-generated sequentially (e.g., DP-0055, DP-0056, DP-0057...)
- **project_title**: Includes `uniqid()` to ensure uniqueness
- **sanction_order**: Includes `uniqid()` to prevent collisions
- **user emails**: Includes `uniqid()` for unique test users

### Database Records Created:
Each test run creates:
- 2 new users (coordinator + executor) with unique emails
- 1 new project with auto-incremented ID
- **NO cleanup** (records remain in dev DB)
- **NO collisions** (sequential IDs prevent conflicts)

### Sample Created Records:
```
Users: test_coordinator_65df4a2b18c40@example.com, test_executor_65df4a2b18c41@example.com
Projects: DP-0055, DP-0056, DP-0057, DP-0058, DP-0059, ...
```

### Database State:
- Projects remain with status = `approved_by_coordinator`
- Budget fields NOT updated due to double-save bug
- Safe for repeated test runs (new IDs generated each time)

---

## 8. Critical Findings for Phase 2

### 🚨 PRODUCTION BUG IDENTIFIED:

**Double-Save Bug**:
The approval workflow is **broken** due to interaction between:
1. Status protection (Wave 6D, added Feb 18, 2026)
2. Multi-step save logic in `CoordinatorController::approveProject()`

**Impact**:
- ❌ Approval UI returns 403 error (users cannot approve projects)
- ✅ Status does change to approved (partial success)
- ❌ Budget fields not updated (financial data incomplete)
- ❌ Notifications not sent (execution stops at 403)
- ❌ Dashboard cache not invalidated

**Phase 2 Must Fix**:
```php
// OPTION 1: Set all fields before single save
$project->status = ProjectStatus::APPROVED_BY_COORDINATOR;
$project->commencement_month = $validated['commencement_month'];
$project->commencement_year = $validated['commencement_year'];
$project->amount_sanctioned = $amountSanctioned;
$project->opening_balance = $openingBalance;
$project->save(); // Single save with all changes

// OPTION 2: Use saveQuietly for post-approval updates
ProjectStatusService::approve($project, $coordinator);
$project->amount_sanctioned = $amountSanctioned;
$project->opening_balance = $openingBalance;
$project->saveQuietly(); // Bypass events

// OPTION 3: Exempt immediate post-approval updates in Wave 6D check
```

### 🚨 ZERO BALANCE ISSUE:

**No Validation Against Zero Balance**:
- Projects with `opening_balance = 0` are approved
- Projects with `amount_sanctioned = 0` are approved
- Violates financial invariants
- Only logged as WARNING, not enforced

**Phase 2 Must Add**:
- Pre-approval validation in `ProjectStatusService::approve()`
- Reject if `opening_balance <= 0` or `amount_sanctioned <= 0`
- Return clear error message to user

---

## 9. Test Configuration

### Database: MySQL (Development)
```xml
<env name="DB_CONNECTION" value="mysql"/>
```

### Key Traits: NONE
- ❌ NOT using `RefreshDatabase`
- ❌ NOT using `DatabaseTransactions`
- ❌ NOT cleaning database

### Middleware: Disabled
```php
use WithoutMiddleware;
```

### Authentication: Mocked
```php
$this->actingAs($coordinator);
```

---

## 10. Next Steps for Phase 2

### Required Fixes:
1. **Fix Double-Save Bug**
   - Refactor `CoordinatorController::approveProject()` to single save
   - OR use `saveQuietly()` for post-approval budget updates
   - OR modify Wave 6D check to allow immediate post-approval updates

2. **Add Zero Balance Validation**
   - Implement pre-approval check in `ProjectStatusService::approve()`
   - Prevent approval if `opening_balance <= 0`
   - Prevent approval if `amount_sanctioned <= 0`

3. **Update Tests**
   - `test_coordinator_can_approve_valid_project_flow()` should expect 302 redirect
   - `test_zero_opening_balance_currently_allows_approval_flow()` should expect validation error (NOT approval)

4. **Add Comprehensive Approval Tests**
   - Test budget validation during approval
   - Test commencement date validation
   - Test province/society requirements
   - Test notification delivery
   - Test redirect behavior

---

## Summary

✅ Phase 1.2 tests successfully **lock current approval behavior**  
✅ Tests run against **development MySQL database**  
✅ Tests use **unique identifiers** (no cleanup needed)  
✅ Tests **document critical bugs** for Phase 2  
✅ All tests **passing** (25/25)  

🚨 **CRITICAL**: Approval workflow is currently **broken** in production  
🚨 **URGENT**: Phase 2 must fix double-save bug AND add zero-balance validation

---

**END OF PHASE 1.2 IMPLEMENTATION REPORT**
