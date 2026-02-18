# Table Standardization Design

**Date:** 2026-02-16  
**Purpose:** Reusable, enforced table pattern — design only. No implementation or refactor in this phase.  
**Reference:** [Table_Format_Integrity_Audit.md](./Table_Format_Integrity_Audit.md)

---

## 1. Current Inconsistencies Summary (from Audit)

| Metric | Count |
|--------|-------|
| Table-containing Blade files scanned | 95+ |
| Index/List/Financial tables audited | 72 |
| Fully compliant (A) | 28 |
| Serial missing (B) | 18 |
| Totals missing (C) | 14 |
| Both missing (D) | 10 |
| Not applicable (E) | 2 |
| **Non-compliant total** | **42** |
| **High-risk (financial impact)** | **12** |

**Main inconsistencies:**

- **Serial:** Mixed use of `#`, `S.No.`, `Sl No`, `No.`; mixed logic (`$index + 1`, `$loop->iteration`, `firstItem() + $index`) and no serial on many list/financial tables.
- **Numeric columns:** Inconsistent alignment (left/center/right); mixed use of `format_indian_currency`, `format_indian`, `number_format`; no standard `text-end` class.
- **Totals:** Some tables use `<tfoot>`, others use a summary block below, others have no totals; dynamic forms use JS for totals with no shared pattern.
- **Pagination:** Paginated lists often use `firstItem() + $index`; non-paginated use `$index + 1` or `$loop->iteration` with no single rule.

This design document defines a single, enforceable standard and a path to adopt it without changing existing views until migration phases are executed.

---

## 2. Proposed Table Standard Specification

### 2.1 Serial Column

| Aspect | Rule |
|--------|------|
| **Header** | Always **S.No.** (single standard label). |
| **Position** | First column of the table. |
| **Logic (paginated)** | `$loop->iteration + $collection->firstItem() - 1` so that serial continues across pages (e.g. page 2 starts at 11 if per-page is 10). |
| **Logic (non-paginated)** | `$loop->iteration`. |
| **When to omit** | Key-value/comparison tables, summary tables where rows are categories (e.g. “by project type”), or when design explicitly opts out (e.g. card-style layout). |

**Rationale:** One label and one formula per context (paginated vs not) avoids drift and supports audit/traceability.

### 2.2 Numeric Column Rules

All monetary and numeric quantity columns must:

| Rule | Specification |
|------|----------------|
| **Alignment** | Right-aligned. |
| **Markup** | `class="text-end"` on `<th>` and `<td>` for those columns. |
| **Formatting** | Use `number_format()` or project helper (e.g. `format_indian_currency()`) with explicit decimals (e.g. 2 for money). |
| **Consistency** | Same helper for same type (money vs count vs percentage) across the app. |

**Rationale:** Right alignment and consistent formatting reduce misread and support scanning; `text-end` allows RTL and layout reuse.

### 2.3 Total Row Rules

| Rule | Specification |
|------|----------------|
| **Container** | Use `<tfoot>`. |
| **Computation** | Prefer `$collection->sum('column')` in Blade where the collection is in scope; otherwise controller-passed totals. |
| **Label** | First cell: “Total” or “Grand Total” (bold). Serial column cell in tfoot: empty or “—” (no number). |
| **Styling** | Totals row: bold; `border-top: 2px` (or Bootstrap `border-top` class) to separate from body. |
| **Dynamic tables** | For JS-driven rows (e.g. statement forms), totals updated via script; same visual pattern (tfoot, bold, border-top). |

**Rationale:** `<tfoot>` is semantic and prints correctly with multi-page tables; one aggregation pattern (sum) keeps behavior predictable.

### 2.4 Financial Summary Alternative

For very large tables (e.g. many budget lines or statement rows):

- **Option:** Omit inline `<tfoot>` and show a **summary card (or summary block) below the table** with the same totals.
- **Rule:** Totals must still be present (either in tfoot or in the summary card); never drop aggregation for financial columns.
- **Use when:** Row count is high and a sticky tfoot or repeated printing of a long tfoot is undesirable from a UX perspective.

---

## 3. Reusable Blade Component Design (Design Only)

The following is a **design** for a family of Blade components. No implementation is created in this phase.

### 3.1 Base Component: `<x-data-table>`

**Intent:** Generic data table with optional serial column and optional totals, and optional pagination awareness.

**Structure (conceptual slots):**

```blade
<x-data-table :collection="..." :paginated="..." :serial="..." :numericColumns="[...]">
    <x-slot:head>
        <th>S.No.</th>
        <th>...</th>
    </x-slot:head>
    <x-slot:body>
        @foreach(...)
            <tr>
                <td>{{-- serial rendered by component when serial=true --}}</td>
                <td>...</td>
            </tr>
        @endforeach
    </x-slot:body>
    <x-slot:footer>
        {{-- optional; when numericColumns provided, component can render total row --}}
    </x-slot:footer>
</x-data-table>
```

**Behaviour (design):**

- **auto-serial:** When `serial="true"`, component injects the first column header “S.No.” (if not already in slot) and, for each row, the correct value using:
  - Paginated: `$loop->iteration + $collection->firstItem() - 1`
  - Non-paginated: `$loop->iteration`
- **auto-total:** When `numericColumns` is set (e.g. `['amount_sanctioned','total_expenses','balance_amount']`), component renders a `<tfoot>` row with empty/serial cell, label “Total”, then `$collection->sum(column)` for each, formatted (e.g. `format_indian_currency`).
- **Pagination awareness:** When `paginated="true"` and a paginator/collection is passed, serial uses the paginated formula; otherwise non-paginated.

**Props (conceptual):**

| Prop | Type | Purpose |
|------|------|--------|
| `collection` | `Illuminate\Support\Collection` or LengthAwarePaginator | Data for body and for sum(). |
| `paginated` | bool | Use paginated serial formula. |
| `serial` | bool | Render S.No. column. |
| `numericColumns` | array | Keys for columns to sum in footer (e.g. `['this_phase','rate_quantity']`). |

**Slots:**

- `head` – thead content (component may prepend S.No. if `serial`).
- `body` – tbody content (component may prepend serial cell per row if `serial`).
- `footer` – optional; if not provided and `numericColumns` is set, component renders default tfoot.

No code is written here; this is the contract the future implementation must satisfy.

### 3.2 Sub-components (conceptual)

- **`<x-data-table.head>`** – Wrapper for `<thead><tr>...</tr></thead>` so that header structure is consistent (e.g. first column S.No. when serial is on).
- **`<x-data-table.body>`** – Wrapper for `<tbody>...</tbody>`; component injects serial `<td>` when `serial` is true.
- **`<x-data-table.footer>`** – Wrapper for `<tfoot>...</tfoot>`; when auto-total is on, component can fill this with the total row; otherwise slot content is used.

These names are the **design**; implementation details (e.g. whether these are one component with slots or multiple sub-components) are left to the implementation phase.

---

## 4. Financial Table Variant Design

### 4.1 Component: `<x-financial-table>`

**Intent:** Specialisation for tables that display monetary columns and totals. Same standard (serial, text-end, tfoot) with a simpler API for the common case.

**Props (design):**

| Prop | Type | Default | Purpose |
|------|------|--------|--------|
| `collection` | Collection / Paginator | required | Data for rows and aggregation. |
| `columns` | array | required | Column definitions (e.g. `[['key'=>'particular','label'=>'Particulars'], ['key'=>'amount_sanctioned','label'=>'Amount Sanctioned','numeric'=>true]]`). |
| `numericColumns` | array | derived or explicit | Keys of columns that are monetary/numeric (for alignment and totals). If not set, can be derived from `columns` where `numeric` is true. |
| `showTotals` | bool | true | Whether to render `<tfoot>` with sums for numeric columns. |
| `serial` | bool | true | Whether to render S.No. column. |
| `paginated` | bool | false | Use paginated serial formula. |

**Behaviour (design):**

- Renders `<table>` with `class="table table-bordered ..."` (exact classes TBD in implementation).
- First column: S.No. when `serial` is true (same logic as base data-table).
- Columns from `columns`: `<th class="text-end">` for numeric columns; `<td class="text-end">` with `format_indian_currency($item->{$key}, 2)` (or project standard).
- When `showTotals` is true: `<tfoot>` with one row – empty/serial cell, “Total”, then `$collection->sum($key)` for each numeric column, formatted; row bold, border-top.

**Rationale:** Single place to enforce monetary formatting, alignment, and totals so that the 12 high-risk financial tables can migrate to one pattern.

---

## 5. Serial Logic Standard (Summary)

| Context | Formula | Example (page 2, perPage 10) |
|---------|---------|------------------------------|
| Paginated | `$loop->iteration + $collection->firstItem() - 1` | 11, 12, … 20 |
| Non-paginated | `$loop->iteration` | 1, 2, 3, … |

**Header:** Always **S.No.** in the first column for list/financial tables that show a serial.

---

## 6. Total Aggregation Standard (Summary)

- **Where:** `<tfoot>` one row.
- **How:** `$collection->sum('column_name')` in Blade, or controller-provided totals for complex/joined data.
- **Format:** Same as body (e.g. `format_indian_currency($collection->sum('amount'), 2)`).
- **Style:** Bold totals row; `border-top` (e.g. 2px or utility class).
- **Dynamic forms:** JS recomputes and writes into tfoot cells; same structure and styling.

---

## 7. Migration Roadmap

Migration is **phased**; existing views are **not** modified in this design phase. Each phase is executed only when approved and can be done incrementally (e.g. one view at a time).

### Phase A — High-Risk Financial Tables (12)

**Goal:** Apply standard (S.No., numeric alignment/format, totals) to all tables with direct financial impact.

**Targets (from audit Section 3):**

1. `general/budgets/index.blade.php`
2. `general/reports/pending.blade.php`
3. `executor/ReportList.blade.php`
4. `provincial/ProjectList.blade.php`
5. `coordinator/ProjectList.blade.php`
6. `admin/projects/index.blade.php`
7. `admin/budget_reconciliation/index.blade.php`
8. `reports/monthly/partials/view/statements_of_account/*.blade.php` (all report types)
9. `projects/partials/Show/LDP/target_group.blade.php`
10. `projects/partials/Show/Edu-RUT/target_group.blade.php`
11. `projects/partials/Show/IIES/family_working_members.blade.php`
12. `projects/partials/Show/IES/family_working_members.blade.php` and `projects/partials/Show/IAH/earning_members.blade.php`

**Deliverables (per phase execution):** Add S.No. where missing; add or normalize `<tfoot>` totals; apply `text-end` and consistent number formatting for monetary columns. Optionally introduce `<x-financial-table>` (or equivalent) during this phase for a subset of views.

### Phase B — Index Listings

**Goal:** Serial and, where applicable, totals for all index/list views (projects, reports, executors, provincials, societies, centers, etc.).

**Scope (from audit):** All tables classified B or D in “Index / List Views” and “Report list” tables (e.g. `admin/reports/index.blade.php`, `general/executors/index.blade.php`, `general/provincials/index.blade.php`, `reports/monthly/index.blade.php`, `executor/pendingReports.blade.php`, etc.).

**Deliverables:** S.No. on every list table; for tables with numeric columns (e.g. budget, utilization), add tfoot or summary block. Optionally use `<x-data-table>` with `serial` and `numericColumns` where it fits.

### Phase C — Minor Listings and Remaining

**Goal:** Remaining beneficiary/member tables, admin audit/correction logs, widget summary tables, and any other tables flagged in the audit.

**Scope:** RST/IAH/IGE/CCI partials (serial or totals), admin reconciliation show/correction_log, provincial/coordinator widget summary tables (grand-total row), budget monitoring partials, report form statement table (serial), etc.

**Deliverables:** Serial where missing; totals (tfoot or summary card) where numeric columns exist; alignment/formatting to standard.

---

## 8. Risk Reduction Explanation

- **Traceability:** Consistent S.No. (and pagination-aware serial) makes it easier to refer to “row 5 on page 2” in support and audits.
- **Financial integrity:** One rule for totals (tfoot + sum) and one rule for formatting (right-align, format_indian_currency) reduces errors and ensures totals are never dropped on financial tables.
- **Maintainability:** A single component design (`<x-data-table>` / `<x-financial-table>`) gives one place to fix bugs and evolve behaviour (e.g. accessibility, export) instead of 72+ ad hoc tables.
- **Phased migration:** High-risk tables (Phase A) get the standard first; index and minor tables follow without big-bang refactor, and each view can be tested after migration.
- **Design-only here:** No code or views are changed in this step, so there is no regression risk until implementation and migration are explicitly undertaken.

---

## 9. Document Status

- **Current phase:** Design only.
- **No code modified:** No Blade, PHP, or JS files have been created or changed.
- **Next steps:** Review and approve this design; then implement components (if desired) and execute Phase A → B → C migration in separate work.

---

**End of Table Standardization Design.**
