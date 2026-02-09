# Production Log Review – 3031

## Overview

- **Environment:** Production (`salprojects.org/v1`)
- **Log source:** `storage/logs/laravel3031prod.log`
- **Date range:** January 29–31, 2026
- **Total ERROR entries:** 59 unique error lines
- **Purpose:** Identify systemic and code-level issues observed in production

---

## Issue Summary Table

| # | Category | Error Type | Severity | Affected Area | Frequency |
|---|----------|------------|----------|---------------|-----------|
| 1 | Database Constraint | NOT NULL violation (IIES Expenses) | High | IIES project type | 10 |
| 2 | Database Constraint | Numeric overflow (Budget columns) | High | Development Projects budget | 6 |
| 3 | File Upload Handling | `getClientOriginalExtension()` on array | High | IES Attachments | 7 |
| 4 | Missing File | routes/api.php not found | Critical | Deployment | 19 |
| 5 | Authorization | Permission denied on project view | Medium | ProjectController@show | 9 |
| 6 | Missing Record | findOrFail on CCI Statistics | Medium | CCI project type | 3 |
| 7 | Undefined Variable | $IGEbudget in Blade view | Medium | PDF Export (IGE) | 2 |
| 8 | Invalid Data Type | Incorrect integer value (CCI Statistics) | Medium | CCI project type | 1 |
| 9 | Undefined Array Key | Missing "activity" key | Medium | Logical Framework | 1 |
| 10 | Database Constraint | IIES Financial Support NOT NULL | Medium | IIES project type | 1 |

---

## Detailed Findings

---

### Issue 1: IIES Expenses – NOT NULL Constraint Violations

- **Error message:**
  ```
  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'iies_total_expenses' cannot be null
  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'iies_support_other_sources' cannot be null
  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'iies_expected_scholarship_govt' cannot be null
  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'iies_beneficiary_contribution' cannot be null
  ```

- **Frequency:** 10 occurrences across projects IIES-0029, IIES-0030, IIES-0031

- **Files involved:**
  - `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` (lines 55-61)
  - `database/migrations/2025_01_31_113236_create_project_i_i_e_s_expenses_table.php`
  - Frontend: IIES expense form Blade views

- **Root cause:**
  The controller uses `$request->all()` and attempts to apply defaults:
  ```php
  $projectExpenses->iies_total_expenses = $validated['iies_total_expenses'] ?? 0;
  ```
  However, the actual insert shows `?` placeholders (null values) reaching the database. The migration defines columns with `->default(0)` but MySQL strict mode still enforces NOT NULL when explicitly passing NULL.

  **Architectural issue:** The null-coalescing operator `?? 0` does not work when the key exists but has a null value. The form may be submitting empty strings or the key with null value rather than omitting the key entirely.

- **Why this surfaced in production:**
  Users are submitting forms with empty or unfilled expense fields. Frontend validation is insufficient or bypassed.

- **Risk if left unresolved:**
  Users cannot save IIES expense data when certain fields are empty, leading to data loss and broken workflows.

---

### Issue 2: Budget Numeric Overflow – Out of Range Values

- **Error message:**
  ```
  SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'rate_duration' at row 1
  SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'this_phase' at row 1
  ```

- **Frequency:** 6 occurrences, all for project DP-0033

- **Files involved:**
  - `app/Http/Controllers/Projects/BudgetController.php` (lines 66-76, 133-143)
  - `app/Http/Controllers/Projects/ProjectController.php` (update method)
  - `database/migrations/2024_07_20_085654_create_project_budgets_table.php`

- **Root cause:**
  The migration defines budget columns as:
  ```php
  $table->decimal('rate_duration', 10, 2)->nullable();
  $table->decimal('this_phase', 10, 2)->nullable();
  ```
  Maximum value for `DECIMAL(10,2)` is 99,999,999.99.

  The log shows values like:
  - `rate_duration`: 121,000,112,100.00
  - `this_phase`: 63,504,000,000.00 and 304,920,282,492,000,000.00

  These values are astronomically larger than the column can hold.

  **Architectural issue:** The budget form likely has a calculation bug where values are being multiplied incorrectly (possibly concatenated strings or JavaScript arithmetic errors). The backend validation only checks `min:0` but has no `max` constraint.

- **Why this surfaced in production:**
  A user entered unusual data in the budget form for DP-0033, and the frontend calculation logic produced impossibly large numbers.

- **Risk if left unresolved:**
  Budget data corruption; users cannot save budget entries with certain input combinations.

---

### Issue 3: IES Attachments – File Upload Type Mismatch

- **Error message:**
  ```
  Call to a member function getClientOriginalExtension() on array
  ```

- **Frequency:** 7 occurrences, all from user ID 38

- **Files involved:**
  - `app/Models/OldProjects/IES/ProjectIESAttachments.php` (line 184)
  - `resources/views/projects/partials/Edit/IES/attachments.blade.php`

- **Root cause:**
  The Blade view uses array notation for file inputs:
  ```blade
  <input type="file" name="{{ $field }}[]" ...>
  ```
  This creates an array of uploaded files. However, the model's `handleAttachments` method expects a single file object:
  ```php
  $file = $request->file($field);
  // ...
  $extension = strtolower($file->getClientOriginalExtension()); // FAILS: $file is an array
  ```

  **Architectural issue:** Frontend-backend contract mismatch. The view was modified to support multiple files but the backend handler was never updated to iterate over the array.

- **Why this surfaced in production:**
  Users attempting to upload attachments in IES project type trigger this error every time.

- **Risk if left unresolved:**
  Complete failure of IES attachment functionality; users cannot upload required documents.

---

### Issue 4: Missing routes/api.php File

- **Error message:**
  ```
  require(/home/u160871038/domains/salprojects.org/public_html/v1/routes/api.php): Failed to open stream: No such file or directory
  ```

- **Frequency:** 19 occurrences in rapid succession (within 2-minute windows)

- **Files involved:**
  - `routes/api.php` (exists in codebase, missing on production server)
  - `vendor/laravel/framework/src/Illuminate/Routing/RouteFileRegistrar.php`

- **Root cause:**
  The `routes/api.php` file exists in the local codebase but was missing from the production server during the logged time periods. This is a deployment issue.

  **Deployment issue:** Either:
  1. The file was not uploaded during deployment
  2. The file was deleted by a deployment script
  3. A partial deployment occurred

- **Why this surfaced in production:**
  The errors occurred in two distinct bursts:
  - Jan 30, 17:28-17:29 (2 errors)
  - Jan 31, 12:03-12:05 (17 errors)
  
  This suggests deployment activities were occurring during these windows.

- **Risk if left unresolved:**
  Complete application failure; no routes are loaded and all HTTP requests return 500 errors.

---

### Issue 5: Authorization – Permission Denied on Project View

- **Error message:**
  ```
  You do not have permission to view this project.
  ```

- **Frequency:** 9 occurrences

- **Files involved:**
  - `app/Http/Controllers/Projects/ProjectController.php` (lines 810-817)
  - `app/Helpers/ProjectPermissionHelper.php`

- **Root cause:**
  The permission check in `ProjectController@show` correctly denies access based on user role and project status. The logged warnings show:
  ```php
  if (!$hasAccess) {
      Log::warning('ProjectController@show - Access denied', [...]);
      abort(403, 'You do not have permission to view this project.');
  }
  ```

  **Classification:** These are correctly logged security events, not bugs. However, logging them at ERROR level is misleading.

- **Why this surfaced in production:**
  Users attempted to access projects they don't have permission to view (possibly via direct URL, bookmarks, or shared links).

- **Risk if left unresolved:**
  No functional risk. However, the error-level logging creates noise in production logs.

  **Logging level misuse:** This should be logged at WARNING or INFO level, not ERROR.

---

### Issue 6: CCI Statistics – Missing Record (findOrFail)

- **Error message:**
  ```
  No query results for model [App\Models\OldProjects\CCI\ProjectCCIStatistics].
  ```

- **Frequency:** 3 occurrences

- **Files involved:**
  - `app/Http/Controllers/Projects/CCI/StatisticsController.php` (line 81)

- **Root cause:**
  The `edit` method uses `firstOrFail()`:
  ```php
  public function edit($projectId)
  {
      try {
          $statistics = ProjectCCIStatistics::where('project_id', $projectId)->firstOrFail();
          return $statistics;
      } catch (\Exception $e) {
          Log::error('Error editing CCI Statistics', ['error' => $e->getMessage()]);
          return null;
      }
  }
  ```

  When a CCI project has no statistics record yet (first-time edit), `firstOrFail()` throws an exception.

  **Architectural issue:** Inconsistent approach. The `show` method correctly handles missing records:
  ```php
  $statistics = ProjectCCIStatistics::where('project_id', $projectId)->first();
  if (!$statistics) {
      Log::warning('No Statistics data found', ...);
  }
  ```
  But the `edit` method uses `firstOrFail()` instead.

- **Why this surfaced in production:**
  Users navigating to edit CCI statistics for projects that haven't had statistics entered yet.

- **Risk if left unresolved:**
  Users cannot access the edit form for CCI statistics on projects without existing data.

---

### Issue 7: Undefined Variable – $IGEbudget in PDF Export

- **Error message:**
  ```
  Undefined variable $IGEbudget (View: resources/views/projects/partials/Show/IGE/budget.blade.php)
  ```

- **Frequency:** 2 occurrences (for project IOGEP-0007)

- **Files involved:**
  - `app/Http/Controllers/Projects/ExportController.php` (line 478)
  - `resources/views/projects/Oldprojects/pdf.blade.php` (line 667)
  - `resources/views/projects/partials/Show/IGE/budget.blade.php` (line 7)

- **Root cause:**
  The Blade view expects `$IGEbudget` variable:
  ```blade
  @if($IGEbudget && $IGEbudget->isNotEmpty())
  ```

  The `show.blade.php` correctly passes this:
  ```blade
  @include('projects.partials.Show.IGE.budget', ['IGEbudget' => $budget ?? collect()])
  ```

  But `pdf.blade.php` does NOT pass the variable:
  ```blade
  @include('projects.partials.Show.IGE.budget')
  ```

  The `loadAllProjectData` method in ExportController loads `$data['budget']` but the view expects `$IGEbudget`.

  **Architectural issue:** Variable naming inconsistency between controllers and views. The PDF export path was added later and didn't follow the same data-passing convention.

- **Why this surfaced in production:**
  Users attempting to download PDF for "Institutional Ongoing Group Educational proposal" projects.

- **Risk if left unresolved:**
  PDF export completely broken for all IGE project types.

---

### Issue 8: CCI Statistics – Invalid Integer Value

- **Error message:**
  ```
  SQLSTATE[22007]: Invalid datetime format: 1366 Incorrect integer value: '-' for column `shifted_children_current_year`
  ```

- **Frequency:** 1 occurrence (CCI-0002)

- **Files involved:**
  - `app/Http/Controllers/Projects/CCI/StatisticsController.php`
  - CCI statistics form Blade views

- **Root cause:**
  The SQL insert shows a literal dash character `-` being inserted into an integer column:
  ```sql
  VALUES (CCI-0002, 187, 22, 153, 22, 2, -, 20, 8, ?, ?, 12, 12, ...)
  ```

  The controller uses null-coalescing:
  ```php
  $statistics->shifted_children_current_year = $validated['shifted_children_current_year'] ?? null;
  ```

  But the form is sending a literal `-` character (possibly from a placeholder or intentional user input meaning "none").

  **Frontend validation gap:** The form allows non-numeric characters to be submitted for integer fields.

- **Why this surfaced in production:**
  A user entered a dash `-` instead of a number (or left a field in an invalid state).

- **Risk if left unresolved:**
  Data entry failures for CCI statistics when users enter non-numeric values.

---

### Issue 9: Undefined Array Key – "activity" in Logical Framework

- **Error message:**
  ```
  Undefined array key "activity"
  ```

- **Frequency:** 1 occurrence (CIC-0002)

- **Files involved:**
  - `app/Http/Controllers/Projects/LogicalFrameworkController.php` (lines 241-247)
  - `app/Http/Controllers/Projects/ProjectController.php` (update method)

- **Root cause:**
  The code accesses array keys without checking their existence:
  ```php
  foreach ($objectiveData['activities'] ?? [] as $activityData) {
      $activity = new ProjectActivity([
          'activity' => $activityData['activity'],  // FAILS if 'activity' key missing
          'verification' => $activityData['verification'],
      ]);
  }
  ```

  The outer loop uses null-coalescing for the `activities` array, but the inner access to `$activityData['activity']` does not.

  **Defensive coding gap:** Inconsistent null-safety. Some array accesses are guarded, others are not.

- **Why this surfaced in production:**
  A form submission included an activities array with malformed or empty activity entries.

- **Risk if left unresolved:**
  Project update failures when activities data is incomplete.

---

### Issue 10: IIES Financial Support – NOT NULL Constraint

- **Error message:**
  ```
  SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'govt_eligible_scholarship' cannot be null
  ```

- **Frequency:** 1 occurrence (IIES-0030)

- **Files involved:**
  - `app/Http/Controllers/Projects/IIES/FinancialSupportController.php`
  - `database/migrations/2024_10_24_165620_create_project_i_i_e_s_scope_financial_supports_table.php`

- **Root cause:**
  The migration defines:
  ```php
  $table->boolean('govt_eligible_scholarship')->default(false);
  ```

  The column has a default, but when MySQL receives an explicit NULL insert, the default is not applied. The controller uses:
  ```php
  'govt_eligible_scholarship' => (int) ($validated['govt_eligible_scholarship'] ?? 0),
  ```

  This should work correctly. The error suggests the code path at the time was using an older version without this fix.

  **Note:** The current codebase shows a fix has been applied (`Phase 1: server-side defaults for NOT NULL columns` comment), but the production deployment may have been running old code.

- **Why this surfaced in production:**
  Deployment lag or the fix was applied after this error occurred.

- **Risk if left unresolved:**
  If running old code, same issue as Issue 1.

---

## Cross-Cutting Patterns Observed

### 1. Null-Coalescing Operator Misuse
Multiple controllers use `$validated['field'] ?? 0` assuming the key might not exist. However, when the key exists with a null or empty string value, this pattern fails. The pattern should be:
```php
$value = isset($validated['field']) && $validated['field'] !== '' ? $validated['field'] : 0;
```

### 2. Frontend-Backend Contract Violations
- IES attachments: View uses array notation (`name="field[]"`) but backend expects single file
- CCI statistics: Form allows non-numeric characters but database expects integers
- Budget form: Calculation produces astronomically large values that exceed column limits

### 3. Inconsistent Error Handling Approaches
Some controllers use:
- `firstOrFail()` (throws exception on missing)
- `first()` with null check (graceful handling)
- `find()` with conditional (graceful handling)

No consistent pattern exists across similar use cases.

### 4. Logging Level Misclassification
Authorization failures are logged at ERROR level when they should be WARNING or INFO. This creates noise when diagnosing actual errors.

### 5. View-Controller Data Contract Gaps
The `pdf.blade.php` includes partials without passing required variables that `show.blade.php` passes. This indicates the PDF export feature was added without full regression testing.

### 6. Deployment Hygiene Issues
The `routes/api.php` missing file error occurred in distinct burst patterns, indicating deployment activities that temporarily broke the application.

---

## What This Reveals About the Current Architecture

### Assumptions Being Made

1. **Form data is always complete and valid** – Controllers assume all expected fields exist and contain valid values. No defensive parsing of incoming data.

2. **Database defaults will handle missing values** – Developers expect `->default(0)` to apply when null is passed, but MySQL strict mode rejects explicit nulls.

3. **Frontend validation is the first line of defense** – Backend validation is minimal, relying on frontend JavaScript to prevent bad data. This fails when JS is disabled or forms are submitted programmatically.

4. **All includes receive the same context** – Views assume parent views pass required variables, but different entry points (show vs pdf export) may not maintain this contract.

### Where Responsibilities Are Leaking

1. **Validation logic is split across layers** – Some validation in Form Requests, some in controllers, some in Blade JS, some in database constraints. No single source of truth.

2. **Controller methods are doing too much** – Controllers handle data transformation, validation, business logic, and error handling. Services exist but are underutilized.

3. **Model boot methods generate IDs** – Auto-ID generation happens in model boot hooks, which can fail silently if not coordinated with the controller transaction.

### Where Defensive Coding Is Missing

1. **Array access without key checks** – `$data['key']` instead of `$data['key'] ?? null` or `isset($data['key'])`

2. **Type coercion before database insert** – Strings and nulls reaching integer/decimal columns without explicit casting

3. **File upload type validation** – No check for whether `$request->file()` returns a single file or array

4. **Numeric bounds checking** – Budget calculations can exceed column limits without frontend or backend validation

5. **Blade variable existence** – Partials don't use `@isset` or `??` defaults for critical variables

---

## Summary

This log reveals a production system with:
- **59 logged errors** across a 3-day period
- **10 distinct issue categories** ranging from deployment issues to code defects
- **Multiple project types affected**: IIES, IES, CCI, IGE, Development Projects
- **Consistent patterns** of insufficient input validation and frontend-backend contract violations

The codebase shows evidence of iterative development where new features (PDF export, multiple file upload) were added without fully testing against all data paths. The errors are concentrated in specialized project types (IIES, CCI, IGE) that may have lower test coverage than the core Development Projects path.

---

## DO NOT Actions (Per Audit Scope)

- No fixes implemented
- No code changes made
- No refactoring performed
- No validation added
- No solutions proposed

This document serves solely as a factual audit record for planning remediation phases.

---

*Document generated: January 31, 2026*
*Reviewed by: Production Post-Mortem Audit*
