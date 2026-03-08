# Provincial Project List FY Scope — Feasibility Audit

**Date:** 2026-03-05  
**Pages Audited:** `/provincial/projects-list`, `/provincial/approved-projects`  
**Goal:** Determine whether FY filtering can be safely implemented without breaking existing filtering, pagination, or queries.

---

## 1. Controller Methods

### 1.1 Method: `ProvincialController::projectList()` (Projects list)

| Attribute | Value |
|-----------|-------|
| **Route** | `GET /provincial/projects-list` |
| **Name** | `provincial.projects.list` |
| **View** | `provincial.ProjectList` |
| **Pagination** | Yes — `->paginate($perPage)->withQueryString()` via `TableFormatter::resolvePerPage($request)` |
| **Sorting** | Implicit (default order from Eloquent) |

**Query flow:**
1. Base query: `Project::accessibleByUserIds($accessibleUserIds)` with filters
2. Full dataset: `(clone $baseQuery)->with(...)->get()` — for grand totals and status distribution
3. Paginated: `(clone $baseQuery)->with(...)->paginate($perPage)->withQueryString()` — for table display

**Filters applied:**
- `project_type` — `when($request->filled('project_type'), ...)`
- `user_id` — `when($request->filled('user_id'), ...)`
- `status` — `when($request->filled('status'), ...)`
- `center` — `when($request->filled('center'), whereHas('user', ...))`
- `society_id` — `when($request->filled('society_id'), ...)`

---

### 1.2 Method: `ProvincialController::approvedProjects()` (Approved projects)

| Attribute | Value |
|-----------|-------|
| **Route** | `GET /provincial/approved-projects` |
| **Name** | `provincial.approved.projects` |
| **View** | `provincial.approvedProjects` |
| **Pagination** | **No** — uses `->get()` |
| **Sorting** | Implicit |

**Query flow:**
1. Base query: `Project::accessibleByUserIds($accessibleUserIds)->approved()`
2. Filters: `place` (center), `user_id`, `project_type`
3. Result: `$projectsQuery->with(['user', 'reports.accountDetails'])->get()`

**Filters applied:**
- `place` — maps to user's `center` via `whereHas('user', ...)`
- `user_id` — `where('user_id', $request->user_id)`
- `project_type` — `where('project_type', ...)`

---

## 2. Project Query Structure

### 2.1 Projects list (`projectList`)

```
Project::accessibleByUserIds($accessibleUserIds)
    ->when(project_type) ->where('project_type', ...)
    ->when(user_id)      ->where(user_id or in_charge)
    ->when(status)       ->where('status', ...)
    ->when(center)       ->whereHas('user', center)
    ->when(society_id)   ->where('society_id', ...)
```

**Does NOT use:** `commencement_month_year` or `inFinancialYear`.

**Structure:** Builder-based; filters applied via `->when()`. Safe to chain `->inFinancialYear($fy)` before `->get()` / `->paginate()`.

---

### 2.2 Approved projects (`approvedProjects`)

```
Project::accessibleByUserIds($accessibleUserIds)
    ->approved()
    ->when(place)        ->whereHas('user', center)
    ->when(user_id)      ->where('user_id', ...)
    ->when(project_type) ->where('project_type', ...)
```

**Does NOT use:** `commencement_month_year` or `inFinancialYear`.

**Structure:** Builder-based; filters applied via `if ($request->filled(...))`. Safe to add `->inFinancialYear($fy)` after `->approved()` and before `->with(...)->get()`.

---

## 3. FY Filtering Compatibility

### 3.1 Proposed change

Add to both methods:
```php
$fy = $request->input('fy', FinancialYearHelper::currentFY());
```

Apply after access/status filters:
```php
->inFinancialYear($fy)
```

### 3.2 `scopeInFinancialYear` behavior

```php
// app/Models/OldProjects/Project.php:400-407
public function scopeInFinancialYear($query, string $fy)
{
    $start = FinancialYearHelper::startDate($fy);
    $end   = FinancialYearHelper::endDate($fy);
    return $query->whereNotNull('commencement_month_year')
        ->whereBetween('commencement_month_year', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
}
```

**Implication:** Projects with `commencement_month_year` null are excluded.

### 3.3 Compatibility summary

| Aspect | projectList | approvedProjects |
|--------|-------------|------------------|
| Pagination | ✓ Unaffected — chain before `paginate()` | N/A (no pagination) |
| Sorting | ✓ Unchanged | ✓ Unchanged |
| Status filter | ✓ Unaffected | ✓ Approved-only preserved |
| Other filters | ✓ All preserved | ✓ All preserved |
| Chaining order | Access → filters → `inFinancialYear` → get/paginate | Access → approved → filters → `inFinancialYear` → get |

**Conclusion:** FY filtering can be safely added to both methods.

---

## 4. Blade Filter Architecture

### 4.1 Projects list (`ProjectList.blade.php`)

**Existing filter form:**
- Action: `route('provincial.projects.list')`
- Method: GET
- Filters: Project Type, Team Member, Status, Center, Society
- Buttons: Apply, Reset
- Submit: Manual (button click)

**FY selector placement:** Insert as first column in the existing filter row, before Project Type. Labels are already present; add "Financial Year" with matching `form-label` pattern.

---

### 4.2 Approved projects (`approvedProjects.blade.php`)

**Existing filter form:**
- Action: `route('provincial.approved.projects')`
- Method: GET
- Filters: Project Type, Executor (user_id)
- Center/place: Not in view (controller supports `place` but Blade has no input)
- Submit: Manual (Filter button)

**FY selector placement:** Add FY as first filter column. Optionally add Center (using `place`) to align with Provincial dashboard control bar.

---

### 4.3 Recommendation

Use a control bar similar to the Provincial dashboard (Phase 3): single row, labeled selects, auto-submit on change. FY selector can be added to the first column in both forms.

---

## 5. Pagination Safety

### 5.1 Projects list

- Uses `->paginate($perPage)->withQueryString()`.
- `withQueryString()` keeps existing query params in pagination links.
- **Action required:** Add a hidden `fy` input (or ensure `fy` is in the form) so it is preserved on Apply. The per-page form uses `request()->except('per_page', 'page')` for hidden inputs — include `fy` in the main filter form; it will be preserved via `withQueryString()` when paginating.

**Pagination links:** Will include `?fy=2026-27&center=...&...&page=2` once `fy` is in the request.

---

### 5.2 Approved projects

- No pagination; uses `get()`. No pagination-related changes needed for FY.

---

## 6. Data Integrity

### 6.1 `commencement_month_year` usage

- **Scope:** `scopeInFinancialYear` uses `whereNotNull('commencement_month_year')` and `whereBetween`.
- **Effect:** Projects with null `commencement_month_year` are excluded when FY filter is applied.

### 6.2 Approved projects

- Approved projects should have `commencement_month_year` from the approval workflow.
- Legacy or migrated projects may have null values.
- **Action:** Run a data check before rollout:
  ```sql
  SELECT COUNT(*) FROM projects
  WHERE status IN ('approved_by_coordinator', ...) 
  AND commencement_month_year IS NULL;
  ```
- If count is significant, consider:
  - Backfill from `commencement_month` / `commencement_year` if available, or
  - Document that those projects are hidden when FY filter is active.

### 6.3 Recommendation

Document that FY filtering excludes projects with null `commencement_month_year`. Optionally add an "All years" choice that skips `inFinancialYear` when the user wants to see all projects including those without commencement dates.

---

## 7. Performance Considerations

### 7.1 Index usage

- Migration `2026_03_05_071759_add_project_query_indexes.php` adds `index('commencement_month_year')` on `projects`.
- `inFinancialYear` uses `whereBetween('commencement_month_year', ...)`, which can use this index.

### 7.2 Query patterns

- **projectList:** Two main queries — full dataset `get()` and paginated `paginate()`. Both would use `inFinancialYear`. Full dataset `get()` may be heavy for large provinces; FY filter reduces the result set, which helps.
- **approvedProjects:** Single `get()` — no N+1 from FY. FY filter reduces rows.
- **Filter dropdowns:** `$projectTypes`, `$centers`, etc. Consider applying FY to these queries for consistency (e.g. project types only from projects in the selected FY).

### 7.3 Recommendation

- No new N+1 or loops introduced by FY filtering.
- Index on `commencement_month_year` is in place.
- Optionally scope filter dropdowns (project types, centers) by FY for consistency.

---

## 8. Filter Persistence

### 8.1 Expected URL format

```
?fy=2026-27&center=...&role=...&project_type=...&status=...&society_id=...
```

**Note:** `approvedProjects` uses `place` for center, not `center`. To align with dashboard and `projectList`, consider standardizing to `center`.

### 8.2 Filter coexistence

| Filter | projectList | approvedProjects |
|--------|-------------|------------------|
| fy | To add | To add |
| center | ✓ `center` | `place` (semantically center) |
| role | N/A (provincial uses user_id/team member) | N/A |
| project_type | ✓ | ✓ |
| status | ✓ | N/A (approved only) |
| user_id | ✓ | ✓ |
| society_id | ✓ | N/A |

All filters can coexist with `fy` in the same GET request.

---

## 9. Implementation Recommendations

### 9.1 Controller

1. **projectList:** Add `$fy = $request->input('fy', FinancialYearHelper::currentFY())` and chain `->inFinancialYear($fy)` on `$baseQuery` after existing filters and before `get()` / `paginate()`.
2. **approvedProjects:** Same `$fy` handling and `->inFinancialYear($fy)` after `->approved()` and other filters.
3. **FY list:** Use `FinancialYearHelper::listAvailableFYFromProjects(Project::accessibleByUserIds($accessibleUserIds)->approved(), false)` and fallback to `[FinancialYearHelper::currentFY()]` when empty (consistent with Phase 2.1 dashboard).
4. **Pass to view:** Add `fy` and `fyList` (or `availableFY`) to `compact()`.

### 9.2 Views

1. **ProjectList.blade.php:** Add FY select as first filter column; ensure `fy` is submitted with the form.
2. **approvedProjects.blade.php:** Add FY select; optionally add Center using `place` to match controller.
3. Use `@foreach($fyList ?? [] as $year)` for options; default to current FY when empty.

### 9.3 Optional enhancements

- Add "All Financial Years" option (empty value) that skips `inFinancialYear` for backward compatibility.
- Add pagination to `approvedProjects` for scalability.
- Standardize center filter param to `center` on approved projects for consistency.
- Add Commencement column (as in Executor project list) to show `commencement_month_year` in the table.

---

## 10. Implementation Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| Projects with null `commencement_month_year` excluded | Medium | Document; consider "All years" option or backfill |
| Full dataset `get()` in projectList still heavy for some provinces | Low | FY filter reduces set; consider paginating grand totals in future |
| Filter param naming (`place` vs `center`) | Low | Standardize to `center` or document difference |
| approvedProjects has no pagination | Low | Add pagination when list grows |
| FY dropdown data scope | Low | Ensure `fyList` from approved projects only (Phase 2.1 pattern) |

---

## 11. Summary

| Criterion | Status |
|-----------|--------|
| FY filter can be added safely | ✓ Yes |
| Pagination preserved (projectList) | ✓ Yes |
| Existing filters unchanged | ✓ Yes |
| Index for FY filtering | ✓ Yes |
| Filter form can host FY selector | ✓ Yes |
| Query string persistence | ✓ Yes (with form update) |
| Data integrity considerations | ⚠ Projects with null commencement excluded |
| Performance | ✓ Improved by smaller result sets |

**Conclusion:** FY filtering can be implemented on both Provincial project list pages with minimal risk. Recommended order: add `inFinancialYear` in the controller, add FY dropdown and hidden/select input in both views, pass `fy` and `fyList` from the controller, and verify data coverage for `commencement_month_year` before rollout.
