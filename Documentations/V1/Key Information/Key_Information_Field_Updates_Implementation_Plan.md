# Key Information Field Updates — Implementation Plan

## 1. Overview

This document describes the implementation plan for updating the **Key Information** section in the Project module. The changes fall into three categories:

1. **Label changes (UI only)** — Renaming four existing field labels without changing database column names.
2. **New field addition** — Adding a new textarea field "Prevailing economic situation in the project area" with a corresponding database column.
3. **Word count rule (frontend only)** — ~~Enforcing a minimum of 100 words per textarea via JavaScript only; no backend or database validation.~~ **REMOVED (2026-02-01):** The 100-word minimum was removed. For some project types these fields may be irrelevant; requiring 100 words forced users to enter irrelevant filler text.

---

## 2. Files Identified

### 2.1 Blade Views

| File | Purpose |
|------|---------|
| `resources/views/projects/partials/key_information.blade.php` | Create form — Key Information section |
| `resources/views/projects/partials/Edit/key_information.blade.php` | Edit form — Key Information section |
| `resources/views/projects/partials/Show/key_information.blade.php` | View/Show page — Key Information display |

### 2.2 Parent Views That Include Key Information

| File | Usage |
|------|-------|
| `resources/views/projects/Oldprojects/createProjects.blade.php` | Includes `partials/key_information` for project create |
| `resources/views/projects/Oldprojects/edit.blade.php` | Includes `partials/Edit/key_information` for project edit |
| `resources/views/projects/Oldprojects/show.blade.php` | Includes `partials/Show/key_information` for project show |
| `resources/views/projects/Oldprojects/pdf.blade.php` | Includes `partials/Show/key_information` for PDF generation |

### 2.3 Controllers

| File | Responsibility |
|------|----------------|
| `app/Http/Controllers/Projects/KeyInformationController.php` | `store()` and `update()` — persists Key Information fields |
| `app/Http/Controllers/Projects/ProjectController.php` | Orchestrates create/update; calls `KeyInformationController` for both store and update |
| `app/Http/Controllers/Projects/GeneralInfoController.php` | Stores general info only; does **not** handle Key Information fields |
| `app/Http/Controllers/Projects/ExportController.php` | Exports Key Information to Word document (uses labels) |
| `app/Http/Controllers/Projects/ProjectController.php` (getProjectDetails) | Returns Key Information for predecessor/prefill logic |

### 2.4 Request Validation

| File | Purpose |
|------|---------|
| `app/Http/Requests/Projects/StoreProjectRequest.php` | Validates create request (includes Key Information fields) |
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | Validates update request (includes Key Information fields) |

**Note:** Per requirements, do **not** add Laravel/request validation for word count.

### 2.5 Model

| File | Purpose |
|------|---------|
| `app/Models/OldProjects/Project.php` | Defines `$fillable` and model properties for Key Information fields |

### 2.6 Migrations

| File | Purpose |
|------|---------|
| `database/migrations/2026_01_07_182657_add_key_information_fields_to_projects_table.php` | Adds `initial_information`, `target_beneficiaries`, `general_situation`, `need_of_project` |
| **New migration (to be created)** | Adds `economic_situation` column |

### 2.7 JavaScript

| File | Purpose |
|------|---------|
| `resources/views/projects/Oldprojects/createProjects.blade.php` | Inline script — form submit handler for create |
| `resources/views/projects/Oldprojects/edit.blade.php` | Inline script — form submit handler for edit |
| `resources/views/projects/partials/scripts.blade.php` | Shared scripts for create form (no current Key Information validation) |
| `resources/views/projects/partials/scripts-edit.blade.php` | Shared scripts for edit form (no current Key Information validation) |

### 2.8 Shared Partials

- Key Information is rendered via the partials listed above; there are no sub-partials for individual fields.

---

## 3. Field Mapping Table

| Old Label | New Label | DB Column | Notes |
|-----------|-----------|-----------|-------|
| Initial Information | **Prevailing social situation in the project area and its adverse effect on life** | `initial_information` | UI label change only |
| Target Beneficiaries | **Detailed information on target beneficiary of the project** | `target_beneficiaries` | UI label change only |
| General Situation | **Educational & cultural situation in the project area** | `general_situation` | UI label change only |
| Need of the Project | Need of the Project *(unchanged)* | `need_of_project` | No change |
| — | **Prevailing economic situation in the project area** | `economic_situation` | **New field** — requires migration |
| Goal of the Project | Goal of the Project *(unchanged)* | `goal` | No change |

---

## 4. Database Change Plan

### 4.1 New Migration for `economic_situation`

- **Migration name (suggested):** `YYYY_MM_DD_HHMMSS_add_economic_situation_to_projects_table.php`
- **Column definition:** `$table->text('economic_situation')->nullable()->before('goal');`
- **Rationale for `TEXT`:** Matches existing Key Information columns (`initial_information`, etc.). MySQL `TEXT` supports up to 65,535 characters.
- **Alternative:** Use `$table->longText('economic_situation')` if future requirements expect longer content.

### 4.2 Placement

- Add `economic_situation` before `goal` to maintain the logical order:  
  initial_information → target_beneficiaries → general_situation → need_of_project → **economic_situation** → goal

### 4.3 Backward Compatibility

- New column is `nullable`; existing rows will have `NULL`.
- No data migration needed.
- Existing projects will show empty value for the new field until edited.

### 4.4 Down Method

```php
$table->dropColumn('economic_situation');
```

---

## 5. UI Update Plan

### 5.1 Create Form (`partials/key_information.blade.php`)

1. Update labels:
   - Line 9: `Initial Information` → `Prevailing social situation in the project area and its adverse effect on life`
   - Line 17: `Target Beneficiaries` → `Detailed information on target beneficiary of the project`
   - Line 27: `General Situation` → `Educational & cultural situation in the project area`
2. Insert new textarea after "Need of the Project" and before "Goal of the Project":
   - Label: `Prevailing economic situation in the project area`
   - Name: `economic_situation`
   - ID: `economic_situation`
   - Class: `form-control sustainability-textarea key-information-textarea` (for JS targeting)
   - Rows: 3
   - Include `@error('economic_situation')` block
3. Add a container/placeholder for per-field word-count error messages (for JavaScript validation).
4. Add data attribute or class (e.g. `data-min-words="100"`) to all six textareas for reusable validation.

### 5.2 Edit Form (`partials/Edit/key_information.blade.php`)

1. Same label changes as create form.
2. Same new `economic_situation` field with `{{ old('economic_situation', $project->economic_situation) }}`.
3. Same error block and data attributes for validation.

### 5.3 View Page (`partials/Show/key_information.blade.php`)

1. Update display labels to match new wording.
2. Add block for `economic_situation`:
   ```blade
   @if($project->economic_situation)
       <div class="mb-3">
           <div class="info-label"><strong>Prevailing economic situation in the project area:</strong></div>
           <div class="info-value">{{ $project->economic_situation }}</div>
       </div>
   @endif
   ```
3. Update the "no key information" condition to include `economic_situation`.

### 5.4 Export (ExportController)

- Update `addKeyInformationSection()` to use new labels and add `economic_situation`:
  - Replace hardcoded labels with the new text.
  - Add block for `economic_situation` (positioned before Goal).

---

## 6. JavaScript Validation Plan

### 6.1 Where Validation Should Live

- **Option A (recommended):** Dedicated JS file (e.g. `public/js/key-information-validation.js`) included on create and edit pages.
- **Option B:** Inline in `createProjects.blade.php` and `edit.blade.php`.
- **Option C:** In `scripts.blade.php` and `scripts-edit.blade.php` with conditional logic (only when Key Information section exists).

Recommendation: Option A for reuse and easier maintenance.

### 6.2 Word Count Calculation

- **Algorithm:** Split trimmed text by whitespace; count non-empty segments.
- **Example:** `text.trim().split(/\s+/).filter(Boolean).length`
- **Edge cases:**
  - Empty string → 0 words.
  - Multiple spaces between words → counted as one word each.
  - Line breaks → treated as word separators.

### 6.3 Validation Logic

1. **Selector:** All Key Information textareas:  
   `#initial_information, #target_beneficiaries, #general_situation, #need_of_project, #economic_situation, #goal`
2. **Minimum:** 100 words per field.
3. **When to validate:** On form `submit` (before `form.submit()` or default submit).
4. **Draft saves:** Skip validation when `input[name="save_as_draft"]` has value `1`.

### 6.4 Error Handling Strategy

1. On submit (non-draft):
   - For each field, compute word count.
   - If any field has < 100 words, prevent default and stop submission.
   - Show inline error per field, e.g.:  
     `"This field must contain at least 100 words (current: X words)."`
2. Error placement: Below each textarea (e.g. `<span class="text-danger key-info-word-error">`).
3. Clear errors on `input`/`change` when the user edits the field.
4. Optional: Real-time word count display, e.g. "X / 100 words".

### 6.5 Reusability Across Forms

- Use a single validation function that:
  - Accepts a form element and an optional "is draft" flag.
  - Queries Key Information textareas within that form.
  - Returns `{ valid: boolean, errors: [{ id, message }] }`.
- Call this from both create and edit form submit handlers.
- Ensure both forms use the same textarea IDs and error container structure.

### 6.6 Integration Points

| Form | Submit Handler Location | Action |
|------|-------------------------|--------|
| Create | `createProjects.blade.php` inline script, `createProjectForm` submit | Call validation; if invalid, `e.preventDefault()` and show errors |
| Edit | `edit.blade.php` inline script, `editProjectForm` submit | Same as create |

---

## 7. Controller & Model Updates

### 7.1 KeyInformationController

- **store():**
  - Add `'economic_situation' => 'nullable|string'` to validation rules.
  - Add handling: `if (array_key_exists('economic_situation', $validated)) { $project->economic_situation = $validated['economic_situation']; }`
- **update():** Same validation and assignment logic.

### 7.2 StoreProjectRequest & UpdateProjectRequest

- Add `'economic_situation' => 'nullable|string'` to rules (no word-count validation).

### 7.3 Project Model

- Add `economic_situation` to `$fillable`.
- Add `@property string|null $economic_situation` to docblock if used.

### 7.4 ProjectController (getProjectDetails)

- Add `'economic_situation' => $project->economic_situation` to the JSON response for predecessor/prefill.

---

## 8. Regression Checklist

After implementation, verify:

- [ ] **Create flow:** New project with all Key Information fields saves correctly.
- [ ] **Create flow:** Word count validation blocks submit when any field has < 100 words.
- [ ] **Create flow:** "Save as Draft" skips word count validation.
- [ ] **Edit flow:** Existing project loads and displays all fields, including `economic_situation`.
- [ ] **Edit flow:** Updates to Key Information (including `economic_situation`) persist.
- [ ] **Edit flow:** Word count validation blocks submit when any field has < 100 words.
- [ ] **Edit flow:** "Save as Draft" skips word count validation.
- [ ] **View/Show:** All six fields render with correct labels; empty fields handled.
- [ ] **Export (Word):** Key Information section uses new labels and includes `economic_situation`.
- [ ] **Predecessor/prefill:** `getProjectDetails` returns `economic_situation` when applicable.
- [ ] **Existing projects:** Projects without `economic_situation` display correctly (no errors).
- [ ] **Individual project types:** IES, IIES, IAH, ILP create/edit still work with Key Information.

---

## 9. Risks & Edge Cases

### 9.1 Existing Data

- **Risk:** Projects created before the change have no `economic_situation` and may have short text in other fields.
- **Mitigation:** New column is nullable; existing rows are fine. Word count is frontend-only, so old data is not re-validated. Existing projects can be edited and will then be subject to validation on submit.

### 9.2 Draft Projects

- **Risk:** Drafts with &lt; 100 words in some fields would fail validation on final submit.
- **Mitigation:** Validation runs only on non-draft submit. Users can save drafts and complete content later.

### 9.3 Edit vs Create Behavior

- **Risk:** Different behavior between create and edit (e.g. edit allowing blanks).
- **Mitigation:** Use the same validation logic and selector set for both forms. Clarify whether empty fields are allowed or all six must have ≥ 100 words.

### 9.4 Goal Field

- **Risk:** `goal` was historically required in some flows; now it is nullable.
- **Mitigation:** Current implementation treats `goal` as nullable. Ensure 100-word rule applies consistently to `goal` as well, and that product requirements are clear on whether `goal` is mandatory.

### 9.5 Export Consistency

- **Risk:** Exported documents use old labels if ExportController is not updated.
- **Mitigation:** Update `addKeyInformationSection()` and any other export paths to use the new labels and include `economic_situation`.

### 9.6 Word Count Definition

- **Risk:** Different definitions of "word" (e.g. with/without punctuation) may confuse users.
- **Mitigation:** Use a simple whitespace-based split. Document the rule for users and consider showing live word count.

---

## 10. Summary of Implementation Order

1. Create migration for `economic_situation` and run it.
2. Update `Project` model (`$fillable`, docblock).
3. Update `KeyInformationController` (validation and assignment).
4. Update `StoreProjectRequest` and `UpdateProjectRequest`.
5. Update Blade partials (labels, new field, error placeholders).
6. Implement JavaScript validation and wire it to create/edit forms.
7. Update `ProjectController::getProjectDetails` for `economic_situation`.
8. Update `ExportController::addKeyInformationSection` (labels and `economic_situation`).
9. Run regression checks from Section 8.

---

*Document version: 1.0*  
*Created: 2026-01-31*
