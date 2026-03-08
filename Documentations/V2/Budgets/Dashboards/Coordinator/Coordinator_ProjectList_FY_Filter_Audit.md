# Coordinator Project List — Financial Year Filter Audit

**Date:** 2026-03-07  
**Route:** `GET /coordinator/projects-list`  
**Controller:** `CoordinatorController::projectList()`  
**View:** `resources/views/coordinator/ProjectList.blade.php`

---

## 1. Current Behavior of the Page

The Coordinator project list at `/coordinator/projects-list` displays all projects visible to the coordinator (global oversight role). It supports:

- **Basic filters:** Search, Province, Status, Project Type
- **Advanced filters:** Provincial, Executor/Applicant, Center, Sort By, Sort Order, Start Date, End Date

**FY behavior today:**

- The controller reads `$fy` from the request with default `FinancialYearHelper::currentFY()`.
- FY is passed to `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)`, so **the backend does apply FY filtering**.
- Because there is **no FY filter in the UI**, users cannot change the FY. They always receive the default (current FY).
- The URL `?fy=2024-25` would work if typed manually, but there is no UI to select it.

---

## 2. Controller Query Analysis

### 2.1 Method Location

`CoordinatorController::projectList()` (approx. lines 496–692)

### 2.2 FY Handling

```php
$fy = $request->input('fy', \App\Support\FinancialYearHelper::currentFY());
$projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)
    ->with(['user.parent', 'reports.accountDetails', 'budgets']);
```

- FY is read from the request.
- It is passed to `getVisibleProjectsQuery($coordinator, $fy)`.

### 2.3 ProjectAccessService Behavior

In `ProjectAccessService::getVisibleProjectsQuery(User $user, ?string $financialYear = null)`:

```php
if ($financialYear !== null) {
    $baseQuery->inFinancialYear($financialYear);
}
```

FY filtering uses the `inFinancialYear($fy)` scope on the Project model (based on `commencement_month_year`).

### 2.4 Variables Passed to View

```php
return view('coordinator.ProjectList', compact(
    'projects',
    'coordinator',
    'projectTypes',
    'users',
    'provinces',
    'centers',
    'provincials',
    'statuses',
    'filterPresets',
    'pagination'
));
```

- **Not passed:** `fy`, `availableFY` (or `fyList`).
- Without these, the Blade view cannot render an FY dropdown or show the active FY.

---

## 3. Blade Filter Analysis

### 3.1 Current Filter Controls

| Filter     | Basic/Advanced | Present | Notes                          |
|------------|----------------|---------|--------------------------------|
| Search     | Basic          | Yes     |                                |
| Province   | Basic          | Yes     |                                |
| Status     | Basic          | Yes     |                                |
| Project Type | Basic        | Yes     |                                |
| Provincial | Advanced       | Yes     |                                |
| Executor/Applicant | Advanced | Yes |                       |
| Center     | Advanced       | Yes     |                                |
| Sort By    | Advanced       | Yes     |                                |
| Sort Order | Advanced       | Yes     |                                |
| Start Date | Advanced       | Yes     |                                |
| End Date   | Advanced       | Yes     |                                |
| **Financial Year** | —     | **No**  | Not implemented in the form    |

### 3.2 Active Filters Display

```blade
@if(request()->anyFilled(['search', 'province', 'status', 'project_type', 'provincial_id', 'user_id', 'center', 'start_date', 'end_date']))
```

`fy` is not included; it would not appear in the “Active Filters” section even if present in the URL.

### 3.3 Pagination

Pagination uses `request()->fullUrlWithQuery(['page' => ...])`, so query parameters (including `fy`) are preserved when changing pages.

---

## 4. Differences with Other Dashboards

### 4.1 Provincial Project List (`/provincial/projects-list`)

| Aspect             | Provincial                         | Coordinator                 |
|--------------------|------------------------------------|-----------------------------|
| FY in query        | Yes (`inFinancialYear($fy)`)       | Yes (via `getVisibleProjectsQuery`) |
| FY from request    | Yes                                | Yes                         |
| FY passed to view  | Yes (`fy`, `fyList`)               | No                          |
| FY dropdown        | Yes (first column of filter form)  | No                          |
| FY list source     | `listAvailableFYFromProjects()`    | N/A                         |
| Reset button       | FY-aware                           | No FY in form               |

### 4.2 Provincial Implementation Snapshot

```php
// Controller
$fy = $request->input('fy', FinancialYearHelper::currentFY());
$fyList = FinancialYearHelper::listAvailableFYFromProjects(
    Project::accessibleByUserIds($accessibleUserIds)->approved(),
    false
);
if (empty($fyList)) {
    $fyList = [FinancialYearHelper::currentFY()];
}
return view('provincial.ProjectList', compact(..., 'fy', 'fyList'));
```

```blade
<!-- Provincial ProjectList.blade.php -->
<div class="col-md-2">
    <label for="fy">Financial Year</label>
    <select name="fy" id="fy" class="form-select auto-filter">
        @foreach($fyList ?? [] as $year)
            <option value="{{ $year }}" {{ ($fy ?? '') == $year ? 'selected' : '' }}>FY {{ $year }}</option>
        @endforeach
    </select>
</div>
```

### 4.3 Executor Project List

The Executor project list uses different routes and controllers; FY filtering is implemented where applicable. Coordinator is most directly comparable to Provincial for FY UI patterns.

---

## 5. Architecture Implications

### 5.1 Components Involved

| Component           | Role                                              | FY support |
|---------------------|---------------------------------------------------|-----------|
| `ProjectAccessService::getVisibleProjectsQuery` | Base query for coordinator projects        | Yes (param `$financialYear`) |
| `Project::inFinancialYear()`                 | Scope on `commencement_month_year`        | Yes |
| `FinancialYearHelper`                        | FY labels, current/next FY, date ranges   | Yes |
| `DatasetCacheService`                        | Used by dashboard, not project list       | No interaction |
| Dashboard cache                              | Phase 7 coordinator dashboard cache       | No impact on project list |

### 5.2 Filter Options Cache

Coordinator project list uses:

```php
$filterCacheKey = 'coordinator_project_list_filters';
$filterOptions = Cache::remember($filterCacheKey, now()->addMinutes(5), function () {
    return [
        'provinces' => ...,
        'centers' => ...,
        'users' => ...,
        'provincials' => ...,
        'projectTypes' => Project::distinct()->whereNotNull('project_type')->pluck('project_type')->...,
        'statuses' => ...,
    ];
});
```

- `projectTypes` and other options are **not FY-scoped**.
- For consistency with Provincial, filter options could later be made FY-scoped if desired.

### 5.3 Inbound Links

Links to the coordinator project list generally omit `fy`:

- Sidebar: `route('coordinator.projects.list', ['status' => 'forwarded_to_coordinator'])`
- Pending approvals widget: same
- Coordinator dashboard “View All Projects”: `route('coordinator.projects.list')` without `fy`

So users always land with the default FY (current FY).

---

## 6. Missing Components

| Component                     | Status | Notes                                              |
|------------------------------|--------|----------------------------------------------------|
| FY filter UI (dropdown)      | Missing | No FY selector in the form                         |
| FY query parameter           | Implemented | `$request->input('fy', ...)` works if provided     |
| FY filtering in controller   | Implemented | Via `getVisibleProjectsQuery($coordinator, $fy)`   |
| FY propagation in pagination | Implicit | `fullUrlWithQuery` keeps all query params          |
| FY dropdown options          | Missing | `$availableFY` / `fyList` not computed or passed   |
| FY in active filters display | Missing | `fy` not in `anyFilled()` or badge logic           |
| FY in form submit            | Missing | Form has no `fy` field                             |

---

## 7. Recommended Fix Strategy

### 7.1 High-Level Flow

```
Controller
    ↓
read FY parameter (already done)
    ↓
apply inFinancialYear($fy) (already done via getVisibleProjectsQuery)
    ↓
build $fyList for dropdown (new)
    ↓
pass $fy, $availableFY (or fyList) to view
    ↓
Blade: add FY dropdown to filter form
    ↓
persist FY in pagination (already via fullUrlWithQuery)
```

### 7.2 Controller Changes

1. **Build FY list for coordinator scope**

   Coordinator sees all projects, so options can be based on approved projects:

   ```php
   $fyList = FinancialYearHelper::listAvailableFYFromProjects(
       Project::approved(),
       false
   );
   if (empty($fyList)) {
       $fyList = [FinancialYearHelper::currentFY()];
   }
   ```

   Or use `FinancialYearHelper::listAvailableFY()` for a fixed range.

2. **Pass variables to view**

   Add `fy` and `fyList` (or `availableFY`) to `compact()`.

3. **Ensure filter form carries FY**

   The form submits to `route('coordinator.projects.list')`; as long as the form includes an `fy` field, it will be preserved.

### 7.3 View Changes

1. Add an FY dropdown in the filter form (basic or advanced section), similar to Provincial.
2. Use `request('fy')` for `selected` and `$fyList` for options.
3. Add `fy` to `anyFilled()` for active filters and display a “FY: X” badge when set.
4. Optionally add a hidden `fy` input in the form if it lives outside the FY dropdown (for clarity).
5. Ensure pagination links continue to use `request()->fullUrlWithQuery()` so `fy` stays in the URL.

### 7.4 Inbound Links (Optional)

For consistency with dashboard FY context, links could include `fy`:

```php
route('coordinator.projects.list', array_filter([
    'status' => $status ?? null,
    'fy' => request('fy') ?? FinancialYearHelper::currentFY(),
]))
```

This is optional and can be done as a follow-up.

---

## 8. Risk Assessment

| Risk                          | Level | Mitigation                                                                 |
|-------------------------------|-------|----------------------------------------------------------------------------|
| Regression in project list    | Low   | Only add UI and pass-through; query logic already uses `$fy`               |
| Cache inconsistency           | Low   | `coordinator_project_list_filters` does not include FY; no change needed   |
| Cross-FY leakage              | Low   | FY scope is already applied; issue is only that users cannot change FY     |
| Inconsistent UX               | Medium| Coordinator differs from Provincial; adding FY dropdown aligns behavior    |
| Breaking links/bookmarks      | None  | New optional `fy` param; existing URLs keep current default behavior       |

---

## 9. Root Cause Summary

- **Why FY filter appears missing:** The backend applies FY filtering via `getVisibleProjectsQuery`, but the Blade view has no FY dropdown or related UI.
- **Why backend already supports FY:** `projectList()` reads `fy` from the request and passes it to `getVisibleProjectsQuery`, which applies `inFinancialYear($fy)` when present.
- **Gap:** The controller never builds `fyList` or passes `fy`/`availableFY` to the view, so the UI cannot expose an FY selector.

---

## 10. Files Referenced

| File | Relevance |
|------|-----------|
| `app/Http/Controllers/CoordinatorController.php` | `projectList()` method |
| `app/Http/Controllers/ProvincialController.php` | FY pattern for project list |
| `app/Services/ProjectAccessService.php` | `getVisibleProjectsQuery()` with FY |
| `app/Support/FinancialYearHelper.php` | FY helpers |
| `resources/views/coordinator/ProjectList.blade.php` | Filter form and layout |
| `resources/views/provincial/ProjectList.blade.php` | Reference implementation for FY UI |
| `routes/web.php` | Route definitions |

---

## 11. References

- Phase 3.1: Provincial Project List FY Filter — `Phase3_1_ProjectList_GlobalFYScope_Implementation.md`
- `Financial_Year_Propagation_Refactor_Report.md`
- `Coordinator_Dashboard_Architecture_Audit.md`
- `ProjectAccessService::getVisibleProjectsQuery` and `Project::inFinancialYear` documentation
