# Frontend–Backend Contract Audit – Batch 8

## Purpose

This document continues the contract audit series, focusing on AdminController, AdminReadOnlyController, BudgetReconciliationController, NotificationController, API ProvinceController/CenterController, and the Province, Center, Society, and Notification models. This batch examines admin read-only flows, budget reconciliation, notifications, reference data APIs, and organizational models.

---

## AdminController Analysis

### AdminController

**Location:** `App\Http\Controllers\AdminController`

**Size:** 42 lines

**Purpose:** Admin dashboard and logout

#### Identified Contract Violations

**Violation 154: Duplicate Profile Middleware Pattern**
- **Pattern:**
  ```php
  $this->middleware(function ($request, $next) {
      $this->profileData = User::find(Auth::user()->id);
      view()->share('profileData', $this->profileData);
      return $next($request);
  });
  ```
  Same pattern as ProfileController and (likely) others — redundant User fetch on every request
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **DRY Violation** – Should use shared middleware or view composer

---

## AdminReadOnlyController Analysis

### AdminReadOnlyController

**Location:** `App\Http\Controllers\Admin\AdminReadOnlyController`

**Size:** 174 lines

**Purpose:** Admin read-only project and report lists; delegates show to ProjectController/ReportController

#### Service Design Analysis

**Strength 1: Proper Pagination on Project List**
```php
$perPage = $request->get('per_page', 50);
$currentPage = (int) $request->get('page', 1);
$projects = $projectsQuery->orderBy('created_at', 'desc')
    ->skip(($currentPage - 1) * $perPage)
    ->take($perPage)
    ->get()
```
✅ **Good:** DB-level pagination for projects

**Strength 2: Delegates show to Existing Controllers**
```php
public function projectShow(string $project_id)
{
    return app(ProjectController::class)->show($project_id);
}
```
✅ **Good:** Reuse of permission logic in ProjectController

**Strength 3: Filter Options Cached**
```php
$filterOptions = Cache::remember($filterCacheKey, now()->addMinutes(5), function () { ... });
```
✅ **Good:** Reduces repeated queries for filter dropdowns

#### Identified Contract Violations

**Violation 155: Report List Fetches All Then Slices (In-Memory Pagination)**
- **Pattern:**
  ```php
  $reports = $reportsQuery->orderBy('created_at', 'desc')->get()
      ->map(function ($report) { ... });

  $perPage = $request->get('per_page', 50);
  $currentPage = (int) $request->get('page', 1);
  $totalReports = $reports->count();
  $paginatedReports = $reports->slice(($currentPage - 1) * $perPage, $perPage)->values();
  ```
  Loads all reports into memory, then slices for "pagination"
- **Impact:** Memory and performance issues at scale; no SQL LIMIT
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ❌ **CRITICAL** – Must use query `->skip()->take()` or `->paginate()`

**Violation 156: Search Term Not Sanitized**
- **Pattern:**
  ```php
  $searchTerm = $request->search;
  $projectsQuery->where(function ($q) use ($searchTerm) {
      $q->where('project_id', 'like', '%' . $searchTerm . '%')
  ```
  Raw request input in LIKE; no validation or escape
- **Impact:** Potential SQL injection if DB driver does not bind; wildcard abuse
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Validate and bind search term

**Violation 157: Filter Inputs Not Validated**
- **Pattern:** `$request->province`, `$request->status`, `$request->project_type` used directly in queries
- **Impact:** Invalid values may cause unexpected results or errors
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Validate against allowed sets

---

## BudgetReconciliationController Analysis

### BudgetReconciliationController

**Location:** `App\Http\Controllers\Admin\BudgetReconciliationController`

**Size:** 283 lines

**Purpose:** Admin-only budget reconciliation (view discrepancies, accept/manual/reject)

#### Service Design Analysis

**Strength 1: Centralized Authorization and Feature Flag**
```php
protected function authorizeReconciliation(): void
{
    if (!$user || $user->role !== 'admin') {
        abort(403, 'Only administrators may access budget reconciliation.');
    }
    if (!config('budget.admin_reconciliation_enabled', false)) {
        abort(403, 'Budget reconciliation is not enabled.');
    }
}
```
✅ **Good:** Single method; feature flag

**Strength 2: Form Request Validation on Mutations**
```php
$request->validate([
    'overall_project_budget' => 'required|numeric|min:0',
    'amount_forwarded' => 'required|numeric|min:0',
    'local_contribution' => 'required|numeric|min:0',
    'admin_comment' => 'required|string|max:2000',
], [...]);
```
✅ **Good:** Manual correction validated

**Strength 3: Uses Service Layer**
```php
$this->correctionService->acceptSuggested($project, $admin, $request->input('admin_comment'));
$this->correctionService->manualCorrection($project, $admin, $newValues, ...);
```
✅ **Good:** Business logic in service

#### Identified Contract Violations

**Violation 158: Index Fetches All Approved Projects**
- **Pattern:**
  ```php
  $projects = $query->orderBy('updated_at', 'desc')->get();
  $rows = [];
  foreach ($projects as $project) { ... }
  ```
  No pagination on reconciliation index
- **Impact:** Memory/performance when many approved projects
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Performance** – Should paginate

**Violation 159: show() Uses Numeric id; Routes May Expose Inconsistency**
- **Pattern:** `Project::findOrFail($id)` with integer `$id` (projects table `id`)
- **Observation:** Elsewhere (e.g. projectShow) uses `project_id` (string). Budget reconciliation uses `id`. Route is `admin/budget-reconciliation/{id}`.
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract Clarity** – Document which key is used where

**Violation 160: Filter Inputs Not Validated**
- **Pattern:** `$request->project_type`, `$request->approval_date_from`, `$request->approval_date_to`, `$request->only_discrepancies` used without validation
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Validate dates and project_type

---

## NotificationController Analysis

### NotificationController

**Location:** `App\Http\Controllers\NotificationController`

**Size:** 128 lines

**Purpose:** Notification list, mark read, preferences

#### Service Design Analysis

**Strength 1: Pagination on Index**
```php
$notifications = $query->paginate(20);
```
✅ **Good:** Paginated list

**Strength 2: Preferences Validation**
```php
$validated = $request->validate([
    'email_notifications' => 'sometimes|boolean',
    'in_app_notifications' => 'sometimes|boolean',
    'notification_frequency' => 'sometimes|in:immediate,daily,weekly',
    ...
]);
$preferences->update($validated);
```
✅ **Good:** Validated and uses validated data only

**Strength 3: Service Layer for Mutations**
```php
NotificationService::markAsRead($id, Auth::id());
NotificationService::markAllAsRead(Auth::id());
NotificationService::delete($id, Auth::id());
```
✅ **Good:** Centralized logic; ownership check in service

#### Identified Contract Violations

**Violation 161: Filter/Type Not Validated**
- **Pattern:**
  ```php
  if ($request->filter === 'unread') { ... }
  if ($request->has('type')) {
      $query->where('type', $request->type);
  }
  ```
  `filter` and `type` from request not validated against allowed values
- **Impact:** Unknown type could return empty or leak info
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Validate against allowed types

**Violation 162: Notification id Route Parameter Not Validated**
- **Pattern:** `markAsRead($id)`, `destroy($id)` — `$id` from route not validated as integer/exists
- **Impact:** Service may throw or return 404; consistent 404 is acceptable but type should be validated
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Low** – Service checks ownership; add explicit validation if needed

---

## API ProvinceController Analysis

### ProvinceController (API)

**Location:** `App\Http\Controllers\Api\ProvinceController`

**Purpose:** List provinces, list centers by province

#### Identified Contract Violations

**Violation 163: Province Model Has No coordinator Relationship**
- **Pattern:**
  ```php
  if (in_array('coordinator', $includes)) {
      $query->with('coordinator:id,name,email');
  }
  ```
  Province model defines `createdBy`, `centers`, `societies`, `users`, `provincialUsers` — no `coordinator` relationship
- **Impact:** Runtime error (undefined relationship) when client sends `?include=coordinator`
- **Phase classification:** **Phase 1 – Critical Data Integrity**
- **Status:** ❌ **BUG** – Remove or implement relationship

**Violation 164: include Parameter Not Validated**
- **Pattern:** `$includes = explode(',', $request->get('include'));` — any string accepted
- **Impact:** Could request non-existent relationships; error or info leak
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Whitelist allowed includes

**Violation 165: No Pagination on index()**
- **Pattern:** `$provinces = $query->orderBy('name')->get();`
- **Impact:** Returns all provinces; acceptable if dataset is small but no contract for future growth
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Consider pagination or document as "all"

---

## API CenterController Analysis

### CenterController (API)

**Location:** `App\Http\Controllers\Api\CenterController`

**Purpose:** List centers, by province

#### Service Design Analysis

**Strength 1: Uses Scopes**
```php
if ($request->has('active') && $request->boolean('active')) {
    $query->active();
}
if ($request->has('province_id')) {
    $query->byProvince($request->integer('province_id'));
}
```
✅ **Good:** Type coercion for province_id

#### Identified Contract Violations

**Violation 166: include Parameter Not Validated**
- **Pattern:** Same as ProvinceController — `explode(',', $request->get('include'))` with no whitelist
- **Phase classification:** **Phase 2 – Input Normalization Gaps**
- **Status:** ⚠️ **Validation Gap** – Whitelist allowed includes

**Violation 167: No Pagination**
- **Pattern:** `$centers = $query->orderBy('name')->get();`
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Contract** – Document or add pagination

---

## Province, Center, Society Models Analysis

### Province Model

**Location:** `App\Models\Province`

**Strengths:** `$fillable` defined, `scopeActive`, clear relationships (centers, societies, users, provincialUsers).

**Violation 168: getAllProvincialUsers() Merges Collections in Memory**
- **Pattern:** Fetches pivot users and foreign key users, then merge/unique
- **Impact:** Two queries + in-memory merge; acceptable for small sets
- **Phase classification:** **Phase 4 – Presentation & Secondary Paths**
- **Status:** ⚠️ **Performance** – Document or optimize if provinces have many users

### Center Model

**Location:** `App\Models\Center`

**Strengths:** `$fillable`, scopes (active, byProvince, bySociety, byProvinceName).

**Violation 169: availableSocieties() Returns Query Builder, Not Relation**
- **Pattern:**
  ```php
  public function availableSocieties()
  {
      return Society::where('province_id', $this->province_id);
  }
  ```
  Returns a query builder, not a relationship — cannot eager load
- **Impact:** Inconsistent with other relationship methods; cannot `$center->availableSocieties`
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **API Inconsistency** – Consider scope on Society or document as helper

### Society Model

**Location:** `App\Models\Society`

**Violation 170: centers() Returns Query Builder, Not Relation**
- **Pattern:**
  ```php
  public function centers()
  {
      return Center::where('province_id', $this->province_id);
  }
  ```
  Same as Center::availableSocieties — not a proper Eloquent relation (no foreign key on Center to Society for this concept)
- **Impact:** Cannot eager load; naming suggests relation
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **API Inconsistency** – Document or refactor as scope

---

## Notification Model Analysis

### Notification Model

**Location:** `App\Models\Notification`

**Strengths:** `$fillable` defined, casts (is_read, read_at, metadata), scopes (unread, read, ofType), morphTo for related.

**Violation 171: related() Polymorphic Without related_type Constraint**
- **Pattern:** `related_type` and `related_id` store polymorphic target; no DB constraint on allowed types
- **Impact:** Any string in related_type; application must enforce allowed types
- **Phase classification:** **Phase 3 – Flow & Lifecycle Assumptions**
- **Status:** ⚠️ **Documentation** – Document allowed related_type values

---

## Phase-wise Issue Summary (Batch 8)

### Phase 1 – Critical Data Integrity

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 163 | Province API references non-existent coordinator relation | Api\ProvinceController | Critical |

### Phase 2 – Input Normalization Gaps

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 156 | Search term not sanitized | AdminReadOnlyController | Medium |
| 157 | Filter inputs not validated | AdminReadOnlyController | Medium |
| 160 | Filter inputs not validated | BudgetReconciliationController | Low |
| 161 | Filter/type not validated | NotificationController | Low |
| 164 | include parameter not validated | Api\ProvinceController | Medium |
| 166 | include parameter not validated | Api\CenterController | Medium |

### Phase 3 – Flow & Lifecycle Assumptions

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 155 | Report list in-memory pagination | AdminReadOnlyController | Critical |
| 158 | Reconciliation index fetches all projects | BudgetReconciliationController | Medium |
| 159 | show() uses id vs project_id | BudgetReconciliationController | Low |
| 162 | Notification id not validated | NotificationController | Low |
| 165 | No pagination on Province API index | Api\ProvinceController | Low |
| 167 | No pagination on Center API index | Api\CenterController | Low |
| 169 | availableSocieties returns query not relation | Center model | Low |
| 170 | Society::centers() returns query not relation | Society model | Low |

### Phase 4 – Presentation & Secondary Paths

| # | Issue | Component | Risk Level |
|---|-------|-----------|------------|
| 154 | Duplicate profile middleware pattern | AdminController | Low |
| 168 | getAllProvincialUsers in-memory merge | Province model | Low |
| 171 | related_type not constrained | Notification model | Low |

---

## Strengths Identified (Batch 8)

### Excellent Implementations

**1. BudgetReconciliationController Authorization**
```php
protected function authorizeReconciliation(): void
{
    if (!$user || $user->role !== 'admin') abort(403, '...');
    if (!config('budget.admin_reconciliation_enabled', false)) abort(403, '...');
}
```
✅ **Good:** Role + feature flag

**2. NotificationController Preferences Update**
```php
$validated = $request->validate([...]);
$preferences->update($validated);
```
✅ **Good:** Validated then update

**3. AdminReadOnlyController Project Pagination**
- Uses skip/take at query level for projects
- Cached filter options

**4. BudgetReconciliationController Manual Correction Validation**
- Numeric min:0, required admin_comment, max lengths

---

## Summary Statistics (Batch 8)

| Category | Count |
|----------|-------|
| Controllers analyzed | 6 (Admin, AdminReadOnly, BudgetReconciliation, Notification, Api Province, Api Center) |
| Models analyzed | 4 (Province, Center, Society, Notification) |
| New violations identified | 18 (154-171) |
| Phase 1 violations | 1 |
| Phase 2 violations | 6 |
| Phase 3 violations | 8 |
| Phase 4 violations | 3 |
| Critical issues | 2 |

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
| Batch 8 | 6 | 0 | 4 | 18 | 2 |
| **Total** | **38** | **10** | **34** | **172** | **24** |

---

## Key Takeaways (Batch 8)

### Critical Issues to Address

1. **AdminReadOnlyController reportIndex** — Loads all reports then slices; must use DB-level pagination.
2. **Api\ProvinceController** — Uses `with('coordinator')` but Province has no coordinator relationship; runtime error for `?include=coordinator`.

### Positive Patterns

1. BudgetReconciliationController: authorization helper + feature flag, validation on mutations, service layer.
2. NotificationController: pagination, validated preferences, service for mutations.
3. AdminReadOnlyController: DB pagination for projects, cached filter options, delegate show to existing controllers.

### Cross-Batch Patterns

- **In-memory "pagination"** (AdminReadOnlyController reports) mirrors risk of fetching all then limiting elsewhere.
- **Unvalidated filter/search input** appears in Admin and API controllers.
- **Include/embed parameters** in APIs need whitelisting.

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
*Batch 8 contract audit performed by: Senior Laravel Architect*
