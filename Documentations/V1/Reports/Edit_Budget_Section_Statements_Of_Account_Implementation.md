# Budget Section (Statements of Account) — Edit Implementation

**Scope:** Report Edit — Budget / Statements of Account table  
**Location:** `resources/views/reports/monthly/partials/edit/statements_of_account/`  
**Version:** 1.0

---

## Summary

| Item | Description |
|------|-------------|
| **Action column** | Hidden by default; shown only when there is at least one **additional** (user-added) row. Budget rows: empty cell. Additional rows: "Remove" button. "Budget Row" badge removed. |
| **Font size** | Table font reduced to `0.8rem`. |
| **Particulars column** | Text wrap enabled so full text is visible (`white-space: normal`, `word-wrap: break-word`, `overflow-wrap: break-word`, `max-width: 200px`). |

---

## Files Modified

All six edit `statements_of_account` partials:

| File | Changes |
|------|---------|
| `development_projects.blade.php` | Reference implementation (already done before this session). |
| `institutional_education.blade.php` | Same pattern as `development_projects`. |
| `individual_livelihood.blade.php` | Same pattern as `development_projects`. |
| `individual_education.blade.php` | Table, Action, Particulars, JS updated; **budget CSS block** added in this session. |
| `individual_health.blade.php` | **Full pattern** applied in this session. |
| `individual_ongoing_education.blade.php` | **Full pattern** applied in this session. |

---

## 1. Table and Action Column

### 1.1 Table class

```html
<table class="table table-bordered budget-statements-table">
```

### 1.2 Header and footer

- **Header:** `<th class="budget-action-col">Action</th>`
- **Footer:** `<th class="budget-action-col"></th>`

### 1.3 Body — Edit mode

- **Budget rows** (`is_budget_row`):  
  `<td class="budget-action-col"></td>` (empty; no "Budget Row" badge).
- **Additional rows:**  
  `<td class="budget-action-col"><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>`

### 1.4 Body — Create mode

- **All rows:**  
  `<td class="budget-action-col"></td>` (empty).

---

## 2. Particulars Column

### 2.1 Edit mode

- **Budget rows:**  
  - `<td class="particulars-cell">`  
  - Hidden: `<input type="hidden" name="particulars[]" value="{{ ... }}">`  
  - Display: `<span class="particulars-text">{{ ... }}</span>`
- **Additional rows:**  
  - `<td class="particulars-cell">`  
  - `<input type="text" name="particulars[]" class="form-control" ... readonly>`

### 2.2 Create mode

- **Budget rows (source varies by type):**  
  - `development_projects`: `$budget->particular`  
  - `institutional_education`: `$budget->iies_particular`  
  - `individual_livelihood`: `$budget->budget_desc`  
  - `individual_education`: `$budget->name . ' - ' . $budget->study_proposed`  
  - `individual_health`: `$budget->particular`  
  - `individual_ongoing_education`: `$budget->particular`  
- **Markup:**  
  - `<td class="particulars-cell"><input type="hidden" name="particulars[]" value="{{ ... }}"><span class="particulars-text">{{ ... }}</span></td>`

### 2.3 `addAccountRow` (new additional row)

- Particulars cell:  
  `<td class="particulars-cell"><input type="text" name="particulars[]" class="form-control" placeholder="..." style="background-color: #202ba3;"></td>`
- Action cell:  
  `<td class="budget-action-col"><button type="button" class="btn btn-danger btn-sm" onclick="removeAccountRow(this)">Remove</button></td>`

---

## 3. JavaScript

### 3.1 `toggleBudgetActionCol()`

```javascript
function toggleBudgetActionCol() {
    var tbl = document.querySelector('.budget-statements-table');
    if (!tbl) return;
    var has = document.querySelector('#account-rows tr[data-row-type="additional"]');
    if (has) tbl.classList.add('show-action-col'); else tbl.classList.remove('show-action-col');
}
```

### 3.2 When to call

- `DOMContentLoaded`: after `reindexAccountRows`, `calculateAllRowTotals`, `calculateTotal`, `updateAllBalanceColors`, `updateBudgetSummaryCards`.
- End of `addAccountRow()`: after `reindexAccountRows()`.
- In `removeAccountRow()`: after `row.remove(); reindexAccountRows(); calculateTotal();`  
  - For `individual_health`: inside the `if (confirm(...))` block.  
  - For `individual_ongoing_education`: no `confirm`; call after `calculateTotal()`.

---

## 4. CSS

Add **before** the `.badge.scheduled-months-badge` block:

```css
/* Budget table: smaller font, Action column only when extra rows, Particulars wrap */
.budget-statements-table { font-size: 0.8rem; }
.budget-statements-table th,
.budget-statements-table td { font-size: inherit; }
.budget-statements-table .budget-action-col { display: none; }
.budget-statements-table.show-action-col .budget-action-col { display: table-cell; }
.budget-statements-table .particulars-cell { white-space: normal; word-wrap: break-word; overflow-wrap: break-word; max-width: 200px; }
.budget-statements-table .particulars-text { display: block; }
```

---

## 5. Reference File

**Fully updated example:**  
`resources/views/reports/monthly/partials/edit/statements_of_account/development_projects.blade.php`

Use it as the template for table structure, Action/Particulars markup, `addAccountRow`, `removeAccountRow`, `toggleBudgetActionCol`, and the budget CSS block.

---

## 6. Behaviour Summary

| Scenario | Action column | Particulars |
|----------|---------------|-------------|
| Only budget rows | Hidden | Wrapped text; hidden + span for budget, input for additional (edit) |
| One or more additional rows | Visible; Remove only on additional rows | Same |
| Add additional row | Shown after add | New row: `particulars-cell` with text input |
| Remove last additional row | Hidden after remove | — |

---

## 7. Notes

- **`individual_health`:** `removeAccountRow` uses `confirm(...)`; `toggleBudgetActionCol()` is called inside the confirm callback.
- **`individual_ongoing_education`:** `removeAccountRow` does **not** use `confirm`; it performs `row.remove(); reindexAccountRows(); calculateTotal(); toggleBudgetActionCol();`.
- **Duplicate `DOMContentLoaded`:** `individual_health` previously had a second `DOMContentLoaded` at the end of the script; it was removed. Only the main `DOMContentLoaded` (with `toggleBudgetActionCol()`) remains.
