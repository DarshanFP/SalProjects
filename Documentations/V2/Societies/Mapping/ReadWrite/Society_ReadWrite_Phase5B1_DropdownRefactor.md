# Phase 5B1 — Dropdown Refactor & Server Enforcement

**Date:** 2026-02-15  
**Scope:** Project create/edit society dropdown replaced with relational `society_id`; role-based visibility; dual-write; backend validation.  
**Constraints:** No read-switch. No schema changes. `society_name` retained and dual-written.

---

## 1. Controllers Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | Import `SocietyVisibilityHelper`. In `create()`: load `$societies = SocietyVisibilityHelper::getSocietiesForProjectForm($user)` and pass in `compact(..., 'societies')`. In `edit()`: same, add `'societies'` to compact. In `getProjectDetails()`: add `society_id` to JSON response for predecessor JS. In `update()` log: use `society_id` instead of `society_name`. |
| `app/Http/Controllers/Projects/GeneralInfoController.php` | Import `Society`. In `store()`: before `Project::create()`, if `society_id` present in validated, set `$validated['society_name'] = Society::find($validated['society_id'])->name` (dual-write). In `update()`: same — when `society_id` in validated, set `society_name` from `Society::find()`. |

---

## 2. Blade Views Updated

| File | Change |
|------|--------|
| `resources/views/projects/partials/general_info.blade.php` | Replaced `<select name="society_name">` with `<select name="society_id" id="society_id">`. Options from `@foreach($societies ?? [] as $society)` with `value="{{ $society->id }}"` and label `{{ $society->name }}`. Selected: `old('society_id', $user->society_id ?? '')`. Added `@error('society_id')`. Predecessor JS: set `society_id` from `data.society_id` instead of `society_name`. |
| `resources/views/projects/partials/Edit/general_info.blade.php` | Replaced all three society dropdown blocks (hardcoded 9 options) with single pattern: `<select name="society_id" id="society_id">` populated from `$societies ?? []`. Selected: `old('society_id', $project->society_id ?? '')`. Added `@error('society_id')`. Script: `getElementById('society_id')` with null check. |

**Hardcoded society name lists removed:** All 9-option literal lists removed from project create and edit partials. No form sends `society_name`; all send `society_id` only.

---

## 3. Validation Rules Added

**StoreProjectRequest** (project create):

```php
use Illuminate\Validation\Rule;
use App\Helpers\SocietyVisibilityHelper;

// In rules():
$allowedSocietyIds = SocietyVisibilityHelper::getAllowedSocietyIds();
return [
    // ...
    'society_id' => ['required', Rule::in($allowedSocietyIds)],
    // society_name removed from rules
];
```

**UpdateProjectRequest** (project update):

```php
$allowedSocietyIds = SocietyVisibilityHelper::getAllowedSocietyIds();
$societyRule = $isDraft
    ? ['nullable', Rule::in($allowedSocietyIds)]
    : ['required', Rule::in($allowedSocietyIds)];
return [
    // ...
    'society_id' => $societyRule,
    // society_name removed from rules
];
// In prepareForValidation(): when draft and society_id not in request, merge from project
if (!$this->exists('society_id') && $project->society_id !== null) {
    $this->merge(['society_id' => $project->society_id]);
}
```

**UpdateGeneralInfoRequest** (if used for general info update):

```php
'society_id' => ['required', Rule::in($allowedSocietyIds)],
```

---

## 4. Dual-Write Confirmed

- **On project create (GeneralInfoController@store):** After mapping other fields, if `$validated['society_id']` is set, `$validated['society_name']` is set from `Society::find($validated['society_id'])->name`. Both `society_id` and `society_name` are passed to `Project::create($validated)`.
- **On project update (GeneralInfoController@update):** Before `$project->update($validated)`, if `society_id` is present in `$validated`, `$validated['society_name']` is set from `Society::find($validated['society_id'])->name`. Both columns stay in sync.
- **No read-switch:** Display and exports still use `$project->society_name` (and/or `society_id` where added, e.g. getProjectDetails). No change to “read path” in reports or exports.

---

## 5. Role-Based Visibility Rules Implemented

**Helper:** `App\Helpers\SocietyVisibilityHelper`

- **queryForProjectForm(User $user):** Returns an Eloquent builder for societies the user may assign.
  - **admin, coordinator, general:** All active societies (no province filter).
  - **provincial:** `province_id = user->province_id` OR `province_id IS NULL` (global).
  - **executor, applicant:** Same as provincial — user’s `province_id` + global.
- **getSocietiesForProjectForm():** Runs the query and returns the collection (used in controllers).
- **getAllowedSocietyIds():** Returns IDs for validation `Rule::in(...)` so backend rejects cross-province or invalid IDs.

Project create is used by executor/applicant; project edit is used by executor, applicant, provincial, coordinator, general. All use the same helper so visibility and validation are consistent.

---

## 6. Security Enforcement

- **Validation:** `society_id` is required on store and on update (unless draft). It must be in `SocietyVisibilityHelper::getAllowedSocietyIds()`, which is built from the same role logic as the dropdown. A manipulated request sending another province’s society ID fails validation.
- **Draft update:** When `save_as_draft` is true, `society_id` is nullable; when absent it is merged from the existing project in `prepareForValidation`, so the stored value does not change. When present, it must still be in the allowed list.

---

## 7. Regression Tests Performed

| Check | Status |
|-------|--------|
| Provincial project create (society dropdown province + global) | Manual test recommended |
| Provincial project edit | Manual test recommended |
| General project create (all societies visible) | Manual test recommended |
| Executor/Applicant project create (province + global) | Manual test recommended |
| Province boundary attempt (submit society_id from other province) | Should fail validation |
| society_name auto-populates on save (dual-write) | Verify in DB after create/update |
| Predecessor load sets society_id in form | getProjectDetails returns society_id; JS sets field |
| Edit form shows correct selected society | $project->society_id used for selected option |

---

## 8. Risk Assessment

| Risk | Mitigation |
|------|------------|
| **society_name retained** | Intended. Dual-write keeps both columns in sync; no column dropped. |
| **No schema change** | Confirmed. Only application code and views changed. |
| **Safe rollback** | Revert controller, request, helper, and blade changes; forms would need to send `society_name` again if rolling back. |
| **Existing projects with society_id null** | Not in scope for 5B1; ensure backfill or migration has set `projects.society_id` before relying on NOT NULL where applicable. |
| **Executor/Provincial user forms** | Society dropdowns for executor/provincial user create/edit (General/Provincial) were **not** changed in this phase. They still use `society_name` and hardcoded or province-scoped lists; can be refactored in a follow-up. |
| **Reports/Exports** | Not modified. They continue to use `society_name` (no read-switch). |

---

## 9. Files Touched (Summary)

- **New:** `app/Helpers/SocietyVisibilityHelper.php`
- **Modified:** `app/Models/OldProjects/Project.php` (fillable + `society()` relationship), `app/Http/Controllers/Projects/ProjectController.php`, `app/Http/Controllers/Projects/GeneralInfoController.php`, `app/Http/Requests/Projects/StoreProjectRequest.php`, `app/Http/Requests/Projects/UpdateProjectRequest.php`, `app/Http/Requests/Projects/UpdateGeneralInfoRequest.php`, `resources/views/projects/partials/general_info.blade.php`, `resources/views/projects/partials/Edit/general_info.blade.php`

**Confirmation:** No read-switch performed. No schema changes. No reports or exports modified.
