# Provincial Table Final Dense Layout

**View:** `resources/views/provincial/ProjectList.blade.php`  
**Scope:** Provincial project list blade and scoped CSS only. No controller, pagination, export, or summary changes.  
**Objective:** Stable, controlled, non-overlapping, compact, production-ready table.

---

## 1. Final Column Distribution Logic

Column widths are set explicitly so the table layout is predictable and consistent across viewports:

| Class | Width | Applied to |
|-------|--------|------------|
| `.col-sno` | 4% | S.No |
| `.col-project-id` | 8% | Project ID |
| `.col-team` | 14% | Team Member |
| `.col-role` | 7% | Role |
| `.col-center` | 9% | Center |
| `.col-society` | 15% | Society |
| `.col-title` | 16% | Project Title |
| `.col-type` | 10% | Project Type |
| `.col-financial` | 8% each, `text-align: right` | Overall Budget, Existing Funds, Local Contribution, Requested / Sanctioned |
| `.col-health` | 6% | Health |
| `.col-status` | 8% | Status |
| `.col-actions` | **160px fixed** | Actions |

Every `<th>` and corresponding `<td>` uses the same class so header and body columns stay aligned. With `table-layout: fixed` and `width: 100%`, the browser allocates space by these widths; the fixed 160px for Actions is reserved, and the rest of the table uses the remaining width for the percentage columns.

---

## 2. Why Action Column Fixed Width

- **Predictable space:** Actions (View, Forward, Revert) always have 160px, so buttons do not get squeezed and do not force the column to grow or shrink with content.
- **No wrapping:** With `.actions-wrapper` using `flex-wrap: nowrap` and a fixed width, buttons stay on one row and do not push other columns.
- **Isolation:** The action column does not participate in percentage distribution, avoiding layout shifts when the number of buttons changes (e.g. when Forward/Revert are shown).
- **Readability:** 160px is enough for 2–3 `btn-sm` buttons with small padding, keeping the table compact but usable.

---

## 3. Why Fixed Layout Used

- **Stability:** `table-layout: fixed` uses the first row (and the set column widths) to determine column widths. Content in later rows does not change column sizes, so headers and cells stay aligned and do not overlap.
- **Control:** Combined with explicit `width` (and `min-width`/`max-width` on Actions), the table has a single, consistent layout strategy: no content-driven resizing.
- **Compactness:** Fixed layout allows tight padding (0.4rem 0.5rem) and smaller buttons without columns collapsing or overlapping.

---

## 4. How Overlap Is Prevented

- **Headers:** `.provincial-project-list-table th { white-space: nowrap; }` keeps header text on one line so headers do not wrap into each other.
- **Cells:** `.provincial-project-list-table td { white-space: normal; overflow-wrap: break-word; word-break: break-word; }` lets long words break inside the cell instead of overflowing or overlapping adjacent columns.
- **Column widths:** Every column has a defined width (percent or 160px); nothing is left to auto-sizing that could cause one column to push another.
- **Text clamp:** `.text-cell` still uses `-webkit-line-clamp: 2` for title/team/society/center where used, so long text is limited to two lines and full text is in the tooltip.

---

## 5. Row Height Optimization

- **Padding:** `padding: 0.4rem 0.5rem` on `th` and `td` (reduced from 0.5rem) lowers vertical row height while keeping content readable.
- **Vertical alignment:** `vertical-align: middle` is unchanged so content stays centered in the row.
- **No extra margins:** No additional margin on cells or inner elements that would increase row height.

---

## 6. Button Size Reduction Explanation

- **Rule:** `.provincial-project-list-table .btn { padding: 0.25rem 0.5rem; font-size: 0.75rem; line-height: 1.2; }` applies only inside the provincial project list table.
- **Purpose:** Buttons (View, Forward, Revert) stay compact so they fit in the 160px action column without wrapping and without increasing row height.
- **Readability:** Font size 0.75rem and line-height 1.2 keep text legible; padding is reduced but not removed so targets remain easy to click.

---

## 7. Confirmation Export Unaffected

- Export link, route, and query parameters are unchanged. Only the table’s presentation (CSS and column classes) was updated. Export logic and output are not modified.

---

## 8. Confirmation Pagination Unaffected

- Per-page selector, `$projects->links()`, and serial resolution are unchanged. No changes to controller or pagination logic.

---

## 9. Performance Impact (None)

- Changes are limited to the view: one inline `<style>` block and class names on existing `<th>`/`<td>`. No new assets, no new JavaScript, no change to queries or payload size. No measurable performance impact.

---

**Document path:** `Documentations/V2/FInalFix/format/Provincial_Table_Final_Dense_Layout.md`
