# Batch 2 Removal Report

**Date:** 2026-02-10  
**Scope:** Remove business `required` from Create general info partial and remove legacy JS draft hack from Create page only.

---

## 1. Files Modified

| File | Change |
|------|--------|
| `resources/views/projects/partials/general_info.blade.php` | Removed `required` attribute from project_type, project_title, society_name (3 elements). |
| `resources/views/projects/Oldprojects/createProjects.blade.php` | Removed JS that stripped `required` on draft click and removed draft-only validation bypass in submit handler. |

**Note:** `general_info.blade.php` (without `Edit/`) is included only by Create (`createProjects.blade.php`). Edit uses `projects.partials.Edit.general_info`; that file was not modified.

---

## 2. Required Attributes Removed (Count + Lines)

**File:** `resources/views/projects/partials/general_info.blade.php`

| # | Field / Element | Line (approx.) | Change |
|---|------------------|----------------|--------|
| 1 | `project_type` (select) | 5 | Removed `required` from `<select name="project_type" id="project_type" class="form-control select-input" required>`. |
| 2 | `project_title` (input) | 44 | Removed `required` from `<input type="text" name="project_title" ... required>`. |
| 3 | `society_name` (select) | 48 | Removed `required` from `<select name="society_name" id="society_name" class="form-select" required>`. |

**Total:** 3 `required` attributes removed. No other attributes (name, id, class, value binding, conditionals, structure) were changed.

---

## 3. JS Logic Removed Summary

**File:** `resources/views/projects/Oldprojects/createProjects.blade.php`

**Removed:**

1. **Required-stripping on draft button click (previously ~lines 411–415):**
   - `const requiredFields = createForm.querySelectorAll('[required]');`
   - `requiredFields.forEach(field => { field.removeAttribute('required'); });`
   - Comment: "Remove required attributes temporarily to allow submission"

2. **Draft-only validation bypass in form submit handler (previously ~lines 494–499):**
   - `const isDraftSave = this.querySelector('input[name="save_as_draft"]');`
   - `if (isDraftSave && isDraftSave.value === '1') { return true; }`
   - Comment: "Check if this is a draft save (bypass validation)" / "Allow draft save without validation"

**Kept:**

- Hidden input `save_as_draft`: still added/set to `1` when "Save Project" (draft) is clicked.
- Loading state: button disabled and "Saving..." spinner on submit.
- Enabling disabled fields and showing hidden sections before submit (so values are included).
- Normal form submission (`createForm.submit()` for draft; form submit event for all).
- Single `checkValidity()` in submit handler for all submissions (no draft bypass).

---

## 4. Confirmation No Other Views Modified

- **Edit form:** `resources/views/projects/Oldprojects/edit.blade.php` and `resources/views/projects/partials/Edit/general_info.blade.php` were not modified.
- **actions.blade.php, key_information partials, comments partials:** Not modified.
- **Create page:** Only `createProjects.blade.php` and the Create general info partial (`partials/general_info.blade.php`) were modified.

---

## 5. Confirmation Backend Untouched

- **Controllers:** Not modified.
- **FormRequests (StoreProjectRequest, UpdateProjectRequest):** Not modified.
- **Migrations, routes, services:** Not modified.

Validation and draft handling remain the responsibility of the backend; Create form no longer uses HTML5 `required` or JS to strip/bypass it for draft.

---

## 6. Risk Notes

- **Create full submit (no draft):** With `required` removed from project_type, project_title, and society_name, the browser will not block submission when those are empty. Backend (StoreProjectRequest) must enforce rules for non-draft; existing store logic is unchanged.
- **Create draft submit:** Form submits with optional general info fields; backend already allows draft with relaxed rules. No new risk.
- **Shared partial:** `partials/general_info.blade.php` is used only by Create (Edit uses `partials/Edit/general_info.blade.php`). No impact on Edit.

---

**Next step:** Create Draft Test (Create Flow).
