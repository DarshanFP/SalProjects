# Frontend–Backend Contract Audit – Batch 4

## Purpose

This document continues the contract audit series, focusing on the reporting system, PDF export functionality, notification services, and the logical framework model hierarchy. This batch examines complex nested data structures, report validation patterns, and inter-entity relationships.

---

## Reporting System Analysis

### StoreMonthlyReportRequest

**Location:** `App\Http\Requests\Reports\Monthly\StoreMonthlyReportRequest`

**Purpose:** Comprehensive validation for monthly development project reports

#### Key Design Features

**Strength 1: Role-Based Authorization**
```php
public function authorize(): bool
{
    return auth()->check() && in_array(auth()->user()->role, ['executor', 'applicant']);
}
```
✅ **Good Practice:** Authorization at FormRequest level, not just middleware

**Strength 2: Draft Mode Conditional Validation**
```php
public function rules(): array
{
    $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';

    return [
        'report_month' => $isDraft 
            ? 'nullable|integer|between:1,12' 
            : 'required|integer|between:1,12',
        'particulars' => $isDraft 
            ? 'nullable|array' 
            : 'required|array',
    ];
}
```
✅ **Good Practice:** Relaxed validation for drafts, strict for submission

**Strength 3: withValidator() for Complex Cross-Field Validation**
```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($isDraft) return;
        
        // Future date validation
        $reportDate = Carbon::create($reportYear, $reportMonth, 1);
        if ($reportDate->isAfter($nextMonth)) {
            $validator->errors()->add('report_month', 'Cannot be more than one month in future.');
        }
    });
}
```
✅ **Best Practice:** Complex date logic in `withValidator()` hook

#### Identified Contract Violations

**Violation 79: Deeply Nested Array Validation**
- **Operation:** Store report with objectives and activities
- **Validation rules:**
  ```php
  'summary_activities' => 'nullable|array',
  'summary_activities.*' => 'nullable|array',
  'summary_activities.*.*' => 'nullable|array',
  'summary_activities.*.*.*' => 'nullable|string',  // 4 levels deep!
  ```
  Four levels of nesting for activity summaries
- **Frontend contract:** Multi-dimensional form arrays
- **Impact:** Extremely complex form structure; easy to mismatch array indices
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** One misaligned index breaks entire activity tree

**Violation 80: No Maximum Array Length Constraints**
- **Operation:** Store report with photos
- **Validation rules:**
  ```php
  'photos' => 'nullable|array',  // No max
  'photos.*' => 'nullable|array',  // No max
  'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
  ```
  Allows unlimited nested photo arrays
- **Impact:** 
  - Memory exhaustion on large submissions
  - DoS potential
  - Storage quota issues
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** User uploads 100+ photos per activity

**Violation 81: balance_amount Allows Negative Values**
- **Operation:** Store account details
- **Validation rules:**
  ```php
  'balance_amount' => 'nullable|array',
  'balance_amount.*' => 'nullable|numeric',  // No min:0!
  ```
  Other amount fields have `min:0`, but balance doesn't
- **Impact:** Intentional (balance can be negative) or oversight?
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Possible Intentional** – Verify with business rules

---

### ReportController (Monthly)

**Location:** `App\Http\Controllers\Reports\Monthly\ReportController`

**Size:** 2000+ lines (very large controller)

#### Controller Design Analysis

**Strength 1: Proper Use of $request->validated()**
```php
public function store(StoreMonthlyReportRequest $request)
{
    // ...
    $validatedData = $request->validated();  // Correct!
    
    $project_id = $validatedData['project_id'];
    $report_id = $this->generateReportId($project_id);
    // ...
}
```
✅ **Correct:** Unlike project controllers, ReportController uses `->validated()`

**Strength 2: Comprehensive Transaction Handling**
```php
DB::beginTransaction();
try {
    $report = $this->createReport($validatedData, $report_id);
    $this->storeObjectivesAndActivities($request, $report_id, $report);
    $this->handleAccountDetails($request, $report_id, $project_id);
    // ...
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Failed to create report', ['error' => $e->getMessage()]);
}
```
✅ **Best Practice:** All related operations in single transaction

#### Identified Contract Violations

**Violation 82: Uses Both $request and $validatedData**
- **Operation:** Store report
- **Controller code:**
  ```php
  $validatedData = $request->validated();
  // ...
  $this->storeObjectivesAndActivities($request, $report_id, $report);  // Passes $request
  $this->handleAccountDetails($request, $report_id, $project_id);  // Passes $request
  ```
  Gets `validated()`, but then passes full `$request` to helper methods
- **Impact:** Helper methods may access unvalidated data
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Risk:** Inconsistent validation enforcement

**Violation 83: Notification Sent After Commit**
- **Operation:** Store report (non-draft)
- **Controller code:**
  ```php
  DB::commit();
  Log::info('Transaction committed');
  
  // Notifications AFTER commit
  if (!$isDraftSave) {
      $reportWithId = DPReport::where('report_id', $report_id)->first();
      foreach ($coordinators as $coordinator) {
          NotificationService::notifyReportSubmission($coordinator, $reportId, ...);
      }
  }
  ```
  Notifications sent outside transaction
- **Impact:** If notification fails, report is still saved (acceptable)
- **Phase classification:** N/A
- **Status:** ✅ **Correct Design** – Notifications shouldn't block save

**Violation 84: Duplicate Validation Logic**
- **Operation:** Store report
- **Controller code:**
  ```php
  private function validateRequest(Request $request)
  {
      return $request->validate([
          'project_id' => 'required|string|max:255',
          'photos.*.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',  // Different max!
          // ... duplicate of StoreMonthlyReportRequest rules
      ]);
  }
  ```
  Private method duplicates FormRequest with different values (`max:2048` vs `max:5120`)
- **Impact:** Confusion about which rules apply; dead code if FormRequest used
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Code Smell** – Likely legacy code not removed

---

## Export System Analysis

### ExportController

**Location:** `App\Http\Controllers\Projects\ExportController`

**Size:** 3000+ lines (extremely large controller)

**Dependencies:** 50+ controller injections in constructor

#### Controller Design Analysis

**Observation: Massive Constructor Injection**
```php
public function __construct(
    ProjectEduRUTBasicInfoController $eduRUTBasicInfoController,
    EduRUTTargetGroupController $eduRUTTargetGroupController,
    // ... 50+ more controllers
    IIESExpensesController $iiesExpensesController
) {
    $this->eduRUTBasicInfoController = $eduRUTBasicInfoController;
    // ... assign all 50+
}
```

**Violation 85: Controller-to-Controller Dependency**
- **Pattern:** ExportController injects 50+ other controllers
- **Impact:**
  - Tight coupling between controllers
  - Slow instantiation (all 50 controllers created)
  - Testing nightmare
  - Violates single responsibility
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **ARCHITECTURAL** – Should use services, not controllers

#### PDF Export Analysis

**downloadPdf Method:**
```php
public function downloadPdf($project_id)
{
    $project = Project::where('project_id', $project_id)
        ->with(['attachments', 'objectives.risks', 'objectives.activities.timeframes', ...])
        ->firstOrFail();

    // Permission check
    $hasAccess = false;
    switch ($user->role) {
        case 'provincial':
            if ($project->user->parent_id === $user->id) {
                if (in_array($project->status, [...])) {
                    $hasAccess = true;
                }
            }
            break;
        // ... other roles
    }

    if (!$hasAccess) {
        abort(403, 'You do not have permission to download this project.');
    }
}
```

**Violation 86: Duplicate Permission Logic**
- **Operation:** PDF download permission check
- **Controller code:**
  ```php
  // Admin, coordinators, and provincials have special access rules
  switch ($user->role) {
      case 'provincial':
          // Complex nested conditions
      case 'coordinator':
          // Different conditions
      case 'admin':
          $hasAccess = true;
  }
  // ...
  // For executor and applicant, use ProjectPermissionHelper
  $hasAccess = ProjectPermissionHelper::canView($project, $user);
  ```
  Permission logic duplicated instead of using `ProjectPermissionHelper` for all roles
- **Impact:** Permission logic divergence; bugs when one is updated but not other
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Security gap if permission rules differ

**Violation 87: Potential Null Access on $project->user**
- **Operation:** Provincial permission check
- **Controller code:**
  ```php
  case 'provincial':
      if ($project->user->parent_id === $user->id) {  // $project->user might be null!
  ```
  No null check before accessing `$project->user->parent_id`
- **Impact:** 500 error if project has no associated user
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Orphaned projects crash PDF export

---

## Logical Framework Model Hierarchy

The application implements a hierarchical structure:
```
Project
  └── ProjectObjective (1:N)
        ├── ProjectResult (1:N)
        ├── ProjectActivity (1:N)
        │     └── ProjectTimeframe (1:N)
        └── ProjectRisk (1:N)
```

### Model: ProjectActivity
**Table:** `project_activities`

**Database Schema:**
```php
$table->string('activity_id')->unique();
$table->string('objective_id');
$table->text('activity')->nullable();
$table->text('verification')->nullable();
```

**ID Generation:**
```php
private function generateActivityId()
{
    $latestActivity = self::where('objective_id', $this->objective_id)->latest('id')->first();
    $sequenceNumber = $latestActivity ? intval(substr($latestActivity->activity_id, -2)) + 1 : 1;
    
    return $this->objective_id . '-ACT-' . str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);
}
```

**Resulting ID:** `PRJ-001-OBJ-01-ACT-01`

### Model: ProjectTimeframe
**Table:** `project_timeframes`

**Database Schema:**
```php
$table->string('timeframe_id')->unique();
$table->string('activity_id');
$table->string('month');
$table->boolean('is_active')->default(true);
```

**Relationships:**
```php
public function activity()
{
    return $this->belongsTo(ProjectActivity::class, 'activity_id', 'activity_id');
}

public function DPactivity()
{
    return $this->belongsTo(DPActivity::class, 'activity_id', 'project_activity_id');
}
```

#### Identified Contract Violations

**Violation 88: Dual Relationship to Different Activity Tables**
- **Model:** ProjectTimeframe
- **Relationships defined:**
  ```php
  public function activity()  // Points to ProjectActivity
  public function DPactivity()  // Points to DPActivity (different table!)
  ```
  Same `activity_id` column links to two different models
- **Impact:** Confusing semantics; which activity does timeframe belong to?
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Reports and projects share timeframe data unexpectedly

**Violation 89: ID Generation Overflow at 99 Records**
- **Affected Models:** ProjectObjective, ProjectActivity, ProjectResult, ProjectRisk, ProjectTimeframe
- **Pattern:**
  ```php
  $sequenceNumber = $latest ? intval(substr($latest->id, -2)) + 1 : 1;
  return $prefix . str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);
  ```
  All use 2-digit sequence numbers
- **Impact:** At 100th record, sequence becomes 3 digits, breaking extraction
  - ID: `PRJ-001-OBJ-01-ACT-100`
  - Extraction: `substr($id, -2)` → `"00"` (wrong!)
  - Next ID: `PRJ-001-OBJ-01-ACT-01` (collision!)
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **CRITICAL** – ID collision after 99 records per parent

**Violation 90: is_active Boolean in Timeframe Only**
- **Model:** ProjectTimeframe
- **Schema:** `is_active` boolean with default true
- **Related models:** ProjectActivity, ProjectResult, ProjectRisk have no `is_active`
- **Impact:** Inconsistent soft-delete/active patterns across hierarchy
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**

---

## Notification Service Analysis

### NotificationService

**Location:** `App\Services\NotificationService`

**Purpose:** Centralized notification handling with user preferences

#### Service Design Analysis

**Strength 1: User Preference Handling**
```php
public static function create(User $user, string $type, ...): Notification
{
    $preferences = NotificationPreference::getOrCreateForUser($user->id);

    if (!$preferences->shouldNotify($type)) {
        Log::info("Notification skipped due to user preferences");
    }

    $notification = Notification::create([
        // ...
        'is_read' => !$preferences->shouldNotify($type),  // Pre-mark as read if disabled
    ]);
}
```
✅ **Excellent:** Respects user preferences, still creates record for audit

**Strength 2: Comprehensive Notification Types**
```php
public static function notifyApproval(...): Notification
public static function notifyRejection(...): Notification
public static function notifyReportSubmission(...): Notification
public static function notifyStatusChange(...): Notification
public static function notifyRevert(...): Notification
public static function notifyDeadlineReminder(...): Notification
```
✅ **Good:** Dedicated methods for each notification type

#### Identified Contract Violations

**Violation 91: Email Notification Not Implemented**
- **Operation:** Notification creation
- **Service code:**
  ```php
  // Future Enhancement: Email notifications
  if ($preferences->email_notifications && $preferences->shouldNotify($type)) {
      // Email notification logic will be added here in a future release
      Log::info("Email notification requested (not yet implemented)");
  }
  ```
  Checks preference but doesn't send email
- **Impact:** User enables email notifications but receives nothing
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Documented Gap** – Future feature placeholder

**Violation 92: Inconsistent Parameter Types Across Methods**
- **Operation:** Various notification methods
- **Service code:**
  ```php
  public static function notifyApproval(
      User $user,
      string $relatedType,
      int|string $relatedId,  // Can be int or string
      string $relatedTitle
  ): Notification

  public static function notifyRejection(
      User $user,
      string $relatedType,
      int $relatedId,  // Only int!
      string $relatedTitle,
      ?string $reason = null
  ): Notification
  ```
  `notifyApproval` accepts `int|string`, `notifyRejection` only `int`
- **Impact:** Projects use string IDs, reports use integer IDs; only some methods work for projects
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Risk:** Runtime error when passing string project_id to `notifyRejection`

---

## Permission Helper Analysis

### ProjectPermissionHelper

**Location:** `App\Helpers\ProjectPermissionHelper`

**Purpose:** Centralized permission checks for projects

#### Design Analysis

**Strength 1: Centralized Permission Logic**
```php
public static function canEdit(Project $project, User $user): bool
{
    if (!ProjectStatus::isEditable($project->status)) {
        return false;
    }
    return self::isOwnerOrInCharge($project, $user);
}
```
✅ **Good:** Single source of truth for permission logic

**Strength 2: Query Builder for Bulk Operations**
```php
public static function getEditableProjects(User $user): \Illuminate\Database\Eloquent\Builder
{
    $query = Project::where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhere('in_charge', $user->id);
    });
    $query->whereIn('status', ProjectStatus::getEditableStatuses());
    return $query;
}
```
✅ **Good:** Reusable query builder for list views

#### Identified Contract Violations

**Violation 93: canView() Doesn't Check Provincial Hierarchy**
- **Operation:** Check view permission
- **Helper code:**
  ```php
  public static function canView(Project $project, User $user): bool
  {
      // Admin and coordinators can view all projects
      if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
          return true;  // Provincial can view ALL projects!
      }
      return self::isOwnerOrInCharge($project, $user);
  }
  ```
  Provincials can view all projects, not just their hierarchy
- **Contrast with ExportController:**
  ```php
  case 'provincial':
      if ($project->user->parent_id === $user->id) {  // Checks hierarchy!
  ```
  ExportController checks parent_id, helper doesn't
- **Impact:** Permission leak – provincials see projects they shouldn't
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **SECURITY** – Inconsistent permission enforcement

**Violation 94: No Report Permission Helper**
- **Observation:** `ProjectPermissionHelper` exists, but no `ReportPermissionHelper`
- **Impact:** Report permission logic scattered across controllers
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architectural Gap** – Should have parallel helper

---

## firstOrFail() Usage Analysis

### Scope of Usage

**Count:** 140 occurrences across 44 controllers

**High-Usage Controllers:**
| Controller | Count | Risk Level |
|------------|-------|------------|
| ProvincialController | 15 | High |
| GeneralController | 14 | High |
| ReportController | 12 | Medium |
| CoordinatorController | 9 | High |
| ProjectController | 7 | Medium |

#### Identified Contract Violations

**Violation 95: Inconsistent Error Handling for Missing Records**
- **Pattern across codebase:**
  ```php
  // Some controllers:
  $project = Project::where('project_id', $id)->firstOrFail();  // 404 on missing
  
  // Other controllers:
  $project = Project::where('project_id', $id)->first();
  if (!$project) {
      return null;  // Graceful null return
  }
  
  // Yet others:
  $record = Model::where('project_id', $id)->first() ?? new Model();  // Default object
  ```
  Three different patterns for same scenario
- **Impact:** Unpredictable behavior across application
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**

**Violation 96: firstOrFail in Edit Methods Without Frontend Check**
- **Pattern:**
  ```php
  // Controller:
  public function edit($projectId)
  {
      $record = Model::where('project_id', $projectId)->firstOrFail();  // 404!
      return $record;
  }
  
  // Blade:
  <a href="{{ route('model.edit', $projectId) }}">Edit</a>  // Always visible!
  ```
  Edit link shows even when no record exists
- **Impact:** User clicks Edit → 404 error
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**

---

## Phase-wise Issue Summary (Batch 4)

### Phase 1 – Critical Data Integrity

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 89 | ID generation overflow at 99 records | 5 Logical Framework models | Critical |
| 93 | Provincial canView() doesn't check hierarchy | ProjectPermissionHelper | Security |

### Phase 2 – Input Normalization Gaps

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 81 | balance_amount allows negative | StoreMonthlyReportRequest | Medium |
| 82 | Uses both $request and $validatedData | ReportController | Medium |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 79 | Deeply nested array validation (4 levels) | StoreMonthlyReportRequest | Medium |
| 80 | No maximum array length constraints | StoreMonthlyReportRequest | High |
| 85 | 50+ controller dependencies | ExportController | Architectural |
| 86 | Duplicate permission logic | ExportController | Medium |
| 87 | Null access on $project->user | ExportController | Medium |
| 88 | Dual relationship to different tables | ProjectTimeframe | High |
| 90 | is_active inconsistent across hierarchy | Timeframe only | Low |
| 92 | Inconsistent parameter types | NotificationService | Medium |
| 94 | No Report permission helper | Architecture gap | Low |
| 95 | Inconsistent firstOrFail patterns | 44 controllers | High |

### Phase 4 – Presentation & Secondary Paths

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 84 | Duplicate validation logic | ReportController | Low |
| 91 | Email notification not implemented | NotificationService | Low |
| 96 | firstOrFail without frontend check | Multiple controllers | Medium |

---

## Strengths Identified (Batch 4)

### Excellent Implementations

**1. StoreMonthlyReportRequest Design**
```php
public function authorize(): bool
{
    return auth()->check() && in_array(auth()->user()->role, ['executor', 'applicant']);
}

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // Complex cross-field date validation
    });
}
```
✅ **Best Practice:** Authorization + complex validation in FormRequest

**2. ReportController Transaction Handling**
```php
DB::beginTransaction();
try {
    $report = $this->createReport($validatedData, $report_id);
    $this->storeObjectivesAndActivities(...);
    $this->handleAccountDetails(...);
    $this->handleOutlooks(...);
    $this->handlePhotos(...);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```
✅ **Best Practice:** All nested operations in single transaction

**3. NotificationService Preference Handling**
```php
$notification = Notification::create([
    'is_read' => !$preferences->shouldNotify($type),
]);
```
✅ **Smart:** Still creates record for audit, but pre-marks as read

**4. Hierarchical ID Generation**
```php
return $this->objective_id . '-ACT-' . str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);
// Result: PRJ-001-OBJ-01-ACT-01
```
✅ **Excellent:** IDs trace full hierarchy (though limited to 99)

---

## Summary Statistics (Batch 4)

| Category | Count |
|----------|-------|
| Form Requests analyzed | 1 |
| Controllers analyzed | 2 |
| Services analyzed | 1 |
| Helpers analyzed | 1 |
| Models analyzed | 4 |
| New violations identified | 18 (79-96) |
| Phase 1 violations | 2 |
| Phase 2 violations | 2 |
| Phase 3 violations | 11 |
| Phase 4 violations | 3 |
| Security issues | 1 |
| Architectural issues | 1 |

---

## Cumulative Statistics (All Batches)

| Batch | Models | Controllers | Services | Violations | Critical |
|-------|--------|-------------|----------|------------|----------|
| Primary | 7 | 5 | 0 | 13 | 4 |
| Extended | 6 | 8 | 2 | 22 | 0 |
| Batch 2 | 6 | 5 | 1 | 20 | 3 |
| Batch 3 | 4 | 4 | 0 | 24 | 4 |
| Batch 4 | 4 | 2 | 2 | 18 | 2 |
| **Total** | **27** | **24** | **5** | **97** | **13** |

---

## Key Takeaways (Batch 4)

### Positive Patterns Observed

1. **Report System** uses `$request->validated()` correctly (unlike project controllers)
2. **Transaction handling** is comprehensive in ReportController
3. **NotificationService** respects user preferences elegantly
4. **Hierarchical IDs** provide excellent traceability

### Critical Issues to Address

1. **ID Overflow Bug** – All logical framework models will fail at 100th record
2. **Provincial Permission Leak** – Helper allows viewing all projects
3. **Controller-Controller Dependency** – ExportController's 50+ injections

### Architectural Recommendations (NOT fixes, just observations)

1. ExportController should use services, not controller injections
2. Permission logic should be fully centralized (not duplicated in controllers)
3. Report permission helper parallel to project permission helper needed

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers
- ❌ Do not modify services
- ❌ Do not propose solutions

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Batch 4 contract audit performed by: Senior Laravel Architect*
