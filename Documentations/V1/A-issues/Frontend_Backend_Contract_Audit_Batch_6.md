# Frontend–Backend Contract Audit – Batch 6

## Purpose

This document continues the contract audit series, focusing on ExecutorController, GeneralController, ProjectQueryService, and the core Project and User models. This batch examines the executor/applicant experience, dual-context General user capabilities, and the foundational data models.

---

## ExecutorController Analysis

### ExecutorController

**Location:** `App\Http\Controllers\ExecutorController`

**Size:** 1,114 lines

**Purpose:** Dashboard and report management for executor/applicant users

#### Controller Design Analysis

**Strength 1: Uses ProjectQueryService Consistently**
```php
public function executorDashboard(Request $request)
{
    $user = Auth::user();
    
    // Get projects where user is owner or in-charge
    $projectsQuery = ProjectQueryService::getProjectsForUserQuery($user);
    // ...
}
```
✅ **Excellent:** Centralized query logic, not duplicated

**Strength 2: Proper Pagination**
```php
$perPage = $request->get('per_page', 15);
$projects = $projectsQuery->paginate($perPage)->appends($request->query());
```
✅ **Good:** Unlike CoordinatorController, ExecutorController paginates

**Strength 3: Comprehensive Dashboard Widgets**
```php
// Get action items data for dashboard widgets
$actionItems = $this->getActionItems($user);
$reportStatusSummary = $this->getReportStatusSummary($user);
$upcomingDeadlines = $this->getUpcomingDeadlines($user);
$chartData = $this->getChartData($user, $request);
$reportChartData = $this->getReportChartData($user, $request);
$quickStats = $this->getQuickStats($user);
$recentActivities = $this->getRecentActivities($user);
$projectHealthSummary = $this->getProjectHealthSummary($enhancedProjects ?? []);
$projectsRequiringAttention = $this->getProjectsRequiringAttention($user);
$reportsRequiringAttention = $this->getReportsRequiringAttention($user);
```
✅ **Good:** Rich dashboard with actionable insights

**Strength 4: Status Service for Transitions**
```php
public function submitReport(Request $request, $report_id)
{
    $report = DPReport::where('report_id', $report_id)
                     ->whereIn('project_id', $projectIds)
                     ->firstOrFail();

    try {
        \App\Services\ReportStatusService::submitToProvincial($report, $user);
        return redirect()->route('executor.report.list')->with('success', '...');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage());
    }
}
```
✅ **Good:** Uses status service; handles exceptions

#### Identified Contract Violations

**Violation 116: Hardcoded Status Lists**
- **Pattern:**
  ```php
  // In pendingReports():
  $reportsQuery->whereIn('status', [
      DPReport::STATUS_DRAFT,
      DPReport::STATUS_REVERTED_BY_PROVINCIAL,
      DPReport::STATUS_REVERTED_BY_COORDINATOR,
      DPReport::STATUS_REVERTED_BY_GENERAL_AS_PROVINCIAL,
      DPReport::STATUS_REVERTED_BY_GENERAL_AS_COORDINATOR,
      DPReport::STATUS_REVERTED_TO_EXECUTOR,
      DPReport::STATUS_REVERTED_TO_APPLICANT,
  ]);
  ```
  Same status lists repeated 4+ times in controller
- **Impact:** If new status added, multiple places need updating
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **DRY Violation** – Should use `DPReport::getEditableStatuses()` or similar

**Violation 117: str_contains for Status Checking**
- **Pattern:**
  ```php
  'reverted' => $projects->filter(function($project) {
      return str_contains($project->status, 'reverted');
  }),
  ```
  Uses string matching instead of status constants
- **Impact:** Fragile; breaks if status name changes
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Code Smell** – Should use `ProjectStatus::isReverted()`

**Violation 118: Report Due Date Logic Hardcoded**
- **Pattern:**
  ```php
  $dueDate = $now->copy()->endOfMonth();
  $isPastDue = $now->gt($dueDate);
  ```
  Business logic for report deadlines embedded in controller
- **Impact:** If deadline rules change, scattered changes needed
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Business Logic Leak** – Should be in service/config

**Violation 119: APPROVED_BY_GENERAL_AS_PROVINCIAL in Approved Status**
- **Pattern:**
  ```php
  $projectsQuery->whereIn('status', [
      ProjectStatus::APPROVED_BY_COORDINATOR,
      ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
      ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL,  // This is FORWARD, not approval!
  ]);
  ```
  Same issue as Violation 97 – misleading status name
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** Cross-reference to Violation 97

---

## GeneralController Analysis

### GeneralController

**Location:** `App\Http\Controllers\GeneralController`

**Size:** 5,900+ lines (extremely large)

**Purpose:** Dual-context dashboard for General users (acts as both Coordinator and Provincial)

#### Controller Design Analysis

**Strength 1: Clear Dual-Context Architecture**
```php
/**
 * General user has COMPLETE coordinator access for coordinator hierarchy
 * General user also acts as provincial for direct team management
 */
public function generalDashboard(Request $request)
{
    // Get coordinator IDs under general
    $coordinatorIds = User::where('parent_id', $general->id)
        ->where('role', 'coordinator')
        ->pluck('id');

    // Get direct team IDs (executors/applicants directly under general)
    $directTeamIds = User::where('parent_id', $general->id)
        ->whereIn('role', ['executor', 'applicant'])
        ->pluck('id');
    // ...
}
```
✅ **Good:** Clear separation of coordinator hierarchy vs direct team

**Strength 2: Recursive Descendant Query**
```php
$allUserIdsUnderCoordinators = $this->getAllDescendantUserIds($coordinatorIds);
```
✅ **Good:** Handles multi-level hierarchy

#### Identified Contract Violations

**Violation 120: Largest Controller (5,900+ lines)**
- **Observation:** Single controller handles:
  - Dual-context dashboard (coordinator + provincial)
  - Coordinator CRUD
  - Provincial CRUD
  - Executor CRUD
  - Center/Society management
  - Project approvals (as coordinator AND provincial)
  - Report approvals (as coordinator AND provincial)
  - System analytics
- **Impact:** Maintenance nightmare; testing impossible
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **CRITICAL ARCHITECTURAL** – Must be split

**Violation 121: Role Check in Controller Instead of Middleware**
- **Pattern:**
  ```php
  public function generalDashboard(Request $request)
  {
      $general = Auth::user();

      if ($general->role !== 'general') {
          abort(403, 'Access denied. Only General users can access this dashboard.');
      }
      // ...
  }
  ```
  Manual role check instead of middleware
- **Impact:** Repeated in every method; easy to forget
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Should use middleware** – `$this->middleware('role:general')`

**Violation 122: Duplicate Code Between Controllers**
- **Observation:** `GeneralController::calculateBudgetSummariesFromProjects()` is nearly identical to:
  - `ProvincialController::calculateBudgetSummariesFromProjects()`
  - `CoordinatorController::calculateBudgetSummariesFromProjects()`
  - `ExecutorController::calculateBudgetSummariesFromProjects()`
- **Impact:** Any bug fix or feature change must be replicated 4 times
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **DRY Violation** – Should be in a shared service

---

## ProjectQueryService Analysis

### ProjectQueryService

**Location:** `App\Services\ProjectQueryService`

**Size:** 171 lines

**Purpose:** Centralized project query logic

#### Service Design Analysis

**Strength 1: Owner-or-InCharge Pattern Centralized**
```php
public static function getProjectsForUserQuery(User $user): Builder
{
    return Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    });
}
```
✅ **Excellent:** Single source of truth for ownership queries

**Strength 2: Composable Query Methods**
```php
public static function getApprovedProjectsForUser(User $user, array $with = [])
public static function getEditableProjectsForUser(User $user, array $with = [])
public static function getRevertedProjectsForUser(User $user, array $with = [])
```
✅ **Good:** Status-specific convenience methods

**Strength 3: Search Filter Builder**
```php
public static function applySearchFilter(Builder $query, string $searchTerm): Builder
{
    return $query->where(function($q) use ($searchTerm) {
        $q->where('project_id', 'like', "%{$searchTerm}%")
          ->orWhere('project_title', 'like', "%{$searchTerm}%")
          ->orWhere('society_name', 'like', "%{$searchTerm}%")
          ->orWhere('place', 'like', "%{$searchTerm}%");
    });
}
```
✅ **Good:** Reusable search logic

#### Identified Contract Violations

**Violation 123: Static Methods Limit Testability**
- **Pattern:** All methods are `static`
- **Impact:** Cannot mock in unit tests; requires integration tests
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Consistent Pattern** – Same issue across all services

**Violation 124: APPROVED_BY_GENERAL_AS_PROVINCIAL in Approved List**
- **Pattern:**
  ```php
  public static function getApprovedProjectsForUser(User $user, array $with = [])
  {
      return self::getProjectsForUserByStatus($user, [
          ProjectStatus::APPROVED_BY_COORDINATOR,
          ProjectStatus::APPROVED_BY_GENERAL_AS_COORDINATOR,
          ProjectStatus::APPROVED_BY_GENERAL_AS_PROVINCIAL,  // This is a FORWARD!
      ], $with);
  }
  ```
  Same semantic confusion: "approved as provincial" is actually "forwarded"
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** Cross-reference to Violation 97

**Violation 125: Search on Non-Indexed Column**
- **Pattern:**
  ```php
  ->orWhere('place', 'like', "%{$searchTerm}%")
  ```
  Searches `place` column which may not be indexed
- **Impact:** Full table scan on large datasets
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Performance** – Verify indexes exist

---

## Project Model Analysis

### Project Model

**Location:** `App\Models\OldProjects\Project`

**Size:** 757 lines

**Purpose:** Core project entity with 50+ relationships

#### Model Design Analysis

**Strength 1: Scope Methods for Status Filtering**
```php
public function scopeCompleted($query)
{
    return $query->whereNotNull('completed_at');
}

public function scopeNotCompleted($query)
{
    return $query->whereNull('completed_at');
}
```
✅ **Good:** Reusable query scopes

**Strength 2: Computed Attributes**
```php
public function getIsCompletedAttribute(): bool
{
    return !is_null($this->completed_at);
}

public function getCommencementMonthAttribute()
{
    return $this->commencement_month_year 
        ? date('m', strtotime($this->commencement_month_year)) 
        : null;
}
```
✅ **Good:** Computed attributes for derived values

**Strength 3: Project ID Generation with Initials**
```php
private function generateProjectId()
{
    $initialsMap = [
        'CHILD CARE INSTITUTION' => 'CCI',
        'Development Projects' => 'DP',
        // ... more mappings
    ];

    $initials = $initialsMap[$this->project_type] ?? 'GEN';
    $latestProject = self::where('project_id', 'like', $initials . '-%')->latest('id')->first();
    $sequenceNumber = $latestProject ? intval(substr($latestProject->project_id, strlen($initials) + 1)) + 1 : 1;
    
    return $initials . '-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
}
```
✅ **Good:** 4-digit padding (supports 9,999 projects per type)

#### Identified Contract Violations

**Violation 126: Model Has 50+ Relationships**
- **Observation:** Project model defines 50+ relationship methods
- **Impact:** 
  - Massive file size (757 lines)
  - All project type relationships in one model
  - Cognitive overload
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architectural** – Consider traits for project-type-specific relationships

**Violation 127: String Columns for Numeric Data**
- **Schema:**
  ```php
  @property string $overall_project_budget
  @property string|null $amount_forwarded
  @property string|null $amount_sanctioned
  @property string|null $opening_balance
  ```
  Budget/amount fields stored as strings
- **Impact:** 
  - No DB-level arithmetic
  - Sorting issues (`"9" > "10"`)
  - Validation bypassed at DB level
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **Schema Issue** – Should be `decimal(15,2)`

**Violation 128: $guarded = [] on User Model**
- **Pattern in User model:**
  ```php
  protected $guarded = [];
  ```
  No mass assignment protection
- **Impact:** Any field can be mass-assigned, including `role`, `status`, `parent_id`
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **SECURITY** – Should use `$fillable` with explicit fields

**Violation 129: Status Labels in Model**
- **Pattern:**
  ```php
  public static $statusLabels = [
      'draft' => 'Draft (Executor still working)',
      'submitted_to_provincial' => 'Executor submitted to Provincial',
      // ...
  ];
  ```
  UI labels in model instead of view/translation layer
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Architectural** – Should be in lang files or presenter

**Violation 130: Comment ID Uses 3-Digit Padding**
- **Pattern:**
  ```php
  public function generateProjectCommentId()
  {
      $latestComment = $this->comments()->orderBy('created_at', 'desc')->first();
      $nextNumber = $latestComment ? (int)substr($latestComment->project_comment_id, -3) + 1 : 1;
      return $this->project_id . '.' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
  }
  ```
  3-digit padding → overflow at 1000 comments per project
- **Impact:** ID collision after 999 comments
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **ID Overflow** – Same pattern as Violation 89

---

## User Model Analysis

### User Model

**Location:** `App\Models\User`

**Purpose:** User authentication and authorization

#### Model Design Analysis

**Strength 1: Hierarchical Relationships**
```php
public function parent()
{
    return $this->belongsTo(User::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(User::class, 'parent_id');
}
```
✅ **Good:** Self-referential hierarchy for org structure

**Strength 2: Uses Spatie Roles**
```php
use HasRoles;
```
✅ **Good:** Proper role management package

#### Identified Contract Violations

**Violation 131: $guarded = [] (Already mentioned in 128)**
- **Pattern:**
  ```php
  protected $guarded = [];
  ```
  No mass assignment protection
- **Impact:** Critical security vulnerability
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **CRITICAL SECURITY** – Must use `$fillable`

**Violation 132: Password Hashing in Cast, Not Mutator**
- **Pattern:**
  ```php
  protected $casts = [
      'password' => 'hashed',
  ];
  ```
  Using Laravel 10+ automatic hashing cast
- **Status:** ✅ **Correct** – Modern Laravel approach

---

## Dashboard Widget Patterns

### Budget Calculation Duplication

**Identical code in 4 controllers:**
```php
private function calculateBudgetSummariesFromProjects($projects, $request)
{
    // Phase 4: Use only canonical project fields
    $projectBudget = (float) ($project->amount_sanctioned ?? $project->overall_project_budget ?? 0);

    // If no budget found, try to get from approved reports
    if ($projectBudget == 0 && $project->reports && $project->reports->count() > 0) {
        foreach ($project->reports as $report) {
            if ($report->isApproved() && $report->accountDetails) {
                $projectBudget = $report->accountDetails->sum('total_amount');
                break;
            }
        }
    }
    // ...
}
```

**Violation 133: Budget Source Fallback Chain**
- **Pattern:** Try 4 different sources for budget:
  1. `amount_sanctioned`
  2. `overall_project_budget`
  3. First approved report's `total_amount`
  4. `0`
- **Impact:** Different projects may have different budget sources; inconsistent reporting
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Unclear Contract** – Should have single canonical source

### Project Health Calculation

**Pattern in ExecutorController:**
```php
private function calculateProjectHealth($project, $budgetUtilization, $lastReportDate)
{
    $health = 100; // Start with perfect health

    // Budget utilization (0-40 points)
    if ($budgetUtilization > 90) {
        $health -= 40;
        $factors[] = 'Budget over 90% utilized';
    }
    // ...

    // Report submission timeliness (0-30 points)
    if ($lastReportDate) {
        $daysSinceLastReport = Carbon::now()->diffInDays($lastReportDate);
        if ($daysSinceLastReport > 60) {
            $health -= 30;
        }
    }
    // ...
}
```

**Strength:** Well-documented health scoring algorithm

**Violation 134: Health Thresholds Hardcoded**
- **Pattern:** Magic numbers for health scoring
- **Impact:** If business rules change, code changes required
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Should be configurable**

---

## Phase-wise Issue Summary (Batch 6)

### Phase 1 – Critical Data Integrity

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 127 | String columns for numeric budget data | Project model | High |
| 128 | $guarded = [] allows mass assignment | User model | Critical |
| 130 | Comment ID overflow at 999 | Project model | Medium |
| 131 | $guarded = [] repeated issue | User model | Critical |

### Phase 2 – Input Normalization Gaps

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 133 | Budget source fallback chain | 4 controllers | Medium |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 116 | Hardcoded status lists | ExecutorController | Medium |
| 117 | str_contains for status checking | ExecutorController | Low |
| 118 | Report due date logic hardcoded | ExecutorController | Low |
| 119 | APPROVED_BY_GENERAL_AS_PROVINCIAL issue | ExecutorController | Low |
| 120 | Largest controller (5,900+ lines) | GeneralController | Critical |
| 121 | Role check in controller not middleware | GeneralController | Medium |
| 122 | Duplicate code between 4 controllers | All dashboard controllers | High |
| 123 | Static methods limit testability | ProjectQueryService | Medium |
| 124 | APPROVED_BY_GENERAL_AS_PROVINCIAL in service | ProjectQueryService | Low |
| 125 | Search on non-indexed column | ProjectQueryService | Low |
| 126 | Model has 50+ relationships | Project model | Medium |
| 134 | Health thresholds hardcoded | ExecutorController | Low |

### Phase 4 – Presentation & Secondary Paths

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 129 | Status labels in model | Project model | Low |

---

## Strengths Identified (Batch 6)

### Excellent Implementations

**1. ExecutorController Pagination**
```php
$perPage = $request->get('per_page', 15);
$projects = $projectsQuery->paginate($perPage)->appends($request->query());
```
✅ **Best Practice:** Unlike other dashboard controllers

**2. ProjectQueryService Centralization**
```php
public static function getProjectsForUserQuery(User $user): Builder
{
    return Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    });
}
```
✅ **Excellent:** Single source of truth for ownership logic

**3. Project ID Generation with 4-Digit Padding**
```php
return $initials . '-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
```
✅ **Good:** Supports 9,999 projects per type (vs 99 in child models)

**4. Comprehensive Dashboard Widgets**
- Action items with urgency levels
- Budget utilization charts
- Project health scoring
- Upcoming deadlines
- Recent activities

---

## Summary Statistics (Batch 6)

| Category | Count |
|----------|-------|
| Controllers analyzed | 2 (ExecutorController, GeneralController) |
| Services analyzed | 1 (ProjectQueryService) |
| Models analyzed | 2 (Project, User) |
| New violations identified | 19 (116-134) |
| Phase 1 violations | 4 |
| Phase 2 violations | 1 |
| Phase 3 violations | 13 |
| Phase 4 violations | 1 |
| Security issues | 2 (mass assignment) |
| Architectural issues | 2 |

---

## Cumulative Statistics (All Batches)

| Batch | Controllers | Services | Models | Violations | Critical |
|-------|-------------|----------|--------|------------|----------|
| Primary | 5 | 0 | 7 | 13 | 4 |
| Extended | 8 | 2 | 6 | 22 | 0 |
| Batch 2 | 5 | 1 | 6 | 20 | 3 |
| Batch 3 | 4 | 0 | 4 | 24 | 4 |
| Batch 4 | 2 | 2 | 4 | 18 | 2 |
| Batch 5 | 2 | 2 | 0 | 19 | 2 |
| Batch 6 | 2 | 1 | 2 | 19 | 4 |
| **Total** | **28** | **8** | **29** | **135** | **19** |

---

## Key Takeaways (Batch 6)

### Positive Patterns Observed

1. **ExecutorController** properly paginates (unlike Coordinator/Provincial)
2. **ProjectQueryService** centralizes ownership logic effectively
3. **Project ID generation** uses 4-digit padding (future-proof)
4. **Dashboard widgets** provide actionable insights

### Critical Issues to Address

1. **User model $guarded = []** – Critical security vulnerability
2. **GeneralController 5,900+ lines** – Unmaintainable
3. **Budget source fallback chain** – Unclear canonical source
4. **String columns for amounts** – Schema-level issue

### Controller Size Comparison (All Roles)

| Controller | Lines | Status |
|------------|-------|--------|
| GeneralController | 5,900+ | ❌ Critical |
| CoordinatorController | 3,000+ | ❌ Critical |
| ProvincialController | 2,347 | ❌ High |
| ExecutorController | 1,114 | ⚠️ Moderate |

---

## DO NOT

This document is for diagnosis only:
- ❌ Do not implement fixes
- ❌ Do not add validation rules
- ❌ Do not refactor controllers
- ❌ Do not modify models
- ❌ Do not propose solutions

This audit serves as the foundation for phase-wise remediation planning.

---

*Document generated: January 31, 2026*  
*Batch 6 contract audit performed by: Senior Laravel Architect*
