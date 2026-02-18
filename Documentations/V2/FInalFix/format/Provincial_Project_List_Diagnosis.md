# Provincial Project List Structural Diagnosis

**Date:** 2026-02-16  
**Scope:** Read-only investigation. No code modified.  
**View:** Provincial project listing used by provincial (and general) users.

---

## 1. View File

| Item | Value |
|------|--------|
| **Path** | `resources/views/provincial/ProjectList.blade.php` |
| **Route name** | `provincial.projects.list` |
| **URL** | `GET /provincial/projects-list` |
| **Controller** | `App\Http\Controllers\ProvincialController` |
| **Controller method** | `projectList(Request $request)` |

---

## 2. Controller Method

- **Method:** `ProvincialController::projectList(Request $request)`
- **Query:** `Project::whereIn('user_id', $accessibleUserIds)` with optional filters: `project_type`, `user_id`, `status`, `center` (via `whereHas('user', ...)`).
- **Data fetch:** `$projects = $projectsQuery->with(['user', 'reports.accountDetails'])->get();` — **no pagination**; all matching rows loaded.
- **Post-load:** Each project is mapped to attach `resolvedFinancials[$project_id]`, `budget_utilization`, `total_expenses`, `health_status` using `ProjectFinancialResolver` and `DerivedCalculationService`.
- **Passed to view:** `projects`, `resolvedFinancials`, `users`, `projectTypes`, `centers`, `statusDistribution`.

---

## 3. Pagination Type

| Check | Result |
|-------|--------|
| Controller uses `->paginate()`? | **No** — uses `->get()`. |
| Controller uses `->get()`? | **Yes.** |
| Controller uses `DataTables::of()` or yajra? | **No.** |
| Blade has `{{ $collection->links() }}`? | **No.** |
| DataTables JS / ajax table? | **No.** |

**Classification: D) No pagination.**

All projects matching the filters are loaded in one query and rendered in a single table. No server-side or client-side paging.

---

## 4. Numeric Columns

Numeric (currency/financial) columns in the table:

| # | Column header | Source | Alignment in view |
|---|----------------|--------|-------------------|
| 1 | Overall Project Budget | `$resolvedFinancials[$project_id]['overall_project_budget']` | `text-end` |
| 2 | Existing Funds | `$resolvedFinancials[$project_id]['amount_forwarded']` | `text-end` |
| 3 | Local Contribution | `$resolvedFinancials[$project_id]['local_contribution']` | `text-end` |
| 4 | Amount Requested | `$resolvedFinancials[$project_id]['amount_sanctioned']` | `text-end` |

- **Health** column shows a badge (good/warning/critical) with tooltip containing **Budget Utilization %** — derived in controller (`budget_utilization`, `health_status`), not a separate numeric column.
- Formatting: `format_indian_currency(..., 2)` for the four currency columns.

---

## 5. Totals Present?

| Check | Result |
|-------|--------|
| `<tfoot>` in table? | **No.** |
| Any `sum()` in Blade for table totals? | **No.** |
| Totals passed from controller (e.g. grand totals)? | **No** — controller does not pass table-level sums. |
| Summary block above table? | **No** — status cards and chart are by status count, not financial totals. |

**Conclusion:** No row totals, no tfoot, no summary block for the numeric columns. Totals are **missing** for Overall Budget, Existing Funds, Local Contribution, Amount Requested.

---

## 6. Export Present?

| Check | Result |
|-------|--------|
| Export button in view? | **No.** |
| Route like `export/provincial/projects` or similar for this list? | **No** — no list-level export route for provincial projects. |
| Excel/CSV in this view? | **No.** |

**Conclusion:** No export functionality for the provincial project list. (Per-project PDF/DOC download exists via `provincial.projects.downloadPdf` / `downloadDoc`.)

---

## 7. Province Isolation Status

- **Mechanism:** Access is **not** a simple `where('province_id', auth()->user()->province_id)` on projects. Projects do not have a `province_id` column; isolation is by **user hierarchy**.
- **Implementation:** `getAccessibleUserIds($provincial)` returns:
  - **Direct children:** `User::where('parent_id', $provincial->id)->whereIn('role', ['executor', 'applicant'])->pluck('id')`.
  - **General users:** If `$provincial->role === 'general'`, also users in **managed provinces** (with optional session `province_filter_ids` / `province_filter_all`), i.e. `User::whereIn('province_id', $provincesToUse)->whereIn('role', ...)`.
- **Query:** `Project::whereIn('user_id', $accessibleUserIds)` — so only projects whose `user_id` is in the accessible set are shown.
- **Verification:** `showProject()` enforces the same rule: it loads the project then checks `in_array($project->user_id, $accessibleUserIds->toArray())` and aborts 403 if not allowed.

**Status:** Province/hierarchy isolation is applied via **getAccessibleUserIds()** (no direct `province_id` on projects; access is by parent_id and, for general, managed provinces). No dedicated ProjectQueryService used in `projectList`.

---

## 8. Performance Observations

| Item | Finding |
|------|--------|
| **Dataset size** | Unbounded per request — all matching projects are loaded with `->get()`. Size depends on number of accessible users and their projects. |
| **Eager loading** | **Yes:** `->with(['user', 'reports.accountDetails'])` — reduces N+1 for user and reports. |
| **N+1 risk** | **Low** for user/reports. **Moderate** in the post-load `map()`: each project calls `$resolver->resolve($project)` and loops `$project->reports` (already loaded); no extra queries per project if resolver is in-memory. If resolver or report logic triggers lazy loads, N+1 could appear. |
| **Memory** | All projects and their relations held in memory; no chunking or cursor. |
| **Scalability** | As project count grows, response time and memory will increase. Pagination and/or summary-only queries would improve scalability. |

---

## 9. Migration Complexity Level

**Assessment: Medium.**

| Factor | Notes |
|--------|--------|
| **Pagination** | Currently none; introducing `paginate()` + `withQueryString()` and `TableFormatter::resolvePerPage()` is straightforward. Requires passing paginator, `currentPerPage`, and optional `allowPageSizeSelector`. |
| **Serial column** | Add S.No. (e.g. `$loop->iteration` or pagination-aware); simple. |
| **Totals** | Add tfoot or summary block. Totals must be computed from **resolved** financials (not raw DB columns). Controller would need to either (a) sum the same `resolvedFinancials` it already builds, or (b) expose a dedicated aggregate (e.g. from resolver/service). Medium effort. |
| **Export** | New route + controller (and optional service); query must reuse same filters and same resolved-financial logic for consistency. Medium effort. |
| **Component swap** | Replacing the table with `<x-financial-table>` would require mapping current columns + `resolvedFinancials` into a flat structure (e.g. one row per project with columns from resolver). Doable but more invasive. |
| **Province isolation** | No change needed; keep using `getAccessibleUserIds()`. |

---

## 10. Recommended Migration Strategy

1. **Phase 1 – Pagination (no UI change to table structure)**  
   - In controller: replace `->get()` with `$perPage = TableFormatter::resolvePerPage($request);` and `->paginate($perPage)->withQueryString()`.  
   - Pass `currentPerPage`, `allowedPageSizes`, and the paginator to the view.  
   - In Blade: add `{{ $projects->links() }}` below the table.  
   - Keeps existing table markup; improves performance and sets up for page-size selector later.

2. **Phase 2 – Serial + totals**  
   - Add S.No. column (first column); use `TableFormatter::resolveSerial($loop, $projects, true)` when paginated.  
   - In controller: compute grand totals from the **same** `resolvedFinancials` (or from a single aggregated resolution pass) and pass `grandTotals` and `totalRecordCount` to the view.  
   - Add either a `<tfoot>` row with sums for the four currency columns or a summary block above the table (e.g. `<x-table-summary>`).

3. **Phase 3 – Optional page size selector and export**  
   - If using a table component: set `allowPageSizeSelector="true"` and `allowExport="true"` with a dedicated `exportRoute`.  
   - Implement export route/controller that reuses the same filters and same resolved-financial logic, with chunking for large result sets.

4. **Phase 4 – Optional component migration**  
   - If migrating to `<x-financial-table>`, build a collection of objects (or arrays) that expose: project_id, team member, role, center, title, type, overall_budget, existing_funds, local_contribution, amount_requested, health, status, plus action URLs. Map `resolvedFinancials` into those keys so the component can render and total them.

---

**End of diagnosis. No code was modified.**
