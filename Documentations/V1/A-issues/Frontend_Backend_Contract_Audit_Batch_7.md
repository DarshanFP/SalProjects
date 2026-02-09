# Frontend–Backend Contract Audit – Batch 7

## Purpose

This document continues the contract audit series, focusing on ActivityHistoryService, ReportQueryService, ActivityHistoryController, ActivityHistoryHelper, ProfileController, ProvinceFilterController, ActivityHistory model, and route/authentication patterns. This batch examines audit trails, profile management, filter state, and routing/authorization contracts.

---

## ActivityHistoryService Analysis

### ActivityHistoryService

**Location:** `App\Services\ActivityHistoryService`

**Size:** 382 lines

**Purpose:** Audit trail logging and activity feed retrieval

#### Service Design Analysis

**Strength 1: Comprehensive Logging Methods**
```php
public static function logProjectUpdate(Project $project, User $user, ?string $notes = null): void
public static function logProjectDraftSave(Project $project, User $user, ?string $notes = null): void
public static function logProjectSubmit(Project $project, User $user, ?string $notes = null): void
public static function logProjectComment(Project $project, User $user, string $comment): void
public static function logReportCreate(DPReport $report, User $user, ?string $notes = null): void
public static function logReportUpdate(DPReport $report, User $user, ?string $notes = null, ?string $previousStatus = null): void
public static function logReportDraftSave(DPReport $report, User $user, ?string $notes = null): void
public static function logReportSubmit(DPReport $report, User $user, ?string $notes = null): void
public static function logReportComment(DPReport $report, User $user, string $comment): void
```
✅ **Good:** Granular action types for audit trail

**Strength 2: Exception Swallowing with Logging**
```php
} catch (\Exception $e) {
    Log::error('Failed to log project update activity', [
        'project_id' => $project->project_id,
        'user_id' => $user->id,
        'error' => $e->getMessage(),
    ]);
}
```
✅ **Intentional:** Does not fail main operation if audit log fails

#### Identified Contract Violations

**Violation 135: Duplicate Ownership Query Logic**
- **Pattern:**
  ```php
  public static function getForExecutor(User $user): Collection
  {
      $projectIds = Project::where(function($query) use ($user) {
          $query->where('user_id', $user->id)
                ->orWhere('in_charge', $user->id);
      })->pluck('project_id');
      // ...
  }
  ```
  Same query pattern in `getForProvincial`, `getWithFilters` — duplicates ProjectQueryService
- **Impact:** If ownership logic changes, must update 4+ places
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **DRY Violation** – Should use `ProjectQueryService::getProjectIdsForUser()`

**Violation 136: No Pagination on Activity Feeds**
- **Pattern:**
  ```php
  return ActivityHistory::where(...)
      ->with('changedBy')
      ->orderBy('created_at', 'desc')
      ->get();
  ```
  Fetches all activities without limit
- **Impact:** Memory/performance issues for users with thousands of activities
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Performance** – Should paginate or limit

**Violation 137: getForCoordinator Fetches All Records**
- **Pattern:**
  ```php
  public static function getForCoordinator(): Collection
  {
      return ActivityHistory::with('changedBy')
          ->orderBy('created_at', 'desc')
          ->get();
  }
  ```
  No filters, no pagination — fetches entire activity_history table
- **Impact:** Critical performance risk at scale
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **CRITICAL** – Must paginate

**Violation 138: Hardcoded Assumption in logProjectSubmit**
- **Pattern:**
  ```php
  'previous_status' => 'draft', // Assuming submitted from draft
  ```
  Assumes project was always draft before submit
- **Impact:** Audit trail incorrect if submit from reverted status
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Incorrect Assumption** – Should use actual previous status

---

## ReportQueryService Analysis

### ReportQueryService

**Location:** `App\Services\ReportQueryService`

**Size:** 78 lines

**Purpose:** Report query abstraction delegating to ProjectQueryService

#### Service Design Analysis

**Strength 1: Delegates to ProjectQueryService**
```php
public static function getProjectIdsForUser(User $user): Collection
{
    return ProjectQueryService::getProjectIdsForUser($user);
}
```
✅ **Good:** No duplicate ownership logic

**Strength 2: Composable Query Methods**
```php
public static function getReportsForUserQuery(User $user): Builder
public static function getReportsForUser(User $user, array $with = []): Collection
public static function getReportsForUserByStatus(User $user, $statuses, array $with = []): Collection
```
✅ **Good:** Consistent API

#### Identified Contract Violations

**Violation 139: Thin Wrapper — May Be Unnecessary**
- **Observation:** ReportQueryService is a thin wrapper; many controllers use DPReport directly with ProjectQueryService
- **Impact:** Inconsistent usage — some code uses ReportQueryService, some uses raw DPReport queries
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architectural** – Either adopt fully or remove

---

## ActivityHistoryController Analysis

### ActivityHistoryController

**Location:** `App\Http\Controllers\ActivityHistoryController`

**Size:** 116 lines

**Purpose:** Activity history views (my, team, all, project, report)

#### Identified Contract Violations

**Violation 140: $request->all() Passed to Filters**
- **Pattern:**
  ```php
  public function myActivities(Request $request)
  {
      $activities = ActivityHistoryService::getWithFilters($request->all(), $user);
      return view('activity-history.my-activities', compact('activities'));
  }
  ```
  Unvalidated request data passed to service
- **Impact:** Filter parameters (type, status, date_from, date_to, search) not validated; potential injection
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Should validate filter inputs

**Violation 141: Role Check in Controller Instead of Middleware**
- **Pattern:**
  ```php
  if (!in_array($user->role, ['executor', 'applicant'])) {
      abort(403, 'Access denied');
  }
  ```
  Manual role checks in every method
- **Impact:** Repeated logic; easy to forget when adding routes
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Should use middleware** – Route groups with role middleware

**Violation 142: General Role Missing in ActivityHistoryHelper**
- **Pattern:** `ActivityHistoryHelper::canViewProjectActivity` and `canViewReportActivity` check for `admin` and `coordinator` but not `general`
  ```php
  if (in_array($user->role, ['admin', 'coordinator'])) {
      return true;
  }
  ```
  General has same access as coordinator (per routes) but helper excludes general
- **Impact:** General users may get 403 on project/report history despite route access
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **BUG** – General should be included

---

## ProfileController Analysis

### ProfileController

**Location:** `App\Http\Controllers\ProfileController`

**Size:** 65 lines

**Purpose:** Profile edit, update, password change

#### Identified Contract Violations

**Violation 143: Profile Update Without Validation**
- **Pattern:**
  ```php
  public function update(Request $request)
  {
      $id = Auth::user()->id;
      $data = User::find($id);
      $data->name = $request->name;
      $data->email = $request->email;
      $data->phone = $request->phone;
      $data->center = $request->center;
      $data->save();
      return redirect()->back()->with('success', 'Profile updated successfully');
  }
  ```
  No validation rules; direct assignment
- **Impact:** Empty strings, invalid email, XSS in name, unbounded phone/center length
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ❌ **CRITICAL** – Must validate

**Violation 144: Profile Update Bypasses Mass Assignment**
- **Pattern:** Direct property assignment instead of `$data->update($validated)` — bypasses mass assignment (which is disabled via `$guarded = []` anyway)
- **Impact:** No centralized validation; any future fields must be added manually
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Maintainability**

**Violation 145: Password Change Lacks Complexity Rules**
- **Pattern:**
  ```php
  $request->validate([
      'old_password' => 'required',
      'new_password' => 'required|confirmed',
  ]);
  ```
  No `min:8`, no uppercase/symbol/number rules
- **Impact:** Weak passwords allowed
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ⚠️ **Security** – Cross-reference Violation 112

**Violation 146: Duplicate User Lookup in Constructor and edit()**
- **Pattern:**
  ```php
  $this->middleware(function ($request, $next) {
      $this->profileData = User::find(Auth::user()->id);
      view()->share('profileData', $this->profileData);
      return $next($request);
  });
  // ...
  public function edit()
  {
      $profileData = User::find($id);  // Fetches again
      return view('profileAll.profile', compact('profileData'));
  }
  ```
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Minor** – Redundant query

---

## ProvinceFilterController Analysis

### ProvinceFilterController

**Location:** `App\Http\Controllers\ProvinceFilterController`

**Size:** 121 lines

**Purpose:** Session-based province filter for general users

#### Service Design Analysis

**Strength 1: Validates Managed Provinces**
```php
$managedProvinces = $user->managedProvinces()->pluck('provinces.id')->toArray();
$selectedProvinceIds = array_map('intval', array_intersect($selectedProvinceIds, $managedProvinces));
```
✅ **Good:** Only allows selection of provinces user manages

**Strength 2: JSON API with Proper 403**
```php
if ($user->role !== 'general') {
    return response()->json(['success' => false, 'message' => '...'], 403);
}
```
✅ **Good:** Clear error response

#### Identified Contract Violations

**Violation 147: Role Check in Controller**
- **Pattern:** Manual `$user->role !== 'general'` in every method
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Should use middleware** – Route-level restriction

**Violation 148: Session-Based Filter State**
- **Pattern:** Filter stored in session; no persistence to user preferences
- **Impact:** Filter lost on logout; different devices have different state
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **UX** – May be intentional for per-session filter

---

## ActivityHistory Model Analysis

### ActivityHistory Model

**Location:** `App\Models\ActivityHistory`

**Size:** 185 lines

**Purpose:** Audit trail entity with polymorphic-like related_id

#### Identified Contract Violations

**Violation 149: Status Labels Duplicated from Project/DPReport**
- **Pattern:**
  ```php
  $badgeClasses = [
      'draft' => 'bg-secondary',
      'submitted_to_provincial' => 'bg-primary',
      // ... 15+ statuses
  ];
  ```
  Same badge map in `getNewStatusBadgeClassAttribute` and `getPreviousStatusBadgeClassAttribute`; status labels come from Project::$statusLabels and DPReport::$statusLabels
- **Impact:** If new status added, 4+ places need updating (Project, DPReport, ActivityHistory x2)
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **DRY Violation** – Should use shared constant/config

**Violation 150: related_id Polymorphic Without Type Safety**
- **Pattern:** `related_id` stores project_id or report_id; `type` determines which
- **Impact:** `related()` returns Project or DPReport; caller must know type; no DB foreign key
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Architectural** – Consider proper polymorphic relation

---

## Route & Authentication Analysis

### Web Routes (web.php)

**Location:** `routes/web.php`

#### Identified Contract Violations

**Violation 151: Default Fallback to /profile for Unknown Roles**
- **Pattern:**
  ```php
  $url = match($role) {
      'admin' => '/admin/dashboard',
      'general' => '/general/dashboard',
      'coordinator' => '/coordinator/dashboard',
      'provincial' => '/provincial/dashboard',
      'executor' => '/executor/dashboard',
      'applicant' => '/executor/dashboard',
      default => '/profile',
  };
  ```
  Unknown roles (typo, new role not yet wired) redirect to profile instead of login or error
- **Impact:** Silent misdirection; user may not realize they have wrong role
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **UX/Security** – Should log and perhaps redirect to login

**Violation 152: Duplicate Role-to-URL Logic**
- **Pattern:** Same `match($role)` appears in:
  1. `routes/web.php` (dashboard redirect)
  2. `AuthenticatedSessionController::store()`
- **Impact:** Must update both if role URLs change
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **DRY Violation** – Extract to helper

**Violation 153: Logging on Every Dashboard Redirect**
- **Pattern:**
  ```php
  Log::info('Dashboard route - Redirecting based on role', [...]);
  Log::info('Dashboard route - Redirecting to', [...]);
  ```
  Logs on every dashboard hit
- **Impact:** Log volume; potential PII (user_id, role)
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Logging** – Consider debug level or remove in production

---

## Phase-wise Issue Summary (Batch 7)

### Phase 1 – Critical Data Integrity

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 145 | Password change lacks complexity rules | ProfileController | Medium |

### Phase 2 – Input Normalization Gaps

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 140 | $request->all() passed to filters | ActivityHistoryController | Medium |
| 143 | Profile update without validation | ProfileController | Critical |
| 144 | Profile update bypasses validation | ProfileController | Medium |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 135 | Duplicate ownership query logic | ActivityHistoryService | Medium |
| 136 | No pagination on activity feeds | ActivityHistoryService | Medium |
| 137 | getForCoordinator fetches all | ActivityHistoryService | Critical |
| 138 | Hardcoded assumption in logProjectSubmit | ActivityHistoryService | Low |
| 139 | ReportQueryService thin wrapper | ReportQueryService | Low |
| 141 | Role check in controller | ActivityHistoryController | Medium |
| 142 | General role missing in helper | ActivityHistoryHelper | Critical |
| 147 | Role check in controller | ProvinceFilterController | Low |
| 148 | Session-based filter state | ProvinceFilterController | Low |
| 150 | related_id polymorphic without type safety | ActivityHistory model | Medium |
| 151 | Default fallback for unknown roles | web.php | Medium |
| 152 | Duplicate role-to-URL logic | web.php, Auth | Low |

### Phase 4 – Presentation & Secondary Paths

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 146 | Duplicate user lookup | ProfileController | Low |
| 149 | Status labels duplicated | ActivityHistory model | Low |
| 153 | Logging on every dashboard redirect | web.php | Low |

---

## Strengths Identified (Batch 7)

### Excellent Implementations

**1. ActivityHistoryService Exception Handling**
```php
} catch (\Exception $e) {
    Log::error('Failed to log project update activity', [...]);
}
```
✅ **Good:** Audit failures do not break main flow

**2. ProvinceFilterController Province Validation**
```php
$selectedProvinceIds = array_map('intval', array_intersect($selectedProvinceIds, $managedProvinces));
```
✅ **Good:** Only managed provinces can be selected

**3. ReportQueryService Delegation**
```php
return ProjectQueryService::getProjectIdsForUser($user);
```
✅ **Good:** No duplicate ownership logic

**4. ActivityHistoryHelper Permission Check**
- Uses `ProjectPermissionHelper`-style logic for project/report access
- Clear role-based branching

---

## Summary Statistics (Batch 7)

| Category | Count |
|----------|-------|
| Controllers analyzed | 4 (ActivityHistory, Profile, ProvinceFilter) |
| Services analyzed | 2 (ActivityHistoryService, ReportQueryService) |
| Models analyzed | 1 (ActivityHistory) |
| Helpers analyzed | 1 (ActivityHistoryHelper) |
| Route/auth analyzed | 1 (web.php, AuthenticatedSessionController) |
| New violations identified | 19 (135-153) |
| Phase 1 violations | 1 |
| Phase 2 violations | 3 |
| Phase 3 violations | 12 |
| Phase 4 violations | 3 |
| Critical issues | 3 |

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
| Batch 7 | 4 | 2 | 1 | 19 | 3 |
| **Total** | **32** | **10** | **30** | **154** | **22** |

---

## Key Takeaways (Batch 7)

### Critical Issues to Address

1. **ActivityHistoryHelper excludes General** – General users may get 403 on project/report history
2. **ProfileController update has no validation** – XSS, invalid email, unbounded input
3. **getForCoordinator fetches all activities** – No pagination; full table scan

### Positive Patterns

1. ProvinceFilterController validates managed provinces before session store
2. ActivityHistoryService swallows audit exceptions so main flow continues
3. ReportQueryService delegates to ProjectQueryService (no duplicate ownership logic)

### Cross-Batch Patterns

- **Role checks in controllers** instead of middleware (Violations 121, 141, 147)
- **No pagination** on list endpoints (Violations 109, 136, 137)
- **Password complexity** missing (Violations 112, 145)
- **$request->all() / unvalidated input** (Violations 55-78, 140)

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
*Batch 7 contract audit performed by: Senior Laravel Architect*
