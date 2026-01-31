# Phase 4: Admin UI and Routes — Audit and Specification

**Document type:** Phase 4 deliverable (UI & routes only; no business logic changes)  
**Authority:** Principal Full-Stack Architect & Access-Control Steward  
**Reference:** ADMIN_STEWARD_CONSTITUTION.md (binding), PHASE_WISE_ADMIN_STEWARD_IMPLEMENTATION_PLAN.md  
**Scope:** Visibility, safety, correctness — not new write power

---

## 1. Scope and Non-Goals (Phase 4)

### 1.1 In Scope (Phase 4)

- **Admin UI inventory:** Audit existing admin-accessible views; identify missing UI for project listing, budget visibility (read-only), approval state, resolver/discrepancy visibility, user/role visibility.
- **Route and middleware audit:** Enumerate admin-accessible routes; verify middleware consistency; ensure admin routes are explicit, not piggybacking, and audit-loggable where applicable.
- **Impersonation UI (readiness only):** Design UI entry points for “Act as Executor/Coordinator/Provincial” and “Exit impersonation”; banner when impersonating; no implementation of impersonation logic.
- **Feature flag enforcement:** Every admin write-capable UI element behind a feature flag, default OFF; read-only UI when flag OFF.
- **Access boundaries:** Admin UI must respect read-only default, explicit escalation, no silent mutations.

### 1.2 Explicit Non-Goals (Phase 4)

- **No business logic changes:** No changes to resolver behaviour, approval flows, ProjectStatusService, ReportStatusService, or permission helpers.
- **No budget logic changes:** No changes to BudgetSyncGuard, AdminCorrectionService, or edit locks.
- **No impersonation implementation:** No session/middleware/controller logic for impersonation; only UI design and placeholders.
- **Do not enable any feature flags:** Document requirements only; do not set `BUDGET_ADMIN_RECONCILIATION_ENABLED` or any new flag to true.
- **No new write capabilities:** No approval, budget mutation, or status mutation UI unless already documented and flagged.

---

## 2. Current State Analysis

### 2.1 Admin Routes (Enumerated)

All routes below are under `middleware(['auth', 'role:admin'])` unless noted.

| Route name | Method | Path | Controller / behaviour | Notes |
|------------|--------|------|------------------------|-------|
| `admin.dashboard` | GET | `/admin/dashboard` | AdminController::adminDashboard | Returns `admin.dashboard` view. |
| `admin.logout` | GET | `/admin/logout` | AdminController::adminLogout | Logout and redirect to login. |
| `admin.budget-reconciliation.index` | GET | `/admin/budget-reconciliation` | BudgetReconciliationController::index | Gated by `config('budget.admin_reconciliation_enabled')`; 403 if off. |
| `admin.budget-reconciliation.log` | GET | `/admin/budget-reconciliation/log` | BudgetReconciliationController::correctionLog | Same gate. |
| `admin.budget-reconciliation.show` | GET | `/admin/budget-reconciliation/{id}` | BudgetReconciliationController::show | Same gate. |
| `admin.budget-reconciliation.accept` | POST | `/admin/budget-reconciliation/{id}/accept` | BudgetReconciliationController::acceptSuggested | Write; same gate. |
| `admin.budget-reconciliation.manual` | POST | `/admin/budget-reconciliation/{id}/manual` | BudgetReconciliationController::manualCorrection | Write; same gate. |
| `admin.budget-reconciliation.reject` | POST | `/admin/budget-reconciliation/{id}/reject` | BudgetReconciliationController::reject | Write; same gate. |
| (catch-all) | ANY | `/admin/{all}` | Closure: returns `view('admin.dashboard')` | **Problem:** Any unmatched `/admin/*` shows dashboard; can mask 404s. |

**Routes admin does NOT have (middleware excludes admin):**

- `activities.all-activities`: under `role:coordinator,general` only → **admin gets 403** when clicking “All Activities” in sidebar (controller allows admin; route does not).
- `projects.list`, `projects.downloadPdf`, `projects.downloadDoc`, `projects.activity-history`, `reports.activity-history`, monthly report show/download, aggregated report index/show, etc.: under `role:executor,applicant,provincial,coordinator,general` → **admin not in list** → 403 for all of these.
- Coordinator/provincial/executor/general dashboards and project/report lists: each under its role middleware → admin has no dedicated read-only route to project list, report list, or project/report show.

### 2.2 Admin Views (Inventory)

| View | Layout | Sidebar visible? | Purpose |
|------|--------|-------------------|---------|
| `admin.dashboard` | `layoutAll.app` | **No** | Dashboard; layoutAll has no sidebar. |
| `admin.budget_reconciliation.index` | `layoutAll.app` | **No** | List approved projects with discrepancy comparison. |
| `admin.budget_reconciliation.show` | (assumed layoutAll or similar) | **No** | Single project reconciliation detail. |
| `admin.budget_reconciliation.correction_log` | (assumed) | **No** | Correction log entries. |
| `admin.sidebar` | N/A (partial) | — | Sidebar fragment; only included when layout includes it (e.g. `profileAll.app`). |

**Finding:** Admin dashboard and budget-reconciliation views extend `layoutAll.app`, which does **not** include any sidebar. Coordinator/General/Provincial/Executor dashboards use full HTML documents that `@include` their role sidebar. So **admin never sees the admin sidebar** on dashboard or budget-reconciliation pages unless they visit a page that uses `profileAll.app` (e.g. profile). This is a **layout/sidebar visibility bug**.

### 2.3 Admin Sidebar Content (Current)

- **Dashboard** — links to `admin.dashboard` ✅
- **All Activities** — links to `route('activities.all-activities')` → **403** for admin (route is coordinator,general only) ❌
- **Governance (if flag on):** Budget Reconciliation → `admin.budget-reconciliation.index` ✅; **Correction Log** not as standalone sidebar item (only linked from reconciliation index page) — acceptable but could add direct “Correction Log” in sidebar.
- **Placeholder / broken:** “Email” (Inbox, Read, Compose) → static HTML `pages/email/*.html` (not app routes) ❌
- **Placeholder / broken:** “Calendar” → `pages/apps/calendar.html` ❌
- **Reports:** Quarterly / Half-Yearly / Annual → `aggregated.quarterly.index`, `aggregated.half-yearly.index`, `aggregated.annual.index` → these routes are under `role:executor,applicant,provincial,coordinator,general` → **admin gets 403** ❌
- **Project Application:** Individual / Group / Other → static `pages/general/*.html`, `pages/auth/*.html`, `pages/error/*.html` — not app routes ❌
- **Docs:** “Documentation” → `#` ❌

### 2.4 Middleware Consistency

- **Role middleware:** `app/Http/Middleware/Role.php` checks `$user->role` against allowed list; admin is only allowed where `role:admin` is used. No “admin added to other role groups” in `web.php` for non–read-only routes — **constitution-compliant**.
- **Budget reconciliation:** Controller enforces `role === 'admin'` and `config('budget.admin_reconciliation_enabled')`; middleware is `auth, role:admin`. **Consistent.**

### 2.5 Feature Flags (Current)

- **Budget reconciliation:** `config('budget.admin_reconciliation_enabled')` (env: `BUDGET_ADMIN_RECONCILIATION_ENABLED`), default `false`. Sidebar “Budget Reconciliation” and “Governance” block shown only when true. Controller returns 403 when false. **Correct.**
- **Impersonation:** No flag or UI yet (readiness only in this phase).

---

## 3. Gaps Identified

### 3.1 UI / Layout Gaps

1. **Admin sidebar not shown on admin pages:** Dashboard and budget-reconciliation views use `layoutAll.app`, which has no sidebar. Admin users do not see the admin sidebar on these pages. **Fix:** Use a layout that includes the admin sidebar for all admin views (e.g. admin-specific layout with `@include('admin.sidebar')` and same header/footer as layoutAll), or add conditional sidebar to layoutAll when `auth()->user()->role === 'admin'`.
2. **All Activities returns 403:** Sidebar links to `activities.all-activities`, but route is under `role:coordinator,general`. Controller already allows admin. **Fix:** Add an admin-only route (e.g. `GET /admin/activities/all`) that points to the same `ActivityHistoryController::allActivities` method, so admin can view all activities without being added to coordinator middleware.
3. **No admin project list or project show UI:** Admin has no dedicated read-only route to list all projects or to view a single project (read-only). Constitution grants admin “superset visibility.” **Gap:** Need admin routes (e.g. `/admin/projects`, `/admin/projects/{id}`) that render read-only list and show reusing existing logic (or same controller methods with admin allowed).
4. **No admin report list or report show UI:** Same for reports — no read-only list or show for admin.
5. **Aggregated reports links 403:** Sidebar “Quarterly / Half-Yearly / Annual Reports” point to routes that exclude admin. Either add admin read-only routes for these or remove/hide these sidebar items for admin until such routes exist.
6. **Placeholder and broken links:** Email, Calendar, Individual/Group/Other (project application), Documentation — point to static HTML or `#`. These should be removed or replaced with “Coming later” / hidden so admin does not rely on broken links.

### 3.2 Route Gaps

1. **All Activities:** Add `GET /admin/activities/all` → `ActivityHistoryController::allActivities` (same method), middleware `auth, role:admin`.
2. **Project list (read-only):** Add `GET /admin/projects` → coordinator/project list style, read-only (reuse CoordinatorController::projectList or equivalent with read-only contract).
3. **Project show (read-only):** Add `GET /admin/projects/{project_id}` → read-only project show (reuse existing show view/logic).
4. **Report list (read-only):** Add `GET /admin/reports` (and optionally pending/approved) → read-only report list.
5. **Report show (read-only):** Add `GET /admin/reports/monthly/{report_id}` (and equivalent for other report types if needed) → read-only report show.
6. **Correction Log direct link:** Already have `admin.budget-reconciliation.log`; add “Correction Log” as a direct sidebar item (in Governance section when flag on) for clarity.
7. **Catch-all `/admin/{all}`:** Remove or narrow so that unknown `/admin/*` return 404 instead of dashboard. Prefer explicit routes only.

### 3.3 Impersonation UI (Readiness Only)

- **Entry points to design (no implementation):**
  - “Act as Executor” — future: list/select executor user → start impersonation → redirect to executor dashboard.
  - “Act as Coordinator” — same pattern.
  - “Act as Provincial” — same pattern.
  - “Act as General” / “Act as Applicant” — same pattern if required.
- **Exit:** “Exit impersonation” button/link in a persistent banner (and optionally in sidebar/header).
- **Banner (when impersonating):** Persistent, unambiguous: “Acting as: [Name] ([Role]). Logged in as Admin: [Admin Name]. [Exit impersonation].” Distinct style (e.g. warning/info).
- **Feature flag:** Impersonation UI and logic must be gated (e.g. `ADMIN_IMPERSONATION_ENABLED`), default OFF. Phase 4 only documents this; no flag implementation required.
- **No default activation:** Impersonation must be explicitly started by user action.

### 3.4 Feature Flag Requirements

- **Existing:** Budget reconciliation already behind `budget.admin_reconciliation_enabled`; default OFF. Keep.
- **Future admin write UI:** Every admin write-capable element (e.g. accept/manual/reject in reconciliation, future config toggles) must be behind a feature flag, default OFF. When flag OFF, show read-only or disabled UI.
- **Impersonation:** When implemented, behind e.g. `ADMIN_IMPERSONATION_ENABLED`, default OFF; UI hidden when OFF.

### 3.5 Access Boundaries (Documentation)

- **Read-only default:** Admin project/report/activity views added in this phase must be **read-only** (no approve, revert, submit, edit, delete).
- **No silent mutations:** No UI that allows approval, budget mutation, or status mutation unless it is an explicit, documented, audited flow (e.g. budget reconciliation with flag on).
- **Explicit escalation:** Any write action in admin UI must be behind a feature flag and clearly labeled (e.g. “Budget reconciliation is enabled; actions are audited”).

---

## 4. UI Components to Add or Fix

### 4.1 Fix (No New Logic)

| Item | Action |
|------|--------|
| Admin layout | Ensure all admin views (dashboard, budget-reconciliation/*) use a layout that includes `admin.sidebar` (e.g. new `admin.layout.app` or conditional in layoutAll). |
| Sidebar: All Activities | Point “All Activities” to new admin route `admin.activities.all` (or keep name and add route) so admin does not get 403. |
| Sidebar: Correction Log | Add explicit “Correction Log” link in Governance section (when flag on) to `admin.budget-reconciliation.log`. |
| Sidebar: Placeholders | Remove or hide “Email,” “Calendar,” “Individual/Group/Other” (project application), “Documentation” links that point to static HTML or `#`; or replace with disabled “Coming later” and hide from production. |
| Sidebar: Aggregated reports | Either (a) add admin read-only routes for aggregated quarterly/half-yearly/annual index and point sidebar to them, or (b) hide these sidebar items for admin until routes exist. |
| Catch-all route | Remove or restrict `Route::any('admin/{all}')` so only explicit admin routes are valid; return 404 for unknown `/admin/*`. |

### 4.2 Add (Read-Only Only)

| Component | Description |
|-----------|-------------|
| Admin project list | Page listing all projects (read-only), with link to project show. Reuse existing list view/controller logic; ensure no approve/revert/edit actions. |
| Admin project show | Read-only project detail (general info, approval state, budget visibility). No edit/submit/approve buttons. |
| Admin report list | Read-only report list (e.g. monthly); link to report show. |
| Admin report show | Read-only report detail. No submit/forward/approve/revert. |
| Impersonation entry (design) | Placeholder or disabled “Impersonate user” section in sidebar/dashboard: “Act as Executor / Coordinator / Provincial” (no logic; gated by future flag). |
| Impersonation banner (design) | Partial/blade component for banner: “Acting as: … Logged in as Admin: … Exit impersonation.” Shown only when `is_impersonating` is true (variable not set in Phase 4). |
| Impersonation exit (design) | Button/link “Exit impersonation” in banner (and optionally in header/sidebar). No implementation. |

---

## 5. Routes to Add, Fix, or Restrict

### 5.1 Add (Admin-Only, Read-Only Where Noted)

| Method | Path | Name | Controller / action | Notes |
|--------|------|------|--------------------|-------|
| GET | `/admin/activities/all` | `admin.activities.all` | ActivityHistoryController::allActivities | Same method as coordinator; read-only. |
| GET | `/admin/projects` | `admin.projects.index` | TBD: e.g. AdminController or CoordinatorController::projectList (read-only contract) | Read-only list. |
| GET | `/admin/projects/{project_id}` | `admin.projects.show` | TBD: reuse show logic, read-only | Read-only show. |
| GET | `/admin/reports` | `admin.reports.index` | TBD: read-only report list | Read-only list. |
| GET | `/admin/reports/monthly/{report_id}` | `admin.reports.monthly.show` | TBD: reuse monthly report show, read-only | Read-only show. |

(Additional report types can be added similarly when needed.)

### 5.2 Fix

| Item | Action |
|------|--------|
| All Activities | Add admin route above; sidebar already links to `activities.all-activities` — change sidebar link to `admin.activities.all` for admin so one route serves admin. |
| Catch-all | Remove `Route::any('admin/{all}')` or replace with explicit 404 fallback for unknown `/admin/*`. |

### 5.3 Restrict

- No relaxation of middleware: do **not** add `admin` to `role:coordinator,general` or other role groups for non–read-only routes. Admin-only routes must use `role:admin` and, when reusing controller methods, pass only read-only behaviour or existing audited flows.

---

## 6. Feature Flag Requirements (Summary)

- **Budget reconciliation:** Already gated by `budget.admin_reconciliation_enabled`; default OFF. Keep; do not enable in Phase 4.
- **Admin write-capable UI:** Every such element must be behind a feature flag, default OFF. When OFF, show read-only or disabled UI.
- **Impersonation (future):** UI and logic behind e.g. `ADMIN_IMPERSONATION_ENABLED`, default OFF. Phase 4 only documents; no implementation.

---

## 7. Explicit Non-Goals for Phase 4 (Recap)

- Do **not** implement impersonation logic (session, middleware, start/stop).
- Do **not** change resolver, approval, or budget guard behaviour.
- Do **not** add admin to role middleware for coordinator/general/provincial/executor/applicant routes.
- Do **not** enable any feature flags.
- Do **not** add approval, budget mutation, or status mutation UI except what already exists (budget reconciliation) and is already flagged.

---

## 8. Phase 4 Implementation Summary (Post-Implementation)

### 8.1 What Was Implemented

- **Admin layout and sidebar:** Created `resources/views/admin/layout.blade.php` that includes `admin.sidebar` and yields content. All admin views (dashboard, budget_reconciliation/*) now extend `admin.layout` so the admin sidebar is always visible on admin routes.
- **Sidebar link cleanup:** Removed placeholder/broken links (Email, Calendar, Quarterly/Half-Yearly/Annual reports, Individual/Group/Other, Documentation). Sidebar now contains only: Dashboard, All Activities, Projects, Reports; Governance (Budget Reconciliation, Correction Log) when `budget.admin_reconciliation_enabled` is true; Impersonation placeholders when `admin.impersonation_enabled` is true.
- **All Activities for admin:** Added route `GET /admin/activities/all` → `ActivityHistoryController::allActivities` (name: `admin.activities.all`). Sidebar “All Activities” links to this route. View `activity-history.all-activities` uses `admin.layout` when user is admin and form/clear use `admin.activities.all` when admin.
- **Catch-all removed:** Removed `Route::any('admin/{all}')` that returned dashboard. Unknown `/admin/*` URLs now return 404.
- **Read-only admin project views:** Added `GET /admin/projects` → `AdminReadOnlyController::projectIndex` (name: `admin.projects.index`), `GET /admin/projects/{project_id}` → `AdminReadOnlyController::projectShow` (name: `admin.projects.show`). Project list and show are read-only (no edit/submit/approve). Project show reuses `ProjectController::show`; view uses `admin.layout` when user is admin; “Back to Projects” and Download PDF hidden for admin where appropriate.
- **Read-only admin report views:** Added `GET /admin/reports` → `AdminReadOnlyController::reportIndex` (name: `admin.reports.index`), `GET /admin/reports/monthly/{report_id}` → `AdminReadOnlyController::reportShow` (name: `admin.reports.monthly.show`). Report list and show are read-only. Report show reuses `ReportController::show`; view uses `admin.layout` when user is admin; “Back to Reports” points to `admin.reports.index` for admin; Download PDF hidden for admin.
- **Impersonation UI placeholders:** Added `config/admin.php` with `impersonation_enabled` (env: `ADMIN_IMPERSONATION_ENABLED`, default false). Sidebar shows “Act as Executor”, “Act as Coordinator”, “Act as Provincial”, “Exit Impersonation” (all disabled / “Coming later”) only when `admin.impersonation_enabled` is true. Created `admin.partials.impersonation-banner` (shown when impersonation session exists; no backend yet). No session changes, no effective-user logic.
- **Config:** `config/admin.php` added; no feature flags were enabled.

### 8.2 What Remains Intentionally Unimplemented

- **Impersonation backend:** No session/middleware/controller logic for start/stop impersonation; no effective-user resolution.
- **Admin added to role middleware:** Admin was not added to `role:coordinator`, `role:general`, or any other role group; admin reaches list/show via dedicated admin routes only.
- **Resolver, approval, budget logic:** No changes to ProjectStatusService, ReportStatusService, BudgetSyncGuard, AdminCorrectionService, or permission helpers.
- **Feature flags:** `BUDGET_ADMIN_RECONCILIATION_ENABLED` and `ADMIN_IMPERSONATION_ENABLED` remain default false; none were enabled.

### 8.3 Constitutional Compliance

- **I11 (role middleware):** Admin routes use `role:admin` only; admin was not added to coordinator/general/provincial/executor/applicant middleware.
- **Read-only default:** Admin project and report list/show provide visibility only; no approve, revert, submit, edit, or budget mutation from these views.
- **Explicit escalation:** Budget reconciliation (write) remains behind `budget.admin_reconciliation_enabled`; impersonation UI behind `admin.impersonation_enabled`.
- **No silent mutations:** No new write paths; no “if admin then allow” in permission or status logic.

---

**Document version:** 1.1  
**Phase:** 4 (UI & routes only) — implementation complete  
**End of Phase 4 Admin UI and Routes.**
