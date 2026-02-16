# Master Execution Checklist — Milestone Structure

**Context:** Solo developer; production database live.  
**Rule:** Milestone 1 and Milestone 2 MUST NOT overlap. Each milestone must be **fully closed** before the next begins.

---

## Milestone 1 — Data Integrity Shield (skip-empty-sections)

**Scope:** Prevent accidental data loss when project update request has empty or missing section data. No validation rule changes, no schema changes, no resolver changes, no societies changes.

**In scope:**
- Add “skip section update when request section is absent or empty” guard to all section controllers that use delete-then-recreate.
- Optional config: `project.update_skip_empty_sections`.
- Unit and integration tests for skip-empty behaviour.

**Out of scope for M1:** UpdateProjectRequest changes, schema/validation alignment, resolver, societies, empty() numeric fixes, PDF/mail, attachment auth.

**Closure criteria:** All section controllers skip mutation when their section key is absent/empty; config (if used) documented; tests green; deployment checklist and rollback documented; production verified.

---

## Milestone 2 — Validation & Schema Alignment

**Scope:** Align validation and schema so that NOT NULL columns never receive NULL from update; fix silent drops of valid zero values; optional defense-in-depth and PDF/mail hardening. No resolver Phase 4, no societies structural changes.

**In scope:**
- Schema–validation alignment (e.g. overall_project_budget and other NOT NULL columns; prepareForValidation / merge where needed).
- Replace empty() checks on numeric fields with explicit null/'' checks (e.g. IES family working members monthly_income).
- Optional: attachment project-level authorization check; PDF/show variable contract audit; graceful mail failure handling.

**Out of scope for M2:** Skip-empty guard (done in M1); Resolver Phase 2/4; Societies migrations or Own/Disown; FormRequests per section (that is M5); Route renames.

**Closure criteria:** No NULL write to NOT NULL columns from update path; zero values not dropped; tests green; optional items done or explicitly deferred; production verified.

---

## Milestone 3 — Resolver Parity & Financial Stability

**Scope:** Resolver parity tests and Phase 4 integration (BudgetValidationService redirect, blade arithmetic removal, rounding standardisation). No societies changes, no project update flow changes beyond what M1/M2 did.

**In scope:**
- Resolver Phase 2: Parity tests (ProjectFinancialResolver vs ProjectFundFieldsResolver for PhaseBased and Individual; approved vs non-approved).
- Resolver Phase 4: Redirect BudgetValidationService to resolver; remove duplicated formulas; standardise rounding; remove blade arithmetic where applicable.
- DP0030 remediation only if product-approved (doc currently says Do NOT implement).

**Out of scope for M3:** Societies schema or mapping; Data loss guard (M1); Validation/schema alignment (M2); Structural cleanup (M5).

**Closure criteria:** Parity tests passing; BudgetValidationService using resolver; blade arithmetic removed per plan; existing budget/export tests green; production verified.

---

## Milestone 4 — Societies V2 Structural Upgrade

**Scope:** Societies schema (unique name, null-safe display), users province backfill and NOT NULL, projects province_id/society_id and backfill, dual-write society_id + society_name, visibleToUser, Own/Disown, read-switch and cleanup. No resolver changes, no project section controller logic changes.

**In scope:**
- Run/apply societies migrations (drop composite unique, add unique(name)); fix General societies index null-safe province; align address validation max length.
- Run users:province-backfill; verify; make users.province_id NOT NULL.
- Add projects.province_id, projects.society_id (and users.society_id) migrations; backfill; dual-write in project/user create/update; replace hardcoded dropdowns with Society::visibleToUser().
- Implement Society::scopeVisibleToUser; POST own/disown routes; provincial list filter; Own/Disown UI.
- Read-switch (export/display from relation with society_name fallback); later cleanup (drop society_name columns) only after verification.

**Out of scope for M4:** Resolver; Data loss guard; Validation alignment; Structural cleanup (M5).

**Closure criteria:** Migrations run; backfills run and verified; dual-write and visibleToUser and Own/Disown working; exports/display use relation with fallback; production verified.

---

## Milestone 5 — Structural Cleanup & Documentation Sync

**Scope:** Transaction boundary improvements, FormRequests per section, UpdateProjectRequest documentation or validation, documentation updates, optional route/UI cleanups. No new data-loss or societies or resolver behaviour.

**In scope:**
- Single transaction boundary per orchestrated project save (Production Forensic Phase 2); replace remaining empty()-style drops if any.
- FormRequests per project type section (or document that sub-controllers receive unvalidated input).
- UpdateProjectRequest: document or add validation for type-specific sections.
- Documentation sync: RESOLVER_IMPLEMENTATION_TODO, BudgetPhaseStabilization, any drift notes.
- Optional: Edit form HTML5 required for draft; Route Phase 4–5 (non-blocking); IES show partials remediation; submit completeness clarification.

**Out of scope for M5:** New skip-empty logic (M1); New validation/schema for NOT NULL (M2); Resolver Phase 4 (M3); Societies mapping or Own/Disown (M4).

**Closure criteria:** Transaction boundary and FormRequest/docs updates done; docs aligned with code; optional items done or explicitly deferred; production verified.

---

## Dependency Order (No Overlap)

1. **Milestone 1** must be fully closed before Milestone 2 starts.  
2. **Milestone 2** must be fully closed before Milestone 3 starts.  
3. **Milestone 3** must be fully closed before Milestone 4 starts.  
4. **Milestone 4** must be fully closed before Milestone 5 starts.  
5. **Milestone 5** is last; no milestone may start in parallel with another.

---

*Reference: PENDING_TASKS_REPORT.md, SAFE_REFACTOR_STRATEGY.md, Societies_V2_Tasks_Status.md, RESOLVER_IMPLEMENTATION_TODO.md.*
