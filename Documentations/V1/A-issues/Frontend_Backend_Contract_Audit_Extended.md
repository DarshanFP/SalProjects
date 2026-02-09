# Frontend–Backend Contract Audit – Extended Analysis

## Purpose

This document extends the primary contract audit (`Frontend_Backend_Contract_Audit.md`) with additional models, controllers, and services analysis. It covers project type-specific budget handling, multi-row dynamic form patterns, and service layer contract violations.

---

## Model-by-Model Contract Review (Extended)

### Model: ProjectIESExpenses
**Table:** `project_IES_expenses`

**Database Schema:**
```php
$table->string('total_expenses')->nullable();
$table->string('expected_scholarship_govt')->nullable();
$table->string('support_other_sources')->nullable();
$table->string('beneficiary_contribution')->nullable();
$table->string('balance_requested')->nullable();
```

**Note:** Uses `string` type for decimal values (legacy design decision)

**Model Fillable:**
```php
'total_expenses', 'expected_scholarship_govt', 'support_other_sources', 
'beneficiary_contribution', 'balance_requested'
```

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `<input type="number" name="total_expenses">` | `$validated['total_expenses'] ?? null` | ⚠️ **RISK** |
| Create | `particulars[]`, `amounts[]` arrays | Parallel array iteration | ⚠️ **RISK** |
| Update | Reuses `store()` method | Same as Create | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 13: String Storage for Numeric Values**
- **Operation:** Create/Update
- **Fields involved:** All expense fields
- **Frontend behavior:** `<input type="number">` submits numeric strings
- **Backend assumption:**
  ```php
  $projectExpenses->total_expenses = $validated['total_expenses'] ?? null;
  ```
  Assigns directly without type casting
- **Database constraint impact:**
  - String columns accept any value including empty string
  - No numeric validation at database level
  - Allows non-numeric values like "N/A" or "-"
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Data integrity relies entirely on frontend validation

**Violation 14: Parallel Array Iteration Without Length Check**
- **Operation:** Create/Update
- **Fields involved:** `particulars[]`, `amounts[]`
- **Frontend behavior:**
  ```blade
  <input name="particulars[]">
  <input name="amounts[]">
  ```
  Dynamic rows can have mismatched lengths if JS fails
- **Backend assumption:**
  ```php
  for ($i = 0; $i < count($particulars); $i++) {
      if (!empty($particulars[$i]) && !empty($amounts[$i])) {
          // create detail
      }
  }
  ```
  Iterates based on `particulars` length, accesses `amounts[$i]` without checking array bounds
- **Database constraint impact:** ArrayIndexOutOfBounds if arrays mismatched
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

### Model: ProjectILPBudget
**Table:** `project_ILP_budget`

**Database Schema:**
```php
$table->string('budget_desc')->nullable();
$table->string('cost')->nullable();
$table->string('beneficiary_contribution')->nullable();
$table->string('amount_requested')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\ILP\BudgetController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `budget_desc[0]`, `budget_desc[1]`, ... indexed arrays | `$validated['budget_desc'] ?? []` | ⚠️ **RISK** |
| Create | `beneficiary_contribution` (single value) | Applied to ALL rows | ❌ **VIOLATED** |
| Show | N/A | Returns first row's contribution for all | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 15: Single Value Applied to Multiple Rows**
- **Operation:** Create/Update
- **Fields involved:** `beneficiary_contribution`, `amount_requested`
- **Frontend behavior:**
  ```blade
  <input type="number" name="beneficiary_contribution" value="{{ $beneficiary_contribution ?? '' }}">
  ```
  Single input field at form level
- **Backend assumption:**
  ```php
  foreach ($budgetDescs as $index => $description) {
      ProjectILPBudget::create([
          'project_id' => $projectId,
          'budget_desc' => $description,
          'cost' => $costs[$index] ?? null,
          'beneficiary_contribution' => $validated['beneficiary_contribution'] ?? null,  // SAME for ALL
          'amount_requested' => $validated['amount_requested'] ?? null,  // SAME for ALL
      ]);
  }
  ```
  Same `beneficiary_contribution` applied to every budget row
- **Database constraint impact:** Redundant data storage, 5 identical rows get same contribution value
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Architectural issue:** Database schema stores per-row data but only first row values are meaningful

**Violation 16: Show Method Returns First Row Values for Summary**
- **Operation:** Show/Edit
- **Fields involved:** `beneficiary_contribution`, `amount_requested`
- **Frontend behavior:** Expects single summary values
- **Backend assumption:**
  ```php
  return [
      'budgets' => $budgets,
      'total_amount' => $budgets->sum('cost'),
      'beneficiary_contribution' => $budgets->first()->beneficiary_contribution ?? 0,  // First row only!
      'amount_requested' => $budgets->first()->amount_requested ?? 0,  // First row only!
  ];
  ```
- **Database constraint impact:** None, but data loss if first row deleted
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**

---

### Model: ProjectIAHBudgetDetails
**Table:** `project_IAH_budget_details`

**Database Schema:**
```php
$table->string('particular')->nullable();
$table->string('amount')->nullable();
$table->string('total_expenses')->nullable();
$table->string('family_contribution')->nullable();
$table->string('amount_requested')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\IAH\IAHBudgetDetailsController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `particular[]`, `amount[]` arrays | Parallel array iteration | ⚠️ **RISK** |
| Create | `family_contribution` (single) | Applied to all rows | ⚠️ **RISK** |
| Create | N/A | Server calculates `amount_requested` | ✅ Valid |
| Edit | N/A | Maps data for frontend consumption | ✅ Valid |

#### Identified Contract Violations

**Violation 17: Server-Side Calculation Using `array_sum()` on Potentially Non-Numeric Array**
- **Operation:** Create/Update
- **Fields involved:** `amount[]`, `total_expenses`
- **Frontend behavior:**
  ```blade
  <input type="number" name="amount[]" class="form-control amount-field">
  ```
- **Backend assumption:**
  ```php
  $amounts = $validated['amount'] ?? [];
  $totalExpenses = array_sum($amounts);  // Assumes all values are numeric!
  ```
  PHP's `array_sum()` treats non-numeric strings as 0, silently dropping invalid values
- **Database constraint impact:** Silent data corruption if amounts contain non-numeric values
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

**Violation 18: Calculated Fields Stored Per-Row**
- **Operation:** Create/Update
- **Fields involved:** `total_expenses`, `amount_requested`
- **Frontend behavior:** JavaScript calculates, submits once
- **Backend assumption:**
  ```php
  for ($i = 0; $i < count($particulars); $i++) {
      ProjectIAHBudgetDetails::create([
          'total_expenses' => $totalExpenses,  // SAME for ALL rows
          'family_contribution' => $familyContribution,  // SAME for ALL rows
          'amount_requested' => $totalExpenses - $familyContribution,  // SAME for ALL rows
      ]);
  }
  ```
  Summary fields replicated across every detail row
- **Database constraint impact:** Redundant storage, data inconsistency if any row updated independently
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Architectural issue:** Mixing summary and detail data in same table

---

### Model: ProjectRSTTargetGroup
**Table:** `project_RST_target_group`

**Database Schema:**
```php
$table->integer('tg_no_of_beneficiaries')->nullable();
$table->text('beneficiaries_description_problems')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\RST\TargetGroupController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `<input type="number" name="tg_no_of_beneficiaries">` | `$validated['tg_no_of_beneficiaries'] ?? null` | ✅ Valid |
| Create | Textarea for description | Direct assignment | ✅ Valid |
| Update | Same as Create | Updates existing or creates new | ✅ Valid |

#### Identified Contract Violations

**Violation 19: Inconsistent `request->input()` vs `$validated` Usage**
- **Operation:** Create
- **Fields involved:** `beneficiaries_description_problems`
- **Frontend behavior:** Standard textarea
- **Backend assumption:**
  ```php
  if ($targetGroup) {
      $targetGroup->update([
          'beneficiaries_description_problems' => $validated['beneficiaries_description_problems'] ?? null,
      ]);
  } else {
      ProjectRSTTargetGroup::create([
          'beneficiaries_description_problems' => $request->input('beneficiaries_description_problems'),  // Different!
      ]);
  }
  ```
  Update path uses `$validated`, create path uses `$request->input()` directly
- **Database constraint impact:** None, but bypasses any validation on create path
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Code smell:** Inconsistent data access patterns

---

### Model: ProjectLDPTargetGroup
**Table:** `project_LDP_target_group`

**Database Schema:**
```php
$table->string('L_beneficiary_name')->nullable();
$table->string('L_family_situation')->nullable();
$table->string('L_nature_of_livelihood')->nullable();
$table->integer('L_amount_requested')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\LDP\TargetGroupController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Create | `L_beneficiary_name[]`, `L_family_situation[]`, etc. | Parallel array iteration | ⚠️ **RISK** |
| Create | N/A | Complex null check for row validity | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 20: Complex "Any Field Filled" Row Validation**
- **Operation:** Create/Update
- **Fields involved:** All target group fields
- **Frontend behavior:** Multiple rows with 4 fields each
- **Backend assumption:**
  ```php
  foreach ($beneficiaryNames as $index => $name) {
      // Skip if all fields are null
      if (!is_null($name) || !is_null($validated['L_family_situation'][$index] ?? null) ||
          !is_null($validated['L_nature_of_livelihood'][$index] ?? null) || 
          !is_null($validated['L_amount_requested'][$index] ?? null)) {
          // Create row
      }
  }
  ```
  Creates row if ANY field has value, even empty string (which is not null)
- **Database constraint impact:** Partially filled rows saved; empty strings treated as valid
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Empty string `""` passes `!is_null()` check, creating rows with minimal data

**Violation 21: Integer Column with String Nullable Check**
- **Operation:** Create
- **Fields involved:** `L_amount_requested`
- **Frontend behavior:** Number input
- **Backend assumption:**
  ```php
  'L_amount_requested' => $validated['L_amount_requested'][$index] ?? null,
  ```
  Assigns directly to integer column
- **Database constraint impact:**
  - Empty string from form becomes null after Eloquent casting
  - Non-numeric string would fail at database level
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

## Service Layer Contract Analysis

### Service: BudgetSyncService

**Location:** `App\Services\Budget\BudgetSyncService`

**Purpose:** Synchronize type-specific budget data to project-level fields

#### Service Contract Matrix
| Method | Input Expectations | Output | Contract Status |
|--------|-------------------|--------|-----------------|
| `syncFromTypeSave(Project)` | Project with loaded relations | `bool` | ✅ Valid |
| `syncBeforeApproval(Project)` | Project with status `forwarded_to_coordinator` | `bool` | ✅ Valid |

#### Identified Contract Violations

**Violation 22: Relation Loading Assumption**
- **Operation:** `syncFromTypeSave()`, `syncBeforeApproval()`
- **Fields involved:** Type-specific relations (iiesExpenses, budgets, etc.)
- **Caller behavior:** May or may not have loaded relations
- **Service assumption:**
  ```php
  $resolved = $this->resolver->resolve($project, false);
  ```
  Resolver internally calls `$project->loadMissing($relations)` – handles missing relations
- **Database constraint impact:** None, but multiple queries if relations not pre-loaded
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ✅ **Well-Designed** – Service defensively loads missing relations

**Violation 23: Feature Flag Check Without Clear Error**
- **Operation:** All sync methods
- **Fields involved:** N/A
- **Caller behavior:** Calls sync expecting data to be synced
- **Service assumption:**
  ```php
  if (!BudgetSyncGuard::canSyncOnTypeSave($project)) {
      BudgetAuditLogger::logGuardRejection(...);
      return false;  // Silent failure!
  }
  ```
  Returns `false` without exception; caller may not check return value
- **Database constraint impact:** None, but data inconsistency if caller ignores return
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Callers like `IIESExpensesController::store()` don't check return value:
  ```php
  app(BudgetSyncService::class)->syncFromTypeSave($project);  // Return ignored!
  ```

---

### Service: ProjectFundFieldsResolver

**Location:** `App\Services\Budget\ProjectFundFieldsResolver`

**Purpose:** Resolve canonical budget values from type-specific sources

#### Service Contract Matrix
| Method | Input Expectations | Output | Contract Status |
|--------|-------------------|--------|-----------------|
| `resolve(Project, bool)` | Project with any relations | `array` of 5 fund fields | ✅ Valid |
| `resolveIIES(Project)` | Project with iiesExpenses relation | `array` | ⚠️ **RISK** |
| `resolveIES(Project)` | Project with iesExpenses relation | `array` | ⚠️ **RISK** |

#### Identified Contract Violations

**Violation 24: First() on HasMany Without Null Check**
- **Operation:** `resolveIES()`
- **Fields involved:** IES expenses data
- **Caller behavior:** Calls with project that may have empty collection
- **Service assumption:**
  ```php
  protected function resolveIES(Project $project): array
  {
      $expenses = $project->iesExpenses->first();  // Could be null if collection empty!
      if (!$expenses) {
          return $this->fallbackFromProject($project);
      }
      // ...
  }
  ```
  Actually handles null correctly
- **Database constraint impact:** None
- **Phase classification:** N/A
- **Status:** ✅ **Correct** – Properly handles null case

**Violation 25: Implicit Float Casting on Nullable String Columns**
- **Operation:** All resolve methods
- **Fields involved:** All budget/expense fields
- **Service assumption:**
  ```php
  $overall = (float) ($expenses->iies_total_expenses ?? 0);
  ```
  Casts potentially null/empty string to float
- **Database constraint impact:** None (safe casting)
- **Phase classification:** N/A
- **Status:** ✅ **Correct** – `(float) ""` returns 0.0 in PHP

---

## Controller Pattern Analysis

### Pattern: Delete-Then-Insert for Updates

**Controllers Using This Pattern:**
- `ILPBudgetController::store()` / `update()`
- `IAHBudgetDetailsController::store()` / `update()`
- `LDPTargetGroupController::store()` / `update()`
- `IESExpensesController::store()` / `update()`

**Implementation:**
```php
public function store(FormRequest $request, $projectId)
{
    DB::beginTransaction();
    try {
        // 1. Delete existing records
        ProjectILPBudget::where('project_id', $projectId)->delete();
        
        // 2. Insert new records from form
        foreach ($budgetDescs as $index => $description) {
            ProjectILPBudget::create([...]);
        }
        
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
    }
}
```

#### Identified Contract Issues

**Violation 26: Referential Integrity with Related Records**
- **Operation:** Update
- **Controllers:** All using delete-then-insert
- **Frontend behavior:** User modifies some rows, adds new ones
- **Backend assumption:** Safe to delete all and recreate
- **Database constraint impact:**
  - If related records reference these IDs, delete may fail
  - If no foreign keys, orphaned references possible
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Auto-generated IDs change on every save, breaking external references

**Violation 27: Race Condition on Concurrent Edits**
- **Operation:** Update from two users simultaneously
- **Controllers:** All using delete-then-insert
- **Frontend behavior:** Two users edit same project
- **Backend assumption:** Transaction handles concurrency
- **Database constraint impact:**
  - User A: Reads 5 rows → User B: Reads 5 rows
  - User A: Deletes 5, inserts 3 (saves) → User B: Deletes 3 (A's data!), inserts 4
  - Result: User A's data lost
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** No optimistic locking or version checking

---

### Pattern: Unified Update Through Store

**Controllers Using This Pattern:**
```php
public function update(FormRequest $request, $projectId)
{
    return $this->store($request, $projectId);
}
```

**Controllers:**
- `IIESExpensesController`
- `IESExpensesController`
- `RST\TargetGroupController`
- `LDP\TargetGroupController`
- Many others

#### Identified Contract Issues

**Violation 28: Store vs Update Semantic Difference Lost**
- **Operation:** Update
- **Controllers:** All using store() for update()
- **Frontend behavior:** Edit form submitted to update route
- **Backend assumption:** Create and update are identical operations
- **Database constraint impact:** None directly
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** 
  - Create should validate project exists
  - Update should validate record exists
  - Both are conflated, allowing "update" to create if nothing exists

---

## View Pattern Analysis

### Pattern: JavaScript Calculated Fields

**Views Using This Pattern:**
- `Edit/IIES/estimated_expenses.blade.php`
- `Edit/ILP/budget.blade.php`
- `Edit/IAH/budget_details.blade.php`
- `Edit/budget.blade.php` (Development Projects)

**Implementation:**
```javascript
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.amount-field').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    totalField.value = total.toFixed(2);
    
    // Also update parent form field
    if (overallBudgetField) {
        overallBudgetField.value = total.toFixed(2);
    }
}
```

#### Identified Contract Issues

**Violation 29: Cross-Form Field Updates**
- **Operation:** User input in type-specific section
- **Views:** ILP, IAH budget sections
- **Frontend behavior:**
  ```javascript
  // In ILP budget:
  if (overallBudgetField) {
      overallBudgetField.value = amountRequested.toFixed(2);
  }
  ```
  Updates `#overall_project_budget` in parent form
- **Backend assumption:** `overall_project_budget` comes from General Info section
- **Database constraint impact:** None (both are submitted)
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Two sources of truth for same field; last write wins

**Violation 30: Readonly Fields Submitted But Recalculated Server-Side (Sometimes)**
- **Operation:** Form submission
- **Views:** Various budget forms
- **Frontend behavior:**
  ```blade
  <input type="number" name="total_expenses" readonly>
  ```
  Readonly but still submitted
- **Backend assumption:** Mixed – some controllers trust submitted value, others recalculate
  
  IAH Controller recalculates:
  ```php
  $totalExpenses = array_sum($amounts);  // Server-side calculation
  ```
  
  IIES Controller trusts frontend:
  ```php
  $projectExpenses->iies_total_expenses = $validated['iies_total_expenses'] ?? 0;
  ```
- **Database constraint impact:** Inconsistent behavior across project types
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

## Dynamic Row Pattern Analysis

### Pattern: Array-Based Multi-Row Inputs

**Forms Using This Pattern:**
- Budget items (all project types)
- Target groups (LDP, RST)
- Expense details (IES, IIES)
- Beneficiaries (IGE)

**Frontend Implementation:**
```blade
<tbody id="items-table">
    @foreach ($items as $index => $item)
    <tr>
        <td><input name="field1[{{ $index }}]" value="{{ $item->field1 }}"></td>
        <td><input name="field2[{{ $index }}]" value="{{ $item->field2 }}"></td>
    </tr>
    @endforeach
</tbody>
<button onclick="addRow()">Add More</button>
```

```javascript
function addRow() {
    const row = `<tr>
        <td><input name="field1[${nextIndex}]"></td>
        <td><input name="field2[${nextIndex}]"></td>
    </tr>`;
    table.insertAdjacentHTML('beforeend', row);
    nextIndex++;
}
```

#### Identified Contract Issues

**Violation 31: Index Gaps After Row Removal**
- **Operation:** User removes middle row then saves
- **Views:** All dynamic row forms
- **Frontend behavior:**
  - Initial: indices 0, 1, 2
  - Remove row 1
  - After removal: indices 0, 2 (gap)
  - New row added: indices 0, 2, 3
- **Backend assumption:**
  ```php
  foreach ($fieldArray as $index => $value) {
      $otherValue = $otherArray[$index] ?? null;  // Works with PHP foreach
  }
  ```
  PHP foreach handles sparse arrays correctly
- **Database constraint impact:** None (PHP handles sparse arrays)
- **Phase classification:** N/A
- **Status:** ✅ **Works correctly** but could confuse debugging

**Violation 32: Empty Rows Submitted**
- **Operation:** User adds row, doesn't fill it, saves
- **Views:** All dynamic row forms
- **Frontend behavior:** Empty inputs submitted as empty strings
- **Backend assumption:**
  ```php
  if (!empty($particulars[$i]) && !empty($amounts[$i])) {
      // Only create if both filled
  }
  ```
  Controllers check for non-empty before creating
- **Database constraint impact:** None (properly handled)
- **Phase classification:** N/A
- **Status:** ✅ **Correctly handled** in most controllers

---

## Cross-Cutting Service Patterns

### Pattern: BudgetSyncService Called After Type-Specific Save

**Controllers Using This Pattern:**
```php
// After saving type-specific data:
$project = Project::where('project_id', $projectId)->first();
if ($project) {
    app(BudgetSyncService::class)->syncFromTypeSave($project);
}
```

**Controllers:**
- `IIESExpensesController::store()`
- `IESExpensesController::store()`
- `ILP\BudgetController::store()` / `update()`
- `IAH\IAHBudgetDetailsController::store()` / `update()`

#### Identified Contract Issues

**Violation 33: Sync Called Without Loaded Relations**
- **Operation:** Post-save sync
- **Controllers:** All using BudgetSyncService
- **Controller behavior:**
  ```php
  $project = Project::where('project_id', $projectId)->first();  // No eager loading!
  app(BudgetSyncService::class)->syncFromTypeSave($project);
  ```
- **Service assumption:** Will load missing relations via `loadMissing()`
- **Database constraint impact:** Extra queries; N+1 potential
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Performance risk:** Resolver triggers additional queries for each sync

**Violation 34: Return Value Ignored**
- **Operation:** All sync calls
- **Controllers:** All using BudgetSyncService
- **Controller behavior:**
  ```php
  app(BudgetSyncService::class)->syncFromTypeSave($project);  // No return check!
  return response()->json(['message' => 'Saved successfully.'], 200);  // Always success!
  ```
  Success response sent regardless of sync result
- **Database constraint impact:** None, but misleading success message
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**

---

## Phase-wise Issue Summary (Extended)

### Phase 1 – Critical Data Integrity
*No new critical issues in extended analysis*

### Phase 2 – Input Normalization Gaps

| # | Issue | Models Affected | Risk Level |
|---|-------|-----------------|------------|
| 13 | String storage for numeric values | ProjectIESExpenses | Medium |
| 14 | Parallel array iteration without bounds check | IES, IIES expenses | Medium |
| 15 | Single value applied to multiple rows | ILP Budget | Low |
| 17 | array_sum() on potentially non-numeric array | IAH Budget | Medium |
| 18 | Calculated fields stored per-row (redundant) | IAH Budget | Low |
| 19 | Inconsistent request->input() vs $validated | RST TargetGroup | Low |
| 20 | Complex "any field filled" validation | LDP TargetGroup | Medium |
| 21 | Integer column with string handling | LDP TargetGroup | Low |
| 29 | Cross-form field updates (two sources of truth) | ILP, IAH Budget views | Medium |
| 30 | Inconsistent readonly field handling | All budget forms | Medium |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Controllers Affected | Risk Level |
|---|-------|---------------------|------------|
| 16 | Show returns first row values for summary | ILP BudgetController | Medium |
| 23 | Feature flag check returns false silently | BudgetSyncService | Low |
| 26 | Delete-then-insert breaks referential integrity | All multi-row controllers | High |
| 27 | Race condition on concurrent edits | All multi-row controllers | High |
| 28 | Store vs Update semantics conflated | Many controllers | Low |
| 33 | Sync called without loaded relations | All sync callers | Low |
| 34 | Sync return value ignored | All sync callers | Low |

### Phase 4 – Secondary Paths
*See primary audit document*

---

## Architectural Observations (Extended)

### Data Model Anti-Patterns

1. **Summary Data in Detail Tables**
   - IAH Budget: `total_expenses`, `amount_requested` stored on every detail row
   - ILP Budget: `beneficiary_contribution`, `amount_requested` duplicated
   - Should be: Separate summary record or computed on read

2. **String Columns for Numeric Data**
   - IES Expenses: All amount fields are `string` type
   - Allows any text value, no database-level validation
   - Should be: `decimal` or `integer` types

3. **Mixed Array Semantics**
   - Some forms use sequential indices: `name[0]`, `name[1]`
   - Some forms use associative keys: `phases[0][budget][0][field]`
   - Backend handles both, but error-prone

### Controller Anti-Patterns

1. **Delete-All-Then-Insert for Updates**
   - Loses auto-generated IDs
   - Potential referential integrity issues
   - Race conditions on concurrent edits
   - Should be: Update existing, delete removed, insert new

2. **Store() as Update()**
   - Loses semantic distinction
   - No validation that record exists for update
   - Allows "update" to create records

3. **Inconsistent Return Types**
   - Some controllers return JSON: `return response()->json([...])`
   - Some return models: `return $model`
   - Some return arrays: `return ['data' => $data]`
   - Caller must know which to expect

### Service Layer Strengths

1. **BudgetSyncService**
   - Proper guard checks before operations
   - Audit logging for all actions
   - Feature flags for gradual rollout
   - Defensive relation loading

2. **ProjectFundFieldsResolver**
   - Single source of truth for budget calculations
   - Handles all project types consistently
   - Proper null handling throughout

---

## Summary Statistics

| Category | Count |
|----------|-------|
| Models analyzed (extended) | 6 |
| Controllers analyzed | 8 |
| Services analyzed | 2 |
| New violations identified | 22 |
| Phase 1 violations | 0 |
| Phase 2 violations | 10 |
| Phase 3 violations | 9 |
| Phase 4 violations | 0 |
| Well-designed patterns | 3 |

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
*Extended contract audit performed by: Senior Laravel Architect*
