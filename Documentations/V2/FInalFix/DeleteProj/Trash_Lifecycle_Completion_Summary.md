# Trash Lifecycle Completion Summary

## Full Lifecycle Map

```
Create Project → Edit → Submit → Approve
       ↓
   [Soft Delete] → Trash
       ↓
   [Restore] → Back to active
       ↓
   [Force Delete] (Admin only) → Permanent removal
```

## Routes

| Action | Method | Route | Name |
|--------|--------|-------|------|
| List trash | GET | /projects/trash | projects.trash.index |
| Move to trash | POST | /executor/projects/{id}/trash | projects.trash |
| Restore | POST | /projects/{id}/restore | projects.restore |
| Force delete | DELETE | /projects/{id}/force-delete | projects.forceDelete |

## Authorization Matrix

| Role | View Trash | Restore | Force Delete |
|------|------------|---------|--------------|
| executor | Own only | Own | — |
| applicant | Own only | Own | — |
| provincial | Province | Province | — |
| coordinator | All | All | — |
| general | All | All | — |
| admin | All | All | Yes |

## Role Matrix (Trash Visibility)

| Role | Trashed Projects Visible |
|------|--------------------------|
| executor | user_id = self OR in_charge = self (province-bound) |
| applicant | Same as executor |
| provincial | province_id = user.province_id |
| coordinator | All (no province filter) |
| general | All |
| admin | All |

## Province Boundary Confirmation

- Provincial users: `where('province_id', $user->province_id)` on trash query
- Executor/Applicant: province filter + ownership filter
- Coordinator/General/Admin: no province filter (global)

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| Trashed in dashboard | SoftDeletes auto-excludes; SocietiesAuditCommand fixed |
| Cross-province restore | ProjectPermissionHelper::canDelete enforces province |
| Unauthorized force delete | role:admin middleware |
| Data loss on force delete | Activity history logged; confirmation dialog |

## Production Readiness Status

- [x] Phase 1: Trash listing
- [x] Phase 2: Restore logic
- [x] Phase 3: Admin force delete
- [x] Phase 4: Badge rendering
- [x] Phase 5: Dashboard exclusion
- [x] Phase 6: Navigation
- [x] Documentation complete

**Pre-deployment:** Ensure migration `add_deleted_at_to_projects_table` has been run.

## Files Modified

- `app/Services/ProjectQueryService.php` — getTrashedProjectsQuery
- `app/Http/Controllers/Projects/ProjectController.php` — trashIndex, restore, forceDelete
- `app/Services/ProjectForceDeleteCleanupService.php` — forceDelete method
- `app/Services/ActivityHistoryService.php` — logProjectForceDelete
- `app/Console/Commands/SocietiesAuditCommand.php` — exclude trashed in raw SQL
- `routes/web.php` — trash, restore, force-delete routes
- `resources/views/projects/trash/index.blade.php` — new
- `resources/views/projects/partials/status-badge.blade.php` — new
- `resources/views/projects/Oldprojects/index.blade.php` — use status-badge
- `resources/views/projects/Oldprojects/approved.blade.php` — use status-badge
- `resources/views/executor/sidebar.blade.php` — Trash link
- `resources/views/coordinator/sidebar.blade.php` — Trash link
- `resources/views/partials/sidebar/provincial.blade.php` — Trash link
- `resources/views/general/sidebar.blade.php` — Trash link
- `resources/views/admin/sidebar.blade.php` — Trash link
