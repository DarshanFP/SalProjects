# Coordinator Approved Projects Page — Architecture Audit

**Date:** 2026-03-07  
**Route:** `GET /coordinator/approved-projects`  
**Controller:** `CoordinatorController::approvedProjects()`  
**View:** `resources/views/coordinator/approvedProjects.blade.php`  
**Reference:** Coordinator project list (`/coordinator/projects-list`) enhanced implementation

---

## 1. Current Implementation

### 1.1 Controller Method

`CoordinatorController::approvedProjects()` (lines ~2652–2708):

- **Base query:** `Project::approved()->distinct()->pluck('project_id')` then `Project::whereIn('project_id', $projectIds)`
- **Filters:** province, project_type, user_id (executor)
- **No FY** parameter read or applied
- **No pagination** — uses `->get()` (all rows returned)
- **Resolver:** Per-project `$resolver->resolve($project)` in a loop (N calls)
- **Filter options:** `projectTypes` from `Project::distinct()`, `provinces` from `User::distinct()`, `users` from `User::where('role','executor')`

### 1.2 View

- **Filters:** Province, Project Type, Executor
- **Filter button:** Manual "Filter" (no auto-filter)
- **No FY dropdown**
- **No Center filter**
- **No Active Filters** section
- **No pagination** UI
- **Province → Executor:** jQuery AJAX to `coordinator.executors.byProvince` (executor options loaded on province change)

### 1.3 Variables Passed to View

```php
compact('projects', 'coordinator', 'projectTypes', 'users', 'provinces', 'resolvedFinancials')
```

Missing: `fy`, `fyList`, `centers`, `pagination`.

---

## 2. Missing Features Compared to Project List Page

| Feature | Project List | Approved Projects |
|---------|--------------|-------------------|
| FY filter | Yes | **No** |
| FY dropdown | Yes | **No** |
| fyList generation | Yes | **No** |
| fy, fyList passed to view | Yes | **No** |
| Center filter | Yes (advanced) | **No** |
| Active Filters section | Yes | **No** |
| FY in active filters | Yes | N/A |
| Pagination | Yes (100 per page) | **No** |
| Pagination query string preservation | Yes | N/A |
| Auto-filter (dropdown submit on change) | Yes | **No** |
| Clear button | Yes | **No** |
| ProjectAccessService | Yes | **No** (raw `Project::` queries) |
| resolveCollection (batch) | Yes (via projectList) | **No** (per-project resolve loop) |
| Filter options cache | Yes (`coordinator_project_list_filters`) | **No** |
| Search | Yes | **No** |
| Sort By / Order | Yes | **No** |
| Provincial filter | Yes | **No** |

---

## 3. FY Filtering Behavior

| Aspect | Status | Notes |
|--------|--------|-------|
| `$fy = request('fy', ...)` | Missing | Not read |
| `inFinancialYear($fy)` | Missing | Not applied |
| FY list for dropdown | Missing | Not generated |
| FY passed to view | Missing | Not in compact() |

Approved projects shows all approved projects across all financial years. No FY scoping.

---

## 4. UI Filter Availability

| Filter | Present | Notes |
|--------|---------|-------|
| Financial Year | No | — |
| Province | Yes | Yes |
| Project Type | Yes | Yes |
| Center | No | — |
| Executor | Yes | Yes (user_id) |
| Search | No | — |
| Provincial | No | — |
| Sort By | No | — |
| Filter button | Yes | Manual submit; no auto-filter |
| Clear button | No | — |

---

## 5. Pagination Propagation

| Aspect | Status |
|--------|--------|
| Pagination | Not implemented |
| `withQueryString()` / `fullUrlWithQuery()` | N/A — no pagination |
| Query parameter preservation | N/A |

All approved projects are loaded in one request. For large datasets this may affect performance and memory.

---

## 6. Filter Options Scope

| Option | Source | FY-Scoped? |
|--------|--------|------------|
| projectTypes | `Project::distinct()->pluck('project_type')` | No |
| provinces | `User::distinct()->pluck('province')` | No |
| users | `User::where('role','executor')->...` | No (filtered by province when selected) |
| centers | Not provided | — |

Filter options are not limited by FY or by approved projects in the selected FY.

---

## 7. Query Architecture

| Aspect | Project List | Approved Projects |
|--------|--------------|-------------------|
| Base query | `ProjectAccessService::getVisibleProjectsQuery($coordinator, $fy)` | `Project::approved()->pluck()` + `whereIn()` |
| FY scope | Via `getVisibleProjectsQuery` → `inFinancialYear` | None |
| Access control | Centralized in ProjectAccessService | Direct `Project::approved()` |

Approved projects does not use `ProjectAccessService`. It uses direct `Project::` queries. For coordinators this is effectively global, but it diverges from the project list and other coordinator views.

---

## 8. Performance Observations

| Observation | Impact |
|-------------|--------|
| No pagination | All approved projects loaded; risk for large datasets |
| Per-project resolver loop | N resolver calls instead of `resolveCollection` |
| No filter options cache | Filter dropdowns rebuilt every request |
| Two-step query | `pluck('project_id')` then `whereIn` — acceptable but could be a single query |

---

## 9. Recommended Improvements

### 9.1 High Priority (Architecture Consistency)

1. **Add FY filtering**
   - Read `$fy = request('fy', FinancialYearHelper::currentFY())`
   - Apply `->inFinancialYear($fy)` to approved projects query
   - Generate `fyList` with `FinancialYearHelper::listAvailableFYFromProjects(Project::approved(), false)`
   - Pass `fy` and `fyList` to the view

2. **Use ProjectAccessService**
   - Replace base query with `$this->projectAccessService->getVisibleProjectsQuery($coordinator, $fy)->whereIn('status', ProjectStatus::APPROVED_STATUSES)` or equivalent
   - Keeps coordinator access logic consistent with project list

3. **Batch resolver**
   - Replace per-project `$resolver->resolve($project)` loop with `ProjectFinancialResolver::resolveCollection($projects)`
   - Reduces resolver calls and aligns with project list

### 9.2 Medium Priority (UX Parity)

4. **Add FY dropdown**
   - Add FY select to the filter form
   - Use same pattern as project list

5. **Add Center filter**
   - Add Center dropdown (from users with approved projects in FY, or from `User` with center filter)
   - Apply `whereHas('user', fn => where('center', $request->center))` when filled

6. **Auto-filter**
   - Add `auto-filter` class to dropdowns
   - Add auto-submit script
   - Remove Filter button and add Clear button

7. **Active Filters section**
   - Add `request()->anyFilled([...])` check
   - Show badges for active filters (fy, province, project_type, user_id, center)

### 9.3 Lower Priority (Scalability)

8. **Pagination**
   - Replace `->get()` with `->paginate($perPage)->withQueryString()`
   - Add pagination UI and pass `$pagination` to view
   - Use `request()->fullUrlWithQuery(['page' => ...])` to keep filters

9. **Filter options scope**
   - Scope `projectTypes` by FY: `Project::approved()->inFinancialYear($fy)->distinct()->pluck('project_type')`
   - Scope `users` by users with approved projects in FY
   - Add `centers` from users with approved projects in FY

10. **Filter options cache**
    - Consider `Cache::remember('coordinator_approved_projects_filters_' . $fy, 5, ...)` for filter options
    - Lower priority if FY-scoped options are lightweight

---

## 10. Summary

The approved projects page is missing FY filtering, ProjectAccessService, batch resolution, pagination, auto-filter, Active Filters, Center filter, and Clear button relative to the project list. Aligning it with the project list architecture would improve consistency, performance, and scalability.
