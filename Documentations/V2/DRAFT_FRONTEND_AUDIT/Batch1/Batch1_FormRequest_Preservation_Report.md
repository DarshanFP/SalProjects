# Draft Preservation via FormRequest Report

**Date:** 2026-02-10  
**Scope:** Move draft preservation from ProjectController into UpdateProjectRequest::prepareForValidation() so preserved values are included in validated().

---

## 1. Controller Cleanup Summary

**File:** `app/Http/Controllers/Projects/ProjectController.php`  
**Method:** `update()`

**Removed:** The following three merge blocks were removed entirely.

- **project_type:**  
  `if ($request->boolean('save_as_draft') && !$request->filled('project_type') && $project->project_type) { $request->merge(['project_type' => $project->project_type]); }`

- **in_charge:**  
  `if ($request->boolean('save_as_draft') && !$request->filled('in_charge') && $project->in_charge !== null && $project->in_charge !== '') { $request->merge(['in_charge' => $project->in_charge]); }`

- **overall_project_budget:**  
  `if ($request->boolean('save_as_draft') && !$request->filled('overall_project_budget') && $project->overall_project_budget !== null) { $request->merge(['overall_project_budget' => $project->overall_project_budget]); }`

**Unchanged:** Status update for draft, transaction boundaries, redirects, GeneralInfoController call, and all other logic in `update()` remain as before.

---

## 2. New prepareForValidation() Code

**File:** `app/Http/Requests/Projects/UpdateProjectRequest.php`  
**Placement:** After `authorize()`, before `rules()`.

```php
/**
 * Prepare the data for validation.
 * When saving as draft, preserve existing NOT NULL fields so validated() contains them and DB does not receive NULL.
 */
protected function prepareForValidation(): void
{
    if (!$this->boolean('save_as_draft')) {
        return;
    }

    $projectId = $this->route('project_id');
    $project = $projectId ? Project::where('project_id', $projectId)->first() : null;

    if (!$project) {
        return;
    }

    if (!$this->filled('project_type') && $project->project_type !== null) {
        $this->merge(['project_type' => $project->project_type]);
    }

    if (!$this->filled('in_charge') && $project->in_charge !== null) {
        $this->merge(['in_charge' => $project->in_charge]);
    }

    if (!$this->filled('overall_project_budget') && $project->overall_project_budget !== null) {
        $this->merge(['overall_project_budget' => $project->overall_project_budget]);
    }
}
```

**Note:** The route for `projects.update` uses the parameter `project_id` (not a bound `project` model). The project is resolved from `$this->route('project_id')` with a single query. If the app later adds implicit route model binding for `project`, this could be switched to `$this->route('project')` to avoid the query.

---

## 3. Fields Preserved

| Field | Condition | Effect |
|-------|-----------|--------|
| **project_type** | `save_as_draft` is true, request does not have a filled `project_type`, and `$project->project_type` is not null | Request is merged with existing project_type before validation. |
| **in_charge** | `save_as_draft` is true, request does not have a filled `in_charge`, and `$project->in_charge` is not null | Request is merged with existing in_charge before validation. |
| **overall_project_budget** | `save_as_draft` is true, request does not have a filled `overall_project_budget`, and `$project->overall_project_budget` is not null | Request is merged with existing overall_project_budget before validation. Numeric 0 is preserved (`!== null`). |

All use `$this->filled()` so only truly missing or empty input is replaced; valid 0 for budget is not overwritten.

---

## 4. Laravel Lifecycle Explanation

1. **Request hits route** `PUT .../update` → `UpdateProjectRequest` is resolved.
2. **prepareForValidation()** runs (Laravel calls it from `ValidatesWhenResolvedTrait` before validation).
3. When `save_as_draft` is true, we merge preserved values into the request **input**.
4. **Validation** runs; the validator uses the **current** request input (including merged values).
5. **validated()** returns the validated attributes, which now **include** the preserved values when they were merged in step 3.
6. **authorize()** runs (order may vary with middleware; project is resolved again for permission check).
7. **Controller** runs; it calls `GeneralInfoController::update($request, ...)`.
8. **GeneralInfoController** uses `$validated = $request->validated()`; that array now contains the preserved project_type, in_charge, and overall_project_budget when they were merged in prepareForValidation.
9. **DB write** `$project->update($validated)` receives non-null values for NOT NULL columns → no NOT NULL violation.

---

## 5. Confirmation validated() Now Contains Preserved Values

- **Before (controller merge):** Merge happened in `ProjectController::update()` **after** the FormRequest had already validated. `$request->validated()` was the validator’s snapshot from **before** the merge, so preserved values never appeared in validated() and could still be written as NULL.
- **After (FormRequest prepareForValidation):** Merge happens **before** validation. The validator runs on the request input that already includes the merged values. So the validator’s result (and thus `$request->validated()`) includes the preserved project_type, in_charge, and overall_project_budget when the user had cleared them and save_as_draft was true. GeneralInfoController’s `$project->update($validated)` therefore writes the preserved values and does not write NULL for those NOT NULL columns.

---

## 6. Risk Notes

- **Single query in prepareForValidation:** The project is loaded with `Project::where('project_id', $projectId)->first()`. `authorize()` also loads the project. So there are two project loads per update request when save_as_draft is true. Acceptable for minimal change; could be optimized later (e.g. route model binding or sharing the same instance).
- **Route parameter:** Uses `$this->route('project_id')` because the route is `{project_id}/update`. No change to routes was made.
- **No other files modified:** GeneralInfoController, submit logic, migrations, routes, Blade, services, and transaction boundaries were not changed. Preservation exists only in `UpdateProjectRequest::prepareForValidation()`.
- **Codebase check:** Grep for draft preservation merge logic confirmed no duplicate preservation; only `UpdateProjectRequest` contains the in_charge / overall_project_budget / project_type preservation merges.

---

**Next step:** Rerun Draft Save Test to confirm behavior.
