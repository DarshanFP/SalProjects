# Draft Preservation Patch Report

## 1. File Modified

- `app/Http/Controllers/Projects/ProjectController.php`

## 2. Code Snippet Added

Inside `update()` method, immediately after the existing `project_type` draft preservation block:

```php
// Draft preservation: avoid NOT NULL violations when user clears in_charge or overall_project_budget
if ($request->boolean('save_as_draft') && !$request->filled('in_charge') && $project->in_charge !== null && $project->in_charge !== '') {
    $request->merge(['in_charge' => $project->in_charge]);
}
if ($request->boolean('save_as_draft') && !$request->filled('overall_project_budget') && $project->overall_project_budget !== null) {
    $request->merge(['overall_project_budget' => $project->overall_project_budget]);
}
```

## 3. Fields Preserved

| Field | Condition | Effect |
|-------|-----------|--------|
| **in_charge** | `save_as_draft` is true, request does not have a filled `in_charge`, and project already has a non-empty `in_charge` | Request is merged with `$project->in_charge` so validation/update do not write NULL. |
| **overall_project_budget** | `save_as_draft` is true, request does not have a filled `overall_project_budget`, and project already has a non-null value | Request is merged with `$project->overall_project_budget` so validation/update do not write NULL. Numeric `0` is not treated as empty (`$request->filled()` and `!== null` preserve 0). |

## 4. Placement Location (Line Reference)

- **File:** `app/Http/Controllers/Projects/ProjectController.php`
- **Method:** `update()`
- **Lines:** Approximately **1395–1401**
- **Placement:** After `$project` is loaded (line ~1388), after the existing `project_type` preservation block (lines 1392–1394), and **before** any validation-dependent update logic and before `GeneralInfoController::update()` is called (line ~1407).

## 5. Confirmation No Other Logic Changed

- **Migrations:** Not modified.
- **Validation rules:** Not modified (`UpdateProjectRequest` unchanged).
- **Submit logic:** Not modified.
- **GeneralInfoController:** Not modified.
- **Services / Routes / Blade files:** Not modified.
- **Transaction boundaries:** Unchanged; preservation runs inside the existing `DB::beginTransaction()` / `try` block.
- **Status handling:** Unchanged; draft status is still applied after commit (existing block).
- **Redirect logic:** Unchanged.
- **`$validated`:** Not modified; only `$request` is merged so that subsequent validation and `GeneralInfoController::update()` receive the preserved values.

## 6. Risk Notes

- **Minimal surface area:** Only `ProjectController@update()` is changed; same pattern as existing `project_type` preservation.
- **Draft-only:** Preservation runs only when `save_as_draft` is true; submit and normal update paths are unchanged.
- **filled() usage:** `$request->filled()` ensures we only preserve when the field is truly missing or empty string; numeric `0` for `overall_project_budget` is not treated as empty and is not overwritten by this patch.
- **Edge case:** If the project’s `in_charge` or `overall_project_budget` is ever null in the DB (e.g. legacy data), we do not merge (conditions prevent it); behavior remains consistent with “preserve only when project already has a value.”

---

**Next step:** Rerun Draft Save Test before proceeding to Batch 2.
