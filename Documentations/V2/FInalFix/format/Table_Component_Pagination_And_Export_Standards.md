# Table Component Pagination and Export Standards

**Date:** 2026-02-16  
**Scope:** Infrastructure only. No migration of existing production views.  
**Reference:** [Table_Component_Extended_Standards.md](./Table_Component_Extended_Standards.md)

---

## 1. Page Size Selector Rules

### Allowed page sizes

Defined in `TableFormatter::ALLOWED_PAGE_SIZES`:

```php
[10, 25, 50, 100]
```

- Only these values are valid for the selector and for `resolvePerPage()`.
- Arbitrary large values (e.g. 500, 10000) are **never** allowed from the request.

### Helper: `TableFormatter::resolvePerPage($request, $default = 25)`

- **Reads:** `?per_page=` from the request.
- **Validates:** Value must be one of `ALLOWED_PAGE_SIZES`. Otherwise treated as invalid.
- **Fallback:** If missing or invalid, returns `$default`. If `$default` is not in the allowed list, the implementation uses the first allowed value so the result is always safe.
- **Never** returns a value outside the allowed list or an arbitrary large number.

### Controller usage

```php
use App\Helpers\TableFormatter;

$perPage = TableFormatter::resolvePerPage(request());
$projects = $query->paginate($perPage)->withQueryString();
```

Pass `$perPage` and `allowedPageSizes` into the view so the component can show the selector and preserve filters.

### Component: `allowPageSizeSelector`, `currentPerPage`, `allowedPageSizes`

When `allowPageSizeSelector=true` and `paginated=true`:

- A **dropdown** is rendered **above** the table: “Rows per page: [ 10 | 25 | 50 | 100 ]”.
- **On change:** Submit via **GET** (form with `method="get"`).
- **Preserve:** All current query parameters (filters, sort, etc.) except `per_page`; `per_page` is updated from the select.
- Optional: when `per_page` changes, reset `page` to 1 (document in controller or front-end) to avoid empty pages.

---

## 2. Export Button Rules

### Component: `allowExport`, `exportRoute`

When `allowExport=true` and `exportRoute` is a non-empty route name:

- A **“Download Excel”** button is rendered above the table.
- **Link:** `route($exportRoute, request()->query())` so the export URL receives the **current query string** (all filters, sort, etc.).
- **Semantics:** Export must use the **full filtered dataset**, not only the current page. The export endpoint is responsible for:
  - Applying the same filters (and sort, if desired) as the list.
  - Running a single (possibly chunked) query with **no pagination**.
  - Returning an Excel file download.

### Rules

- **Must** pass current filters (and optionally sort) via query string so export respects them.
- **Must not** export only the current page; export the full filtered result set.
- **Must** export the full filtered dataset; the export controller/service reads query params and builds the same filter scope without `limit`/`offset`.

---

## 3. Controller Responsibilities

### Pagination + page size

1. Resolve per-page: `$perPage = TableFormatter::resolvePerPage(request());`
2. Paginate: `$items = $query->paginate($perPage)->withQueryString();`
3. Pass to view: `currentPerPage` => `$perPage`, `allowedPageSizes` => `TableFormatter::ALLOWED_PAGE_SIZES` (or custom array), and set `allowPageSizeSelector` => `true` when the table is paginated.

### Export

1. Define a **dedicated export route** that accepts the same query parameters as the list (filters, sort).
2. In the export controller: **authorize** the same as the list (see Security).
3. Build the **same base query** as the list from request query params (no pagination).
4. Use **chunking** or a **queue job** when the dataset is large (see Performance guardrails).
5. Return Excel download (e.g. Maatwebsite Excel or CSV).

### Summary block

- `totalRecordCount` and grand totals must still reflect the **full** (filtered) dataset.
- Page size only changes how many rows are shown per page; it does **not** change total record count or grand totals.
- Keep passing `totalRecordCount` and `grandTotals` from the controller when `showSummary=true` and the table is paginated.

---

## 4. Export Architecture Pattern (Design)

Recommended structure for export; no full implementation in this phase:

1. **Dedicated export controller**  
   One (or more) controller actions per export type (e.g. `ReportExportController@excel`). Receives same query params as the list; builds same filter scope.

2. **Dedicated export service (optional)**  
   Service that takes filter/sort params and returns a query or chunked iterator. Keeps controller thin and allows reuse and testing.

3. **Single aggregate query, no pagination**  
   Export runs the same logical query as the list but **without** `limit`/`offset`. Use `get()` or chunked methods (e.g. `cursor()`, `chunk()`).

4. **Chunking for large datasets**  
   If the result set can be large, use chunking (e.g. `chunk(1000, callback)`) or `cursor()` so that not all rows are loaded into memory at once.

5. **Return Excel download**  
   - If the project uses **Maatwebsite Excel:** use an Export class (e.g. `FromQuery`, `FromCollection`) and `Excel::download()`. Use chunking/FromQuery so large exports do not load everything into memory.
   - If not: design an export service abstraction (e.g. `ExportService::toExcel($query, $columns)`) that streams or chunks and returns a download response. Implementation can be CSV or Excel later.

---

## 5. Performance Guardrails

### Export

- **If dataset > 10,000 rows:**
  - **Must** use one of:
    - **Chunked export:** Stream rows in chunks (e.g. 1000) to avoid loading all into memory.
    - **Queued export:** Generate the file in a job and notify/link when done.
    - **Warning:** Show a warning to the user (e.g. “Large export may take a while”) and still use chunking or queue.
  - **Do not** load the entire result set into memory in one go.

### Not implemented in this phase

- **Queue export job:** Not implemented; only the **rule** is documented. When implementing, use a job that:
  - Accepts the same filter/sort params.
  - Runs the query in chunks.
  - Writes to storage and returns a download link or attaches the file to a notification.

---

## 6. Security Considerations (Authorization Check)

- The **export route** must enforce the **same authorization** as the list view (e.g. same policy, same role/scope checks).
- Do **not** rely only on “hidden” URLs; treat export as a privileged action and check that the user is allowed to see the data they are exporting.
- Validate and sanitize query parameters (e.g. allowed filter keys, allowed sort columns) so they cannot be abused (e.g. SQL injection, mass export of another user’s data).

---

## 7. Example Controller Snippet

```php
use App\Helpers\TableFormatter;

public function index(Request $request)
{
    $query = Project::query()
        ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
        ->when($request->filled('search'), fn ($q) => $q->where('project_title', 'like', '%' . $request->search . '%'))
        ->orderBy($request->get('sort', 'created_at'), $request->get('dir', 'desc'));

    $perPage = TableFormatter::resolvePerPage($request);
    $projects = $query->clone()->paginate($perPage)->withQueryString();

    $grandTotals = [
        'amount_sanctioned' => (float) $query->clone()->sum('amount_sanctioned'),
        'balance_amount'    => (float) $query->clone()->sum('balance_amount'),
    ];

    return view('projects.index', [
        'projects'          => $projects,
        'grandTotals'       => $grandTotals,
        'totalRecordCount'  => $projects->total(),
        'currentPerPage'    => $perPage,
        'allowedPageSizes'  => TableFormatter::ALLOWED_PAGE_SIZES,
        'columns'           => $this->getColumns(),
    ]);
}
```

---

## 8. Example Route Definition

```php
// List (existing)
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

// Export: same filters via query string; dedicated controller
Route::get('/reports/export', [ReportExportController::class, 'excel'])
    ->name('reports.export.excel')
    ->middleware('auth'); // same as index
```

View:

```blade
<x-financial-table
    :collection="$reports"
    :columns="$columns"
    :paginated="true"
    :showSummary="true"
    :grandTotals="$grandTotals"
    :totalRecordCount="$totalRecordCount"
    :allowPageSizeSelector="true"
    :currentPerPage="$currentPerPage"
    :allowedPageSizes="$allowedPageSizes"
    :allowExport="true"
    exportRoute="reports.export.excel"
/>
```

Export controller (pattern): read `request()->query()`, build same filters, run query without pagination, chunk if large, return Excel.

---

## 9. Future Queue-Ready Architecture Note

When adding queued exports:

1. **Route:** POST (or GET with token) to “request export” that dispatches a job and returns “Export started” or a download link when ready.
2. **Job:** Accepts user id, filter/sort params, and optional notification channel. Builds same query, chunked export, stores file, then notifies user with link.
3. **Authorization:** Job should re-check that the user is allowed to export that scope (e.g. same policy as list).
4. **Limits:** Consider max rows per export (e.g. 50,000) and rate limits per user to avoid abuse.

---

## 10. Summary Section (Unchanged)

- The **summary block** (Total Records + grand totals) is **not** affected by page size selection.
- `totalRecordCount` and `grandTotals` always refer to the **full** (filtered) dataset.
- The controller continues to pass these when `showSummary=true`; the component does not derive them from the current page.

---

## 11. New / Extended API Summary

### TableFormatter

| Addition | Purpose |
|----------|--------|
| `ALLOWED_PAGE_SIZES` | `[10, 25, 50, 100]`. |
| `resolvePerPage($request = null, $default = 25)` | Safe per-page from query; always one of allowed or safe default. |

### FinancialTable (new props)

| Prop | Type | Default | Purpose |
|------|------|---------|--------|
| `allowPageSizeSelector` | bool | false | Show “Rows per page” dropdown (GET, preserves query). |
| `currentPerPage` | int | 25 | Current per_page for selector and pagination. |
| `allowedPageSizes` | array | ALLOWED_PAGE_SIZES | Options in dropdown. |
| `allowExport` | bool | false | Show “Download Excel” button. |
| `exportRoute` | string\|null | null | Route name for export; receives `request()->query()`. |

---

**End of Table Component Pagination and Export Standards.**
