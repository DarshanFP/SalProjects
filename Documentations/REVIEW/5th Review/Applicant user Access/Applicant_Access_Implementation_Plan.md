# Applicant User Access - Phase Wise Implementation Plan

## Executive Summary

This document outlines the implementation plan to grant **applicant** role users the same access as **executor** role users on projects where they are either the **Executor** (owner via `user_id`) or **Applicant** (in-charge via `in_charge`).

### Current State Analysis

#### Project Association Model
- Projects have two key user associations:
  - `user_id`: The project creator/owner (can be executor or applicant)
  - `in_charge`: The project in-charge person (can be executor or applicant)

#### Current Access Differences

**Executors:**
- ✅ Can edit projects they own (`user_id` matches)
- ✅ Can edit projects where they are in-charge (`in_charge` matches)
- ✅ Can view projects they own or are in-charge of
- ✅ Can submit projects they own or are in-charge of
- ✅ Can see approved projects in dashboard (only by `user_id` currently)

**Applicants:**
- ✅ Can edit projects they own (`user_id` matches)
- ❌ **CANNOT** edit projects where they are in-charge (`in_charge` matches) - **RESTRICTION TO REMOVE**
- ✅ Can view projects they own or are in-charge of (via `ProjectPermissionHelper::canView`)
- ✅ Can submit projects they own or are in-charge of (via `ProjectPermissionHelper::canSubmit`)
- ❌ **CANNOT** see approved projects in dashboard where they are in-charge - **RESTRICTION TO REMOVE**

#### Key Files Identified

1. **Permission Helper:**
   - `app/Helpers/ProjectPermissionHelper.php` - Contains `canApplicantEdit()` method that restricts applicants

2. **Controllers:**
   - `app/Http/Controllers/ExecutorController.php` - Dashboard only queries by `user_id`
   - `app/Http/Controllers/Projects/ProjectController.php` - Some methods may need updates
   - Various report controllers that filter by `user_id`

3. **Routes:**
   - `routes/web.php` - Already allows both roles in most places

---

## Phase 1: Core Permission Helper Updates

### Objective
Update `ProjectPermissionHelper` to grant applicants the same access as executors.

### Tasks

#### Task 1.1: Update `canApplicantEdit()` Method
**File:** `app/Helpers/ProjectPermissionHelper.php`  
**Current Code (Lines 92-103):**
```php
public static function canApplicantEdit(Project $project, User $user): bool
{
    if ($user->role !== 'applicant') {
        return false;
    }

    // Applicants can only edit projects they created
    return $project->user_id === $user->id;
}
```

**Required Change:**
```php
public static function canApplicantEdit(Project $project, User $user): bool
{
    if ($user->role !== 'applicant') {
        return false;
    }

    // Applicants can edit projects they own or are in-charge of (same as executors)
    return self::isOwnerOrInCharge($project, $user);
}
```

**Impact:** This method may be used in views or other places. Need to verify all usages.

**Testing:**
- ✅ Applicant can edit project they created
- ✅ Applicant can edit project where they are in-charge
- ✅ Applicant cannot edit project they don't own and aren't in-charge of

**Estimated Time:** 30 minutes

---

#### Task 1.2: Update `getEditableProjects()` Method
**File:** `app/Helpers/ProjectPermissionHelper.php`  
**Current Code (Lines 108-124):**
```php
public static function getEditableProjects(User $user): \Illuminate\Database\Eloquent\Builder
{
    $query = Project::where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhere('in_charge', $user->id);
    });

    // Filter by editable statuses
    $query->whereIn('status', ProjectStatus::getEditableStatuses());

    // For executors, exclude approved projects
    if ($user->role === 'executor') {
        $query->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR);
    }

    return $query;
}
```

**Required Change:**
```php
public static function getEditableProjects(User $user): \Illuminate\Database\Eloquent\Builder
{
    $query = Project::where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhere('in_charge', $user->id);
    });

    // Filter by editable statuses
    $query->whereIn('status', ProjectStatus::getEditableStatuses());

    // For executors and applicants, exclude approved projects
    if (in_array($user->role, ['executor', 'applicant'])) {
        $query->where('status', '!=', ProjectStatus::APPROVED_BY_COORDINATOR);
    }

    return $query;
}
```

**Impact:** This ensures applicants get the same project filtering logic as executors.

**Testing:**
- ✅ Applicant sees editable projects they own
- ✅ Applicant sees editable projects where they are in-charge
- ✅ Applicant doesn't see approved projects in editable list

**Estimated Time:** 15 minutes

---

#### Task 1.3: Verify `canEdit()` Method
**File:** `app/Helpers/ProjectPermissionHelper.php`  
**Current Code (Lines 14-23):**
```php
public static function canEdit(Project $project, User $user): bool
{
    // Check if project is in editable status
    if (!ProjectStatus::isEditable($project->status)) {
        return false;
    }

    // Check ownership
    return self::isOwnerOrInCharge($project, $user);
}
```

**Status:** ✅ **Already correct** - Uses `isOwnerOrInCharge()` which works for both roles.

**No changes needed.**

---

#### Task 1.4: Verify `canView()` Method
**File:** `app/Helpers/ProjectPermissionHelper.php`  
**Current Code (Lines 47-56):**
```php
public static function canView(Project $project, User $user): bool
{
    // Admin and coordinators can view all projects
    if (in_array($user->role, ['admin', 'coordinator', 'provincial'])) {
        return true;
    }

    // Check ownership
    return self::isOwnerOrInCharge($project, $user);
}
```

**Status:** ✅ **Already correct** - Uses `isOwnerOrInCharge()` which works for both roles.

**No changes needed.**

---

#### Task 1.5: Verify `canSubmit()` Method
**File:** `app/Helpers/ProjectPermissionHelper.php`  
**Current Code (Lines 28-42):**
```php
public static function canSubmit(Project $project, User $user): bool
{
    // Check if project is in submittable status
    if (!ProjectStatus::isSubmittable($project->status)) {
        return false;
    }

    // Check user role
    if (!in_array($user->role, ['executor', 'applicant'])) {
        return false;
    }

    // Check ownership
    return self::isOwnerOrInCharge($project, $user);
}
```

**Status:** ✅ **Already correct** - Already allows both roles and uses `isOwnerOrInCharge()`.

**No changes needed.**

---

### Phase 1 Summary
- **Files Modified:** 1 (`ProjectPermissionHelper.php`)
- **Methods Updated:** 2 (`canApplicantEdit`, `getEditableProjects`)
- **Methods Verified:** 3 (`canEdit`, `canView`, `canSubmit`)
- **Total Estimated Time:** 45 minutes

---

## Phase 2: ExecutorController Updates

### Objective
Update `ExecutorController` to include projects where applicants are in-charge, matching executor behavior.

### Tasks

#### Task 2.1: Update `ExecutorDashboard()` Method
**File:** `app/Http/Controllers/ExecutorController.php`  
**Current Code (Lines 15-41):**
```php
public function ExecutorDashboard(Request $request)
{
    $executor = Auth::user();

    // Get the authenticated user's projects that are approved by coordinator
    $projectsQuery = Project::where('user_id', Auth::id())
                           ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $projectsQuery->where('project_type', $request->project_type);
    }

    $projects = $projectsQuery->with(['reports.accountDetails', 'budgets'])->get();

    // Calculate budget summaries from projects and their reports
    $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

    // Fetch distinct project types for filters
    $projectTypes = Project::where('user_id', Auth::id())
                          ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
                          ->distinct()
                          ->pluck('project_type');

    // Pass the projects to the executor index view
    return view('executor.index', compact('projects', 'budgetSummaries', 'projectTypes'));
}
```

**Required Change:**
```php
public function ExecutorDashboard(Request $request)
{
    $user = Auth::user();

    // Get projects where user is owner or in-charge (for both executor and applicant)
    $projectsQuery = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })
    ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $projectsQuery->where('project_type', $request->project_type);
    }

    $projects = $projectsQuery->with(['reports.accountDetails', 'budgets'])->get();

    // Calculate budget summaries from projects and their reports
    $budgetSummaries = $this->calculateBudgetSummariesFromProjects($projects, $request);

    // Fetch distinct project types for filters
    $projectTypes = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })
    ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
    ->distinct()
    ->pluck('project_type');

    // Pass the projects to the executor index view
    return view('executor.index', compact('projects', 'budgetSummaries', 'projectTypes'));
}
```

**Impact:** Both executors and applicants will see approved projects where they are owner or in-charge.

**Testing:**
- ✅ Executor sees approved projects they own
- ✅ Executor sees approved projects where they are in-charge
- ✅ Applicant sees approved projects they own
- ✅ Applicant sees approved projects where they are in-charge

**Estimated Time:** 30 minutes

---

#### Task 2.2: Update `ReportList()` Method
**File:** `app/Http/Controllers/ExecutorController.php`  
**Current Code (Lines 43-65):**
```php
public function ReportList(Request $request)
{
    $executor = Auth::user();

    // Fetch reports for this executor
    $reportsQuery = DPReport::where('user_id', $executor->id);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    // Eager load relationships to prevent N+1 queries
    $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

    // Calculate budget summaries
    $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

    // Fetch distinct project types for filters
    $projectTypes = DPReport::where('user_id', $executor->id)->distinct()->pluck('project_type');

    return view('executor.ReportList', compact('reports', 'budgetSummaries', 'projectTypes'));
}
```

**Required Change:**
```php
public function ReportList(Request $request)
{
    $user = Auth::user();

    // Fetch reports for projects where user is owner or in-charge
    $projectIds = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })->pluck('project_id');

    $reportsQuery = DPReport::whereIn('project_id', $projectIds);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    // Eager load relationships to prevent N+1 queries
    $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

    // Calculate budget summaries
    $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

    // Fetch distinct project types for filters
    $projectTypes = DPReport::whereIn('project_id', $projectIds)->distinct()->pluck('project_type');

    return view('executor.ReportList', compact('reports', 'budgetSummaries', 'projectTypes'));
}
```

**Impact:** Both executors and applicants will see reports for projects they own or are in-charge of.

**Testing:**
- ✅ Executor sees reports for projects they own
- ✅ Executor sees reports for projects where they are in-charge
- ✅ Applicant sees reports for projects they own
- ✅ Applicant sees reports for projects where they are in-charge

**Estimated Time:** 30 minutes

---

#### Task 2.3: Update `pendingReports()` Method
**File:** `app/Http/Controllers/ExecutorController.php`  
**Current Code (Lines 84-120):**
```php
public function pendingReports(Request $request)
{
    $executor = Auth::user();

    // Fetch pending reports for this executor (underwriting, submitted_to_provincial, reverted_by_provincial, reverted_by_coordinator)
    $reportsQuery = DPReport::where('user_id', $executor->id)
                           ->whereIn('status', [
                               'underwriting',
                               DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_COORDINATOR,
                           ]);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    // Eager load relationships to prevent N+1 queries
    $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

    // Calculate budget summaries
    $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

    // Fetch distinct project types for filters
    $projectTypes = DPReport::where('user_id', $executor->id)
                           ->whereIn('status', [
                               'underwriting',
                               DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_COORDINATOR,
                           ])
                           ->distinct()
                           ->pluck('project_type');

    return view('executor.pendingReports', compact('reports', 'budgetSummaries', 'projectTypes'));
}
```

**Required Change:**
```php
public function pendingReports(Request $request)
{
    $user = Auth::user();

    // Get project IDs where user is owner or in-charge
    $projectIds = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })->pluck('project_id');

    // Fetch pending reports for projects where user is owner or in-charge
    $reportsQuery = DPReport::whereIn('project_id', $projectIds)
                           ->whereIn('status', [
                               'underwriting',
                               DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_COORDINATOR,
                           ]);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    // Eager load relationships to prevent N+1 queries
    $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

    // Calculate budget summaries
    $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

    // Fetch distinct project types for filters
    $projectTypes = DPReport::whereIn('project_id', $projectIds)
                           ->whereIn('status', [
                               'underwriting',
                               DPReport::STATUS_SUBMITTED_TO_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_PROVINCIAL,
                               DPReport::STATUS_REVERTED_BY_COORDINATOR,
                           ])
                           ->distinct()
                           ->pluck('project_type');

    return view('executor.pendingReports', compact('reports', 'budgetSummaries', 'projectTypes'));
}
```

**Impact:** Both executors and applicants will see pending reports for projects they own or are in-charge of.

**Testing:**
- ✅ Executor sees pending reports for projects they own
- ✅ Executor sees pending reports for projects where they are in-charge
- ✅ Applicant sees pending reports for projects they own
- ✅ Applicant sees pending reports for projects where they are in-charge

**Estimated Time:** 30 minutes

---

#### Task 2.4: Update `approvedReports()` Method
**File:** `app/Http/Controllers/ExecutorController.php`  
**Current Code (Lines 122-148):**
```php
public function approvedReports(Request $request)
{
    $executor = Auth::user();

    // Fetch approved reports for this executor
    $reportsQuery = DPReport::where('user_id', $executor->id)
                           ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    // Eager load relationships to prevent N+1 queries
    $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

    // Calculate budget summaries
    $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

    // Fetch distinct project types for filters
    $projectTypes = DPReport::where('user_id', $executor->id)
                           ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                           ->distinct()
                           ->pluck('project_type');

    return view('executor.approvedReports', compact('reports', 'budgetSummaries', 'projectTypes'));
}
```

**Required Change:**
```php
public function approvedReports(Request $request)
{
    $user = Auth::user();

    // Get project IDs where user is owner or in-charge
    $projectIds = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })->pluck('project_id');

    // Fetch approved reports for projects where user is owner or in-charge
    $reportsQuery = DPReport::whereIn('project_id', $projectIds)
                           ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR);

    // Apply filtering if provided in the request
    if ($request->filled('project_type')) {
        $reportsQuery->where('project_type', $request->project_type);
    }

    // Eager load relationships to prevent N+1 queries
    $reports = $reportsQuery->with(['user', 'project', 'accountDetails'])->get();

    // Calculate budget summaries
    $budgetSummaries = $this->calculateBudgetSummaries($reports, $request);

    // Fetch distinct project types for filters
    $projectTypes = DPReport::whereIn('project_id', $projectIds)
                           ->where('status', DPReport::STATUS_APPROVED_BY_COORDINATOR)
                           ->distinct()
                           ->pluck('project_type');

    return view('executor.approvedReports', compact('reports', 'budgetSummaries', 'projectTypes'));
}
```

**Impact:** Both executors and applicants will see approved reports for projects they own or are in-charge of.

**Testing:**
- ✅ Executor sees approved reports for projects they own
- ✅ Executor sees approved reports for projects where they are in-charge
- ✅ Applicant sees approved reports for projects they own
- ✅ Applicant sees approved reports for projects where they are in-charge

**Estimated Time:** 30 minutes

---

#### Task 2.5: Update `submitReport()` Method
**File:** `app/Http/Controllers/ExecutorController.php`  
**Current Code (Lines 67-82):**
```php
public function submitReport(Request $request, $report_id)
{
    $report = DPReport::where('report_id', $report_id)
                     ->where('user_id', Auth::id())
                     ->firstOrFail();

    // Check if report is in underwriting status
    if ($report->status !== 'underwriting') {
        return redirect()->back()->with('error', 'Report can only be submitted when in underwriting status.');
    }

    // Update report status to submitted_to_provincial
    $report->update(['status' => DPReport::STATUS_SUBMITTED_TO_PROVINCIAL]);

    return redirect()->route('executor.report.list')->with('success', 'Report submitted to provincial successfully.');
}
```

**Required Change:**
```php
public function submitReport(Request $request, $report_id)
{
    $user = Auth::user();
    
    // Get project IDs where user is owner or in-charge
    $projectIds = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })->pluck('project_id');

    $report = DPReport::where('report_id', $report_id)
                     ->whereIn('project_id', $projectIds)
                     ->firstOrFail();

    // Check if report is in underwriting status
    if ($report->status !== 'underwriting') {
        return redirect()->back()->with('error', 'Report can only be submitted when in underwriting status.');
    }

    // Update report status to submitted_to_provincial
    $report->update(['status' => DPReport::STATUS_SUBMITTED_TO_PROVINCIAL]);

    return redirect()->route('executor.report.list')->with('success', 'Report submitted to provincial successfully.');
}
```

**Impact:** Both executors and applicants can submit reports for projects they own or are in-charge of.

**Testing:**
- ✅ Executor can submit reports for projects they own
- ✅ Executor can submit reports for projects where they are in-charge
- ✅ Applicant can submit reports for projects they own
- ✅ Applicant can submit reports for projects where they are in-charge
- ❌ User cannot submit reports for projects they don't own and aren't in-charge of

**Estimated Time:** 30 minutes

---

### Phase 2 Summary
- **Files Modified:** 1 (`ExecutorController.php`)
- **Methods Updated:** 5 (`ExecutorDashboard`, `ReportList`, `pendingReports`, `approvedReports`, `submitReport`)
- **Total Estimated Time:** 2.5 hours

---

## Phase 3: ProjectController Verification and Updates

### Objective
Verify and update `ProjectController` methods to ensure applicants have full access.

### Tasks

#### Task 3.1: Verify `approvedProjects()` Method
**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Current Code (Lines 1682-1697):**
```php
public function approvedProjects()
{
    $user = Auth::user();

    // Fetch approved projects where the user is either the owner or the in-charge
    $projects = Project::where(function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('in_charge', $user->id);
    })
    ->where('status', ProjectStatus::APPROVED_BY_COORDINATOR)
    ->orderBy('project_id')
    ->orderBy('user_id')
    ->get();

    return view('projects.Oldprojects.approved', compact('projects', 'user'));
}
```

**Status:** ✅ **Already correct** - Already includes both `user_id` and `in_charge`.

**No changes needed.**

---

#### Task 3.2: Verify `index()` Method
**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Action Required:** Search for `index()` method and verify it uses `ProjectPermissionHelper::getEditableProjects()` or includes both `user_id` and `in_charge`.

**Expected:** Should use `ProjectPermissionHelper::getEditableProjects()` which we're updating in Phase 1.

**Testing:**
- ✅ Applicant sees editable projects they own
- ✅ Applicant sees editable projects where they are in-charge

**Estimated Time:** 30 minutes (verification + potential fix)

---

#### Task 3.3: Verify `show()` Method
**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Current Code (Lines 766-813):**
```php
// For executor and applicant, use ProjectPermissionHelper
$hasAccess = ProjectPermissionHelper::canView($project, $user);
```

**Status:** ✅ **Already correct** - Uses `ProjectPermissionHelper::canView()` which already works for both roles.

**No changes needed.**

---

#### Task 3.4: Verify `edit()` Method
**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Action Required:** Search for `edit()` method and verify it uses `ProjectPermissionHelper::canEdit()` or checks both `user_id` and `in_charge`.

**Expected:** Should use `ProjectPermissionHelper::canEdit()` which already works for both roles.

**Testing:**
- ✅ Applicant can edit projects they own
- ✅ Applicant can edit projects where they are in-charge

**Estimated Time:** 30 minutes (verification + potential fix)

---

#### Task 3.5: Verify `update()` Method
**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Action Required:** Search for `update()` method and verify it uses `ProjectPermissionHelper::canEdit()` or checks both `user_id` and `in_charge`.

**Expected:** Should use `ProjectPermissionHelper::canEdit()` which already works for both roles.

**Testing:**
- ✅ Applicant can update projects they own
- ✅ Applicant can update projects where they are in-charge

**Estimated Time:** 30 minutes (verification + potential fix)

---

#### Task 3.6: Verify `submitToProvincial()` Method
**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Action Required:** Search for `submitToProvincial()` method and verify it uses `ProjectPermissionHelper::canSubmit()` or checks both `user_id` and `in_charge`.

**Expected:** Should use `ProjectPermissionHelper::canSubmit()` which already works for both roles.

**Testing:**
- ✅ Applicant can submit projects they own
- ✅ Applicant can submit projects where they are in-charge

**Estimated Time:** 30 minutes (verification + potential fix)

---

### Phase 3 Summary
- **Files Modified:** 1 (`ProjectController.php`) - potentially
- **Methods Verified:** 6
- **Total Estimated Time:** 2.5 hours (if fixes needed)

---

## Phase 4: Report Controllers Verification

### Objective
Verify all report controllers allow applicants to access reports for projects they own or are in-charge of.

### Tasks

#### Task 4.1: Verify Monthly Report Controller
**File:** `app/Http/Controllers/Reports/Monthly/ReportController.php`  
**Action Required:** 
- Search for methods that filter by `user_id` or `project->user_id`
- Ensure they also check `project->in_charge`
- Verify `StoreMonthlyReportRequest` and `UpdateMonthlyReportRequest` allow applicants

**Key Methods to Check:**
- `create()`
- `store()`
- `edit()`
- `update()`
- `index()`
- `show()`

**Expected Pattern:**
```php
// Get project IDs where user is owner or in-charge
$projectIds = Project::where(function($query) use ($user) {
    $query->where('user_id', $user->id)
          ->orWhere('in_charge', $user->id);
})->pluck('project_id');

// Then filter reports by project_id
$reports = DPReport::whereIn('project_id', $projectIds)->get();
```

**Testing:**
- ✅ Applicant can create reports for projects they own
- ✅ Applicant can create reports for projects where they are in-charge
- ✅ Applicant can edit reports for projects they own
- ✅ Applicant can edit reports for projects where they are in-charge

**Estimated Time:** 1 hour

---

#### Task 4.2: Verify Quarterly Report Controllers
**Files:** 
- `app/Http/Controllers/Reports/Quarterly/DevelopmentProjectController.php`
- `app/Http/Controllers/Reports/Quarterly/DevelopmentLivelihoodController.php`
- `app/Http/Controllers/Reports/Quarterly/InstitutionalSupportController.php`
- `app/Http/Controllers/Reports/Quarterly/SkillTrainingController.php`
- `app/Http/Controllers/Reports/Quarterly/WomenInDistressController.php`

**Action Required:** Verify all quarterly report controllers allow applicants to access reports for projects they own or are in-charge of.

**Estimated Time:** 2 hours

---

#### Task 4.3: Verify Aggregated Report Controllers
**Files:**
- `app/Http/Controllers/Reports/Aggregated/AggregatedQuarterlyReportController.php`
- `app/Http/Controllers/Reports/Aggregated/AggregatedHalfYearlyReportController.php`
- `app/Http/Controllers/Reports/Aggregated/AggregatedAnnualReportController.php`

**Current Status:** Based on codebase search, these already check:
```php
if (in_array($user->role, ['executor', 'applicant'])) {
    // Executors/applicants see only their own reports
}
```

**Action Required:** Verify they filter by projects where user is owner or in-charge, not just `user_id`.

**Estimated Time:** 1.5 hours

---

### Phase 4 Summary
- **Files Modified:** Multiple report controllers
- **Total Estimated Time:** 4.5 hours

---

## Phase 5: Other Controllers and Views Verification

### Objective
Verify all other controllers and views that may need updates.

### Tasks

#### Task 5.1: Verify Export Controllers
**File:** `app/Http/Controllers/Projects/ExportController.php`  
**Action Required:** Verify `downloadPdf()` and `downloadDoc()` methods use `ProjectPermissionHelper::canView()`.

**Current Status:** Based on codebase search, these already use `ProjectPermissionHelper::canView()`.

**Estimated Time:** 30 minutes

---

#### Task 5.2: Verify IEG Budget Controller
**File:** `app/Http/Controllers/Projects/IEG_Budget_IssueProjectController.php`  
**Action Required:** Verify all methods use `ProjectPermissionHelper` methods.

**Current Status:** Based on codebase search, `show()` method already uses `ProjectPermissionHelper::canView()`.

**Estimated Time:** 1 hour

---

#### Task 5.3: Verify Views
**Files to Check:**
- `resources/views/projects/partials/actions.blade.php`
- `resources/views/projects/Oldprojects/index.blade.php`
- `resources/views/projects/Oldprojects/show.blade.php`
- `resources/views/executor/index.blade.php`
- `resources/views/executor/ReportList.blade.php`

**Action Required:** Verify views don't have hardcoded role checks that restrict applicants.

**Estimated Time:** 1 hour

---

### Phase 5 Summary
- **Files Verified:** Multiple controllers and views
- **Total Estimated Time:** 2.5 hours

---

## Phase 6: Testing and Validation

### Objective
Comprehensive testing of all changes to ensure applicants have full executor access.

### Test Scenarios

#### Test 6.1: Project Access Tests
**Scenarios:**
1. ✅ Applicant creates a project (should work)
2. ✅ Applicant edits project they created (should work)
3. ✅ Applicant edits project where they are in-charge (should work after Phase 1)
4. ✅ Applicant views project they created (should work)
5. ✅ Applicant views project where they are in-charge (should work)
6. ✅ Applicant submits project they created (should work)
7. ✅ Applicant submits project where they are in-charge (should work)
8. ❌ Applicant cannot edit project they don't own and aren't in-charge of (should fail)

**Estimated Time:** 2 hours

---

#### Test 6.2: Dashboard Tests
**Scenarios:**
1. ✅ Applicant sees approved projects they own in dashboard (should work after Phase 2)
2. ✅ Applicant sees approved projects where they are in-charge in dashboard (should work after Phase 2)
3. ✅ Applicant sees correct budget summaries (should work after Phase 2)

**Estimated Time:** 1 hour

---

#### Test 6.3: Report Access Tests
**Scenarios:**
1. ✅ Applicant creates monthly report for project they own (should work)
2. ✅ Applicant creates monthly report for project where they are in-charge (should work after Phase 4)
3. ✅ Applicant edits monthly report for project they own (should work)
4. ✅ Applicant edits monthly report for project where they are in-charge (should work after Phase 4)
5. ✅ Applicant submits monthly report for project they own (should work)
6. ✅ Applicant submits monthly report for project where they are in-charge (should work after Phase 2)
7. ✅ Applicant sees reports list for projects they own (should work after Phase 2)
8. ✅ Applicant sees reports list for projects where they are in-charge (should work after Phase 2)

**Estimated Time:** 2 hours

---

#### Test 6.4: Aggregated Report Tests
**Scenarios:**
1. ✅ Applicant generates quarterly report for projects they own (should work)
2. ✅ Applicant generates quarterly report for projects where they are in-charge (should work after Phase 4)
3. ✅ Applicant generates half-yearly report (should work after Phase 4)
4. ✅ Applicant generates annual report (should work after Phase 4)

**Estimated Time:** 1.5 hours

---

#### Test 6.5: Edge Cases
**Scenarios:**
1. ✅ Applicant who is both owner and in-charge (should see project once, not duplicate)
2. ✅ Multiple applicants on different projects (should only see their own)
3. ✅ Applicant with no projects (should see empty dashboard)
4. ✅ Applicant with projects in different statuses (should see correct filtering)

**Estimated Time:** 1 hour

---

### Phase 6 Summary
- **Test Scenarios:** 25+
- **Total Estimated Time:** 7.5 hours

---

## Phase 7: Documentation and Cleanup

### Objective
Document all changes and ensure code consistency.

### Tasks

#### Task 7.1: Update Code Comments
**Action Required:** Update method comments in `ProjectPermissionHelper` to reflect that applicants have same access as executors.

**Estimated Time:** 30 minutes

---

#### Task 7.2: Create Migration Script (if needed)
**Action Required:** If any database changes are needed (unlikely based on current analysis), create migration.

**Estimated Time:** 1 hour (if needed)

---

#### Task 7.3: Update Documentation
**Action Required:** Update any relevant documentation files that mention applicant access restrictions.

**Files to Check:**
- `Documentations/REVIEW/Code_Review_Report.md`
- `Documentations/Database_Tables_and_Relationships.md`
- Any other relevant documentation

**Estimated Time:** 1 hour

---

### Phase 7 Summary
- **Total Estimated Time:** 2.5 hours

---

## Implementation Timeline

| Phase | Description | Estimated Time | Dependencies |
|-------|-------------|----------------|--------------|
| Phase 1 | Core Permission Helper Updates | 45 minutes | None |
| Phase 2 | ExecutorController Updates | 2.5 hours | Phase 1 |
| Phase 3 | ProjectController Verification | 2.5 hours | Phase 1 |
| Phase 4 | Report Controllers Verification | 4.5 hours | Phase 1, Phase 2 |
| Phase 5 | Other Controllers and Views | 2.5 hours | Phase 1 |
| Phase 6 | Testing and Validation | 7.5 hours | All phases |
| Phase 7 | Documentation and Cleanup | 2.5 hours | All phases |
| **Total** | | **22.5 hours** | |

---

## Risk Assessment

### Low Risk
- ✅ Most permission checks already use `ProjectPermissionHelper` methods
- ✅ Routes already allow both executor and applicant roles
- ✅ `canView()`, `canEdit()`, and `canSubmit()` already work for both roles

### Medium Risk
- ⚠️ Some controllers may have direct `user_id` checks that need updating
- ⚠️ Report controllers may need significant updates
- ⚠️ Views may have hardcoded role checks

### High Risk
- ⚠️ Testing is critical - need to ensure no security holes are introduced
- ⚠️ Need to verify all edge cases are handled

---

## Success Criteria

1. ✅ Applicants can edit projects where they are in-charge (not just owner)
2. ✅ Applicants see approved projects in dashboard where they are in-charge
3. ✅ Applicants can create/edit reports for projects where they are in-charge
4. ✅ Applicants can submit reports for projects where they are in-charge
5. ✅ Applicants have identical access to executors on projects they own or are in-charge of
6. ✅ No security vulnerabilities introduced
7. ✅ All existing functionality for executors still works
8. ✅ All tests pass

---

## Notes

1. **Database Schema:** No database changes are required. The `projects` table already has both `user_id` and `in_charge` columns.

2. **Backward Compatibility:** All changes are backward compatible. Existing executor functionality will continue to work.

3. **Role-Based Access:** The Spatie Laravel Permission package is used, but the actual access control is primarily based on project ownership (`user_id`) and in-charge status (`in_charge`), not just roles.

4. **Testing Priority:** Focus testing on:
   - Projects where applicant is in-charge (not owner)
   - Reports for projects where applicant is in-charge
   - Dashboard views for applicants
   - Edge cases with multiple projects

5. **Code Review:** After implementation, conduct a thorough code review focusing on:
   - Security (no unauthorized access)
   - Performance (query optimization)
   - Consistency (same patterns used throughout)

---

## Conclusion

This implementation plan provides a comprehensive roadmap to grant applicants the same access as executors on projects where they are either the owner or in-charge. The changes are primarily focused on:

1. Updating permission helper methods
2. Updating controller queries to include `in_charge` checks
3. Verifying all controllers and views
4. Comprehensive testing

The estimated total time is **22.5 hours**, with the majority of time spent on verification, updates, and testing to ensure security and functionality.
