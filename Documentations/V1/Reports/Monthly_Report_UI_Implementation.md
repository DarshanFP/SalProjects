# Monthly Report UI Implementation

This document summarizes the UI and styling implementations done for Monthly Reports: activity Scheduled months badge, Budgets Details table (view), and Budget Row / Statements of Account (edit) updates.

---

## 1. Activity Scheduled Months Badge

### Problem
On report create/edit, the “Scheduled: January, February, …” line used `badge bg-info` with a light blue background (`#66d1d1`) and white text, making it hard to read.

### Solution
- Replaced `badge bg-info` with `badge scheduled-months-badge`.
- Background: **`#0f766e`** (darker teal), `color: #fff`.

### Files

| File | Changes |
|------|---------|
| `resources/views/reports/monthly/partials/create/objectives.blade.php` | Badge class in activity loop and in `addActivity` JS; CSS for `.objective-card .badge.scheduled-months-badge`, `.activity-card .badge.scheduled-months-badge` |
| `resources/views/reports/monthly/partials/edit/objectives.blade.php` | Same badge + CSS |

### CSS Added

```css
.objective-card .badge.scheduled-months-badge,
.activity-card .badge.scheduled-months-badge {
    background-color: #0f766e !important;
    color: #fff;
}
```

---

## 2. Budgets Details Table (View)

### Scope
View partials that show the “Budgets Details” table:  
`development_projects`, `individual_health`, `individual_education`, `individual_livelihood`, `institutional_education`.

Path: `resources/views/reports/monthly/partials/view/statements_of_account/`.

### 2.1 Font Size

- Table font set to **`0.875rem`** to align with other sections.

### 2.2 Particulars Column

- Width: **22%** (`table-layout: fixed`).
- Text wrap: `word-wrap: break-word`, `overflow-wrap: break-word`, `white-space: normal`, `vertical-align: middle`.
- `min-width: 0` to allow shrinking.

### 2.3 Table Within Layout

- Wrapper: `div.table-responsive.budget-details-table-wrapper` with `max-width: 100%`, `overflow-x: auto`.
- Table: `table-layout: fixed`, `width: 100%`, `max-width: 100%`.
- Column widths:
  - Particulars: 22%
  - Amount Sanctioned, Total Amount: 13% each
  - Expenses Last Month, Expenses This Month, Total Expenses, Balance Amount: 12% each
- Numeric: `text-align: right`, `min-width: 0`.

### 2.4 Header Row: Text Wrap

- All `th`: `word-wrap: break-word`, `overflow-wrap: break-word`, `white-space: normal` to avoid overlapping (e.g. “Expenses Last Month”, “Expenses This Month”, “Total Expenses”).

### 2.5 Header Row: Centering

- `th`: `text-align: center`.

### 2.6 Budget Row Badge (View)

- “Budget Row” in Particulars: `badge bg-info` → `badge scheduled-months-badge` with **`#0f766e`** (same as activity Scheduled months).
- Scoped: `.budget-details-table .badge.scheduled-months-badge { background-color: #0f766e !important; color: #fff; }`

### 2.7 Markup and Classes (View)

- Table: `budget-details-table`.
- Wrapper: `budget-details-table-wrapper`.
- Headers/cells: `col-particulars`, `col-numeric` for layout and alignment.

### 2.8 CSS Block (View)

```css
.budget-details-table-wrapper {
    max-width: 100%;
    overflow-x: auto;
}

.budget-details-table {
    font-size: 0.875rem;
    table-layout: fixed;
    width: 100%;
    max-width: 100%;
}

.budget-details-table th {
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    text-align: center;
}

.budget-details-table .col-particulars {
    width: 22%;
    min-width: 0;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    vertical-align: middle;
}

.budget-details-table .col-numeric {
    width: 13%;
    min-width: 0;
    text-align: right;
}

.budget-details-table th.col-numeric:nth-child(n+4),
.budget-details-table td.col-numeric:nth-child(n+4) {
    width: 12%;
}

.budget-details-table .badge.scheduled-months-badge {
    background-color: #0f766e !important;
    color: #fff;
}
```

---

## 3. Budget Row Badge (Edit & Create Statements of Account)

### 3.1 Edit Partials

Path: `resources/views/reports/monthly/partials/edit/statements_of_account/`.

Files:  
`development_projects`, `individual_health`, `individual_education`, `individual_livelihood`, `individual_ongoing_education`, `institutional_education`.

- “Budget Row” badge: `badge bg-info` → `badge scheduled-months-badge`.
- CSS:  
  `.badge.scheduled-months-badge { background-color: #0f766e !important; color: #fff; }`

### 3.2 Create Partials (Statements of Account)

Path: `resources/views/reports/monthly/partials/statements_of_account/`.

Same six partials: same class and same `.badge.scheduled-months-badge` rule.

---

## 4. Edit Budget/Statements Table (budget-statements-table)

### Scope
Edit partials only:  
`development_projects`, `individual_health`, `individual_education`, `individual_livelihood`, `individual_ongoing_education`, `institutional_education`  
in `resources/views/reports/monthly/partials/edit/statements_of_account/`.

### 4.1 Table and Action Column

- Table: `table table-bordered budget-statements-table`.
- Action header/cells: `budget-action-col`.
- Action column hidden by default; shown only when there is at least one “additional” row.

### 4.2 Toggle Logic

- `toggleBudgetActionCol()`:
  - If `#account-rows tr[data-row-type="additional"]` exists → add `show-action-col` on `.budget-statements-table`.
  - Otherwise → remove `show-action-col`.
- Called on: `DOMContentLoaded`, `addAccountRow()`, `removeAccountRow()`.

### 4.3 Particulars for Budget Rows

- **Budget rows** (`is_budget_row` or budget template):
  - `input[name="particulars[]"]` is `type="hidden"`.
  - Label: `<span class="particulars-text">…</span>`.
  - Wrapper: `td.particulars-cell`.
- **Non‑budget / additional rows**:  
  - `input` (text) in `td.particulars-cell` as before.
- **Budget rows:**  
  - “Budget Row” badge and its `td` in the Action column were removed; Action `td` for budget rows is empty (only `budget-action-col`). The `.badge.scheduled-months-badge` rule is retained for create SoA and view; in these edit partials the badge is no longer rendered.

### 4.4 Add-Row Markup

- New rows: `td.particulars-cell`, `td.budget-action-col` with Remove button.
- `addAccountRow()` (and similar) calls `toggleBudgetActionCol()` after insert.
- Rows that can be removed should use `data-row-type="additional"` (or equivalent) so `toggleBudgetActionCol` can detect them.

### 4.5 CSS (Edit)

```css
.budget-statements-table { font-size: 0.8rem; }
.budget-statements-table th,
.budget-statements-table td { font-size: inherit; }

.budget-statements-table .budget-action-col { display: none; }
.budget-statements-table.show-action-col .budget-action-col { display: table-cell; }

.budget-statements-table .particulars-cell {
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 200px;
}
.budget-statements-table .particulars-text { display: block; }
```

---

## 5. File Summary

| Area | Files |
|------|-------|
| **Scheduled months badge** | `partials/create/objectives.blade.php`, `partials/edit/objectives.blade.php` |
| **Budgets Details (view)** | `partials/view/statements_of_account/` → `development_projects`, `individual_health`, `individual_education`, `individual_livelihood`, `institutional_education` |
| **Budget Row badge + budget-statements (edit)** | `partials/edit/statements_of_account/` → `development_projects`, `individual_health`, `individual_education`, `individual_livelihood`, `individual_ongoing_education`, `institutional_education` |
| **Budget Row badge (create SoA)** | `partials/statements_of_account/` → same six partials |

---

## 6. Shared Color

- **`#0f766e`** is used for:
  - Activity **Scheduled months** badge (create/edit objectives).
  - **Budget Row** badge in view Budgets Details and in edit/create Statements of Account (where that badge is still present).

---

## 7. Notes

- View “Budgets Details” table: `budget-details-table`, `col-particulars`, `col-numeric`, `budget-details-table-wrapper`. Edit budget/statements table: `budget-statements-table`, `budget-action-col`, `particulars-cell`, `particulars-text`.
- Action column visibility in edit is driven by `toggleBudgetActionCol()` and `#account-rows tr[data-row-type="additional"]`. Rows from project/DB use `data-row-type="budget"` or `"additional"`; `addAccountRow` sets `data-row-type="additional"` on new rows so the Action column is shown when any additional row exists.
- PDF/export (e.g. `PDFReport.blade.php`) may use `budget-badge` and is not covered by this implementation.
