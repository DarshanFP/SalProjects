# Provincial Project List — Pilot Migration (Phase A)

**Scope:** Safe, incremental changes. Only `ProvincialController::projectList` and `resources/views/provincial/ProjectList.blade.php` modified. No global refactor.

---

## 1. Changes Made

### Controller (`ProvincialController::projectList`)

- **Base query**  
  Single chain with `whereIn('user_id', $accessibleUserIds)` and `->when(...)` for filters (project_type, user_id, status, center). Cloned before any `get()` or `paginate()` so filters and province isolation are applied consistently.

- **Full dataset for totals**  
  `$fullDataset = (clone $baseQuery)->with(['user', 'reports.accountDetails'])->get()`. Used to:
  - Run `ProjectFinancialResolver` and health/utilization logic on the full filtered set.
  - Build `$resolvedFinancials` (keyed by `project_id`) and `$grandTotals` (overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned).
  - Compute `$totalRecordCount` and `$statusDistribution`.

- **Pagination**  
  `$perPage = TableFormatter::resolvePerPage($request)`; listing query: `(clone $baseQuery)->with([...])->paginate($perPage)->withQueryString()`. Current page collection is transformed to attach `budget_utilization`, `total_expenses`, `health_status` using existing `$resolvedFinancials` (no second resolver run on full set).

- **New view variables**  
  `$projects` (LengthAwarePaginator), `$grandTotals`, `$totalRecordCount`, `$currentPerPage`, `$allowedPageSizes`, plus existing `resolvedFinancials`, `users`, `projectTypes`, `centers`, `statusDistribution`.

### View (`resources/views/provincial/ProjectList.blade.php`)

- **S.No column**  
  First column; serial number via `TableFormatter::resolveSerial($loop, $projects, $projects->hasPages())` so numbering is correct across pages.

- **Summary block (above table)**  
  Shows: Total Records, Total Overall Budget, Total Existing Funds, Total Local Contribution, Total Amount Requested. Values from `$grandTotals` and `$totalRecordCount`; currency formatted with `format_indian_currency(..., 2)`.

- **Project ID**  
  Wrapped with `route('projects.show', $project->project_id)` so the ID is clickable and goes to the default project show route.

- **Page size selector**  
  Above the table: GET form with dropdown of `$allowedPageSizes`. Hidden inputs preserve all current filters (except `per_page` and `page`); form submits on change.

- **Export button**  
  “Download Excel” links to `route('provincial.projects.export', request()->query())` so current filters are passed. Export logic is not implemented; route returns 501.

- **Pagination links**  
  Rendered in card footer via `$projects->links()` when available.

- **Empty state**  
  Colspan updated to 14 to include the new S.No column.

### Route

- **Placeholder**  
  `GET /provincial/projects-list/export` → `ProvincialController@projectsExport` named `provincial.projects.export`. Method returns `abort(501, 'Export not implemented yet.')`. Query string is available in `$request->query()` for future implementation.

---

## 2. Performance Impact

- **Full dataset load**  
  Grand totals and status distribution are computed from the full filtered result set (`$fullDataset`). For large provinces or heavy filters this means:
  - One full query + resolver run over all matching rows.
  - Memory and CPU scale with filtered count, not page size.
- **Listing**  
  Only the current page is loaded for the table; pagination reduces rows per request.
- **Recommendation**  
  If filtered counts grow large (e.g. thousands), consider computing totals via aggregate queries or a dedicated summary endpoint instead of loading all rows. This pilot keeps the “full dataset for totals” approach as specified.

---

## 3. Province Isolation Verification

- **Unchanged**  
  `getAccessibleUserIds($provincial)` is still the single entry point for who the provincial user can see (direct children +, for general users, province-filtered users). No changes to this helper or to province/session logic.
- **Scoping**  
  All project queries use `Project::whereIn('user_id', $accessibleUserIds)`. Filters (project_type, user_id, status, center) are applied on top of that. Base query is cloned for both full dataset and paginated query, so both see the same province-scoped, filtered set.
- **Export placeholder**  
  When export is implemented, it must use the same `getAccessibleUserIds` and the same base filters (from query string) so export remains province-isolated.

---

## 4. Rollback Plan

1. **Controller**  
   Revert `ProvincialController::projectList` to the previous version that:
   - Built one query without cloning, called `->get()` on it, and ran resolver/map on that collection.
   - Did not pass `grandTotals`, `totalRecordCount`, `currentPerPage`, `allowedPageSizes`.
2. **View**  
   Revert `ProjectList.blade.php` to:
   - Remove S.No column and its header.
   - Remove summary block, page size selector, and export button.
   - Restore project ID link to `route('provincial.projects.show', $project->project_id)` if desired (pilot uses `projects.show`).
   - Remove `$projects->links()` and set empty row colspan back to 13.
3. **Route**  
   Remove or comment out the `provincial.projects.export` route and `projectsExport` method if no longer needed.

Git: `git checkout -- app/Http/Controllers/ProvincialController.php resources/views/provincial/ProjectList.blade.php routes/web.php` (after backing up any other local changes).

---

## 5. Test Checklist

- [ ] **Province isolation**  
  Log in as provincial (and as general with province filter). Confirm list and totals only include projects for accessible users; try different filters and page sizes.
- [ ] **Pagination**  
  Change page and per-page; confirm URL has `page` and `per_page`, table rows and S.No update correctly, and totals/summary do not change with page.
- [ ] **Filters**  
  Apply project type, team member, status, center; confirm list and summary reflect filtered set; reset clears filters.
- [ ] **Summary block**  
  Total Records and all four currency totals match the filtered set (e.g. compare with a small filtered set by hand or DB).
- [ ] **Project ID link**  
  Click project ID; should open project show via `projects.show`.
- [ ] **Page size selector**  
  Change per page; list and pagination update; other query params preserved.
- [ ] **Export button**  
  Click “Download Excel”; expect 501 or “Export not implemented” (no crash). URL should include current query string.
- [ ] **Status distribution**  
  Status cards and chart (if used) match filtered project set.
- [ ] **Empty state**  
  Use filters that return no projects; table shows one row with “No projects found” and colspan 14.

---

## 6. Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/ProvincialController.php` | `projectList`: base query + clone, fullDataset for totals/resolver, paginate with TableFormatter; new `projectsExport` placeholder. Added `use App\Helpers\TableFormatter`. |
| `resources/views/provincial/ProjectList.blade.php` | S.No column, summary block, page size form, export button, project ID → `projects.show`, pagination links, colspan 14, `TableFormatter` use. |
| `routes/web.php` | Added GET `provincial.projects.export` → `ProvincialController@projectsExport`. |

---

## 7. Grand Totals Logic Confirmation

- **Source**  
  Sums over the **full filtered collection** (`$fullDataset`) after resolver run.
- **Keys**  
  `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned` (same as resolver output).
- **View**  
  Summary block uses these four plus `$totalRecordCount`; no per-row summing in the view.

---

## 8. Province Isolation Confirmation

- **Intact**  
  All project data (list and totals) still derived from `getAccessibleUserIds($provincial)` and the same base query. No new data paths; export placeholder does not yet expose data.
