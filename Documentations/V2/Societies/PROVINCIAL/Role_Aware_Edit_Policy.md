# Role-Aware Edit Policy (Wave 5F)

**Version:** Wave 5F  
**Date:** 2026-02-18  
**Status:** Implemented

---

## 1. Purpose of Refactor

- **Replace** the global status-based edit gate with a **role-aware status policy**.
- **Preserve** province isolation (no cross-province access).
- **Preserve** financial immutability (approved/final statuses never editable).
- **Do not weaken** approval integrity.
- **Future-proof** the model: Provincial and Coordinator can operate across the lifecycle (except final states); Executor/Applicant remain restricted to legacy editable statuses.

---

## 2. Previous Global `isEditable` Model

- A single list **`getEditableStatuses()`** defined which statuses were editable.
- **`ProjectPermissionHelper::canEdit()`** used **`ProjectStatus::isEditable($project->status)`** for every role.
- **Effect:** Any role that passed province + ownership checks could edit only if the project was in one of those statuses (draft, reverted_*, etc.). Submitted/forwarded were **not** in the list, so **no one** could edit projects in `submitted_to_provincial` or `forwarded_to_coordinator`, including Provincial and Coordinator.

---

## 3. New Role-Aware Policy

- **`ProjectStatus::canEditForRole(string $status, string $role): bool`** centralizes the rule:
  - **Final statuses** → never editable by any role.
  - **Provincial** → can edit all non-final statuses.
  - **Coordinator** → can edit all non-final statuses (e.g. forwarded_to_coordinator, submitted_to_provincial).
  - **Admin / General** → can edit all non-final statuses.
  - **Executor / Applicant** → can edit only statuses in the legacy **`getEditableStatuses()`** list (unchanged behavior).

- **`ProjectPermissionHelper::canEdit()`** now uses **`ProjectStatus::canEditForRole($project->status, $user->role)`** instead of **`ProjectStatus::isEditable($project->status)`**.
- **Unchanged:** Province check, ownership logic (executor/applicant = owner or in_charge), and delete logic.

---

## 4. Final Status Immutability Guarantee

The following statuses are **never** editable by **any** role:

| Constant | Value |
|----------|--------|
| `APPROVED_BY_COORDINATOR` | approved_by_coordinator |
| `APPROVED_BY_GENERAL_AS_COORDINATOR` | approved_by_general_as_coordinator |
| `APPROVED_BY_GENERAL_AS_PROVINCIAL` | approved_by_general_as_provincial |
| `REJECTED_BY_COORDINATOR` | rejected_by_coordinator |
| `REJECTED_BY_GENERAL` | rejected_by_general |

Defined in **`ProjectStatus::FINAL_STATUSES`**. The first check in **`canEditForRole()`** blocks these for everyone.

---

## 5. Role vs Status Matrix

| Role | Can edit non-final statuses | Can edit FINAL_STATUSES | Notes |
|------|-----------------------------|--------------------------|--------|
| **Provincial** | Yes (all) | No | Full lifecycle edit except final. |
| **Coordinator** | Yes (all) | No | Includes submitted_to_provincial, forwarded_to_coordinator. |
| **Admin** | Yes (all) | No | Same as Provincial/Coordinator for status. |
| **General** | Yes (all) | No | Same as above. |
| **Executor** | Only legacy editable list | No | draft, reverted_*, reverted_to_* only. |
| **Applicant** | Only legacy editable list | No | Same as Executor. |

**Legacy editable statuses** (Executor/Applicant only):  
`draft`, `reverted_by_provincial`, `reverted_by_coordinator`, `reverted_by_general_as_provincial`, `reverted_by_general_as_coordinator`, `reverted_to_executor`, `reverted_to_applicant`, `reverted_to_provincial`, `reverted_to_coordinator`.

**Non-final but not in legacy list** (editable only by Provincial/Coordinator/Admin/General):  
`submitted_to_provincial`, `forwarded_to_coordinator`.

---

## 6. Financial Integrity Note

- **Approved** statuses are in **`FINAL_STATUSES`** and remain **locked** for edits. Financial aggregation (e.g. sanctioned amounts, dashboards) continues to rely on immutable approved data.
- **Budget/report** logic that depends on approved state is unchanged; no new bypass for approved or rejected projects.

---

## 7. Warning: Do Not Enable Edits on FINAL_STATUSES

- **Do not** add any of **`FINAL_STATUSES`** to an “editable” list or bypass **`canEditForRole()`** for those values.
- **Do not** introduce status override endpoints or role overrides that allow editing approved or rejected projects.
- Allowing edits on final statuses would break approval integrity and financial immutability guarantees.

---

## 8. Backward Compatibility

- **`ProjectStatus::getEditableStatuses()`** and **`ProjectStatus::isEditable()`** are **unchanged** and remain in use (e.g. for Executor/Applicant logic and any existing callers).
- **Province check** and **ownership** rules in **`ProjectPermissionHelper::canEdit()`** are **unchanged**.
- **Approval workflow**, report logic, and province boundaries are **not** modified.

---

**End of Wave 5F documentation.**
