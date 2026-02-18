# True Soft Delete Refactor

**Date:** 2026-02-16  
**Scope:** Convert soft delete to a full restorable architecture: soft delete touches only the project row; child cleanup runs only on force delete.

---

## 1. Previous Behavior

- **Move to Trash (destroy):** Before this refactor, `ProjectController::destroy()` did the following in one flow:
  1. Authorized via `canDelete()`.
  2. For non-individual types: called `sustainabilityController->destroy()` and (in an earlier phase) had removed `logicalFrameworkController->destroy()`.
  3. Ran a large `switch` on `project_type` and called the corresponding section controllers' `destroy()` (EduRUT, CIC, CCI, IGE, LDP, RST, IES, ILP, IAH, IIES). Each of those deleted child rows and, for attachment controllers, deleted files from storage.
  4. Called `$project->delete()` (soft delete: set `deleted_at`).

- **Result:** Moving a project to trash **removed child data and attachment files**. Restore would bring back only the project row; sections and files were already gone, so the project was not fully restorable.

---

## 2. Structural Problem

- Soft delete was doing **hard-delete-style cleanup** (child rows + files) before setting `deleted_at`.
- Restore (`$project->restore()`) only cleared `deleted_at`; it could not bring back:
  - Sustainability, logical framework, type-specific section data.
  - Attachment records and files on disk.
- So "Move to Trash" was effectively a **destructive** operation for child data, and restore was incomplete.

---

## 3. New Architecture

- **Soft delete (Move to Trash):** Only updates the project row (sets `deleted_at`). No child deletes, no file deletes.
- **Force delete:** Runs only when something explicitly calls `$project->forceDelete()`. The `Project` model registers a `forceDeleting` listener that runs **child cleanup and file deletion** before the row is removed. Cleanup is centralized in `ProjectForceDeleteCleanupService`.
- **Restore:** Only clears `deleted_at`. All child records and files are still present, so the project is fully restorable.

---

## 4. Soft Delete Flow

1. User submits "Move to Trash" → `POST executor/projects/{project_id}/trash` → `ProjectController::destroy($project_id)`.
2. Controller loads project (`Project::where('project_id', $project_id)->firstOrFail()`), checks `ProjectPermissionHelper::canDelete()`, then calls `$project->delete()`.
3. Because the model uses `SoftDeletes`, `delete()` only sets `deleted_at` on the project row. No model event runs child cleanup.
4. Log: `"Project soft deleted (no child data removed)"`.
5. Redirect to project index with success message.

**No child data or files are deleted in this path.**

---

## 5. Force Delete Flow

1. Some code path calls `$project->forceDelete()` (e.g. a future admin "Permanently delete" action).
2. Laravel fires `forceDeleting` on the model **before** the row is deleted.
3. In `Project::boot()`, the listener runs:
   `app(ProjectForceDeleteCleanupService::class)->cleanup($project)`.
4. **ProjectForceDeleteCleanupService::cleanup($project):**
   - For non-individual types: `SustainabilityController::destroy()`, then logical framework (all objectives for the project) and type-specific section controllers.
   - For each project type: calls the same section controllers as the old destroy (EduRUT, CIC, CCI, IGE, LDP, RST, IES, ILP, IAH, IIES) so that child rows and type-specific attachment files are removed.
   - Calls `deleteAttachmentFiles()` to remove storage directories for the project (IES, IIES, IAH, ILP, and generic `project_attachments/{type}/{id}`).
5. After the listener returns, Laravel performs the actual `forceDelete()` (row removed; DB CASCADE may remove remaining child rows that reference `projects`).

Cleanup runs **only** on `forceDelete()`, not on soft `delete()`.

---

## 6. Restore Integrity

- After `$project->restore()`:
  - **Project row:** `deleted_at` is null again.
  - **Child records:** Unchanged (sustainability, objectives, type-specific tables, project_budgets, project_attachments, reports, etc.) because soft delete did not remove them.
  - **Attachment files:** Unchanged (no file deletion on soft delete).
  - **Financial/report data:** Unchanged; no child deletes during soft delete.

So restore is **complete**: the project and all its data and files are back in use.

---

## 7. Risk Assessment

| Risk | Mitigation |
|------|-------------|
| Force delete not used yet | No UI calls `forceDelete()` in this refactor. When adding "Permanently delete", call `$project->forceDelete()` so cleanup runs. |
| Cleanup service errors | Section controller `destroy()` calls are wrapped in try/catch; failures are logged and the rest of cleanup continues. |
| Double cleanup on force delete | Cleanup runs once in `forceDeleting`; then the row is removed. DB CASCADE may delete some children; section controllers already deleted others. No duplicate work in application code. |
| Authorization | Unchanged: `destroy()` (trash) and `restore()` still use `ProjectPermissionHelper::canDelete()`. Force delete, when added, must enforce its own authorization (e.g. admin-only). |

---

## Files Changed

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/ProjectController.php` | `destroy()` reduced to: load project, `canDelete()`, `$project->delete()`, log "Project soft deleted (no child data removed)", redirect. All child and file cleanup removed. |
| `app/Models/OldProjects/Project.php` | In `boot()`, added `static::forceDeleting(function (Project $project) { app(ProjectForceDeleteCleanupService::class)->cleanup($project); });` |
| `app/Services/ProjectForceDeleteCleanupService.php` | **New.** Encapsulates all child and file cleanup; called only from `Project::forceDeleting`. |

---

**End of document.**
