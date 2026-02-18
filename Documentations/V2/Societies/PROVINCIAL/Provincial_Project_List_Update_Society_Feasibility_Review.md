# Provincial Projects List — Update Society (Regardless of Status) — Feasibility Review

**Date:** 2026-02-17  
**Scope:** Audit only. No code or schema changes.  
**URL:** `/provincial/projects-list`  
**Requirement:** Provincial users shall be able to update the **society** of their projects **irrespective of project status** (including approved, rejected, forwarded, etc.) from the provincial projects list surface.

---

## 1. Executive Summary

| Item | Finding |
|------|--------|
| **Feasibility** | **Feasible** with a dedicated “update society only” capability and UI. |
| **Current gap** | Project edit/update is gated by **editable status**; approved/rejected projects cannot be edited today, so society cannot be changed for those. |
| **Recommended approach** | Add a **status-independent** “Update society” action scoped to provincial (and general acting as provincial), at `/provincial/projects-list`, with a dedicated route and controller method that only updates `society_id` / `society_name` (and optionally `province_id` for consistency). |
| **Risks / considerations** | Authorization must stay province- and access-scoped; activity logging recommended; optional sync of `project.province_id` when society changes. |

---

## 2. Current State

### 2.1 URL and Controller

- **Route:** `GET /provincial/projects-list` → `ProvincialController@projectList` (name: `provincial.projects.list`).
- **Middleware:** `auth`, `role:provincial,general`.
- **View:** `resources/views/provincial/ProjectList.blade.php`.

### 2.2 What the List Shows

- **Data source:** Projects for **accessible users** via `getAccessibleUserIds($provincial)` (direct children for provincial; for general: direct children + users in managed provinces, with optional session province filter).
- **Columns:** S.No, Project ID, Team Member, Role, Center, Project Title, Project Type, Overall Project Budget, Existing Funds, Local Contribution, Requested/Sanctioned, Health, Status, **Actions** (View; Forward/Revert only when status is `submitted_to_provincial` or `reverted_by_coordinator`).
- **Society:** Society is **not** displayed in the table and there is **no** “Update society” action on this page today.

### 2.3 How Project Edit/Update Works Today

- **Edit:** `GET /projects/{project_id}/edit` → `ProjectController@edit`.  
  - Uses `ProjectPermissionHelper::canEdit($project, $user)`.  
  - **canEdit** requires:
    1. **Province check:** `project.province_id === user.province_id` (or user has no `province_id`, e.g. admin/general in some flows).
    2. **Status check:** `ProjectStatus::isEditable($project->status)`.
    3. **Role:** Provincial/coordinator can edit **only when** status is editable; executor/applicant only for own/in-charge.
- **Update:** `PUT /projects/{project_id}/update` → `UpdateProjectRequest` (authorize via `canEdit`) → `ProjectController@update` → `GeneralInfoController@update` (which dual-writes `society_id` and `society_name`).
- **Editable statuses** (only these allow edit/update today):  
  `draft`, `reverted_by_provincial`, `reverted_by_coordinator`, `reverted_by_general_as_provincial`, `reverted_by_general_as_coordinator`, `reverted_to_executor`, `reverted_to_applicant`, `reverted_to_provincial`, `reverted_to_coordinator`.  
  **Not editable:** e.g. `submitted_to_provincial`, `forwarded_to_coordinator`, `approved_by_coordinator`, `rejected_by_coordinator`, etc.

**Conclusion:** For approved, rejected, or forwarded projects, provincial users **cannot** change society via the existing edit/update flow. A **new**, status-independent “update society” path is required.

---

## 3. Data and Backend Readiness

### 3.1 Project Model

- **Table:** `projects` (via `App\Models\OldProjects\Project`).
- **Society fields:** `society_id`, `society_name` (both present; dual-write pattern in use).
- **Province:** `province_id` (server-set on create; not taken from client on general update). When society is set at create, `province_id` is derived from society or user.

### 3.2 Society Update Logic (Existing)

- **GeneralInfoController@update:** If `society_id` is in validated data, it loads the society and sets `society_name`; it does **not** currently set `project.province_id` from the new society. For a provincial-only society change within the same province, `province_id` remains correct. If ever a society from another province were allowed (it should not be), `province_id` could become inconsistent unless explicitly synced.

### 3.3 Society Visibility for Provincial

- **SocietyVisibilityHelper::queryForProjectForm($user):** For users with `province_id` (e.g. provincial), returns only **active societies in that province**. So provincial users already have a province-scoped society list for dropdowns; the same list can be used for “update society” on the projects list.

### 3.4 Provincial Project Access

- **Authorization:** Access to projects is via **user hierarchy**, not direct province on project: `getAccessibleUserIds($provincial)` defines which users’ projects the provincial can see. Any “update society” action must:
  - Restrict to projects where `project.user_id` is in `getAccessibleUserIds($provincial)`.
  - Restrict `society_id` to societies in the provincial’s province (e.g. via `SocietyVisibilityHelper::getAllowedSocietyIds()` or equivalent), so that **only societies in the same province** can be assigned.

---

## 4. Feasibility Assessment

### 4.1 Functional Feasibility

- **Yes.** The database supports `society_id` / `society_name` (and optionally `province_id` sync). Existing helpers (society visibility, province checks, accessible users) can be reused. No new columns are required.

### 4.2 Authorization Feasibility

- **Yes.** A dedicated “update project society” action can:
  - Be allowed only for roles that can access provincial project list: `provincial` and `general` (when on provincial routes).
  - Check that the project’s `user_id` is in `getAccessibleUserIds(auth()->user())` (same as project list).
  - Validate that the chosen `society_id` is in the provincial’s allowed set (e.g. `SocietyVisibilityHelper::getAllowedSocietyIds()`), ensuring society remains within the same province.

### 4.3 UI Feasibility

- **Yes.** Options (to be decided in implementation phase):
  - **Inline:** Add a “Society” column and an “Update society” control (e.g. dropdown or modal) per row on `/provincial/projects-list`.
  - **Modal:** “Update society” button per row opening a small modal with a province-scoped society dropdown.
  - **Dedicated minimal form:** Link from the list to a page that only edits society (e.g. `GET/PUT /provincial/projects-list/{project_id}/society`).

The list already has an Actions column; an extra button or dropdown there is consistent with the current layout.

### 4.4 Consistency and Integrity

- **Province consistency:** When updating only society, consider setting `project.province_id = society.province_id` when the society changes, so that project always reflects the society’s province. Today’s general update does not do this; for a provincial-scoped list, societies are same-province only, so it is safe but clearer if implemented explicitly.
- **Budget/approval:** Society is metadata; no evidence that budget or approval logic is keyed by society. Changing society does not require unlocking budget or changing status.
- **Activity history:** `ActivityHistoryService` logs project updates and status changes. Recommendation: log a dedicated “project society updated” (or include society change in a generic “project updated”) when society is changed from this flow, for auditability.

---

## 5. Constraints and Risks

| Risk / constraint | Mitigation |
|-------------------|------------|
| Bypassing “editable status” only for society | Implement a **separate** action and route (e.g. `PATCH/PUT /provincial/projects/{project_id}/society`) that only updates society (and optionally province_id). Do not relax the existing full project edit/update gate. |
| Cross-province society | Validate `society_id` against `SocietyVisibilityHelper::getAllowedSocietyIds(auth()->user())` so provincial can only assign societies from their province. |
| General user on provincial route | Same as provincial: use `getAccessibleUserIds()` and province-scoped societies (for general, scope by managed provinces or selected province filter as per existing behaviour). |
| Audit trail | Log society change (e.g. old/new society_id or names) in activity history or application log. |

---

## 6. Recommended Implementation Outline (For Later)

*(No code in this audit; this is a checklist for when implementation is approved.)*

1. **Route:** Add a route under the provincial group, e.g. `PUT` or `PATCH` `/provincial/projects/{project_id}/society`, name e.g. `provincial.projects.updateSociety`.
2. **Controller:** New method (e.g. `ProvincialController@updateProjectSociety`) that:
   - Resolves project by `project_id`; ensures `project.user_id` is in `getAccessibleUserIds(auth()->user())`.
   - Validates request: `society_id` required, exists, and in `SocietyVisibilityHelper::getAllowedSocietyIds(auth()->user())`.
   - Updates project: `society_id`, `society_name` (from Society model), and optionally `province_id` from society.
   - Optionally calls activity history to log the society change.
   - Returns redirect back to `provincial.projects.list` (or JSON if AJAX) with success/error message.
3. **Form request (optional):** A small request class that authorizes (project in accessible set) and validates `society_id`.
4. **UI on `/provincial/projects-list`:**
   - Add a “Society” column (display current `society_name` or “—”) and an “Update society” control (button + dropdown or modal) that submits to the new route. Use `SocietyVisibilityHelper::getSocietiesForProjectForm()` (or equivalent) for the dropdown, passed from `projectList` or loaded via AJAX.
5. **Permissions:** Do **not** change `ProjectPermissionHelper::canEdit` or editable status list. Keep “update society” as a separate capability for provincial (and general on provincial routes) only.

---

## 7. References

- **Route definition:** `routes/web.php` (e.g. lines 350–351 for `provincial.projects.list`).
- **Controller:** `app/Http/Controllers/ProvincialController.php` — `projectList()` (around 471), `getAccessibleUserIds()` (40–77).
- **Permission helper:** `app/Helpers/ProjectPermissionHelper.php` — `canEdit()` uses `ProjectStatus::isEditable()`.
- **Status constants:** `app/Constants/ProjectStatus.php` — `getEditableStatuses()`, `isEditable()`.
- **Society visibility:** `app/Helpers/SocietyVisibilityHelper.php` — `queryForProjectForm()`, `getAllowedSocietyIds()`.
- **Project update flow:** `app/Http/Controllers/Projects/ProjectController.php` (edit/update), `app/Http/Requests/Projects/UpdateProjectRequest.php` (authorize via canEdit), `app/Http/Controllers/Projects/GeneralInfoController.php` (dual-write society_id/society_name).
- **Role access model:** `Documentations/V2/Societies/Mapping/ReadWrite/Role_Access_Model_Analysis.md`.
- **Society dropdown audit:** `Documentations/V2/Societies/Mapping/ReadWrite/Society_Dropdown_Surface_Audit.md`.

---

## 8. Conclusion

The feature is **feasible**: provincial users can be given the ability to update the society of their projects from `/provincial/projects-list` **irrespective of project status**, by introducing a dedicated “update society only” action and route, reusing existing province and society scoping, and without relaxing the existing full project edit/update rules. This document is a review only; no code changes are made until implementation is approved.
