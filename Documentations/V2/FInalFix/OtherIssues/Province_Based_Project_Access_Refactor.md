# Province-Based Project Access Refactor

**Date:** 2026-02-16  
**Status:** Implemented

---

## 1. Previous Behaviour

- **Society validation:** `UpdateProjectRequest` validated `society_id` with `Rule::in($allowedSocietyIds)`. `SocietyVisibilityHelper::getAllowedSocietyIds()` returned societies in the user's province **or** global (null province). Projects whose `society_id` was outside that list failed validation and could not be updated (e.g. IIES-0059, IOES-0043).
- **Authorization:** `ProjectPermissionHelper::canEdit` checked only status (editable) and owner/in-charge; no province check. Admin/coordinator/provincial could view all projects regardless of province.
- **Project lists:** Executor index used `ProjectQueryService::getProjectsForUserQuery()` (owner or in-charge) without province filter. Provincial list used `whereHas('user', parent_id)`; coordinator list had no province filter.
- **Society dropdown:** Societies were filtered by role (provincial/executor: province + global; admin/coordinator/general: all).

---

## 2. New Authorization Model

| Rule | Implementation |
|------|----------------|
| **Province isolation** | `project.province_id` must equal `user.province_id`. If `user.province_id` is null (e.g. admin/general), province check is skipped (backward compatibility). |
| **Executor / Applicant** | Can edit/update/delete only if `project.user_id === user.id` OR `project.in_charge === user.id`, and project is in editable status, and province matches. |
| **Provincial / Coordinator** | Can edit/update/delete **all projects in their province** (same province_id). Can view all projects in their province. |
| **Admin / General** | Can edit/view all (province check skipped when `user.province_id` is null). |

---

## 3. Security Guarantees

- **No cross-province access:** Any user with a non-null `province_id` can only access projects where `project.province_id === user.province_id`.
- **Ownership preserved:** Executors and applicants cannot edit other users' projects; they must be owner or in-charge.
- **Role hierarchy:** Provincial and coordinator can manage all projects within their province; they do not need to be owner or in-charge.
- **Society validation:** Update request no longer blocks on society list; validation is `required|exists:societies,id`. Authorization (who can edit which project) is handled in `authorize()`, not via society_id allow-list.

---

## 4. Role Matrix

| Role       | View projects              | Edit/Update/Delete projects     | Province rule        |
|-----------|----------------------------|----------------------------------|----------------------|
| Executor  | Own + in-charge, in province | Own + in-charge, in province, editable status | Must match           |
| Applicant | Own + in-charge, in province | Own + in-charge, in province, editable status | Must match           |
| Provincial| All in province            | All in province                 | Must match           |
| Coordinator | All in province          | All in province                 | Must match           |
| Admin     | All                        | All                             | No province filter*  |
| General   | All                        | All                             | No province filter*  |

\* When `user.province_id` is null, province check is skipped.

---

## 5. Province Isolation Enforcement

| Location | Change |
|----------|--------|
| **ProjectPermissionHelper** | `passesProvinceCheck($project, $user)`: returns false if both have province_id and they differ. Used in `canEdit`, `canView`, `canSubmit`, `canDelete`, `canUpdate`. |
| **ProjectQueryService::getProjectsForUserQuery** | Adds `where('province_id', $user->province_id)` when `$user->province_id !== null`. |
| **ProjectPermissionHelper::getEditableProjects** | Adds `where('province_id', $user->province_id)` when user has province_id; then applies owner/in-charge and status for executor/applicant. |
| **ProjectController::listProjects** | Adds `where('province_id', $user->province_id)` when user has province_id (before role-specific filters). |
| **SocietyVisibilityHelper** | `queryForProjectForm` returns only societies where `province_id = user->province_id` when user has province_id; else all active societies. |

---

## 6. Impact on IIES & IOES

- **IIES-0059, IOES-0043:** Update was previously failing when `society_id` was not in `getAllowedSocietyIds()` (e.g. society in another province or legacy data). Validation now only requires `society_id` to exist in `societies`. Whether the user can edit the project is determined by `ProjectPermissionHelper::canEdit()` (province + status + role/ownership). As long as the project is in the user's province and the user is owner/in-charge (or provincial/coordinator), update is allowed regardless of which society is selected.
- **Attachments:** No change to attachment view/download logic; access remains via existing routes and file-id. Project access (show/edit) is still gated by `canView` / `canEdit`, which now enforce province.

---

## 7. Regression Checklist

- [ ] IIES-0059 update works (user in same province, owner or in-charge).
- [ ] IOES-0043 update works (user in same province, owner or in-charge).
- [ ] Attachments view/download still work for allowed users.
- [ ] Cross-province access blocked: user A (province 1) cannot view/edit project in province 2.
- [ ] Executor cannot edit another executor's project (same province, not owner/in-charge).
- [ ] Provincial can edit all projects in their province.
- [ ] Coordinator can edit all projects in their province.
- [ ] Society dropdown shows only societies in user's province (when user has province_id).
- [ ] Admin/General (no province_id) can still access all projects and societies.
