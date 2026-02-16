# Societies Mapping — Folder Summary

**Read-only documentation audit.** No existing files were modified.

---

## 1. Folder Overview

**Path:** `Documentations/V2/Societies/Mapping`

**Contents:** Three markdown documents that together describe the migration from string-based `society_name` to relational `society_id` (FK) on projects and users, the introduction of `projects.province_id`, and an ownership model (global vs province-owned societies with Own/Disown).

**Document evolution:** Feasibility (V1) → Phase Plan (V2, Revisions 3–5) → Implementation Summary (execution-oriented). Later docs supersede or refine earlier ones; Phase Plan V2 is the authoritative source for schema, ownership, and migration order.

---

## 2. File Summaries

### 2.1 Society_Project_Mapping_Feasibility_V1.md

| Aspect | Content |
|--------|--------|
| **Purpose** | Feasibility audit: replace hardcoded society dropdowns with dynamic data and replace `society_name` (string) with `society_id` (FK) on projects and user flows. No implementation. |
| **Main concepts** | Current architecture (tables, models, controllers, views); risks (data loss, typo, province ambiguity, reports); hardcoded value audit; migration strategy; backfill by name + province; rollback; required controller/validation/view changes. |
| **Entities / models** | `projects` (society_name), `users` (society_name, province_id), `societies` (province_id, name, unique(province_id, name)), report tables (quarterly, annual, half_yearly, dp_reports). Models: Project (OldProjects), User, Society. |
| **Relationships** | Project → user (for province); society per (province_id, name); reports denormalize society_name from project. No society_id on projects/users at start. |
| **Business rules** | Provincial sees societies in their province only; General sees all; backfill resolves society by province + normalized name; unmatched leave society_id NULL. |
| **Technical constraints** | FK onDelete('set null') for society_id; index on society_id; N+1 avoidance via eager load. |
| **TODO / open** | Optional: add society_id to report tables later; legacy_society_name column option. |

---

### 2.2 Society_Project_Mapping_PhasePlan_V2.md

| Aspect | Content |
|--------|--------|
| **Purpose** | Full phase plan (Revision 5): migrate to society_id + province_id with **global + province-owned societies**, unique(name), Own/Disown, **users.province_id normalization first**, production-safe rollout. Analysis and plan only. |
| **Main concepts** | Global vs province-owned society (province_id NULL vs set); global unique(name); exclusive ownership; Own/Disown (no direct transfer); societies and provinces immutable; dual-write; read switch; cleanup; Users Province Normalization before projects.province_id backfill. |
| **Entities / models** | Province (immutable), Society (global or province-owned, unique name, immutable), Project (province_id required, society_id nullable), User (province_id, society_id). |
| **Relationships** | Province has many societies, projects, users; Society has many projects, users, belongs to 0 or 1 province; Project belongs to province, user, optional society; User belongs to province, optional society. No cascade-on-delete. |
| **Business rules** | Society name globally unique (DB); ownership exclusive; Own only when province_id IS NULL; Disown only by owning province; transfer = Disown then Own; visibility = global + own province for provincial; reports store historical society_name snapshot. |
| **Technical constraints** | unique(name) on societies; province_id nullable on societies; projects.province_id NOT NULL after backfill; atomic Own/Disown updates with WHERE; verification gate (users province_id NULL = 0) before projects backfill. |
| **TODO / open** | Pre-migration duplicate names must be resolved before unique(name); optional cleanup of society_name and users.province. |

---

### 2.3 Society_Project_Mapping_Implementation_Summary.md

| Aspect | Content |
|--------|--------|
| **Purpose** | Execution-oriented summary: phase-wise steps, indexing, invariants, risk status, go-live criteria. Defers full detail to Phase Plan V2 (Revision 4). |
| **Main concepts** | Phase 0 (audit) → Phase 1 (schema) → Phase 2 (backfill) → Phase 3 (constraints) → Phase 4 (dual write) → Phase 5 (read switch) → Phase 6 (cleanup); final state and go-live readiness. |
| **Entities / models** | Province, Society, Project, User — aligned with Phase Plan (immutable provinces/societies; global/owned society; province_id + society_id on project/user). |
| **Relationships** | Same as Phase Plan: Province → societies/projects/users; Society → projects/users; no cascade. |
| **Business rules** | Same invariants: global unique name, exclusive ownership, no delete of societies/provinces, reports as historical snapshot. |
| **Technical constraints** | Schema steps (drop composite unique, add unique(name), nullable province_id; add province_id/society_id and indexes); backfill then NOT NULL. |
| **TODO / open** | References “Revision 4” only; Phase Plan has since added Revision 5 (users province normalization). |

---

## 3. Cross-File Observations

### 3.1 Overlapping Concepts

- **Migration goal:** All three agree on moving from `society_name` (string) to `society_id` (FK) on projects and users.
- **Backfill:** All describe backfill from name (with typo normalization); Feasibility uses province + name; Phase Plan/Implementation use **name only** after global unique(name).
- **Dual-write and read fallback:** Phase Plan and Implementation both specify writing both society_id and society_name and reading via relation with society_name fallback during transition.
- **Reports:** All state reports keep society_name as denormalized/historical snapshot; display from project relation or legacy field during transition.
- **Provincial vs General:** Visibility and validation (provincial: societies in scope; General: all or scoped by selected province) are consistent across Phase Plan and Feasibility; Implementation assumes same.

### 3.2 Conflicting or Divergent Definitions

| Topic | Feasibility V1 | Phase Plan V2 / Implementation |
|-------|----------------|--------------------------------|
| **Society uniqueness** | Unique per (province_id, name); same name allowed in different provinces. | **Global** unique(name); one row per name. |
| **Backfill resolution** | By **province_id + normalized name**. | By **name only** (no province); single society per name. |
| **Society delete / FK** | Recommends nullable society_id + onDelete('set null'). | Societies **cannot be deleted**; no cascade; no set null on delete. |
| **Users.province_id** | Users already have province_id (nullable) and province relation. | **Users Province Normalization** is a prerequisite phase: add users.province_id, backfill from users.province (string), verify, then NOT NULL **before** projects.province_id backfill. |
| **Migration order** | Add society_id to projects → backfill → users → code → drop society_name. | (1) Societies constraints + nullable province_id, (2) **Users province_id** full normalization, (3) Projects province_id then society_id, (4) Users society_id, (5) Backfill, (6) Dual-write, (7) Read switch, (8) Cleanup. |

### 3.3 Repeated Business Rules

- Provincial may only see/select societies visible to them (global + own province in V2; province-scoped in V1).
- Unmatched society_name during backfill → leave society_id NULL; keep or log for review.
- Typo "ST. ANNS'S SOCIETY, VISAKHAPATNAM" → normalize to "ST. ANN'S SOCIETY, VISAKHAPATNAM".
- Validation: society_id exists and (for provincial) within visible scope.
- Reports do not auto-update when a society is renamed.

### 3.4 Relationship Inconsistencies

- **Feasibility:** Backfill and scoping assume society is tied to a province (composite unique). Phase Plan removes that: society is global or owned by one province, but **name** is globally unique; relationship to province is ownership, not name-scoping.
- **Implementation Summary** does not mention users.province_id normalization; Phase Plan (Revision 5) makes it a mandatory step before projects.province_id. Execution order in Implementation (Phase 1 schema for projects/users) does not explicitly list “Users province_id add + backfill + verify + NOT NULL” as a prior step.

### 3.5 Terminology Mismatches

- **Feasibility:** “Society in a province,” “province scope,” “societies from their province.”
- **Phase Plan:** “Global society” (province_id NULL) vs “province-owned”; “Own” / “Disown”; “visible to user” (global + own province).
- **Implementation:** Uses “global” and “exclusive ownership” but does not define Own/Disown; reader must refer to Phase Plan.

### 3.6 Missing or Under-specified Components

- **Implementation Summary** does not describe Own/Disown routes, controller actions, or UI (buttons on society index); Phase Plan does.
- **Feasibility** does not define the 9 canonical global societies (Phase Plan lists them in Section 8.2) or the “insert as global first, then Own” approach.
- **Dropdown visibility** by context (project create vs executor vs provincial) is detailed in Phase Plan (Section 5); Feasibility and Implementation only summarize “scope by province” or “visible to user.”
- **Verification gate** (users with province_id NULL = 0 before proceeding) is explicit in Phase Plan only; Implementation’s “audit” and “backfill verified” do not spell out this gate.

---

## 4. System-Level Summary

- **Overall architectural intent:** Move from string-based society references and hardcoded dropdowns to a relational model with a single societies table, globally unique names, and explicit ownership (global vs province-owned). Denormalize province onto projects for reporting and grouping without user joins.
- **Domain model:** **Province** (immutable, no delete) → **Society** (immutable, unique name, either global or owned by one province) → **Project** (province_id required, society_id optional) and **User** (province_id, society_id optional). Reports hold a historical snapshot of society name.
- **Problem the mapping layer solves:** (1) Eliminate free-text society_name and hardcoded options. (2) Remove province ambiguity when the same name existed in multiple provinces (by making name globally unique). (3) Support reporting and dashboards via projects.province_id and society_id without joining users. (4) Govern which societies provincial users see (global + own province) and who can “own” a society (Own/Disown).
- **Architecture style:** **Centralized** (one societies table); **type-driven** (global vs province-owned by province_id NULL vs set); **controller-based** (Own/Disown, visibility checks, validation); **table-segregated** (projects, users, reports keep their own province_id/society_id or society_name snapshot). Not polymorphic.
- **Scalability assumptions:** Index strategy (province_id, society_id, composite) for filtering and analytics; no cascade deletes; reports stay denormalized; backfill in batches; dual-write period before dropping society_name.
- **Potential future risk areas:** Pre-migration duplicate names blocking unique(name); users.province_id not normalized before projects backfill; version drift between Implementation Summary and Phase Plan Revision 5; no audit trail for Own/Disown; reports not linked by society_id if future reporting needs it.

---

## 5. Architectural Risks (Detail)

1. **Pre-migration duplicate names:** Under composite unique(province_id, name), the same name can exist in multiple provinces. Switching to unique(name) requires a data audit and resolution (merge/rename/remove) or the migration fails. Documented in Phase Plan 3.2 and 9.1; Feasibility does not assume global uniqueness.
2. **Users province_id prerequisite:** If projects.province_id is backfilled from project.user->province_id, then users must have province_id populated and verified (NOT NULL) first. Implementation Summary does not list “Users Province Normalization” as a distinct phase; following only Implementation could lead to wrong order.
3. **Version drift:** Implementation Summary references “Revision 4”; Phase Plan is at “Revision 5” (Users Province Normalization). Readers could miss the prerequisite phase if they rely only on the Implementation Summary.
4. **Own/Disown and concurrency:** Phase Plan describes atomic updates and rowsAffected check (409 on conflict); no formal concurrency or audit logging is specified; “acceptable governance” may not suffice for strict compliance needs.
5. **Report tables:** All docs accept historical society_name in reports. If future requirements need report-by-society_id or referential integrity for reports, that is out of scope and not designed here.

---

## 6. Recommendations (Documentation Only)

1. **Align Implementation Summary with Revision 5:** Add an explicit “Phase 0.5” or “Prerequisite: Users Province Normalization” (add province_id, backfill, verify NULL=0, NOT NULL) before projects.province_id, and reference Phase Plan V2 Revision 5.
2. **Single source of truth for migration order:** State in Implementation Summary that the canonical order is in Phase Plan Section 9 (Revision 5) and list the full sequence (societies → users province_id → projects province_id → society_id on projects/users → backfill → dual-write → read switch → cleanup).
3. **Resolve Feasibility vs Phase Plan for readers:** Add a short note in Feasibility (or in this folder summary) that the “name + province” backfill and composite unique design were superseded by Phase Plan’s global unique(name) and name-only backfill; keep Feasibility as historical context.
4. **Glossary or cross-reference:** Add a one-page glossary in this folder for terms: global society, province-owned, Own, Disown, visibleToUser, dual-write, read switch, verification gate.
5. **Explicit “no delete” policy:** Ensure all three docs state that societies and provinces cannot be deleted and that no cascade-on-delete is used from them; Feasibility’s onDelete('set null') recommendation should be explicitly marked superseded by “no delete” policy.
6. **Testing and monitoring:** Phase Plan’s testing checklist (Section 15) and monitoring plan (Section 16) could be referenced from Implementation Summary under “Go-Live Readiness” so that execution docs point to concrete verification steps.

---

*End of Folder Summary — documentation synthesis only; no code or file changes.*
