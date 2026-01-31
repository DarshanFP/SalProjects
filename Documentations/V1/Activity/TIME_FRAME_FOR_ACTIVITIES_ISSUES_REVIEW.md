# Time Frame for Activities – Issues Review

**Context:** Project edit page (Executor Dashboard), Logical Framework section – "Time Frame for Activities" table.  
**Scope:** Edit mode JavaScript (`scripts-edit.blade.php`), server-rendered partials (`edit_timeframe.blade.php`, `_timeframe.blade.php`).  
**Date:** 2026-01-30

---

## 1. Executive Summary

The Time Frame for Activities section has the **same structural and indexing issues** as the Activities and Means of Verification table. The JavaScript that builds or resets Time Frame rows uses a **different column schema** than the Blade partials, causing layout misalignment, wrong reindexing, and potential data/UX gaps for dynamically added objectives (4th objective onward).

---

## 2. Verdict

| Issue Category | Present? | Severity |
|----------------|----------|----------|
| Column count mismatch (No. column missing in JS) | **Yes** | High |
| Wrong cell targeted by reindexTimeFrameRows | **Yes** | High |
| Missing textarea for timeframe description | **Yes** | Medium |
| Missing Add Row button (createNewObjectiveCard only) | **Yes** | Low |
| Missing table-responsive / timeframe-table classes | **Yes** | Low |

---

## 3. Canonical Structure (Edit Blade)

**File:** `resources/views/projects/partials/edit_timeframe.blade.php`

### 3.1 Table structure

| Column | Width | Content |
|--------|-------|---------|
| 1. No. | 3% | Serial number (1, 2, 3…) |
| 2. Activities | 45% | Textarea with `name="objectives[X][activities][Y][timeframe][description]"` |
| 3–14. Jan–Dec | calc(45% / 12) each | Month checkboxes |
| 15. Action | 5% | Remove button |

**Total:** 15 columns.

### 3.2 Row structure (tbody)

Each row has 15 `<td>` elements:

1. `<td style="text-align: center; vertical-align: middle;">1</td>` (No.)
2. `<td class="activity-description-text table-cell-wrap">` containing a **textarea** for the activity/timeframe description
3. 12 `<td class="text-center month-checkbox-cell">` with checkboxes
4. `<td>` with Remove button

### 3.3 Other details

- Wrapper: `<div class="table-responsive">` around the table
- Table: `class="table table-bordered table-sm timeframe-table"`
- Card header: "Add Row" button calling `addTimeFrameRow(this)`

---

## 4. JavaScript Template Structure (Mismatch)

**File:** `resources/views/projects/partials/scripts-edit.blade.php`

### 4.1 createNewObjectiveCard() – Time Frame table (lines 321–343)

| Aspect | Edit Blade | JS Template | Match? |
|--------|------------|-------------|--------|
| thead columns | 15 (No., Activities, Jan–Dec, Action) | **14** (Activities, Jan–Dec, Action) | **No** – No. column missing |
| thead widths | No. 3%, Activities 45%, Action 5% | Activities 40%, Action 6% | **No** |
| First tbody cell | No. (number) | **activity-description-text** (empty) | **No** |
| Second tbody cell | activity-description-text **with textarea** | (N/A – first cell is description) | **No** |
| activity-description-text content | Textarea with `name="...timeframe][description]"` | **Empty td** – no textarea | **No** |
| table-responsive wrapper | Yes | **No** | **No** |
| timeframe-table class | Yes | **No** (`table table-bordered` only) | **No** |
| Add Row button | Yes (in card header) | **No** | **No** |

**JS template row (lines 335–339):**

```html
<tr class="activity-timeframe-row">
    <td class="activity-description-text"></td>   <!-- Empty; no textarea. Should be 2nd cell; 1st should be No. -->
    <!-- 12 checkbox cells -->
    <td><button ...>Remove</button></td>
</tr>
```

So the row has **14 cells**; the first is `activity-description-text` with no content and no form input.

### 4.2 resetObjectiveSections() – Time Frame row (lines 410–418)

Same structure as createNewObjectiveCard:

- One row with 14 cells: `activity-description-text` (empty) + 12 checkboxes + Remove
- No No. cell
- No textarea inside `activity-description-text`

---

## 5. Root Cause Analysis

### 5.1 Column count mismatch (same pattern as Activities table)

- **Server-rendered objectives (1–3):** Use `edit_timeframe.blade.php` → 15 columns, correct layout.
- **Dynamically added objectives (4+):** Use either:
  - Cloned card + `resetObjectiveSections()` → tbody row replaced with 14-cell row; thead stays 15-column from clone → **mixed structure**
  - `createNewObjectiveCard()` → entire table built with 14 columns → **full mismatch**

Consequences:

1. First row: No dedicated No. cell; first cell is `activity-description-text`.
2. `addTimeFrameRow()` adds a No. cell only to **new** rows (lines 862–868), so the first row keeps 14 cells while new rows have 15 → mixed row lengths.
3. Column alignment breaks; Remove button can appear under the wrong header.

### 5.2 reindexTimeFrameRows() overwrites wrong cell

**Code (lines 912–920):**

```javascript
function reindexTimeFrameRows(timeFrameTbody) {
    const timeframeRows = timeFrameTbody.querySelectorAll('.activity-timeframe-row');
    timeframeRows.forEach((row, index) => {
        const indexCell = row.querySelector('td:first-child');
        if (indexCell) {
            indexCell.textContent = index + 1;
        }
    });
}
```

For 14-cell rows, `td:first-child` is the **activity-description-text** cell (which may hold the synced activity description). Setting `indexCell.textContent = index + 1` overwrites that content with "1", "2", etc., so:

- Serial number appears in the wrong column
- Activity description text is lost or corrupted

### 5.3 Missing textarea for timeframe description

**Edit Blade:** The `activity-description-text` cell contains a textarea:

```html
<textarea name="objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][description]" ...>
```

**JS template:** The cell is empty: `<td class="activity-description-text"></td>`.

Impact:

- `attachActivityEventListeners` sets `descriptionText.innerText = activityDescription` – works for display.
- `updateNameAttributes` only syncs `innerText` and does not create/update a description input.
- There is no `objectives[X][activities][Y][timeframe][description]` input for JS-built rows.
- The backend (`LogicalFrameworkController`) does not appear to use `timeframe][description]`; it uses `activity` and `timeframe][months]`. So this may not cause data loss, but:
  - The UI allows editing the description in the Blade partial; that behavior is inconsistent for JS-built rows.
  - Any future use of `timeframe][description]` would fail for those rows.

### 5.4 removeActivity() description matching

**Code (lines 768–775):**

```javascript
timeFrameRows.forEach((timeFrameRow) => {
    const descEl = timeFrameRow.querySelector('.activity-description-text');
    const timeFrameDescription = descEl ? (descEl.querySelector('textarea') ? descEl.querySelector('textarea').value : descEl.innerText) : '';
    if (timeFrameDescription === activityDescription) {
        matchingTimeFrameRow = timeFrameRow;
    }
});
```

This supports both textarea and `innerText`. For JS-built rows (no textarea), it uses `innerText`, so matching can still work if the description was synced. Fallback by index (lines 778–779) also applies. So this part is relatively robust.

### 5.5 addTimeFrameRow() conditional No. cell

**Code (lines 862–868):**

```javascript
const firstTd = newTimeFrameRow.querySelector('td:first-child');
if (firstTd && (firstTd.classList.contains('activity-description-text') || firstTd.querySelector('textarea'))) {
    const indexCell = document.createElement('td');
    indexCell.style.cssText = 'text-align: center; vertical-align: middle;';
    indexCell.textContent = timeFrameTbody.children.length + 1;
    newTimeFrameRow.insertBefore(indexCell, newTimeFrameRow.firstChild);
}
```

When cloning a 14-cell row, the first cell is `activity-description-text`, so a No. cell is prepended. New rows end up with 15 cells, while the original 14-cell row stays as-is → mixed structure and misalignment.

---

## 6. File and Line References

| File | Lines | Issue |
|------|-------|-------|
| `scripts-edit.blade.php` | 327–332 | createNewObjectiveCard: thead has 14 columns (no No.) |
| `scripts-edit.blade.php` | 335–339 | createNewObjectiveCard: tbody row has 14 cells; first is empty activity-description-text (no textarea) |
| `scripts-edit.blade.php` | 321–324 | createNewObjectiveCard: no Add Row button, no table-responsive, no timeframe-table |
| `scripts-edit.blade.php` | 414–417 | resetObjectiveSections: same 14-cell row, no No. cell, no textarea |
| `scripts-edit.blade.php` | 862–868 | addTimeFrameRow: adds No. only to new rows → mixed row structure |
| `scripts-edit.blade.php` | 915–919 | reindexTimeFrameRows: sets td:first-child → overwrites activity-description-text on 14-cell rows |
| `edit_timeframe.blade.php` | 10–46 | Canonical 15-column structure (reference) |

---

## 7. Recommendations

### 7.1 Align JS template with Edit Blade (high priority)

1. **createNewObjectiveCard() – Time Frame table**
   - Add No. column to thead: `<th scope="col" style="width: 3%;">No.</th>`
   - Adjust column widths to match edit_timeframe (e.g. Activities 45%, Action 5%)
   - Add `table-responsive` wrapper and `timeframe-table` class
   - Add "Add Row" button in the card header
   - First tbody row: 15 cells – No. | activity-description-text (with textarea) | 12 checkboxes | Action

2. **resetObjectiveSections() – Time Frame row**
   - Build a 15-cell row: No. "1" | activity-description-text (with textarea) | 12 checkboxes | Remove

### 7.2 Textarea in activity-description-text

- Ensure the JS-built `activity-description-text` cell contains a textarea with the correct `name` pattern so that `objectives[X][activities][Y][timeframe][description]` is submitted when used.
- `updateNameAttributes` should set the textarea `name` when present.

### 7.3 reindexTimeFrameRows safety

- After fixing the template, every row will have a No. cell as `td:first-child`, so `reindexTimeFrameRows` will only update the index cell.
- Optionally add a guard: only set `textContent` if the cell does not contain a textarea (i.e. is clearly the index cell).

### 7.4 addTimeFrameRow

- Once all rows have 15 cells and a No. cell, the condition `firstTd.classList.contains('activity-description-text')` will be false for the first cell (it will be the No. cell), so no extra No. cell will be inserted. The current logic will behave correctly.

---

## 8. Checklist for Implementation

- [ ] Add No. column to Time Frame thead in createNewObjectiveCard()
- [ ] Add No. cell to first Time Frame tbody row in createNewObjectiveCard()
- [ ] Add textarea inside activity-description-text in createNewObjectiveCard()
- [ ] Add No. cell and textarea in resetObjectiveSections() Time Frame row
- [ ] Add table-responsive wrapper and timeframe-table class
- [ ] Add "Add Row" button in Time Frame card header (createNewObjectiveCard)
- [ ] Align column widths with edit_timeframe (No. 3%, Activities 45%, Action 5%)
- [ ] Ensure updateNameAttributes sets textarea name for timeframe description when present
- [ ] Test: add 4th objective, add activities, add timeframe row, verify layout and serial numbers

---

## 9. Related Documentation

- `Documentations/V1/Activity/ACTIVITIES_MEANS_OF_VERIFICATION_LAYOUT_REVIEW.md` – Same class of issues in the Activities table; fix pattern is analogous.
