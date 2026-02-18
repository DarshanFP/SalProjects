# Province ID — Project Creation Fix (Phase 1)

**Date:** 2026-02-16  
**Scope:** Project creation flow only. Update flow and authorization unchanged.  
**Objective:** Ensure every new project has `province_id` set server-side; never from client.

---

## 1. Root Cause

- **Project** model did not include `province_id` in `$fillable`, so `Project::create($validated)` could not persist it via mass assignment.
- **GeneralInfoController::store()** never set `province_id` before calling `Project::create()`.
- Validation and form do not (and must not) expose `province_id` to the client.

Result: New projects were created without `province_id`, risking constraint failure (when DB enforces NOT NULL) or rows with null province.

---

## 2. Files Modified

| File | Change |
|------|--------|
| **app/Models/OldProjects/Project.php** | Added `'province_id'` to `$fillable` with comment: server-set only, never from client. |
| **app/Http/Controllers/Projects/GeneralInfoController.php** | Before `Project::create()`, set `$validated['province_id']`: IF `society_id` exists then `Society::find($validated['society_id'])->province_id`, ELSE `auth()->user()->province_id`. (Null-safe: `?->` and fallback to user when society not found.) |

No other files were modified. Update flow, request validation rules, and views were not changed.

---

## 3. Before vs After Behavior

### Before

- `$validated` did not contain `province_id`.
- `Project::$fillable` did not include `province_id`, so it would not be mass-assigned even if present.
- New projects could be stored with `province_id` = null (or DB default), leading to integrity/constraint issues.

### After

- **When `society_id` exists:**  
  `$provinceId = Society::find($validated['society_id'])?->province_id ?? auth()->user()->province_id`
- **Else:**  
  `$provinceId = auth()->user()->province_id`
- **Then:** `$validated['province_id'] = $provinceId`; then `Project::create($validated)`.
- `province_id` is always set in `$validated` before create, and is in `$fillable`, so it is persisted. It is never read from request or validation; server-set only.

---

## 4. Safety Validation (Verified)

- **Not from request:** `province_id` is only assigned from `Society::find(...)->province_id` or `Auth::user()->province_id`. Never from `$request->input()` or `$request->validated()` (no rule for `province_id` in store requests).
- **Not in validation rules:** `province_id` does not appear in `StoreProjectRequest`, `StoreGeneralInfoRequest`, or `UpdateProjectRequest` rules.
- **Not in update flow:** Update logic was not modified; `province_id` remains non-editable.
- **Not in project form:** Project create/edit forms do not include a `province_id` field.

---

## 5. Regression Checklist

- [ ] **New project creation (with society):** Create a project with a society selected; confirm `projects.province_id` equals that society’s `province_id`.
- [ ] **New project creation (no society / draft):** Create without a society (if allowed); confirm `province_id` equals the current user’s `province_id`.
- [ ] **province_id stored:** After create, assert `$project->province_id` is non-null and matches expected province.
- [ ] **Update does not change province_id:** Edit an existing project (general info) and save; confirm `projects.province_id` is unchanged.
- [ ] **Client cannot set province_id:** No project create/edit form or API sends `province_id`; value is always derived from society or user.

---

**End of document.**  
Phase 1 limits changes to project creation; no update flow or authorization logic was modified.
