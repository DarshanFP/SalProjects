# Comprehensive Code Review Report

## SalProjects - Laravel Application

**Review Date:** Generated on Review  
**Reviewer:** Code Analysis System  
**Scope:** Complete Codebase Review

---

## Executive Summary

This comprehensive code review identified **multiple categories of issues** across the codebase including:

-   **Security vulnerabilities**
-   **Code quality issues**
-   **Validation problems**
-   **JavaScript issues**
-   **Architecture concerns**
-   **Best practice violations**

---

## Table of Contents

1. [Critical Security Issues](#critical-security-issues)
2. [Validation & Input Handling Issues](#validation--input-handling-issues)
3. [Code Quality Issues](#code-quality-issues)
4. [JavaScript & Frontend Issues](#javascript--frontend-issues)
5. [Database & Query Issues](#database--query-issues)
6. [Error Handling Issues](#error-handling-issues)
7. [Architecture & Design Issues](#architecture--design-issues)
8. [Specific File Issues](#specific-file-issues)
9. [Recommendations](#recommendations)

---

## Critical Security Issues

### 1. Sensitive Data Logging

**Severity:** HIGH  
**Location:** Multiple Controllers

**Issue:** The application logs entire request data using `$request->all()`, which may expose sensitive information.

**Affected Files:**

-   `app/Http/Controllers/Projects/ProjectController.php` (Line 724, 881, 1426)
-   `app/Http/Controllers/Projects/LogicalFrameworkController.php` (Line 30-31)
-   `app/Http/Controllers/ProvincialController.php` (Line 399)
-   `app/Http/Controllers/Projects/GeneralInfoController.php` (Line 91)
-   `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` (Line 22, 134)
-   `app/Http/Controllers/Reports/Monthly/ReportController.php` (Line 412)

**Example:**

```php
Log::info('ProjectController@store - Data received from form', $request->all());
```

**Recommendation:**

-   Use `$request->only()` to log only necessary fields
-   Implement data sanitization before logging
-   Remove sensitive fields (passwords, tokens, etc.) from logs
-   Consider using Laravel's log filtering

**Fix:**

```php
Log::info('ProjectController@store - Data received from form', [
    'project_type' => $request->project_type,
    'project_title' => $request->project_title,
    // Only log non-sensitive fields
]);
```

### 2. Request Data Merging Without Validation

**Severity:** MEDIUM  
**Location:** `app/Http/Controllers/Projects/ProjectController.php`

**Issue:** Request data is merged without proper validation checks.

**Lines:**

-   Line 732: `$request->merge(['project_id' => $project->project_id]);`
-   Line 1430: `$request->merge(['phases' => $request->input('phases', [])]);`

**Recommendation:**

-   Validate merged data before merging
-   Use validated data arrays instead of merging to request

### 3. Missing CSRF Protection Verification

**Severity:** MEDIUM  
**Location:** Routes and Controllers

**Issue:** While Laravel provides CSRF protection by default, some AJAX requests may not be properly protected.

**Recommendation:**

-   Verify all AJAX requests include CSRF tokens
-   Check that API routes (if any) use proper authentication

---

## Validation & Input Handling Issues

### 1. Syntax Error in Validation Rule

**Severity:** HIGH  
**Location:** `app/Http/Controllers/Projects/GeneralInfoController.php`

**Issue:** Double pipe operator in validation rule.

**Line 30:**

```php
'current_phase' => 'nullable||integer',  // Double pipe (||) instead of single (|)
```

**Fix:**

```php
'current_phase' => 'nullable|integer',
```

### 2. Inconsistent Validation Patterns

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** Some controllers use inline validation, others use FormRequest classes. Inconsistent approach makes maintenance difficult.

**Affected Files:**

-   Most controllers use inline `$request->validate()`
-   Only `ProfileUpdateRequest` uses FormRequest pattern

**Recommendation:**

-   Create FormRequest classes for major operations
-   Standardize validation approach across the application
-   Move validation logic to dedicated FormRequest classes

### 3. Missing Validation for File Uploads

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** File upload validation may be incomplete in some controllers.

**Recommendation:**

-   Ensure all file uploads have:
    -   MIME type validation
    -   File size limits
    -   File extension validation
    -   Virus scanning (if applicable)

### 4. Direct Request Parameter Usage in Queries

**Severity:** LOW-MEDIUM  
**Location:** Multiple Controllers

**Issue:** Request parameters used directly in where clauses (though Laravel's query builder protects against SQL injection, validation is still needed).

**Affected Files:**

-   `app/Http/Controllers/ProjectController.php` (Line 1796)
-   `app/Http/Controllers/ProvincialController.php` (Lines 44, 49, 53, etc.)
-   `app/Http/Controllers/CoordinatorController.php` (Multiple lines)
-   `app/Http/Controllers/ExecutorController.php` (Line 24, 51, etc.)

**Example:**

```php
$projectsQuery->where('project_type', $request->project_type);
```

**Recommendation:**

-   Validate all request parameters before using in queries
-   Use whitelist validation for enum-like fields (project_type, status, etc.)
-   Consider using request validation middleware

---

## Code Quality Issues

### 1. Large Controller Files

**Severity:** MEDIUM  
**Location:** `app/Http/Controllers/Projects/ProjectController.php`

**Issue:** ProjectController has 1841 lines, violating Single Responsibility Principle.

**Recommendation:**

-   Break down into smaller, focused controllers
-   Extract common logic to service classes
-   Use traits for shared functionality
-   Consider using Repository pattern

### 2. Commented Out Code Blocks

**Severity:** LOW  
**Location:** Multiple Files

**Issue:** Large blocks of commented code exist in the codebase.

**Affected Files:**

-   `app/Http/Controllers/Projects/ProjectController.php` (Lines 293-482, large commented block)
-   `app/Http/Controllers/Projects/ProjectControllerOld.text` (Entire file appears to be old code)
-   `resources/views/projects/partials/scripts.blade.php` (Lines 181-264, commented phase functionality)

**Recommendation:**

-   Remove commented code blocks
-   Use version control (Git) for code history
-   If code is needed for reference, move to documentation

### 3. Duplicate Code Patterns

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** Similar code patterns repeated across multiple controllers (e.g., project type switching, status handling).

**Recommendation:**

-   Extract common patterns to service classes
-   Use strategy pattern for project type handling
-   Create base controller with shared methods

### 4. Inconsistent Naming Conventions

**Severity:** LOW  
**Location:** Multiple Files

**Issue:** Some inconsistencies in variable naming and method naming.

**Examples:**

-   `$iiesExpenses` vs `$IIESExpenses` (inconsistent casing)
-   Mix of camelCase and snake_case

**Recommendation:**

-   Follow PSR-12 coding standards
-   Use consistent naming throughout
-   Use Laravel's naming conventions

### 5. Missing Type Hints

**Severity:** LOW  
**Location:** Multiple Controllers

**Issue:** Some methods lack return type hints and parameter type hints.

**Recommendation:**

-   Add return type hints to all methods
-   Add parameter type hints where applicable
-   Use strict types (`declare(strict_types=1);`)

### 6. Magic Strings and Numbers

**Severity:** MEDIUM  
**Location:** Multiple Files

**Issue:** Hard-coded strings for project types, statuses, and roles throughout the codebase.

**Example:**

```php
case 'Individual - Ongoing Educational support':
case 'Individual - Initial - Educational support':
```

**Recommendation:**

-   Create constants or enums for project types
-   Create constants for status values
-   Use configuration files for magic values

**Example Fix:**

```php
class ProjectType {
    const INDIVIDUAL_ONGOING_EDUCATIONAL = 'Individual - Ongoing Educational support';
    const INDIVIDUAL_INITIAL_EDUCATIONAL = 'Individual - Initial - Educational support';
    // ... etc
}
```

---

## JavaScript & Frontend Issues

### 1. Console.log Statements in Production

**Severity:** LOW  
**Location:** `resources/views/projects/partials/scripts.blade.php`

**Issue:** Console.log statements left in production code.

**Line 7:**

```javascript
console.log(`${key}: ${value}`);
```

**Recommendation:**

-   Remove all console.log statements
-   Use proper logging service if debugging is needed
-   Consider using environment-based logging

### 2. Potential Null Reference Errors

**Severity:** MEDIUM  
**Location:** `resources/views/projects/partials/scripts.blade.php`

**Issue:** JavaScript code doesn't check if DOM elements exist before accessing them.

**Lines 13-22:**

```javascript
document.getElementById('in_charge').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    // No check if element exists
```

**Recommendation:**

-   Add null checks before accessing DOM elements
-   Use optional chaining where applicable
-   Add defensive programming practices

**Fix:**

```javascript
const inChargeElement = document.getElementById("in_charge");
if (inChargeElement) {
    inChargeElement.addEventListener("change", function () {
        // ...
    });
}
```

### 3. Inline JavaScript in Blade Templates

**Severity:** LOW  
**Location:** Multiple Blade Files

**Issue:** Large JavaScript blocks embedded in Blade templates make maintenance difficult.

**Recommendation:**

-   Extract JavaScript to separate files
-   Use Laravel Mix/Vite for asset compilation
-   Organize JavaScript into modules

### 4. Missing Error Handling in JavaScript

**Severity:** MEDIUM  
**Location:** `resources/views/projects/partials/scripts.blade.php`

**Issue:** JavaScript functions don't handle errors gracefully.

**Recommendation:**

-   Add try-catch blocks for critical operations
-   Implement proper error handling
-   Show user-friendly error messages

---

## Database & Query Issues

### 1. Missing Eager Loading

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** Some queries may suffer from N+1 query problems.

**Example in ProjectController:**

```php
$projects = Project::where(function($query) use ($user) {
    // ...
})->get();
// Later accessing $project->user without eager loading
```

**Recommendation:**

-   Use `with()` to eager load relationships
-   Review all queries for N+1 problems
-   Use Laravel Debugbar to identify N+1 issues

**Fix:**

```php
$projects = Project::with('user', 'budgets', 'attachments')
    ->where(function($query) use ($user) {
        // ...
    })->get();
```

### 2. Query Optimization Opportunities

**Severity:** LOW  
**Location:** Multiple Controllers

**Issue:** Some queries could be optimized with proper indexing and query structure.

**Recommendation:**

-   Review database indexes
-   Use `select()` to limit columns when full models aren't needed
-   Consider using database query caching for frequently accessed data

### 3. Transaction Handling

**Severity:** LOW  
**Location:** Multiple Controllers

**Issue:** While transactions are used, some complex operations might benefit from better transaction boundaries.

**Recommendation:**

-   Review transaction boundaries
-   Ensure proper rollback on all error paths
-   Consider using database transactions for related operations

---

## Error Handling Issues

### 1. Generic Error Messages

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** Generic error messages don't provide useful information for debugging or users.

**Example:**

```php
return redirect()->back()->withErrors(['error' => 'There was an error creating the project. Please try again.']);
```

**Recommendation:**

-   Provide more specific error messages
-   Log detailed errors for developers
-   Show user-friendly messages to end users
-   Use Laravel's exception handling features

### 2. Inconsistent Error Handling

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** Different controllers handle errors differently.

**Recommendation:**

-   Standardize error handling approach
-   Create custom exception classes
-   Use Laravel's exception handler for consistent error responses

### 3. Missing Error Logging

**Severity:** LOW  
**Location:** Some Controllers

**Issue:** Not all errors are properly logged.

**Recommendation:**

-   Ensure all exceptions are logged
-   Use structured logging
-   Include context in error logs

---

## Architecture & Design Issues

### 1. Fat Controllers

**Severity:** MEDIUM  
**Location:** Multiple Controllers

**Issue:** Controllers contain too much business logic.

**Recommendation:**

-   Move business logic to service classes
-   Use Repository pattern for data access
-   Keep controllers thin (only handle HTTP concerns)

### 2. Missing Service Layer

**Severity:** MEDIUM  
**Location:** Application-wide

**Issue:** Business logic is directly in controllers instead of service classes.

**Recommendation:**

-   Create service classes for complex operations
-   Extract business logic from controllers
-   Use dependency injection for services

**Example Structure:**

```
app/
  Services/
    ProjectService.php
    ReportService.php
    UserService.php
```

### 3. Tight Coupling

**Severity:** MEDIUM  
**Location:** Multiple Files

**Issue:** Controllers are tightly coupled to models and other controllers.

**Example:**

```php
$project = (new GeneralInfoController())->store($request);
```

**Recommendation:**

-   Use dependency injection
-   Create interfaces for services
-   Reduce direct instantiation

### 4. Missing API Resources

**Severity:** LOW  
**Location:** Application-wide

**Issue:** API responses (if any) don't use Laravel API Resources for consistent formatting.

**Recommendation:**

-   Use API Resources for JSON responses
-   Ensure consistent response format
-   Version API responses

---

## Specific File Issues

### 1. ProjectController.php (1841 lines)

**Issues:**

-   Too large, violates SRP
-   Large commented code blocks (lines 293-482)
-   Complex switch statements for project types
-   Multiple responsibilities (CRUD, status management, etc.)

**Recommendations:**

-   Split into multiple controllers:
    -   `ProjectController` (basic CRUD)
    -   `ProjectStatusController` (status management)
    -   `ProjectTypeController` (project type-specific operations)
-   Extract project type logic to service classes
-   Remove commented code

### 2. GeneralInfoController.php

**Issues:**

-   Validation syntax error (line 30)
-   Logging sensitive data (line 91)
-   Missing return type hints

**Recommendations:**

-   Fix validation syntax error
-   Sanitize logged data
-   Add return type hints

### 3. scripts.blade.php

**Issues:**

-   Console.log statements
-   Missing null checks
-   Large inline JavaScript
-   Commented code blocks

**Recommendations:**

-   Remove console.log
-   Add null checks
-   Extract to separate JS file
-   Remove commented code

### 4. ProjectControllerOld.text

**Issues:**

-   Entire file appears to be old/unused code

**Recommendations:**

-   Delete if not needed
-   Move to archive if reference is needed

### 5. Routes (web.php)

**Issues:**

-   Very long file (442 lines)
-   Some commented routes
-   Complex route grouping

**Recommendations:**

-   Split routes into multiple files
-   Remove commented routes
-   Use route model binding where applicable
-   Organize routes by feature

---

## Recommendations

### Immediate Actions (High Priority)

1. **Fix Validation Syntax Error**

    - Fix double pipe in `GeneralInfoController.php` line 30

2. **Remove Sensitive Data from Logs**

    - Replace all `$request->all()` in logs with selective field logging
    - Implement log sanitization

3. **Add Null Checks in JavaScript**

    - Add defensive checks for all DOM element access
    - Prevent null reference errors

4. **Remove Console.log Statements**
    - Remove all console.log from production code

### Short-term Actions (Medium Priority)

1. **Refactor Large Controllers**

    - Break down `ProjectController` into smaller controllers
    - Extract business logic to services

2. **Standardize Validation**

    - Create FormRequest classes for major operations
    - Move validation logic out of controllers

3. **Improve Error Handling**

    - Create custom exception classes
    - Standardize error responses
    - Improve error messages

4. **Remove Commented Code**

    - Delete all commented code blocks
    - Use Git for code history

5. **Create Constants/Enums**
    - Replace magic strings with constants
    - Create enums for project types and statuses

### Long-term Actions (Low Priority)

1. **Implement Service Layer**

    - Create service classes for business logic
    - Use dependency injection

2. **Optimize Database Queries**

    - Review and fix N+1 problems
    - Add proper indexes
    - Optimize query performance

3. **Improve Code Organization**

    - Split routes into multiple files
    - Organize JavaScript into modules
    - Improve folder structure

4. **Add Type Hints**

    - Add return type hints
    - Add parameter type hints
    - Use strict types

5. **Implement Testing**
    - Add unit tests for services
    - Add feature tests for controllers
    - Add integration tests

---

## Code Quality Metrics

### Current State

-   **Total Controllers:** 94+
-   **Largest Controller:** 1841 lines (ProjectController)
-   **Average Controller Size:** ~200-300 lines
-   **Commented Code Blocks:** Multiple large blocks
-   **Validation Issues:** 1 syntax error, multiple inconsistencies
-   **Security Issues:** Multiple sensitive data logging instances

### Target State

-   **Controller Size:** < 300 lines per controller
-   **Service Classes:** Business logic extracted
-   **Validation:** All using FormRequest classes
-   **Error Handling:** Standardized and consistent
-   **Code Coverage:** > 80% test coverage

---

## Conclusion

This codebase review identified **multiple areas for improvement** across security, code quality, validation, and architecture. While the application appears functional, addressing these issues will:

1. **Improve Security:** Prevent data exposure and vulnerabilities
2. **Enhance Maintainability:** Make code easier to understand and modify
3. **Reduce Bugs:** Catch issues early through better validation and error handling
4. **Improve Performance:** Optimize queries and reduce N+1 problems
5. **Enable Scalability:** Better architecture supports future growth

**Priority should be given to:**

1. Security issues (sensitive data logging)
2. Validation syntax error
3. JavaScript null reference issues
4. Large controller refactoring

---

## Appendix: File-by-File Issue Summary

### Controllers with Issues

| File                             | Issues                                        | Priority |
| -------------------------------- | --------------------------------------------- | -------- |
| `ProjectController.php`          | Large file, commented code, sensitive logging | HIGH     |
| `GeneralInfoController.php`      | Validation syntax error, sensitive logging    | HIGH     |
| `ProvincialController.php`       | Sensitive logging, direct request usage       | MEDIUM   |
| `CoordinatorController.php`      | Sensitive logging, direct request usage       | MEDIUM   |
| `LogicalFrameworkController.php` | Sensitive logging                             | MEDIUM   |
| `IAHDocumentsController.php`     | Sensitive logging                             | MEDIUM   |
| `ReportController.php`           | Sensitive logging                             | MEDIUM   |

### Views with Issues

| File                | Issues                                   | Priority |
| ------------------- | ---------------------------------------- | -------- |
| `scripts.blade.php` | Console.log, null checks, commented code | MEDIUM   |

### Routes with Issues

| File      | Issues                      | Priority |
| --------- | --------------------------- | -------- |
| `web.php` | Long file, commented routes | LOW      |

---

---

## User Access & Permission Issues

### Critical Issues Preventing Project Submission/Editing

#### 1. Applicants Cannot Submit Projects

**Severity:** HIGH  
**Location:** `app/Http/Controllers/Projects/ProjectController.php` (Line 1812)  
**Location:** `resources/views/projects/partials/actions.blade.php` (Line 14)

**Issue:** The `submitToProvincial` method only allows 'executor' role, but applicants should also be able to submit projects. The route allows both roles, but the controller and view restrict it to executors only.

**Current Code:**

```php
// ProjectController.php line 1812
if($user->role !== 'executor' || !in_array($project->status, ['draft','reverted_by_provincial'])) {
    abort(403, 'Unauthorized action.');
}
```

```blade
{{-- actions.blade.php line 14 --}}
@if($userRole === 'executor')
    @if($status === 'draft' || $status === 'reverted_by_provincial')
        <!-- Submit button only shown for executors -->
    @endif
@endif
```

**Problem:**

-   Routes allow both `executor` and `applicant` roles (line 249 in web.php)
-   Controller method only checks for `executor`
-   View only shows submit button for `executor`
-   Applicants cannot submit their projects

**Fix:**

```php
// ProjectController.php - submitToProvincial method
public function submitToProvincial($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Allow both executor and applicant roles
    if(!in_array($user->role, ['executor', 'applicant']) || !in_array($project->status, ['draft','reverted_by_provincial'])) {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'submitted_to_provincial';
    $project->save();

    return redirect()->back()->with('success', 'Project submitted to Provincial successfully.');
}
```

```blade
{{-- actions.blade.php --}}
@if(in_array($userRole, ['executor', 'applicant']))
    @if($status === 'draft' || $status === 'reverted_by_provincial')
        <!-- Submit button shown for both executors and applicants -->
        <form action="{{ route('projects.submitToProvincial', $project->project_id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Submit to Provincial</button>
        </form>
    @endif
@endif
```

#### 2. Missing Status Check in Edit Method

**Severity:** HIGH  
**Location:** `app/Http/Controllers/Projects/ProjectController.php` (Line 1160-1200)

**Issue:** The `edit` method doesn't check project status before allowing edits. Users can edit projects that are already submitted, forwarded, or approved, which should not be allowed.

**Current Code:**

```php
// No status check in edit method
public function edit($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Only checks applicant role, no status check
    if ($user->role === 'applicant') {
        if ($project->user_id !== $user->id) {
            return redirect()->route('projects.show', $project_id)
                ->with('error', 'You can only edit projects you created...');
        }
    }
    // Missing: Status check to prevent editing submitted/approved projects
}
```

**Problem:**

-   Users can edit projects with status 'submitted_to_provincial', 'forwarded_to_coordinator', or 'approved_by_coordinator'
-   This can cause data inconsistency
-   Projects should only be editable when status is 'draft' or 'reverted*by*\*'

**Fix:**

```php
public function edit($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Check if project can be edited based on status
    $editableStatuses = ['draft', 'reverted_by_provincial', 'reverted_by_coordinator'];
    if (!in_array($project->status, $editableStatuses)) {
        return redirect()->route('projects.show', $project_id)
            ->with('error', 'This project cannot be edited in its current status (' . $project->status . ').');
    }

    // Check role-based access
    if ($user->role === 'applicant') {
        if ($project->user_id !== $user->id) {
            return redirect()->route('projects.show', $project_id)
                ->with('error', 'You can only edit projects you created. You have view-only access to projects where you are the in-charge.');
        }
    }

    // Check if user owns the project or is in-charge
    if ($user->role === 'executor' || $user->role === 'applicant') {
        if ($project->user_id !== $user->id && $project->in_charge !== $user->id) {
            abort(403, 'You do not have permission to edit this project.');
        }
    }

    // Continue with edit logic...
}
```

#### 3. Missing Status Check in Update Method

**Severity:** HIGH  
**Location:** `app/Http/Controllers/Projects/ProjectController.php` (Line 1422-1455)

**Issue:** The `update` method also lacks status validation, allowing updates to projects that shouldn't be editable.

**Fix:**

```php
public function update(Request $request, $project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Check if project can be updated based on status
    $editableStatuses = ['draft', 'reverted_by_provincial', 'reverted_by_coordinator'];
    if (!in_array($project->status, $editableStatuses)) {
        DB::rollBack();
        return redirect()->route('projects.show', $project_id)
            ->with('error', 'This project cannot be updated in its current status (' . $project->status . ').');
    }

    // Existing applicant check...
    if ($user->role === 'applicant') {
        if ($project->user_id !== $user->id) {
            DB::rollBack();
            return redirect()->route('projects.show', $project_id)
                ->with('error', 'You can only edit projects you created...');
        }
    }

    // Continue with update logic...
}
```

#### 4. Inconsistent Edit Permission Logic

**Severity:** MEDIUM  
**Location:** `resources/views/projects/Oldprojects/show.blade.php` (Line 161-168)

**Issue:** The view has different logic than the controller for determining if a user can edit.

**Current View Logic:**

```php
if ($user->role === 'executor') {
    $canEdit = ($project->status == 'draft' || $project->status == 'reverted_by_provincial' || $project->status == 'reverted_by_coordinator');
} elseif ($user->role === 'applicant') {
    $canEdit = ($project->user_id === $user->id && ($project->status == 'draft' || $project->status == 'reverted_by_provincial' || $project->status == 'reverted_by_coordinator'));
}
```

**Problem:**

-   View checks status, but controller doesn't
-   View logic for executor doesn't check ownership
-   Inconsistent between view and controller

**Recommendation:**

-   Create a helper method or policy to centralize edit permission logic
-   Use the same logic in both view and controller

#### 5. Executors Can Edit Projects They Don't Own

**Severity:** MEDIUM  
**Location:** `app/Http/Controllers/Projects/ProjectController.php` (Line 1160)

**Issue:** The `edit` method doesn't verify that executors own the project or are the in-charge before allowing edits.

**Current Code:**

```php
// Only checks applicant role, no check for executor ownership
if ($user->role === 'applicant') {
    // Check ownership
}
// Missing: Check if executor owns project or is in-charge
```

**Fix:**

```php
// Check ownership for executors and applicants
if (in_array($user->role, ['executor', 'applicant'])) {
    if ($user->role === 'applicant') {
        // Applicants can only edit projects they created
        if ($project->user_id !== $user->id) {
            return redirect()->route('projects.show', $project_id)
                ->with('error', 'You can only edit projects you created...');
        }
    } else {
        // Executors can edit projects they own or are in-charge of
        if ($project->user_id !== $user->id && $project->in_charge !== $user->id) {
            abort(403, 'You do not have permission to edit this project.');
        }
    }
}
```

#### 6. Missing Submit Button When Coordinator Reverts Project

**Severity:** HIGH  
**Location:** `resources/views/projects/partials/actions.blade.php` (Line 15)  
**Location:** `app/Http/Controllers/Projects/ProjectController.php` (Line 1812)

**Issue:** When a coordinator reverts a project, the status becomes `reverted_by_coordinator`, but the "Submit to Provincial" button and submission logic only allow `draft` or `reverted_by_provincial` statuses. This prevents executors from submitting projects that were reverted by the coordinator.

**Project Status Flow:**

```
Executor creates project
    ↓
Status: 'draft'
    ↓
Executor submits → Status: 'submitted_to_provincial'
    ↓
Provincial forwards → Status: 'forwarded_to_coordinator'
    ↓
    ├─→ Coordinator approves → Status: 'approved_by_coordinator' ✓
    │
    └─→ Coordinator reverts → Status: 'reverted_by_coordinator' ⚠️ ISSUE HERE
            ↓
        Executor should be able to resubmit, but:
        - Submit button doesn't show (only checks for 'draft' or 'reverted_by_provincial')
        - Controller rejects submission (only allows 'draft' or 'reverted_by_provincial')
        - Project gets stuck in 'reverted_by_coordinator' status
```

**Expected Flow After Coordinator Revert:**

```
Coordinator reverts → Status: 'reverted_by_coordinator'
    ↓
Executor makes corrections
    ↓
Executor should see "Submit to Provincial" button
    ↓
Executor submits → Status: 'submitted_to_provincial'
    ↓
Flow continues normally...
```

**Current Broken Flow:**

```
Coordinator reverts → Status: 'reverted_by_coordinator'
    ↓
Executor makes corrections
    ↓
❌ "Submit to Provincial" button is MISSING
    ↓
❌ Project stuck - cannot proceed
```

**Current Code:**

```blade
{{-- actions.blade.php line 15 --}}
@if($userRole === 'executor')
    @if($status === 'draft' || $status === 'reverted_by_provincial')
        <!-- Submit button only shows for draft or reverted_by_provincial -->
        <form action="{{ route('projects.submitToProvincial', $project->project_id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Submit to Provincial</button>
        </form>
    @endif
@endif
```

```php
// ProjectController.php line 1812
public function submitToProvincial($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Only allows draft or reverted_by_provincial, missing reverted_by_coordinator!
    if($user->role !== 'executor' || !in_array($project->status, ['draft','reverted_by_provincial'])) {
        abort(403, 'Unauthorized action.');
    }
    // ...
}
```

**Problem:**

-   When coordinator reverts, status becomes `reverted_by_coordinator`
-   View only shows submit button for `draft` or `reverted_by_provincial`
-   Controller only allows submission for `draft` or `reverted_by_provincial`
-   Executors cannot submit projects reverted by coordinator
-   Projects get stuck in `reverted_by_coordinator` status

**Fix:**

```blade
{{-- actions.blade.php --}}
@if(in_array($userRole, ['executor', 'applicant']))
    @if(in_array($status, ['draft', 'reverted_by_provincial', 'reverted_by_coordinator']))
        <!-- Submit button shows for draft, reverted_by_provincial, AND reverted_by_coordinator -->
        <form action="{{ route('projects.submitToProvincial', $project->project_id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Submit to Provincial</button>
        </form>
    @endif
@endif
```

```php
// ProjectController.php - submitToProvincial method
public function submitToProvincial($project_id)
{
    $project = Project::where('project_id', $project_id)->firstOrFail();
    $user = Auth::user();

    // Allow both executor and applicant roles
    // Allow draft, reverted_by_provincial, AND reverted_by_coordinator statuses
    if(!in_array($user->role, ['executor', 'applicant']) ||
       !in_array($project->status, ['draft', 'reverted_by_provincial', 'reverted_by_coordinator'])) {
        abort(403, 'Unauthorized action.');
    }

    $project->status = 'submitted_to_provincial';
    $project->save();

    return redirect()->back()->with('success', 'Project submitted to Provincial successfully.');
}
```

**Additional Note:** The coordinator's revert action sets status to `reverted_by_coordinator` (line 688 in CoordinatorController.php), which is correct. The issue is that the executor's submit functionality doesn't recognize this status as a valid state for resubmission.

### Summary of Access Control Issues

| Issue                                          | Severity | Location                  | Impact                                                            |
| ---------------------------------------------- | -------- | ------------------------- | ----------------------------------------------------------------- |
| Applicants cannot submit                       | HIGH     | Controller + View         | Applicants blocked from submitting                                |
| No status check in edit                        | HIGH     | ProjectController::edit   | Can edit submitted/approved projects                              |
| No status check in update                      | HIGH     | ProjectController::update | Can update submitted/approved projects                            |
| Missing submit button after coordinator revert | HIGH     | Controller + View         | Projects stuck when coordinator reverts, executor cannot resubmit |
| Inconsistent edit logic                        | MEDIUM   | View vs Controller        | Confusing behavior                                                |
| Missing ownership check                        | MEDIUM   | ProjectController::edit   | Executors can edit any project                                    |

### Recommended Fixes Priority

1. **IMMEDIATE:** Fix missing submit button for `reverted_by_coordinator` status (CRITICAL - blocks workflow)
2. **IMMEDIATE:** Fix `submitToProvincial` to allow applicants
3. **IMMEDIATE:** Add status checks to `edit` and `update` methods
4. **HIGH:** Add ownership verification for executors
5. **MEDIUM:** Create centralized permission checking method
6. **MEDIUM:** Align view and controller permission logic

### Suggested Permission Helper Method

```php
// Add to ProjectController or create a ProjectPolicy
protected function canEditProject($project, $user)
{
    // Check status
    $editableStatuses = ['draft', 'reverted_by_provincial', 'reverted_by_coordinator'];
    if (!in_array($project->status, $editableStatuses)) {
        return [
            'allowed' => false,
            'reason' => 'Project cannot be edited in status: ' . $project->status
        ];
    }

    // Check role and ownership
    if ($user->role === 'applicant') {
        if ($project->user_id !== $user->id) {
            return [
                'allowed' => false,
                'reason' => 'You can only edit projects you created'
            ];
        }
    } elseif (in_array($user->role, ['executor', 'applicant'])) {
        if ($project->user_id !== $user->id && $project->in_charge !== $user->id) {
            return [
                'allowed' => false,
                'reason' => 'You do not have permission to edit this project'
            ];
        }
    } else {
        return [
            'allowed' => false,
            'reason' => 'Your role does not allow editing projects'
        ];
    }

    return ['allowed' => true];
}

protected function canSubmitProject($project, $user)
{
    if (!in_array($user->role, ['executor', 'applicant'])) {
        return [
            'allowed' => false,
            'reason' => 'Only executors and applicants can submit projects'
        ];
    }

    if (!in_array($project->status, ['draft', 'reverted_by_provincial', 'reverted_by_coordinator'])) {
        return [
            'allowed' => false,
            'reason' => 'Project can only be submitted when status is draft, reverted_by_provincial, or reverted_by_coordinator'
        ];
    }

    // Check ownership
    if ($project->user_id !== $user->id && $project->in_charge !== $user->id) {
        return [
            'allowed' => false,
            'reason' => 'You can only submit projects you own or are in-charge of'
        ];
    }

    return ['allowed' => true];
}
```

**End of Report**
