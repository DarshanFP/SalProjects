# Admin Steward Constitution

**Document type:** Non-negotiable system law  
**Authority:** Principal System Architect & Governance Steward  
**Effective:** Upon agreement and publication  
**Application:** SALProjects (Laravel, live production)  
**Status:** Binding on all future design and code decisions

---

## 1. Purpose of This Document

### 1.1 Why This Constitution Exists

This document formalises the governance rules for the Admin role and related system behaviour in SALProjects. The application is **live** and has completed multiple phases governing budget correctness, approval authority, resolver discipline, audit safety, and admin stewardship. Before any further implementation, the rules established in those phases must be **locked** as constitutional constraints.

This constitution exists so that:

- **Future developers** have a single, unambiguous source of what is permitted and what is forbidden when touching admin, approval, budget, or audit.
- **Future architects** cannot inadvertently or under pressure introduce designs that violate identity, audit, or authority boundaries.
- **Auditors** can verify that the system adheres to stated governance rules and that no back doors or silent overrides exist.
- **Future maintainers** (“future me”) are bound by the same rules and cannot weaken them without explicit amendment of this document and agreed change control.

### 1.2 Why These Rules Are Frozen

The rules below reflect decisions already made and implemented in a production system. They protect:

- **Accountability:** Every action attributable to a real identity; admin never hidden.
- **Approval integrity:** The chain of authority (executor → provincial → coordinator/general) is not bypassed.
- **Budget governance:** Approved project budget data is not mutated except through explicit, audited correction flows.
- **Audit immutability:** Logs are append-only; history is not rewritten.

These rules are **frozen** so that urgency, convenience, or short-term pressure cannot justify violations. Any change to this constitution requires explicit review, documentation update, and—where applicable—business and security approval. There are no exceptions “just for this release” or “just for this bug.”

---

## 2. Admin Steward Definition

### 2.1 What Admin IS

Admin is the **System Steward** role. Admin:

- **Has superset visibility:** Admin may be granted access to view all projects, all reports, all activities, all correction logs, and—when implemented—system diagnostics and configuration. Visibility is a superset of what any other role can see; it does not imply action authority.
- **Has controlled action authority:** Admin does **not** perform role actions (submit, forward, approve, revert, edit draft) by virtue of being admin. Action authority is exercised **only** by (a) **impersonating** a user and then acting under that user’s role and permissions, or (b) using an **explicit admin-only tool** (e.g. budget reconciliation) that has its own preconditions, audit, and safety checks.
- **Has bounded correction authority:** Admin may correct data only through **explicit correction workflows** (e.g. budget reconciliation). Such workflows are feature-flagged, audited, and do not use the normal edit paths. There is no ad-hoc edit of approved project or report data outside these flows.
- **Has bounded configuration authority:** Admin may be granted read-only (or limited, audited) access to feature flags and system configuration used by the application. Configuration authority does not include runtime environment variables, database credentials, or infrastructure-level configuration. This does **not** include arbitrary code execution, direct database manipulation, or removal of approval or budget guards.
- **Is accountable:** Admin identity is never lost. Every action performed by or on behalf of admin must be traceable to the admin and, when applicable, to the impersonated user and effective role.

### 2.2 What Admin IS NOT

Admin is **not**:

- A **superuser.** Admin does not bypass approval rules, status checks, or ownership checks by virtue of role alone. No “if user is admin then allow” in permission or status logic outside impersonation or an explicit, audited admin tool.
- A **shortcut.** Admin does not approve, revert, submit, or forward “as admin.” Such actions are performed only while impersonating a user who holds the appropriate role, subject to the same business rules as that user.
- **Invisible.** Admin identity must never be omitted from audit records. No action may be logged without the real actor (admin) being recorded when the actor is admin.
- **Above the law.** Admin is bound by this constitution and by the same invariants that protect approval, budget, and audit. No implementation may grant admin privileges that violate the following sections.

---

## 3. Non-Negotiable Invariants

The following invariants must hold at all times. No feature, fix, or optimisation may violate them.

### 3.1 Identity and Audit Invariants

- **I1.** Every action that changes state (project, report, status, budget, or other audited entity) must record the **real actor.** When the actor is an admin, the record must include the admin identity (e.g. admin_user_id or equivalent). When the action is performed while impersonating, the record must also include the impersonated user and effective role. The **original role** of the actor (admin) must be preserved.
- **I2.** Audit and activity log rows are **immutable.** No application code may update or delete audit rows. Corrections or reversals, if ever permitted by policy, must be recorded as new rows (e.g. reversal or correction events), not as edits to existing rows.
- **I3.** Admin identity must **never** be hidden, stripped, or omitted from any audit trail. Any code path that writes an audit record for an action performed by admin must persist admin identity. There is no “system” or “automated” actor that replaces admin when admin performs an action.

### 3.2 Approval Authority Invariants

- **I4.** The **approval chain** is fixed: projects and reports move through defined statuses; only roles with explicit authority (provincial, coordinator, general) may forward, approve, or revert, and only when status and ownership rules allow. Admin does **not** hold approval authority by default. Admin may perform approval actions **only** while impersonating a user who holds that authority, and subject to the same rules as that user.
- **I5.** **Status checks** in ProjectStatusService, ReportStatusService, and equivalent logic may not be relaxed or bypassed for admin outside impersonation. When admin is impersonating, the **effective user** (impersonated user) is passed to these services; the services apply the same role and status rules as for a real user of that role. No branch that says “if admin then allow regardless of status.”
- **I6.** **Ownership and hierarchy** checks (e.g. who can edit, who can submit, who can approve) apply to the effective user when impersonation is active. Admin does not gain universal ownership or universal approval by virtue of being admin.

### 3.3 Budget Governance Invariants

- **I7.** **BudgetSyncGuard** and Phase 3 edit lock (restriction of general-info and budget edits after approval) remain in force. No code path may mutate approved project budget fields (e.g. overall_project_budget, amount_forwarded, local_contribution, amount_sanctioned, opening_balance) through the **normal** project or report edit flows when the project is approved.
- **I8.** The **only** permitted bypass of the approved-project budget lock is through **explicit correction workflows** (e.g. budget reconciliation). Such workflows must be: (a) admin-only, (b) feature-flagged, (c) fully audited (admin_user_id, action type, old and new values, comment, timestamp, and optionally IP), and (d) documented. No other path may write to approved project budget fields.
- **I9.** Resolver discipline (e.g. ProjectFundFieldsResolver, sync rules) is not weakened. Admin correction workflows may use the resolver and write results to the project only through the designated service (e.g. AdminCorrectionService) and audit table (e.g. budget_correction_audit). No ad-hoc direct writes to project or type-specific budget tables for approved projects.

### 3.4 Editability and Lifecycle Invariants

- **I10.** **Approved** projects and reports are not editable through the standard edit forms. Only reverted (or draft) entities may be edited via normal edit flows. Admin does not gain an exception; admin may edit only by impersonating a user who has edit rights and when the entity is in an editable status, or through an explicit correction workflow that is audited and feature-flagged.
- **I11.** **Role middleware** for coordinator, general, provincial, executor, and applicant is not altered to add “admin” to every group so that admin can hit any route without impersonation. Admin reaches role workflows **only** by impersonating (and using dedicated admin routes that delegate to the same controllers with effective user) or by using admin-only tools. Existing role middleware remains role-specific.

---

## 4. Impersonation Rules

### 4.1 When Impersonation Is Required

Admin **must** use impersonation when performing any action that belongs to a role workflow: submit project, submit report, forward, approve, revert, edit draft, or any other action that is normally restricted by role and status. There is no “admin override” that allows these actions without impersonation. The only way for admin to perform them is to impersonate a user who has the right role and then to comply with the same business rules as that user.

### 4.2 What Impersonation Means

- **Explicit:** Impersonation is started by an explicit user action (e.g. “Start impersonating [user]”). It is not automatic or implicit.
- **Reversible:** Impersonation can be ended at any time (e.g. “Exit impersonation”). The system must support one-click exit and must clear impersonation state on exit, session expiry, or when the impersonated user is invalid (e.g. deleted or deactivated).
- **Visible:** When impersonation is active, the UI must show a persistent, unambiguous indication (e.g. banner) that the current session is “Acting as [impersonated user] ([role]); logged in as Admin: [admin name].” The admin identity must never be hidden during impersonation.
- **Auditable:** Start and stop of impersonation must be logged (admin_user_id, impersonated_user_id, effective_role, action, timestamp). Every action taken during impersonation must be recorded with admin_user_id, impersonated_user_id, effective_role, and original_role = admin so that both the real actor and the effective actor are known.

### 4.3 What Impersonation Does NOT Allow

- Impersonation does **not** allow bypassing status or ownership checks. The effective user (impersonated user) is subject to the same rules as a real user of that role: they can edit only what they could edit, submit only what they could submit, approve only what they could approve.
- Impersonation does **not** allow admin to approve or revert “as admin.” The workflow sees the effective user and effective role; the audit sees both admin and effective user.
- Impersonation does **not** grant access to admin-only tools (e.g. budget reconciliation) in the guise of the impersonated user. Admin-only tools are invoked as admin, not as the impersonated user, and are audited accordingly.

---

## 5. Correction Authority Rules

### 5.1 When Admin May Correct

Admin may correct data only when:

- The correction is performed through an **explicit correction workflow** (e.g. budget reconciliation) that is designed, documented, and implemented for that purpose; and
- The workflow is **feature-flagged** so that it can be disabled without code change; and
- The entity and field being corrected are within the scope of that workflow (e.g. approved project fund fields only for budget reconciliation); and
- The workflow is **admin-only** (only users with role admin may access it).

There is no general “admin can correct anything” authority. Each correction workflow must define its own preconditions, allowed operations, and audit format.

### 5.2 How Corrections Must Be Performed

- Corrections must go through the **designated service** (e.g. AdminCorrectionService) and **designated audit table** (e.g. budget_correction_audit). No direct writes to project or report tables from controllers or one-off scripts for approved data.
- Every correction action (e.g. accept suggested, manual correction, reject) must be **explicit** (user-initiated). No automatic or background correction of approved data.
- All correction workflows must **re-validate** where applicable (e.g. re-run resolver before applying “accept suggested”) and must **not** alter approval status unless the workflow explicitly defines that (today, budget reconciliation does not change approval status).

### 5.3 Mandatory Audit Expectations

For every correction workflow:

- **Who:** admin_user_id (and, if the workflow is ever used in an impersonation context, impersonated_user_id; today budget reconciliation is not used while impersonating).
- **What:** action type (e.g. accept_suggested, manual_correction, reject), entity id, and old and new values (or clear indication of “no change” for reject).
- **When:** timestamp.
- **Why/context:** mandatory comment or reason where the workflow requires it (e.g. manual correction requires a reason); optional comment for other actions.
- **Where:** optionally IP or request identifier for security review.

Audit rows are **append-only.** No update or delete of correction audit rows by the application.

---

## 6. Explicit Prohibitions

The following are **forbidden.** They must not be added to the system, and they must be rejected in code review.

### 6.1 Actions That Must Never Be Added

- **Silent bypass of permission or status for admin:** Any logic that grants permission or allows an action solely because the user role is admin, without impersonation or an explicit correction workflow, is prohibited. Examples: “if (user->role === 'admin') return true” in canEdit, canSubmit, canApprove, or equivalent; “if (user->role === 'admin') skip status check.”
- **Direct edit of approved project or report via normal forms:** Allowing admin (or any user) to edit approved project or report content (general info, budget fields, etc.) through the standard project/report edit screens. Approved data may change only through explicit correction workflows that are audited and feature-flagged.
- **Admin added to all role middleware:** Changing route middleware so that admin is included in coordinator, general, provincial, executor, or applicant groups for the purpose of letting admin hit those routes without impersonation. Admin must reach role workflows via impersonation and dedicated admin routes that delegate with effective user.
- **Hiding admin identity in audit:** Writing an audit record for an action performed by admin without storing the admin identity (admin_user_id or equivalent). Omitting admin_user_id, or storing only the impersonated user when the actor was admin, is prohibited.
- **Update or delete of audit rows:** Any application code that updates or deletes rows in activity_histories, budget_correction_audit, or any other designated audit table. Audit is append-only.

### 6.2 Patterns That Must Never Appear in Code

- **Conditional bypass:** “If admin, skip this check” or “If admin, allow regardless of status/ownership” in permission helpers, status services, or controllers—except inside a designated correction workflow that has its own checks and audit.
- **Dual identity without audit:** Using an “effective user” for business logic while failing to pass admin identity and impersonation context to the audit layer. When impersonation is active, both effective user and admin must be recorded.
- **Back door:** Any route, config flag, or code path that allows mutation of approved project or report data without going through the designated correction service and audit table.
- **Weakening BudgetSyncGuard or Phase 3 lock:** Adding a branch that allows sync or edit when the project is approved, unless the branch is inside the designated admin correction service (or another future, equally audited workflow) and is documented.

### 6.3 Anti-Patterns to Reject in Code Review

- PRs that add “admin” to role middleware for non–read-only routes.
- PRs that add “if (user->role === 'admin') return true” (or equivalent) in canEdit, canSubmit, canApprove, canView (for edit-like actions), or in status checks, without a clear link to impersonation or a documented correction workflow.
- PRs that write to project or report budget fields (or other locked fields) for approved entities outside the designated correction workflow.
- PRs that update or delete audit table rows.
- PRs that log an action without including admin_user_id when the actor is admin.
- PRs that introduce a “superuser” or “system” override without a corresponding constitution amendment and explicit approval.

### 6.4 PR Gate Enforcement

Any PR affecting admin behaviour, approval flows, budget logic, impersonation, or audit mechanisms MUST explicitly reference compliance with this document. The PR description or review checklist must state that the change has been verified against this constitution. Violations are detectable at PR review time and must be rejected.

---

## 7. Future Change Policy

### 7.1 Changes That Require Documentation Updates

- Any new **admin-only tool** (e.g. a new correction workflow, diagnostics, or config toggling) must be documented: purpose, preconditions, audit format, and how it complies with this constitution.
- Any change to **authority bounds** (what admin can see, do, or correct) must be reflected in this document or in a linked, approved appendix. The constitution remains the single source of truth for what admin is and is not allowed to do.
- Any change to **impersonation** behaviour (who can be impersonated, what is logged, how exit works) must be documented and must not violate the invariants in Section 3 or the rules in Section 4.

### 7.2 Changes That Require Feature Flags

All admin-only write capabilities MUST be behind a feature flag, default disabled in production.

- **New admin-only tools** that write data or change system behaviour must be behind a feature flag (or equivalent) so that they can be disabled without code deploy.
- **Impersonation** may be gated (e.g. ADMIN_IMPERSONATION_ENABLED) so that it can be turned off in emergency or for compliance.
- **Correction workflows** (e.g. budget reconciliation) are already gated; any new correction workflow must also be gated.

### 7.3 Changes That Require Business Approval

- Enabling **impersonation** in production (if gated).
- Enabling **feature-flag toggling** by admin in production (if implemented).
- Enabling any **new admin-only tool** that writes data or changes system behaviour.
- Any **constitution amendment** that relaxes an invariant or prohibition (see Section 7.4).

### 7.4 Changes That Must Never Be Allowed

- **Relaxing** identity or audit invariants (Section 3.1): e.g. allowing audit rows to be updated or deleted, or allowing admin identity to be omitted.
- **Relaxing** approval authority invariants (Section 3.2): e.g. allowing admin to approve or revert without impersonation or without the same status/ownership rules as the effective role.
- **Relaxing** budget governance invariants (Section 3.3): e.g. allowing normal edit or sync paths to mutate approved project budget fields, or allowing a new correction path that is not audited and feature-flagged.
- **Removing** the requirement for explicit impersonation when admin performs role actions (submit, forward, approve, revert, edit draft).
- **Introducing** a superuser or “system” role that bypasses these rules without a formal constitution amendment, security review, and business sign-off. Such an amendment would be exceptional and must be documented and versioned.

---

## 8. Final Declaration

### 8.1 Binding Force

This document constitutes **non-negotiable system law** for the SALProjects application. The rules set forth in Sections 2 through 7 are **binding** on:

- All current and future implementation work.
- All design decisions affecting admin, approval, budget, or audit.
- All code reviews: any change that violates this constitution must be rejected.
- All deployments: no release may include code that violates these rules.

Pressure from deadlines, incidents, or operational urgency **does not** justify a violation. If a change cannot be made without violating this constitution, the change must be redesigned or the constitution must be amended through the process stated in Section 7.

### 8.2 Compliance of Future Phases

All future phases of the Admin Steward work—including but not limited to impersonation implementation, audit extension, UI and route alignment, and admin-only tools—**must** comply with this constitution. No phase may:

- Weaken or bypass the invariants in Section 3.
- Introduce silent overrides or back doors.
- Omit admin identity from audit.
- Allow direct edit of approved project or report data outside explicit correction workflows.
- Add admin to role middleware for the purpose of bypassing impersonation.

Acceptance of this document implies acceptance that these rules override any conflicting informal practice, legacy assumption, or ad-hoc decision. The constitution is the **final authority** for Admin behaviour in the system.

---

**Document version:** 1.0  
**Effective upon:** Agreement and publication  
**Supersedes:** No prior constitution; consolidates and freezes rules from FINAL_ADMIN_STEWARD_ROLE_ANALYSIS.md, PHASE_WISE_ADMIN_STEWARD_IMPLEMENTATION_PLAN.md, and related governance decisions.

**End of Admin Steward Constitution.**
