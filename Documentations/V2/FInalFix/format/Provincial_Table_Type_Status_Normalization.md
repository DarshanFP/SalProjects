# Provincial Table Type & Status Column Normalization

**View:** `resources/views/provincial/ProjectList.blade.php`  
**Scope:** Provincial project list blade and scoped CSS only. No controller changes; badges and action column unchanged.  
**Goal:** Make Project Type and Status behave like other text columns—no special width rules; action column remains fixed.

---

## 1. Previous Issue with Type/Status

- **Project Type** and **Status** use badge markup. Bootstrap’s `.badge` often uses `white-space: nowrap` (or similar) by default, so long labels did not wrap and could stretch the column or force horizontal overflow.
- In earlier layouts, **col-type** and **col-status** had percentage or fixed widths, so these columns were treated differently from other content columns and could feel cramped or inconsistent.
- Long project type names or long status labels (e.g. “Reverted by Coordinator”) could overlap adjacent columns or push the table width when they did not wrap.

---

## 2. Why Width Rules Removed

- The table uses **header-driven width** (`table-layout: auto`): column width is determined by header text and content, with no percentage-based column widths.
- **No `.col-type` or `.col-status`** (or any other column) have explicit width in the current CSS. Only **`.col-actions`** has a fixed width (160px).
- Type and Status are treated as **normal columns**: they get their width from the header row like S.No, Role, Center, etc. Removing any special width rules keeps behavior consistent and avoids over-constraining these two columns.

---

## 3. Wrapping Normalization Strategy

- **All body cells** already use:
  - `white-space: normal;`
  - `overflow-wrap: break-word;`
  - `word-break: break-word;`
  so content wraps inside the cell.
- **Type and Status cells** wrap their content in a **`.text-wrap-cell`** container:
  - `.text-wrap-cell { display: block; max-width: 100%; }` so the wrapper does not exceed the cell and allows wrapping.
- **Badges** inside the table are normalized with:
  - `.provincial-project-list-table .badge { white-space: normal; }` so badge text can wrap instead of forcing one long line.
- Result: Type and Status columns wrap like other text columns; they do not stretch the column or overlap others.

---

## 4. Badge Behavior Explanation

- **Badges kept:** Project Type and Status still render as `<span class="badge ...">`. No removal or replacement of badges.
- **Wrapping:** The scoped rule `.provincial-project-list-table .badge { white-space: normal; }` overrides the default (often nowrap) behavior so long labels can wrap within the cell.
- **Container:** Each badge sits inside a `<div class="text-wrap-cell">` so the block is constrained to `max-width: 100%` and stays within the column. Tooltips on the wrapper show full project type or full status label on hover.
- **No min-width:** Badges do not have a min-width; column width remains driven by the header and table layout.

---

## 5. Confirmation Action Column Unchanged

- Only **`.col-actions`** has fixed dimensions: `width: 160px; min-width: 160px; max-width: 160px;`
- No other column has fixed or percentage width in the scoped CSS.
- Action column markup (`.col-actions`, `.actions-wrapper`, buttons) is unchanged.

---

## 6. Confirmation No Overlap

- **Headers:** `th { white-space: nowrap; }` keeps header text on one line.
- **Body:** `td { white-space: normal; overflow-wrap: break-word; word-break: break-word; }` keeps content inside cells.
- **Type/Status:** Wrapping in `.text-wrap-cell` and `white-space: normal` on badges ensures long type/status text wraps inside the column instead of overflowing or overlapping.

---

## 7. Confirmation Export/Pagination Unaffected

- **Export:** Link, route, and query parameters unchanged. Only table presentation (CSS and wrapper divs for Type/Status) was modified.
- **Pagination:** Per-page selector and `$projects->links()` unchanged. No controller or data changes.

---

**Document path:** `Documentations/V2/FInalFix/format/Provincial_Table_Type_Status_Normalization.md`
