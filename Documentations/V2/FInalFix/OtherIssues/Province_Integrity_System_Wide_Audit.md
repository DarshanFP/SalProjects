# Province Integrity System-Wide Audit

**Date:** 2026-02-16  
**Scope:** Read-only investigation. No code modified, no migrations run, no fixes applied.  
**Objective:** Identify missing `province_id` propagation and related risks across the codebase.

---

## 1. Tables With province_id

| Table | Source | Notes |
|-------|--------|--------|
| **projects** | Migrations: `add_province_id_to_projects_table`, `enforce_projects_province_id_not_null`, `production_phase3_*` | NOT NULL + FK to `provinces(id)` (production phase 3). |
| **users** | Migrations: `add_province_center_foreign_keys_to_users_table`, `add_province_id_to_users_table`, `enforce_users_province_id_not_null`, `production_phase2_*` | NOT NULL + FK (production phase 2). |
| **societies** | `create_societies_table`, `make_societies_province_id_nullable`, `production_phase1_*` | Nullable (global societies allowed). |
| **centers** | `create_centers_table` | NOT NULL, FK to provinces. |
| **provincial_user_province** | `create_provincial_user_province_table` | Pivot: `user_id`, `province_id`. |

**Report tables:** No `province_id` column found in migrations. Reports are scoped by `project_id` only; province scope is implicit via project.

**Child project tables (ILP, IAH, IIES, IES, EduRUT, etc.):** No `province_id` column. Child records use `project_id` only; province is implied by parent project.

---

## 2. Model Configuration Review

| Model | province_id in $fillable? | Guarded? | Cast? | Mutator? | Auto-set? |
|-------|---------------------------|----------|-------|----------|-----------|
| **Project** (`App\Models\OldProjects\Project`) | **No** | N/A (fillable used) | No | No | No |
| **User** | N/A | `$guarded = []` (all mass assignable) | No | No | No |
| **Society** | Yes | No | No | No | No |
| **Center** | Yes | No | No | No | No |
| **Province** | N/A (no province_id on Province) | — | — | — | — |

**Finding (Critical):** `Project` model does **not** include `province_id` in `$fillable`. Therefore `Project::create($validated)` will **never** set `province_id` via mass assignment, even if it is present in the array.

---

## 3. Creation Flow Gaps

### 3.1 Project creation

- **Entry:** `ProjectController::store()` → `storeGeneralInfoAndMergeProjectId()` → `GeneralInfoController::store()`.
- **GeneralInfoController::store():**
  - Uses `$validated = $request->validated()` (from `StoreProjectRequest`).
  - Adds: `user_id`, `status`, `commencement_month_year`, `amount_forwarded`, `local_contribution`, `executor_*`, `in_charge`, `overall_project_budget`, `predecessor_project_id`, `full_address`, `society_name`.
  - **Does not set `province_id`** (neither from user nor from society).
- **StoreProjectRequest / StoreGeneralInfoRequest:** No rule for `province_id`; form does not send it.
- **Project model:** `province_id` not in `$fillable`, so it would be ignored on `create()` even if set.

**Result:** New projects are created **without** `province_id` being set in code. If the DB enforces `NOT NULL` on `projects.province_id`, creation may fail (or use DB default if any). **Flagged: Critical creation regression (A).**

**Recommended (no code applied):** Set `province_id` from society or from auth user before create (e.g. `$validated['province_id'] = Society::find($validated['society_id'])->province_id ?? auth()->user()->province_id`), and add `province_id` to `Project::$fillable` (or set via `$project->province_id = ...` after create).

### 3.2 User creation

- **GeneralController:** Coordinator, Provincial, and Executor creation all set `province_id` explicitly (e.g. `province_id` from request/context).
- **ProvincialController:** Executor and new Provincial creation set `province_id` (e.g. `$provinceId`, `$province->id`).

**Result:** No creation gap identified for User.

### 3.3 Society / Center creation

- **GeneralController / ProvincialController:** `Society::create()` and `Center::create()` include `province_id` in the payload.

**Result:** No creation gap identified for Society or Center.

### 3.4 Report creation

- **AnnualReport, HalfYearlyReport, QuarterlyReport:** Created with `project_id`, `report_id`, `generated_by_user_id`. No `province_id` on these models/tables; scope is via project. No gap.

### 3.5 Child project records (IIES, IES, IAH, ILP, EduRUT, etc.)

- Controllers create child records (e.g. `ProjectILPRevenuePlanItem::create([...])`) with `project_id` and type-specific fields only. No `province_id` on child tables.
- Access to the project (and thus to creating/editing child records) is gated by `UpdateProjectRequest` / `ProjectPermissionHelper::canEdit()`, which enforce province (and role). There is **no explicit “project province matches user province” check** immediately before creating a child record; reliance is on “user can only edit this project if province matches.”

**Result:** Flagged as **Minor (E):** No separate province_id on children; consider documenting that province alignment is enforced via parent project access only.

---

## 4. Update Flow Risks

### 4.1 Project update

- **GeneralInfoController::update():** Uses `$validated = $request->validated()` (from `UpdateProjectRequest`). No rule for `province_id` in `UpdateProjectRequest::rules()`; `prepareForValidation()` merges `in_charge`, `overall_project_budget`, `society_id` (and draft-related fields) from existing project, but **does not merge or protect `province_id`**.
- **Project::$fillable:** Does not include `province_id`, so `$project->update($validated)` would not change `province_id` even if it were present in the request.
- **Risk:** If `province_id` were later added to `$fillable` and to the form/validation, it could become editable and allow cross-province reassignment. Currently: **Update does not set or overwrite province_id (safe by omission).** Flag as **Update inconsistency (D):** Intentionally keep province_id non-editable and ensure it is never added to update rules or fillable for user input.

### 4.2 User update

- **GeneralController (province rename, user assignment, etc.):** Various flows set or clear `user->province_id` (e.g. when assigning provincials to a province). Logic is context-specific; no generic “accidentally set to null” pattern identified in the audited paths.

### 4.3 Society / Center update

- Society and Center have `province_id` in fillable; updates that include `province_id` (e.g. center transfer) are explicit in GeneralController. No accidental nulling identified.

---

## 5. Project-Type Controller Review

- **IIES, IES, IAH, ILP, EduRUT (and other type-specific controllers):** Child records are created/updated by `project_id`. Authorization is at the project level (route/request authorization and `ProjectPermissionHelper::canEdit`), which enforces province. No controller was found that creates a child record without the user having already passed project-level access checks.
- **Gap:** No explicit “parent project’s province_id matches current user’s province_id” check immediately before create. Acceptable if all entry points are behind the same project permission layer; otherwise consider adding an explicit check for defense in depth.

**Classification:** Minor (E) — recommend documenting that province alignment is enforced via parent project access only; optional explicit check in critical paths.

---

## 6. Query Enforcement Review

| Location | Province filter applied? | Notes |
|----------|---------------------------|--------|
| **ProjectController::listProjects()** | Yes | `if ($user->province_id !== null) $query->where('province_id', $user->province_id)`. |
| **ProjectQueryService::getProjectsForUserQuery()** | Yes | Same pattern. |
| **ProjectPermissionHelper::getEditableProjects()** | Yes | Same pattern. |
| **ProjectQueryService::getProjectsForUsersQuery($userIds)** | **No** | Returns `Project::where(user_id|in_charge in $userIds)` with **no** province filter. |
| **GeneralController** (project listing) | No (by design?) | Uses `getProjectsForUsersQuery($allUserIdsUnderCoordinators)` and similar; no province filter. General may be intended to see all teams’ projects across provinces. |
| **GeneralController** (raw `Project::where(...)`) | No | Multiple places use `Project::where(function($q) { $q->whereIn('user_id', ...)->orWhereIn('in_charge', ...) })` with no province condition. |

**Findings:**

- **B) Authorization gap:** `getProjectsForUsersQuery($userIds)` does not apply province. Callers (e.g. GeneralController) can receive project lists that span provinces. If General/Coordinator should be province-bound, this is a gap; if they are intentionally global, document it and optionally restrict `$userIds` by province at the caller.
- **Listings for Provincial/Coordinator:** `listProjects` and `getProjectsForUserQuery` correctly enforce province for the current user.

---

## 7. Authorization Consistency

| Layer | Province handling |
|-------|-------------------|
| **ProjectPermissionHelper** | `passesProvinceCheck($project, $user)`: allows if `user->province_id === null`, else requires `project->province_id === user->province_id`. Used in `canEdit`, `canUpdate`, `canDelete`, `canSubmit`, `canView`. Consistent. |
| **SocietyVisibilityHelper** | `queryForProjectForm()`: if `user->province_id !== null`, restricts societies to `province_id = user->province_id`. Consistent. |
| **ProjectQueryService** | `getProjectsForUserQuery()` applies province; `getProjectsForUsersQuery()` does not. Mixed; see §6. |
| **ProjectController::listProjects()** | Applies province filter. Consistent for that view. |

**Summary:** Single-user project queries and permission checks are province-consistent. Multi-user project listing (General/Coordinator) does not enforce province at the query service level.

---

## 8. Identified Risks

| ID | Classification | Description |
|----|----------------|-------------|
| 1 | **A) Critical (data integrity)** | Project creation never sets `province_id`. Project model does not have `province_id` in `$fillable`, and GeneralInfoController does not set it. With DB NOT NULL, new projects may fail or rely on default. |
| 2 | **B) Authorization gap** | `ProjectQueryService::getProjectsForUsersQuery($userIds)` does not filter by province. GeneralController and any other caller can get cross-province projects if `$userIds` span provinces. |
| 3 | **B) Authorization gap** | GeneralController uses raw `Project::where(user_id|in_charge in ...)` in many places without province filter. For General, may be intentional; should be confirmed and documented. |
| 4 | **C) Creation regression** | Same as #1: project create path omits `province_id`. |
| 5 | **D) Update inconsistency** | `UpdateProjectRequest` and GeneralInfoController do not mention `province_id`. Safe today; if `province_id` is ever added to fillable or form, it could become editable. Recommend keeping province_id server-set only and not in update rules. |
| 6 | **E) Minor** | Child project records (IIES, IES, IAH, ILP, etc.) have no province_id and no explicit “project province matches user” check before create; reliance on project-level permission only. |

---

## 9. Recommended Hardening Strategy (NO CODE APPLIED)

1. **Project creation (Critical)**  
   - Before `Project::create()`, set `province_id` from society (e.g. `Society::find($validated['society_id'])->province_id`) or, if no society, from `auth()->user()->province_id`.  
   - Add `province_id` to `Project::$fillable` so the value is persisted on create (or set `$project->province_id = ...` after create and save).  
   - Ensure StoreProjectRequest/StoreGeneralInfoRequest do not accept `province_id` from the client; keep it server-side only.

2. **Project update**  
   - Do not add `province_id` to update validation or to user-editable fillable. In `prepareForValidation()` (draft/update), optionally merge existing `province_id` from project so it is never overwritten if fillable is later extended.

3. **Query layer**  
   - Either: (a) Add an optional province filter to `getProjectsForUsersQuery()` when the calling user is province-scoped (e.g. pass `$user` and apply `where('province_id', $user->province_id)` when `$user->province_id !== null`), or (b) Document that General/Coordinator are allowed to see cross-province and ensure `$userIds` are only those under their allowed scope.

4. **GeneralController project listings**  
   - Decide whether General should see projects only in certain provinces. If yes, apply province filter (e.g. by user’s province or by allowed province list) to all project queries that aggregate by coordinator/direct team.

5. **Child record creation (optional)**  
   - In type-specific controllers, optionally add a one-liner: ensure `$project->province_id === auth()->user()->province_id` (or equivalent) when user is province-scoped, before creating child records, for defense in depth.

6. **Documentation**  
   - Document that project province is set at create only (from society or user), never from client input, and that child tables inherit province via parent project access control.

---

## 10. Priority Fix Order

1. **P0 (Critical):** Set `province_id` on project creation and add it to `Project::$fillable` (or set after create). Prevents integrity/constraint failures and ensures every project has a province.
2. **P1 (Authorization):** Resolve `getProjectsForUsersQuery()` and GeneralController project listing: either add province filter for province-scoped users or document and enforce that General’s `$userIds` are limited by allowed provinces.
3. **P2 (Consistency):** Document that `province_id` is not updateable via form; consider merging existing `province_id` in update prepareForValidation so it is never accidentally cleared if fillable/rules change.
4. **P3 (Minor):** Document that child record province alignment is enforced via parent project access; optionally add explicit province check before child create in critical controllers.

---

**End of audit.**  
No code was modified; no migrations were run; no fixes were applied. This document is for analysis and planning only.
