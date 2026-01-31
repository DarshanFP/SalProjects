# Editable Project Budget Sync – Documented Retrospective Review

**Role:** Principal Software Architect, Financial Systems Auditor, Post-Implementation Reviewer  
**Date:** 2026-01-29  
**Scope:** Causal analysis of whether the requirement _“As long as a project is editable, derived budget fields (overall, local, forwarded) MUST be kept in sync with type-specific budgets”_ was identified, and why the gap survived until real IIES editing exposed it.  
**Constraint:** Evidence from MD files only; no code changes; no blame; no architectural rewrites.

**Source documents (single continuous story):**  
Basic_Info_Fund_Fields_Mapping_Analysis.md, Budget_System_Wide_Audit.md, PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md, Approvals_And_Reporting_Budget_Integration_Recommendations.md, PHASE_0_VERIFICATION.md through PHASE_6_IMPLEMENTATION_SUMMARY.md, LOCAL_CONTRIBUTION_UPDATE_FAILURE_ANALYSIS.md.

---

## 1. Executive Summary

The requirement _“As long as a project is editable, derived budget fields (overall, local, forwarded) MUST be kept in sync with type-specific budgets”_ was **never stated as an explicit invariant** in the documentation. It was addressed **implicitly and only in event form**: “On save/update of type-specific budget, sync to `projects`.” The design therefore relied on **write-time sync** (sync when the user saves type budget), not on a **read-time or continuous invariant** (whenever the project is editable, derived fields shall reflect type-specific data).

Phase 2 implemented sync-on-type-save and pre-approval sync, guarded by feature flags and by “do not sync when approved.” As a result:

- The **mechanism** to keep derived fields in sync for editable projects (sync on type save) was designed and built.
- The **operational** choice to keep sync **off by default** (flags false until verification) and the **intentional** rule “do not sync approved projects” meant that in environments where flags were not enabled, editing and saving an IIES (or other type-specific) budget did **not** update `projects`. The gap was therefore **incomplete implementation of an event-based design**, combined with **no explicit requirement** that “editable ⇒ derived fields in sync” be true at all times.

The system remained **safe**: approval stayed the sole authority for sanctioned/opening; approved projects were not auto-mutated; resolver and sync are gated. The gap was **incompleteness** (derived fields not updated when sync was off or not run), not **corruption**. The gap surfaced during **real IIES editing** because the user saved IIES expenses and expected `projects.local_contribution` (and related fields) to reflect that save; the only path that could do so (sync-on-type-save) was disabled by default.

**Conclusion:** This was primarily an **assumption mismatch** (event-based “sync on save” vs invariant “always in sync when editable”) with an **intentional deferral** (sync off by default until Phase 2 verification). It was not a true oversight of the sync mechanism itself, but the invariant was never written down or enforced at read time.

**Inferred invariant:** Because no project can be edited once approved, "editable" implies "not approved." So the requirement _"If a project is editable, the project's budget fields must reflect the latest type-specific budget data"_ is satisfied by running sync on type save and sync before approval for non-approved projects. Enabling Phase 2 flags makes that invariant hold in practice.

---

## 2. Timeline of Understanding (Phase 0 → Phase 6)

| Phase      | Documented understanding                                                              | What was in scope                                                          | What was explicitly out of scope or not stated                                                                                 |
| ---------- | ------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| **0**      | Safeguards: feature flags, logging, guard (no sync when approved).                    | Config, logging, BudgetSyncGuard, BudgetAuditLogger.                       | No requirement that “editable ⇒ derived in sync”; only that sync, when run, is guarded.                                        |
| **1**      | Resolver is canonical and read-only; no writes to `projects`.                         | ProjectFundFieldsResolver, display of resolved values on Show.             | No statement that derived fields “must” match type-specific for editable projects; only that resolver can compute them.        |
| **2**      | Sync on type-specific budget save (when not approved) and sync before approval.       | BudgetSyncService, insertion points in IIES/IES/ILP/IAH/IGE and approval.  | Sync is **event-driven** (on save, before approval). No “whenever editable, ensure in sync” at read time. Flags default false. |
| **3**      | Post-approval budget edits blocked; reverted projects editable again.                 | canEditBudget, Phase 3 guards on General Info and type budget controllers. | No new requirement that “while editable, derived = type-specific”; Phase 2 sync on save still the only mechanism.              |
| **4**      | Reports/dashboards read only from `projects`; discrepancy logging read-only.          | Report show/edit notes, dashboard canonical fields only.                   | No recomputation of project-level fields from type-specific at report/dashboard level; historical reports immutable.           |
| **5**      | Verification that dashboards use canonical fields only.                               | No code change.                                                            | N/A.                                                                                                                           |
| **6 / 6a** | Backfill and admin reconciliation for **approved** projects with wrong stored values. | Admin correction, audit log.                                               | Does not address “editable project: keep in sync on every save”; that remains Phase 2 sync-on-save when flags on.              |

Across the timeline, the only **when** for keeping derived fields in sync was: (1) on save of type-specific budget, (2) immediately before approval. At no phase was it stated that “as long as a project is editable, derived budget fields MUST be kept in sync with type-specific budgets” as an invariant.

---

## 3. Where the Gap Was First Implicitly Visible

- **Basic_Info_Fund_Fields_Mapping_Analysis.md (Section 9.1 Option B):** “On **store/update** of type-specific budget … compute the five values and write them to `projects`.” This establishes **when** sync should happen (on store/update), not that derived fields **must** be in sync whenever the project is editable. So the gap is present from the start: the requirement is event-based, not invariant-based.

- **Budget_System_Wide_Audit.md (Section 5.3 “Where and when it should run”):** “On save/update of type-specific budget” and “Immediately before approval.” Again, sync is tied to **events**, not to the state “project is editable.”

- **PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN.md (Phase 2, Section 5.3):** “If `ProjectStatus::isApproved($project->status)` is true, **do not sync**.” So for **editable** projects (reverted, draft, etc.), sync-on-type-save is the intended mechanism; but the plan does not state that “editable ⇒ derived in sync” is a requirement. It only defines the trigger (type save) and the guard (not approved).

- **LOCAL_CONTRIBUTION_UPDATE_FAILURE_ANALYSIS.md** makes the gap **operationally visible**: for IIES, the only path that can set `projects.local_contribution` from type-specific data is `BudgetSyncService::syncFromTypeSave()`, which is **disabled by default** (feature flags off). So when a user **edits** an IIES project and saves expenses, derived fields are not updated unless Phase 2 sync is explicitly enabled.

The gap is therefore **implicitly visible** from the first design (Option B: sync on save) because the design never elevated “editable ⇒ in sync” to an explicit requirement; it became **observable** when real IIES editing occurred with sync off.

---

## 4. Why the Gap Was Not Dangerous Until Now

- **Approval is final authority:** `amount_sanctioned` and `opening_balance` are written only at approval. So derived fields being stale on `projects` did not cause wrong sanctioned/opening to be stored from normal edits.

- **Sync is gated:** Sync does not run for approved projects (by design), so approved data was not silently overwritten. No corruption of approved state.

- **Resolver is canonical and pure:** Phase 1 resolver only reads and computes; it does not write. Display (e.g. Show with resolved values) can show correct values without persisting them.

- **UI does not recompute budgets:** Report and dashboard logic read from `projects`; they do not derive project-level fund fields from type-specific tables. So the risk was **stale or zero** values (incomplete), not wrong computed values (corruption).

- **Historical reports are immutable:** Existing reports keep their stored values; Phase 4 only added read-only discrepancy notes and logging. So past reports were not altered by the gap.

The gap was **incompleteness**: after an edit/save of type-specific budget, `projects` could still hold 0 or old values if sync was off. That is only “dangerous” when (1) the user or a downstream process expects `projects` to reflect the last type-specific save (e.g. before approval, or on next report create), or (2) the project is approved later and approval reads those stale zeros. So the gap became **visible and problematic** when **real IIES editing** (save) happened and the expectation was that `projects` would be updated—which only sync-on-type-save could do, and it was off by default.

---

## 5. Exact Trigger That Exposed the Gap (IIES Editing)

**LOCAL_CONTRIBUTION_UPDATE_FAILURE_ANALYSIS.md** documents the trigger:

- **Project:** IIES-0025 (Individual - Initial - Educational support).
- **Flow:** User edited the project and saved; IIES expenses (and contribution-related fields in `project_IIES_expenses`) were updated successfully.
- **Observation:** `projects.local_contribution` was not updated.
- **Cause:** The only path that can set `projects.local_contribution` from IIES data is `BudgetSyncService::syncFromTypeSave()`, which runs after IIES expense save but only when `BudgetSyncGuard::canSyncOnTypeSave($project)` is true. That guard requires `resolver_enabled` and `sync_to_projects_on_type_save` to be true; both default to false. So sync did not run, and derived fields on `projects` stayed out of sync with type-specific data.

So the **exact trigger** was: **editing an IIES project and saving type-specific budget (expenses) with Phase 2 sync disabled (default flags).** The expectation (derived fields on `projects` reflect the save) was not met because the mechanism (sync-on-type-save) was intentionally gated and off by default.

---

## 6. Whether This Was: Intentional Deferral, Assumption Mismatch, or True Oversight

- **Intentional deferral:** Yes, in part. Sync-on-type-save was implemented but **disabled by default**; the plan (Phase 0) states that flags remain false until Phase 1/2 are verified. So “do not run sync until we’ve validated” was intentional. That deferral led to the situation where editing and saving IIES did not update `projects` when flags were not enabled.

- **Assumption mismatch:** Yes. The design assumed **event-based consistency**: “when the user saves type budget, sync runs (when flags on and not approved), so after save, derived fields are in sync.” It did **not** assume an **invariant**: “whenever the project is editable, the system SHALL ensure derived budget fields are in sync with type-specific budgets.” So anyone assuming the invariant (e.g. “after I save IIES expenses, Basic Info / projects should show the new local contribution”) would see a gap if sync was off. The documents never stated the invariant; they only stated the events (sync on save, sync before approval).

- **True oversight:** Partially. The **mechanism** (sync on type save) was not overlooked; it was designed and implemented. What was **not** written down was the **requirement** that “as long as a project is editable, derived budget fields MUST be kept in sync with type-specific budgets.” So the oversight is at the **requirements/documentation** level (invariant never stated), not at the **implementation** level (sync-on-save was implemented but gated and off by default).

**Conclusion:** A mix of **intentional deferral** (sync off until verified) and **assumption mismatch** (event-based design vs invariant “editable ⇒ in sync”), with a **documentation oversight** (invariant never explicitly stated). Not a pure implementation oversight.

---

## 7. Confirmation: “The System Remained Safe, But Incomplete”

- **Safe:** Approval remains the final authority; approved projects are not auto-mutated; resolver is canonical and pure; sync is gated (not approved, flags); UI does not recompute budgets; historical reports are immutable. No evidence of data corruption from the gap.

- **Incomplete:** For editable projects (e.g. reverted or pre-approval), when type-specific budget is saved and sync is disabled or not run, derived budget fields on `projects` (overall, local, forwarded) may remain stale or zero. Completeness would require either (1) sync-on-type-save to run whenever type budget is saved and project is not approved (and flags enabled), or (2) an explicit requirement and possibly read-time resolution for editable projects, as agreed by the organisation.

**Confirmed:** The system remained safe, but incomplete.

---

## 8. Recommended Next Step (Documentation-Level, Not Code)

1. **Document the invariant (if adopted):** If the organisation adopts the requirement that _“As long as a project is editable, derived budget fields (overall, local, forwarded) MUST be kept in sync with type-specific budgets,”_ add it explicitly to the budget alignment documentation (e.g. PHASE_WISE_BUDGET_ALIGNMENT_IMPLEMENTATION_PLAN or a dedicated “Budget invariants” section). Clarify whether “in sync” is satisfied by (a) sync-on-type-save when flags are on and project not approved, or (b) read-time resolution for editable projects, or both.

2. **Clarify flag policy:** Document in the same place when `sync_to_projects_on_type_save` (and related flags) are intended to be enabled (e.g. after Phase 2 verification, per environment), and that with them off, derived fields on `projects` will not be updated on type budget save for editable projects.

3. **Add to operational runbook:** In the runbook or deployment notes, state that for type-specific project types (IIES, IES, ILP, IAH, IGE), enabling Phase 2 sync-on-type-save is required if project-level fund fields on `projects` are to stay in sync with type-specific budget data when users edit and save; and that with sync off, Basic Info (from `projects`) may show zeros or stale values until sync is enabled and a save (or pre-approval sync) runs.

4. **Optional:** Add a short “Lessons learned” note to the Phase 2 or Review folder: “Editable-project sync was specified as event-based (sync on save) rather than as an invariant; the invariant was never written. For clarity and to avoid similar gaps, state invariants explicitly where they matter for product expectations.”

No code changes, no reopening of closed phases, and no architectural rewrites are recommended in this review.

---

_Document version: 1.0 – Editable project budget sync retrospective. Evidence-based; neutral and professional language. Strong systems fail at the edges of assumptions; this review identifies that edge._
