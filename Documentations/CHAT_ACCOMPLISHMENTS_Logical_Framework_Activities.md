# Chat Accomplishments: Logical Framework – Activities and Means of Verification

Summary of all changes made in this chat for the **Activities and Means of Verification** and **Time Frame for Activities** sections on the project create and edit pages.

---

## 1. Nesting and Extra Column Bug (Create & Edit)

### Problem
When adding new rows in **Activities and Means of Verification** and **Time Frame for Activities**, rows appeared nested and an extra column was introduced.

### Root Cause
The cloned row already included a "No." cell as the first `<td>`. The script then inserted **another** "No." cell at the beginning, producing 5 cells instead of 4 and causing misalignment and nesting.

### Fixes Applied

#### Create – `resources/views/projects/partials/logical_framework.blade.php`
- **`addActivity`:** Removed the block that created and inserted an extra "No." cell. The cloned row already has: No. | Activities | Means of Verification | Action. `reindexActivities()` updates the number in the existing first cell.
- **`addTimeFrameRow`:** Removed the extra "No." cell insertion for the Time Frame table. The cloned row already has a "No." cell; `reindexTimeFrameRows()` updates it.

#### Edit – `resources/views/projects/partials/scripts-edit.blade.php`
- **`addActivity`:** The edit view uses 4 columns (with "No."); `createNewObjectiveCard` uses 3. An extra "No." cell is now added **only** when the cloned row has no "No." column (i.e. when the first cell has `table-cell-wrap` or contains a `textarea`).
- **`addTimeFrameRow`:** An extra "No." cell is added only when the cloned row does not already have one (e.g. when the first cell is `activity-description-text` or contains a `textarea`).
- **`removeActivity`:** 
  - `activityIndex` is computed **before** removing the activity row so the matching Time Frame row can be removed by index when description matching fails.
  - Description matching for the timeframe row now supports both a `textarea` inside `.activity-description-text` (edit) and plain text (create).

---

## 2. Serial Number (No.) Column – Reduce Width

### Goal
Make the "No." column narrower so it does not take more space than needed for 1–2 digit numbers.

### Changes

| File | Before | After |
|------|--------|-------|
| `resources/views/projects/partials/logical_framework.blade.php` (create – Activities table) | `min-width: 50px` | `min-width: 38px; width: 3%` |
| `resources/views/projects/partials/_timeframe.blade.php` (create – Time Frame table) | `min-width: 50px` | `min-width: 38px; width: 3%` |
| `resources/views/projects/partials/Edit/logical_framework.blade.php` (edit – Activities table) | `width: 5%` | `width: 3%` |
| `resources/views/projects/partials/edit_timeframe.blade.php` (edit – Time Frame table) | `width: 5%` | `width: 3%` |

---

## 3. Activities and Means of Verification – Column Width Adjustments

### Goals
- **Activities** and **Means of Verification:** increase width for more room to type.
- **Action:** reduce width to what is needed for the header and the Remove button.

### Changes

#### Create – `resources/views/projects/partials/logical_framework.blade.php`

| Column | Before | After |
|--------|--------|-------|
| Activities | `min-width: 200px` | `min-width: 280px` |
| Means of Verification | `min-width: 200px` | `min-width: 280px` |
| Action | `min-width: 80px` | `min-width: 72px` |

#### Edit – `resources/views/projects/partials/Edit/logical_framework.blade.php`

| Column | Before | After |
|--------|--------|-------|
| Activities | `width: 40%` | `width: 44%` |
| Means of Verification | `width: 45%` | `width: 47%` |
| Action | `width: 10%` | `width: 6%` |

#### Edit – `createNewObjectiveCard` in `resources/views/projects/partials/scripts-edit.blade.php`

| Column | Before | After |
|--------|--------|-------|
| Activities | `width: 40%` | `width: 46%` |
| Means of Verification | `width: 50%` | `width: 47%` |
| Action | `width: 10%` | `width: 7%` |

---

## Files Modified

1. `resources/views/projects/partials/logical_framework.blade.php` – create: `addActivity`, `addTimeFrameRow`, and column widths for Activities table.
2. `resources/views/projects/partials/_timeframe.blade.php` – create: No. column width for Time Frame table.
3. `resources/views/projects/partials/Edit/logical_framework.blade.php` – edit: column widths for Activities table.
4. `resources/views/projects/partials/edit_timeframe.blade.php` – edit: No. column width for Time Frame table.
5. `resources/views/projects/partials/scripts-edit.blade.php` – edit: `addActivity`, `addTimeFrameRow`, `removeActivity`, and `createNewObjectiveCard` Activities table column widths.

---

## Result

- New rows in **Activities and Means of Verification** and **Time Frame for Activities** no longer nest or add an extra column on create and edit.
- **No.** column is narrower (about 3% or 38px min) and leaves more space for content.
- **Activities** and **Means of Verification** have more width.
- **Action** column is sized to fit the header and Remove button, with freed space given to the content columns.
