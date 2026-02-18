# Province-Based Project Access Refactor — Modified Files

**Date:** 2026-02-16  
**Audit type:** Read-only (no files modified by this audit)

---

## 1. Summary

| Category | Count |
|----------|--------|
| **Total files modified** | 5 |
| **Total files created** | 1 |
| **Total files deleted** | 0 |

*(Created file: this document. The refactor also added an untracked doc `Province_Based_Project_Access_Refactor.md` in a prior step.)*

---

## 2. Modified Files List

### 1. app/Helpers/ProjectPermissionHelper.php

- **Change type:** Modified
- **Methods modified / added:**
  - **Added:** `passesProvinceCheck(Project $project, User $user): bool` — Enforces province isolation; returns false when both have province_id and they differ.
  - **Refactored:** `canEdit()` — Now checks province first, then editable status, then role (provincial/coordinator → true; executor/applicant → owner or in_charge; admin/general → true).
  - **Added:** `canUpdate()` — Alias of `canEdit()`.
  - **Added:** `canDelete()` — Same rules as `canEdit()`.
  - **Refactored:** `canSubmit()` — Added province check before status and ownership.
  - **Refactored:** `canView()` — Added province check; admin/coordinator/provincial/general can view (within province when applicable); executor/applicant only if owner or in_charge.
  - **Refactored:** `getEditableProjects()` — Adds `where('province_id', $user->province_id)` when user has province_id; for executor/applicant restricts to owner/in_charge and not approved.
- **Purpose of change:** Centralize province-based authorization; ensure project.province_id matches user.province_id and role-based edit/view/delete rules are applied consistently.

---

### 2. app/Http/Requests/Projects/UpdateProjectRequest.php

- **Change type:** Modified
- **Methods modified:**
  - **rules()** — Replaced `society_id` validation from `Rule::in($allowedSocietyIds)` to `['required', 'exists:societies,id']` (draft: `['nullable', 'exists:societies,id']`).
  - **messages()** — Added `society_id.required` and `society_id.exists`.
- **Other:** Removed imports `Illuminate\Validation\Rule` and `App\Helpers\SocietyVisibilityHelper`.
- **Purpose of change:** Stop blocking updates when the project’s society is outside the previous allow-list; permission is handled in `authorize()` via `ProjectPermissionHelper::canEdit()`, not via society validation.

---

### 3. app/Helpers/SocietyVisibilityHelper.php

- **Change type:** Modified
- **Methods modified / added:**
  - **Refactored:** `queryForProjectForm()` — No longer branches by role; if `user->province_id` is set, returns societies where `province_id = user->province_id`; if null, returns all active societies. Added return type `Builder`.
  - **Added:** `getAllowedSocieties(?User $user = null)` — Alias that returns `queryForProjectForm($user)->get()`.
- **Purpose of change:** Society visibility is province-based only (when user has province_id); no “global” societies or role-specific allow-lists.

---

### 4. app/Services/ProjectQueryService.php

- **Change type:** Modified
- **Methods modified:**
  - **getProjectsForUserQuery()** — Builds query with optional `where('province_id', $user->province_id)` when `$user->province_id !== null`, then applies owner/in_charge filter.
- **Purpose of change:** Executor/applicant project lists (index, create predecessor list, etc.) only include projects in the user’s province.

---

### 5. app/Http/Controllers/Projects/ProjectController.php

- **Change type:** Modified
- **Methods modified:**
  - **show()** — Replaced custom access logic (switch on admin/coordinator/provincial) with a single `ProjectPermissionHelper::canView($project, $user)` check.
  - **destroy()** — Added guard: if `!ProjectPermissionHelper::canDelete($project, Auth::user())`, aborts with 403.
  - **listProjects()** — Added province filter: when `$user->province_id !== null`, applies `$query->where('province_id', $user->province_id)`; removed comment about coordinator having no filter.
- **Purpose of change:** Align show/delete/list with province isolation and centralized permission helper; coordinators and provincials only see projects in their province.

---

## 3. Newly Created Files (refactor-related)

| File | Description |
|------|-------------|
| `Documentations/V2/FinalFix/OtherIssues/Province_Access_Refactor_Modified_Files.md` | This audit document. |
| `Documentations/V2/FInalFix/OtherIssues/Province_Based_Project_Access_Refactor.md` | Refactor design and behaviour doc (untracked in git). |

---

## 4. Deleted Files (refactor)

None.

---

## 5. Safety Confirmation

| Check | Result |
|-------|--------|
| **No unrelated modules modified** | Yes. Only the five files above were modified; no attachment controllers, other request classes, or unrelated helpers were changed. |
| **No migrations executed** | Yes. No migrations were run or added as part of this refactor. |
| **No attachment logic altered** | Yes. IES/IIES/IAH/ILP attachment controllers and views were not modified. |
| **No config files changed** | Yes. No config or env changes. |

---

## 6. Git Snapshot (at audit time)

```
Modified (5):
  app/Helpers/ProjectPermissionHelper.php            | 105 +++++++++++++--------
  app/Helpers/SocietyVisibilityHelper.php            |  38 +++-----
  app/Http/Controllers/Projects/ProjectController.php|  56 +++--------
  app/Http/Requests/Projects/UpdateProjectRequest.php|   9 +-
  app/Services/ProjectQueryService.php               |  15 ++-
 5 files changed, 107 insertions(+), 116 deletions(-)
```
