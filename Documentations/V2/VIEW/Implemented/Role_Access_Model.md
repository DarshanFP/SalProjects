# Role Access Model — Project View, Download, and Activity

**Last updated:** 2026-02-23  
**Related:** `Implementation_Summary_Phases_1-6.md`

---

## 1. Provincial Scope Includes Owner AND In-Charge

Provincial users can access projects where **either** the project owner (`user_id`) **or** the project in-charge (`in_charge`) is within their scope. Scope is determined by:

- **Direct children:** Executors/applicants with `parent_id = provincial.id`
- **General users:** Also includes users from managed provinces (respects province filter in session)

This applies to: project list, project show, report lists, pending approvals, team overview, and download.

---

## 2. Download Access Follows View Access

For read-only roles (admin, coordinator, provincial, general), **if a user can view a project, they can download it**. There are no status-based restrictions on PDF/DOC download for these roles. Executor/applicant download access continues to follow `ProjectPermissionHelper::canView()`.

---

## 3. Route Usage by Role

| Role        | List Route                | Show Route                 | Download PDF/DOC                    | Activity History          |
|-------------|---------------------------|----------------------------|------------------------------------|---------------------------|
| Executor    | executor projects         | `projects.show`            | `projects.downloadPdf` (shared)    | `projects.activity-history` |
| Applicant   | applicant projects        | `projects.show`            | `projects.downloadPdf` (shared)    | `projects.activity-history` |
| Provincial  | `provincial.projects.list`| `provincial.projects.show` | `provincial.projects.downloadPdf` or shared | `projects.activity-history` |
| Coordinator | `coordinator.projects.list`| `coordinator.projects.show`| `coordinator.projects.downloadPdf` or shared | `projects.activity-history` |
| General     | coordinator-style routes  | coordinator-style          | shared                             | `projects.activity-history` |
| Admin       | admin routes              | `admin.projects.show`      | shared (admin now included)        | `projects.activity-history` |

**Important:** The project ID link in the provincial project list uses `provincial.projects.show` (not `projects.show`) so provincial users stay within provincial routes and avoid 403.

---

## 4. Centralized Access Logic

`ProjectAccessService` consolidates access rules:

- `getAccessibleUserIds(User $provincial)` — IDs of executors/applicants in scope
- `canViewProject(Project $project, User $user)` — single source of truth for view access
- `getVisibleProjectsQuery(User $user)` — query builder for visible projects

Used by: ProvincialController, ActivityHistoryHelper, and (for provincial) ExportController.

---

*Part of Project View Access implementation (Phases 1–7).*
