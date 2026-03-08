# Coordinator Approved Projects — Architecture Upgrade

**Date:** 2026-03-07  
**Route:** `GET /coordinator/approved-projects`  
**Controller:** `CoordinatorController::approvedProjects()`  
**View:** `resources/views/coordinator/approvedProjects.blade.php`

---

## 1. Implementation Overview

The Coordinator approved projects page has been upgraded to align with the Coordinator project list architecture. Changes include:

- FY filtering via `ProjectAccessService` and `inFinancialYear`
- Pagination (100 per page) with query string preservation
- Batch `resolveCollection()` instead of per-project resolver loop
- Filter options cache (FY-scoped for projectTypes)
- FY dropdown, Center filter, auto-filter UI
- Active Filters display and Clear button

---

## 2. Controller Refactor

### 2.1 Before

- Base query: `Project::approved()->pluck('project_id')` + `Project::whereIn()`
- No FY filter
- No pagination
- Per-project `$resolver->resolve($project)` loop
- No filter options cache
- No Center filter

### 2.2 After

- Base query: `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)->whereIn('status', ProjectStatus::APPROVED_STATUSES)`
- FY from request with `FinancialYearHelper::currentFY()` default
- Pagination: `->paginate(100)->withQueryString()`
- `ProjectFinancialResolver::resolveCollection($projects->getCollection())`
- Filter options cache: `coordinator_approved_projects_filters_{$fy}`, 5 min TTL
- Center filter via `whereHas('user', fn => where('center', $request->center))`

---

## 3. ProjectAccessService Integration

Base query:

```php
$projectsQuery = $this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)
    ->whereIn('status', ProjectStatus::APPROVED_STATUSES);
```

- Uses centralized access logic
- FY is passed to `getVisibleProjectsQuery`, which applies `inFinancialYear($fy)`
- Status restricted to `ProjectStatus::APPROVED_STATUSES`
- Aligns with project list and other coordinator views

---

## 4. FY Filtering

| Component | Implementation |
|-----------|----------------|
| FY from request | `$fy = $request->input('fy', FinancialYearHelper::currentFY())` |
| FY list | `FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false)` |
| Fallback | `[FinancialYearHelper::currentFY()]` if empty |
| Query scope | Via `getVisibleProjectsQuery($coordinator, $fy)` → `inFinancialYear($fy)` |

---

## 5. Pagination Architecture

```php
$projects = $projectsQuery
    ->with(['user.parent', 'reports.accountDetails', 'budgets'])
    ->latest()
    ->paginate(100)
    ->withQueryString();
```

- 100 projects per page
- `withQueryString()` keeps FY, province, project_type, user_id, center in links
- Eager loads relations for resolver and view

---

## 6. Resolver Batch Processing

**Before:** N calls to `$resolver->resolve($project)`

**After:**

```php
$resolvedFinancials = ProjectFinancialResolver::resolveCollection($projects->getCollection());
```

- One batch resolution for the current page
- Uses same map keyed by `project_id`

---

## 7. Filter Options Cache

| Key | TTL | Contents |
|-----|-----|----------|
| `coordinator_approved_projects_filters_{$fy}` | 5 min | projectTypes (FY-scoped), provinces, users, centers |

- `projectTypes`: `Project::approved()->inFinancialYear($fy)->distinct()->pluck('project_type')`
- `provinces`: `User::distinct()->whereNotNull('province')->pluck('province')`
- `users`: `User::whereIn('role', ['executor', 'applicant'])->get()`
- `centers`: `User::distinct()->whereNotNull('center')->where('center','!=','')->pluck('center')`

---

## 8. Auto Filter UI

- All filter dropdowns use `auto-filter`
- Form submits on change
- Script:

```javascript
document.querySelectorAll('.auto-filter').forEach(function(el) {
    el.addEventListener('change', function() {
        if (filterSubmitting) return;
        filterSubmitting = true;
        this.closest('form').submit();
    });
});
```

- Filter button removed; Clear button added

---

## 9. Active Filters Display

- Shown when `request()->anyFilled(['fy','province','project_type','user_id','center'])`
- Badges for FY, Province, Project Type, Center, Executor
- “Clear All” link to reset filters

---

## 10. Performance Improvements

| Improvement | Impact |
|-------------|--------|
| `resolveCollection` | 1 batch call instead of N resolver calls |
| Filter options cache | Fewer DB queries for dropdowns |
| Pagination | Limited rows per request |
| Eager loading | Reduces N+1 queries |

---

## 11. Files Modified

| File | Changes |
|------|---------|
| `app/Http/Controllers/CoordinatorController.php` | Refactored `approvedProjects()`: ProjectAccessService, FY, pagination, `resolveCollection`, filter cache |
| `resources/views/coordinator/approvedProjects.blade.php` | FY dropdown, Center, auto-filter, Active Filters, pagination, removed jQuery AJAX |

---

## 12. Removed

- Province → Executor AJAX (replaced by server-side filters and cache)
- jQuery dependency for this page (replaced by plain JS in `@push('scripts')`)
- Manual Filter button
