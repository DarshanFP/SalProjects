# Wave-Wise Implementation Plan

**Primary references:**  
`Documentations/V2/FinalFix/MASTER_EXECUTION_CHECKLIST.md`  
`Documentations/V2/FinalFix/PENDING_TASKS_REPORT.md` (or `Documentations/V2/PENDING_TASKS_REPORT.md`)

**Execution mode:** Solo developer; Milestone 1 and 2 MUST NOT overlap; each milestone fully closed before next; production database live.

**Constraints:** Do not break status workflows; do not break reporting or exports; prioritize safety over speed. No code generated in this document.

---

# PHASE 1 — IMPACT ANALYSIS

## Milestone 1 — Data Integrity Shield (skip-empty-sections)

### 1.1 Impacted files

| File | Change type |
|------|-------------|
| `app/Http/Controllers/Projects/BudgetController.php` | Add presence/empty check in `update()`; skip delete+create when `phases` or `phases[0]['budget']` absent/empty. |
| `app/Http/Controllers/Projects/LogicalFrameworkController.php` | Add presence/empty check in `update()`; skip delete+recreate when `objectives` absent/empty or no valid objective text. |
| `app/Http/Controllers/Projects/RST/BeneficiariesAreaController.php` | Skip mutation when section arrays empty (in `update()` / `store()` path used by update). |
| `app/Http/Controllers/Projects/RST/GeographicalAreaController.php` | Same. |
| `app/Http/Controllers/Projects/RST/TargetGroupController.php` | Same. |
| `app/Http/Controllers/Projects/RST/TargetGroupAnnexureController.php` | Same. |
| `app/Http/Controllers/Projects/RST/InstitutionInfoController.php` | Same (single-row: empty = key absent or empty value). |
| `app/Http/Controllers/Projects/IGE/InstitutionInfoController.php` | Same pattern. |
| `app/Http/Controllers/Projects/IGE/IGEBeneficiariesSupportedController.php` | Same. |
| `app/Http/Controllers/Projects/IGE/NewBeneficiariesController.php` | Same. |
| `app/Http/Controllers/Projects/IGE/OngoingBeneficiariesController.php` | Same. |
| `app/Http/Controllers/Projects/IGE/IGEBudgetController.php` | Same. |
| `app/Http/Controllers/Projects/IGE/DevelopmentMonitoringController.php` | Same. |
| `app/Http/Controllers/Projects/ILP/PersonalInfoController.php` | Same. |
| `app/Http/Controllers/Projects/ILP/RevenueGoalsController.php` | Same. |
| `app/Http/Controllers/Projects/ILP/RiskAnalysisController.php` | Same. |
| `app/Http/Controllers/Projects/ILP/StrengthWeaknessController.php` | Same. |
| `app/Http/Controllers/Projects/ILP/BudgetController.php` | Same. |
| `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` | Same (section presence for update path). |
| `app/Http/Controllers/Projects/IAH/IAHPersonalInfoController.php` | Same. |
| `app/Http/Controllers/Projects/IAH/IAHEarningMembersController.php` | Same. |
| `app/Http/Controllers/Projects/IAH/IAHHealthConditionController.php` | Same. |
| `app/Http/Controllers/Projects/IAH/IAHSupportDetailsController.php` | Same. |
| `app/Http/Controllers/Projects/IAH/IAHBudgetDetailsController.php` | Same. |
| `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` | Same. |
| `app/Http/Controllers/Projects/IES/IESPersonalInfoController.php` | Same. |
| `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php` | Same. |
| `app/Http/Controllers/Projects/IES/IESImmediateFamilyDetailsController.php` | Same. |
| `app/Http/Controllers/Projects/IES/IESEducationBackgroundController.php` | Same. |
| `app/Http/Controllers/Projects/IES/IESExpensesController.php` | Same. |
| `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/IIESPersonalInfoController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/IIESFamilyWorkingMembersController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/IIESImmediateFamilyDetailsController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/EducationBackgroundController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/FinancialSupportController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` | Same. |
| `app/Http/Controllers/Projects/IIES/IIESExpensesController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/AchievementsController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/AgeProfileController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/AnnexedTargetGroupController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/EconomicBackgroundController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/PersonalSituationController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/PresentSituationController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/RationaleController.php` | Same. |
| `app/Http/Controllers/Projects/CCI/StatisticsController.php` | Same. |
| `app/Http/Controllers/Projects/EduRUTTargetGroupController.php` | Same. |
| `app/Http/Controllers/Projects/EduRUTAnnexedTargetGroupController.php` | Same. |
| `app/Http/Controllers/Projects/ProjectEduRUTBasicInfoController.php` | Same. |
| `app/Http/Controllers/Projects/LDP/InterventionLogicController.php` | Same. |
| `app/Http/Controllers/Projects/LDP/NeedAnalysisController.php` | Same. |
| `app/Http/Controllers/Projects/LDP/TargetGroupController.php` | Same. |
| `app/Http/Controllers/Projects/CICBasicInfoController.php` | Same. |
| `config/project.php` or equivalent (optional) | New key `update_skip_empty_sections` (default true). |
| `tests/Unit/Projects/SectionUpdateGuardTest.php` (or per-controller) | New: guard behaviour when section absent/empty vs present. |
| `tests/Feature/Projects/ProjectUpdateDataPreservationTest.php` | New: full update full payload; full update one section omitted; revert then partial update. |

**Not changed:** GeneralInfoController, SustainabilityController, KeyInformationController, AttachmentController, ProjectController (orchestration), UpdateProjectRequest, routes, ProjectStatusService, ExportController, ProjectDataHydrator.

### 1.2 Impacted controllers

All section controllers listed in 1.1 that are invoked from `ProjectController@update` for institutional or type-specific sections: BudgetController, LogicalFrameworkController, RST (5), IGE (6), ILP (6), IAH (6), IES (6), IIES (7), CCI (8), Edu-RUT (3), LDP (3), CIC (1).

### 1.3 Affected project types

All project types that use these sections: Development Projects, NEXT PHASE DEVELOPMENT PROPOSAL, Residential Skill Training Proposal 2, CHILD CARE INSTITUTION, Institutional Ongoing Group Educational (IGE), Livelihood Development Projects (LDP), Individual - Ongoing Educational (IES), Individual - Livelihood (ILP), Individual - Access to Health (IAH), Individual - Initial Educational (IIES), Rural-Urban-Tribal (Edu-RUT), PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER (CIC).

### 1.4 Database tables affected

No schema changes. Behavioural change only: when request section is empty, **no** delete and **no** create for these tables: `project_budgets`, `project_objectives` (and results, risks, activities, timeframes), `project_dp_rst_beneficiaries_areas`, `project_rst_geographical_areas`, `project_rst_target_groups`, `project_rst_target_group_annexure`, `project_rst_institution_info`, all IGE/ILP/IAH/IES/IIES/CCI/Edu-RUT/LDP/CIC type-specific tables (see SAFE_REFACTOR_STRATEGY).

### 1.5 Regression risks

- **Full-form submit with all sections present:** Must behave exactly as today (delete then recreate). Mitigation: guard only skips when section is absent or empty; full payload still runs existing logic.
- **Export/PDF/DOC:** Read from DB; preserving data when request empty only adds or keeps rows. No regression if guard is correct.
- **Status workflows:** Unchanged; no dependency on section content.
- **Reporting/aggregations:** Unchanged; they read from same tables.

### 1.6 Migration requirements

None.

### 1.7 Required test coverage

- Unit: For each section (or shared guard), test “section key absent → no delete”; “section key empty array → no delete”; “section present with data → delete and create as now.”
- Integration: Full project update with full payload (all sections) → DB state unchanged from current behaviour. Full project update with one section omitted (e.g. remove `objectives`) → that section’s rows unchanged. Revert then update with partial payload → no data loss in omitted sections.
- Export: Spot-check PDF/DOC for one project per type after partial update (section preserved).

---

## Milestone 2 — Validation & Schema Alignment

### 2.1 Impacted files

| File | Change type |
|------|-------------|
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | prepareForValidation: ensure NOT NULL columns (e.g. overall_project_budget, project_type) never receive null when save_as_draft or partial submit (merge from existing project). |
| `app/Http/Controllers/Projects/GeneralInfoController.php` | Ensure update payload never writes NULL to NOT NULL columns (already partially done via validated(); extend if any key can be missing). |
| `app/Http/Controllers/Projects/IES/IESFamilyWorkingMembersController.php` | Replace empty() check for monthly_income (and similar) with explicit check so 0 is not dropped. |
| Other controllers with empty() on numerics (if found) | Same. |
| `app/Http/Controllers/Projects/ExportController.php` | Optional: audit variable passing to all PDF partials; align with show contract. |
| `app/Http/Controllers/Auth/PasswordResetLinkController.php` or mail handling | Optional: graceful handling for mail transport failure (user message + logging). |
| `app/Http/Controllers/Projects/AttachmentController.php`, IIES/IAH/IES attachment controllers | Optional: add project/report-level access check before serving file. |

### 2.2 Impacted controllers

GeneralInfoController, UpdateProjectRequest (form request), IESFamilyWorkingMembersController (and any other with empty() on numerics); optionally ExportController, PasswordResetLinkController, attachment-related controllers.

### 2.3 Affected project types

All (validation and schema alignment); IES explicitly for empty() fix; all types for PDF contract if audited.

### 2.4 Database tables affected

No new migrations. Writes to `projects` (and possibly type-specific tables) must not violate NOT NULL. No new columns.

### 2.5 Regression risks

- **Draft save:** Must still allow partial data where business rules permit; prepareForValidation must not overwrite intentional clears with stale data for fields that are allowed to be empty.
- **Zero values:** Changing empty() to explicit null/'' must not introduce new required validation that blocks valid drafts.

### 2.6 Migration requirements

None for M2.

### 2.7 Required test coverage

- Unit: UpdateProjectRequest with missing overall_project_budget / project_type and save_as_draft → merged from project; no NULL in validated. IES family member with monthly_income=0 → row created.
- Integration: Draft save with minimal fields; update with zero in numeric field; export PDF for one project per type (no missing variable).

---

## Milestone 3 — Resolver Parity & Financial Stability

### 3.1 Impacted files

| File | Change type |
|------|-------------|
| `tests/Unit/Budget/ProjectFinancialResolverParityTest.php` (new) | Compare ProjectFinancialResolver vs ProjectFundFieldsResolver for PhaseBased and Individual; approved vs non-approved. |
| `app/Services/BudgetValidationService.php` | Redirect to ProjectFinancialResolver (or use resolver output) per Phase 4; remove duplicated formulas. |
| Blade views that perform budget arithmetic | Remove inline arithmetic; use resolved values passed from controller. |
| `app/Http/Controllers/Projects/BudgetController.php` (if applicable) | Use resolver for any display or validation path touched. |
| Config / rounding | Standardise rounding where specified in Phase 4. |
| `Documentations/V2/Budgets/Overview/RESOLVER_IMPLEMENTATION_TODO.md` | Update checklist to reflect parity done and Phase 4 done. |

### 3.2 Impacted controllers

BudgetValidationService (service); controllers that call BudgetValidationService or render budget in Blade; any controller still using sum('amount_sanctioned') / sum('overall_project_budget') for business logic (replace with resolver per Phase 3B).

### 3.3 Affected project types

All project types that use budget (phase-based and individual); reporting/aggregation views that display financial totals.

### 3.4 Database tables affected

None (read-only for resolver; no schema change).

### 3.5 Regression risks

- **Display/export:** Resolver output must match or improve on current display for all types and statuses (draft, submitted, approved, reverted).
- **BudgetValidationService:** Validation rules must remain equivalent when backed by resolver.
- **Reporting/aggregations:** Totals must remain correct or improve (no regression).

### 3.6 Migration requirements

None.

### 3.7 Required test coverage

- Unit: Resolver parity tests (PhaseBased, Individual, approved, non-approved); BudgetValidationService behaviour unchanged after redirect.
- Integration: Project show, export PDF/DOC, provincial list, coordinator list: financial figures match or match previous resolver behaviour.
- Existing: All budget domain tests (DerivedCalculationService, formula parity, rounding parity) must remain green.

---

## Milestone 4 — Societies V2 Structural Upgrade

### 4.1 Impacted files

| File | Change type |
|------|-------------|
| `database/migrations/2026_02_13_161757_enforce_unique_name_on_societies.php` | Run if not already run. |
| `resources/views/general/societies/index.blade.php` | Null-safe province display: `$society->province?->name ?? 'Global'`. |
| `app/Http/Controllers/GeneralController.php` | Address validation `address` → `max:2000`; society create/update for dual-write and visibility (later step). |
| `app/Console/Commands/UsersProvinceBackfillCommand.php` | Run; verify no NULL province_id; then migration to make users.province_id NOT NULL. |
| New migrations | projects.province_id, projects.society_id; users.society_id; composite index (province_id, society_id) on projects. |
| Backfill commands | projects.province_id from user; projects.society_id from society_name; users.society_id. |
| `app/Models/Society.php` | scopeVisibleToUser($user). |
| `app/Http/Controllers/ProvincialController.php` | ownSociety, disownSociety; societies list filter visibleToUser. |
| `routes/web.php` | POST provincial/society/{id}/own, disown. |
| `app/Http/Requests/Projects/StoreProjectRequest.php`, `UpdateProjectRequest.php`, GeneralInfo requests | society_id nullable|exists:societies,id; provincial visibility. |
| `app/Http/Controllers/Projects/GeneralInfoController.php` (or ProjectController) | Accept society_id; dual-write society_name from relation. |
| `resources/views/projects/partials/general_info.blade.php`, `Edit/general_info.blade.php` | Replace hardcoded options with dynamic Society::visibleToUser(); name="society_id". |
| `app/Http/Controllers/Projects/ExportController.php` | `$project->society->name ?? $project->society_name`. |
| Provincial/General society views | Own/Disown buttons; society create/edit with province. |

### 4.2 Impacted controllers

GeneralController, ProvincialController, ProjectController or GeneralInfoController, ExportController; society-related requests and views.

### 4.3 Affected project types

All (project create/edit and export display society); Societies CRUD (General and Provincial).

### 4.4 Database tables affected

`societies` (unique name; province_id nullable already); `users` (province_id NOT NULL after backfill; society_id added); `projects` (province_id, society_id added). Later: drop society_name columns (post read-switch verification).

### 4.5 Regression risks

- **Project create/edit:** Society dropdown must show correct list; save must persist society_id and society_name (dual-write); existing projects without society_id must still display name (fallback).
- **Export:** Must show society name (from relation or society_name fallback).
- **Provincial society list:** visibleToUser must not hide societies that provincial should see; Own/Disown must update province_id correctly.

### 4.6 Migration requirements

Run enforce_unique_name_on_societies; add users.province_id NOT NULL (after backfill); add projects.province_id, projects.society_id, users.society_id; composite index; later drop society_name (optional, after verification).

### 4.7 Required test coverage

- Unit: Society::scopeVisibleToUser; Own/Disown updates province_id.
- Integration: General/Provincial society CRUD; project create/edit with society_id; export shows society name; backfill idempotency and counts.

---

## Milestone 5 — Structural Cleanup & Documentation Sync

### 5.1 Impacted files

| File | Change type |
|------|-------------|
| `app/Http/Controllers/Projects/ProjectController.php` | Ensure single transaction boundary for update (no partial commit on section failure); document or refactor. |
| Section controllers (if any still using empty() or swallowing errors) | Replace empty() where applicable; consistent throw or return so orchestrator can roll back. |
| FormRequests (new or existing) | Per-section FormRequests for type-specific data, or document that sub-controllers receive unvalidated input. |
| `app/Http/Requests/Projects/UpdateProjectRequest.php` | Document type-specific validation (or add optional validation). |
| `Documentations/V2/Budgets/Overview/RESOLVER_IMPLEMENTATION_TODO.md` | Update: Phase 2–4 done; wiring noted. |
| `Documentations/V2/Implementations/Phase_2/BudgetPhaseStabilization_ImplementationPlan.md` | Update: Phase 2.4 completed reference. |
| Optional: Blade edit partials | Remove or conditional required for draft; IES show read-only remediation. |
| Optional: routes | Phase 4–5 role-prefix (non-blocking). |

### 5.2 Impacted controllers

ProjectController (transaction boundary); any section controller with inconsistent error handling; optional attachment/IES views.

### 5.3 Affected project types

All (transaction boundary); optional items affect IES show and edit form.

### 5.4 Database tables affected

None.

### 5.5 Regression risks

- **Transaction boundary:** If one section fails, entire update must roll back; no partial commit. Current behaviour (nested transactions) must be preserved or improved.
- **Documentation:** Docs must match code so future changes don’t follow outdated instructions.

### 5.6 Migration requirements

None.

### 5.7 Required test coverage

- Integration: Project update with one section throwing → no partial DB change (rollback). Existing suite green.
- Optional: IES show read-only; draft save without HTML5 required block.

---

# PHASE 2 — ATOMIC IMPLEMENTATION PLAN

## Milestone 1 — Data Integrity Shield

### 2.1.1 Sequential, testable tasks (strict order)

1. **Task 1.1** — Add config key `project.update_skip_empty_sections` (default true) and document “empty” rule per section in a single doc or comment. No behaviour change. **Verify:** Config readable; doc exists.
2. **Task 1.2** — BudgetController: In `update()`, before delete, check `phases` and `phases[0]['budget']`; if absent or empty, return early (no delete, no create). **Verify:** Unit test: empty → no delete; full payload → same as before.
3. **Task 1.3** — LogicalFrameworkController: In `update()`, before delete, check `objectives`; if absent or empty or no valid objective text, return early. **Verify:** Unit test same pattern.
4. **Task 1.4** — RST: BeneficiariesAreaController, GeographicalAreaController, TargetGroupController, TargetGroupAnnexureController, InstitutionInfoController — add guard in update/store path. **Verify:** RST project type: full payload → same; omit one section → that section preserved.
5. **Task 1.5** — IGE: All six IGE section controllers — same guard. **Verify:** IGE project full/partial update.
6. **Task 1.6** — ILP: All six ILP section controllers — same guard. **Verify:** ILP project full/partial update.
7. **Task 1.7** — IAH: All six IAH section controllers — same guard. **Verify:** IAH project full/partial update.
8. **Task 1.8** — IES: All six IES section controllers — same guard. **Verify:** IES project full/partial update.
9. **Task 1.9** — IIES: All seven IIES section controllers — same guard. **Verify:** IIES project full/partial update.
10. **Task 1.10** — CCI: All eight CCI section controllers — same guard. **Verify:** CCI project full/partial update.
11. **Task 1.11** — Edu-RUT, LDP, CIC: Remaining section controllers — same guard. **Verify:** One project per type full/partial update.
12. **Task 1.12** — Integration tests: Full update full payload; full update one section omitted; revert then partial update. **Verify:** All green.
13. **Task 1.13** — Deployment checklist and rollback doc written. **Verify:** Checklist and rollback steps in FinalFix.

### 2.1.2 Rollback plan (M1)

- **Code rollback:** Revert all controller changes (remove guard; restore unconditional delete then create). Deploy previous release.
- **Config rollback:** If feature flag used, set `project.update_skip_empty_sections` to false and redeploy config cache (if applicable). No DB rollback (no migrations).

### 2.1.3 Production deployment checklist (M1)

- [ ] All new unit and integration tests passing.
- [ ] Existing test suite (including Budget, Export) green.
- [ ] Config key added and default documented.
- [ ] Deploy during low-traffic window.
- [ ] After deploy: spot-check one project per type — edit, submit with one section omitted (e.g. via dev tools remove section key), confirm that section preserved.
- [ ] Monitor logs for errors in section controllers.

### 2.1.4 Verification steps before milestone closure (M1)

- [ ] Every section controller in scope has guard; config (if used) is documented.
- [ ] Unit tests for guard behaviour (absent/empty vs present) green.
- [ ] Integration tests: full payload → unchanged behaviour; one section omitted → section preserved; revert then partial update → no data loss.
- [ ] PDF/DOC export for at least one project per type after partial update — no error, data correct.
- [ ] Rollback and deployment checklist signed off.

---

## Milestone 2 — Validation & Schema Alignment

### 2.2.1 Sequential, testable tasks (strict order)

1. **Task 2.1** — Audit UpdateProjectRequest and GeneralInfoController: list all NOT NULL columns that can receive value from request; ensure prepareForValidation and merge prevent NULL for those columns when key is missing. **Verify:** Test draft save with missing overall_project_budget and project_type → no NULL write.
2. **Task 2.2** — IESFamilyWorkingMembersController: Replace condition that uses empty() on monthly_income (and similar numerics) with explicit check (e.g. allow 0). **Verify:** Unit/integration: row with monthly_income=0 is created.
3. **Task 2.3** — Scan other section controllers for empty() on numeric fields; fix same way. **Verify:** No valid zero dropped.
4. **Task 2.4** — (Optional) ExportController: Audit variable names passed to every project-type PDF partial; align with show. **Verify:** No undefined variable for any type.
5. **Task 2.5** — (Optional) Password reset: Graceful handling for mail failure (user message + log). **Verify:** No 500 when mail fails.
6. **Task 2.6** — (Optional) Attachment controllers: Add project/report access check before serving file. **Verify:** Unauthorized project/report returns 403.
7. **Task 2.7** — Integration tests for schema alignment and zero value. **Verify:** All green.

### 2.2.2 Rollback plan (M2)

- Revert request and controller changes; redeploy. No migrations to roll back.

### 2.2.3 Production deployment checklist (M2)

- [ ] Tests green; no NULL write test passing; zero-value test passing.
- [ ] Deploy; verify draft save and update with zero values in staging/production.
- [ ] Monitor for integrity errors in logs.

### 2.2.4 Verification steps before milestone closure (M2)

- [ ] No NOT NULL column receives NULL from update path in tests and spot-check.
- [ ] Zero values (e.g. monthly_income=0) persisted where applicable.
- [ ] Optional items done or explicitly deferred and documented.

---

## Milestone 3 — Resolver Parity & Financial Stability

### 2.3.1 Sequential, testable tasks (strict order)

1. **Task 3.1** — Add ProjectFinancialResolverParityTest: compare resolver output vs ProjectFundFieldsResolver for PhaseBased project, Individual project, approved, non-approved. **Verify:** Parity or documented acceptable difference.
2. **Task 3.2** — Fix any parity failure (resolver or legacy) so tests pass. **Verify:** Parity tests green.
3. **Task 3.3** — BudgetValidationService: Identify all call sites and current formulas; plan redirect to resolver (or resolver output). **Verify:** Plan documented.
4. **Task 3.4** — Implement BudgetValidationService redirect (or use resolver) without changing validation outcome. **Verify:** Existing validation tests green; manual validation behaviour unchanged.
5. **Task 3.5** — Remove duplicated formulas from BudgetValidationService (or other services) per Phase 4. **Verify:** No duplicate logic; tests green.
6. **Task 3.6** — Blade: Remove inline budget arithmetic where specified in Phase 4; pass resolved values from controller. **Verify:** Display/export unchanged or improved.
7. **Task 3.7** — Rounding standardisation (if in scope). **Verify:** Budget rounding parity test green.
8. **Task 3.8** — Update RESOLVER_IMPLEMENTATION_TODO.md. **Verify:** Checklist reflects Phase 2 and 4 done.

### 2.3.2 Rollback plan (M3)

- Revert BudgetValidationService and Blade changes; keep parity tests. Redeploy. Resolver remains wired (no need to unwire).

### 2.3.3 Production deployment checklist (M3)

- [ ] Parity tests and all budget domain tests green.
- [ ] Deploy; verify project show, export PDF, provincial/coordinator lists show correct financials.
- [ ] Verify validation (e.g. budget total) still works on submit/edit.

### 2.3.4 Verification steps before milestone closure (M3)

- [ ] Parity tests passing; BudgetValidationService uses resolver; blade arithmetic removed per plan.
- [ ] Reporting and exports show correct financials; no regression.

---

## Milestone 4 — Societies V2 Structural Upgrade

### 2.4.1 Sequential, testable tasks (strict order)

1. **Task 4.1** — Run migration enforce_unique_name_on_societies (if not run). **Verify:** unique(name) exists; composite unique dropped.
2. **Task 4.2** — General societies index: null-safe province display. **Verify:** Global societies (province_id null) show “Global”.
3. **Task 4.3** — GeneralController address validation → max:2000. **Verify:** Validation accepts long address.
4. **Task 4.4** — Run users:province-backfill; verify no user has NULL province_id. **Verify:** Count of NULL province_id = 0.
5. **Task 4.5** — Migration: users.province_id NOT NULL. **Verify:** Migration runs.
6. **Task 4.6** — Migrations: projects.province_id, projects.society_id (nullable, FK, index); users.society_id (nullable, FK); composite index on projects. **Verify:** Migrations run.
7. **Task 4.7** — Backfill projects.province_id from user; backfill projects.society_id from society_name (match by name). **Verify:** Counts and spot-check.
8. **Task 4.8** — Backfill users.society_id. **Verify:** Counts and spot-check.
9. **Task 4.9** — Society model: scopeVisibleToUser($user). **Verify:** Unit test.
10. **Task 4.10** — Add society_id to project and user requests; dual-write in GeneralInfoController/ProjectController and General/Provincial user store/update. **Verify:** Save project with society_id → society_name set from relation.
11. **Task 4.11** — Replace hardcoded society dropdowns with Society::visibleToUser() and society_id. **Verify:** Create/edit project shows correct list; save persists society_id and society_name.
12. **Task 4.12** — ExportController: society name from relation with fallback. **Verify:** Export shows correct name.
13. **Task 4.13** — Routes and ProvincialController: ownSociety, disownSociety; provincial societies list filter visibleToUser; Own/Disown UI. **Verify:** Own/Disown updates province_id; list scoped.
14. **Task 4.14** — Integration tests and production verification. **Verify:** No regression; new behaviour correct.

### 2.4.2 Rollback plan (M4)

- Migrations: roll back in reverse order (society_id, province_id on projects/users; NOT NULL revert; unique name revert). Backfills cannot be fully rolled back without backup; prefer forward fix. Dual-write and UI: revert code; keep migrations if already run and backfill done (read can stay with fallback).

### 2.4.3 Production deployment checklist (M4)

- [ ] Backup production DB before migrations and backfills.
- [ ] Run migrations on staging; run backfills; verify counts.
- [ ] Deploy code (dual-write, visibleToUser, Own/Disown); verify society dropdown and export.
- [ ] Run backfills on production during maintenance window; deploy code; verify.

### 2.4.4 Verification steps before milestone closure (M4)

- [ ] unique(name) on societies; users.province_id NOT NULL; projects have province_id and society_id populated where applicable.
- [ ] Dual-write and read with fallback working; visibleToUser and Own/Disown working.
- [ ] Export and project create/edit show correct society.

---

## Milestone 5 — Structural Cleanup & Documentation Sync

### 2.5.1 Sequential, testable tasks (strict order)

1. **Task 5.1** — ProjectController@update: Confirm single transaction boundary; if any section can commit independently, refactor so that one failure rolls back all. **Verify:** Test: one section throws → no partial commit.
2. **Task 5.2** — Replace any remaining empty()-based numeric drops in section controllers (if found). **Verify:** No valid zero dropped.
3. **Task 5.3** — Document or add FormRequests per section for type-specific validation; document in UpdateProjectRequest. **Verify:** Doc or code reflects validation boundary.
4. **Task 5.4** — Update RESOLVER_IMPLEMENTATION_TODO.md and BudgetPhaseStabilization (Phase 2.4 completed). **Verify:** Docs match code.
5. **Task 5.5** — (Optional) Edit form: remove or conditional required for draft; IES show read-only remediation; Route Phase 4–5. **Verify:** Per optional scope.

### 2.5.2 Rollback plan (M5)

- Revert transaction boundary and FormRequest/docs changes; redeploy.

### 2.5.3 Production deployment checklist (M5)

- [ ] Tests green; transaction boundary test green.
- [ ] Deploy; verify project update rollback on section failure.
- [ ] Publish doc updates.

### 2.5.4 Verification steps before milestone closure (M5)

- [ ] Single transaction boundary confirmed; docs updated; optional items done or deferred and documented.

---

# PHASE 3 — TEST STRATEGY

## Milestone 1

| Type | Required | Edge cases |
|------|----------|------------|
| Unit | Guard: section absent → no delete; section empty array → no delete; section present with data → delete and create as now. | Single-row sections (e.g. InstitutionInfo): key missing vs key null vs key empty string. |
| Integration | Full update full payload; full update one section omitted; revert then partial update. | RST, IGE, ILP, IAH, IES, IIES, CCI, Edu-RUT, LDP, CIC each at least one type. |
| Cross-module | Export PDF/DOC after partial update — no error; data preserved. | All project types in export matrix. |

## Milestone 2

| Type | Required | Edge cases |
|------|----------|------------|
| Unit | UpdateProjectRequest: missing NOT NULL keys with save_as_draft → merged from project. IES: monthly_income=0 → row created. | Draft with only project_type; update with 0 in numeric field. |
| Integration | Draft save; update with zero; export PDF. | IES and any type with numeric optional. |
| Cross-module | No integrity violation in logs after draft/update. | — |

## Milestone 3

| Type | Required | Edge cases |
|------|----------|------------|
| Unit | Resolver parity: PhaseBased, Individual, approved, non-approved. BudgetValidationService after redirect. | Edge statuses; zero amounts. |
| Integration | Project show, export PDF/DOC, provincial list: financial figures. | All types; approved vs draft. |
| Cross-module | Existing budget domain tests (DerivedCalculationService, formula parity, rounding) green. | — |

## Milestone 4

| Type | Required | Edge cases |
|------|----------|------------|
| Unit | Society::scopeVisibleToUser; Own/Disown updates province_id. | Global vs province-owned; multi-province. |
| Integration | Society CRUD; project create/edit society_id; export society name; backfill idempotency. | Provincial vs General; visibility. |
| Cross-module | Report generation and any report that uses society_name — still correct. | — |

## Milestone 5

| Type | Required | Edge cases |
|------|----------|------------|
| Unit | (Optional) Section FormRequest validation. | — |
| Integration | Update with one section throwing → full rollback; no partial commit. | One section fails mid-update. |
| Cross-module | Full suite green; docs match code. | — |

---

# PHASE 4 — DEPENDENCY MAP

## Milestone order (must precede)

- **Milestone 1** must be fully closed before **Milestone 2** starts. (No overlap: M1 is skip-empty only; M2 is validation/schema.)
- **Milestone 2** must be fully closed before **Milestone 3** starts.
- **Milestone 3** must be fully closed before **Milestone 4** starts.
- **Milestone 4** must be fully closed before **Milestone 5** starts.

## Tasks that MUST be isolated

- **M1:** No change to UpdateProjectRequest validation rules, no change to GeneralInfoController write logic (only section controllers). Isolated from M2.
- **M2:** No change to section controller delete/create logic (only validation and empty() fixes). Isolated from M1 and M3.
- **M3:** No change to societies or project update flow (only resolver and BudgetValidationService/Blade). Isolated from M4.
- **M4:** No change to resolver or to section controller guard (M1). Isolated from M3 and M5.
- **M5:** No new data-loss or societies or resolver behaviour; only transaction boundary, docs, optional cleanups. Isolated from M1–M4.

## Hidden coupling to avoid

- **Resolver (M3)** is already wired in many controllers; M3 only adds parity tests and redirects BudgetValidationService and removes blade arithmetic. Do not unwire resolver in M3.
- **Societies backfills (M4)** depend on users.province_id and societies.name; run migrations and users backfill before projects backfill.
- **ExportController (M2 optional, M4)** — M2 can audit PDF variable contract; M4 adds society name from relation. Do not change export project-type data loading in M1 (no change to ProjectDataHydrator or ExportController in M1).

## Confirmation: no milestone overlap

| Milestone | Does NOT include |
|-----------|-------------------|
| M1 | Validation rule changes, schema, resolver, societies, empty() fixes, PDF/mail, attachment auth |
| M2 | Skip-empty guard (M1), Resolver Phase 4 (M3), Societies migrations or Own/Disown (M4), FormRequests per section (M5) |
| M3 | Societies, Data loss guard, Validation alignment, Structural cleanup (M5) |
| M4 | Resolver, Data loss guard, Validation alignment, Structural cleanup |
| M5 | New skip-empty logic, New NOT NULL validation, Resolver Phase 4, Societies mapping/Own/Disown |

---

# WAVE-BY-WAVE SUMMARY

| Wave | Milestone | Risk rating | File count (approx) | Rollback |
|------|-----------|-------------|---------------------|----------|
| 1 | M1 — Data Integrity Shield | **Low** (additive guard only) | ~50 controllers + config + tests | Revert controller changes; config false if used |
| 2 | M2 — Validation & Schema Alignment | **Low–Medium** (validation and zero-value) | ~5–10 | Revert request/controller changes |
| 3 | M3 — Resolver Parity & Financial Stability | **Medium** (display/validation) | ~5–15 + tests | Revert BudgetValidationService and Blade; keep parity tests |
| 4 | M4 — Societies V2 | **Medium** (migrations + backfills) | ~20+ (migrations, commands, controllers, views) | Migrations rollback; backfill not reversible without backup |
| 5 | M5 — Structural Cleanup & Docs | **Low** (transaction + docs) | ~5–10 + docs | Revert code and doc changes |

---

# MILESTONE CLOSURE CRITERIA (CHECKLIST)

**M1 closed when:** All section controllers have skip-empty guard; config (if used) set and documented; unit and integration tests green; deployment and rollback documented; production spot-check done.

**M2 closed when:** No NULL write to NOT NULL columns; zero values not dropped; tests green; optional items done or deferred; production verified.

**M3 closed when:** Resolver parity tests passing; BudgetValidationService uses resolver; blade arithmetic removed per plan; budget and export tests green; production verified.

**M4 closed when:** Societies schema and users/projects migrations and backfills done; dual-write and visibleToUser and Own/Disown working; export and project create/edit correct; production verified.

**M5 closed when:** Transaction boundary confirmed; docs updated; optional items done or deferred; production verified.

---

*End of Wave-Wise Implementation Plan. Reference: MASTER_EXECUTION_CHECKLIST.md, PENDING_TASKS_REPORT.md, SAFE_REFACTOR_STRATEGY.md.*
