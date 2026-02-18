# Phase 3 — Admin Force Delete (Permanent Delete)

## Summary

Admin-only route to permanently delete trashed projects. Removes project and all related data.

## Route

| Method | Path | Name | Middleware |
|--------|------|------|------------|
| DELETE | `/projects/{project_id}/force-delete` | `projects.forceDelete` | auth, role:admin |

## Controller Logic

**Method:** `ProjectController::forceDelete($project_id)`

1. `$project = Project::withTrashed()->where('project_id', $project_id)->firstOrFail()`
2. Log activity: `ActivityHistoryService::logProjectForceDelete($project, $user)` (before delete)
3. Call: `ProjectForceDeleteCleanupService::forceDelete($project)` (triggers model forceDeleting → cleanup)
4. Redirect to `projects.trash.index` with success

## Role Restriction

- Only `role:admin` middleware
- No province restriction (admin is global)

## Cleanup Flow

1. `ProjectForceDeleteCleanupService::forceDelete($project)` calls `$project->forceDelete()`
2. Laravel fires `Project::forceDeleting` event
3. `ProjectForceDeleteCleanupService::cleanup($project)` runs:
   - Type-specific child data deletion (logical framework, sustainability, type sections)
   - Attachment file deletion from storage
4. Project row removed from database

## Activity History

- Entry created with `action_type = 'force_delete'`, `new_status = 'permanently_deleted'`
- Logged BEFORE deletion so project_id is available

## Risk Notes

- **Irreversible:** No undo
- **Cascading:** All child data and files removed
- **Audit:** Activity history preserves who deleted and when

## Data Destruction Checklist

- [x] Project row
- [x] Logical framework / objectives
- [x] Sustainability records
- [x] Type-specific sections (CCI, IGE, LDP, RST, IES, ILP, IAH, IIES, etc.)
- [x] Attachment files (public disk)
- [x] Budgets, comments, status history (via foreign key or explicit cleanup)
