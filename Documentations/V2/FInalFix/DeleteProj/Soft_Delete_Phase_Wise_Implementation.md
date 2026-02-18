# Soft Delete Implementation Plan

**Date:** 2026-02-16  
**Scope:** Phase-wise rollout of soft delete for projects. No hard delete from UI; restore capability added.

---

## Phase 0 – Pre-check

### Confirmation (completed)

| Check | Result |
|-------|--------|
| Project model uses SoftDeletes | **No** (before change) → **Yes** (after Phase 2) |
| `projects` table has `deleted_at` column | **No** (before migration) → **Yes** (after Phase 1) |
| Queries use Eloquent (not raw SQL bypassing model) | **Yes** for main app. `ProjectQueryService` and controllers use `Project::query()` / `Project::where()`. Edge case: `SocietiesAuditCommand` uses `DB::table('projects')` for audit; it does not use the Project model and will include soft-deleted rows unless updated. |

**Proceed:** Yes. Main application flow uses Eloquent; default scoping will exclude soft-deleted once trait is added.

---

## Phase 1 – Migration

### Migration created and run

- **File:** `database/migrations/2026_02_16_120000_add_deleted_at_to_projects_table.php`
- **Name:** `add_deleted_at_to_projects_table`
- **Change:** `$table->softDeletes();` on `projects` table (adds nullable `deleted_at` timestamp).
- **Down:** `$table->dropSoftDeletes();`

### Confirmation

- Migration was run successfully (path: `database/migrations/2026_02_16_120000_add_deleted_at_to_projects_table.php`).
- `deleted_at` column exists on `projects`.
- Existing rows are unchanged (nullable column; no backfill).

---

## Phase 2 – Model Update

### File: `App\Models\OldProjects\Project`

- **Added:** `use Illuminate\Database\Eloquent\SoftDeletes;`
- **Added:** `use SoftDeletes;` in the class body (with `use HasFactory;`).

### Confirmation

- `$project->delete()` now performs a **soft delete**: row remains in DB with `deleted_at` set.
- Default `Project::query()` and all queries built from it exclude soft-deleted records.

---

## Phase 3 – Query Verification

### Audited

- **ProjectQueryService:** Uses `Project::query()` and `Project::query()` (in getProjectsForUserQuery and getProjectsForUsersQuery). No `withTrashed()` or `onlyTrashed()`.
- **listProjects()** (ProjectController): Uses `Project::query()`; no withTrashed/onlyTrashed.
- **getProjectsForUserQuery / getProjectsForUsersQuery:** No manual `whereNull('deleted_at')`; Eloquent SoftDeletes scope applies automatically.

### Result

- Default behaviour excludes soft-deleted records everywhere these queries are used.
- No code changes required in Phase 3.

---

## Phase 4 – Controller Adjustment

### File: `App\Http\Controllers\Projects\ProjectController`

**Changes:**

1. **Removed:** Call to `$this->logicalFrameworkController->destroy($project_id)` in the non-individual block. Replaced with a comment that logical framework is handled by FK cascade on permanent delete and that child section controllers still handle type-specific attachment cleanup.
2. **Kept:** All other child cleanup (sustainabilityController and type-specific controllers for attachments/sections). No removal of attachment controller cleanup.
3. **Updated:** Success message from "Project deleted successfully." to "Project moved to trash successfully." and log message to "Project moved to trash (soft deleted)".
4. **`$project->delete()`:** Unchanged; with SoftDeletes trait this now performs a soft delete.

### Confirmation

- Destroy flow still authorizes with `ProjectPermissionHelper::canDelete()`.
- Child data (sustainability, type-specific sections, attachment controller cleanup) still runs; only the logical framework controller call was removed.
- Final `$project->delete()` is a soft delete.

---

## Phase 5 – Route & UI

### Route

- **Route:** `POST executor/projects/{project_id}/trash` (prefix `executor/projects`).
- **Action:** `ProjectController@destroy`.
- **Name:** `projects.trash`.
- **Location:** `routes/web.php`, inside the `executor/projects` prefix group.

### UI

- **Label:** "Move to Trash" (replacing any previous "Delete" in the implemented views).
- **Visibility:** Button shown only when `ProjectPermissionHelper::canDelete($project, $user)` is true.
- **Places updated:**
  - **resources/views/projects/Oldprojects/index.blade.php:** In the Actions column, added a form POST to `projects.trash` with confirmation.
  - **resources/views/projects/Oldprojects/show.blade.php:** After Edit Project, added form POST to `projects.trash` with confirmation.

### Confirmation

- Trash action uses POST and CSRF.
- Confirmation: "Move this project to trash? You can restore it later."
- Only visible when `canDelete()` returns true.

---

## Phase 6 – Restore Capability

### Method: `ProjectController::restore($project_id)`

- Load: `Project::withTrashed()->where('project_id', $project_id)->firstOrFail()`.
- Authorize: `ProjectPermissionHelper::canDelete($project, Auth::user())`; 403 if not allowed.
- If not trashed: redirect to `projects.index` with info message.
- Else: `$project->restore()`, log, redirect to `projects.index` with success.

### Route

- **Route:** `POST executor/projects/{project_id}/restore`.
- **Name:** `projects.restore`.

### Note

- Restore UI (e.g. from a "Trash" list or a trashed project view) can be added later; route and method are in place.

---

## Phase 7 – Trash View

- **Status:** Optional future enhancement; not implemented in this rollout.
- **Intent:** Use `Project::onlyTrashed()` (and province/ownership filters) for a separate "Trash" index; link "Restore" to `projects.restore`.
- **Not exposed** in this phase.

---

## Phase 8 – Validation Checklist

Verify after deployment:

| Item | Expected |
|------|----------|
| Soft-deleted projects in normal index | Not visible (default query excludes them). |
| Dashboard counts | Do not include soft-deleted (queries use Project::query()). |
| Financial reports | Do not include soft-deleted (same query layer). |
| Exports | Do not include soft-deleted (same query layer). |
| Editable / submittable | Soft-deleted rows are not returned by normal queries, so they are not editable or submittable in the UI. |
| Province filtering | Unchanged; applied on same query builder. |
| Authorization | canDelete() used for trash and restore. |

---

## Phase 9 – Cleanup Plan (Future)

After confirming stability:

1. **Logical framework:** Already removed from destroy in Phase 4; no further change for that call.
2. **Manual file deletion:** Optional: for soft delete, consider skipping type-specific attachment file deletion (keep files for restore). Currently child controllers still run and may delete files; if desired, future change could skip file deletion when only soft-deleting.
3. **Force delete route:** If a future admin-only "Permanently delete" is added, use `$project->forceDelete()` and ensure it is only for the appropriate role and possibly only for trashed projects.

---

## Files Modified

| File | Change |
|------|--------|
| `database/migrations/2026_02_16_120000_add_deleted_at_to_projects_table.php` | **Created.** Add softDeletes() to projects. |
| `app/Models/OldProjects/Project.php` | Added SoftDeletes import and trait. |
| `app/Http/Controllers/Projects/ProjectController.php` | Removed logicalFrameworkController->destroy call; updated messages; added restore(). |
| `routes/web.php` | Added projects.trash and projects.restore routes. |
| `resources/views/projects/Oldprojects/index.blade.php` | Added "Move to Trash" form (when canDelete). |
| `resources/views/projects/Oldprojects/show.blade.php` | Added "Move to Trash" form (when canDelete). |

---

## Migration Name

`2026_02_16_120000_add_deleted_at_to_projects_table`

---

**End of implementation summary.**
