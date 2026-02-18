# Audit: Provincial Project Edit Capability

**Objective:** Determine whether Provincial users can pass `ProjectPermissionHelper::canEdit()` for projects listed at `/provincial/projects-list`. No code changes; read-only.

---

## PHASE 1 — TRACE `canEdit()` LOGIC

**File:** `app/Helpers/ProjectPermissionHelper.php`

**Method:** `canEdit(Project $project, User $user)` (lines 32–51)

### 1. Province check

- **Code:** `passesProvinceCheck($project, $user)` (line 35).
- **Logic:** If `$user->province_id === null` → true. Else → true iff `$project->province_id === $user->province_id`.
- **Ref:** `passesProvinceCheck()` lines 21–26.

### 2. Role-based conditions

- **Code:** lines 41–48.
- **Behavior:** If role in `['provincial', 'coordinator']` → return true (no ownership check). If role in `['executor', 'applicant']` → return true iff `$project->user_id === $user->id || $project->in_charge === $user->id`. If role in `['admin', 'general']` → return true. Else → false.

### 3. Status-based conditions

- **Code:** lines 37–39.
- **Logic:** If `!ProjectStatus::isEditable($project->status)` → return false.

### 4. Ownership constraints

- **Provincial/coordinator/admin/general:** No ownership check; only province + status.
- **Executor/applicant:** Must be owner (`user_id`) or in-charge (`in_charge`).

### 5. Early returns

1. If `!passesProvinceCheck(...)` → return false.
2. If `!ProjectStatus::isEditable($project->status)` → return false.
3. Then role branch: provincial/coordinator → true; executor/applicant → owner/in_charge; admin/general → true; else → false.

### Full decision tree (logical)

```
canEdit(project, user)
├── passesProvinceCheck(project, user) ?
│   ├── NO  → return false
│   └── YES ↓
├── ProjectStatus::isEditable(project->status) ?
│   ├── NO  → return false
│   └── YES ↓
└── user->role ?
    ├── in ['provincial','coordinator'] → return true
    ├── in ['executor','applicant']     → return (project->user_id === user->id || project->in_charge === user->id)
    ├── in ['admin','general']          → return true
    └── else                            → return false
```

**Code reference:** `ProjectPermissionHelper.php` lines 32–51.

---

## PHASE 2 — EDITABLE STATUSES

**File:** `app/Constants/ProjectStatus.php`

**`getEditableStatuses()` (lines 43–56):**

- `draft`
- `reverted_by_provincial`
- `reverted_by_coordinator`
- `reverted_by_general_as_provincial`
- `reverted_by_general_as_coordinator`
- `reverted_to_executor`
- `reverted_to_applicant`
- `reverted_to_provincial`
- `reverted_to_coordinator`

**`isEditable(string $status)` (lines 79–82):** `return in_array($status, self::getEditableStatuses());`

All and only the above statuses are editable.

---

## PHASE 3 — PROVINCIAL ROLE BEHAVIOR

**Sources:** `ProvincialController` (e.g. middleware `role:provincial,general` line 36), `getAccessibleUserIds()` (lines 45–81).

### 1. Does provincial own projects?

- **canEdit:** For role `provincial` there is no check on `project->user_id` or in_charge; province + editable status suffices.
- **projectList:** Uses `whereIn('user_id', $accessibleUserIds)`. `getAccessibleUserIds()` does not add the provincial’s own ID (only direct children and, for general, province users). So the list shows projects whose creator is in the accessible set (child executors/applicants, or for general other users in managed provinces). Provincial does not need to be owner to edit.

### 2. Are projects created by child users?

- **Yes.** `projectList()` (lines 481–482) uses `Project::whereIn('user_id', $accessibleUserIds)`. So every project in the list has `project->user_id` in `accessibleUserIds`, i.e. creator is a direct child (executor/applicant with `parent_id = provincial->id`) or, for general, a user in managed provinces. So those projects are “created by child users” (or other province users for general).

### 3. Does canEdit() allow provincial to edit child-user projects?

- **Yes.** For `role === 'provincial'`, after province and editable-status checks, the code returns true with no ownership check (lines 41–42). So provincial can edit any project in their province that is in an editable status, including when `project->user_id` is a child.

### 4. Is role comparison strict (e.g. only creator can edit)?

- **No.** For provincial/coordinator the check is only province + editable status. Creator/in_charge is required only for executor/applicant.

**Code references:** `ProjectPermissionHelper::canEdit` lines 41–42; `ProvincialController::getAccessibleUserIds` lines 45–81; `projectList` lines 481–482.

---

## PHASE 4 — projectList() DATA SOURCE

**Method:** `ProvincialController::projectList()` (from line 474).

### 1. How projects are retrieved

- `$accessibleUserIds = $this->getAccessibleUserIds($provincial);`
- `$baseQuery = Project::whereIn('user_id', $accessibleUserIds)` + optional filters (project_type, user_id, status, center).
- Paginated list uses the same `$baseQuery` (cloned) with `->paginate($perPage)`.

So projects are those whose creator (`user_id`) is in `$accessibleUserIds`. There is no additional filter on `project.province_id` in the query.

### 2. Do returned projects satisfy canEdit() role requirements?

- **canEdit** for provincial requires: (1) same province, (2) editable status. It does not require `user_id` or in_charge.
- Projects in the list have `user_id` in `accessibleUserIds`. Those users (direct children or managed-province users) are in the same province as the provincial (by hierarchy). Project `province_id` is set at create from creator’s province (or society), so for normal data `project->province_id` matches the provincial’s province. So for projects in the list, **province check** is satisfied in normal conditions.
- **Status** is not filtered in the query; the list can include both editable and non-editable statuses. So: if status is in `ProjectStatus::getEditableStatuses()`, **canEdit** can return true (assuming province matches). If status is not editable (e.g. submitted, approved), **canEdit** returns false. So list membership does not guarantee canEdit; only those in **editable** status pass the status part of canEdit.

### 3. project.user_id vs provincial user ID

- **project.user_id** for list items is always one of `$accessibleUserIds` (child executor/applicant or, for general, province user). It is not the provincial’s ID (provincial’s ID is not added to `getAccessibleUserIds()`).
- So for a typical provincial user, **project.user_id** is a **child** user ID, not the provincial’s. canEdit does not require it to be the provincial.

**Code references:** `ProvincialController::projectList()` lines 476–482, 547–550; `getAccessibleUserIds()` 45–81; `GeneralInfoController::store()` (project `province_id` from creator/society) ~62–68.

---

## PHASE 5 — SCENARIO MATRIX

Using only `ProjectPermissionHelper::canEdit()` and `ProjectStatus::isEditable()`.

| # | Scenario | Province | Status | Creator (user_id) | canEdit() outcome | Reason |
|---|----------|----------|--------|-------------------|-------------------|--------|
| **A** | Provincial user; project in **draft**; created by **child** | Same province | draft (editable) | Child | **true** | Province OK; status editable; role provincial → true (no ownership check). |
| **B** | Provincial user; project **reverted_by_provincial**; created by child | Same province | reverted_by_provincial (editable) | Child | **true** | Same as A. |
| **C** | Provincial user; **approved** project | Same province | e.g. approved_by_coordinator (not editable) | Any | **false** | `ProjectStatus::isEditable()` false → early return false (line 37–39). |
| **D** | Provincial user; **draft** project created by **another province** | Different province | draft (editable) | Other province | **false** | `passesProvinceCheck()` false (project.province_id ≠ user.province_id) → early return false. |

Note: In scenario D, such a project would not appear in `/provincial/projects-list` anyway, because `getAccessibleUserIds()` only includes users in the provincial’s hierarchy (same province). So canEdit is false both by province check and by list construction.

---

## PHASE 6 — CONCLUSION REPORT

### 1. Can provincial edit projects in editable statuses?

**YES.**

For a provincial user, `canEdit($project, $user)` returns true if and only if:

1. **Province:** `passesProvinceCheck($project, $user)` is true (same province, or user has no province_id).
2. **Status:** `ProjectStatus::isEditable($project->status)` is true.

There is **no** ownership or in-charge requirement for role `provincial`. So provincial can edit all projects in their province that are in one of the editable statuses, including projects created by child users.

### 2. If NO — blocking condition

Not applicable; the answer is YES for editable statuses.

### 3. If logic prevented editing child projects — recommendation

Not applicable. The current logic **allows** provincial to edit child-user projects (same province + editable status). No change needed for that.

### Truth table (canEdit for provincial)

| passesProvinceCheck | isEditable(status) | canEdit (provincial) |
|---------------------|--------------------|----------------------|
| false               | any                | **false**            |
| true                | false              | **false**            |
| true                | true               | **true**             |

### Where the gate is enforced

- **Update (save):** `UpdateProjectRequest::authorize()` calls `ProjectPermissionHelper::canEdit($project, $user)` (UpdateProjectRequest.php lines 17–28).
- **Edit (form):** `ProjectController::edit()` uses `ProjectPermissionHelper::canEdit($project, $user)` (ProjectController.php ~1092–1094).
- **Delete:** e.g. `AttachmentController` and other mutation controllers use `canEdit` (or equivalent) before delete.

---

**End of audit.** No code was modified; all conclusions are from the referenced files and the `canEdit()` gate only.
