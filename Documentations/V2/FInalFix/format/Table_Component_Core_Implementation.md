# Table Component Core Implementation

**Date:** 2026-02-16  
**Scope:** Core infrastructure only. No table migrations; no refactor of existing views.  
**Reference:** [Table_Standardization_Design.md](./Table_Standardization_Design.md)

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│  Views (existing or future)                                     │
│  <x-data-table> or <x-financial-table>                           │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│  Blade components                                                │
│  • DataTable.php / data-table.blade.php                          │
│  • FinancialTable.php / financial-table.blade.php                │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│  TableFormatter (App\Helpers\TableFormatter)                    │
│  • formatCurrency, formatNumber                                  │
│  • calculateTotal, calculateMultipleTotals                       │
│  • resolveSerial                                                 │
│  (delegates currency to NumberFormatHelper)                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│  NumberFormatHelper (existing)                                   │
│  • formatIndianCurrency, formatIndian                            │
└─────────────────────────────────────────────────────────────────┘
```

- **TableFormatter:** Pure logic (formatting, aggregation, serial). No DB, no side effects. Used by components and can be used directly in any view.
- **DataTable:** Generic table with optional S.No., optional tfoot totals. Uses slots: `head`, `body`, optional `footer`.
- **FinancialTable:** Table driven by a `columns` definition; auto S.No., auto `text-end` and currency format for numeric columns, auto tfoot totals.
- **Backward compatibility:** No existing view or route uses these components until migration phases are run. Components are opt-in.

---

## 2. TableFormatter Responsibilities

**Class:** `App\Helpers\TableFormatter` (class-based; no global functions).

| Method | Purpose |
|--------|--------|
| `formatCurrency($value, $decimals = 2)` | Null-safe; delegates to `NumberFormatHelper::formatIndianCurrency`. Returns formatted string (e.g. "Rs. 10,00,000.00"). |
| `formatNumber($value, $decimals = 0)` | Null-safe; delegates to `NumberFormatHelper::formatIndian`. |
| `calculateTotal(Collection $collection, string $column)` | Sums one column; supports objects and arrays; null-safe. Returns float. |
| `calculateMultipleTotals(Collection $collection, array $columns)` | Sums multiple columns. Returns `array<string, float>`. |
| `resolveSerial($loop, $collection = null, $paginated = false)` | Returns S.No. for current row: paginated `$loop->iteration + $collection->firstItem() - 1`, else `$loop->iteration`. |

- No direct DB calls.
- Pure logic only; fully testable; no side effects.
- Autoloaded via PSR-4 (`App\` → `app/`).

---

## 3. Component API

### 3.1 DataTable

**Usage:** Generic list/budget table with optional serial and optional totals.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `collection` | Collection / LengthAwarePaginator | required | Data for body and for totals. |
| `paginated` | bool | false | Use pagination-aware serial. |
| `serial` | bool | true | Render S.No. column (header + empty cell in tfoot). |
| `numericColumns` | array | [] | Column keys to sum (e.g. `['amount_sanctioned', 'balance_amount']`). |
| `showTotals` | bool | false | If true and `numericColumns` non-empty, render tfoot with sums. |

**Slots:**

- `head` – Content for `<thead>` (after S.No. if serial). Parent must provide one `<th>` per column.
- `body` – Content for `<tbody>`. Parent should use `TableFormatter::resolveSerial($loop, $collection, $paginated)` for the first cell of each row when `serial` is true.
- `footer` – Optional. If provided and auto-totals are not used, this is rendered in `<tfoot>`.

**Example:**

```blade
<x-data-table
    :collection="$items"
    :paginated="true"
    :serial="true"
    :numericColumns="['amount_sanctioned','balance_amount']"
    :showTotals="true"
>
    <x-slot:head>
        <th>Particulars</th>
        <th class="text-end">Amount Sanctioned</th>
        <th class="text-end">Balance</th>
    </x-slot:head>
    <x-slot:body>
        @foreach($items as $item)
        <tr>
            <td>{{ \App\Helpers\TableFormatter::resolveSerial($loop, $items, true) }}</td>
            <td>{{ $item->particular }}</td>
            <td class="text-end">{{ \App\Helpers\TableFormatter::formatCurrency($item->amount_sanctioned) }}</td>
            <td class="text-end">{{ \App\Helpers\TableFormatter::formatCurrency($item->balance_amount) }}</td>
        </tr>
        @endforeach
    </x-slot:body>
</x-data-table>
```

**Auto tfoot:** When `showTotals` is true and `numericColumns` is set, the component renders a single tfoot row: empty (serial), "Total", then one cell per key in `numericColumns` with formatted sum. Table structure should be: S.No., then one optional label column, then numeric columns in the same order as `numericColumns`.

---

### 3.2 FinancialTable

**Usage:** Table fully driven by a column definition; best for statement/budget-style data.

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `collection` | Collection / LengthAwarePaginator | required | Rows. |
| `columns` | array | required | Column definitions (see below). |
| `serial` | bool | true | Render S.No. column. |
| `paginated` | bool | false | Use pagination-aware serial. |
| `showTotals` | bool | true | Render tfoot with sums for numeric columns. |

**Columns definition:**

```php
[
    ['key' => 'particular', 'label' => 'Particulars'],
    ['key' => 'amount_sanctioned', 'label' => 'Amount Sanctioned', 'numeric' => true],
    ['key' => 'total_expenses', 'label' => 'Total Expenses', 'numeric' => true],
    ['key' => 'balance_amount', 'label' => 'Balance Amount', 'numeric' => true],
]
```

- `key` – Attribute on each item (`$item->key` or `$item['key']`).
- `label` – Header text.
- `numeric` – If true: `text-end`, formatted via `TableFormatter::formatCurrency`; included in tfoot sum.

**Example:**

```blade
<x-financial-table
    :collection="$report->accountDetails"
    :columns="[
        ['key' => 'particulars', 'label' => 'Particulars'],
        ['key' => 'amount_sanctioned', 'label' => 'Amount Sanctioned', 'numeric' => true],
        ['key' => 'balance_amount', 'label' => 'Balance', 'numeric' => true],
    ]"
/>
```

---

## 4. Usage Examples

### 4.1 FinancialTable (minimal)

```blade
<x-financial-table :collection="$budgets" :columns="$columns" />
```

### 4.2 FinancialTable (no serial, no totals)

```blade
<x-financial-table
    :collection="$items"
    :columns="$columns"
    :serial="false"
    :showTotals="false"
/>
```

### 4.3 DataTable with custom footer

```blade
<x-data-table :collection="$list" :serial="true" :showTotals="false">
    <x-slot:head>...</x-slot:head>
    <x-slot:body>...</x-slot:body>
    <x-slot:footer>
        <tr><td colspan="3" class="text-end fw-bold">Custom summary</td></tr>
    </x-slot:footer>
</x-data-table>
```

### 4.4 Using TableFormatter in any view (no component)

```blade
<td>{{ \App\Helpers\TableFormatter::resolveSerial($loop, $paginator, true) }}</td>
<td class="text-end">{{ \App\Helpers\TableFormatter::formatCurrency($row->amount, 2) }}</td>
```

---

## 5. Performance Considerations

- **TableFormatter:** Pure PHP; no I/O. Sums use `Collection::sum()` (single pass). Safe for large collections within normal page size (e.g. hundreds of rows).
- **Components:** One-time resolution of totals in the view; no N+1. Collection is passed by reference; no full clone.
- **Large tables:** For very large lists (e.g. thousands of rows), prefer server-side pagination and keep `showTotals` for the current page only, or compute totals in the controller and pass them in (e.g. via a custom footer slot).
- **Caching:** TableFormatter does not cache; suitable for request-scoped use. If totals are expensive, compute in controller and pass into the view.

---

## 6. Why Helper-Based Approach Was Chosen

- **Reusability:** Both components and ad hoc views can call `TableFormatter` for serial, currency, and totals without duplicating logic.
- **Testability:** TableFormatter is a plain class with static methods; easy to unit test (formatting, sums, serial formula) without Blade or HTTP.
- **Consistency:** One place for “how we format currency” and “how we compute S.No.” so that future migrations (and existing views that adopt the helper) behave the same.
- **No globals:** Project rule: no new global helper functions. TableFormatter is class-based and invoked as `TableFormatter::method()` or via the components that use it.
- **Backward compatibility:** Existing code can keep using `format_indian_currency()` (global) or start using `TableFormatter::formatCurrency()` without changing the rest of the app; components use TableFormatter so that when views migrate, they get the same behaviour.

---

## 7. Migration Readiness Checklist

Before migrating existing tables to these components:

- [ ] TableFormatter covered by unit tests (formatCurrency, formatNumber, calculateTotal, calculateMultipleTotals, resolveSerial).
- [ ] Manual check of dev preview: `resources/views/dev/table_component_preview.blade.php` (e.g. via a dev-only route).
- [ ] Confirm no production route or layout includes the components until migration is intended.
- [ ] Phase A (high-risk financial tables): Replace only the table markup with `<x-financial-table>` or `<x-data-table>`; keep same controller data (collection, columns).
- [ ] After each migrated view: verify S.No. (and pagination if applicable), numeric alignment, and tfoot totals.
- [ ] No changes to existing layout files or global CSS in this implementation; table classes are minimal (`table table-bordered`, `text-end`, `fw-bold`, `table-light`) to avoid conflicts.

---

## 8. Files Created (Summary)

| File | Purpose |
|------|---------|
| `app/Helpers/TableFormatter.php` | Formatting, aggregation, serial logic. |
| `app/View/Components/DataTable.php` | DataTable component class. |
| `resources/views/components/data-table.blade.php` | DataTable view (head/body/footer slots, optional tfoot). |
| `app/View/Components/FinancialTable.php` | FinancialTable component class. |
| `resources/views/components/financial-table.blade.php` | FinancialTable view (columns-driven, auto serial/totals). |
| `resources/views/dev/table_component_preview.blade.php` | Dev-only preview for FinancialTable. |
| `Documentations/V2/FinalFix/Format/Table_Component_Core_Implementation.md` | This document. |

**No existing views or routes were modified.** Components are not referenced anywhere except in the dev preview and in this documentation.

---

**End of Table Component Core Implementation.**
