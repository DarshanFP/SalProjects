# Province Query Layer Hardening (Phase 2)

**Date:** 2026-02-16  
**Scope:** Query-level province enforcement only. No authorization, routes, or role definitions changed.  
**Objective:** Province-bound users must never see cross-province data; General and Coordinator retain global visibility.

---

## 1. Original Risk

- **ProjectQueryService::getProjectsForUsersQuery($userIds)** returned projects for the given user IDs with **no province filter**.
- Callers (e.g. GeneralController) could receive projects from **any province** when listing by coordinator hierarchy or direct team.
- If a province-bound user (e.g. Provincial or Executor) ever used the same listing path, or if user IDs spanned provinces, **cross-province leakage** was possible.
- General and Coordinator are designed to see all projects (global); the gap was that the query layer did not distinguish between “current user is global” vs “current user is province-bound.”

---

## 2. Structural Improvement

- **Signature change:**  
  `getProjectsForUsersQuery($userIds)` → `getProjectsForUsersQuery($userIds, ?User $currentUser = null)`
- **Conditional province filter inside the method:**
  - If `$currentUser !== null` **and** `$currentUser->province_id !== null` → apply `$query->where('province_id', $currentUser->province_id)`.
  - If `$currentUser === null` **or** `$currentUser->province_id === null` → do **not** apply province filter (global access).
- **Callers** pass `auth()->user()` as the second argument so that:
  - General / Coordinator (typically `province_id === null`) get no province filter → see all.
  - Provincial / Executor (typically `province_id` set) get province filter → see only their province.
- **getProjectIdsForUsers** updated to accept optional `?User $currentUser = null` and forward it to `getProjectsForUsersQuery` so ID-based callers also respect province.

---

## 3. Before vs After Behavior

### Before

- `getProjectsForUsersQuery($userIds)` built:  
  `Project::where(user_id|in_charge in $userIds)` with **no** province condition.
- All callers received projects for those user IDs regardless of province.
- Safe for General/Coordinator (intended global) but risky if the same code path were used for province-bound users or if data were mixed.

### After

- `getProjectsForUsersQuery($userIds, $currentUser)`:
  - Builds the same user_id/in_charge condition.
  - **Additionally:** when `$currentUser` is present and has `province_id`, adds `where('province_id', $currentUser->province_id)`.
  - When `$currentUser` is null or has no `province_id`, no province filter is added.
- Callers pass `auth()->user()`, so:
  - **General / Coordinator** (`province_id` null) → no province filter → **see all projects** (unchanged).
  - **Provincial / Executor** (`province_id` set) → province filter applied → **see only projects in their province**.

---

## 4. Role Matrix Impact

| Role        | province_id   | Filter applied?      | Visibility              |
|------------|---------------|----------------------|-------------------------|
| General    | typically null| No                   | All projects (global)   |
| Coordinator| typically null| No                   | All projects (global)   |
| Provincial | set           | Yes                  | Only own province       |
| Executor   | set           | Yes                  | Only own province       |
| Applicant  | set           | Yes                  | Only own province       |

- **General and Coordinator visibility is NOT restricted.**
- Province-bound users (Provincial, Executor, Applicant) are restricted to their province at the query layer when using `getProjectsForUsersQuery` / `getProjectIdsForUsers` with `auth()->user()`.

---

## 5. Files Modified

| File | Change |
|------|--------|
| **app/Services/ProjectQueryService.php** | `getProjectsForUsersQuery($userIds, ?User $currentUser = null)`: when `$currentUser` and `$currentUser->province_id` are set, apply `where('province_id', $currentUser->province_id)`. `getProjectIdsForUsers($userIds, ?User $currentUser = null)` added and forwards to `getProjectsForUsersQuery`. |
| **app/Http/Controllers/GeneralController.php** | All calls to `getProjectsForUsersQuery(...)` and `getProjectIdsForUsers(...)` updated to pass `auth()->user()` as the second argument (5 and 2 call sites respectively). |

No changes to ProjectPermissionHelper, route definitions, authorization middleware, or role definitions.

---

## 6. Safety Confirmation

- **General sees all projects across provinces:** Yes — General has `province_id` null, so no province filter is applied.
- **Coordinator sees all:** Yes — same as above when Coordinator has `province_id` null.
- **Provincial sees only own province:** Yes — when `province_id` is set, the query adds `where('province_id', $currentUser->province_id)`.
- **Executor sees only own province (and own projects):** Yes — same province filter; existing logic (user_id / in_charge) still restricts to projects they own or are in-charge of, within that province.
- **No cross-province leakage for province-bound users:** The query layer now enforces province whenever the current user has `province_id` set; callers use `auth()->user()` so the acting user’s province is used.
- **Authorization logic unchanged:** ProjectPermissionHelper, canEdit, canView, etc. were not modified; this is query-layer hardening only.

---

## 7. Regression Checklist

- [ ] General: List projects (coordinator hierarchy + direct team); confirm projects from all provinces appear.
- [ ] Coordinator: Same as General if Coordinator uses the same listing; confirm global visibility.
- [ ] Provincial: List projects; confirm only projects in the provincial’s province appear.
- [ ] Executor: List projects; confirm only own province and only projects where they are owner or in-charge.
- [ ] No cross-province rows for Provincial/Executor in any listing using this query path.

---

**End of document.**  
Phase 2 is limited to query-level province enforcement; authorization and roles are unchanged.
