# Frontend–Backend Contract Audit

## Purpose

This audit identifies systemic contract violations between frontend inputs and backend expectations across the Laravel application. Production failures revealed that implicit assumptions about data flow, structure, and validation exist at multiple layers, leading to:

- **Database constraint violations** (NOT NULL, type mismatches, numeric overflow)
- **Type coercion failures** (arrays vs scalars, strings vs integers)
- **Missing data assumptions** (`firstOrFail()` on non-existent records)
- **View-controller data mismatches** (undefined variables in alternate render paths)

Contract enforcement is required because:
1. **Frontend validation is not backend validation** – JS can be bypassed or disabled
2. **Multiple entry points exist** – Standard forms, PDF exports, API calls each have different data paths
3. **Database constraints are the last line of defense** – When they fail, the entire transaction fails
4. **Type coercion is implicit** – PHP's loose typing masks mismatches until database insertion

---

## Model-by-Model Contract Review

### Model: ProjectIIESExpenses
**Table:** `project_IIES_expenses`

**Database Schema:**
```php
$table->decimal('iies_total_expenses', 10, 2)->default(0);
$table->decimal('iies_expected_scholarship_govt', 10, 2)->default(0);
$table->decimal('iies_support_other_sources', 10, 2)->default(0);
$table->decimal('iies_beneficiary_contribution', 10, 2)->default(0);
$table->decimal('iies_balance_requested', 10, 2)->default(0);
```

**Model Fillable:**
```php
'iies_total_expenses', 'iies_expected_scholarship_govt', 
'iies_support_other_sources', 'iies_beneficiary_contribution', 
'iies_balance_requested'
```

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `<input type="number" name="iies_total_expenses">` (can be empty string) | `$validated['iies_total_expenses'] ?? 0` | ❌ **VIOLATED** |
| Create | `<input type="number" name="iies_support_other_sources">` (can be empty) | Direct assignment with `?? 0` | ❌ **VIOLATED** |
| Create | `<input type="number" name="iies_expected_scholarship_govt">` (can be empty) | Direct assignment with `?? 0` | ❌ **VIOLATED** |
| Update | Same as Create | Reuses `store()` method | ❌ **VIOLATED** |
| Read | N/A | Returns model or empty instance | ✅ Valid |

#### Identified Contract Violations

**Violation 1: Empty String vs NULL vs Zero**
- **Operation:** Create/Update
- **Fields involved:** All decimal fields (`iies_total_expenses`, `iies_support_other_sources`, etc.)
- **Frontend behavior:** 
  - HTML5 number inputs submit empty string `""` when left blank
  - JavaScript `parseFloat("")` returns `NaN`, which converts to empty string in form submission
  - Readonly calculated fields may submit empty string
- **Backend assumption:**
  ```php
  $projectExpenses->iies_total_expenses = $validated['iies_total_expenses'] ?? 0;
  ```
  Assumes `??` will catch missing values, but doesn't catch `""` (empty string) which exists as a key
- **Database constraint impact:**
  - MySQL strict mode rejects `INSERT ... VALUES ('')` for decimal columns
  - Migration defines `->default(0)` but explicit NULL/empty string bypasses default
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Production evidence:** 10 failures across IIES-0029, IIES-0030, IIES-0031

**Violation 2: Array-based Expense Details Without Validation**
- **Operation:** Create
- **Fields involved:** `iies_particulars[]`, `iies_amounts[]`
- **Frontend behavior:**
  - Dynamic rows allow empty particular with empty amount
  - Both arrays can be submitted with mismatched lengths
- **Backend assumption:**
  ```php
  foreach ($particulars as $index => $particular) {
      if (!empty($particular) && !empty($amounts[$index] ?? null)) {
          // create detail
      }
  }
  ```
  Assumes arrays are parallel and checks both, BUT silently skips invalid rows
- **Database constraint impact:** None directly, but data loss occurs silently
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

**Violation 3: Calculated Readonly Fields**
- **Operation:** Create
- **Fields involved:** `iies_total_expenses`, `iies_balance_requested`
- **Frontend behavior:**
  - Calculated via JavaScript `calculateTotalExpenses()`
  - Fields are `readonly` but still submitted
  - If JS fails, empty string is submitted
- **Backend assumption:** Trusts submitted calculated values without recalculation
- **Database constraint impact:** Can insert incorrect totals if JS calculation fails
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

### Model: ProjectCCIStatistics
**Table:** `project_CCI_statistics`

**Database Schema:**
```php
$table->integer('total_children_previous_year')->nullable();
$table->integer('shifted_children_current_year')->nullable();
// ... all other fields ->nullable()
```

**Model Fillable:**
All 14 children statistics fields

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `<input type="text" name="shifted_children_current_year">` | `$validated['shifted_children_current_year'] ?? null` | ❌ **VIOLATED** |
| Edit | Uses `firstOrFail()` | Assumes record exists | ❌ **VIOLATED** |
| Update | Uses `updateOrCreate()` | Correctly handles missing records | ✅ Valid |

#### Identified Contract Violations

**Violation 4: Text Input for Integer Columns**
- **Operation:** Create
- **Fields involved:** All 14 integer columns
- **Frontend behavior:**
  ```blade
  <input type="text" name="shifted_children_current_year" class="no-spinner">
  ```
  - Uses `type="text"` instead of `type="number"`
  - No frontend validation for numeric values
  - Users can enter `-`, `N/A`, or any text
- **Backend assumption:**
  ```php
  $statistics->shifted_children_current_year = $validated['shifted_children_current_year'] ?? null;
  ```
  Assumes value is numeric or null
- **Database constraint impact:**
  ```
  SQLSTATE[22007]: Invalid datetime format: 1366 Incorrect integer value: '-'
  ```
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Production evidence:** 1 failure on CCI-0002 with literal `-` character

**Violation 5: firstOrFail() on First-Time Edit**
- **Operation:** Edit (read for editing)
- **Fields involved:** All fields
- **Frontend behavior:** User clicks "Edit" button for project with no statistics yet
- **Backend assumption:**
  ```php
  public function edit($projectId) {
      $statistics = ProjectCCIStatistics::where('project_id', $projectId)->firstOrFail();
  }
  ```
  Assumes statistics record always exists before editing
- **Database constraint impact:** None (query-level exception)
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Production evidence:** 3 failures on edit attempts for projects without statistics
- **Inconsistency:** `show()` method correctly uses `first()` with null check, but `edit()` uses `firstOrFail()`

---

### Model: ProjectIESAttachments
**Table:** `project_IES_attachments`

**Database Schema:**
```php
$table->string('aadhar_card')->nullable();
$table->string('fee_quotation')->nullable();
// ... 6 more attachment fields
```

**Model Methods:**
```php
public static function handleAttachments($request, $projectId)
```

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create/Update | `<input type="file" name="aadhar_card[]" multiple>` | `$file = $request->file($field)` expects single file | ❌ **VIOLATED** |
| Create/Update | Array of files per field | Calls `getClientOriginalExtension()` on array | ❌ **VIOLATED** |

#### Identified Contract Violations

**Violation 6: Array File Upload vs Single File Handler**
- **Operation:** Create/Update
- **Fields involved:** All 8 attachment fields
- **Frontend behavior:**
  ```blade
  <input type="file" name="{{ $field }}[]" ... >
  ```
  - Uses array notation `name="field[]"` to allow multiple files
  - JavaScript adds "Add Another File" functionality
  - Each field can have multiple file inputs
- **Backend assumption:**
  ```php
  foreach ($fields as $field) {
      if ($request->hasFile($field)) {
          $file = $request->file($field);
          $extension = strtolower($file->getClientOriginalExtension());  // FAILS
      }
  }
  ```
  - Expects `$request->file($field)` to return a single `UploadedFile` object
  - Calls method directly without checking if result is array
- **Database constraint impact:** None (runtime exception before database)
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Production evidence:** 7 failures from user ID 38
- **Root cause:** Frontend was changed to support multiple files but backend was never updated

**Violation 7: Multiple File Upload Without Pivot Table**
- **Operation:** Create
- **Fields involved:** All attachment fields
- **Frontend behavior:** Allows multiple files per field with names and descriptions
- **Backend assumption:** Database schema has only single string column per field type
- **Database constraint impact:** 
  - New files overwrite previous files
  - Only one file path can be stored per field
  - Multiple file metadata (`_names[]`, `_descriptions[]`) submitted but never stored
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**

---

### Model: ProjectBudget
**Table:** `project_budgets`

**Database Schema:**
```php
$table->decimal('rate_quantity', 10, 2)->nullable();
$table->decimal('rate_multiplier', 10, 2)->nullable();
$table->decimal('rate_duration', 10, 2)->nullable();
$table->decimal('this_phase', 10, 2)->nullable();
```

**Constraints:** DECIMAL(10,2) max value = 99,999,999.99

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create/Update | `<input type="number" name="phases[0][budget][0][rate_quantity]">` | Direct insertion with `?? 0` | ❌ **VIOLATED** |
| Create/Update | JavaScript multiplication: `rate_quantity * rate_multiplier * rate_duration` | Trusts calculated value | ❌ **VIOLATED** |

#### Identified Contract Violations

**Violation 8: Numeric Overflow from JavaScript Calculation**
- **Operation:** Create/Update
- **Fields involved:** `rate_duration`, `this_phase`
- **Frontend behavior:**
  ```javascript
  function calculateBudgetRowTotals(input) {
      const row = input.closest('tr');
      const quantity = parseFloat(row.querySelector('[name*="rate_quantity"]').value) || 0;
      const multiplier = parseFloat(row.querySelector('[name*="rate_multiplier"]').value) || 0;
      const duration = parseFloat(row.querySelector('[name*="rate_duration"]').value) || 0;
      const thisPhase = quantity * multiplier * duration;
      row.querySelector('[name*="this_phase"]').value = thisPhase;
  }
  ```
  - No bounds checking on calculated values
  - No validation that result fits in DECIMAL(10,2)
  - String concatenation errors can produce astronomical values
- **Backend assumption:**
  ```php
  'rate_duration' => $budget['rate_duration'] ?? 0,
  'this_phase' => $budget['this_phase'] ?? 0,
  ```
  - Backend validation only checks `min:0`
  - No `max` constraint
  - Trusts frontend calculation
- **Database constraint impact:**
  ```
  SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'rate_duration'
  Values: 121,000,112,100.00 (exceeds 99,999,999.99 limit)
  Values: 304,920,282,492,000,000.00 (wildly exceeds limit)
  ```
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Production evidence:** 6 failures on project DP-0033
- **Root cause:** Likely string concatenation bug in JavaScript or user entering commas/formatting

**Violation 9: No Server-Side Recalculation**
- **Operation:** Create/Update
- **Fields involved:** `this_phase` (calculated field)
- **Frontend behavior:** Calculated via JavaScript, submitted as readonly input
- **Backend assumption:** Trusts submitted value without recalculating `quantity * multiplier * duration`
- **Database constraint impact:** Can store incorrect totals if JS tampered or failed
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

### Model: ProjectIGEBudget
**Table:** `project_IGE_budget`

**Database Schema:**
```php
$table->string('name')->nullable();
$table->string('college_fees')->nullable();  // Note: string, not decimal
$table->string('total_amount')->nullable();  // Note: string, not decimal
```

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Read (PDF export) | N/A | Expects `$IGEbudget` variable | ❌ **VIOLATED** |
| Read (Standard view) | N/A | Passes `['IGEbudget' => $budget]` | ✅ Valid |

#### Identified Contract Violations

**Violation 10: Undefined Variable in PDF Export Path**
- **Operation:** Read (PDF generation)
- **Fields involved:** All fields (via view rendering)
- **Frontend behavior:** N/A (server-side rendering)
- **Backend assumption:**
  - **Standard view path:** `show.blade.php`
    ```blade
    @include('projects.partials.Show.IGE.budget', ['IGEbudget' => $budget ?? collect()])
    ```
  - **PDF export path:** `pdf.blade.php`
    ```blade
    @include('projects.partials.Show.IGE.budget')  // NO variable passed!
    ```
  - **Partial expectation:** `budget.blade.php`
    ```blade
    @if($IGEbudget && $IGEbudget->isNotEmpty())
    ```
- **Database constraint impact:** None (rendering exception)
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Production evidence:** 2 failures on IOGEP-0007
- **Root cause:** PDF export feature added later, didn't follow same data-passing convention as standard view

---

### Model: ProjectActivity (Logical Framework)
**Table:** `project_activities`

**Database Schema:**
```php
$table->text('activity');  // NOT NULL
$table->text('verification')->nullable();
```

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create/Update | JavaScript-generated nested arrays | Direct array access `$activityData['activity']` | ❌ **VIOLATED** |

#### Identified Contract Violations

**Violation 11: Undefined Array Key Without Defensive Access**
- **Operation:** Create/Update
- **Fields involved:** `activity`, `verification`
- **Frontend behavior:**
  - Complex nested structure: `objectives[0][activities][0][activity]`
  - JavaScript dynamically adds/removes activity rows
  - Can submit empty activity objects: `{"activities": [{}]}`
- **Backend assumption:**
  ```php
  foreach ($objectiveData['activities'] ?? [] as $activityData) {
      $activity = new ProjectActivity([
          'activity' => $activityData['activity'],  // FAILS if key missing
          'verification' => $activityData['verification'],
      ]);
  }
  ```
  - Outer loop uses `?? []` for safety
  - Inner access to `$activityData['activity']` has no safety check
- **Database constraint impact:** None (PHP error before database)
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Production evidence:** 1 failure on CIC-0002
- **Root cause:** Inconsistent defensive coding – some array accesses guarded, others not

---

### Model: ProjectIIESScopeFinancialSupport
**Table:** `project_IIES_scope_financial_support`

**Database Schema:**
```php
$table->boolean('govt_eligible_scholarship')->default(false);
$table->boolean('other_eligible_scholarship')->default(false);
$table->decimal('scholarship_amt', 10, 2)->nullable();
```

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | Checkbox inputs (missing when unchecked) | Cast to int: `(int) ($validated['govt_eligible_scholarship'] ?? 0)` | ⚠️ **PARTIAL** |

#### Identified Contract Violations

**Violation 12: Boolean Checkbox Absence vs Zero**
- **Operation:** Create
- **Fields involved:** `govt_eligible_scholarship`, `other_eligible_scholarship`
- **Frontend behavior:**
  - HTML checkboxes only submit when checked
  - Unchecked checkboxes send no data (key doesn't exist in request)
- **Backend assumption (current code):**
  ```php
  'govt_eligible_scholarship' => (int) ($validated['govt_eligible_scholarship'] ?? 0),
  ```
  - Current code correctly handles missing key with `?? 0`
  - Casts to int for boolean column
- **Database constraint impact:** 
  - Production log shows 1 failure where NULL reached database
  - Current code should prevent this
  - Suggests production was running old code version
- **Phase classification:** **Phase 1 – Critical Data Integrity** (historical issue)
- **Production evidence:** 1 failure on IIES-0030 (likely fixed since then)

---

## Cross-Cutting Contract Anti-Patterns

### Anti-Pattern 1: Null-Coalescing Operator Misuse

**Locations:** 
- `IIESExpensesController.php` (lines 57-61)
- `FinancialSupportController.php` (lines 29-34)
- `CCIStatisticsController.php` (lines 28-41)
- `BudgetController.php` (lines 70-75)

**Pattern:**
```php
$model->field = $validated['field'] ?? 0;
```

**Problem:**
- `??` only catches undefined keys
- Does NOT catch `""` (empty string), `null`, or `"0"`
- HTML5 number inputs submit `""` when empty
- Form fields with `value=""` submit empty string

**Why This Fails:**
```php
$data = ['field' => ''];           // Empty string from form
$value = $data['field'] ?? 0;      // Returns '', not 0
$result = (int) $value;            // Casts '' to 0 in PHP
// But when passed to Eloquent:
$model->field = '';                // Eloquent converts '' to NULL for decimals
// MySQL strict mode:
INSERT INTO table (field) VALUES (NULL);  // Fails on NOT NULL or default columns
```

**Correct Pattern:**
```php
$value = isset($validated['field']) && $validated['field'] !== '' 
    ? $validated['field'] 
    : 0;
```

---

### Anti-Pattern 2: Array vs Scalar File Upload Mismatch

**Locations:**
- `ProjectIESAttachments.php` (line 184)
- All attachment-related Blade views using `name="field[]"`

**Pattern:**
- Frontend: `<input type="file" name="aadhar_card[]">`
- Backend: `$file = $request->file('aadhar_card')`

**Problem:**
- `name="field[]"` creates array even with single file
- `$request->file('field')` returns:
  - `UploadedFile` object if single file (no `[]`)
  - `Array<UploadedFile>` if array notation (`[]`)
- Calling `$file->getClientOriginalExtension()` fails when `$file` is array

**Why This Fails:**
```php
// Frontend submits:
<input type="file" name="aadhar_card[]">
// Request contains:
['aadhar_card' => [UploadedFile]]
// Backend code:
$file = $request->file('aadhar_card');  // Returns array, not object
$ext = $file->getClientOriginalExtension();  // Fatal error: Call to method on array
```

**Correct Pattern:**
```php
$files = $request->file('field');
if (is_array($files)) {
    foreach ($files as $file) {
        $ext = $file->getClientOriginalExtension();
    }
} else if ($files) {
    $ext = $files->getClientOriginalExtension();
}
```

---

### Anti-Pattern 3: Text Input for Numeric Database Columns

**Locations:**
- `resources/views/projects/partials/Edit/CCI/statistics.blade.php` (all 14 fields)

**Pattern:**
```blade
<input type="text" name="shifted_children_current_year" class="no-spinner">
```

**Problem:**
- Database column type: `INTEGER`
- Input type: `text` (allows any characters)
- No frontend validation
- No backend type coercion before database

**Why This Fails:**
```php
// User enters:
<input type="text" value="-">
// Backend receives:
$validated['shifted_children_current_year'] = '-';
// Eloquent assigns:
$model->shifted_children_current_year = '-';
// MySQL attempts:
INSERT INTO table (shifted_children_current_year) VALUES ('-');
// MySQL error:
SQLSTATE[22007]: Incorrect integer value: '-'
```

**Why `type="text"` Used:**
- Comment in code suggests avoiding spinner arrows: `class="no-spinner"`
- CSS can disable spinners without using `type="text"`

**Correct Pattern:**
```blade
<input type="number" name="field" class="no-spinner">
```
```css
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button { display: none; }
```

---

### Anti-Pattern 4: firstOrFail() vs first() Inconsistency

**Locations:**
- `CCIStatisticsController::edit()` uses `firstOrFail()`
- `CCIStatisticsController::show()` uses `first()` with null check

**Pattern:**
```php
// Edit method:
public function edit($projectId) {
    $statistics = ProjectCCIStatistics::where('project_id', $projectId)->firstOrFail();
}

// Show method:
public function show($projectId) {
    $statistics = ProjectCCIStatistics::where('project_id', $projectId)->first();
    if (!$statistics) {
        Log::warning('No Statistics data found', ...);
    }
}
```

**Problem:**
- Edit is intended for first-time data entry AND editing existing data
- `firstOrFail()` throws exception if no record exists
- User cannot access edit form for new records

**Why This Fails:**
- CCI project created
- User clicks "Edit Statistics"
- No statistics record exists yet
- Controller throws 404 exception
- User sees error instead of empty form

**Correct Pattern:**
```php
public function edit($projectId) {
    $statistics = ProjectCCIStatistics::where('project_id', $projectId)->first();
    if (!$statistics) {
        $statistics = new ProjectCCIStatistics(['project_id' => $projectId]);
    }
    return $statistics;
}
```

**Note:** Some controllers correctly use this pattern (e.g., `IIESExpensesController::edit()`)

---

### Anti-Pattern 5: Trusted Calculated Fields

**Locations:**
- Budget: `this_phase` calculated in JavaScript
- IIES Expenses: `iies_total_expenses`, `iies_balance_requested` calculated in JavaScript

**Pattern:**
```javascript
function calculateTotal() {
    const total = field1 * field2 * field3;
    document.querySelector('[name="total"]').value = total;
}
```

**Problem:**
- Readonly fields are still editable via browser DevTools
- JavaScript can be disabled
- Calculation bugs produce incorrect values
- Backend trusts submitted value without recalculation

**Why This Fails:**
- User opens DevTools
- Edits readonly field value directly
- Submits inflated total
- Backend stores incorrect total without validation

**Correct Pattern:**
- Don't submit calculated fields
- Recalculate server-side from component values
- Or validate server calculation matches client calculation

```php
$calculated = $rate_quantity * $rate_multiplier * $rate_duration;
if (abs($calculated - $submitted_this_phase) > 0.01) {
    return back()->withErrors(['this_phase' => 'Calculated value mismatch']);
}
```

---

### Anti-Pattern 6: Alternate Render Path Data Contract Violation

**Locations:**
- `pdf.blade.php` includes IGE budget without variable
- `show.blade.php` includes IGE budget WITH variable

**Pattern:**
```blade
{{-- Standard view --}}
@include('partials.Show.IGE.budget', ['IGEbudget' => $budget])

{{-- PDF view --}}
@include('partials.Show.IGE.budget')  {{-- Missing variable! --}}
```

**Problem:**
- Blade partial expects specific variable names
- Different entry points (show, edit, pdf, export) don't maintain same contract
- No compile-time checking of passed variables

**Why This Fails:**
- `ExportController::downloadPdf()` loads data into `$data['budget']`
- View expects `$IGEbudget`
- Variable name mismatch causes undefined variable error

**Correct Pattern:**
- Standardize variable names across all entry points
- OR use explicit variable mapping in each include
- OR refactor partial to accept generic variable name

---

## Phase-wise Issue Summary

### Phase 1 – Critical (Must Fix First)

**Database Constraint Violations – Immediate Data Loss/Corruption**

1. **IIES Expenses NOT NULL Violations** (10 occurrences)
   - Empty string reaching decimal columns
   - Files: `IIESExpensesController.php`, IIES expense views
   - Impact: Users cannot save expense data

2. **Budget Numeric Overflow** (6 occurrences)
   - JavaScript calculation producing values exceeding DECIMAL(10,2)
   - Files: `BudgetController.php`, `resources/views/projects/partials/Edit/budget.blade.php`
   - Impact: Budget data corruption, save failures

3. **CCI Statistics Invalid Integer** (1 occurrence)
   - Text input allowing non-numeric characters
   - Files: `CCIStatisticsController.php`, CCI statistics view
   - Impact: Statistics save failures with literal `-` or text

4. **IES Attachments Array Type Mismatch** (7 occurrences)
   - Array file upload vs single file handler
   - Files: `ProjectIESAttachments.php`, IES attachments views
   - Impact: Complete attachment functionality failure

---

### Phase 2 – Normalization

**Input Handling & Type Coercion Issues**

5. **Null-Coalescing with Empty String** (pervasive)
   - `$validated['field'] ?? 0` doesn't catch empty string
   - Files: All controllers using this pattern
   - Impact: Inconsistent null handling, occasional failures

6. **Array-based Repeating Rows Without Validation**
   - IIES expense particulars/amounts can be mismatched or empty
   - Files: `IIESExpensesController.php`
   - Impact: Silent data loss on invalid rows

7. **Undefined Array Key Access**
   - `$activityData['activity']` without checking key existence
   - Files: `LogicalFrameworkController.php`
   - Impact: Occasional failures with malformed nested data

8. **Trusted Calculated Fields**
   - Budget `this_phase`, IIES totals calculated client-side only
   - Files: Budget and IIES controllers
   - Impact: Can store incorrect calculated values

9. **Boolean Checkbox Absence**
   - Unchecked checkboxes don't submit, requiring `?? false`
   - Files: IIES Financial Support controller
   - Impact: Historical failures (likely fixed)

---

### Phase 3 – Lifecycle

**Flow & State Management Issues**

10. **firstOrFail() on First-Time Edit** (3 occurrences)
    - CCI Statistics edit assumes record exists
    - Files: `CCIStatisticsController::edit()`
    - Impact: Cannot edit projects without existing statistics

11. **Inconsistent Edit-Before-Create Handling**
    - Some controllers handle missing records gracefully, others don't
    - Files: Various type-specific controllers
    - Impact: Inconsistent user experience across project types

---

### Phase 4 – Secondary Paths

**Alternate Rendering & View Issues**

12. **PDF Export Missing Variables** (2 occurrences)
    - `$IGEbudget` undefined in PDF render path
    - Files: `ExportController.php`, `pdf.blade.php`
    - Impact: PDF generation broken for IGE projects

13. **Multiple File Upload Metadata Loss**
    - Frontend allows multiple files with names/descriptions
    - Backend only stores single file path per field
    - Files: IES/IIES attachment views and models
    - Impact: Metadata loss, file overwrites

---

## Architectural Observations

### Where Contracts Are Implicit

1. **Between HTML Input Types and Database Types**
   - No enforcement that `<input type="number">` maps to numeric DB column
   - `<input type="text">` can be used for any data type
   - Frontend developer decisions affect backend data integrity

2. **Between Request Data Structure and Backend Expectations**
   - Array notation `name="field[]"` creates array in request
   - Backend assumes scalar unless explicitly handling arrays
   - No type hints or contracts at controller method level

3. **Between Blade View Variables and Controller Data**
   - Views expect specific variable names (`$IGEbudget`, `$statistics`)
   - Controllers pass data with arbitrary keys (`$data['budget']`)
   - No compile-time checking of variable contracts

4. **Between Database `->default()` and `->nullable()`**
   - Migrations define defaults: `->default(0)`
   - Explicit NULL/empty string bypasses defaults
   - MySQL strict mode enforces constraints that migrations suggest are optional

---

### Where Contracts Are Violated

1. **JavaScript Calculation → Backend Trust**
   - Frontend calculates totals, backend stores without verification
   - No server-side recalculation or validation
   - Allows tampering or calculation bugs to corrupt data

2. **Single Entry Point Validation → Multiple Entry Points**
   - Form Request validates create/update
   - PDF export, show view, edit view don't go through same validation
   - Different paths have different data requirements

3. **Frontend File Structure → Backend Handler Assumptions**
   - View changed to support multiple files (`name="field[]"`)
   - Backend never updated to handle arrays
   - Type contract violated without test coverage catching it

4. **NULL vs Empty String vs Zero**
   - Three distinct values with different meanings
   - Often treated as equivalent by backend logic
   - Database enforces distinctions that application logic ignores

---

### Where Responsibility Is Unclear

1. **Who Validates Calculated Fields?**
   - Frontend calculates in JavaScript
   - Backend could recalculate to verify
   - Currently: neither validates, both trust the other

2. **Who Enforces Type Correctness?**
   - HTML input type suggests type
   - Backend Form Request validation is optional
   - Database constraints are final enforcement
   - Currently: Database is first line of defense (should be last)

3. **Who Ensures Related Records Exist?**
   - Some controllers use `firstOrFail()` (fails fast)
   - Some controllers use `first()` with null check (graceful)
   - Some controllers use `updateOrCreate()` (creates if missing)
   - Currently: Inconsistent per developer preference

4. **Who Maintains View-Controller Data Contracts?**
   - Views expect specific variable names
   - Controllers pass variables
   - No enforced contract between them
   - Currently: Runtime failures reveal contract violations

---

## Methodology Notes

This audit analyzed:
- **108 Models** across `app/Models` and subfolders
- **152 Migrations** defining database constraints
- **120 Form Requests** defining validation rules
- **200+ Blade Views** in `resources/views/projects/partials`
- **50+ Controllers** in `app/Http/Controllers/Projects`
- **Production Log:** `laravel3031prod.log` (59 unique errors)

Each identified violation was traced through:
1. Database schema (migration files)
2. Model definition (fillable, casts, relationships)
3. Frontend input (Blade views, JavaScript)
4. Backend handler (controller methods)
5. Validation rules (Form Requests)
6. Production failures (log evidence)

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers
- ❌ Do not modify views
- ❌ Do not propose solutions

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Contract audit performed by: Senior Laravel Architect*  
*Models analyzed: 108*  
*Contract violations identified: 13 primary + 6 anti-patterns*
