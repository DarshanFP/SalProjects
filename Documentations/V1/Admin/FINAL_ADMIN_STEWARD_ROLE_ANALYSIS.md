# Admin Steward Role – Final Analysis & Design

**Document type:** Authoritative design and analysis  
**Role:** Principal System & Security Architect  
**Date:** 2026-01-29  
**Application:** SALProjects (Laravel, mission-critical, LIVE)  
**Classification:** Admin is a SYSTEM STEWARD, not a superuser.

---

## 1. Executive Summary

### 1.1 Purpose

This document defines the **Admin Steward** role for SALProjects: a controlled, auditable, non-superuser pattern with **superset visibility**, **controlled superset access**, **explicit impersonation**, **last-resort correction**, **system configuration**, and **audit/diagnostics** authority. Admin identity must never be lost; every action must be attributable to the admin and, when applicable, to the impersonated user and effective role.

### 1.2 Core Principle

**Admin is NOT a superuser.** Admin is a **steward** who:

- Sees more (visibility superset) and can act in bounded ways (access superset) under clear rules.
- Uses **impersonation** to act _as_ another role when supporting users or diagnosing workflows—never by silently bypassing guards.
- Uses **last-resort correction** only through dedicated, audited flows (e.g. budget reconciliation), not by editing any entity without trace.
- Does not weaken approval chains, budget governance, or existing role guards.

### 1.3 Non-Negotiable Requirement

Admin **MUST** be able to **impersonate** these roles: **executor**, **coordinator**, **provincial**, **applicant**, **general**. Impersonation MUST be:

- **Explicit** (user-initiated, reversible action).
- **Reversible** (one-click exit).
- **Visible in UI** (persistent banner when active).
- **Fully auditable** (admin_user_id, impersonated_user_id, effective_role, original_role=admin on every logged action).

### 1.4 Current State (Findings)

- **Auth & sessions:** Laravel `web` guard, session-based; single `User` model with `role` (admin, general, coordinator, provincial, executor, applicant). No impersonation or “effective user” concept.
- **Role enforcement:** `Role` middleware checks `$user->role` against route-allowed roles; admin has dedicated routes plus catch-all; other role routes do **not** allow admin (e.g. `activities.all-activities` is coordinator,general only → admin gets 403).
- **Visibility:** Admin can view all projects (ProjectPermissionHelper::canView). Admin cannot edit/submit/approve/revert via normal flows (no admin override in canEdit, canSubmit; ProjectStatusService/ReportStatusService do not accept admin).
- **Last-resort correction:** Implemented only for **budget reconciliation** (Phase 6): admin-only, feature-flagged, explicit accept/manual/reject, full audit in `budget_correction_audit` (admin_user_id, action_type, old/new values, comment, ip).
- **Audit:** ActivityHistory stores `changed_by_user_id`, `changed_by_user_role`, `changed_by_user_name` (no admin vs impersonated distinction). BudgetCorrectionAudit stores `admin_user_id` and `user_role`. No impersonation audit schema yet.

### 1.5 Recommendations at a Glance

| Area          | Recommendation                                                                                                                                                                              |
| ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Impersonation | Introduce session-based “impersonation context” (impersonated_user_id, effective_role), middleware to resolve “effective user” for permission/logging, and explicit start/stop routes + UI. |
| Audit         | Extend ActivityHistory (or add audit table) with admin_user_id, impersonated_user_id, effective_role, original_role; require all services to pass “acting user” for logging.                |
| Visibility    | Keep and formalize: admin sees all projects/reports/activities (with route access fixed where needed).                                                                                      |
| Access        | Grant access via impersonation only (admin chooses a user and acts as that user’s role), not by adding admin to every role middleware.                                                      |
| Correction    | Keep budget reconciliation as the pattern: dedicated admin-only flows, feature flags, immutable audit. Do not add silent overrides in project/report edit paths.                            |
| UI            | Admin sidebar in layout; impersonation switcher; persistent “Acting as: X (Admin: Y)” banner when impersonating; safety warnings on destructive actions.                                    |

---

## 2. Admin Steward Definition

### 2.1 Definition

**Admin Steward** is the role responsible for:

1. **System stewardship** – Oversight, diagnostics, and safe intervention within defined boundaries.
2. **User support** – Reproducing issues and performing actions on behalf of users via **impersonation** (explicit and auditable).
3. **Last-resort correction** – Correcting data only through dedicated, audited flows (e.g. budget reconciliation), with no silent overrides in normal edit/approval paths.
4. **Configuration and diagnostics** – Feature flags, config visibility, logs, and audit views (within policy).

Admin is **not**:

- A superuser who bypasses approval or budget rules.
- A role that can silently edit any project/report without going through impersonation or dedicated correction flows.
- Allowed to hide their identity in audit trails.

### 2.2 Authority Bounds

| Authority                     | In scope                                                                                                         | Out of scope                                                                                                                              |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| **Visibility**                | All projects, reports, users, activities, aggregated reports, budget reconciliation list, correction log.        | N/A (superset).                                                                                                                           |
| **Access (normal workflows)** | Only via **impersonation**: choose a user, act as that user’s role; subject to same business rules as that role. | No “admin override” that skips status/ownership checks in ProjectController, ReportController, ProjectStatusService, ReportStatusService. |
| **Last-resort correction**    | Budget reconciliation (accept/manual/reject) for approved projects only; feature-flagged; immutable audit.       | No ad-hoc edit of approved project/report outside this (or another future dedicated, audited flow).                                       |
| **Configuration**             | Read (and optionally limited write) of feature flags / config used by the app (e.g. budget.\*).                  | No arbitrary code or DB access; no removal of approval/budget guards.                                                                     |
| **Audit & diagnostics**       | View activity history, correction log, logs (as implemented).                                                    | No deletion or alteration of audit records.                                                                                               |

### 2.3 What Must NOT Change

- **Approval chain:** Coordinator/provincial/general approval and revert logic must remain; admin does not approve “as admin” outside impersonation.
- **Budget governance:** BudgetSyncGuard, Phase 3 edit lock, resolver behaviour unchanged; only AdminCorrectionService (and similar future flows) may bypass for approved projects, with full audit.
- **Role middleware:** Existing route groups (coordinator, general, provincial, executor, applicant) stay as-is; admin reaches those flows **only** by impersonating a user in that role.
- **Silent overrides:** No “if (user->role === 'admin') return true” in canEdit/canSubmit/canDelete without going through impersonation or a dedicated, audited admin flow.

---

## 3. Role & Authority Matrix

### 3.1 User Roles (Current)

| Role            | Purpose                                                                                                        | Key routes / middleware                                                               |
| --------------- | -------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| **admin**       | System steward (this document).                                                                                | `role:admin` → /admin/dashboard, /admin/budget-reconciliation*, catch-all /admin/*.   |
| **general**     | Top operational role: coordinator + province/center management, dual context (as coordinator / as provincial). | `role:general` → /general/\*; also `role:coordinator,general` for coordinator routes. |
| **coordinator** | Final approval; manages provincials.                                                                           | `role:coordinator,general` → /coordinator/\*.                                         |
| **provincial**  | Forwards to coordinator; manages executors/applicants, centers.                                                | `role:provincial` → /provincial/\*.                                                   |
| **executor**    | Creates projects, submits reports.                                                                             | `role:executor,applicant` → /executor/_, reports/monthly/_, etc.                      |
| **applicant**   | Same as executor for app flows.                                                                                | Same as executor.                                                                     |

### 3.2 Admin vs Other Roles

| Capability                                | Other roles                                              | Admin (steward)                                                                      |
| ----------------------------------------- | -------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| View all projects                         | Coordinator, provincial (and general via coordinator).   | Yes (ProjectPermissionHelper::canView).                                              |
| View all activities                       | Coordinator, general (route: activities.all-activities). | Controller allows admin; **route middleware does not** → 403 today; should be fixed. |
| Edit project (draft/reverted)             | Owner/in-charge, status must be editable.                | Only via **impersonation** as executor/applicant (or future dedicated flow).         |
| Submit project                            | Executor/applicant, ownership + submittable status.      | Only via **impersonation** as executor/applicant.                                    |
| Forward / approve / revert                | Provincial, coordinator, general (by role and status).   | Only via **impersonation** as provincial/coordinator/general.                        |
| Budget reconciliation (approved projects) | No.                                                      | Yes (admin-only, feature-flagged, audited).                                          |
| Feature flags / config                    | No.                                                      | Read (and optionally limited write) in admin UI.                                     |

### 3.3 Visibility Matrix (Who Sees What)

| Entity                | Executor/Applicant | Provincial | Coordinator | General | Admin                  |
| --------------------- | ------------------ | ---------- | ----------- | ------- | ---------------------- |
| Own projects          | Yes                | —          | —           | —       | Yes (all)              |
| Team projects         | —                  | Yes        | Yes         | Yes     | Yes (all)              |
| All projects          | —                  | —          | Yes         | Yes     | Yes                    |
| All reports           | —                  | Team       | Yes         | Yes     | Yes (all)              |
| All activities        | —                  | Team       | All         | All     | All (once route fixed) |
| Budget reconciliation | —                  | —          | —           | —       | Yes (if flag on)       |
| Correction log        | —                  | —          | —           | —       | Yes                    |

---

## 4. Impersonation Model

### 4.1 Goals

- Admin can **act as** executor, applicant, provincial, coordinator, or general by selecting a **real user** of that role.
- The system uses the **impersonated user** for ownership, hierarchy, and role checks during the session; the **admin** remains stored for audit.
- No silent override: business rules (status, ownership, etc.) apply as for the impersonated user.
- Entry and exit are explicit and reversible; UI always shows that impersonation is active.

### 4.2 Data Model (Session)

Store in session (e.g. `session('impersonation')`):

```php
[
    'admin_user_id'    => (int) Auth::id(),      // never lost
    'impersonated_user_id' => (int),
    'impersonated_user_name' => (string),
    'effective_role'   => (string) 'executor'|'applicant'|'provincial'|'coordinator'|'general',
    'started_at'       => (datetime),
]
```

- When impersonation is **off**: `session()->has('impersonation') === false`; `effectiveUser()` = Auth::user(); `effectiveRole()` = Auth::user()->role.
- When impersonation is **on**: `effectiveUser()` = User::find(session('impersonation.impersonated_user_id')); `effectiveRole()` = session('impersonation.effective_role'); **original user** remains Auth::user() (admin).

### 4.3 Resolving “Effective” Identity

- **Middleware (e.g. ResolveEffectiveUser):** Runs after auth. If session has impersonation and Auth::user()->role === 'admin', set app bindings / request attributes: `effective_user`, `effective_role`, `is_impersonating` = true. Controllers and services use `effective_user` for permission and for “who did this” in logs.
- **Route access:** When impersonating, admin must hit routes that the **effective_role** is allowed to use. Options: (a) Add a separate admin-only route group that accepts admin and, when impersonation is active, runs the same controller actions as the target role (recommended to avoid changing existing middleware), or (b) Extend role middleware to allow admin when `effective_role` is in the allowed list and impersonation is on. Option (a) keeps existing role middleware unchanged and is safer.

### 4.4 Entry (Start Impersonation)

- **Route:** e.g. `POST /admin/impersonate/start` (admin only).
- **Input:** `user_id` (target user to impersonate). Validate: target exists, target.role is one of executor, applicant, provincial, coordinator, general; optionally restrict to non-admin.
- **Action:** Set session('impersonation', [...]); log "admin X started impersonating user Y (role Z)" to audit.
- **Response:** Redirect to the dashboard appropriate for `effective_role` (e.g. /executor/dashboard if executor).

### 4.5 Active Context

- **Banner:** Every page when `is_impersonating` is true must show a persistent banner: e.g. “Acting as: [Name] ([Role]). You are logged in as Admin: [Admin Name]. [Exit impersonation].”
- **Breadcrumb/header:** Show effective user and role; do not show admin identity only in dropdown (keep in banner).
- **Permissions:** All permission checks (ProjectPermissionHelper, ActivityHistoryHelper, etc.) use `effective_user` and `effective_role`, not Auth::user(), when impersonation is on. Services (ProjectStatusService, ReportStatusService) receive `effective_user` so status/role checks apply to the impersonated user.

### 4.6 Exit (Stop Impersonation)

- **Route:** e.g. `POST /admin/impersonate/stop` (admin only).
- **Action:** Log "admin X stopped impersonating user Y"; clear session('impersonation'); redirect to /admin/dashboard.
- **One-click:** Banner “Exit impersonation” must be clearly visible and single-click (no nested confirmation unless policy requires).

### 4.7 Failure Handling

- **Target user deleted/deactivated:** On next request, if impersonated_user_id invalid or user inactive, clear impersonation, redirect to admin dashboard with message “Impersonation ended (user unavailable).”
- **Session expiry:** Normal session expiry clears impersonation; admin re-authenticates as admin.
- **Admin role removed:** If Auth::user()->role is no longer admin, clear impersonation and abort or redirect to login.
- **Audit:** Log impersonation start/stop and any forced stop (e.g. “impersonation_ended_user_unavailable”).

---

## 5. Audit & Logging Model

### 5.1 Principles

- **Admin identity is never lost:** Every log record that represents an action by an admin must include the real admin user (e.g. admin_user_id).
- **Impersonation is explicit in audit:** When the action was performed while impersonating, store impersonated_user_id, effective_role, and original_role = 'admin'.
- **Immutable:** No update/delete of audit rows by the application (already the case for budget_correction_audit).

### 5.2 Current Audit Surfaces

| Surface                       | Table / model           | Identifies actor                                               | Impersonation-aware   |
| ----------------------------- | ----------------------- | -------------------------------------------------------------- | --------------------- |
| Project/report status changes | activity_histories      | changed_by_user_id, changed_by_user_role, changed_by_user_name | No                    |
| Budget corrections            | budget_correction_audit | admin_user_id, user_role                                       | N/A (admin-only flow) |
| Laravel log                   | logging channel         | Context in log messages                                        | No                    |

### 5.3 Required Additions for Impersonation

- **activity_histories (or dedicated audit table):** Add nullable columns: `admin_user_id`, `impersonated_user_id`, `effective_role`, `original_role`. When the actor is an admin:
    - If not impersonating: `admin_user_id` = admin id, `impersonated_user_id` = null, `effective_role` = 'admin', `original_role` = 'admin'.
    - If impersonating: `admin_user_id` = admin id, `impersonated_user_id` = target user id, `effective_role` = session effective_role, `original_role` = 'admin'.
- **ProjectStatusService / ReportStatusService / ActivityHistoryService:** Accept an optional “acting user” (and admin context) and write both “who did it” (effective) and “admin + impersonation” when present. Controllers called during impersonation pass the effective user and admin context from middleware/request.

### 5.4 Impersonation Lifecycle Audit

- **Table:** e.g. `admin_impersonation_log` or a dedicated section in a general admin_audit table.
- **Columns (conceptual):** id, admin_user_id, impersonated_user_id, effective_role, action (start|stop|forced_stop), reason_or_comment, ip_address, created_at.
- **Usage:** Log on start, stop, and forced stop (e.g. user unavailable).

### 5.5 What to Log (Checklist)

- Project/report status changes: always store changed_by_user_id (effective); when actor is admin, also admin_user_id, impersonated_user_id (nullable), effective_role, original_role.
- Budget reconciliation: already logs admin_user_id, user_role, action_type, old/new values, comment, ip.
- Impersonation: start, stop, forced_stop with admin_user_id, impersonated_user_id, effective_role.
- Configuration changes (if implemented): who (admin_user_id), what (key/value or diff), when.

---

## 6. UI Requirements

### 6.1 Admin Layout and Navigation

- **Sidebar:** Admin uses the same layout pattern as other roles (e.g. include admin sidebar in layout when profileData->role === 'admin'). Sidebar entries: Dashboard, All Activities (fix route), Budget Reconciliation (if flag on), Correction Log, Impersonation (see below), optional Config/Logs.
- **No placeholder links:** Replace static HTML / # with real routes or remove.

### 6.2 Impersonation Switch

- **Entry:** Admin-only “Impersonate user” UI: list or search users (by role filter), select user, confirm “Start impersonating [Name] ([Role])?”. On confirm, POST start and redirect to appropriate dashboard.
- **Exit:** Persistent “Exit impersonation” in banner and optionally in sidebar/header when is_impersonating.
- **Visibility:** Impersonation controls only visible when Auth::user()->role === 'admin'.

### 6.3 Context Banners

- **When impersonating:** Full-width or prominent banner: “Acting as: [Impersonated Name] ([Role]). Logged in as Admin: [Admin Name]. [Exit impersonation].” Use distinct style (e.g. warning/info) so it cannot be missed.
- **When not impersonating:** No need for a special admin banner on every page; optional “You are logged in as Admin” in header.

### 6.4 Safety Warnings

- **Destructive or sensitive actions:** When admin is impersonating and the action is submit/approve/revert/delete (if ever added), show a short line: “This action will be recorded as performed by you (Admin) on behalf of [Impersonated User].”
- **Budget reconciliation:** Already has strong warning (approved project, audit trail). Keep as-is.

### 6.5 Admin Dashboards

- **Main admin dashboard:** Keep /admin/dashboard; can add widgets: link to All Activities, Budget Reconciliation, Impersonation, Correction Log, system health/config summary (read-only or limited write).
- **No need to duplicate** coordinator/provincial/executor dashboards; admin reaches them by impersonating and is redirected to the correct dashboard after start.

---

## 7. Risk Analysis & Guardrails

### 7.1 Risks

| Risk                                    | Mitigation                                                                                                                                                                                                           |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Admin misused as “superuser”            | Strict definition: no silent overrides; access only via impersonation or dedicated audited flows (e.g. budget reconciliation).                                                                                       |
| Impersonation used to evade audit       | Every action during impersonation logs admin_user_id + impersonated_user_id + effective_role + original_role.                                                                                                        |
| Session fixation / impersonation hijack | Start/stop only over HTTPS; regenerate session id on login and optionally on impersonation start/stop.                                                                                                               |
| Leaving impersonation on by mistake     | Prominent banner; optional timeout (e.g. max 2 hours) with warning before auto-exit.                                                                                                                                 |
| Weakening approval/budget rules         | Design rule: do not add admin to role middleware for approval flows; do not add “if admin then allow” in ProjectStatusService/ReportStatusService for normal flows. Use impersonation or dedicated admin flows only. |

### 7.2 Guardrails (Must Preserve)

- **Approval chain:** No change to who can approve/revert by role and status.
- **Budget:** BudgetSyncGuard and Phase 3 behaviour unchanged; only AdminCorrectionService (and future similar flows) bypass with audit.
- **Role middleware:** Existing groups unchanged; admin gets into other role flows only by impersonation (and dedicated admin routes that delegate to same business logic with effective user).
- **Audit:** Append-only; no deletion or alteration of audit rows by app code.

### 7.3 What Must NEVER Be Added

- Silent “if (user->role === 'admin') return true” in canEdit/canSubmit/canDelete without impersonation or a dedicated audited flow.
- Removing or weakening status checks in ProjectStatusService / ReportStatusService for admin outside impersonation.
- Allowing admin to approve/revert projects or reports “as admin” without impersonation or a future explicit “admin override” flow with full audit (if ever required by policy).
- Any path that writes to project/report/budget without recording admin_user_id (and impersonated_user_id when applicable) in an immutable audit trail.

---

## 8. Phased Implementation Plan

### Phase 1: Foundation (Audit & Visibility)

**Goal:** Admin identity and optional impersonation context in audit; fix visibility gaps.

1. **Audit schema**
    - Add to `activity_histories` (or new table): `admin_user_id` (nullable), `impersonated_user_id` (nullable), `effective_role` (nullable), `original_role` (nullable).
    - Migration; backfill existing rows with nulls.
2. **ActivityHistoryService / ProjectStatusService / ReportStatusService**
    - Extend log methods to accept optional admin context (admin_user_id, impersonated_user_id, effective_role, original_role) and persist when provided.
3. **Route fix**
    - Allow admin to access “All Activities”: add route in admin group or add `admin` to `role:coordinator,general` for `activities.all-activities` (prefer admin group to avoid widening coordinator route).
4. **Admin sidebar**
    - Include admin sidebar in layout when user is admin (e.g. in layoutAll.app or dedicated admin layout).

**Deliverables:** Migration, service signature updates, one route change, layout change. No impersonation yet.

**Estimate:** Small (1–2 days).

---

### Phase 2: Impersonation (Session & Middleware)

**Goal:** Admin can start/stop impersonation; effective user and role drive permissions and audit.

1. **Session and helpers**
    - Define session key and structure for impersonation (see §4.2).
    - Helper or service: `effectiveUser()`, `effectiveRole()`, `isImpersonating()` (and optionally `adminUser()` for banner).
2. **Middleware**
    - `ResolveEffectiveUser`: after auth, if admin and session has impersonation, resolve impersonated user, bind `effective_user`, `effective_role`, `is_impersonating` to request/app; validate target user still exists and is active.
    - Register in web stack (after auth).
3. **Routes**
    - POST /admin/impersonate/start (body: user_id).
    - POST /admin/impersonate/stop.
    - Both admin-only, CSRF protected.
4. **Impersonation audit**
    - Create `admin_impersonation_log` (or equivalent); log start, stop, forced_stop (e.g. when target user invalid).
5. **Controllers**
    - Start: validate user_id, set session, log, redirect to role dashboard.
    - Stop: clear session, log, redirect to /admin/dashboard.
    - Forced stop: in middleware, clear session and redirect with message when target user missing/inactive.

**Deliverables:** Session contract, middleware, routes, impersonation log table and logging, start/stop controllers.

**Estimate:** 2–3 days.

---

### Phase 3: Impersonation in Permission and Services

**Goal:** When impersonating, permission checks and status changes use effective user; audit records admin + impersonation.

1. **Permission helpers**
    - ProjectPermissionHelper, ActivityHistoryHelper, etc.: when `isImpersonating()`, use `effectiveUser()` and `effectiveRole()` instead of Auth::user() for checks. Do not add “admin can always do X” without impersonation.
2. **Controllers (project/report)**
    - When impersonation is on, resolve effective user and pass to services; ensure views receive effective user for “your projects” etc. Option: admin routes that mirror executor/coordinator/provincial/general routes and call same controller methods with effective_user injected (so existing role middleware stays unchanged).
3. **Services**
    - ProjectStatusService, ReportStatusService, ActivityHistoryService: accept optional “acting user” and “admin context”; use acting user for role/ownership checks; when writing ActivityHistory, set admin_user_id, impersonated_user_id, effective_role, original_role when provided.
4. **Route access**
    - Implement option (a) from §4.3: admin-only route group that, when impersonation is active, dispatches to the same controller actions as the target role (e.g. executor dashboard, project edit, report submit) with effective_user. This avoids changing existing role middleware.

**Deliverables:** Helper usage in permission layer; service signatures and logging; admin mirror routes or middleware extension; tests for “admin impersonating executor can submit only own/charged projects.”

**Estimate:** 3–5 days.

---

### Phase 4: UI (Banner, Impersonation Switch, Safety)

**Goal:** Visible impersonation state and safe entry/exit.

1. **Banner**
    - When `is_impersonating`: show persistent banner with “Acting as: …”, “Logged in as Admin: …”, “Exit impersonation” link/button.
2. **Impersonation entry**
    - Admin UI: list/search users, filter by role, select user, confirm, POST start.
3. **Exit**
    - One-click “Exit impersonation” in banner (and optionally in sidebar/header).
4. **Safety**
    - On sensitive actions (submit/approve/revert), when impersonating, show one-line notice that action is recorded as admin on behalf of [user].

**Deliverables:** Blade/JS for banner and impersonation UI; wiring to start/stop routes.

**Estimate:** 1–2 days.

---

### Phase 5: Configuration & Diagnostics (Optional)

**Goal:** Admin can view (and optionally change) feature flags and view audit/logs.

1. **Config view**
    - Read-only (or limited write) list of key config (e.g. budget.\* flags). If write is allowed, log changes (admin_user_id, key, old_value, new_value).
2. **Correction log**
    - Already exists; ensure linked from admin sidebar.
3. **Impersonation log**
    - Admin view: filter by admin, by impersonated user, by date; read-only.

**Deliverables:** Config page, impersonation log page, links in sidebar.

**Estimate:** 1–2 days.

---

### Summary Timeline

| Phase | Content                                                       | Estimate |
| ----- | ------------------------------------------------------------- | -------- |
| 1     | Audit schema, service logging, route fix, admin sidebar       | 1–2 days |
| 2     | Session, middleware, start/stop routes, impersonation log     | 2–3 days |
| 3     | Permission + services use effective user; admin mirror routes | 3–5 days |
| 4     | Banner, impersonation UI, safety wording                      | 1–2 days |
| 5     | Config view, impersonation log view (optional)                | 1–2 days |

Total: ~8–14 days for core (Phases 1–4); +1–2 days if Phase 5 is included.

---

## 9. References

- **Existing admin docs:** Documentations/V1/Admin/ADMIN_VIEWS_AND_ROUTES_REVIEW.md, ADMIN_DOCUMENTATION_SUMMARY_AND_STATUS.md.
- **Budget reconciliation:** Documentations/V1/Basic Info fund Mapping Issue/PHASE_6_IMPLEMENTATION_SUMMARY.md; config/budget.php (admin_reconciliation_enabled); App\Services\Budget\AdminCorrectionService; App\Models\BudgetCorrectionAudit.
- **Auth & roles:** config/auth.php; App\Http\Middleware\Role; App\Models\User (role); routes/web.php (role middleware groups).
- **Permission & status:** App\Helpers\ProjectPermissionHelper; App\Services\ProjectStatusService; App\Services\ReportStatusService; App\Services\Budget\BudgetSyncGuard.
- **Audit:** App\Models\ActivityHistory; database migrations activity_histories, budget_correction_audit.

---

**End of document. This file is the single authoritative source for the Admin Steward role design and implementation plan.**
