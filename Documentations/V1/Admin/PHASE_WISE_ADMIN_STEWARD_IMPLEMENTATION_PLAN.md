# Phase-Wise Admin Steward Implementation Plan

**Document type:** Implementation plan (executable by engineering)  
**Role:** Senior System Architect & Security Steward  
**Date:** 2026-01-29  
**Application:** SALProjects (Laravel, live production, multi-role)  
**Reference:** FINAL_ADMIN_STEWARD_ROLE_ANALYSIS.md

---

## Mission Context

This application is **live** and has completed multiple phases (Phase 1–6) for budget correctness, approval authority, resolver discipline, and audit safety. The Admin role is being formalized as a **System Steward**, not a superuser.

**Core principles (non-negotiable):**

- Admin must **never** silently bypass business rules.
- All actions must be **auditable**.
- Existing role workflows must **remain intact**.
- Approved projects must **not** be directly edited outside explicit correction workflows.
- Admin authority is exercised **only** via:
    - **Explicit impersonation**, or
    - **Explicit correction workflows** (e.g. budget reconciliation).

**Absolute constraints:**

- Do **not** weaken approval rules.
- Do **not** allow admin to directly edit approved projects (except through dedicated, audited correction flows).
- Do **not** remove existing role middleware.
- Assume a **mature, live system** — no greenfield assumptions.

---

## Phase 1: Admin Role Definition (Foundation)

### Goal

Formalise the Admin Steward definition in documentation and configuration so that all subsequent phases and code changes align to a single, explicit authority model. No behavioural change to the application in this phase; only documentation and optional config placeholders.

### What Changes

- **Documentation:** A single, canonical “Admin Steward Definition” section (or doc) that is linked from this plan and from developer onboarding. It must state:
    - Admin is a **System Steward**, not a superuser.
    - **Visibility:** Admin may see all projects, reports, activities, correction logs, and (when built) system diagnostics — i.e. a superset of what any role can see.
    - **Action authority:** Admin does **not** get to perform role actions (submit, forward, approve, revert, edit draft) by virtue of being admin. Action authority is exercised **only** by:
        - Impersonating a user (and then following that user’s role rules), or
        - Using an explicit admin-only tool (e.g. budget reconciliation) that has its own preconditions, audit, and safety checks.
    - **Correction authority:** Limited to explicitly designed flows (e.g. budget reconciliation). No ad-hoc edit of approved project/report data outside these flows.
    - **Configuration authority:** Read-only (or later, limited toggling) of feature flags and system configuration used by the app; no arbitrary code or DB access.
- **Configuration (optional):** If not already present, a config key or small config section that signals “Admin Steward mode” or references the definition (e.g. for feature-flagging future admin tools). No change to existing role middleware or route logic.

### What Does NOT Change

- No changes to routes, middleware, controllers, or services.
- No changes to approval logic, budget resolver, or BudgetSyncGuard.
- No changes to existing admin routes (dashboard, budget-reconciliation, logout).

### Risk Level

**Low.** Documentation and optional config only; no runtime behaviour change.

### Validation Checklist

- [ ] Canonical “Admin Steward Definition” document/section exists and is linked from this plan.
- [ ] Definition explicitly separates: Visibility / Action authority / Correction authority / Configuration authority.
- [ ] Definition explicitly states what Admin **cannot** do (e.g. no silent bypass, no direct edit of approved projects outside correction flows).
- [ ] Team review completed; definition agreed with product/security where applicable.
- [ ] Optional: config placeholder for future admin-tool feature flags present and documented.

### Rollback Considerations

None required; documentation can be revised without deployment. If config was added, it can be reverted in a later release.

---

## Phase 2: Impersonation Architecture (Core)

### Goal

Design and implement a **session-based impersonation** system so that Admin can act **as** executor, applicant, provincial, coordinator, or general. The effective user (impersonated user) drives all workflow logic; the authenticated user remains the admin. All actions during impersonation must be auditable with admin_user_id, impersonated_user_id, effective_role, and context.

### What Changes

- **Session contract:** A well-defined session structure for “impersonation context” (e.g. admin_user_id, impersonated_user_id, impersonated_user_name, effective_role, started_at). When this context is set, the request is treated as “admin acting as &lt;effective_role&gt;”.
- **Effective user vs authenticated admin:** Application code must distinguish:
    - **Authenticated user:** Always the admin when impersonation is active (used for audit and “who is really doing this”).
    - **Effective user:** The impersonated user (used for permissions, ownership, hierarchy, and “who the workflow sees”). Middleware or a central resolver must provide the effective user and effective role to downstream code when impersonation is active.
- **Middleware:** New middleware (e.g. “Resolve effective user” or “Impersonation context”) that:
    - Runs after authentication.
    - If the authenticated user is admin and session contains valid impersonation context, resolves the impersonated user, validates they exist and are active (and optionally that their role is one of executor, applicant, provincial, coordinator, general).
    - Binds “effective user” and “effective role” to the request/container so controllers and services can use them without touching Auth::user() for business logic during impersonation.
    - If impersonation context is invalid (e.g. user deleted/deactivated), clears the context and redirects to admin dashboard with a safe message.
- **Route access:** Admin must be able to perform actions that belong to the **effective role** without changing existing role middleware. Preferred approach: **admin-only routes** that mirror the target role’s routes (e.g. /admin/impersonate/executor/...) and dispatch to the **same** controller actions used by executor, with the effective user injected. This keeps existing `role:executor,applicant` (etc.) middleware unchanged and avoids adding “admin” to every role group.
- **Start impersonation:** Admin-only route (e.g. POST /admin/impersonate/start) that accepts a target user_id, validates role and availability, sets session impersonation context, logs the start event (see Phase 3), and redirects to the appropriate dashboard for that role.
- **Stop impersonation:** Admin-only route (e.g. POST /admin/impersonate/stop) that clears session impersonation context, logs the stop event, and redirects to /admin/dashboard. Must be one-click from the UI (no unnecessary nested confirmation).
- **UI banner:** When impersonation is active, **every** page must show a persistent, prominent banner (e.g. “Acting as: [Name] ([Role]). You are logged in as Admin: [Admin Name]. [Exit impersonation].”). The banner must be impossible to miss (e.g. full-width, distinct colour).
- **Permission and service layer:** Where permissions and status checks are performed (e.g. ProjectPermissionHelper, ProjectStatusService, ReportStatusService), the code must use the **effective user** and **effective role** when impersonation is active — so that workflow logic sees “executor” or “coordinator” etc., not “admin”. Admin identity is passed only for **audit** (see Phase 3).

### What Does NOT Change

- Existing role middleware groups (coordinator, general, provincial, executor, applicant) remain unchanged; admin is **not** added to those middleware lists for normal routes.
- Existing approval and status rules (ProjectStatus, ReportStatusService, etc.) are not relaxed; they receive the effective user and apply the same rules as for a real user of that role.
- Budget reconciliation and other admin-only tools remain separate; they are not “impersonation” and do not use the effective user for their logic.
- No direct edit of approved projects or reports outside existing (or future) explicit correction flows.

### Risk Level

**Medium.** Introduces new session state and middleware; incorrect resolution of “effective user” could lead to wrong permissions or wrong audit. Mitigation: strict validation of impersonation context, immutable audit of start/stop, and thorough tests for “admin impersonating X can do only what X can do”.

### Validation Checklist

- [ ] Session structure for impersonation is documented and used consistently.
- [ ] Middleware correctly resolves effective user and clears invalid context.
- [ ] Admin can start impersonation only for executor, applicant, provincial, coordinator, general (and optionally only for active users).
- [ ] Admin can stop impersonation in one click; session is cleared and redirect is to admin dashboard.
- [ ] Banner is visible on every page when impersonation is active and shows both “Acting as” and “Logged in as Admin”.
- [ ] Permission and status checks use effective user when impersonation is active; workflow behaviour is identical to a real user of that role.
- [ ] No existing role middleware is modified to add “admin”; admin reaches role workflows via dedicated admin routes that delegate to existing controllers with effective user.
- [ ] Rollback: disabling the new middleware or the impersonation routes must leave the rest of the app unchanged (no dependency of normal flows on impersonation).

### Rollback Considerations

- Impersonation can be disabled by removing or bypassing the “start impersonation” route and clearing any existing impersonation session (e.g. via a one-off route or config).
- Middleware can be removed from the stack; existing role behaviour is unaffected.
- Session keys for impersonation should be namespaced so that clearing them does not affect other session data.

---

## Phase 3: Audit & Accountability

### Goal

Ensure **no action loses traceability** and **admin identity is never hidden**. Extend activity and audit logging so that every action taken during impersonation (or by admin in admin-only tools) records admin_user_id, impersonated_user_id when applicable, effective_role, and original_role. Logs must remain **immutable** (append-only; no update/delete of audit rows by the application).

### What Changes

- **Activity / audit schema:** Extend the table(s) used for project/report status changes and other user-driven actions (e.g. `activity_histories`) with nullable columns: `admin_user_id`, `impersonated_user_id`, `effective_role`, `original_role`. When the actor is an admin:
    - If **not** impersonating: store admin_user_id = admin, impersonated_user_id = null, effective_role = 'admin', original_role = 'admin'.
    - If **impersonating:** store admin_user_id = admin, impersonated_user_id = target user id, effective_role = session effective_role, original_role = 'admin'.
- **Call sites:** All code paths that write to these audit tables (e.g. ProjectStatusService::logStatusChange, ReportStatusService::logStatusChange, ActivityHistoryService methods) must accept an optional “audit context” (admin_user_id, impersonated_user_id, effective_role, original_role) and persist it when provided. When impersonation middleware has set the context, controllers pass this context into the services.
- **Impersonation lifecycle log:** A dedicated table or log stream for impersonation events: start, stop, forced_stop (e.g. target user invalid). Each row must include: admin_user_id, impersonated_user_id, effective_role, action (start/stop/forced_stop), timestamp, optional reason or comment, and optionally ip_address. No update or delete of these rows by the application.
- **Policies:** Document that audit tables are append-only; no application code may update or delete audit rows. Any “correction” of mistakes must be a new row (e.g. “correction” or “reversal” event) if policy allows.

### What Does NOT Change

- Existing audit rows remain as-is; backfill of new columns for old rows can be null (or “unknown” if a sentinel is used). No rewriting of history.
- Budget correction audit (`budget_correction_audit`) already records admin_user_id; it does not need to record impersonated_user_id (budget reconciliation is not impersonation). No change to its structure unless product explicitly asks to link it to an impersonation session.
- Laravel’s logging channel configuration need not change unless the team chooses to add a dedicated admin-audit channel; the requirement is primarily for **database** audit tables used for accountability.

### Risk Level

**Medium.** Schema migrations and changes to logging call sites; missing or incorrect context in one place could reduce traceability. Mitigation: code review of every audit write path; tests that verify audit rows contain admin and impersonation fields when applicable.

### Validation Checklist

- [ ] Migration adds nullable admin_user_id, impersonated_user_id, effective_role, original_role to the relevant audit/activity table(s); backfill is null for existing rows.
- [ ] All status-change and activity log write paths accept and persist the new audit context when provided.
- [ ] When admin acts without impersonation (e.g. budget reconciliation), admin_user_id is set and impersonated_user_id is null.
- [ ] When admin acts while impersonating, both admin_user_id and impersonated_user_id are set, and effective_role / original_role are correct.
- [ ] Impersonation lifecycle table exists and is written on start, stop, and forced_stop.
- [ ] No application code updates or deletes audit rows; policy document or comment states immutability.
- [ ] Rollback: migration rollback removes new columns; application must handle nulls in those columns for old rows if code already expects them.

### Rollback Considerations

- Migration rollback will drop new columns; ensure application code does not assume these columns exist in all environments until migration is run. Prefer defensive checks (e.g. column_exists) or nullable access when reading.
- If a bug is found in context passing, fix is to correct the call sites and optionally backfill where possible; do not delete or alter existing audit rows.

---

## Phase 4: UI & Route Alignment

### Goal

Review all admin-accessible routes and views; fix missing or broken access so that Admin has **visibility** to all projects, all reports, all activities, and all correction logs. Ensure admin layout (e.g. sidebar) is present and that admin does not rely on duplicated business logic — reuse existing controllers and views where possible.

### What Changes

- **Route audit:** List every route that Admin should be able to **view** (read-only or list) for: projects, reports, activities, correction log. Compare with current middleware. Where the controller already allows admin (e.g. ActivityHistoryController::allActivities) but the **route** is under a role group that excludes admin (e.g. role:coordinator,general), add an equivalent route under the admin group that points to the same controller method, or extend the middleware for that specific route to include admin — **without** adding admin to the entire coordinator/general group. Prefer a dedicated admin route so that future changes to coordinator routes do not accidentally affect admin.
- **Admin sidebar in layout:** Ensure the admin sidebar is included when the authenticated user is admin (e.g. in the main layout or an admin layout). Today the admin sidebar exists but may not be included in the layout used by admin pages; fix so that admin sees Dashboard, All Activities, Budget Reconciliation (if enabled), Correction Log, and later Impersonation and Config/Diagnostics.
- **Broken links:** Fix or remove placeholder links in the admin sidebar (e.g. static HTML or #) so that every item either points to a real route or is explicitly marked as “coming later” / hidden.
- **All Activities for admin:** Ensure Admin can reach “All Activities” (e.g. route name activities.all-activities or equivalent). If the current route is under coordinator,general, add a route in the admin group that uses the same controller method so Admin gets 403 no longer.
- **Correction Log:** Ensure a direct link to the correction log exists in the admin sidebar (in addition to the link from the budget reconciliation index page).
- **Reuse of controllers/views:** For **visibility** (list projects, list reports, view project, view report, all activities), prefer reusing existing controller methods and views by either: (a) routing admin to the same URL and allowing admin in middleware for that read-only route, or (b) adding an admin route that calls the same controller method with the same view. Do **not** duplicate business logic in new admin-only controllers for simple listing/viewing.

### What Does NOT Change

- Existing role-specific routes and middleware for **actions** (submit, approve, revert, edit) are unchanged; Admin performs those actions only via impersonation (Phase 2) or via explicit admin-only tools (Phase 5).
- Existing coordinator/provincial/executor/general dashboards and their URLs are unchanged; Admin reaches them through impersonation and the existing routes, not by adding admin to those route groups.
- No new business logic in project/report approval or budget sync; only routing and layout fixes.

### Risk Level

**Low.** Mostly routing and layout; improves visibility and UX without changing approval or data rules.

### Validation Checklist

- [ ] Admin can open a list/view of all projects (using existing logic).
- [ ] Admin can open a list/view of all reports (using existing logic where applicable).
- [ ] Admin can open “All Activities” without 403.
- [ ] Admin can open Budget Reconciliation (when feature flag is on) and Correction Log from the sidebar.
- [ ] Admin sidebar is visible in the layout for all admin pages.
- [ ] No placeholder links in the sidebar point to broken or non-existent routes; placeholders are either implemented or hidden.
- [ ] No duplicate business logic introduced; admin reuses existing controllers/views for read-only visibility.
- [ ] Rollback: reverting route or layout changes restores previous behaviour; no persistent state change.

### Rollback Considerations

- Route and layout changes can be reverted in a single deployment; no database or session schema change in this phase.

---

## Phase 5: Admin-Only Tools (Non-Impersonation)

### Goal

Define and implement **explicit admin-only tools** that Admin can use **without** impersonation. Each tool has clear preconditions, audit expectations, and safety checks. These are the only ways Admin can change system state or sensitive data outside impersonation.

### What Changes

- **Budget discrepancy review & correction (existing pattern):** Already implemented (Phase 6). Document it as the **reference pattern** for admin-only tools:
    - **Preconditions:** config flag enabled; project status is approved; admin role.
    - **Audit:** Every action (accept suggested, manual correction, reject) is recorded in `budget_correction_audit` with admin_user_id, action_type, old/new values, comment, ip. No impersonation context.
    - **Safety:** No automatic correction; explicit admin action required; Phase 3 budget lock remains for normal edit paths.
- **System diagnostics (new or formalised):** An admin-only page or section that shows read-only system health information (e.g. queue status, cache status, key config values that affect behaviour). No write actions unless explicitly designed (e.g. “clear cache” with audit). Preconditions: admin role. Audit: if any action is added, log admin_user_id and action. Safety: no exposure of secrets; no destructive action without confirmation and audit.
- **Feature-flag visibility (and limited toggling):** An admin-only view of feature flags (e.g. budget.\*) that the application uses. If toggling is allowed, it must be limited to a whitelist of flags (e.g. BUDGET_ADMIN_RECONCILIATION_ENABLED), require confirmation, and be audited (who, which flag, old value, new value, timestamp). Preconditions: admin role; optional: additional “config change” permission or environment restriction (e.g. not in production without change control). Safety: no toggling of flags that would weaken approval or budget governance.
- **Read-only system configuration:** An admin-only view of non-secret configuration (e.g. app name, feature flags, list of enabled modules). No edit unless explicitly part of “feature-flag toggling” above with audit. Preconditions: admin role. Safety: no display of passwords, API keys, or secrets.

### What Does NOT Change

- Approval and revert logic; ProjectStatusService and ReportStatusService are unchanged for normal flows.
- BudgetSyncGuard and Phase 3 edit lock; only the existing budget reconciliation flow (and any future, equally audited flow) bypasses for approved projects.
- Impersonation remains the only way for Admin to perform role actions (submit, forward, approve, revert) as another user.

### Risk Level

**Medium** for any new write capability (e.g. feature-flag toggling). **Low** for read-only diagnostics and config view. Mitigation: strict whitelist for toggles; full audit for any change; no flags that weaken security or approval.

### Validation Checklist

- [ ] Budget reconciliation is documented as the reference admin-only correction tool (preconditions, audit, safety).
- [ ] System diagnostics (if implemented) are read-only or every action is audited; no secrets exposed.
- [ ] Feature-flag view exists; if toggling is implemented, only whitelisted flags can be changed and each change is audited (admin_user_id, flag, old/new, timestamp).
- [ ] System configuration view (if implemented) does not display secrets; any editable config is covered by the same audit rules as feature flags.
- [ ] Each tool’s preconditions, audit expectations, and safety checks are documented in this plan or in a linked doc.
- [ ] Rollback: new tools can be disabled by feature flag or route removal without affecting impersonation or existing budget reconciliation.

### Rollback Considerations

- New admin tools should be behind feature flags or at least behind “admin” role so that disabling the role or the route disables the tool.
- Feature-flag toggles should be reversible (e.g. toggle back to previous value); audit log remains for accountability.

---

## Phase 6: Guardrails & Non-Goals

### Goal

Document explicitly what Admin **must never do**, what remains **future scope**, what requires **feature flags**, and what requires **business approval** before enablement. This phase is **documentation and policy**; it may also inform code review checklists and automated checks where feasible.

### What Changes

- **Documentation (this section or a linked “Guardrails” doc):**
    - **What Admin must never do:**
        - Silently bypass approval or status checks (e.g. “if admin then allow” in canEdit, canSubmit, canApprove) outside impersonation or an explicit, audited admin tool.
        - Directly edit approved project or report content (e.g. general info, budget fields) through the normal project/report edit forms; only dedicated correction flows (e.g. budget reconciliation) may change approved data, and only with full audit.
        - Remove or alter existing role middleware so that admin is treated as “all roles” on every route.
        - Hide admin identity in audit logs (admin_user_id or equivalent must always be recorded when the actor is admin).
        - Delete or update audit log rows.
    - **Future scope (not in current plan):**
        - Soft delete / restore for projects or reports (if ever approved, would be a separate, audited admin tool).
        - User management (create/edit users, reset password) as an admin tool; currently may exist under coordinator/general — if exposed to admin, should be via impersonation or a dedicated audited tool.
        - Bulk operations by admin (e.g. bulk approve) — only via impersonation and subject to the same rules as the impersonated role, or a future explicit tool with audit.
    - **Feature flags:**
        - Budget reconciliation is already gated by config (e.g. BUDGET_ADMIN_RECONCILIATION_ENABLED).
        - New admin tools (diagnostics, config toggling) should be gated by a flag or environment so they can be disabled without code deploy.
        - Impersonation itself may be gated (e.g. ADMIN_IMPERSONATION_ENABLED) so that it can be turned off in emergency or for compliance.
    - **Business approval before enablement:**
        - Enabling impersonation in production.
        - Enabling feature-flag toggling by admin in production.
        - Any new admin-only tool that writes data or changes system behaviour.

### What Does NOT Change

- No code change required in this phase; only documentation and possibly a short “guardrails” checklist for PRs or security review.

### Risk Level

**Low.** Documentation only; reduces risk of future violations if the team and reviewers use the checklist.

### Validation Checklist

- [ ] “What Admin must never do” is written and agreed (e.g. with security/product).
- [ ] Future scope is clearly marked so that no one assumes it is part of the current plan.
- [ ] Feature-flag requirements for admin tools (including impersonation) are documented.
- [ ] Process for business approval before enabling new admin capabilities in production is stated or linked.
- [ ] Optional: a short guardrails checklist is added to the PR template or wiki for changes touching admin, permission, or audit.

### Rollback Considerations

None; documentation can be updated anytime.

---

## Summary: Phase Order and Dependencies

| Phase | Name                       | Depends on | Risk       |
| ----- | -------------------------- | ---------- | ---------- |
| 1     | Admin Role Definition      | —          | Low        |
| 2     | Impersonation Architecture | 1          | Medium     |
| 3     | Audit & Accountability     | 1, 2       | Medium     |
| 4     | UI & Route Alignment       | 1          | Low        |
| 5     | Admin-Only Tools           | 1, 3       | Low–Medium |
| 6     | Guardrails & Non-Goals     | —          | Low        |

**Recommended execution order:** 1 → 6 (in parallel or early) → 4 → 2 → 3 → 5. Phase 1 and 6 can be done first (docs and guardrails). Phase 4 (UI/route alignment) can be done in parallel with or before Phase 2 so that admin has correct visibility. Phase 2 (impersonation) and Phase 3 (audit) should be implemented together so that every impersonation action is audited from the start. Phase 5 (admin-only tools) can follow once audit and impersonation are stable.

---

**End of Phase-Wise Admin Steward Implementation Plan.**
