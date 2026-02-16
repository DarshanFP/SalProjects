# Repository-Wide Pending Tasks Report

**Generated:** 2026-02-14  
**Scope:** All .md files across the repository; cross-verification with codebase; dependency and risk analysis; consolidated backlog.  
**Method:** Phase 1 document discovery → Phase 2 codebase verification → Phase 3 dependency/risk → Phase 4 backlog → Phase 5 output.  
**No code generated; analysis and recommendations only.**

---

# 1. Summary of Total Pending Tasks

| Category | Count | Notes |
|----------|-------|--------|
| **Refactor / Architectural debt** | 12+ | Resolver Phase 2–4, Financial Source of Truth, Budget phase stabilization, Data loss safe refactor |
| **Validation / Form contract** | 8+ | UpdateProjectRequest type-specific validation, draft HTML5 required, schema–validation alignment |
| **Societies / Mapping** | 15+ | Society→Project mapping, Own/Disown, visibleToUser, backfills, dual-write, cleanup |
| **Security / Data integrity** | 6+ | User mass assignment, attachment project-level auth, Production Forensic remediation |
| **Config / Feature flags** | 3+ | Resolver parity gate, update_skip_empty_sections, budget flags |
| **Route / Auth** | 4+ | Logout deployment checklist, Phase 4 role-prefix (non-blocking), compare route references |
| **Testing / Documentation drift** | 10+ | Parity tests for resolver, Data loss integration tests, IES show remediation, DP0030 remediation |
| **Migrations / Backfills** | 6+ | Societies unique(name) run, users province backfill, projects province_id/society_id |
| **Other (API, V3, IOES)** | 5+ | V3 Phase 2–4, Submit completeness validation, optional follow-ups |

**Total distinct pending items:** **~70+** (many are sub-tasks of larger initiatives).

**Critical / high-impact clusters:**

1. **Data loss on update** — Safe refactor (skip empty sections) not implemented; deployment checklist and integration tests pending.  
2. **Societies V2** — Society→Project mapping, Own/Disown, visibleToUser, backfills, dual-write not implemented.  
3. **Production Forensic remediation** — Phases 1–4 outlined but not implemented (schema/validation, PDF contracts, mail, transaction boundaries, FormRequests, view-contract maps).  
4. **Resolver full integration** — Phase 2 parity validation and Phase 4 (BudgetValidationService redirect, blade arithmetic removal) not completed; resolver is already wired in many controllers (implementation differs from “do not wire until parity” note).  
5. **Validation gaps** — UpdateProjectRequest does not validate type-specific sections; draft save can hit NOT NULL columns; empty() dropping zero values.

---

# 2. Tasks by Priority (High → Low)

## High priority (data integrity / security / production incidents)

| # | Task | Source doc(s) | Code status | Risk |
|---|------|----------------|-------------|------|
| 1 | **Data loss on update:** Implement “skip section when request empty” across all section controllers | DATA_LOSS_ON_REVERT_ANALYSIS.md, SAFE_REFACTOR_STRATEGY.md | NOT STARTED | High (user-visible data loss) |
| 2 | **User mass assignment:** Address `User::$guarded = []` and sensitive field updates from request | Production_Forensic_Review_10022026.md | NOT STARTED | High (security) |
| 3 | **Schema–validation alignment:** Prevent NULL write to `overall_project_budget` (and other NOT NULL columns) on draft/update | Production_Forensic_Review, Draft_Nullability | PARTIAL (prepareForValidation helps some fields) | High (integrity) |
| 4 | **Production Forensic Phase 1:** Align PDF export variable passing with show contract; graceful mail failure handling | Production_Forensic_Review_10022026.md | PARTIAL (IGE/IAH PDF fixed per PDF_and_Route_Fix) | Medium–High |
| 5 | **Attachment project-level authorization:** Add check that user can access project/report that owns attachment (defense in depth) | ATTACHMENT_ACCESS_REVIEW.md | NOT STARTED | Medium |

## Medium priority (architectural / consistency)

| # | Task | Source doc(s) | Code status | Risk |
|---|------|----------------|-------------|------|
| 6 | **Resolver Phase 2:** Parity validation (compare with ProjectFundFieldsResolver); unit tests for PhaseBased and Individual | RESOLVER_IMPLEMENTATION_TODO.md | NOT STARTED (resolver already wired) | Medium (drift from doc) |
| 7 | **Resolver Phase 4:** Redirect BudgetValidationService to resolver; remove duplicated formulas; remove phase from financial logic; standardize rounding | RESOLVER_IMPLEMENTATION_TODO.md | NOT STARTED | Medium |
| 8 | **Societies schema:** Drop composite unique `(province_id, name)`; add unique(name); run migration; fix General index null-safe province display | Societies_V2_Tasks_Status.md, PhasePlan_V2 | PARTIAL (migration file exists `enforce_unique_name_on_societies`) | Medium |
| 9 | **Users province:** Run `users:province-backfill`; verify no NULL province_id; then make `users.province_id` NOT NULL | Societies_V2_Tasks_Status.md | NOT STARTED (migration and command exist) | Medium |
| 10 | **Society→Project mapping:** Add projects.province_id, projects.society_id; backfill; dual-write society_id + society_name; replace hardcoded dropdowns with Society::visibleToUser() | Societies_V2_Tasks_Status.md, PhasePlan, Feasibility | NOT STARTED | Medium |
| 11 | **Own/Disown & visibleToUser:** Implement Society::scopeVisibleToUser; POST own/disown routes; provincial list filter; Own/Disown UI | Societies_V2_Tasks_Status.md | NOT IMPLEMENTED | Medium |
| 12 | **UpdateProjectRequest:** Validate type-specific section payloads when invoked from ProjectController@update (or document that sub-controllers receive unvalidated input) | IOES_0028_Data_Loss_Investigation, Production_Forensic | NOT STARTED | Medium |
| 13 | **DP0030 View/Edit budget discrepancy:** Remediation per recommended fix strategy (query mismatch, accessor mutation, blade arithmetic, formatting) | DP0030_View_Edit_Budget_Discrepancy_Finding.md | NOT STARTED (doc says “Do NOT implement” until approved) | Medium |
| 14 | **Production Forensic Phase 2–3:** Single transaction boundary per orchestrated save; replace empty() with explicit null/'' checks; standardize FormRequests per section | Production_Forensic_Review_10022026.md | NOT STARTED | Medium |
| 15 | **Edit form HTML5 required:** Remove or conditionally omit `required` in Blade for draft-relevant fields so “Save as draft” allows partial data without browser block | Phase1_2_Implementation_Report, Required_Attribute_Master_List | PARTIAL (backend relaxed; Blade still has required in places) | Medium |

## Lower priority (cleanup / optional / non-blocking)

| # | Task | Source doc(s) | Code status | Risk |
|---|------|----------------|-------------|------|
| 16 | **Logout deployment checklist:** Pre/deploy/post steps (remove GET logout if still present, route:cache, manual test) | LOGOUT_ROUTE_FIX_PLAN.md | PARTIAL (GET logout already removed in code; checklist is procedural) | Low |
| 17 | **Route Phase 4–5:** Role-prefix standardisation (activities, project actions); structural cleanup (non-blocking) | Route_Conflict_Audit_V2.md | NOT STARTED | Low |
| 18 | **Financial Source of Truth / Budget_Source_Audit:** Apply recommended resolver usage across export, provincial list, show, edit (many items) | Financial_Source_Of_Truth_Enforcement_Plan, Budget_Source_Audit_Report | PARTIAL (resolver used in many places; some fallbacks remain) | Low–Medium |
| 19 | **IES Show partials remediation:** Remove “Edit:” headings, read-only display, format_indian_currency, empty state (per IOES_Show_Partials_Edit_Label_Review) | IOES_Show_Partials_Edit_Label_Review.md | NOT STARTED | Low |
| 20 | **BudgetPhaseStabilization manual checklist:** Verify next_phase null, phase preservation (doc may be outdated vs Phase 2.4 completion) | BudgetPhaseStabilization_ImplementationPlan.md | UNCLEAR (Phase 2.4 complete; stabilization steps may be done or deferred) | Low |
| 21 | **Submit completeness validation:** Document or implement whether submit should validate project completeness (clarification question in Draft_Submit_Architecture_Discovery) | Draft_Submit_Architecture_Discovery.md | NOT IMPLEMENTED (documented as open) | Low |
| 22 | **GeneralController address validation:** Align `address` max length to 2000 (currently 255 in GeneralController) | Societies_V2_Tasks_Status.md | PARTIAL | Low |
| 23 | **SeedCanonicalSocietiesCommand:** Add “MISSIONARY SISTERS OF ST. ANN” to CANONICAL_NAMES if per Phase Plan | Societies_V2_Tasks_Status.md | NOT STARTED | Low |
| 24 | **V3 Fair Integration:** Phase 2–4 (data exposure, multi-tenancy, API architecture) | Integration-Architecture-And-Feasibility-Assessment.md | NOT STARTED | Low (future) |

---

# 3. Tasks by System Area

| Area | Pending tasks | Status summary |
|------|----------------|----------------|
| **Project update / data loss** | Skip-empty-sections refactor; transaction boundary; FormRequest per section; validation for type-specific payloads | Not started; safe refactor plan exists |
| **Budget / financial** | Resolver Phase 2 parity + Phase 4; BudgetValidationService redirect; blade arithmetic removal; DP0030 remediation; Budget phase stabilization verification | Resolver wired; Phase 2–4 checklist largely unchecked |
| **Societies** | Unique(name) migration run; province null-safe index; users backfill + NOT NULL; projects province_id/society_id; dual-write; visibleToUser; Own/Disown; cleanup society_name | Phase 1 address done; mapping and Own/Disown not started |
| **Status workflows** | No pending tasks that block status (revert/approve/submit unchanged) | — |
| **Reporting** | Resolver usage in aggregations (Phase 3B); report views use resolver | Partial |
| **Exports** | PDF/DOC variable contracts (Phase 1 forensic); resolver for financial sections | Partially addressed by PDF fix |
| **Activity history** | No pending tasks | — |
| **Attachments** | Project/report-level auth check (defense in depth) | Not started |
| **Permissions** | Societies visibleToUser; provincial delete (not implemented by design) | Not started for Own/Disown |
| **Dashboard aggregations** | Resolver in province/team totals (Phase 3B) | Partial |
| **Auth / routes** | Logout deployment checklist; Phase 4 role-prefix (non-blocking) | Logout fix applied in code |
| **Validation** | UpdateProjectRequest type-specific; draft required in Blade; schema–validation alignment; empty() for numerics | Gaps documented |
| **Testing** | Resolver parity tests; Data loss integration tests; Budget phase verification; IES show regression | Gaps documented |
| **Config** | project.update_skip_empty_sections (when implemented); budget.resolver_enabled / sync flags | Some in use |
| **Migrations** | enforce_unique_name_on_societies run; users province backfill run; projects province_id/society_id (future) | Migrations exist for societies/address/users province |

---

# 4. Hidden Risks Identified

1. **Resolver wired without parity tests:** RESOLVER_IMPLEMENTATION_TODO says “Do not wire into controllers until parity tests pass.” Codebase shows ProjectFinancialResolver used in ProjectController, ProvincialController, GeneralController, CoordinatorController, ExecutorController, Admin. BudgetReconciliationController still uses ProjectFundFieldsResolver. **Risk:** Behavioural drift between resolver and legacy resolver in edge cases; no automated parity guard.  
2. **Delete-then-recreate without “skip when empty”:** First save after revert (or any update with partial payload) can wipe sections. **Risk:** User-visible data loss; perception that “revert deleted data.”  
3. **empty() on numeric fields:** IES (and possibly others) drop rows when monthly_income is 0. **Risk:** Silent data loss for valid zero values.  
4. **User mass assignment:** `User::$guarded = []` with controllers updating role/status/password from request. **Risk:** Elevation of privilege or integrity if request is tampered.  
5. **Project type handler drift:** NEXT_PHASE_DEVELOPMENT_PROPOSAL has no edit branch; falls to default with log warning. **Risk:** Incomplete edit experience for that type.  
6. **Disabled inputs not submitted:** If sections are hidden and inputs disabled, data can be omitted from payload. **Risk:** Reinforces need for “skip when empty” and/or section-specific endpoints.  
7. **Documentation drift:** BudgetPhaseStabilization refers to “Phase 2.4 not started” while Budget_Domain_Lock_Complete marks Phase 2.4 complete; RESOLVER_IMPLEMENTATION_TODO says “no controller wired” but resolver is widely used. **Risk:** Future changes may follow outdated docs.

---

# 5. Documentation vs Code Drift Report

| Doc | Documented state | Code state | Drift |
|-----|------------------|------------|--------|
| RESOLVER_IMPLEMENTATION_TODO.md | Phase 1 skeleton; “Do not wire until parity” | Phase 1 done; resolver wired in many controllers | **IMPLEMENTED DIFFERENTLY** — wiring done before parity |
| Societies_V2_Tasks_Status.md | “Drop composite unique” and “add unique(name)” still needed | Migration `enforce_unique_name_on_societies` exists and does both | **PARTIAL** — migration exists; run status unknown |
| Route_Conflict_Audit_V2.md | “Logout partially applied — commented in web.php” | web.php has no GET logout; only comment and auth.php POST | **COMPLETE** — GET logout removed |
| BudgetPhaseStabilization_ImplementationPlan.md | “Phase 2.4 not started” | Phase 2.4 marked complete in Budget_Domain_Lock_Complete; DerivedCalculationService exists | **DRIFT** — Phase 2.4 is started/complete |
| LOGOUT_ROUTE_FIX_PLAN.md | Pre-deploy: “Remove custom GET logout from web.php (lines 53–56)” | Lines 53–56 are dashboard/profile; no GET logout there | **COMPLETE** — removal already done |
| DATA_LOSS / SAFE_REFACTOR_STRATEGY | Deployment checklist unchecked; “skip empty sections” not implemented | No guard in section controllers | **NOT STARTED** — docs are plan only |
| Production_Forensic_Review | Phase 1–4 remediation “outline only; not implemented” | Some Phase 1 items addressed by PDF fix; rest not done | **PARTIAL** |
| Societies PhasePlan §8.2 | 9 canonical names including “MISSIONARY SISTERS OF ST. ANN” | SeedCanonicalSocietiesCommand has 8 (excludes that name) | **DRIFT** — seeder differs from doc |

---

# 6. Suggested Execution Roadmap

**Assumption:** Production with live data; no breaking changes to status/reporting/exports.

**Wave 1 — Immediate safety (weeks 1–2)**  
1. Data loss: Implement “skip section when request empty” per SAFE_REFACTOR_STRATEGY (BudgetController, LogicalFramework, then RST → IGE → ILP → IAH → IES → IIES → CCI → Edu-RUT/LDP/CIC). Add config `project.update_skip_empty_sections` and integration tests.  
2. Schema–validation: Ensure overall_project_budget (and other NOT NULL) never receive NULL from update (prepareForValidation / merge already help; verify and extend if needed).  
3. Logout: Complete deployment checklist (verify route:cache, manual test per role) if not already done in production.

**Wave 2 — Data integrity and validation (weeks 3–4)**  
4. Production Forensic Phase 1: Confirm PDF/show variable contracts for all types; graceful mail failure handling.  
5. Replace empty() checks on numeric fields with explicit null/'' checks (IES and any similar controllers).  
6. Optional: Add attachment project-level auth check (defense in depth).

**Wave 3 — Societies and resolver (weeks 5–8)**  
7. Societies: Run unique(name) migration if not run; fix General index null-safe province; align address max length; run users:province-backfill; verify and make users.province_id NOT NULL.  
8. Resolver: Add Phase 2 parity tests (PhaseBased + Individual + approved); then Phase 4 (BudgetValidationService redirect, blade arithmetic removal) in small steps.  
9. Society→Project mapping: Add migrations and backfills (projects.province_id, society_id; users.society_id); dual-write and replace dropdowns; then Own/Disown and visibleToUser.

**Wave 4 — Structural and cleanup (ongoing)**  
10. Production Forensic Phase 2–3: Single transaction boundary; FormRequests per section.  
11. UpdateProjectRequest: Document or add validation for type-specific sections.  
12. Edit form: Remove or conditional required in Blade for draft (optional follow-up).  
13. DP0030 remediation when product approves.  
14. Route Phase 4–5 (non-blocking).  
15. IES Show partials remediation; Submit completeness clarification.

**Safe order:** Data loss patch first (user-visible); then schema/validation; then Societies and resolver; then structural refactors. Do not change status workflows or reporting contracts in Waves 1–2.

---

# 7. Suggested Test Coverage Additions

| Area | Suggested tests | Purpose |
|------|------------------|--------|
| **Data loss refactor** | Per section controller: “when section key absent, no delete”; “when section key empty array, no delete”; “when section present with data, delete and create as now.” Integration: full update full payload; full update one section omitted; revert then partial update. | Prevent regression and verify skip-empty behaviour |
| **Resolver** | Unit: ProjectFinancialResolver output vs ProjectFundFieldsResolver for same project (PhaseBased and Individual); approved vs non-approved. | Parity guard before/after Phase 4 |
| **Budget** | Phase preservation: edit phase 1, save; phase 2 rows still exist. next_phase null on create/update. | Align with BudgetPhaseStabilization |
| **Societies** | visibleToUser scope; Own/Disown updates province_id; dual-write society_id + society_name on project save. | Society mapping and visibility |
| **Validation** | UpdateProjectRequest with type-specific payload missing: section controllers receive empty; no exception. Draft save with project_type omitted: merge from DB and success. | Contract and draft behaviour |
| **Export** | For each project type: downloadPdf and downloadDoc after update (full and partial) — no exception, correct variables passed. | PDF/DOC contract stability |
| **Empty/zero values** | IES family member with monthly_income=0: row is created (no empty() drop). | Silent data loss prevention |

---

# Appendix A — Consolidated Backlog (Structured)

*Abbreviated; each item can be expanded with full source file and context.*

| ID | Category | Source MD | Original context | Code status | Recommended action | Risk | Effort | Order |
|----|----------|-----------|------------------|-------------|--------------------|------|--------|-------|
| T01 | Refactor | SAFE_REFACTOR_STRATEGY, DATA_LOSS | Skip section update when request empty for that section | NOT STARTED | Implement guard in all section controllers; add config and tests | High | Large | 1 |
| T02 | Security | Production_Forensic | User $guarded = []; sensitive fields from request | NOT STARTED | Restrict fillable/guarded; validate role/status in dedicated layer | High | Medium | 2 |
| T03 | Validation Gap | Production_Forensic, Draft_Nullability | NULL write to NOT NULL columns on update | PARTIAL | Extend prepareForValidation and validation rules | High | Small | 3 |
| T04 | Bug Fix | Production_Forensic Phase 1 | PDF variable contracts; mail failure handling | PARTIAL | Audit all types; add graceful mail handling | Medium | Medium | 4 |
| T05 | Security | ATTACHMENT_ACCESS_REVIEW | Project-level auth for attachment download | NOT STARTED | Add project/report access check in attachment controllers | Medium | Small | 5 |
| T06 | Architectural Debt | RESOLVER_IMPLEMENTATION_TODO | Phase 2 parity; Phase 4 redirect BudgetValidationService | PARTIAL (wired, no parity) | Add parity tests; then Phase 4 steps | Medium | Large | 6 |
| T07 | Migration Pending | Societies_V2 | unique(name); run backfill; NOT NULL users.province_id | PARTIAL | Run migration; run backfill; verify; add NOT NULL migration | Medium | Medium | 7 |
| T08 | Feature Parity | Societies_V2 | Society→Project mapping; dual-write; visibleToUser; Own/Disown | NOT STARTED | Implement per PhasePlan and Societies_V2_Tasks_Status | Medium | Large | 8 |
| T09 | Validation Gap | IOES_0028, Production_Forensic | UpdateProjectRequest type-specific validation | NOT STARTED | Document or add FormRequests per section | Medium | Large | 9 |
| T10 | Refactor | DP0030 | View/Edit budget discrepancy remediation | NOT STARTED | Implement when approved (doc says Do NOT implement yet) | Medium | Medium | 10 |
| T11 | Refactor | Production_Forensic Phase 2–3 | Transaction boundary; empty()→explicit checks; FormRequests | NOT STARTED | Single transaction; replace empty(); introduce section FormRequests | Medium | Large | 11 |
| T12 | Documentation Drift | Multiple | Resolver wired vs “do not wire”; Phase 2.4 started vs “not started” | IMPLEMENTED DIFFERENTLY | Update RESOLVER_IMPLEMENTATION_TODO and BudgetPhaseStabilization | Low | Small | 12 |
| T13 | Config Flag Cleanup | SAFE_REFACTOR_STRATEGY | project.update_skip_empty_sections | NOT STARTED | Add when implementing T01 | Low | Small | 13 |
| T14 | Testing Gap | RESOLVER, SAFE_REFACTOR, BudgetPhaseStabilization | Parity tests; integration tests; phase preservation | NOT STARTED | Add per Section 7 above | Medium | Medium | 14 |
| T15 | Bug Fix | Production_Forensic | empty() dropping zero (e.g. IES monthly_income) | NOT STARTED | Replace with explicit null/'' check | Medium | Small | 15 |
| T16 | Refactor | Route_Conflict Phase 4–5 | Role-prefix standardisation; structural cleanup | NOT STARTED | Optional; non-blocking | Low | Medium | 16 |
| T17 | Feature Parity | LOGOUT_ROUTE_FIX_PLAN | Deployment checklist | PARTIAL | Execute checklist in production | Low | Small | 17 |
| T18 | Validation Gap | Phase1_2_Implementation_Report | Edit form required attributes for draft | PARTIAL | Remove or conditional required in Blade | Low | Medium | 18 |
| T19 | Refactor | IOES_Show_Partials | IES show read-only remediation | NOT STARTED | Apply checklist in Section 5 of that doc | Low | Medium | 19 |
| T20 | Documentation Drift | Societies PhasePlan | SeedCanonicalSocietiesCommand 8 vs 9 names | PARTIAL | Add missing name if product agrees | Low | Small | 20 |

---

# Appendix B — Source MD Files Scanned (Key)

- Documentations/V2/DataLossOnRevert/DATA_LOSS_ON_REVERT_ANALYSIS.md  
- Documentations/V2/DataLossOnRevert/SAFE_REFACTOR_STRATEGY.md  
- Documentations/V2/Budgets/Overview/RESOLVER_IMPLEMENTATION_TODO.md  
- Documentations/V2/Budgets/Overview/FINANCIAL_DOMAIN_V2_ARCHITECTURE.md  
- Documentations/V2/Budgets/Overview/CONTROLLER_ARITHMETIC_AUDIT_REPORT.md  
- Documentations/V2/Budgets/Overview/WAVE2_DISPLAY_ARITHMETIC_AUDIT_REPORT.md  
- Documentations/V2/Budgets/DP0030_View_Edit_Budget_Discrepancy_Finding.md  
- Documentations/V2/Societies/Societies_V2_Tasks_Status.md  
- Documentations/V2/Societies/Mapping/Society_Project_Mapping_PhasePlan_V2.md  
- Documentations/V2/Societies/Societies_CRUD_And_Access_Audit.md  
- Documentations/V2/ERRORS10022026/Production_Forensic_Review_10022026.md  
- Documentations/V2/Conflicts/Route_Conflict_Audit_V2.md  
- Documentations/V2/Conflicts/Route_Conflict_Implementation_Plan.md  
- Documentations/V2/LOGOUT_ROUTE_FIX_PLAN.md  
- Documentations/V2/ATTACHMENT_ACCESS_REVIEW.md  
- Documentations/V2/DRAFT_IMPLEMENTATION/Phase1_2_Implementation_Report.md  
- Documentations/V2/DRAFT_SUBMIT_DISCOVERY/Draft_Submit_Architecture_Discovery.md  
- Documentations/V2/DRAFT_FRONTEND_AUDIT/Required_Attribute_Master_List.md  
- Documentations/V2/IOES/IOES_Show_Partials_Edit_Label_Review.md  
- Documentations/V2/IOES/1/IOES_0028_Data_Loss_Investigation.md  
- Documentations/V2/Implementations/Phase_2/BudgetPhaseStabilization_ImplementationPlan.md  
- Documentations/V2/Implementations/Phase_2/2.4/Budget_Domain_Lock_Complete.md  
- Documentations/V2/Conflicts/Wrong Sources/Financial_Source_Of_Truth_Enforcement_Plan.md  
- Documentations/V2/Conflicts/Wrong Sources/Budget_Source_Audit_Report.md  
- Documentations/V3-Fair-Integration/Integration-Architecture-And-Feasibility-Assessment.md  

*(Additional .md files under Documentations/, tests/, and root were included in pattern scan; key sources above are those that contributed explicit TODOs, checklists, or phase plans.)*

---

**End of report.** Use this document as the single backlog for incomplete architectural decisions and pending work; update as tasks are completed or reprioritised.
