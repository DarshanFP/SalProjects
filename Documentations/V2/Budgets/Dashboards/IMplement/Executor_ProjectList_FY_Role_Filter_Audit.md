# Executor Project List Filter Feasibility Audit

**Date:** 2026-03-05  
**Scope:** `/executor/projects` and `/executor/projects/approved`  
**Goal:** Safely introduce **Financial Year** and **Project Relationship (Role)** filters.

---

## 1. Controllers Handling Project Lists

The executor project list pages are **not** handled by `ExecutorController` or `ExecutorProjectController`. They are handled by **`App\Http\Controllers\Projects\ProjectController`** under the `executor/projects` route prefix.

| Route | Method | Controller | View |
|-------|--------|-------------|------|
| `GET /executor/projects` | `index()` | `ProjectController::index` | `projects.Oldprojects.index` |
| `GET /executor/projects/approved` | `approvedProjects()` | `ProjectController::approvedProjects` | `projects.Oldprojects.approved` |

**Relevant route group** (from `routes/web.php`):

```php
Route::prefix('executor/projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('approved', [ProjectController::class, 'approvedProjects'])->name('projects.approved');
    // ...
});
```

### 1.1 Method: `ProjectController::index()` (Pending projects)

- **Query builder used:**  
  `ProjectQueryService::getProjectsForUserQuery($user)->notApproved()->with(['user', 'objectives', 'budgets'])->get()`
- **Pagination:** **None.** Results are fetched with `->get()` and passed as a collection.
- **Status filtering:** Only non-approved projects (via `Project` model scope `scopeNotApproved()`).

### 1.2 Method: `ProjectController::approvedProjects()` (Approved projects)

- **Query builder used:**  
  `ProjectQueryService::getApprovedProjectsForUser($user)` — which internally uses `getProjectsForUserByStatus($user, APPROVED_STATUSES)` and returns `$query->get()` (a **Collection**), then `->sortBy(['project_id', 'user_id'])->values()`.
- **Pagination:** **None.** Full collection is loaded and sorted in memory.
- **Status filtering:** Only approved statuses (Coordinator, General-as-Coordinator, General-as-Provincial).

---

## 2. Current Query Architecture

### 2.1 How project datasets are fetched

| Page | Base data source | Returns | Status filter |
|------|-------------------|---------|----------------|
| Pending (`/executor/projects`) | `getProjectsForUserQuery($user)` | Builder → `get()` | `notApproved()` scope |
| Approved (`/executor/projects/approved`) | `getApprovedProjectsForUser($user)` | Collection (via `getProjectsForUserByStatus` → `get()`) | `whereIn('status', APPROVED_STATUSES)` |

- **`getProjectsForUserQuery($user)`**  
  Province-scoped; `user_id = $user->id OR in_charge = $user->id`. Returns `Builder`. Used for “all projects I own or am in-charge of.”

- **`getOwnedProjectsQuery($user)`**  
  Province-scoped; `user_id = $user->id`. Returns `Builder`. Used on executor dashboard for “owned” scope.

- **`getInChargeProjectsQuery($user)`**  
  Province-scoped; `in_charge = $user->id AND user_id != $user->id`. Returns `Builder`. Used on executor dashboard for “in-charge” scope.

- **`getApprovedProjectsForUser($user)`**  
  Uses `getProjectsForUserQuery` + status filter, then `->get()`. Returns **Collection**, so no further query chaining (e.g. FY, pagination) is possible without changing this flow.

### 2.2 Status filtering

- **Pending page:** `Project::scopeNotApproved()` — `whereNotIn('status', ProjectStatus::APPROVED_STATUSES)`.
- **Approved page:** `whereIn('status', [APPROVED_BY_COORDINATOR, APPROVED_BY_GENERAL_AS_COORDINATOR, APPROVED_BY_GENERAL_AS_PROVINCIAL])`.

Both flows are consistent with existing status constants and dashboard behaviour.

---

## 3. Financial Year Filter Feasibility

### 3.1 Existing FY scope

The `Project` model (`App\Models\OldProjects\Project`) already defines:

- **`scopeInFinancialYear($query, string $fy)`**  
  Uses `FinancialYearHelper::startDate($fy)` and `endDate($fy)`; filters on `commencement_month_year` with `whereNotNull` and `whereBetween`. Safe to apply on any project `Builder`.

### 3.2 Applying `->inFinancialYear($fy)` to project lists

- **Pending page:**  
  Base query is `getProjectsForUserQuery($user)->notApproved()...`. This is a **Builder**. You can safely chain `->inFinancialYear($fy)` before `->with(...)` and before `->get()` (or before `->paginate()` if pagination is added). No conflict with `notApproved()` or existing filters.

- **Approved page:**  
  Currently uses `getApprovedProjectsForUser($user)`, which returns a **Collection**. To support FY (and pagination), the controller must be refactored to use a **Builder** instead of the collection-returning helper, e.g.:
  - Build from the same role-based query used for the list (see Section 4), add `whereIn('status', APPROVED_STATUSES)`, then `->inFinancialYear($fy)`, then `->orderBy(...)->paginate(...)`.

**Conclusion:** Applying `->inFinancialYear($fy)` to the existing project queries is **feasible** and will not break status or province scoping. For the approved page, the only requirement is to stop using `getApprovedProjectsForUser()` for the list and use a Builder-based flow so that `inFinancialYear` and pagination can be applied.

### 3.3 Pagination and sorting

- **Current state:** Neither page uses pagination; both use `->get()` (or equivalent) and pass a full collection.
- **After adding FY (and optionally role) filters:** Introducing `->paginate($perPage)->appends($request->query())` is recommended so that:
  - Query parameters (`fy`, `role`, etc.) are preserved across page links.
  - Large result sets are not loaded at once.

Sorting is currently only on the approved page (in-memory `sortBy`). Moving to DB ordering (e.g. `->orderBy('project_id')->orderBy('user_id')`) on the Builder before `paginate()` is straightforward and keeps behaviour consistent.

---

## 4. Project Role Filter Feasibility

### 4.1 Desired options

- **Owner** — only projects where `user_id = $user->id`.
- **In-Charge** — only projects where `in_charge = $user->id` and not owner.
- **Owner + In-Charge** — combined set (each project once).

### 4.2 Using existing queries (no dataset merging)

| Role | Query to use | Notes |
|------|----------------|-------|
| **Owner** | `ProjectQueryService::getOwnedProjectsQuery($user)` | Returns Builder; no merging. |
| **In-Charge** | `ProjectQueryService::getInChargeProjectsQuery($user)` | Returns Builder; no merging. |
| **Owner + In-Charge** | `ProjectQueryService::getProjectsForUserQuery($user)` | Single query with `(user_id = X OR in_charge = X)`; no merging. |

All three return a **Builder**, so the controller can:

1. Read `role` from request (e.g. `owner`, `in_charge`, `owned_and_in_charge` or empty for “all”).
2. Choose the base query with a `match` or `switch` on `role`.
3. Apply status (notApproved for index, approved for approved page), then optional `->inFinancialYear($fy)`, then `->orderBy(...)->paginate(...)->appends($request->query())`.

No merging of collections is required; only one query is used per request.

### 4.3 Validation

- Restrict `role` to allowed values (e.g. `['owner', 'in_charge', 'owned_and_in_charge']`); default to `owned_and_in_charge` or current behaviour (`getProjectsForUserQuery`) if not specified.

---

## 5. Blade Layout Analysis

### 5.1 Actual view paths

- **Pending:** `resources/views/projects/Oldprojects/index.blade.php`  
- **Approved:** `resources/views/projects/Oldprojects/approved.blade.php`  

Both extend `executor.dashboard` and render a single card with a table. There are **no** executor-specific views under `resources/views/executor/projects/`; the user-facing “executor project list” is implemented by the above two Blade files.

### 5.2 Where to insert filters

- **Above the table**, inside the same card (e.g. below the card header, above the table) is the natural place for:
  - FY dropdown (optional; default e.g. current FY or “All”).
  - Role dropdown or radio: Owner / In-Charge / Owner + In-Charge.
- **Near the page title** (card header) is an alternative; keeping filters in the same card body as the table keeps the layout consistent with the executor dashboard (which already places FY and scope above the project tables).

### 5.3 Existing forms

- Neither Blade file currently has a filter form or GET form for filters. The executor dashboard (`executor.index`) already uses request parameters (`fy`, `scope`, `show`, `search`, etc.) with a control bar and preserves them via `->appends($request->query())` on pagination. The same pattern can be used: a simple form (GET) with selects for FY and role, submitting to the same route so that `?fy=2026-27&role=owner` (and optional `page`) are applied. No existing form needs to be removed; filters are additive.

---

## 6. Pagination Compatibility

### 6.1 Current state

- **Executor project list pages:** No pagination; full collection is passed to the view.
- **Executor dashboard:** Uses `paginate()->appends($request->query())` so that `fy`, `scope`, `search`, etc. are preserved on page links.

### 6.2 After adding filters

- Routes do **not** need to be changed. Laravel allows query parameters on any GET route; `?fy=2026-27&role=owner` and `?page=2` are already supported.
- To preserve filter (and sort) parameters on pagination links, use:

  ```php
  $projects = $query->orderBy(...)->paginate($perPage)->appends($request->query());
  ```

- Ensure the view uses the paginator’s links (e.g. `{{ $projects->links() }}`) so that “next/previous” and page numbers include `fy` and `role`. No extra route parameter support is required beyond normal query string usage.

---

## 7. FY List Source for Dropdown

### 7.1 Recommended source

Use **`FinancialYearHelper::listAvailableFYFromProjects($projectQuery)`** so the FY dropdown reflects only financial years that exist in the **current** project set (e.g. for the selected role).

- **Base query for FY list:** Use the same role-based query used for the list, e.g.  
  `ProjectQueryService::getProjectsForUserQuery($user)` (or `getOwnedProjectsQuery` / `getInChargeProjectsQuery` when a single-role filter is chosen). Do **not** apply status or FY to this “FY discovery” query so that the dropdown shows all FYs that appear in the user’s projects (optionally add an “All” option).
- **Fallback:** If `listAvailableFYFromProjects` returns an empty array, use `FinancialYearHelper::listAvailableFY()` as already done on the executor dashboard.
- **Selected FY:** If the user selects an FY that is not in the list (e.g. from a bookmark), prepend it to the list so the dropdown still shows the current selection (same pattern as executor dashboard).

This keeps the FY list consistent with the data the user can see for the chosen role.

---

## 8. Implementation Recommendation

### 8.1 Safe architecture for adding filters

1. **Controller refactor (both pages)**  
   - Use a **Builder** as the single source for the list:
     - Resolve base query from `role`: `getOwnedProjectsQuery`, `getInChargeProjectsQuery`, or `getProjectsForUserQuery`.
     - **Index:** chain `->notApproved()`.
     - **Approved:** chain `->whereIn('status', ProjectStatus::APPROVED_STATUSES)` (and optionally `->distinct()` if using `getProjectsForUserQuery`).
   - Apply optional `->inFinancialYear($fy)` when `$fy` is provided (e.g. from request; default can be `null` for “All” or `FinancialYearHelper::currentFY()`).
   - Apply `->with(...)`, `->orderBy(...)`, then `->paginate($perPage)->appends($request->query())` and pass the paginator to the view.

2. **Request parameters**  
   - `fy` (optional): e.g. `2026-27`; validate against allowed FY list or allow any `YYYY-YY` pattern.  
   - `role` (optional): `owner` | `in_charge` | `owned_and_in_charge`; default `owned_and_in_charge` (or current behaviour).  
   - Existing routes remain unchanged; no route parameter support is required beyond query strings.

3. **Views**  
   - Add a small filter form (GET) above the table in both `projects/Oldprojects/index.blade.php` and `projects/Oldprojects/approved.blade.php`: FY dropdown, Role dropdown, Submit/Apply.  
   - Use `FinancialYearHelper::listAvailableFYFromProjects($queryForFY)` (and fallback) for FY options; pass `availableFY` and current `fy`/`role` from the controller.  
   - Add pagination links using the paginator (e.g. `$projects->links()`).

4. **ProjectQueryService**  
   - No strict need for new methods. The controller can build the approved filter from the chosen role query + `whereIn('status', APPROVED_STATUSES)`. Optionally, a `getApprovedProjectsForUserQuery(User $user): Builder` could be added for clarity and reuse; it would return `getProjectsForUserQuery($user)->whereIn('status', APPROVED_STATUSES)` (and optionally `->distinct()`).

5. **Backward compatibility**  
   - If `fy` and `role` are omitted, default behaviour can match current: `getProjectsForUserQuery` + same status filters; optionally keep returning `get()` without pagination for a transition period, or introduce pagination with a default page size (e.g. 15) in the same change.

### 8.2 Summary

| Item | Feasibility | Notes |
|------|-------------|--------|
| FY filter | Yes | Use `Project::scopeInFinancialYear($fy)` on existing Builder; approved page needs Builder-based refactor. |
| Role filter | Yes | Use `getOwnedProjectsQuery` / `getInChargeProjectsQuery` / `getProjectsForUserQuery`; no merging. |
| Pagination | Yes | Add `paginate()->appends($request->query())`; preserve `fy`, `role`, and sort. |
| FY dropdown source | Yes | `FinancialYearHelper::listAvailableFYFromProjects($query)` with role-based query; fallback `listAvailableFY()`. |
| Blade placement | Yes | Above the table in the same card; add GET form for FY and role. |
| Route/query params | Yes | No route changes; use query string and `appends($request->query())` on paginator. |

Adding Financial Year and Project Relationship filters to the Executor project list pages is **feasible** with the above refactor and preserves existing behaviour when filters are not used.
