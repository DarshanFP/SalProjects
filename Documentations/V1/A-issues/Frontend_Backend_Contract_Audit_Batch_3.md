# Frontend–Backend Contract Audit – Batch 3

## Purpose

This document continues the contract audit series, focusing on Form Request validation patterns, middleware authorization, personal information models, and complex multi-field forms. This batch examines how validation rules align with database constraints and frontend behaviors.

---

## Form Request Validation Analysis

### StoreProjectRequest

**Location:** `App\Http\Requests\Projects\StoreProjectRequest`

**Purpose:** Main project creation/update validation

#### Validation Rules Analysis

```php
public function rules(): array
{
    $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';

    return [
        'project_type' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
        'project_title' => 'nullable|string|max:255',
        'overall_project_budget' => 'nullable|numeric|min:0',  // No max!
        'amount_forwarded' => [
            'nullable',
            'numeric',
            'min:0',
            function ($attribute, $value, $fail) {
                // Cross-field validation
                $overallBudget = $this->input('overall_project_budget', 0);
                $localContribution = (float) $this->input('local_contribution', 0);
                $combined = ((float) $value) + $localContribution;
                if ($combined > 0 && $overallBudget > 0 && $combined > $overallBudget) {
                    $fail('Sum cannot exceed overall project budget');
                }
            },
        ],
        // ...
    ];
}
```

#### Identified Contract Violations

**Violation 55: Draft Mode Bypasses Required Fields**
- **Operation:** Create project in draft mode
- **Fields involved:** `project_type` and potentially others
- **Frontend behavior:** User clicks "Save as Draft"
- **Validation assumption:**
  ```php
  $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';
  'project_type' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
  ```
  Only `project_type` has conditional validation; other fields always nullable
- **Database constraint impact:** Projects saved without `project_type` may break type-specific logic
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Draft projects with missing data enter normal workflow

**Violation 56: No Maximum Bound on Numeric Fields**
- **Operation:** Create/Update project
- **Fields involved:** `overall_project_budget`, `total_amount_sanctioned`, `amount_forwarded`
- **Validation rules:**
  ```php
  'overall_project_budget' => 'nullable|numeric|min:0',  // No max constraint!
  ```
- **Database constraint:** Column is `DECIMAL(10,2)` with max 99,999,999.99
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Risk:** User enters 100,000,000+ → Database error not caught by validation

**Violation 57: Cross-Field Validation Only for amount_forwarded**
- **Operation:** Create/Update
- **Fields involved:** Budget fields
- **Validation rules:**
  ```php
  'amount_forwarded' => [
      // Custom function validates: amount_forwarded + local_contribution <= overall_budget
  ],
  'local_contribution' => [
      'nullable',
      'numeric',
      'min:0',
      // No cross-field validation here!
  ],
  ```
  Only `amount_forwarded` has cross-field check; `local_contribution` doesn't trigger same validation
- **Database constraint impact:** User can set `local_contribution` > `overall_project_budget`
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Inconsistent validation based on field edit order

**Violation 58: prepareForValidation Only Handles save_as_draft**
- **Operation:** Request preparation
- **Validation rules:**
  ```php
  protected function prepareForValidation(): void
  {
      if ($this->has('save_as_draft')) {
          $this->merge([
              'save_as_draft' => filter_var($this->save_as_draft, FILTER_VALIDATE_BOOLEAN)
          ]);
      }
  }
  ```
  No other field normalization (empty strings, whitespace trimming, etc.)
- **Database constraint impact:** Empty strings pass validation but may cause issues
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

### StoreIIESExpensesRequest

**Location:** `App\Http\Requests\Projects\IIES\StoreIIESExpensesRequest`

**Purpose:** IIES expenses validation

#### Validation Rules Analysis

```php
public function rules(): array
{
    return [
        'iies_total_expenses' => 'nullable|numeric|min:0',
        'iies_expected_scholarship_govt' => 'nullable|numeric|min:0',
        'iies_support_other_sources' => 'nullable|numeric|min:0',
        'iies_beneficiary_contribution' => 'nullable|numeric|min:0',
        'iies_balance_requested' => 'nullable|numeric|min:0',
        'iies_particulars' => 'array',
        'iies_particulars.*' => 'nullable|string|max:255',
        'iies_amounts' => 'array',
        'iies_amounts.*' => 'nullable|numeric|min:0',
    ];
}
```

#### Identified Contract Violations

**Violation 59: Validation Doesn't Match Controller Field Names**
- **Operation:** Store IIES expenses
- **Fields involved:** All expense fields
- **Form Request rules:** Uses `iies_particulars`, `iies_amounts`
- **Controller usage:**
  ```php
  // IIESExpensesController::store()
  $particulars = $validated['iies_particulars'] ?? [];
  $amounts = $validated['iies_amounts'] ?? [];
  ```
  But earlier in same file:
  ```php
  // Blade might send different field names
  $projectExpenses->iies_total_expenses = $validated['iies_total_expenses'] ?? 0;
  ```
- **Status:** ✅ **Correct** – Field names consistent between request and controller

**Violation 60: No Max Constraint for Array Length**
- **Operation:** Store expenses with many detail rows
- **Validation rules:**
  ```php
  'iies_particulars' => 'array',  // No max:N constraint
  'iies_particulars.*' => 'nullable|string|max:255',
  ```
  User could submit 1000+ array items
- **Database constraint impact:** Performance issues, potential DoS
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Malicious or erroneous bulk submissions

---

### StoreCCIStatisticsRequest

**Location:** `App\Http\Requests\Projects\CCI\StoreCCIStatisticsRequest`

**Purpose:** CCI statistics validation

#### Validation Rules Analysis

```php
public function rules(): array
{
    return [
        'total_children_previous_year' => 'nullable|integer|min:0',
        'total_children_current_year' => 'nullable|integer|min:0',
        'reintegrated_children_previous_year' => 'nullable|integer|min:0',
        // ... 14 similar fields
    ];
}
```

#### Identified Contract Violations

**Violation 61: No Logical Validation Across Related Fields**
- **Operation:** Store CCI statistics
- **Fields involved:** All statistics fields
- **Validation rules:** Each field validated independently
- **Business logic gap:**
  - `reintegrated_children` should be ≤ `total_children`
  - `shifted_children` + `pursuing_higher_studies` + `settled_children` + `working_children` should ≤ `total_children`
  - No validation enforces these relationships
- **Database constraint impact:** None (database allows any integers)
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Data integrity issues: more children leaving than exist

**Violation 62: Missing Fields from Production Error Log**
- **Operation:** Store CCI statistics
- **Production error:** `Incorrect integer value: '-'`
- **Validation rules:** All fields have `integer` rule
- **Issue:** Validation should catch `-` as invalid, but production error occurred
- **Possible cause:**
  1. Controller uses `$request->all()` instead of `$request->validated()`
  2. Form Request not being applied to route
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **CRITICAL** – Validation exists but not enforced

---

## Middleware Authorization Analysis

### Role Middleware

**Location:** `App\Http\Middleware\Role`

**Implementation:**
```php
public function handle(Request $request, Closure $next, ...$roles): Response
{
    $user = $request->user();
    $userRole = $user->role;  // Assumes user always has 'role' property

    // Parse roles - handle comma-separated and individual
    $allowedRoles = [];
    foreach ($roles as $role) {
        if (strpos($role, ',') !== false) {
            $allowedRoles = array_merge($allowedRoles, explode(',', $role));
        } else {
            $allowedRoles[] = $role;
        }
    }

    if (!in_array($userRole, $allowedRoles)) {
        $redirectUrl = $this->getDashboardUrl($userRole);
        return redirect($redirectUrl);
    }

    return $next($request);
}
```

#### Identified Contract Violations

**Violation 63: No Null Check on User**
- **Operation:** Route protection
- **Middleware assumption:**
  ```php
  $user = $request->user();
  $userRole = $user->role;  // If $user is null, this crashes!
  ```
  Assumes `auth` middleware always runs first
- **Impact:** 500 error if unauthenticated user hits protected route without `auth` middleware
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Order-dependent middleware stack

**Violation 64: Redirect Instead of 403 for Unauthorized Access**
- **Operation:** Access denied
- **Middleware behavior:**
  ```php
  if (!in_array($userRole, $allowedRoles)) {
      return redirect($this->getDashboardUrl($userRole));
  }
  ```
  Silently redirects instead of showing access denied
- **Impact:** User confusion; AJAX requests receive redirect instead of error
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** API routes with this middleware return 302, not 403

**Violation 65: Applicant Mapped to Executor Dashboard**
- **Operation:** Role-based routing
- **Implementation:**
  ```php
  'applicant' => '/executor/dashboard', // Applicants get same access as executors
  ```
  Comment documents intentional behavior, but may not be desired
- **Impact:** None if intentional
- **Phase classification:** N/A
- **Status:** ⚠️ **Documented Decision** – Should be verified

---

### MultiRole Middleware

**Location:** `App\Http\Middleware\MultiRole`

**Implementation:**
```php
public function handle(Request $request, Closure $next): Response
{
    return $next($request);  // Does nothing!
}
```

#### Identified Contract Violations

**Violation 66: Empty Middleware**
- **Operation:** N/A
- **Implementation:** Middleware passes through without any logic
- **Impact:** If routes use `MultiRole` middleware, no authorization occurs
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **Dead Code** – Either incomplete or should be removed

---

## Model-by-Model Contract Review (Batch 3)

### Model: ProjectILPPersonalInfo
**Table:** `project_ILP_personal_info`

**Database Schema (from migration):**
```php
$table->string('name')->nullable();
$table->integer('age')->nullable();
$table->string('gender')->nullable();
$table->date('dob')->nullable();
$table->string('email')->nullable();
$table->boolean('small_business_status')->default(false);
$table->decimal('monthly_income', 10, 2)->nullable();
```

**Controller:** `App\Http\Controllers\Projects\ILP\PersonalInfoController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Store | Various form inputs | `updateOrCreate()` pattern | ✅ Valid |
| Store | `small_business_status` checkbox | `(int) ($validated['small_business_status'] ?? 0)` | ⚠️ **RISK** |
| Store | `spouse_name` (conditional) | Only saved if `marital_status == 'Married'` | ✅ Valid |

#### Identified Contract Violations

**Violation 67: Boolean Field Stored as Integer**
- **Operation:** Store personal info
- **Field:** `small_business_status`
- **Database schema:** `boolean` with `->default(false)`
- **Controller handling:**
  ```php
  'small_business_status' => (int) ($validated['small_business_status'] ?? 0),
  ```
  Casts to int (0 or 1) instead of boolean
- **Frontend behavior:** Checkbox sends "1" when checked, nothing when unchecked
- **Impact:** Works but inconsistent typing
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Works** but should use `filter_var(..., FILTER_VALIDATE_BOOLEAN)`

**Violation 68: Conditional Field Clearing Based on Related Field**
- **Operation:** Store personal info
- **Fields:** `spouse_name`, `marital_status`
- **Controller logic:**
  ```php
  'spouse_name' => ($validated['marital_status'] ?? '') == 'Married' 
      ? ($validated['spouse_name'] ?? null) 
      : null,
  ```
  Clears `spouse_name` if `marital_status` is not "Married"
- **Database constraint impact:** None
- **Phase classification:** N/A
- **Status:** ✅ **Good Practice** – Prevents orphaned conditional data

**Violation 69: Date Field Without Format Validation**
- **Operation:** Store personal info
- **Field:** `dob`
- **Database schema:** `date` type
- **Controller handling:** `'dob' => $validated['dob'] ?? null`
- **Form Request validation:** Not shown (likely `nullable|date`)
- **Frontend behavior:** Date picker or text input
- **Impact:** Various date formats may be submitted
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** `2024-01-15` vs `15/01/2024` vs `Jan 15, 2024`

---

### Model: ProjectILPRiskAnalysis
**Table:** `project_ILP_risk_analysis`

**Database Schema:**
```php
$table->string('identified_risks')->nullable();
$table->string('mitigation_measures')->nullable();
$table->string('business_sustainability')->nullable();
$table->string('expected_profits')->nullable();
```

**Controller:** `App\Http\Controllers\Projects\ILP\RiskAnalysisController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Store | Textarea content | Deletes existing, creates new | ⚠️ **RISK** |
| Update | Same textarea | Uses `updateOrCreate()` | ✅ Valid |
| Show | N/A | Returns array with empty defaults | ✅ Valid |

#### Identified Contract Violations

**Violation 70: Store Deletes First, Update Uses UpdateOrCreate**
- **Operation:** Store vs Update
- **Controller patterns:**
  ```php
  // In store():
  ProjectILPRiskAnalysis::where('project_id', $projectId)->delete();
  ProjectILPRiskAnalysis::create([...]);
  
  // In update():
  ProjectILPRiskAnalysis::updateOrCreate(
      ['project_id' => $projectId],
      [...data...]
  );
  ```
  Different patterns for same table; store loses auto-generated ID
- **Database constraint impact:** ID changes on every store
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** External references to `ILP_risk_id` broken

**Violation 71: Update Method Doesn't Use Null Coalescing**
- **Operation:** Update risk analysis
- **Controller code:**
  ```php
  // In update():
  [
      'identified_risks' => $validatedData['identified_risks'],  // No ?? null
      'mitigation_measures' => $validatedData['mitigation_measures'],  // No ?? null
      // ...
  ]
  ```
  Direct array access without null fallback
- **Impact:** `Undefined array key` if field not submitted
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ❌ **BUG** – Missing defensive handling in update()

---

### Model: ProjectCCIAgeProfile
**Table:** `project_CCI_age_profile`

**Database Schema (24 nullable integer/string columns):**
```php
$table->integer('education_below_5_bridge_course_prev_year')->nullable();
$table->integer('education_below_5_bridge_course_current_year')->nullable();
// ... 22 more fields
```

**Controller:** `App\Http\Controllers\Projects\CCI\AgeProfileController`

#### CRUD Contract Matrix
| Operation | Frontend Inputs | Backend Expectations | Contract Status |
|----------|-----------------|----------------------|-----------------|
| Store | 24 input fields | `fill($validated)` - mass assignment | ⚠️ **RISK** |
| Update | Same 24 fields | `updateOrCreate()` with mass fill | ⚠️ **RISK** |
| Show | N/A | Returns array with default structure | ✅ Valid |

#### Identified Contract Violations

**Violation 72: Mass Assignment with fill() on Unvalidated Data**
- **Operation:** Store age profile
- **Controller code:**
  ```php
  $validated = $request->all();  // NOT ->validated()!
  
  $ageProfile = new ProjectCCIAgeProfile();
  $ageProfile->project_id = $projectId;
  $ageProfile->fill($validated);  // Mass assigns all keys!
  $ageProfile->save();
  ```
  Uses `->all()` not `->validated()`, then mass assigns
- **Database constraint impact:** Any request field matching fillable column is saved
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Risk:** Attacker could send `id=999` or other protected fields

**Violation 73: Custom Primary Key with Inconsistent Ordering**
- **Model definition:**
  ```php
  protected $primaryKey = 'CCI_age_profile_id';
  public $incrementing = false;
  protected $keyType = 'string';
  ```
  Uses string primary key, but...
- **ID Generation:**
  ```php
  private function generateCCIAgeProfileId()
  {
      $latest = self::latest('id')->first();  // Orders by 'id', not primary key!
      $sequenceNumber = $latest ? intval(substr($latest->CCI_age_profile_id, -4)) + 1 : 1;
  }
  ```
  Queries by `id` column (auto-increment) but generates custom `CCI_age_profile_id`
- **Impact:** ID generation logic inconsistent with primary key usage
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Works** but confusing architecture

**Violation 74: Show Returns Different Type Based on Data Existence**
- **Operation:** Show age profile
- **Controller code:**
  ```php
  public function show($projectId)
  {
      $ageProfile = ProjectCCIAgeProfile::where('project_id', $projectId)->first();

      if ($ageProfile) {
          $ageProfile = $ageProfile->toArray();  // Returns array
      } else {
          $ageProfile = [  // Returns array with defaults
              'education_below_5_bridge_course_prev_year' => null,
              // ... partial list
          ];
      }
      return $ageProfile;  // Always array
  }
  ```
  Inconsistency: Returns partial default structure (doesn't include all 24 fields)
- **Impact:** Blade may access missing keys
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Risk:** `Undefined array key` in views for missing default fields

---

### Model: ProjectObjective
**Table:** `project_objectives`

**Database Schema:**
```php
$table->string('objective_id')->unique();
$table->string('project_id');
$table->text('objective')->nullable();
```

**Relationships:**
- `hasMany(ProjectResult::class)`
- `hasMany(ProjectActivity::class)`
- `hasMany(ProjectRisk::class)`

#### Model Design Analysis

**Strength: Hierarchical ID Generation**
```php
private function generateObjectiveId()
{
    $latestObjective = self::where('project_id', $this->project_id)
        ->latest('id')
        ->first();
    $sequenceNumber = $latestObjective 
        ? intval(substr($latestObjective->objective_id, -2)) + 1 
        : 1;

    return $this->project_id . '-OBJ-' . str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);
}
```
✅ **Good:** IDs include project ID prefix for easy tracing

**Violation 75: Sequence Number Extraction Assumes Fixed Format**
- **Operation:** ID generation
- **Logic:** `intval(substr($latestObjective->objective_id, -2))`
  - Assumes last 2 characters are the sequence number
  - Fails if sequence exceeds 99 (would become 3+ digits)
- **Impact:** After 99 objectives, next ID would be `...-OBJ-100`, but extraction gets `00`
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** ID collision after 99 objectives per project

---

## Controller Pattern Analysis (Batch 3)

### Pattern: $request->all() vs $request->validated()

**Controllers Using `$request->all()`:**
```php
// Common pattern across most controllers:
$validated = $request->all();  // Bypasses FormRequest validation!
```

**Controllers affected:**
- `ILPPersonalInfoController`
- `ILPRiskAnalysisController`
- `CCIAgeProfileController`
- `CCIRationaleController`
- `IGEBeneficiariesSupportedController`
- Almost all type-specific controllers

#### Identified Contract Issues

**Violation 76: Systematic Bypass of FormRequest Validation**
- **Operation:** All store/update operations
- **Pattern:**
  ```php
  public function store(FormRequest $request, $projectId)
  {
      // Uses all() instead of validated()
      $validated = $request->all();  // Variable name is misleading!
  }
  ```
  Form Requests define rules, but `->all()` ignores them
- **Impact:**
  - All validation rules in Form Requests are decorative
  - Any form data, including malicious fields, is processed
  - Misleading variable name `$validated` when data isn't validated
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **CRITICAL SYSTEMIC** – Affects entire application

**Evidence from Production Log:**
- `Incorrect integer value: '-'` for integer column
- Validation rule exists: `'nullable|integer|min:0'`
- But `$request->all()` means invalid data reaches database

---

### Pattern: Inconsistent Return Types from Show/Edit Methods

| Controller | show() Returns | edit() Returns |
|------------|----------------|----------------|
| `ILPRiskAnalysisController` | Array with empty defaults | Model or null |
| `ILPPersonalInfoController` | Model | Model or null |
| `CCIAgeProfileController` | Array | Model or null |
| `CCIRationaleController` | Model | Model (via firstOrFail) |

**Violation 77: No Consistent Contract for View Data**
- **Issue:** Same operation returns different types across controllers
- **Impact:** Views must handle multiple data types
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**

---

## Database Schema Analysis (Batch 3)

### ILP Personal Info Migration

**Schema:**
```php
$table->string('name')->nullable();
$table->integer('age')->nullable();
$table->date('dob')->nullable();
$table->string('email')->nullable();
$table->boolean('small_business_status')->default(false);
$table->decimal('monthly_income', 10, 2)->nullable();
```

**Violations:**
| Column | Type | Issue |
|--------|------|-------|
| `age` | integer | No max constraint; user could enter 999 |
| `email` | string | No email format at database level |
| `monthly_income` | decimal(10,2) | Max 99,999,999.99 not validated in FormRequest |

**Violation 78: Age Column Allows Unrealistic Values**
- **Database:** `integer()->nullable()` – allows any integer
- **Validation:** Likely `nullable|integer|min:0` but no max
- **Risk:** User enters 999 or -5 if validation bypassed
- **Phase classification:** **Phase 2 – Input Normalization Gaps**

---

## Phase-wise Issue Summary (Batch 3)

### Phase 1 – Critical Data Integrity

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 56 | No max bound on budget numeric fields | StoreProjectRequest | High |
| 62 | Validation exists but not enforced (CCI Stats) | Production evidence | Critical |
| 72 | Mass assignment with fill() on unvalidated data | CCIAgeProfileController | Critical |
| 76 | Systematic $request->all() bypasses validation | All controllers | **CRITICAL** |

### Phase 2 – Input Normalization Gaps

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 55 | Draft mode only changes project_type validation | StoreProjectRequest | Medium |
| 57 | Cross-field validation asymmetric | StoreProjectRequest | Medium |
| 58 | No prepareForValidation for data normalization | StoreProjectRequest | Low |
| 61 | No logical validation across CCI statistics | StoreCCIStatisticsRequest | Medium |
| 67 | Boolean stored as integer | ILPPersonalInfoController | Low |
| 69 | Date field without format validation | ILPPersonalInfoController | Medium |
| 71 | Update method missing null coalescing | ILPRiskAnalysisController | Medium |
| 78 | Age column allows unrealistic values | ILP Personal Info | Medium |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 60 | No max constraint for array length | StoreIIESExpensesRequest | Low |
| 63 | No null check on user in Role middleware | Role middleware | Medium |
| 64 | Redirect instead of 403 for unauthorized | Role middleware | Medium |
| 66 | Empty MultiRole middleware | MultiRole middleware | High |
| 70 | Store deletes first, Update uses updateOrCreate | ILPRiskAnalysisController | Medium |
| 73 | Custom primary key with inconsistent ordering | CCIAgeProfile | Low |
| 75 | Sequence extraction assumes fixed 2-digit format | ProjectObjective | Low |

### Phase 4 – Presentation & Secondary Paths

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 74 | Show returns partial default structure | CCIAgeProfileController | Medium |
| 77 | Inconsistent return types from show/edit | Multiple controllers | Low |

---

## Critical Finding: $request->all() Anti-Pattern

### Scope of Impact

**Pattern Location:** Every type-specific controller

**Example from IIESExpensesController:**
```php
// Comment says "Use all() to get all form data"
$validated = $request->all();  // Misleading variable name!
```

**Why This Happened:**
Comments in controllers explain the rationale:
> "Use all() to get all form data including fields not in StoreProjectRequest validation rules"

The architecture has two layers of Form Requests:
1. `StoreProjectRequest` / `UpdateProjectRequest` – Main project validation
2. Type-specific requests like `StoreIIESExpensesRequest` – Type validation

But controllers bypass type-specific validation entirely.

### Recommended Audit Actions

1. **Catalog all `$request->all()` usage** (at least 30+ controllers)
2. **Map each to its Form Request** to identify which rules are being ignored
3. **Priority fix:** Controllers with numeric/date fields reaching database

### Evidence Chain

1. Form Request exists: `StoreCCIStatisticsRequest`
2. Rules defined: `'total_children_previous_year' => 'nullable|integer|min:0'`
3. Controller uses: `$validated = $request->all()`
4. Production error: `Incorrect integer value: '-'`
5. Conclusion: Validation rules exist but are never applied

---

## Strengths Identified (Batch 3)

### Well-Designed Patterns

**1. Conditional Field Clearing**
```php
'spouse_name' => ($validated['marital_status'] ?? '') == 'Married' 
    ? ($validated['spouse_name'] ?? null) 
    : null,
```
✅ Prevents orphaned data when parent condition changes

**2. Cross-Field Validation Closure**
```php
'amount_forwarded' => [
    'nullable',
    'numeric',
    'min:0',
    function ($attribute, $value, $fail) {
        // Complex cross-field business rule
    },
],
```
✅ Proper place for business logic validation

**3. Hierarchical ID Generation**
```php
return $this->project_id . '-OBJ-' . str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);
```
✅ IDs trace back to parent project

**4. Role Middleware Logging**
```php
Log::info('Role middleware - Checking access', [
    'user_id' => $user->id,
    'user_role' => $userRole,
    'allowed_roles' => $allowedRoles,
    'has_access' => in_array($userRole, $allowedRoles),
]);
```
✅ Comprehensive audit trail for authorization decisions

---

## Summary Statistics (Batch 3)

| Category | Count |
|----------|-------|
| Form Requests analyzed | 3 |
| Middleware analyzed | 2 |
| Models analyzed | 4 |
| Controllers analyzed | 4 |
| Migrations reviewed | 1 |
| New violations identified | 24 (55-78) |
| Phase 1 violations | 4 |
| Phase 2 violations | 9 |
| Phase 3 violations | 7 |
| Phase 4 violations | 2 |
| Systemic critical issues | 1 ($request->all()) |

---

## Cumulative Statistics (All Batches)

| Batch | Models | Controllers | Services | Violations | Critical |
|-------|--------|-------------|----------|------------|----------|
| Primary | 7 | 5 | 0 | 13 | 4 |
| Extended | 6 | 8 | 2 | 22 | 0 |
| Batch 2 | 6 | 5 | 1 | 20 | 3 |
| Batch 3 | 4 | 4 | 0 | 24 | 4 |
| **Total** | **23** | **22** | **3** | **79** | **11** |

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers
- ❌ Do not modify middleware
- ❌ Do not propose solutions

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Batch 3 contract audit performed by: Senior Laravel Architect*
