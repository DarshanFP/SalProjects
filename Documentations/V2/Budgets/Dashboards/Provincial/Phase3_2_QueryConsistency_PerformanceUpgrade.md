# Phase 3.2 — Provincial Query Consistency + Performance Upgrade

**Date:** 2026-03-05  
**Phase:** Provincial Dashboard Architecture  
**Goals:**
1. Ensure dropdown filters respect Financial Year scope
2. Introduce pagination for approved projects
3. Prepare the Provincial dashboard for ultra-scale performance via shared dataset service

---

## Summary

- **FY-scoped dropdowns:** Project types, centers, and team members (projectList) now reflect only the currently selected FY dataset.
- **Approved projects pagination:** Replaced `get()` with `paginate(25)->withQueryString()`; pagination links added to view.
- **ProjectQueryService::forProvincial():** New method returns FY-scoped project query for provincial scope.
- **Shared dataset:** Provincial dashboard loads team projects once and passes to widget methods, eliminating redundant queries.

---

## 1. FY Filter for Dropdown Queries

### 1.1 projectList()

| Dropdown     | Before                               | After                                                  |
|-------------|---------------------------------------|--------------------------------------------------------|
| projectTypes| `Project::accessible...->distinct()`   | `Project::accessible...->inFinancialYear($fy)->distinct()` |
| centers     | `User::whereIn(id, accessibleUserIds)`| `User::whereIn(id, userIdsWithProjectsInFy)`           |
| users       | Unchanged                             | Unchanged                                              |

**userIdsWithProjectsInFy:** Users who have at least one project (as owner or in-charge) in the selected FY.

### 1.2 approvedProjects()

| Dropdown   | Before                                          | After                                                                 |
|-----------|--------------------------------------------------|-----------------------------------------------------------------------|
| projectTypes | `Project::accessible...->approved()->distinct()` | `Project::accessible...->approved()->inFinancialYear($fy)->distinct()` |
| places    | `User::whereHas('projects', approved)`           | `User::whereHas('projects', approved & inFinancialYear($fy))`         |

### 1.3 provincialDashboard()

| Dropdown     | Before                               | After                                                       |
|-------------|---------------------------------------|-------------------------------------------------------------|
| projectTypes| `Project::accessible...->approved()`  | `ProjectQueryService::forProvincial()->approved()->distinct()` |
| centers     | `User::whereIn(id, accessibleUserIds)`| `User::whereIn(id, userIdsWithProjectsInFy)`                |

---

## 2. Pagination for Approved Projects

### 2.1 Controller

**Before:**
```php
$projects = $projectsQuery->with(['user', 'reports.accountDetails'])->get();
```

**After:**
```php
$projects = $projectsQuery
    ->with(['user', 'reports.accountDetails'])
    ->paginate(25)
    ->withQueryString();
```

- **Page size:** 25
- **Query string:** `withQueryString()` keeps FY, project_type, user_id, etc. in pagination links

### 2.2 View

Added pagination links:
```blade
@if(isset($projects) && method_exists($projects, 'links'))
    <div class="card-footer d-flex justify-content-end mt-3">
        {{ $projects->links() }}
    </div>
@endif
```

---

## 3. ProjectQueryService::forProvincial()

**File:** `app/Services/ProjectQueryService.php`

```php
public static function forProvincial(User $provincial, string $fy): Builder
{
    $accessibleUserIds = app(ProjectAccessService::class)->getAccessibleUserIds($provincial);
    return Project::accessibleByUserIds($accessibleUserIds)->inFinancialYear($fy);
}
```

**Returns:** Builder for projects where owner or in-charge is in the provincial’s scope, and `commencement_month_year` falls within the given FY.

**Usage:**
- Provincial dashboard base query
- Reusable for consistent FY and access scoping

---

## 4. Shared Dataset for Dashboard Widgets

### 4.1 provincialDashboard() flow

1. **Base query:**
   ```php
   $baseProjectsQuery = ProjectQueryService::forProvincial($provincial, $fy);
   ```

2. **Approved projects (with filters):**
   ```php
   $projectsQuery = (clone $baseProjectsQuery)->approved();
   // + center, role, project_type filters
   $projects = $projectsQuery->with(...)->get();
   ```

3. **Shared dataset for widgets:**
   ```php
   $teamProjectsInFy = $baseProjectsQuery->with(['user', 'reports.accountDetails'])->get();
   ```

4. **Widget calls:**
   ```php
   $performanceMetrics = $this->calculateTeamPerformanceMetrics($provincial, $fy, $teamProjectsInFy);
   $chartData = $this->prepareChartDataForTeamPerformance($provincial, $fy, $teamProjectsInFy);
   $centerPerformance = $this->calculateCenterPerformance($provincial, $fy, $teamProjectsInFy);
   $budgetData = $this->calculateEnhancedBudgetData($provincial, $fy, $teamProjectsInFy);
   $centerComparison = $this->prepareCenterComparisonData($provincial, $fy, $teamProjectsInFy);
   ```

### 4.2 Widget method changes

Each method accepts an optional `$teamProjects` parameter:

- `calculateTeamPerformanceMetrics($provincial, $fy, $teamProjects = null)`
- `prepareChartDataForTeamPerformance($provincial, $fy, $teamProjects = null)`
- `calculateCenterPerformance($provincial, $fy, $teamProjects = null)`
- `calculateEnhancedBudgetData($provincial, $fy, $teamProjects = null)`
- `prepareCenterComparisonData($provincial, $fy, $teamProjects = null)`

If `$teamProjects` is null, the method fetches its own data (unchanged behavior). When provided, it uses the shared dataset.

### 4.3 Query reduction

**Before:** Each widget ran its own `Project::accessibleByUserIds()->inFinancialYear($fy)->...->get()`.

**After:** Single `$teamProjectsInFy` load shared across widgets.

---

## 5. Performance Safety

| Check                                  | Status |
|----------------------------------------|--------|
| No extra queries introduced            | ✓ Single load for widgets |
| Dataset reused across widget methods   | ✓ |
| Pagination preserved (projectList)     | ✓ Existing `withQueryString()` |
| Pagination added (approvedProjects)    | ✓ 25 per page |
| Dropdown filters populate correctly    | ✓ FY-scoped |
| Backward compatibility                 | ✓ Optional `$teamProjects` param |

---

## 6. Files Modified

| File                                             | Changes |
|--------------------------------------------------|---------|
| `app/Services/ProjectQueryService.php`           | Added `forProvincial($provincial, $fy)` |
| `app/Http/Controllers/ProvincialController.php`  | FY dropdowns, shared dataset, pagination for approved, widget method signatures |
| `resources/views/provincial/ProjectList.blade.php` | No changes (dropdowns driven by controller) |
| `resources/views/provincial/approvedProjects.blade.php` | Pagination links in footer |

---

## 7. Implementation Verification

| Item                                  | Status |
|--------------------------------------|--------|
| FY filter on projectTypes (projectList, approvedProjects, dashboard) | ✓ |
| FY filter on centers/places          | ✓ |
| Pagination on approved projects      | ✓ |
| `ProjectQueryService::forProvincial()`| ✓ |
| Shared dataset for dashboard widgets | ✓ |
| Widget methods accept optional dataset | ✓ |
