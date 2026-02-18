# Phase 2 — Restore Logic

## Summary

Restore flow allows users with delete permission to restore soft-deleted projects.

## Route

| Method | Path | Name | Middleware |
|--------|------|------|------------|
| POST | `/projects/{project_id}/restore` | `projects.restore` | auth, role:executor,applicant,provincial,coordinator,general,admin |

## Controller Logic

**Method:** `ProjectController::restore($project_id)`

1. `$project = Project::withTrashed()->where('project_id', $project_id)->firstOrFail()`
2. Authorization: `ProjectPermissionHelper::canDelete($project, auth()->user())` — abort 403 if false
3. If not trashed: redirect with info message
4. `$project->restore()`
5. Redirect to `projects.trash.index` with success message

## Restore Flow

```
POST /projects/{id}/restore
    → Load project (withTrashed)
    → canDelete check (province + role + ownership)
    → If trashed: restore()
    → Redirect to trash index
```

## Authorization Matrix

`ProjectPermissionHelper::canDelete()` uses same rules as `canEdit()`:

| Role | Can Restore |
|------|-------------|
| executor | Own or in-charge projects (province-bound) |
| applicant | Own or in-charge projects (province-bound) |
| provincial | Any project in their province |
| coordinator | Any project (global) |
| general | Any project (global) |
| admin | Any project (global) |

## Province Check Verification

- `canDelete` internally calls `passesProvinceCheck(project, user)`
- If `user->province_id !== null`, project must have same `province_id`
- Global roles (coordinator, general, admin) have `province_id` null → no province restriction

## Security Review

- 403 returned when user lacks permission
- Province boundary enforced via ProjectPermissionHelper
- Ownership respected for executor/applicant
- No direct ID manipulation bypass (project loaded by project_id, then checked)
