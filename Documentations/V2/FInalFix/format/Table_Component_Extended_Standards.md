# Table Component Extended Standards

**Date:** 2026-02-16  
**Scope:** Infrastructure enhancement only. No migration of existing production views.  
**Reference:** [Table_Component_Core_Implementation.md](./Table_Component_Core_Implementation.md)

---

## 1. Clickable Project ID Rule

When a table lists projects and the project identifier should link to the project show page:

- **Props:** Set `linkProjectId="true"` and `projectIdColumn="project_id"` (or the actual column key, e.g. `project_id`).
- **Behaviour:** For the column whose `key` equals `projectIdColumn`, each cell is rendered as:
  ```html
  <a href="{{ TableFormatter::projectLink($item->{$key}) }}" class="fw-semibold text-primary text-decoration-none">{{ $value }}</a>
  ```
- **URL:** `TableFormatter::projectLink($projectId)` returns `route('projects.show', $projectId)`. No DB calls.
- **When not to use:** Key-value tables, audit logs where ID is not a project ID, or when the link target is a different route (handle in custom view or extend helper).

---

## 2. Summary Block Rule

A **summary block** appears **above** the table when `showSummary="true"` on `<x-financial-table>`.

- **Content:**
  - **Total Records:** From `totalRecordCount` (controller) or `$collection->total()` / `$collection->count()` via `TableFormatter::resolveTotalRecordCount()`.
  - **Per-column totals:** From `grandTotals` (controller) when provided; otherwise see Grand totals vs page totals (below).
- **Rendering:** Implemented via `<x-table-summary>` with props: `totalRecordCount`, `totals` (array), optional `labels` (column key → display label). Styling: border, light background, flex layout.
- **When to show:** Use for list/financial tables where the user should see “Total Records” and “Total Amount Sanctioned” (or similar) for the **entire** dataset, not only the current page.

---

## 3. Grand Totals vs Page Totals Clarification

| Concept | Meaning | Where computed |
|--------|---------|----------------|
| **Page totals** | Sum of numeric columns for the **current page** of data. | Component: `TableFormatter::calculateMultipleTotals($collection, $numericColumnKeys)`. Shown in `<tfoot>`. |
| **Grand totals** | Sum of numeric columns for the **full dataset** (all pages). | **Controller** when the list is paginated. Passed as `grandTotals` array. Shown in the **summary block** when `showSummary="true"`. |

- **Non-paginated:** If `showSummary="true"` and `grandTotals` is not passed, the component may derive totals from the current collection (same as page totals) for the summary block. No extra query.
- **Paginated:** The component must **not** compute full-dataset sums (would require loading all rows). The controller is responsible for passing `grandTotals` (and optionally `totalRecordCount`) so the summary block is correct and there is no performance regression.

---

## 4. Controller Responsibility for Grand Totals

When the table is **paginated** and **showSummary** is used:

1. **Compute grand totals in the controller** (e.g. one aggregate query or from existing aggregates).
2. **Pass them to the view:**
   ```php
   return view('...', [
       'reports' => $reports,  // LengthAwarePaginator
       'grandTotals' => [
           'amount_sanctioned' => $totalSanctioned,
           'total_expenses'    => $totalExpenses,
           'balance_amount'    => $totalBalance,
       ],
       'totalRecordCount' => $reports->total(), // optional; component can use $collection->total()
   ]);
   ```
3. **In the Blade view:**
   ```blade
   <x-financial-table
       :collection="$reports"
       :columns="$columns"
       :paginated="true"
       :showSummary="true"
       :grandTotals="$grandTotals"
       :totalRecordCount="$totalRecordCount"
   />
   ```

If `grandTotals` is omitted on a paginated table with `showSummary="true"`, the summary block will show **Total Records** (from `totalRecordCount` or `$collection->total()`) but **no** column totals—by design, to avoid summing the full dataset in the view.

---

## 5. Performance Safety Rule

- **No full-dataset sum in the component when paginated.** The component never calls `calculateMultipleTotals()` on the full dataset for a paginated collection. It only sums the current page for `<tfoot>` and uses controller-provided `grandTotals` for the summary block.
- **TableFormatter** remains pure: no DB calls; all aggregation is on the in-memory collection or on controller-provided arrays.
- **Total record count:** Use `TableFormatter::resolveTotalRecordCount($collection, $totalFromController)` so that when the controller passes a total (e.g. from the paginator), that value is used without extra queries.

---

## 6. Migration Guidelines

- **Do not modify existing production views** in this phase; only the infrastructure (TableFormatter, FinancialTable, table-summary) was extended.
- When migrating a view to use the summary block:
  1. Ensure the controller passes `grandTotals` (and optionally `totalRecordCount`) when the list is paginated.
  2. Add `showSummary="true"` and, if applicable, `:grandTotals="$grandTotals"` and `:totalRecordCount="$totalRecordCount"`.
  3. Verify that grand totals are computed in the controller without N+1 or loading full result sets.
- When migrating a project list to use clickable project ID:
  1. Add a column with `'key' => 'project_id'` (or the actual key) in the columns definition.
  2. Set `linkProjectId="true"` and `projectIdColumn="project_id"` (or the same key).
  3. Ensure `route('projects.show', $projectId)` is correct for that context (executor/project show).

---

## 7. Example Controller Snippet for Passing Totals

```php
// Example: paginated reports list with grand totals for summary block

$query = Report::query()
    ->where(...)
    ->with(...);

$reports = $query->clone()->paginate(20);

$grandTotals = [
    'amount_sanctioned' => (float) $query->clone()->sum('amount_sanctioned'),
    'total_expenses'    => (float) $query->clone()->sum('total_expenses'),
    'balance_amount'    => (float) $query->clone()->sum('balance_amount'),
];

return view('reports.index', [
    'reports'          => $reports,
    'grandTotals'      => $grandTotals,
    'totalRecordCount' => $reports->total(),
    'columns'          => $columns,
]);
```

Use a single aggregate query where possible (e.g. `selectRaw('sum(amount_sanctioned) as total_sanctioned, ...')`) to avoid multiple full-table scans.

---

## 8. New / Extended API Summary

### TableFormatter (new methods)

| Method | Purpose |
|--------|--------|
| `projectLink($projectId)` | Returns `route('projects.show', $projectId)`. |
| `resolveGrandTotal($totalsFromController, $column)` | Returns one grand total from controller array; no computation. |
| `resolveGrandTotals($controllerTotals)` | Returns safe keyed array of floats from controller. |
| `resolveTotalRecordCount($collection, $totalFromController)` | Returns controller total, or `$collection->total()`, or `$collection->count()`. |

### FinancialTable (new props)

| Prop | Type | Default | Purpose |
|------|------|---------|--------|
| `showSummary` | bool | false | Render summary block above table. |
| `grandTotals` | array | [] | Controller-calculated grand totals (keyed by column). |
| `totalRecordCount` | int\|null | null | Controller-provided total record count. |
| `projectIdColumn` | string\|null | null | Column key to render as project link. |
| `linkProjectId` | bool | false | When true and projectIdColumn set, render that column as link. |

### New partial

| View | Purpose |
|------|--------|
| `components/table-summary.blade.php` | Renders Total Records + per-column totals; props: `totalRecordCount`, `totals`, `labels`. |

---

**End of Table Component Extended Standards.**
