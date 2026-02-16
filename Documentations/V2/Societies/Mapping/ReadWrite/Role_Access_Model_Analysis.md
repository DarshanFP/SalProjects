# Role Access Model Analysis

**Date:** 2026-02-15  
**Purpose:** Formal definition of role behavior before implementing province-scoped society dropdown logic (Phase 5B).  
**Constraints:** NO CODE MODIFICATIONS. NO SCHEMA CHANGES. Analysis and documentation only.

---

## 1. Roles Identified

**Source:** Database schema and seeders (no live `SELECT DISTINCT role` run; inferred from code).

| Role        | In DB Enum (migration) | In Seeders / Code | Notes |
|------------|-------------------------|-------------------|--------|
| **admin**      | Yes (`create_users_table`, `add_applicant_role`) | UsersTableSeeder | Admin-only routes; budget reconciliation; read-only project/report views. |
| **coordinator**| Yes | UsersTableSeeder, GeneralController (creates), CoordinatorController | Has coordinator routes; can approve/revert projects and reports; sees all projects. |
| **general**    | Yes | UsersTableSeeder, GeneralController | Has general-only routes + **all** coordinator routes (`role:coordinator,general`). Can act as provincial on provincial routes (`role:provincial,general`). |
| **provincial** | Yes | UsersTableSeeder, GeneralController (creates), ProvincialController | Single province (province_id); sees projects/reports of children (executor/applicant under them) and optionally province users when General. |
| **executor**   | Yes | UsersTableSeeder, GeneralController, ProvincialController, CoordinatorController | Creates/edits own or in-charge projects; submits to provincial. |
| **applicant**  | Yes (added in `add_applicant_role`) | ReportTestDataSeeder, various controllers | Same project capabilities as executor (owner/in-charge edit, submit). |

**List for dropdown/refactor:** `admin`, `coordinator`, `general`, `provincial`, `executor`, `applicant`.

---

## 1.1 Role Usage in Code (Categorized)

**Controllers:** Role checks in GeneralController (`role !== 'general'`, `role === 'coordinator'`, `role === 'provincial'`, `role === 'executor'/'applicant'`), ProvincialController (`role === 'general'`, `getAccessibleUserIds`), CoordinatorController, ProjectController (`role === 'provincial'` for list filter), ExportController (`admin`, `coordinator`, `provincial`), ReportController and report controllers (provincial/coordinator/executor/applicant), Admin/BudgetReconciliationController (`role === 'admin'`), Auth (commented role redirects).

**Middleware:** `routes/web.php` — `role:admin`, `role:coordinator,general`, `role:general`, `role:provincial`, `role:provincial,general` (ProvincialController), `role:executor,applicant`, and shared groups `role:executor,applicant,provincial,coordinator,general` for project/report views and downloads.

**Policies:** No project/society policies found; `AuthServiceProvider` has no Gate definitions. Access is route middleware + controller checks + ProjectPermissionHelper (role in canView/canSubmit).

**Blade role checks:** `auth()->user()->role === 'admin'`, `hasRole('admin')`, etc. in profileAll/app.blade.php; `role === 'executor'|'applicant'|'provincial'|'coordinator'` in projects/Oldprojects/show, reports/monthly (index, partials), reports/aggregated (index).

**Service classes:** ProjectStatusService (general vs coordinator vs provincial for approve/revert); ReportStatusService (same); ProjectPermissionHelper (admin, coordinator, provincial can view all; executor/applicant submit); ActivityHistoryHelper (provincial); HandlesAuthorization trait (hasRole, isAdmin, isCoordinator, isProvincial, isGeneral, isExecutorOrApplicant).

---

## 2. Role Capability Matrix

| Role        | Create Project | Edit Project (own/in-charge) | Approve Project | View All Projects | Province Scope | Society Scope (current) | Notes |
|------------|----------------|------------------------------|-----------------|-------------------|----------------|--------------------------|--------|
| **admin**  | No             | No (read-only)               | No              | Yes (all)          | All            | N/A (no dropdown use)    | Admin routes only; no project create/edit. |
| **coordinator** | No        | No (but can view all)        | Yes (approve/revert as coordinator) | Yes (all) | All (filter optional) | Not used in coordinator project list; no society dropdown in coordinator flow. |
| **general**| No             | No (but can view via coordinator + general project list) | Yes (as coordinator or as provincial, with level revert) | Yes (coordinator hierarchy + direct team; filter by province/coordinator) | Multi (managedProvinces pivot + optional session filter) | All societies in dropdown today (hardcoded 9); when building provincial/executor forms: provinces with activeSocieties (all provinces). | Uses coordinator routes + general routes; can use provincial routes (middleware `role:provincial,general`); getAccessibleUserIds includes managed provinces. |
| **provincial** | No          | No (but can view projects of accessible users) | Yes (forward/revert as provincial) | No — only users in getAccessibleUserIds (direct children) | Own province (province_id) | Province-only (Society::where(province_id)) in provincial views. | Single province; society dropdown already province-scoped in code. |
| **executor**   | Yes        | Yes (owner or in-charge; status editable) | No | No — only own + in-charge | User's province (for in-charge filter in edit) | Hardcoded 9 in project create/edit; no province filter on list. | Creates/edits projects; submits to provincial. |
| **applicant**  | Yes        | Yes (same as executor)      | No              | No — only own + in-charge | User's province | Same as executor. | Same as executor for projects. |

**Approve semantics:**  
- **Coordinator:** `ProjectStatusService::approve` → APPROVED_BY_COORDINATOR; revert → REVERTED_BY_COORDINATOR.  
- **General:** Same service; when `user->role === 'general'` → APPROVED_BY_GENERAL_AS_COORDINATOR / REVERTED_BY_GENERAL_AS_COORDINATOR; can also revert as provincial (REVERTED_BY_GENERAL_AS_PROVINCIAL, etc.).  
- **Provincial:** Forward to coordinator; revert to executor/applicant (no final “approve” at provincial level for project).

---

## 3. Province Behavior Model

- **admin:** No province_id requirement in logic; sees all data.  
- **coordinator:** No province_id tied to user in code; sees all projects; can filter by `user.province` (string) in UI.  
- **general:**  
  - **Not** tied to a single province_id for “own province.”  
  - Has **managed provinces** via pivot `provincial_user_province` (many-to-many).  
  - When General uses **provincial routes** (e.g. provincial dashboard, project list), `getAccessibleUserIds()` includes: (1) direct children (executor/applicant under general), (2) users in **managed provinces** (province_id in managed list), with optional session province filter.  
  - So General is **multi-province / super-provincial** when acting on provincial routes; no single “own” province.  
- **provincial:** Exactly one province: `User.province_id` (and legacy `User.province` string). All society/list scoping is by this province.  
- **executor / applicant:** Have `province_id` / `province` (assigned by provincial or general). Used for in-charge dropdown filtering (same province) and for display; not for “view all projects in province” (they only see own/in-charge).

**Conclusion:** province_id is “own province” only for **provincial**. For **general** it’s “managed provinces” (pivot); for **coordinator** there is no province scope.

---

## 4. Society Visibility Model (Intended for Phase 5B)

Based on code and role behavior:

| Role        | Context | Which societies in dropdown? | Rationale |
|------------|---------|------------------------------|------------|
| **admin**  | N/A     | N/A                          | No society dropdown in admin flows. |
| **coordinator** | Coordinator project/report flows | **All societies** (or per-province if UI adds province filter) | Coordinator sees all projects; no province restriction. Option: all active societies; or societies from provinces that have projects. |
| **general**| Project create/edit (general) | **All active societies** (or grouped by province) | General sees all projects (coordinator + direct team); can create projects for any province via executor/applicant. So society list should not be restricted to one province. |
| **general**| Provincial/executor user create/edit (general) | **Province-scoped + optional global** | When creating/editing provincial or executor, society dropdown is already province-driven (JS or server). Should remain: societies for selected province (or managed provinces) plus global (province_id NULL) if business allows. |
| **provincial**| Project create/edit (if ever given) / Provincial user create/edit / Executor create/edit | **Province-specific only** | Provincial has single province_id. Only societies with `province_id = user.province_id` (and is_active). |
| **executor / applicant**| Project create/edit | **Province-scoped** (user’s province) + **global** (if any) | Restrict to societies in the user’s province so they cannot assign a society from another province. Optionally include global societies (province_id NULL). |

**Summary:**  
- **Province-specific only:** Provincial (all contexts).  
- **Province + global:** Executor/Applicant (project form).  
- **All or province-grouped:** General (project form); Coordinator (if society dropdown added).  
- **Province-scoped (and optional global):** General when acting as provincial (provincial/executor forms).

---

## 5. Identified Ambiguities

1. **General — “super-provincial” vs “multi-province provincial”**  
   - **Code:** General has coordinator-level access (all projects) and can use provincial routes with `getAccessibleUserIds` = direct children + users in managed provinces. So General is both “coordinator-level” and “multi-province provincial.”  
   - **Ambiguity:** For **project create** (when done by whom?): If executor/applicant creates project, society dropdown is on executor’s form. If General ever creates a project on behalf of someone, business must confirm: all societies vs societies from a chosen province vs societies from managed provinces only.

2. **Coordinator — society dropdown**  
   - Coordinator currently has no society dropdown in project list or project create (executor/applicant create projects). If coordinator gets a “create project for user” or “filter by society” later, confirm: show all societies or filter by province first.

3. **Global societies (province_id NULL)**  
   - SeedCanonicalSocietiesCommand and docs reference “global” societies. Not all views distinguish them. For dropdowns: should “global” societies appear for every province, or only for General/Coordinator, or for Executor/Applicant as well? Needs business rule.

4. **ProvincialController middleware `role:provincial,general`**  
   - General can use provincial URLs (dashboard, project list, report list, etc.). When General is on that path, `getAccessibleUserIds` uses managed provinces (and session filter). So for society dropdown on **provincial** views (e.g. edit provincial user), if the logged-in user is General, which provinces’ societies to show is defined (managed provinces); for society dropdown on **project** create (executor flow), the creator is executor/applicant — so no ambiguity: scope by executor’s province.

5. **Project create route**  
   - Project create is under executor/applicant routes; so the “user” on the form is executor or applicant. Society dropdown there should be scoped to that user’s province (and optionally global). No ambiguity for provincial role.

---

## 6. Recommendation Before Phase 5B1

- **Provincial (role = provincial):**  
  - **Society dropdown:** Only societies where `province_id = auth()->user()->province_id` and `is_active = true`.  
  - **Backend:** Validate that any submitted `society_id` belongs to that province.

- **Executor / Applicant (project create/edit):**  
  - **Society dropdown:** Societies where `province_id = auth()->user()->province_id` OR `province_id IS NULL` (global), and `is_active = true`.  
  - **Backend:** Validate that submitted `society_id` is either in user’s province or global.

- **General (project create/edit — if form is ever used by General):**  
  - **Society dropdown:** All active societies (or grouped by province for UX). If product restricts “managed provinces only,” then societies from `auth()->user()->managedProvinces()` + global.  
  - **General (provincial/executor user create/edit):** Already province-driven (select province then society). Use society_id; options = societies for selected province (from managed provinces when General) + global if allowed.

- **Coordinator:**  
  - No society dropdown in current flows. If one is added later: all active societies, or societies from provinces that have projects/users.

- **Admin:**  
  - No society dropdown in current flows.

**Implementation order for Phase 5B1:**  
1. Replace all society dropdowns with `society_id` and relational options.  
2. For **provincial** views (provincial/edit, executor create/edit under provincial): pass `Society::where('province_id', $province->id)->where('is_active', true)` (or equivalent); accept only `society_id` in that set.  
3. For **executor/applicant** project create/edit: pass societies for `user->province_id` + global; validate `society_id` accordingly.  
4. For **general** (project form if used by general, or “all projects” context): pass all active societies (or from managed provinces only if product rule is added).  
5. Ensure backend always validates `society_id` against allowed set per role/context to prevent cross-province assignment.

---

## 7. Summary Table (Quick Reference)

| Role        | View All Projects | Province Scope | Society Dropdown Scope (recommended) |
|------------|-------------------|----------------|--------------------------------------|
| admin      | Yes               | All            | N/A |
| coordinator| Yes               | All            | All (if ever added) |
| general    | Yes (coordinator + direct + managed provinces on provincial routes) | Multi (pivot) | All (or managed) for project; province + global for user forms |
| provincial | No (accessible users only) | Own (province_id) | Province only |
| executor   | No (own + in-charge) | Own (for filter) | Province + global |
| applicant  | No (own + in-charge) | Own (for filter) | Province + global |

**General clarified:** General is a **hybrid**: (1) **Coordinator with extended access** — has all coordinator routes and can approve/revert as coordinator; (2) **Multi-province provincial** — can use provincial routes and see data for managed provinces (pivot) plus direct team; (3) **Not** “single provincial” — no single province_id; (4) **Not** “super-provincial” in the sense of “one province with extra power” — rather, “multiple provinces via pivot + direct team.”

---

**Confirmation:** No code or schema was modified; this document is analysis and recommendation only.
