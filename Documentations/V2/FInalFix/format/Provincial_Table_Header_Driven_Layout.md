# Provincial Table Header-Driven Layout

**View:** `resources/views/provincial/ProjectList.blade.php`  
**Scope:** Provincial project list blade and scoped CSS only. No controller, pagination, or export changes.  
**Goal:** Column width driven by header content; body wraps; action column fixed; no overlap, no runaway expansion.

---

## 1. Why Percentage-Based Layout Failed

- With **table-layout: fixed** and percentage widths, the table divided 100% (minus the fixed action column) among many columns. Some headers (e.g. “Requested / Sanctioned”, “Overall Project Budget”) need more space than a small percentage allowed.
- Percentages were arbitrary and did not reflect actual header or content length, so either headers wrapped or columns felt too tight.
- Different viewport sizes made the same percentages feel wrong: narrow screens over-compressed, wide screens under-used.
- No single percentage set worked well across all content, so the layout felt brittle.

---

## 2. Why Fixed Layout Caused Compression

- **table-layout: fixed** uses the first row to determine column widths, then applies those widths to all rows. With many columns and a fixed total width, each column got a fixed share regardless of header text length.
- Long header labels (e.g. “Requested / Sanctioned”, “Local Contribution”) were compressed into narrow columns, causing wrapping, overlap, or illegibility.
- Body content was also forced into those same narrow columns, so wrapping was excessive and readability suffered.
- Fixing the action column to 160px further reduced space for the rest, increasing compression on content columns.

---

## 3. Header-Driven Width Logic

- **table-layout: auto** is used so the browser sizes columns from content. The main content that drives width is the **header row**.
- **Headers:** `.provincial-project-list-table th { white-space: nowrap; font-weight: 600; }` ensures:
  - Header text never wraps, so the header cell’s natural width is the width of the header label.
  - That width becomes the effective minimum for the column (reinforced by `min-width: fit-content` on `th` and `td`).
- **Result:** Each column is at least as wide as its header. Shorter headers (e.g. “S.No”, “Role”, “Health”) get narrower columns; longer ones (e.g. “Project Title”, “Requested / Sanctioned”) get wider columns. No arbitrary percentages.

---

## 4. Action Column Isolation Logic

- The action column is the only one with an explicit width: **.col-actions { width: 160px; min-width: 160px; max-width: 160px; }**.
- It is applied to the Actions `<th>` and the actions `<td>` so the column is fixed regardless of layout algorithm.
- **Effects:**
  - Action column does not grow with content and does not shrink below 160px.
  - Buttons (View, Forward, Revert) always have a stable 160px; `.actions-wrapper` with `flex-wrap: nowrap` keeps them on one row.
  - Other columns are sized by the header-driven logic without having to “reserve” space for actions via percentages.

---

## 5. Wrapping Behavior Explanation

- **Body cells:** `.provincial-project-list-table td { white-space: normal; overflow-wrap: break-word; word-break: break-word; }` means:
  - Cell content can wrap onto multiple lines (`white-space: normal`).
  - Long words or URLs break so they stay inside the cell (`overflow-wrap: break-word`, `word-break: break-word`).
- **Result:** Body content wraps **inside** the column width (which is driven by the header). It does not expand the column and does not overflow into or overlap adjacent columns. Where `.text-cell` is used, 2-line clamp plus tooltip still limits visible lines and shows full text on hover.

---

## 6. No Overlap Guarantee

- **Headers:** `white-space: nowrap` plus `min-width: fit-content` keeps headers on one line and prevents the header row from collapsing, so headers do not overlap each other.
- **Cells:** `white-space: normal` with `overflow-wrap: break-word` and `word-break: break-word` keeps body content inside the cell; it wraps instead of overflowing.
- **Column width:** Header-driven minimum (via auto layout and fit-content) plus a fixed 160px for actions gives a deterministic layout: columns do not collapse below header size and do not run into each other. The table may scroll horizontally inside `.table-responsive` on small viewports, but columns do not overlap.

---

## 7. Confirmation Pagination/Export Unaffected

- **Pagination:** Per-page selector, `$projects->links()`, and serial resolution are unchanged. No controller or data changes.
- **Export:** Export link, route, and query parameters are unchanged. Only the table’s presentation (CSS and removal of percentage-based column classes) was modified. Export logic and output are not touched.

---

**Document path:** `Documentations/V2/FInalFix/format/Provincial_Table_Header_Driven_Layout.md`
