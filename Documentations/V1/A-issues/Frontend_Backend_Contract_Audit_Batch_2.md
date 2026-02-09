# Frontend–Backend Contract Audit – Batch 2

## Purpose

This document continues the contract audit series, analyzing additional models, controllers, and reporting infrastructure. This batch focuses on simple CRUD patterns, file upload handling, reporting models, and service layer strategies.

---

## Model-by-Model Contract Review (Batch 2)

### Model: ProjectCCIRationale
**Table:** `project_CCI_rationale`

**Database Schema:**
```php
$table->string('CCI_rationale_id')->unique();
$table->string('project_id');
$table->text('description')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\CCI\RationaleController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | Textarea for description | `$validated['description'] ?? null` | ✅ Valid |
| Update | Same textarea | `updateOrCreate()` pattern | ✅ Valid |
| Edit | N/A | Uses `firstOrFail()` | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 35: Edit Uses `firstOrFail()`, Show Uses `first()`**
- **Operation:** Edit vs Show
- **Fields involved:** N/A
- **Frontend behavior:** Edit form link always visible
- **Backend assumption:**
  ```php
  // In edit():
  $rationale = ProjectCCIRationale::where('project_id', $projectId)->firstOrFail();
  
  // In show():
  $rationale = ProjectCCIRationale::where('project_id', $projectId)->first();
  if (!$rationale) {
      Log::warning('No Rationale data found', ['project_id' => $projectId]);
  }
  ```
  `edit()` throws 404 if no rationale exists; `show()` gracefully returns null
- **Database constraint impact:** None directly, but UX inconsistency
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** User clicks Edit when no rationale exists → 404 error instead of empty form

**Violation 36: UpdateOrCreate in Update Method**
- **Operation:** Update
- **Fields involved:** `description`
- **Frontend behavior:** User edits existing rationale
- **Backend assumption:**
  ```php
  $rationale = ProjectCCIRationale::updateOrCreate(
      ['project_id' => $projectId],
      ['description' => $validated['description'] ?? null]
  );
  ```
  Creates new record if none exists during "update"
- **Database constraint impact:** None, but semantic confusion
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ✅ **Intentional Design** – Update creates if missing (upsert pattern)

---

### Model: ProjectIGEBeneficiariesSupported
**Table:** `project_IGE_beneficiaries_supported`

**Database Schema:**
```php
$table->string('IGE_bnfcry_supprtd_id')->unique();
$table->string('project_id');
$table->string('class')->nullable();
$table->integer('total_number')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\IGE\IGEBeneficiariesSupportedController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `class[]`, `total_number[]` arrays | Parallel iteration with null check | ⚠️ **RISK** |
| Edit | N/A | Returns collection, checks instanceof | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 37: Redundant Collection Type Check**
- **Operation:** Edit
- **Fields involved:** N/A
- **Frontend behavior:** Expects collection of beneficiaries
- **Backend assumption:**
  ```php
  $beneficiariesSupported = ProjectIGEBeneficiariesSupported::where('project_id', $projectId)->get();
  
  // Redundant check: get() ALWAYS returns Collection
  if (!$beneficiariesSupported instanceof \Illuminate\Database\Eloquent\Collection) {
      $beneficiariesSupported = collect();
  }
  ```
  Eloquent's `get()` always returns a Collection instance, check is unnecessary
- **Database constraint impact:** None
- **Phase classification:** N/A
- **Status:** ⚠️ **Code Smell** – Unreachable defensive code

**Violation 38: Integer Column Accepting Null Without Type Cast**
- **Operation:** Create
- **Fields involved:** `total_number`
- **Frontend behavior:**
  ```blade
  <input type="number" name="total_number[]" class="form-control">
  ```
  Can be empty string if user clears field
- **Backend assumption:**
  ```php
  if (!is_null($class) && !is_null($totalNumbers[$index] ?? null)) {
      ProjectIGEBeneficiariesSupported::create([
          'total_number' => $totalNumbers[$index],  // Empty string → 0 via Eloquent casting
      ]);
  }
  ```
  Empty string becomes `0` due to integer column type
- **Database constraint impact:** Empty input saved as `0` instead of `NULL`
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** User intent "no value" stored as "zero value"

---

### Model: ProjectAttachment
**Table:** `project_attachments`

**Database Schema:**
```php
$table->string('project_id');
$table->string('file_path')->nullable();
$table->string('file_name')->nullable();
$table->string('description')->nullable();
$table->string('public_url')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\AttachmentController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Store | `file` (UploadedFile), `file_name`, `attachment_description` | Extensive validation | ✅ Excellent |
| Update | Same as Store | Adds new attachment (not replace) | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 39: Update Method Adds, Doesn't Replace**
- **Operation:** Update
- **Fields involved:** All file fields
- **Frontend behavior:** User expects to "update" attachment
- **Backend assumption:**
  ```php
  // AttachmentController::update()
  // Comment says: "Store new file (add to existing attachments, don't replace)"
  $attachment = new ProjectAttachment([...]);
  $attachment->save();
  
  return redirect()->back()->with('success', 'Attachment added successfully');
  ```
  Method named `update()` but creates new record, doesn't update existing
- **Database constraint impact:** Multiple attachments accumulate
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** User confusion – "update" route adds files instead of replacing

**Violation 40: File Validation Duplicated Across Store/Update**
- **Operation:** Store and Update
- **Fields involved:** File validation logic
- **Frontend behavior:** File upload form
- **Backend assumption:**
  ```php
  // Validation logic repeated in both store() and update():
  $request->validate(['file' => 'required|file|max:7168']);
  if (!$this->isValidFileType($file)) { ... }
  if ($file->getSize() > $maxSize) { ... }
  $filename = $this->sanitizeFilename(...);
  // ... exact same logic in both methods
  ```
  50+ lines of identical validation/processing code duplicated
- **Database constraint impact:** None
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Code Smell** – DRY violation, but functionally correct

**Status: ✅ AttachmentController is Well-Designed**
Despite violations 39-40, this controller demonstrates:
- Comprehensive MIME type validation
- File size checks (client and server)
- Path traversal prevention via `sanitizeFilename()`
- Security-conscious storage path sanitization
- Transactional file operations with rollback
- Detailed audit logging throughout

---

## Reporting Infrastructure Analysis

### Model: DPReport (Monthly Development Project Report)
**Table:** `DP_Reports`

**Database Schema:**
```php
$table->string('report_id')->primary();
$table->string('project_id');
$table->string('amount_sanctioned_overview')->nullable();
$table->string('amount_forwarded_overview')->nullable();
$table->string('amount_in_hand')->nullable();
$table->string('total_balance_forwarded')->nullable();
$table->enum('status', [multiple status constants]);
```

**Controller:** `App\Http\Controllers\Reports\Monthly\ReportController`

#### Database Design Analysis

**Observation 1: String Columns for Amounts**
- All monetary fields use `string` type instead of `decimal`
- Same anti-pattern as project models (legacy decision)
- Allows non-numeric values, no database-level validation

**Observation 2: Complex Status Enum**
- 14 distinct status values
- Includes granular revert statuses: `reverted_to_executor`, `reverted_to_applicant`, etc.
- Well-documented status labels array
- Helper methods: `isApproved()`, `isEditable()`, `isSubmittedToProvincial()`

#### Identified Contract Violations

**Violation 41: Amount Forwarded Always Set to 0**
- **Operation:** Report creation
- **Fields involved:** `amount_forwarded_overview`
- **Frontend behavior:** Might expect forwarded amount from project
- **Backend assumption:**
  ```php
  // ReportController::create()
  $amountForwarded = 0.00; // Always set to 0 - no longer used in reports
  ```
  Hardcoded to 0, but database column still exists
- **Database constraint impact:** Dead column storing always-zero values
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Technical Debt** – Column should be removed or repurposed

---

### Model: DPObjective (Report Objective)
**Table:** `DP_Objectives`

**Database Schema:**
```php
$table->string('objective_id')->primary();
$table->string('report_id');
$table->string('project_objective_id')->nullable();
$table->text('objective')->nullable();
$table->json('expected_outcome')->nullable();
$table->boolean('changes')->nullable();
```

**Casts:**
```php
'expected_outcome' => 'array',
'changes' => 'boolean',
```

#### Identified Contract Violations

**Violation 42: JSON Array Without Frontend Schema Validation**
- **Operation:** Store report objectives
- **Fields involved:** `expected_outcome`
- **Frontend behavior:** Likely multiple inputs serialized to JSON
- **Backend assumption:**
  ```php
  protected $casts = [
      'expected_outcome' => 'array',
  ];
  ```
  Eloquent auto-converts array to JSON, accepts any structure
- **Database constraint impact:** No schema enforcement on JSON structure
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Inconsistent array structures across records, breaking display logic

---

### Model: DPActivity (Report Activity)
**Table:** `DP_Activities`

**Database Schema:**
```php
$table->string('activity_id')->primary();
$table->string('objective_id');
$table->string('project_activity_id')->nullable();
$table->text('activity')->nullable();
$table->string('month')->nullable();
```

**Model Method:**
```php
public function hasUserFilledData(): bool
{
    if (trim((string) ($this->summary_activities ?? '')) !== '') return true;
    if (trim((string) ($this->qualitative_quantitative_data ?? '')) !== '') return true;
    if (trim((string) ($this->intermediate_outcomes ?? '')) !== '') return true;
    // "Add Other Activity": project_activity_id empty and user-typed activity
    if (trim((string) ($this->project_activity_id ?? '')) === '' && trim((string) ($this->activity ?? '')) !== '') {
        return true;
    }
    return false;
}
```

#### Identified Contract Violations

**Violation 43: Month Field Documented as "JS-Filled", No Backend Validation**
- **Operation:** Activity creation
- **Fields involved:** `month`
- **Frontend behavior:** JavaScript populates `month` from report period
- **Backend assumption:**
  ```php
  // Comment in hasUserFilledData():
  // "month is JS-filled (report-period-sync), not user-filled; excluded from check."
  ```
  Backend trusts frontend to populate `month` correctly
- **Database constraint impact:** Missing validation; corrupt data if JS fails
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Records with null or invalid `month` if JS doesn't execute

**Violation 44: Complex Business Logic in Model Method**
- **Operation:** Determining if activity should be saved
- **Fields involved:** All activity fields
- **Frontend behavior:** User may leave fields empty
- **Backend assumption:**
  ```php
  public function hasUserFilledData(): bool
  {
      // Complex logic mixing field emptiness checks with conditional rules
      // Different treatment for project_activity_id empty vs filled
  }
  ```
  This belongs in a service layer, not a model method
- **Database constraint impact:** None directly
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architectural Issue** – Business logic leaking into model

---

### Model: DPAccountDetail (Report Budget Accounting)
**Table:** `DP_AccountDetails`

**Database Schema:**
```php
$table->string('particulars')->nullable();
$table->string('amount_forwarded')->nullable();
$table->string('amount_sanctioned')->nullable();
$table->string('total_amount')->nullable();
$table->string('expenses_last_month')->nullable();
$table->string('expenses_this_month')->nullable();
$table->string('total_expenses')->nullable();
$table->string('balance_amount')->nullable();
```

**Fillable includes:**
```php
'is_budget_row',  // But not in schema above!
```

#### Identified Contract Violations

**Violation 45: Fillable Field Not in Migration**
- **Operation:** Create account detail
- **Fields involved:** `is_budget_row`
- **Frontend behavior:** Unknown (field usage undocumented)
- **Backend assumption:**
  ```php
  protected $fillable = [
      // ... other fields ...
      'is_budget_row',  // Field listed as fillable
  ];
  ```
  But migration doesn't define `is_budget_row` column
- **Database constraint impact:**
  - If column doesn't exist: `is_budget_row` silently ignored on insert
  - If column added later: Version mismatch between code and schema
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **CRITICAL** – Code–schema mismatch

**Violation 46: All Amount Fields as String (Reporting Tables)**
- **Operation:** All accounting operations
- **Fields involved:** All 7 amount columns
- **Frontend behavior:** Number inputs
- **Backend assumption:** String storage for monetary values
- **Database constraint impact:** Same as project tables – no numeric validation
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ⚠️ **Systemic** – Affects entire reporting subsystem

---

## Service Layer Pattern Analysis (Batch 2)

### Service: BudgetCalculationService

**Location:** `App\Services\Budget\BudgetCalculationService`

**Purpose:** Centralized budget calculation using strategy pattern

**Public Methods:**
```php
public static function getBudgetsForReport(Project $project, bool $calculateContributions = true): Collection
public static function getBudgetsForExport(Project $project): Collection
public static function calculateContributionPerRow(float $contribution, int $totalRows): float
public static function calculateAmountSanctioned(float $originalAmount, float $contributionPerRow): float
```

#### Service Contract Analysis

**Strength 1: Strategy Pattern Implementation**
```php
private static function getStrategyForProjectType(string $projectType): BudgetCalculationStrategyInterface
{
    $config = config('budget.field_mappings');
    if (!isset($config[$projectType])) {
        Log::warning('Unknown project type, using fallback');
        return new DirectMappingStrategy('Development Projects');
    }
    $strategyClass = $config[$projectType]['strategy'];
    return new $strategyClass($projectType);
}
```
✅ **Excellent Design:**
- Configuration-driven strategy selection
- Graceful fallback for unknown types
- Proper logging for debugging

**Strength 2: Defensive Math Operations**
```php
public static function preventNegativeAmount(float $amount): float
{
    return max(0, $amount);
}

public static function calculateContributionPerRow(float $contribution, int $totalRows): float
{
    return $totalRows > 0 ? $contribution / $totalRows : 0;
}
```
✅ **Correct:** Division-by-zero prevention, negative amount handling

#### Identified Contract Violations

**Violation 47: Static Methods for Service That Could Benefit from Dependency Injection**
- **Operation:** All budget calculations
- **Caller behavior:** Static calls from controllers and other services
- **Service design:**
  ```php
  public static function getBudgetsForReport(Project $project, ...): Collection
  {
      $strategy = self::getStrategyForProjectType($project->project_type);
      return $strategy->getBudgets($project, ...);
  }
  ```
  All methods are static; service can't be mocked or extended
- **Impact:** Testing difficulty, hard to override in tests
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architectural Trade-off** – Simpler syntax but less testable

**Violation 48: Exception Thrown for Missing Strategy Class**
- **Operation:** Strategy instantiation
- **Caller behavior:** Expects collection return
- **Service assumption:**
  ```php
  if (!class_exists($strategyClass)) {
      throw new \RuntimeException("Strategy class not found: {$strategyClass}");
  }
  ```
  Throws exception if config references non-existent class
- **Impact:** 500 error if config is incorrect
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ✅ **Correct** – Config errors should fail fast

---

## View Pattern Analysis (Batch 2)

### Pattern: S.No Column Auto-Renumbering

**View:** `projects/partials/Edit/IGE/beneficiaries_supported.blade.php`

**Implementation:**
```blade
<tbody id="beneficiaries-supported-rows">
    <tr>
        <td>{{ $index + 1 }}</td>  <!-- S.No -->
        <td><input name="class[]"></td>
        <td><input name="total_number[]"></td>
        <td><button onclick="removeBeneficiaryRow(this)">Remove</button></td>
    </tr>
</tbody>
```

```javascript
function removeBeneficiaryRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateBeneficiaryRowNumbers();  // Renumber remaining rows
}

function updateBeneficiaryRowNumbers() {
    const rows = document.querySelectorAll('#beneficiaries-supported-rows tr');
    rows.forEach((row, index) => {
        row.children[0].textContent = index + 1;  // Update S.No
    });
    beneficiaryRowIndex = rows.length;
}
```

#### Identified Contract Issues

**Violation 49: S.No Renumbering After Remove Creates Index Mismatch**
- **Operation:** User removes middle row
- **Frontend behavior:**
  1. Initial: Rows 1, 2, 3 with inputs `class[0]`, `class[1]`, `class[2]`
  2. Remove row 2
  3. S.No renumbered: Rows now show 1, 2 (but inputs still `class[0]`, `class[2]`)
  4. Visual S.No doesn't match array indices
- **Backend assumption:** PHP `foreach` handles sparse arrays correctly (it does)
- **Impact:** None (PHP handles it), but confusing for debugging
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **UX Confusion** – Functionally correct but visually inconsistent

**Violation 50: Global JS Function Without Namespace**
- **Operation:** Dynamic row management
- **View pattern:**
  ```javascript
  function addBeneficiaryRow() { ... }  // Global scope
  function removeBeneficiaryRow(button) { ... }  // Global scope
  ```
  Multiple project-type partials loaded on same page, all define similar global functions
- **Impact:** Function name collisions if multiple partials loaded
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Risk** – Works now but fragile; should use IIFE or namespacing

---

### Pattern: Inline Styles in Blade Components

**View:** `projects/partials/Edit/IGE/beneficiaries_supported.blade.php`

**Implementation:**
```blade
<!-- Styles -->
<style>
    .form-control {
        color: white;
    }
</style>
```

#### Identified Contract Issues

**Violation 51: Component-Specific Styles Affecting Global Class**
- **Operation:** Rendering IGE form
- **View behavior:**
  ```blade
  <style>
      .form-control {  /* Bootstrap global class */
          color: white;
      }
  </style>
  ```
  Overrides `.form-control` globally, not scoped to this component
- **Impact:** White text on white background in other forms
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ❌ **CSS Specificity Bug** – Breaks other forms on same page

---

## Database Migration Analysis

### Migration: create_project_IIES_expenses_table

**IIES Expenses:**
```php
$table->decimal('iies_total_expenses', 10, 2)->default(0);
$table->decimal('iies_expected_scholarship_govt', 10, 2)->default(0);
$table->decimal('iies_support_other_sources', 10, 2)->default(0);
$table->decimal('iies_beneficiary_contribution', 10, 2)->default(0);
$table->decimal('iies_balance_requested', 10, 2)->default(0);
```

**IIES Expense Details:**
```php
$table->string('iies_particular');
$table->decimal('iies_amount', 10, 2);
```

#### Comparison with Other Expense Tables

**Violation 52: Inconsistent Column Types Across Similar Tables**
- **IIES Migration:** Uses `decimal(10,2)` with `->default(0)` (correct)
- **IES Migration (not shown but exists):** Uses `string` type for same fields
- **Backend controllers:** Both use identical logic:
  ```php
  $expenses->total_expenses = $validated['total_expenses'] ?? null;
  ```
  Same code for different schema types
- **Impact:**
  - IIES: Database enforces numeric, rejects invalid values
  - IES: String columns accept anything, including "abc"
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **CRITICAL** – Inconsistent schema design for similar functionality

---

## Cross-Cutting Patterns (Batch 2)

### Pattern: Controller Edit Methods with firstOrFail() vs first()

**Inconsistent Error Handling Across Controllers:**

| Controller | Edit Method | Show Method | Contract |
|-----------|-------------|-------------|----------|
| `CCIRationaleController` | `firstOrFail()` | `first() + null check` | ❌ Inconsistent |
| `IESExpensesController` | N/A | Returns null on error | ✅ Consistent |
| `IGEBeneficiariesSupportedController` | `get()` (no fail) | `get() + isEmpty()` | ✅ Consistent |
| `StatisticsController` | `firstOrFail()` | N/A | ⚠️ Throws on missing |

**Violation 53: No Standard Pattern for "Edit Record That Might Not Exist"**
- **Inconsistency:** Some controllers throw 404, others return null, others return empty collection
- **Impact:** Frontend must handle multiple error patterns
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**

---

### Pattern: Parallel Array Processing Validation

**Controllers Using Parallel Arrays:**
- `IESExpensesController`: `particulars[]`, `amounts[]`
- `ILPBudgetController`: `budget_desc[]`, `cost[]`
- `IAHBudgetDetailsController`: `particular[]`, `amount[]`
- `IGEBeneficiariesSupportedController`: `class[]`, `total_number[]`
- `LDPTargetGroupController`: `L_beneficiary_name[]`, `L_family_situation[]`, etc.

**Common Validation Pattern:**
```php
foreach ($array1 as $index => $value1) {
    if (!is_null($value1) && !is_null($array2[$index] ?? null)) {
        // Create record
    }
}
```

**Violation 54: No Check for Array Length Mismatch**
- **Risk:** If frontend sends mismatched array lengths:
  ```
  particulars = ['Item 1', 'Item 2', 'Item 3']
  amounts = ['100', '200']  // Missing amount for Item 3
  ```
  Loop creates 2 records, silently drops Item 3
- **Impact:** Data loss without error
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Systemic** – Affects all multi-row controllers

---

## Phase-wise Issue Summary (Batch 2)

### Phase 1 – Critical Data Integrity

| # | Issue | Models Affected | Risk Level |
|---|-------|-----------------|------------|
| 45 | Fillable field not in migration | DPAccountDetail | Critical |
| 46 | All amount fields as string (reporting) | DPAccountDetail, DPReport | High |
| 52 | Inconsistent column types (IIES decimal, IES string) | ProjectIIESExpenses vs ProjectIESExpenses | Critical |

### Phase 2 – Input Normalization Gaps

| # | Issue | Models Affected | Risk Level |
|---|-------|-----------------|------------|
| 38 | Integer column: empty string → 0 instead of NULL | IGEBeneficiariesSupported | Medium |
| 42 | JSON array without frontend schema validation | DPObjective | Medium |
| 43 | Month field JS-filled, no backend validation | DPActivity | Medium |
| 54 | No check for parallel array length mismatch | All multi-row controllers | High |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Controllers Affected | Risk Level |
|---|-------|---------------------|------------|
| 35 | Edit uses firstOrFail(), Show uses first() | CCIRationaleController | Low |
| 39 | Update method adds files, doesn't replace | AttachmentController | Medium |
| 44 | Business logic in model method | DPActivity | Low |
| 47 | Static methods prevent DI/mocking | BudgetCalculationService | Low |
| 53 | No standard pattern for edit-when-missing | Multiple controllers | Medium |

### Phase 4 – Presentation & Secondary Paths

| # | Issue | Components Affected | Risk Level |
|---|-------|---------------------|------------|
| 40 | Duplicate validation logic | AttachmentController | Low |
| 41 | Dead column (amount_forwarded always 0) | DPReport | Low |
| 49 | S.No renumbering creates index mismatch | IGE views | Low |
| 50 | Global JS functions without namespace | All dynamic row views | Medium |
| 51 | Component styles affecting global classes | IGE beneficiaries view | High |

---

## Strengths Identified (Batch 2)

### Excellent Implementations

**1. AttachmentController Security**
```php
private function sanitizeFilename($filename, $extension)
{
    $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $filename);
    $filename = trim($filename, '._');
    if (empty($filename)) {
        $filename = 'attachment';
    }
    return $filename . '.' . $extension;
}
```
✅ **Best Practice:** Path traversal prevention, dangerous character filtering

**2. BudgetCalculationService Strategy Pattern**
```php
private static function getStrategyForProjectType(string $projectType): BudgetCalculationStrategyInterface
{
    $config = config('budget.field_mappings');
    if (!isset($config[$projectType])) {
        Log::warning('Unknown project type, using fallback');
        return new DirectMappingStrategy('Development Projects');
    }
    // ...
}
```
✅ **Best Practice:** Configuration-driven, graceful fallback, proper logging

**3. DPReport Status Management**
```php
public const STATUS_DRAFT = 'draft';
public const STATUS_SUBMITTED_TO_PROVINCIAL = 'submitted_to_provincial';
// ... 14 total statuses

public static $statusLabels = [
    'draft' => 'Draft (Executor still working)',
    // ... human-readable labels
];

public function isApproved(): bool { ... }
public function isEditable(): bool { ... }
```
✅ **Best Practice:** Constants for statuses, helper methods, comprehensive labels

**4. DPActivity Business Logic Encapsulation**
```php
public function hasUserFilledData(): bool
{
    // Clear intent: "Should this activity be saved?"
    // All business rules in one place
}
```
✅ **Good Pattern:** Despite being in model (violation 44), method name clearly communicates intent

---

## Architectural Observations (Batch 2)

### Service Layer Maturity

**Positive Evolution:**
1. `BudgetCalculationService` extracted from controllers (good)
2. Strategy pattern for type-specific logic (excellent)
3. Centralized defensive math operations (excellent)

**Remaining Issues:**
1. Static methods reduce testability
2. Some budget logic still in controllers (not fully extracted)

### Reporting Infrastructure Complexity

**Observations:**
1. Reporting models more complex than project models:
   - JSON columns for dynamic data
   - 14-value status enums
   - Multiple helper methods
   - Relationship to both projects and previous reports

2. String columns for amounts persist into reporting:
   - Same anti-pattern propagated
   - Harder to fix (affects 7 tables minimum)

3. Well-designed status management:
   - Constants prevent magic strings
   - Helper methods encapsulate logic
   - Status labels for display

### File Upload Handling

**AttachmentController is Production-Ready:**
- Comprehensive validation (MIME, size, extension)
- Security hardening (path sanitization, directory traversal prevention)
- Transactional operations with rollback
- Detailed audit logging
- Error handling with cleanup

**But:**
- Update method should replace, not add (semantic issue)
- Validation logic should be extracted to service/request class

---

## Summary Statistics (Batch 2)

| Category | Count |
|----------|-------|
| Models analyzed | 6 |
| Controllers analyzed | 5 |
| Services analyzed | 1 |
| Migrations reviewed | 1 |
| New violations identified | 20 (35-54) |
| Phase 1 violations | 3 |
| Phase 2 violations | 4 |
| Phase 3 violations | 6 |
| Phase 4 violations | 7 |
| Excellent patterns | 4 |

---

## Cumulative Statistics (All Batches)

| Batch | Models | Controllers | Services | Violations | Critical |
|-------|--------|-------------|----------|------------|----------|
| Primary | 7 | 5 | 0 | 13 | 4 |
| Extended | 6 | 8 | 2 | 22 | 0 |
| Batch 2 | 6 | 5 | 1 | 20 | 3 |
| **Total** | **19** | **18** | **3** | **55** | **7** |

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
*Batch 2 contract audit performed by: Senior Laravel Architect*
