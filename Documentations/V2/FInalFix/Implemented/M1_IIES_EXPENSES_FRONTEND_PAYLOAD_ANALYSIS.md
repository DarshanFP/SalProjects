# M1 — IIES Expenses Frontend Payload Analysis

**Date:** 2026-02-14  
**Type:** Read-only diagnostic. No code was modified.

---

## 1. Blade File and Form Source

### 1.1 Blade file path

- **Edit (project edit page):** `resources/views/projects/partials/Edit/IIES/estimated_expenses.blade.php`
- **Included from:** `resources/views/projects/Oldprojects/edit.blade.php` (line 69), only when `$project->project_type === 'Individual - Initial - Educational support'`.

### 1.2 Input names

- **Child rows:** `iies_particulars[]`, `iies_amounts[]` (one pair per table row).
- **Parent fields:** `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`.

### 1.3 Form method and route

- The IIES expenses section is **inside the full project edit form**, not a separate form.
- **Form id:** `editProjectForm`
- **Action:** `{{ route('projects.update', $project->project_id) }}` → `PUT /projects/{project_id}` (ProjectController@update).
- **Method:** POST with `@method('PUT')` (enctype multipart/form-data).
- **Submit:** Normal full-page submit (no AJAX). "Save Changes" button triggers `editForm.submit()` after adding optional hidden `save_as_draft` and disabling the button.

### 1.4 How IIESExpensesController is invoked

- The browser does **not** post to IIESExpensesController.
- The browser posts to **ProjectController@update** with the full edit form body.
- ProjectController@update, for project type `Individual - Initial - Educational support`, calls `$this->iiesExpensesController->update($request, $project->project_id)` (line 1507).
- So **the payload to IIESExpensesController::store/update is the same as the full request** to ProjectController@update (all form fields, including every other section). The controller uses only the IIES-related keys from that request.

---

## 2. Rendering and Row Behavior

### 2.1 How rows are rendered

- **When there are existing expense details:**  
  `@if ($iiesExpenses && $iiesExpenses->expenseDetails->count())` → `@foreach ($iiesExpenses->expenseDetails as $index => $detail)`  
  One `<tr>` per detail with:
  - `name="iies_particulars[]"` value `$detail->iies_particular`
  - `name="iies_amounts[]"` value `$detail->iies_amount`
- **When there are no details:**  
  `@else` → a single empty row (one pair of empty `iies_particulars[]` / `iies_amounts[]`).
- So inputs are **not** hidden; they are real inputs in the DOM, filled from DB (or empty for the single placeholder row).

### 2.2 Empty rows

- "Add More" adds new `<tr>` with empty `iies_particulars[]` and `iies_amounts[]`. So empty rows **are** rendered and submitted if the user adds them and does not remove them.

### 2.3 JavaScript row behavior

- **IIESaddExpenseRow():** Inserts a new `<tr>` with `iies_particulars[]` and `iies_amounts[]` at the end of `#IIES-expenses-table`. Row is **added to the DOM**.
- **IIESremoveExpenseRow(button):** `button.closest('tr').remove()` — the row is **removed from the DOM**, not hidden.
- **reindexIIESExpenseRows():** Only updates the visible "No." column (first cell text). It does **not** change input names or add/remove inputs.
- So on submit, **only rows that remain in the DOM** are submitted. Removed rows are not in the request.

### 2.4 Array indexes

- Inputs use `name="iies_particulars[]"` and `name="iies_amounts[]"`. The browser sends them in **DOM order** as sequential array indices (0, 1, 2, …).
- After "Remove", the remaining rows keep DOM order, so submitted indices are **reindexed** (0, 1, 2, … for whatever rows are left). Original DB indexes are not preserved in the request.

---

## 3. Submission Behavior When User Edits Only One Row

### 3.1 Scenario: user changes only one visible row (no add/remove)

- The table still has the **same number of rows** as when the page loaded (e.g. 3 rows from DB).
- User edits one cell (e.g. amount in row 2). No "Add More" or "Remove" is used.
- User clicks "Save Changes" → full form submit.

**What the browser submits:** **All rows currently in the table.**  
So for 3 rows we get e.g.:

- `iies_particulars[0]`, `iies_particulars[1]`, `iies_particulars[2]`
- `iies_amounts[0]`, `iies_amounts[1]`, `iies_amounts[2]`

Plus all other form fields (general info, objectives, other IIES sections, etc.).

**Conclusion:** **A) All rows currently rendered in the form are submitted.** Not only the edited row, and not only non-empty rows. Empty-looking rows (if any) are still in the DOM and are submitted too.

### 3.2 Scenario: user removes some rows then saves

- Rows removed via "Remove" are **removed from the DOM**, so they are **not** in the request.
- Only the rows left in the table are submitted. So we get a **subset** of rows by design (user chose to remove rows).

### 3.3 Scenario: user adds rows then saves

- New rows from "Add More" are in the DOM and submitted like any other row. So we get **more** rows than were loaded from DB.

---

## 4. Controller Expectation Match

### 4.1 IIESExpensesController::store() (and update → store)

- **Parent data:** Taken from `$validated`: `iies_total_expenses`, `iies_expected_scholarship_govt`, `iies_support_other_sources`, `iies_beneficiary_contribution`, `iies_balance_requested`.
- **Child data:** `$particulars = $validated['iies_particulars'] ?? []`, `$amounts = $validated['iies_amounts'] ?? []`.
- **Create loop:** `foreach ($particulars as $index => $particular)` → uses `$amounts[$index]` for the same index. Only creates a detail row when `!empty($particular) && isset($amounts[$index]) && $amounts[$index] !== null && $amounts[$index] !== ''`.
- **No filtering of empty rows before the loop:** Empty rows are simply skipped in the create step (no DB row created for that index). So the controller **replaces** all existing expense data with exactly the set of rows that pass the non-empty check.

### 4.2 If frontend submits only one row

- If the **form** contained only one row in the DOM (e.g. user removed the others, or only one row was ever rendered), then the request would have e.g. `iies_particulars[0]`, `iies_amounts[0]` only.
- Controller would: delete all existing parent/child rows for that project, create one parent, create one detail row (if that row is non-empty). So **yes: all previous DB rows are deleted and replaced with that one row.** That is consistent with "full form submit" for the current state of the form (one row = user chose to have one row or page showed one row).

---

## 5. Example Payloads and Controller Behavior

### CASE A — Full form submit (3 rows rendered, 1 edited)

User loads edit; DB had 3 expense details. Table shows 3 rows. User changes the amount in row 2 and saves. No add/remove.

**Example request payload (IIES-relevant keys only):**

```text
_method: PUT
iies_particulars[0]: Tuition
iies_amounts[0]: 30000
iies_particulars[1]: Books
iies_amounts[1]: 5000
iies_particulars[2]: Transport
iies_amounts[2]: 2000
iies_total_expenses: 37000
iies_expected_scholarship_govt: 10000
iies_support_other_sources: 0
iies_beneficiary_contribution: 5000
iies_balance_requested: 17000
... (rest of project form)
```

**Controller behavior:**  
Guard sees meaningful parent and children → proceeds. Deletes existing IIES expense parent and all detail rows. Creates one parent with the totals above. Creates three detail rows (Tuition/30000, Books/5000, Transport/2000). Sync runs. **Result:** 3 rows in DB, as intended. No data loss.

### CASE B — Only 1 row submitted

User had 3 rows, removed 2 via "Remove", then saved. So the form has only one row in the DOM.

**Example request payload (IIES-relevant keys only):**

```text
iies_particulars[0]: Tuition
iies_amounts[0]: 30000
iies_total_expenses: 30000
...
```

**Controller behavior:**  
Guard sees meaningful data → proceeds. Deletes all existing expense rows. Creates one parent and one detail row (Tuition, 30000). **Result:** 1 row in DB. This matches the current form state (user intentionally removed two rows). So this is **intended** replacement, not accidental partial loss from "editing one row."

---

## 6. Risk Conclusion

### 6.1 Does the frontend always submit the full dataset?

- **Yes**, for the **current state of the form.**  
  The frontend submits **every row that is currently in the IIES expenses table** when the user clicks Save. There is no AJAX that sends only one section or one row. So:
  - "Full dataset" = all rows present in the DOM at submit time.
  - If the user does not add or remove rows, the submitted set matches the rows that were loaded from DB (plus any edits). So for "edit one row only" (no add/remove), the full set of rows is submitted and no partial-row data loss occurs.

### 6.2 Is partial-row data loss possible?

- **Only in line with user actions:**
  - If the user removes rows with "Remove", those rows are no longer in the DOM and are not submitted → controller replaces DB with the reduced set. That is intentional.
  - If due to a bug or mis-load the form rendered fewer rows than exist in DB (e.g. only one row when DB had three), then submitting would replace DB with that smaller set → that would be a **bug in data loading/rendering**, not in the submit payload itself.
- Under **normal use** (edit one row, no remove): **No.** All rows are in the form and all are submitted.

### 6.3 Is the architecture safe under the current UI?

- **Yes.** The UI is a single full-page form; all IIES expense rows in the table are submitted together. The controller’s delete-then-recreate behavior is designed for "replace with what the form sent." The M1 guard ensures that when the section is absent or empty we do not wipe data; when the section has meaningful data we replace with the submitted set. That matches the UI, which always sends the full current set of rows (and parent fields) for the IIES section.

### 6.4 Risk level

- **LOW** for the question "what payload is submitted when only one expense row is edited?"  
  Editing one row does **not** cause only that row to be submitted; the full table (all rows) is submitted. Partial-row data loss from "editing one row" does not occur. The only way the backend receives "only one row" is when the form actually contains one row (e.g. user removed the others), in which case replacing DB with one row is intended.

---

## 7. Summary Table

| Question | Answer |
|----------|--------|
| Blade file | `resources/views/projects/partials/Edit/IIES/estimated_expenses.blade.php` |
| Route used | Form posts to `projects.update` (ProjectController@update). IIESExpensesController receives same request via `update($request, $project->project_id)`. |
| Submission method | Normal form submit (POST with _method PUT). Not AJAX. |
| Rows rendered | @foreach over `$iiesExpenses->expenseDetails` (from DB), or one empty row if none. |
| Rows submitted | All rows currently in `#IIES-expenses-table` (DOM order; removed rows are not submitted). |
| When user edits one row only | All rows (e.g. 3) are submitted; no partial-row payload. |
| Controller create loop | Iterates `$particulars` by index; uses `$amounts[$index]`; skips empty particular/amount; no pre-filter. |
| Frontend always full dataset? | Yes (full = all rows in the form at submit time). |
| Partial-row data loss from editing one row? | No. |
| Risk level | LOW. |

---

*End of analysis. No code, validation, logging, or guards were modified.*
