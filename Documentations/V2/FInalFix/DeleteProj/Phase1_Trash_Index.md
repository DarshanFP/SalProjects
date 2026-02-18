# Phase 1 — Trash Listing Route + Controller

## Summary

Trash listing page allows users to view soft-deleted projects based on their role and province.

## Route

| Method | Path | Name | Middleware |
|--------|------|------|------------|
| GET | `/projects/trash` | `projects.trash.index` | auth, role:executor,applicant,provincial,coordinator,general,admin |

## Controller Logic

**Method:** `ProjectController::trashIndex()`

1. `$user = auth()->user()`
2. Query: `ProjectQueryService::getTrashedProjectsQuery($user)`
3. Eager load: `user`
4. Order: `deleted_at` DESC
5. Paginate: 15 per page
6. Return view: `projects.trash.index`

## Query Scope Explanation

`ProjectQueryService::getTrashedProjectsQuery(User $user)`:

1. **Base:** `Project::onlyTrashed()` — only soft-deleted rows
2. **Province filter:** If `$user->province_id !== null`, add `where('province_id', $user->province_id)`
3. **Ownership filter (executor/applicant):** If role in `['executor','applicant']`, add `where(user_id = $user->id OR in_charge = $user->id)`

## Role Visibility Matrix

| Role | Province Filter | Ownership Filter | Visible Trashed Projects |
|------|-----------------|------------------|--------------------------|
| executor | Yes (if province_id set) | Yes (own or in-charge) | Own trashed only |
| applicant | Yes (if province_id set) | Yes (own or in-charge) | Own trashed only |
| provincial | Yes | No | All province trashed |
| coordinator | No (province_id null) | No | All trashed |
| general | No (province_id null) | No | All trashed |
| admin | No (province_id null) | No | All trashed |

## Province Enforcement Confirmation

- Provincial users have `province_id` set → restricted to their province
- Coordinator, General, Admin have `province_id` null → no province filter (global)
- Executor/Applicant see only own projects, and if province-bound, within province

## Blade View

**File:** `resources/views/projects/trash/index.blade.php`

- Extends role-appropriate layout (executor.dashboard, coordinator.dashboard, etc.)
- Displays: Project ID, Title, Project Type, Status badge (Trashed), Deleted at
- Actions: Restore button; Permanent Delete (admin only)

## Files Modified

- `app/Services/ProjectQueryService.php` — added `getTrashedProjectsQuery()`
- `app/Http/Controllers/Projects/ProjectController.php` — added `trashIndex()`
- `routes/web.php` — added trash route
- `resources/views/projects/trash/index.blade.php` — new file
