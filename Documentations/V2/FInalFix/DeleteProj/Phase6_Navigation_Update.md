# Phase 6 — Navigation Link

## Summary

Added "Trash" navigation item to all role-appropriate sidebars.

## Visibility

Visible when user role is one of:
- executor
- applicant
- provincial
- coordinator
- general
- admin

## Link

- Route: `route('projects.trash.index')`
- URL: `/projects/trash`

## Updated Sidebars

| Role | File | Location |
|------|------|----------|
| executor | `executor/sidebar.blade.php` | Under Projects → Create projects collapse (with Pending, Approved) |
| applicant | Same as executor | Same |
| provincial | `partials/sidebar/provincial.blade.php` | Under Projects section |
| coordinator | `coordinator/sidebar.blade.php` | Project Application section |
| general | `general/sidebar.blade.php` | Projects section |
| admin | `admin/sidebar.blade.php` | Main section (after Projects) |
