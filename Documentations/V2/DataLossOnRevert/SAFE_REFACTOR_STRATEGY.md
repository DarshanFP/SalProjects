# Safe Refactor Strategy: Data Loss on Revert / Update

**Purpose:** System-wide plan to eliminate accidental data loss when project is updated (especially after provincial revert) without breaking status workflows, reporting, exports, or other project types.  
**Prerequisite:** `DATA_LOSS_ON_REVERT_ANALYSIS.md`  
**Output:** Architecture overview, risk map, dependency map, step-by-step refactor strategy, file impact, order of implementation, and risk per step.  
**No code generated** — plan only.

---

# PHASE 1 — CONTEXT UNDERSTANDING (Summary from Documentation)

## 1.1 DATA_LOSS_ON_REVERT_ANALYSIS.md (Primary)

- **Revert does not delete data.** Provincial revert only updates `project.status` and logs to `activity_histories` / `project_status_histories`.
- **Data loss** occurs on **project update** (edit form submit). Section controllers use **delete-all-then-recreate-from-request**. If request has empty/missing data for a section, that section’s rows are deleted and none recreated.
- **Root cause:** Single full-form submit; each section controller runs with the same request. Missing or empty keys → destructive wipe.

## 1.2 What Problems Were Already Solved (from Other .md Files)

| Area | What was fixed |
|------|----------------|
| **PDF / Export** | IAH budget `->first()` → `->get()`; IGE variable `$IGEbudget` passed; Collection guards in blade partials; `ProjectDataHydrator` unifies PDF data loading (ExportController uses hydrator). |
| **Routes** | Duplicate route names resolved (`budgets.report`, compare, login/logout); `php artisan route:cache` succeeds. |
| **Budget domain** | Arithmetic centralized in `DerivedCalculationService`; `BudgetSyncGuard` locks budget edits when project is approved; no inline arithmetic in controllers; JS/backend parity guards. |
| **Draft / status** | Default status for new projects set to `draft`; `UpdateProjectRequest` relaxes `project_type` when `save_as_draft`; update sets status to DRAFT when `save_as_draft`; `prepareForValidation` merges existing `project_type` when draft and not filled. |
| **Attachment access** | Shared route group includes provincial, coordinator, general for project/report attachment view/download. |
| **Financial architecture** | `ProjectFinancialResolver` + strategies (PhaseBasedBudgetStrategy, DirectMappedIndividualBudgetStrategy); approval-safe behavior; no controller branching for financial resolution. |

## 1.3 Partial Fixes / Inconsistencies

| Item | Detail |
|------|--------|
| **LogicalFrameworkController** | `store()` **skips** when objectives empty (“No objectives data provided - skipping”); `update()` **does not skip** — it deletes all objectives then recreates from `$request->input('objectives', [])`. So empty request → full wipe in update path. |
| **UpdateProjectRequest** | Validates **only general project fields**. Type-specific section fields (e.g. IES/IIES beneficiaries, RST beneficiaries, budget rows) are **not** validated; section controllers operate on unvalidated input when invoked via `ProjectController@update`. |
| **Edit form** | Single form posts all sections. If partials are conditionally rendered or field names differ, some sections can be absent or empty. |
| **RESOLVER_IMPLEMENTATION_TODO.md** | Phase 2–4 checklist for resolver parity and controller arithmetic elimination; not all phases completed. Do not wire resolver into new behavior until parity tests pass. |

## 1.4 Architectural Patterns Already Introduced

- **Budget:** `BudgetSyncGuard::canEditBudget()`, `BudgetSyncService`, `ProjectFinancialResolver`, `DerivedCalculationService`; config flags `budget.resolver_enabled`, `budget.sync_to_projects_on_type_save`, `budget.restrict_general_info_after_approval`.
- **Status:** `ProjectStatusService` for submit/forward/approve/revert; `ProjectStatus::getEditableStatuses()` / `getSubmittableStatuses()`; `ProjectPermissionHelper::canEdit()` / `canSubmit()`.
- **Activity:** Unified `activity_histories`; `ProjectStatusService::logStatusChange()`; `ActivityHistoryService` for project/report updates and comments.
- **PDF data:** `ProjectDataHydrator::hydrate()` — single path for loading project data for PDF; uses same section controllers’ `show()` methods.

## 1.5 Warnings / TODO Notes from Docs

- **RESOLVER_IMPLEMENTATION_TODO:** “Do not wire into controllers until parity tests pass.”
- **Draft nullability:** Many type-specific tables have NOT NULL business columns; draft/partial save can still hit integrity errors if form omits those fields (backend merge helps only for fields in `UpdateProjectRequest::prepareForValidation()`).
- **Production forensic:** `User::$guarded = []` and sensitive field updates from request noted as risk (out of scope for this plan).
- **Migration (draft default):** `Schema::table()->default()->change()` may require `doctrine/dbal` on SQLite/PostgreSQL.

---

# PHASE 2 — CODEBASE ANALYSIS (Summary)

## 2.1 Project Update Flow

- **Entry:** `PUT /projects/{project_id}` → `ProjectController@update(UpdateProjectRequest $request, $project_id)`.
- **Transaction:** `DB::beginTransaction()` at start; `DB::commit()` after all section updates; `catch` → `DB::rollBack()`.
- **Sequence:**  
  1. `GeneralInfoController::update()` (project row only).  
  2. If institutional: `KeyInformationController::update()`, `LogicalFrameworkController::update()`, `SustainabilityController::update()`, `BudgetController::update()`, optional `AttachmentController::update()`.  
  3. Type-specific switch: one branch per project type (RST, CCI, IGE, LDP, IES, ILP, IAH, IIES, Edu-RUT, CIC, DP/NEXT PHASE) calling multiple section controllers’ `update()`.
- **Section controllers:** Receive same `$request` and `$project_id` (or `$project`). Most perform **bulk delete by project_id** (and optionally phase) then **create from request**. No shared “presence check” for section data before delete.

## 2.2 Delete-Then-Recreate and Bulk Deletes

| Controller | Table(s) | Scoped by | Transaction |
|------------|----------|-----------|-------------|
| BudgetController | project_budgets | project_id, phase | None (uses ProjectController’s) |
| LogicalFrameworkController | project_objectives (+ results, risks, activities, timeframes) | project_id | Own `DB::transaction()` inside update |
| SustainabilityController | project_sustainabilities | project_id | Own begin/commit (update upserts one row, no delete-all) |
| RST BeneficiariesAreaController | project_dp_rst_beneficiaries_areas | project_id | Own begin/commit in store/update |
| RST GeographicalAreaController | project_rst_geographical_areas | project_id | Own |
| RST TargetGroupController | project_rst_target_groups | project_id | Own |
| RST TargetGroupAnnexureController | project_rst_target_group_annexure | project_id | Own |
| RST InstitutionInfoController | project_rst_institution_info | project_id | Own |
| IGE (InstitutionInfo, BeneficiariesSupported, NewBeneficiaries, OngoingBeneficiaries, IGEBudget, DevelopmentMonitoring) | Respective IGE tables | project_id | Own |
| ILP (PersonalInfo, RevenueGoals, RiskAnalysis, StrengthWeakness, Budget, AttachedDocuments) | Respective ILP tables | project_id | Own |
| IAH (PersonalInfo, EarningMembers, HealthCondition, SupportDetails, BudgetDetails, Documents) | Respective IAH tables | project_id | Own |
| IES (PersonalInfo, FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, Expenses, Attachments) | Respective IES tables | project_id | Own |
| IIES (PersonalInfo, FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, FinancialSupport, Attachments, Expenses) | Respective IIES tables | project_id | Own |
| CCI (Achievements, AgeProfile, AnnexedTargetGroup, EconomicBackground, PersonalSituation, PresentSituation, Rationale, Statistics) | Respective CCI tables | project_id | Own |
| Edu-RUT (BasicInfo, TargetGroup, AnnexedTargetGroup) | Respective tables | project_id | Own |
| LDP (InterventionLogic, NeedAnalysis, TargetGroup) | Respective LDP tables | project_id | Own |
| CIC BasicInfo | project_cic_basic_info | project_id | Own |

**Note:** SustainabilityController does **not** delete-all; it fetches first or creates one and updates. BudgetController has **no** internal transaction; it runs inside ProjectController’s transaction.

## 2.3 Cross-Table Dependencies

- **Logical framework:** `project_objectives` → `project_results`, `project_risks`, `project_activities` → `project_timeframes`. Deletion of objectives (or cascade) removes children; current code deletes objectives first (no FK cascade from objectives to children in code; children may be deleted by cascade from objective_id in DB — verify migrations).
- **IIES/IES expenses:** Parent expense row + expense_details; controller deletes parent (and optionally details via relation).
- **Project destroy:** `ProjectController::destroy()` explicitly calls each section controller’s `destroy()` by project type, then `$project->delete()`. So full project delete is intentional and type-aware.

## 2.4 Foreign Key Constraints

- **project_budgets:** `project_id` → `projects.project_id` **onDelete('cascade')**.
- **project_objectives:** `project_id` → `projects.project_id` **onDelete('cascade')**.
- Other project_* tables: typically `project_id` FK to `projects`; migrations should be checked per table for cascade. **Application-level** delete in update flow is by controller, not by FK cascade (except on project delete, DB cascade may remove children).

## 2.5 Soft Deletes

- **None** in app (no `SoftDeletes` / `deleted_at` in project or section models). All deletes are hard deletes.

## 2.6 Transactions

- **ProjectController::update():** One outer `DB::beginTransaction()`; all section updates run inside it; single `DB::commit()` or `DB::rollBack()`.
- **Section controllers:** Many have their own `DB::beginTransaction()` / `commit()` / `rollBack()` in store/update/destroy. When invoked from `ProjectController@update`, this creates **nested** transactions (Laravel supports this). If a section controller rolls back, only its inner transaction; outer remains until ProjectController commits or rolls back.
- **BudgetController::update():** Does **not** start a transaction; relies entirely on ProjectController’s. So budget delete + create is atomic with the rest of the update.

## 2.7 Relationships (Project → section data)

- Project hasMany: budgets, attachments, objectives (logical_frameworks), sustainabilities, and type-specific relations (DPRSTBeneficiariesAreas, IAHBudgetDetails, etc.). All keyed by `project_id`.
- **ProjectDataHydrator** and **ExportController** load project then call section controllers’ `show()` or equivalent; they **read** from DB. No assumption that “section state = last submitted form”; they expect “section state = what’s in DB.”

## 2.8 Risks Identified

- **Sections updated without presence check:** If request does not contain a section (or contains empty array), controller still runs delete then create (0 rows) → data loss.
- **Shared request payload:** One request for all sections; form may not send all sections (tabs, JS, field names).
- **No validation of type-specific sections in UpdateProjectRequest:** Section controllers receive unvalidated input; empty/malformed data still triggers delete.
- **Hidden cascade:** DB FKs with onDelete cascade only on project delete; not on section update. So no hidden cascade during update.
- **Status transitions:** Revert does not trigger any update; status change does not auto-save project. No race from status service.
- **LogicalFrameworkController inconsistency:** store() skips when empty; update() does not — same pattern as other controllers (delete then recreate).

---

# PHASE 3 — SYSTEM IMPACT ANALYSIS

## 3.1 What Could Break If Update Logic Changes

- **Behaviour change:** If we add “skip section when request has no/empty data for that section,” then:
  - **Intended:** Preserve existing section data when form doesn’t send it.
  - **Possible unintended:** If front-end is ever changed to “explicitly send empty array to clear section,” that would no longer clear (we’d treat empty as “don’t touch”). Mitigation: document that “empty = preserve”; if “clear section” is required, add an explicit flag (e.g. `section_x_clear=1`) later.

## 3.2 What Depends on Current Delete-Recreate Behaviour

- **Nothing** in reporting, export, or dashboard **depends** on “section must be wiped when request is empty.” They all read from DB. After our change, DB will retain data when request is empty → exports/PDFs/dashboards see more data, not less.
- **Assumption today:** “After a successful update, section state = request state.” We change to “After update, section state = request state if request contained that section; else unchanged.” So the only “dependence” is the **mental model** of “submit form = replace all”; we are refining to “replace only when section is present in request.”

## 3.3 Reports

- **DPReport** (monthly) and other report models link to `project_id`. They do not depend on project section tables for structure; they may reference project for display. No structural dependency on delete-recreate.
- **Aggregated reports** (quarterly, half-yearly, annual) aggregate by project/report; no assumption that project sections were just replaced.

## 3.4 Exports

- **PDF:** `ExportController@downloadPdf` uses `ProjectDataHydrator::hydrate()`, which uses section controllers’ **show** (read) methods. Hydrator expects data in DB; if we preserve data when request is empty, PDF sees that preserved data. **No break.**
- **DOC:** `ExportController@downloadDoc` builds from `$project` and type-specific data; same as above. **No break.**

## 3.5 Activity History / Status Workflows

- **Activity history:** Logs status changes, comments, project/report updates. It does not depend on section content. **No break.**
- **Status workflows:** Submit, forward, approve, revert only change status and log. No dependency on section update behaviour. **No break.**

## 3.6 Dashboard Aggregations

- Dashboards typically count projects by status, list projects, show “reverted” lists. They do not assume section tables were just replaced on update. **No break.**

## 3.7 Other Project Types

- Each project type has its own set of section controllers. Adding a “skip when empty” guard per section is **per-controller**; we do not change the switch in ProjectController or the list of types. **No cross-type break** if each controller is updated consistently (same rule: “if my section is absent or empty, skip mutation”).

## 3.8 API Endpoints

- No separate public API documented; project update is web form. Same request/response contract; we only change when we **mutate** (skip instead of delete when empty).

---

# PHASE 4 — DESIGN: SAFE, COMPREHENSIVE PLAN

## 4.1 Principles

- Do **not** work in isolation: apply the same “presence/empty guard” pattern across all section controllers that currently delete-all-then-recreate.
- Do **not** break: status workflows, reporting, exports, activity history, dashboards, or other project types.
- Preserve **data integrity**: avoid destructive behaviour when request does not carry section data.
- **Production-safe:** assume live data; changes must be backward compatible and deployable with a clear rollback path.

---

## A) Immediate Safety Patch (Minimal Invasive Change)

**Goal:** Prevent data loss when a section’s data is **absent or empty** in the request. Do **not** change behaviour when the section **is** present with at least one valid item.

**Rule (per section controller):**

- Before performing **any** bulk delete for that section (by project_id / phase):
  - **If** the request does **not** contain the section key (e.g. `objectives`, `phases`, `project_area`, type-specific keys) **or** the value is empty (empty array or array of empty items where applicable):
    - **Skip** the entire section update (do not delete, do not create). Return success (or no-op) so ProjectController continues.
  - **Else:** Keep current behaviour (delete then recreate from request).

**Implementation notes:**

- **Definition of “empty”:** For collections: `$request->input('section_key', [])` is `[]` or all elements are empty (e.g. no objective text, no budget rows). For single-row sections: absence of key or null/empty string where “present” means “user sent this section.”
- **Where to implement:** Inside each section controller’s `update()` (and where applicable `store()` when used as update path, e.g. RST BeneficiariesAreaController). One consistent check at the top of the mutation block.
- **BudgetController:** Special case: section is `phases`; “empty” = `phases` missing or `phases[0]['budget']` missing or empty array. If empty, skip delete and create; leave existing budget rows for that project+phase unchanged.
- **LogicalFrameworkController:** Align with others: if `objectives` missing or empty (or no valid objective text), **skip** delete and recreate (same as store() already does for store path).
- **No change** to: GeneralInfoController (no bulk delete), SustainabilityController (already upsert, no delete-all), AttachmentController (file-based; keep current behaviour).
- **Backward compatibility:** When form sends full data (all sections), behaviour is unchanged. Only when payload is missing/empty for a section do we skip.

**Risk:** Low. Only adds a guard; does not change successful full-submit path.

---

## B) Medium-Term Structural Improvement

**Goal:** Make “section presence” explicit and consistent; reduce reliance on raw request shape.

**Options (choose one or combine):**

1. **Explicit section flags (optional)**  
   - Form (or front-end) sends e.g. `sections_included[]` or one flag per section (`update_objectives=1`, `update_budget=1`) when that section was actually rendered and submitted.  
   - Backend: only run section update if flag is set **and** data is present. Reduces ambiguity between “user cleared section” vs “section not in DOM.”

2. **Central “section presence” helper**  
   - One service or trait: `SectionUpdateGuard::shouldUpdateSection($request, $sectionKey, $emptyDefinition)`. Used by all section controllers so the rule is in one place and tests can target one unit.

3. **Form contract documentation**  
   - Document required request keys per project type for full update. Ensures front-end and any future clients send complete payloads when “replace all” is intended.

**Risk:** Medium (touches form/contract); can be phased after A is live.

---

## C) Long-Term Scalable Architecture

**Goal:** Safer, auditable updates and optional section-specific flows.

- **Diff-based updates (optional, per section):**  
  - For sections that are collections with stable identifiers (e.g. budget rows with id): **update** existing by id, **create** new rows, **delete** only rows whose id was explicitly marked removed in request.  
  - Requires: front-end sends ids for existing rows; backend validates ownership (project_id); clear rule for “removed” (e.g. `removed_budget_ids[]` or omit from list = remove).  
  - **Algorithm (high level):** (1) Load existing rows for project (and phase if applicable). (2) Request sends list of items with optional `id`. (3) Match by id; update matched; create without id; delete existing ids not in request (or in explicit “removed” list). (4) Validate and authorize: all ids must belong to project.  
  - **Deletion detection:** Explicit list or “missing id = delete” (document and enforce in validation).

- **Section-specific endpoints (optional):**  
  - e.g. `PUT /projects/{id}/sections/budget`, `PUT /projects/{id}/sections/objectives`.  
  - **Routing:** Under same auth/middleware as project update; route names e.g. `projects.sections.budget.update`.  
  - **Backward compatibility:** Keep existing `PUT /projects/{id}` full update; new endpoints are additive. Front-end can migrate to section PATCHes over time.  
  - **Phased migration:** Introduce endpoints; use them from edit page for “save this section only” if desired; full form still posts to main update.

- **Validation:**  
  - Move type-specific rules into dedicated FormRequests (e.g. `UpdateBudgetRequest` already exists; ensure all section updates that mutate data use validated input).  
  - For diff-based updates: validate that every id in request belongs to the project and (if applicable) phase.

**Risk:** High if done in one go; keep as long-term and incremental.

---

## D) Migration Considerations

- **No schema change required** for immediate safety patch (A). We are not adding columns or FKs.
- If later introducing **section versioning or audit tables**, that would be new migrations; out of scope for this plan.
- If **diff-based** approach needs **stable ids** for collection items: most section tables already have primary keys (id); ensure they are exposed and validated in request. No migration needed for that.

---

## E) Rollback Safety Strategy

- **Immediate patch (A):** Rollback = revert the guard in each section controller (re-run delete then create unconditionally). No data migration to revert. Deploy previous release.
- **Feature flag (optional):** Config e.g. `project.update_skip_empty_sections` default true. If set false, all section controllers behave as today (delete then create). Allows quick rollback without code deploy.
- **Monitoring:** After deploy, monitor for: (1) validation errors on update (should not increase); (2) reports of “my section didn’t update” (could indicate overly broad “empty” definition); (3) activity/error logs for section controllers.

---

## F) Testing Strategy

- **Unit:**  
  - For each section controller (or a shared guard): “When section key is absent, no delete and no create”; “When section key is empty array, no delete and no create”; “When section key has valid data, delete and create as before.”
- **Integration:**  
  - Full project update with **full** payload: all sections present → same DB state as before.  
  - Full project update with **one section omitted** (e.g. remove `objectives` from request): that section’s table unchanged; others updated.  
  - Revert then update with partial payload: no data loss in omitted sections.
- **Regression:**  
  - PDF/DOC export for each project type after update (full and partial).  
  - Status flow: submit → revert → update (draft) → submit again; no regression in status or visibility.
- **Existing tests:** Run full suite; Budget domain tests, formula parity, and resolver tests must remain green.

---

## G) Deployment Safety Checklist

- [ ] All new tests passing (unit + integration).
- [ ] Existing test suite green (including Budget, Export, Status).
- [ ] Config/default for “skip empty sections” (if used) set and documented.
- [ ] Rollback steps documented (revert commits or set config).
- [ ] Deploy during low-traffic window if preferred.
- [ ] Post-deploy: spot-check one project per type — edit, submit with one section left “empty” in request (e.g. via dev tools), confirm that section preserved.
- [ ] Monitor logs for new errors in section controllers.

---

# PHASE 5 — OUTPUT FORMAT

## 5.1 System Architecture Overview (Current State)

- **Single full-form update:** One PUT to `projects.update`; `UpdateProjectRequest` validates only general fields; `ProjectController@update` runs in one transaction and calls GeneralInfoController then institutional common sections then type-specific section controllers.
- **Section controllers:** Each owns one or more tables keyed by `project_id` (and sometimes phase). Pattern: bulk delete by project_id (and phase where relevant), then create from request. No shared guard for “section present.”
- **Read path:** Project show, PDF, DOC use same section controllers’ show methods or ProjectDataHydrator; they read from DB. No assumption that sections were just replaced.
- **Status:** ProjectStatusService and ProjectPermissionHelper drive submit/forward/approve/revert; revert only updates status and logs; no automatic project save.
- **Budget:** BudgetSyncGuard locks budget edits when approved; BudgetSyncService and resolver handle sync and display; DerivedCalculationService for arithmetic.

## 5.2 Risk Map

| Risk | Level | Mitigation |
|------|--------|------------|
| Empty definition too broad (skip when we should update) | Medium | Define “empty” per section (array empty vs “all items empty”); test with partial payloads; optional explicit section flags (medium-term). |
| Breaking full-form submit | Low | Guard only skips when section absent/empty; full payload still runs delete+recreate. |
| Breaking PDF/DOC/Reports | Low | They read from DB; we preserve more data when request empty. No break. |
| Breaking status workflow | Low | No change to status or revert logic. |
| Nested transactions | Low | Already in use; we only add early return in section controllers, no new transaction. |
| Other project types broken | Low | Same guard pattern per controller; type switch unchanged. |

## 5.3 Dependency Map

- **ProjectController@update** → GeneralInfoController, KeyInformationController, LogicalFrameworkController, SustainabilityController, BudgetController, AttachmentController, and all type-specific section controllers. All receive same request.
- **ExportController (PDF)** → ProjectDataHydrator → same section controllers’ **show** methods. No dependency on update behaviour.
- **ProjectController@destroy** → section controllers’ **destroy** by type. Unchanged; explicit delete flow.
- **ActivityHistory / ProjectStatusService** → only status and comments; no section content.
- **Dashboard / Reports** → project_id and status; no section content dependency.

## 5.4 Safe Refactor Strategy (Step-by-Step)

1. **Document “empty” rule per section**  
   For each controller, write down the exact request key(s) and condition that means “section not present” (e.g. `phases[0]['budget']` missing or `[]`; `objectives` missing or `[]` or no valid objective text).

2. **Introduce optional config**  
   Add `config('project.update_skip_empty_sections', true)` so rollback can disable without code revert.

3. **Implement guard in BudgetController**  
   In `update()`: if `phases` missing or empty or `phases[0]['budget']` empty, return early (no delete, no create). Else current behaviour. Unit test.

4. **Implement guard in LogicalFrameworkController**  
   In `update()`: if `objectives` missing or empty or no valid objective text, skip delete and recreate (early return). Else current behaviour. Unit test.

5. **Implement guard in RST controllers**  
   BeneficiariesArea, GeographicalArea, TargetGroup, TargetGroupAnnexure, InstitutionInfo: for each, define section key(s) and “empty”; if empty, skip; else delete then create. Test RST project type.

6. **Implement guard in IGE controllers**  
   Same pattern for IGE section controllers. Test IGE project type.

7. **Implement guard in ILP controllers**  
   Same pattern. Test ILP.

8. **Implement guard in IAH controllers**  
   Same pattern. Test IAH.

9. **Implement guard in IES controllers**  
   Same pattern. Test IES.

10. **Implement guard in IIES controllers**  
    Same pattern. Test IIES.

11. **Implement guard in CCI controllers**  
    Same pattern. Test CCI.

12. **Implement guard in Edu-RUT, LDP, CIC controllers**  
    Same pattern. Test each type.

13. **Integration tests**  
    Full update full payload; full update one section omitted; revert then partial update. Export PDF/DOC per type.

14. **Deploy and monitor**  
    Use deployment checklist; monitor logs and user reports.

15. **(Optional) Centralize guard**  
    Extract `SectionUpdateGuard` or trait and refactor controllers to use it (medium-term).

## 5.5 File-Level Impact List

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/BudgetController.php` | In `update()`: add presence/empty check for `phases` / `phases[0]['budget']`; skip delete+create if empty. |
| `app/Http/Controllers/Projects/LogicalFrameworkController.php` | In `update()`: add presence/empty check for `objectives`; skip delete+recreate if empty. |
| `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php` | In `update()`/`store()` path: skip if section arrays empty. |
| `app/Http/Controllers/Projects/RST/GeographicalAreaController.php` | Same. |
| `app/Http/Controllers/Projects/RST/TargetGroupController.php` | Same. |
| `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php` | Same. |
| `app/Http/Controllers/Projects/RST/InstitutionInfoController.php` | Same (single-row section: “empty” = key absent or empty value). |
| `app/Http/Controllers/Projects/IGE/*` (InstitutionInfo, IGEBeneficiariesSupported, NewBeneficiaries, OngoingBeneficiaries, IGEBudget, DevelopmentMonitoring) | Same pattern per controller. |
| `app/Http/Controllers/Projects/ILP/*` (PersonalInfo, RevenueGoals, RiskAnalysis, StrengthWeakness, Budget, AttachedDocuments) | Same pattern. |
| `app/Http/Controllers/Projects/IAH/*` (PersonalInfo, EarningMembers, HealthCondition, SupportDetails, BudgetDetails, Documents) | Same pattern. |
| `app/Http/Controllers/Projects/IES/*` (PersonalInfo, FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, Expenses, Attachments) | Same pattern. |
| `app/Http/Controllers/Projects/IIES/*` (PersonalInfo, FamilyWorkingMembers, ImmediateFamilyDetails, EducationBackground, FinancialSupport, Attachments, Expenses) | Same pattern. |
| `app/Http/Controllers/Projects/CCI/*` (Achievements, AgeProfile, AnnexedTargetGroup, EconomicBackground, PersonalSituation, PresentSituation, Rationale, Statistics) | Same pattern. |
| `app/Http/Controllers/Projects/EduRUT*`, `LDP/*`, `CICBasicInfoController`, `ProjectEduRUTBasicInfoController` | Same pattern. |
| `config/` (optional) | New key `project.update_skip_empty_sections` if used. |
| `tests/` | New unit tests for guard behaviour; new integration tests for full/partial update and export. |

**Not changed:** GeneralInfoController, SustainabilityController (already safe), AttachmentController (file-based), ProjectController (orchestration only), UpdateProjectRequest (optional later: add section validation), routes, ProjectStatusService, ProjectDataHydrator, ExportController.

## 5.6 Order of Implementation

1. Config (optional) and documentation of “empty” per section.  
2. BudgetController (high impact, single place, no type switch).  
3. LogicalFrameworkController (institutional common).  
4. RST (one project type end-to-end).  
5. IGE, ILP, IAH, IES, IIES (individual and institutional).  
6. CCI, Edu-RUT, LDP, CIC.  
7. Integration tests and deployment checklist.  
8. Deploy; then (optional) centralize guard and add section flags.

## 5.7 Estimated Risk per Step

| Step | Risk | Notes |
|------|------|--------|
| Config + docs | Low | No behaviour change. |
| BudgetController | Low | Single controller; well-defined “empty” (phases/budget). |
| LogicalFrameworkController | Low | Aligns update with store(); objectives key clear. |
| RST (5 controllers) | Low | Same pattern; test one type. |
| IGE (6 controllers) | Low | Same pattern. |
| ILP (6) | Low | Same pattern. |
| IAH (6) | Low | Same pattern. |
| IES (6) | Low | Same pattern. |
| IIES (7) | Low | Same pattern. |
| CCI (8) | Low | Same pattern. |
| Edu-RUT, LDP, CIC | Low | Same pattern. |
| Integration tests | Low | Add tests only. |
| Deploy | Low | With checklist and rollback. |
| Centralize guard (optional) | Medium | Refactor; ensure all controllers use same rule. |

---

**End of plan.** Proceed to implementation only after review and approval; implement in the order above and run tests at each step.
