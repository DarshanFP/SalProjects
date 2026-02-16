# StoreProjectRequest Correction Report

**Date:** 2026-02-10  
**Goal:** Make `project_type` required always during create (including draft), since it is structurally required for project_id generation.

---

## 1. File Modified

- `app/Http/Requests/Projects/StoreProjectRequest.php`

---

## 2. Old Rule Snippet

```php
public function rules(): array
{
    $isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1';

    return [
        'project_type' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
        // ... rest unchanged
```

`project_type` was nullable when draft, required when not draft.

---

## 3. New Rule Snippet

```php
public function rules(): array
{
    return [
        'project_type' => 'required|string|max:255',
        // ... rest unchanged
```

- Draft-based conditional for `project_type` removed.
- Unused `$isDraft` variable removed (it was only used for this rule).
- All other rules in the array are unchanged.

---

## 4. Confirmation Only project_type Rule Changed

- **project_type:** Now always `'required|string|max:255'`. Only change in the rules array.
- **project_title, society_name, in_charge, overall_project_budget, etc.:** Still nullable; draft relaxation for other business fields is unchanged.
- **save_as_draft:** Still `'nullable|boolean'`.
- **authorize(), messages(), attributes(), prepareForValidation():** Not modified.

---

## 5. Confirmation No Other Draft Logic Altered

- Other draft-related behavior is unchanged: other fields remain nullable so partial draft data is still allowed; only `project_type` is now always required.
- UpdateProjectRequest, controllers, migrations, routes, and status logic were not modified.
- No other files were changed.

---

## 6. Risk Notes

- **Create draft with empty project_type:** Validation will now fail with “Project type is required.” User must select a project type before saving as draft. Aligns with domain rule that project_type is structural and required for project_id generation.
- **Create form (Batch 2):** HTML5 `required` was removed from project_type in the create general info partial. Backend now enforces project_type via StoreProjectRequest; browser may not block empty submit, but validation will return the error and redirect back.
- **Minimal diff:** Only the project_type rule and the removal of the unused `$isDraft` variable were changed in `rules()`.

---

**Next step:** Re-run Create Draft Save Test (final confirmation).
