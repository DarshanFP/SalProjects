# Admin Steward — Documentation Index

**Application:** SALProjects (Laravel, live production)  
**Role:** Admin as System Steward (not superuser)  
**Status:** Binding governance and implementation reference

---

## Constitutional Binding Notice

**ADMIN_STEWARD_CONSTITUTION.md is binding system law.**

Any admin UI, route, controller, service, or policy MUST comply with it. No implementation may grant admin privileges that violate the constitution’s invariants (identity and audit, approval authority, budget governance, editability and lifecycle). Pressure from deadlines or incidents does not justify a violation.

---

## Admin UI Philosophy

- **Read-only by default:** Admin has superset *visibility*; action authority is exercised only via explicit impersonation or explicit, audited admin-only tools (e.g. budget reconciliation).
- **Explicit escalation:** Write-capable UI (e.g. budget correction actions) must be behind feature flags, default OFF, and show read-only or disabled UI when the flag is off.
- **No silent mutations:** No approval, budget, or status mutation “as admin” without impersonation or a documented, audited correction workflow.
- **Visibility over power:** Phase 4 focuses on making existing data and state visible to admin (projects, reports, activities, approval state, resolver/discrepancy, users/roles). It does not add new write capabilities beyond what is already defined and flagged.

---

## Document Map

| Document | Purpose |
|----------|---------|
| [ADMIN_STEWARD_CONSTITUTION.md](./ADMIN_STEWARD_CONSTITUTION.md) | **Non-negotiable system law** — invariants, prohibitions, impersonation and correction rules. |
| [FINAL_ADMIN_STEWARD_ROLE_ANALYSIS.md](./FINAL_ADMIN_STEWARD_ROLE_ANALYSIS.md) | Authoritative design: Admin Steward definition, authority bounds, impersonation and audit model. |
| [PHASE_WISE_ADMIN_STEWARD_IMPLEMENTATION_PLAN.md](./PHASE_WISE_ADMIN_STEWARD_IMPLEMENTATION_PLAN.md) | Phased implementation (Phase 1–6); what changes and what must not. |
| [PHASE_4_ADMIN_UI_AND_ROUTES.md](./PHASE_4_ADMIN_UI_AND_ROUTES.md) | Phase 4: Admin UI and routes audit — current state, gaps, feature flags, non-goals. |
| [ADMIN_VIEWS_AND_ROUTES_REVIEW.md](./ADMIN_VIEWS_AND_ROUTES_REVIEW.md) | Historical review of admin views and routes (if present). |
| [ADMIN_DOCUMENTATION_SUMMARY_AND_STATUS.md](./ADMIN_DOCUMENTATION_SUMMARY_AND_STATUS.md) | Summary and status of admin documentation (if present). |

---

**End of README.**
