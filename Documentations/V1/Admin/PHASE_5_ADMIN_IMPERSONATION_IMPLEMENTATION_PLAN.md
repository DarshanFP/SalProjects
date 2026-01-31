# Phase 5: Admin Impersonation — Conceptual Model & Implementation Plan

**Document type:** Design lock (conceptual model + implementation plan; no code, no schema)  
**Authority:** Principal Systems Architect & Security Steward  
**Reference:** ADMIN_STEWARD_CONSTITUTION.md (binding system law)  
**Purpose:** Define a safe, auditable impersonation model for later implementation without violating existing guarantees.

---

# PART 1 — CONCEPTUAL EXPLANATION

This section explains impersonation in plain architectural terms. It is explanatory, not procedural.

---

## 1. Identity Model

### 1.1 Two Identities

The system must distinguish two identities whenever an admin can act on behalf of another user:

- **Real User (authenticated identity):** The person who logged in. In the impersonation scenario, this is always an **admin**. The session, the cookie, and the authentication layer all identify this user. This identity never changes during the request or the session; it is the true actor from a security and accountability standpoint.

- **Effective User (impersonated identity):** The user the admin is “acting as.” This is a **real** user record in the system (executor, applicant, provincial, coordinator, or general). The effective user determines what the current request is *allowed* to do and what data it *sees* in role-based flows. The effective user is not the same as the real user except when impersonation is off.

### 1.2 How Requests Resolve Identity

- When **impersonation is off:** The real user and the effective user are the same. The authenticated user’s id and role are used for both authorization and audit. No special handling is required.

- When **impersonation is on:** The real user remains the authenticated admin. The effective user is the impersonated user, resolved from a stored impersonation context (e.g. in session). Every request that participates in role workflows must resolve “who is acting” in two ways: (1) for **authorization and data access**, use the effective user; (2) for **audit and accountability**, record both the real user (admin) and the effective user (impersonated).

### 1.3 Which Identity Is Used Where

| Concern | Identity used | Rationale |
|--------|----------------|-----------|
| **Authorization** | Effective user | Role and ownership rules must apply as if the impersonated user were logged in. The admin must not gain extra permissions; the effective user’s role and hierarchy determine what can be done. |
| **Data access** | Effective user | Queries (e.g. “my projects”, “team reports”) must be scoped to what the effective user would see. Otherwise the admin would see either too much (admin’s view) or too little (wrong scope). |
| **UI rendering** | Effective user for role-specific content; real user for admin context | Lists, dashboards, and action buttons must reflect the effective user’s role and permissions. At the same time, the UI must always show that the real user is the admin (banner, exit affordance). |
| **Audit logs** | Both | Every state-changing action must record the real user (admin) and, when impersonation is active, the effective user and effective role. This preserves “who actually did it” and “who they were acting as.” |

The critical rule: **authorization and data access use the effective user so that no privilege escalation occurs.** Audit always records the real user (and, when applicable, the effective user) so that no action is ever attributed to a “system” or left ambiguous.

---

## 2. Session & Context Model

### 2.1 Impersonation Within a Session

Impersonation is **state within** an existing session, not a replacement of the session. The admin remains logged in; the session still belongs to the admin. A separate piece of session state (e.g. “impersonation context”) indicates that the admin is currently acting as another user. That context typically holds: who is being impersonated (user id, name, role), when impersonation started, and possibly a reason or ticket reference. The authentication identity (real user) does not change; only the effective identity used for role logic changes.

### 2.2 Start and Stop Behaviour

- **Start:** The admin explicitly chooses a target user (from an admin-only UI) and confirms. The system validates that the target exists, is active, and has an allowed role (executor, applicant, provincial, coordinator, general). It then writes the impersonation context into the session and logs the start event (real user, effective user, action = start). The admin is then redirected into the workflow appropriate for the effective role (e.g. executor dashboard). Start is an explicit, user-initiated action; there is no automatic or implicit start.

- **Stop:** The admin explicitly ends impersonation (e.g. “Exit impersonation” in the banner or sidebar). The system clears the impersonation context from the session, logs the stop event, and redirects the admin back to the admin area (e.g. admin dashboard). Stop must be one-click and always available; the system must not require the admin to “act as” someone else to exit.

### 2.3 Logout, Timeout, and Error

- **Logout:** When the admin logs out, the session is destroyed. Impersonation context is part of the session, so it is destroyed with it. No separate “impersonation session” survives.

- **Timeout:** When the session expires, the next request will require re-authentication. The user will log in again as themselves (admin). Impersonation context does not persist across session expiry; it is not stored server-side outside the session (in this model).

- **Error / invalid target:** If the impersonated user is deleted or deactivated, or if the impersonation context becomes inconsistent, the system must detect this (e.g. on next request or on validation) and clear the impersonation context, redirecting the admin to the admin area with a clear message. This avoids the admin being “stuck” as a user who no longer exists or cannot be resolved.

---

## 3. Authorization Rules

### 3.1 Impersonation Must Not Bypass Role Checks

The constitution states that only roles with explicit authority may forward, approve, or revert, and only when status and ownership rules allow. Impersonation does not create a new authority; it allows the admin to **use the authority of the effective user**. Therefore, every role check, status check, and ownership check must be performed **against the effective user** when impersonation is on. The same middleware and the same business logic that apply to a real executor, provincial, or coordinator apply to the effective user. There is no branch that says “if the real user is admin, allow.” The only change is *which* user is passed into those checks: the effective user instead of the authenticated user when impersonation is active.

### 3.2 Admin Gains No Extra Permissions While Impersonating

The effective user may be an executor with access only to their own projects, or a provincial with access to their team’s projects. The admin, while impersonating that user, has exactly those permissions—no more. The admin cannot see or do anything the effective user could not see or do. This prevents privilege escalation: the admin cannot “impersonate an executor but see all projects,” or “impersonate a provincial but approve as coordinator.” The effective user’s role and hierarchy fully define the permission set.

### 3.3 Existing Middleware and Logic Unchanged

Existing role middleware (e.g. “only coordinator and general”) remains unchanged. The constitution forbids adding admin to those middleware groups so that admin can hit role routes without impersonation. Therefore, when the admin is impersonating, they do **not** hit the coordinator route with an “admin” identity; they hit a **dedicated admin route** (e.g. “admin acting as coordinator”) that (1) verifies impersonation is on and effective role is coordinator, (2) injects the effective user into the request or application context, and (3) delegates to the same controller or logic that the coordinator route uses, passing the effective user. The coordinator middleware is not relaxed; the admin never appears to the middleware as “admin” on a coordinator-only URL. The flow is: admin route → impersonation check → effective user resolution → delegate to same business logic with effective user. Middleware and status services continue to receive a single “acting user” and apply the same rules; they do not need to know whether that user is “real” or “effective.”

---

## 4. Audit & Accountability

### 4.1 What Must Be Logged

Every state-changing action taken while impersonation is active must be recorded with:

- **Real user id (admin):** So that the true actor is never in doubt. This satisfies the constitutional requirement that admin identity is never hidden.

- **Effective user id (impersonated user):** So that it is clear *who* was being acted as—which projects, which reports, which approvals are attributable to that user in the workflow sense.

- **Impersonation reason (if applicable):** Optional or mandatory depending on policy. If the organisation requires a reason or ticket reference when starting impersonation, it should be stored at start time and can be carried in context for the audit record. This helps both the admin (to justify the action) and the system (to deter misuse).

Additional fields that support clarity: effective role, original role (admin), action type, timestamp, and optionally IP or request id.

### 4.2 Why Logs Must Never Be Ambiguous

Ambiguous audit records (e.g. only storing the effective user and not the admin) would (1) violate the constitution (I1, I3), (2) make it impossible to distinguish “user X did Y” from “admin A did Y while acting as X,” and (3) expose the system to disputes or compliance failures. Unambiguous logging protects both the system and the admin: the system can prove who really did what, and the admin can prove they were acting in a supported, audited way rather than as an unrecorded superuser.

### 4.3 Protection of the Admin

When every action during impersonation is tied to both the admin and the effective user, the admin is protected from accusations that they acted without traceability or that they abused privilege. The audit trail shows that they used the designated impersonation mechanism, that they acted only with the effective user’s permissions, and that they did not bypass status or approval rules. This aligns with the “accountable steward” model: the admin is visible and traceable, which is a safeguard for the admin as well as for the organisation.

---

## 5. UI & UX Signals

### 5.1 Why Impersonation Must Be Visible at All Times

If the admin could act as another user without a persistent visual cue, they (or someone looking at the screen) could forget that actions are being performed in the name of the impersonated user and that the real actor is the admin. Constant visibility reduces the risk of mistaken identity, supports compliance (“we always show when an admin is acting as another user”), and satisfies the constitutional requirement that impersonation be visible.

### 5.2 Banner Rules

When impersonation is active, a **persistent banner** (or equivalent) must be present on every page that the admin sees while in the role workflow. The banner must state, in effect: “Acting as: [impersonated user name] ([role]). Logged in as Admin: [admin name].” It must be unambiguous and hard to miss (e.g. full-width, distinct colour). The admin identity must never be hidden; the banner is the primary reminder of who the real user is and who is being acted as.

### 5.3 Exit Affordance

The admin must be able to exit impersonation in one click (e.g. “Exit impersonation” button or link in the banner or in a fixed header/sidebar). The affordance must be available on every page during impersonation so the admin is never “trapped” in the impersonated context. No nested confirmation is required unless policy explicitly demands it.

### 5.4 Read-Only vs Action Clarity

When the admin is impersonating, the UI shows the same screens and actions that the effective user would see (e.g. submit, forward, approve). It must be clear that those actions are performed *as* the effective user and will be audited with both identities. Any wording that reinforces “this action will be recorded as you (Admin) on behalf of [User]” on sensitive actions (e.g. approve, revert) supports clarity and accountability. Read-only visibility (e.g. viewing a project) need not show a special “read-only” label as long as the banner makes it clear that the session is in impersonation mode.

---

# PART 2 — PHASE 5 IMPLEMENTATION PLAN

The following plan is a **design lock** for Phase 5. It does not include code or schema migrations. Implementation must comply with ADMIN_STEWARD_CONSTITUTION.md and with this plan.

---

## 1. Phase Objective & Non-Goals

### 1.1 Objective

Implement **admin impersonation** so that an admin can explicitly “act as” a user with role executor, applicant, provincial, coordinator, or general; perform only the actions that user could perform; and have every such action and every start/stop of impersonation recorded with both real (admin) and effective (impersonated) identity. The implementation must be reversible (one-click exit), observable (persistent banner), and accountable (audit-first).

### 1.2 Non-Goals (Phase 5)

- **No expansion of approval or budget authority:** Impersonation does not grant new approval or budget mutation rights. The effective user’s permissions are unchanged from what that role already has.
- **No admin added to other role middleware:** Admin does not join coordinator, general, provincial, executor, or applicant route groups. Admin reaches role workflows via dedicated admin routes and delegation with effective user.
- **No “emergency override” or superuser mode:** There is no back door or bypass of status/ownership checks.
- **No schema changes in this plan:** Data structures are described conceptually; actual migrations and schema are out of scope for this design document and will be defined in a later, implementation-phase document if needed.
- **No implementation of admin-only tools (e.g. budget reconciliation) during impersonation:** Admin-only tools are invoked as admin, not as the effective user; Phase 5 does not extend those tools.

---

## 2. Preconditions (What Must Already Exist)

- **Binding constitution:** ADMIN_STEWARD_CONSTITUTION.md is agreed and in force. All invariants (identity, approval, budget, audit) and impersonation rules (Section 4) apply.
- **Admin role and routes:** Admin can authenticate and access admin-only routes (e.g. dashboard, activities, projects, reports, budget reconciliation when flagged). Phase 4 read-only admin UI and routes are in place.
- **Impersonation UI placeholders:** Phase 4 has introduced placeholder UI (e.g. “Act as Executor / Coordinator / Provincial”, “Exit impersonation”) gated by a feature flag and marked “Coming later.” Phase 5 replaces placeholders with working entry/exit, without changing the flag’s default (OFF).
- **Activity and audit surfaces:** Existing activity/audit logging (e.g. activity_histories, budget_correction_audit) and any services that write to them exist. Phase 5 will require that call sites accept optional “real user” and “effective user” (or equivalent) so that impersonation can be recorded; the plan does not prescribe schema changes here.
- **Role middleware and status services:** ProjectStatusService, ReportStatusService, permission helpers, and role-based middleware exist and enforce role and status. Phase 5 does not alter their logic; it only ensures they receive the effective user when impersonation is on.

---

## 3. Feature Flags (Mandatory, Default OFF)

- **Impersonation enable/disable:** A single feature flag (e.g. `ADMIN_IMPERSONATION_ENABLED` or equivalent in config) MUST gate the entire impersonation capability. When the flag is OFF: (1) no impersonation start route or UI is available, (2) any existing impersonation context in session is cleared or ignored, and (3) admin sees no working “Act as …” or “Exit impersonation” actions. Default value MUST be OFF (e.g. `false` in config, or env not set). Enabling the flag in production MUST require business/security approval as per the constitution.
- **No other flags required for Phase 5:** Optional: a separate flag to restrict which roles can be impersonated (e.g. only executor and provincial). If not present, the plan assumes all non-admin roles (executor, applicant, provincial, coordinator, general) are impersonatable unless policy later restricts.

---

## 4. Data Structures (Conceptual Only; No Schema Changes Yet)

- **Impersonation context (in-session):** A structure held in the admin’s session while impersonation is active. Conceptually it includes: real user id (admin), impersonated user id, impersonated user name, effective role, and start timestamp. Optionally: reason or ticket reference. This context is cleared on exit, logout, or session expiry. No requirement in this plan to persist it to a database; session storage is sufficient for Phase 5.
- **Impersonation lifecycle log (persistent):** A conceptual store for start/stop (and forced-stop) events. Each record conceptually includes: real user id (admin), impersonated user id, effective role, action (start | stop | forced_stop), optional reason, timestamp, and optionally IP or request id. This store is append-only. Actual table name and column design are left to implementation; the plan only requires that such events be recorded and never updated or deleted.
- **Activity/audit records (existing + extended context):** Where the system already records “who did what” (e.g. changed_by_user_id), Phase 5 requires that when the actor is an admin and impersonation is active, the record also carry: real user id (admin), effective user id (impersonated), effective role, and original role (admin). Extension may be additive (new columns or new table) and is out of scope for this document; the requirement is that no state-changing action during impersonation be logged without both identities.

---

## 5. Middleware & Request Flow (High Level)

- **After authentication:** A middleware (or equivalent) runs that checks: (1) the authenticated user is admin, and (2) the session contains valid impersonation context (e.g. impersonated user exists and is active, and role is allowed). If both hold, the request is annotated with “effective user” and “effective role” (and “impersonation active”). If the session has impersonation context but the target user is invalid or inactive, the middleware clears the context and redirects the admin to the admin area with a clear message (forced stop); a forced_stop event is logged.
- **Route access:** Admin does not hit coordinator/provincial/executor routes directly with “admin” identity. Instead, admin-only routes exist (e.g. “admin acting as executor”) that (1) require auth + admin role, (2) require impersonation to be active and effective role to match the route’s intent (e.g. executor), (3) resolve the effective user from session, (4) delegate to the same controller/logic that the role route uses, passing the effective user as the “acting user,” and (5) ensure that any audit write receives both real user (admin) and effective user. Existing role middleware remains unchanged; it is not broadened to include admin.
- **Controllers and services:** Controllers or services that perform role-based actions (e.g. submit, approve, revert) receive the “acting user” (effective user when impersonation is on, otherwise the authenticated user). They do not receive “admin” as the acting user when impersonation is on. Status services and permission helpers use the acting user for all checks. Audit/logging call sites receive an optional “audit context” (real user id, effective user id, effective role, original role) and persist it when provided by the middleware or controller.

---

## 6. UI Changes (Admin-Only)

- **Entry:** An admin-only UI (e.g. under admin dashboard or sidebar) allows the admin to list or search users by role (executor, applicant, provincial, coordinator, general), select a user, and start impersonation after confirmation. Entry is only visible when the impersonation feature flag is ON. No automatic or default impersonation.
- **Banner:** When impersonation is active, every page rendered in the role workflow (and, if applicable, admin pages) shows a persistent banner stating: “Acting as: [name] ([role]). Logged in as Admin: [admin name].” and an “Exit impersonation” control. The banner is always visible during impersonation; it is not dismissible in a way that hides the fact of impersonation.
- **Exit:** “Exit impersonation” is available in the banner (and optionally in admin sidebar or header). One click clears the impersonation context and redirects to the admin area. No extra confirmation unless policy requires it.
- **Role UI:** While impersonating, the admin sees the same dashboards, lists, and actions that the effective user would see (e.g. executor dashboard, project list, report list). No additional “admin override” buttons or links. Admin-only tools (e.g. budget reconciliation) are not presented as the effective user’s tools; if the admin needs them, they exit impersonation and use them as admin.

---

## 7. Audit Logging Requirements

- **Impersonation lifecycle:** Every start, stop, and forced_stop is recorded in the dedicated impersonation lifecycle log with: real user id (admin), impersonated user id, effective role, action, timestamp, optional reason, and optionally IP or request id. Records are append-only.
- **State-changing actions during impersonation:** Every action that changes state (project, report, status, budget, or other audited entity) and that is performed while impersonation is active must be recorded in the relevant audit/activity store with: real user id (admin), effective user id (impersonated), effective role, and original role (admin). Existing fields (e.g. changed_by_user_id) may represent the effective user for backward compatibility in reporting, but the record must also carry the admin identity so that the real actor is never lost. No record written during impersonation may omit the admin identity.
- **No update or delete of audit rows:** All audit tables (including the impersonation lifecycle log) are append-only. No application code may update or delete these rows.

---

## 8. Explicit Exclusions (What Is NOT Allowed in Phase 5)

- **Adding admin to role middleware:** Admin must not be added to coordinator, general, provincial, executor, or applicant middleware groups so that admin can access those routes without impersonation.
- **Relaxing status or ownership checks:** No “if real user is admin, allow” in ProjectStatusService, ReportStatusService, permission helpers, or controllers. The effective user is subject to the same rules as a real user of that role.
- **Budget or approval bypass:** Impersonation does not allow mutation of approved project or report data outside the existing, audited correction workflows. BudgetSyncGuard and edit locks remain in force for the effective user as for any other user.
- **Hiding admin identity in audit:** No audit record for an action performed by admin (with or without impersonation) may omit the admin (real user) identity.
- **Impersonation of admin users:** The target of impersonation must be a non-admin role (executor, applicant, provincial, coordinator, general). Admin-to-admin impersonation is out of scope for Phase 5 unless explicitly added by a later design.
- **Emergency override or superuser mode:** No back door, config flag, or code path that bypasses the above rules.

---

## 9. Acceptance Criteria

- **Start:** An admin can start impersonation only when the feature flag is ON, by selecting an active non-admin user and confirming. Session receives impersonation context; a start event is logged with admin id, impersonated user id, effective role; admin is redirected to the appropriate role dashboard or entry point.
- **Effective identity:** For the duration of impersonation, authorization, data access, and UI for role workflows use the effective user. No action is allowed that the effective user could not perform. Status and ownership checks use the effective user.
- **Audit:** Every state-changing action during impersonation is logged with both real user (admin) and effective user (and effective role, original role). Start and stop (and forced stop) are logged in the impersonation lifecycle log.
- **Banner:** A persistent banner is visible on every relevant page during impersonation, showing “Acting as …” and “Logged in as Admin …” and an exit control.
- **Exit:** Admin can exit impersonation in one click. Context is cleared, stop event is logged, admin is redirected to admin area. Session expiry or logout also clears impersonation.
- **Invalid target:** If the impersonated user is deleted or deactivated, the next request clears impersonation, logs forced_stop, and redirects the admin with a clear message.
- **Flag OFF:** When the impersonation flag is OFF, no impersonation can be started; any existing context is ignored or cleared; no working “Act as” or “Exit” is available.
- **Constitution:** The implementation complies with ADMIN_STEWARD_CONSTITUTION.md Sections 2–4 and 6–7; no invariant is weakened and no prohibition is violated.

---

**Document version:** 1.0  
**Status:** Design lock for Phase 5; no code or schema in this document.  
**End of Phase 5 Admin Impersonation — Conceptual Model & Implementation Plan.**
