# Wave 5C — Provincial Project Society Update — Implementation Summary

**Date:** 2026-02-17  
**Scope:** Production-safe controlled field mutation from `/provincial/projects-list`.  
**Constraint:** Only when project is **editable** (`ProjectPermissionHelper::canEdit()`). No status bypass. No approval mutation.

---

## 1. Overview

| Item | Result |
|------|--------|
| **Route** | `PATCH /provincial/projects/{project_id}/society` → `provincial.projects.updateSociety` |
| **Middleware** | `auth`, `role:provincial,general` (dedicated group; existing provincial group unchanged) |
| **Authorization** | `ProjectPermissionHelper::canEdit($project, $user)` only — no separate status logic |
| **Fields updated** | `society_id`, `society_name`, `province_id` (from selected society) |
| **UI** | Society column + “Update Society” button (when canEdit) or “Locked” in Actions |

---

## 2. Files Created

| File | Purpose |
|------|--------|
| `app/Http/Requests/Provincial/UpdateProjectSocietyRequest.php` | Form request: `society_id` required, exists:societies,id; authorize via `canEdit()` |

---

## 3. Files Modified

### 3.1 Routes — `routes/web.php`

- **New route group** (before provincial group):
  - `Route::middleware(['auth', 'role:provincial,general'])->group(...)`
  - `Route::patch('/provincial/projects/{project_id}/society', [ProvincialController::class, 'updateProjectSociety'])->name('provincial.projects.updateSociety');`
- No existing routes changed.

### 3.2 Controller — `app/Http/Controllers/ProvincialController.php`

- **Imports added:** `SocietyVisibilityHelper`, `UpdateProjectSocietyRequest`, `DB`.
- **Method:** `updateProjectSociety(UpdateProjectSocietyRequest $request, string $project_id)`
  - Validates `society_id` in `SocietyVisibilityHelper::getAllowedSocietyIds($user)` → 403 if not allowed.
  - Loads `Society`, updates project in `DB::transaction()`: `society_id`, `society_name`, `province_id`; save.
  - Calls `ActivityHistoryService::logProjectSocietyChanged($project, $oldSocietyId, $newSocietyId)`.
  - Redirects to `provincial.projects.list` with success flash.
- **projectList():**
  - `$societies = SocietyVisibilityHelper::queryForProjectForm($provincial)->get();`
  - `societies` added to view `compact(...)` (single query, no N+1).

### 3.3 Activity — `app/Services/ActivityHistoryService.php`

- **Method:** `logProjectSocietyChanged(Project $project, ?int $oldSocietyId, int $newSocietyId)`
  - Creates `ActivityHistory` with `action_type` = `project_society_changed`.
  - `notes` stores JSON: `project_id`, `old_society_id`, `new_society_id`, `changed_by`.

### 3.4 View — `resources/views/provincial/ProjectList.blade.php`

- **@php:** `use App\Helpers\ProjectPermissionHelper;`
- **Table:** New column **Society** (header + cell `{{ $project->society_name ?? '—' }}`).
- **Actions column:**
  - If `ProjectPermissionHelper::canEdit($project, auth()->user())`: button “Update Society” opening modal with `data-update-url`, `data-project-id`, `data-project-title`.
  - Else: badge “Locked” with lock icon.
- **Modal:** `#updateSocietyModal` — form PATCH, CSRF, `@method('PATCH')`, dropdown `society_id` from `$societies`; script sets form `action` from button `data-update-url` on modal show.
- **Empty row:** `colspan` updated from 14 to 15.

---

## 4. Request Flow

1. User clicks “Update Society” on a row (only when `canEdit` is true).
2. Modal opens; script sets form action to `route('provincial.projects.updateSociety', $project->project_id)`.
3. User selects society and submits.
4. `UpdateProjectSocietyRequest` runs: `authorize()` loads project by `route('project_id')`, returns `ProjectPermissionHelper::canEdit($project, auth()->user())`.
5. Controller: checks `society_id` in `getAllowedSocietyIds($user)`; loads Society; transaction update + activity log; redirect with success.

---

## 5. Authorization Summary

| Check | Where |
|-------|--------|
| Route access | Middleware `auth`, `role:provincial,general` |
| Project editable | `UpdateProjectSocietyRequest::authorize()` → `canEdit($project, $user)` |
| Society in scope | `SocietyVisibilityHelper::getAllowedSocietyIds($user)` in controller |

No status bypass: if project is not editable (e.g. approved), `canEdit` is false → 403.

---

## 6. Test Conditions (Manual)

| # | Condition | Expected |
|---|-----------|----------|
| 1 | Draft project | “Update Society” visible; update succeeds. |
| 2 | Reverted project | “Update Society” visible; update succeeds. |
| 3 | Approved project | Button hidden; direct PATCH → 403. |
| 4 | Society from another province | 403 (not in `getAllowedSocietyIds`). |
| 5 | Aggregation | Approved-project logic and financial aggregation unchanged. |
| 6 | After update | `project.province_id` equals `society.province_id`. |

---

## 7. Relation to Feasibility Review

- **Feasibility doc:** `Provincial_Project_List_Update_Society_Feasibility_Review.md` suggested a **status-independent** “update society” for approved/rejected etc.
- **Wave 5C implementation:** Keeps **editable-only** (no change once approved), per project constraints: “No status bypass. No approval mutation.” So only draft/reverted and other editable statuses can have society updated from this flow.

---

## 8. Possible Future Enhancements

- If product requires “update society regardless of status” for provincial, a separate **status-independent** endpoint and policy could be added (e.g. “provincial can always change society for projects in their province”), with explicit audit and approval-impact considerations.
- Current implementation is intentionally minimal and aligned with `ProjectPermissionHelper::canEdit()` (Wave 5C constraints).
