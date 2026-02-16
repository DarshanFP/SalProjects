# System-Wide Incremental Update Architecture — Feasibility Plan

**Document type:** Architectural reverse-engineering and feasibility planning (analysis only).  
**Scope:** Live Laravel production system — no code changes, no refactoring, no migrations.  
**Date:** 2026-02-14.

---

## Executive Summary

The application is a **multi-type project management system** (institutional and individual projects) with a **section-based edit model**. The dominant persistence pattern is **delete-all-then-recreate** for section data on every update. This document analyses the codebase, catalogues destructive patterns, assesses incremental readiness, and proposes a conservative migration path to a **system-wide incremental update architecture** (diff-based: update existing, insert new, delete only explicitly removed).

---

# PHASE 1 — SYSTEM UNDERSTANDING

## 1.1 Project Types

| Constant | Display Name |
|----------|--------------|
| `DEVELOPMENT_PROJECTS` | Development Projects |
| `NEXT_PHASE_DEVELOPMENT_PROPOSAL` | NEXT PHASE - DEVELOPMENT PROPOSAL |
| `RURAL_URBAN_TRIBAL` | Rural-Urban-Tribal |
| `CHILD_CARE_INSTITUTION` | CHILD CARE INSTITUTION |
| `CRISIS_INTERVENTION_CENTER` | PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER |
| `INSTITUTIONAL_ONGOING_GROUP_EDUCATIONAL` | Institutional Ongoing Group Educational proposal |
| `LIVELIHOOD_DEVELOPMENT_PROJECTS` | Livelihood Development Projects |
| `RESIDENTIAL_SKILL_TRAINING` | Residential Skill Training Proposal 2 |
| `INDIVIDUAL_ONGOING_EDUCATIONAL` | Individual - Ongoing Educational support (IES) |
| `INDIVIDUAL_INITIAL_EDUCATIONAL` | Individual - Initial - Educational support (IIES) |
| `INDIVIDUAL_LIVELIHOOD_APPLICATION` | Individual - Livelihood Application (ILP) |
| `INDIVIDUAL_ACCESS_TO_HEALTH` | Individual - Access to Health (IAH) |

**Institutional (8):** CCI, Development Projects, RUT, IGE, LDP, CIC, Next Phase DP, RST.  
**Individual (4):** IES, IIES, ILP, IAH.

---

## 1.2 Controllers and Section Mapping

### Core project flow
- **ProjectController** — create, store, edit, update, show, destroy; orchestrates section controllers on store/update.
- **GeneralInfoController** — projects table (single project row).
- **KeyInformationController** — key info fields on projects (institutional only).
- **LogicalFrameworkController** — objectives → results, risks, activities → timeframes (nested).
- **SustainabilityController** — project_sustainabilities (single row per project).
- **BudgetController** — project_budgets (multi-row per project/phase); triggers BudgetSyncService.
- **AttachmentController** — project_attachments (multi-row).
- **ExportController** — PDF/DOC export; uses ProjectDataHydrator.

### Type-specific section controllers (by project type)

| Project type | Section controllers |
|--------------|---------------------|
| Rural-Urban-Tribal | ProjectEduRUTBasicInfoController, EduRUTTargetGroupController, EduRUTAnnexedTargetGroupController |
| CIC | CICBasicInfoController |
| CCI | Achievements, AgeProfile, AnnexedTargetGroup, EconomicBackground, PersonalSituation, PresentSituation, Rationale, Statistics |
| IGE | InstitutionInfo, IGEBeneficiariesSupported, NewBeneficiaries, OngoingBeneficiaries, IGEBudget, DevelopmentMonitoring |
| LDP | InterventionLogic, NeedAnalysis, TargetGroup |
| RST / Development Projects | BeneficiariesAreaController, GeographicalArea, InstitutionInfo, TargetGroupAnnexure, TargetGroup |
| IES | IESPersonalInfo, IESFamilyWorkingMembers, IESImmediateFamilyDetails, IESEducationBackground, IESExpenses, IESAttachments |
| IIES | IIESPersonalInfo, IIESFamilyWorkingMembers, IIESImmediateFamilyDetails, IIESEducationBackground, IIESFinancialSupport, IIESAttachments, IIESExpenses |
| ILP | PersonalInfo, RevenueGoals, StrengthWeakness, RiskAnalysis, AttachedDocuments, Budget |
| IAH | IAHPersonalInfo, IAHEarningMembers, IAHHealthCondition, IAHSupportDetails, IAHBudgetDetails, IAHDocuments |

---

## 1.3 Models and DB Tables (project section data)

- **projects** — main project row; `project_id` (string) unique; `id` PK.
- **project_objectives** — project_id, objective_id (unique); FK to projects CASCADE.
- **project_results** — objective_id; FK to project_objectives.
- **project_risks** — objective_id.
- **project_activities** — objective_id.
- **project_timeframes** — activity_id.
- **project_sustainabilities** — project_id (single row).
- **project_budgets** — project_id, phase; FK CASCADE.
- **project_attachments** — project_id.
- **project_d_p_r_s_t_beneficiaries_areas** — project_id (multi-row).
- **project_edu_rut_***, **project_c_c_i_***, **project_ige_***, **project_ldp_***, **project_rst_***, **project_ies_***, **project_iies_***, **project_ilp_***, **project_iah_*** — type-specific tables; all keyed by project_id; many multi-row, some single-row.

All section tables use integer `id` or similar PK; FKs reference `project_id` or parent section id (e.g. objective_id, activity_id). **No soft deletes** in project/section models.

---

## 1.4 Services

- **BudgetSyncService** — Syncs type-level budget to `projects` (overall_project_budget, amount_forwarded, etc.) on type budget save and before approval; guarded by BudgetSyncGuard.
- **ProjectFundFieldsResolver / ProjectFinancialResolver** — Resolves canonical financial fields for display and validation (strategies by project type).
- **BudgetValidationService** — Validates budget (totals, balances, reporting); uses resolver.
- **ProjectDataHydrator** — Loads full project data for PDF/export by delegating to section controllers (edit/show shape); read-only.
- **BoundedNumericService** — Used in BudgetController for phase bounds.

Reporting: **Monthly (DPReport)**, **Quarterly**, **Half-yearly**, **Annual** — separate flows; Quarterly DevelopmentProjectController uses `whereNotIn('id', $currentIds)->delete()` (already id-aware, partial incremental).

---

## 1.5 Validation Layers

- **StoreProjectRequest / UpdateProjectRequest** — project-level and general validation; project loaded by project_id where applicable.
- **UpdateBudgetRequest** — normalized phases/budget; BudgetController uses validated/normalized input.
- **Type-specific FormRequests** — e.g. StoreCCIAgeProfileRequest, UpdateCCIAgeProfileRequest; many section controllers use Validator::make(normalized, rules).

Validation is largely **array/index-based** (e.g. phases[0].budget[*], objectives[*].results[*]); **no row-id-based rules** for section rows.

---

## 1.6 Blade Forms and JS

- **Logical framework:** `objectives[{{ $objectiveIndex }}][objective]`, `objectives[{{ $objectiveIndex }}][results][{{ $resultIndex }}][result]`, etc. **No hidden `objective_id`, `result_id`, `activity_id`, `timeframe_id`** in edit form; JS in `scripts-edit.blade.php` and `logical_framework.blade.php` uses **array indices** for naming (addObjective, addResult, addActivity, month checkboxes).
- **Budget:** `phases[0][budget][*]` with particular, rate_*, this_phase; **no budget row `id`** in form.
- **RST beneficiaries area:** `project_area[]`, `category_beneficiary[]`, etc. **Index-only**; Edit blade uses `$area` but no hidden `id`.
- **IGE, ILP, IAH, IES, IIES, CCI, etc.:** Similar: `name="...[]"` or `name="...[$index][...]"`; **row IDs not sent** in most sections. Exceptions: some single-row sections (e.g. CIC basic info) and CCI AgeProfile (updateOrCreate by project_id).

---

## 1.7 Section Classification (single / multi / nested)

| Section | Structure | Notes |
|---------|-----------|-------|
| General info | Single row | projects table |
| Key information | Single row | projects table |
| Logical framework | Nested | objectives → results, risks, activities → timeframes |
| Sustainability | Single row | project_sustainabilities |
| Budget (institutional) | Multi-row | project_budgets by project_id + phase |
| Attachments (default) | Multi-row | project_attachments |
| RST/DP Beneficiaries area | Multi-row | project_d_p_r_s_t_beneficiaries_areas |
| EduRUT Target group / Annexed | Multi-row | project_edu_rut_* |
| CCI (multiple sections) | Single-row each (except AnnexedTargetGroup, Statistics) | Multiple tables |
| CCI AnnexedTargetGroup, Statistics | Multi-row | delete-all then recreate |
| IGE Beneficiaries, New/Ongoing, Budget | Multi-row | delete-all then recreate |
| LDP Intervention, NeedAnalysis, TargetGroup | Single or multi | NeedAnalysis can have multiple; LDP TargetGroup multi |
| RST GeographicalArea, InstitutionInfo, TargetGroup, TargetGroupAnnexure | Single or multi | Mixed |
| IES/IIES PersonalInfo, FamilyWorkingMembers, etc. | Single or multi | Many multi-row; expenses nested (header + details) |
| ILP PersonalInfo, RiskAnalysis, RevenueGoals, Budget, etc. | Single or multi | RevenueGoals: plan items, income, expense tables |
| IAH sections | Single or multi | EarningMembers, BudgetDetails multi-row |
| IES/IIES Expenses | Nested | Parent expense + expense_details |

---

## 1.8 Request Lifecycle

- **Create:** `GET projects/create` → form → `POST projects/store` → StoreProjectRequest → GeneralInfoController::store → (institutional: LogicalFramework, Sustainability, Budget, Attachments) → type-specific store handlers (ProjectController::getProjectTypeStoreHandlers) → redirect.
- **Edit:** `GET projects/{id}/edit` → ProjectController::edit loads project + all type-specific section data via section controller `edit()` methods → single Blade (edit.blade.php) with partials.
- **Update:** `PUT projects/{id}/update` → UpdateProjectRequest → GeneralInfoController::update, KeyInformation (if institutional) → LogicalFrameworkController::update, SustainabilityController::update, BudgetController::update, AttachmentController::update → type-specific update handlers → BudgetSyncService when budget changed → redirect.
- **Show:** `GET projects/{id}` → ProjectController::show with section data for view.
- **Export:** ExportController::downloadPdf / downloadDoc → ProjectDataHydrator loads same shape as show.
- **Approval:** Provincial/Coordinator: forward, approve, reject, revert; BudgetSyncService::syncBeforeApproval; status transitions.

---

# PHASE 2 — DESTRUCTIVE PATTERN AUDIT

## 2.1 Controllers Using Delete-All (by project_id or equivalent)

| Controller | Table(s) | Pattern | Category |
|------------|----------|---------|----------|
| LogicalFrameworkController | project_objectives (then cascades: results, risks, activities, timeframes) | delete all by project_id, then recreate | Nested destructive |
| BudgetController | project_budgets | delete by project_id + phase, then create | Multi-row destructive |
| RST/BeneficiariesAreaController | project_d_p_r_s_t_beneficiaries_areas | delete all by project_id, then create | Multi-row destructive |
| IIES/IIESExpensesController | project_i_i_e_s_expenses + expense_details | delete details then parent, then recreate | Nested destructive |
| IES/IESExpensesController | project_i_e_s_expenses + expense_details | same | Nested destructive |
| IIES/IIESFamilyWorkingMembersController | project_i_i_e_s_family_working_members | delete all by project_id | Multi-row destructive |
| IES/IESFamilyWorkingMembersController | project_i_e_s_family_working_members | same | Multi-row destructive |
| EduRUTTargetGroupController | project_edu_rut_target_groups | delete all by project_id | Multi-row destructive |
| EduRUTAnnexedTargetGroupController | project_edu_rut_annexed_target_groups | delete all by project_id (and single-row delete by id in destroy) | Multi-row destructive |
| LDP/TargetGroupController | project_ldp_target_groups | delete all by project_id | Multi-row destructive |
| LDP/InterventionLogicController | project_ldp_intervention_logics | delete all by project_id | Single-row (effectively) / multi-row |
| RST/TargetGroupAnnexureController | project_r_s_t_target_group_annexures | delete all by project_id | Multi-row destructive |
| RST/GeographicalAreaController | project_r_s_t_geographical_areas | delete all by project_id | Multi-row destructive |
| RST/TargetGroupController | project_r_s_t_target_groups | delete by project_id | Single-row destructive |
| RST/InstitutionInfoController | project_r_s_t_institution_infos | delete by project_id | Single-row destructive |
| CCI/AnnexedTargetGroupController | project_c_c_i_annexed_target_groups | delete all by project_id | Multi-row destructive |
| CCI/StatisticsController | project_c_c_i_statistics | delete all by project_id | Multi-row destructive |
| CCI/EconomicBackgroundController | project_c_c_i_economic_backgrounds | delete by project_id | Single-row destructive |
| CCI/PersonalSituationController | project_c_c_i_personal_situations | delete by project_id | Single-row destructive |
| CCI/RationaleController | project_c_c_i_rationales | delete by project_id | Single-row destructive |
| CCI/PresentSituationController | project_c_c_i_present_situations | delete by project_id | Single-row destructive |
| IGE/IGEBeneficiariesSupportedController | project_ige_beneficiaries_supporteds | delete all by project_id | Multi-row destructive |
| IGE/OngoingBeneficiariesController | project_ige_ongoing_beneficiaries | delete all by project_id | Multi-row destructive |
| IGE/NewBeneficiariesController | project_ige_new_beneficiaries | delete all by project_id | Multi-row destructive |
| IGE/IGEBudgetController | project_ige_budgets | delete all by project_id | Multi-row destructive |
| IGE/DevelopmentMonitoringController | project_ige_development_monitorings | delete by project_id | Single-row destructive |
| IGE/InstitutionInfoController | project_ige_institution_infos | delete by project_id | Single-row destructive |
| ILP/RevenueGoalsController | project_ilp_revenue_plan_items, revenue_incomes, revenue_expenses | delete all by project_id for all three | Multi-row (coupled) |
| ILP/StrengthWeaknessController | project_ilp_business_strength_weaknesses | delete all by project_id | Multi-row destructive |
| ILP/RiskAnalysisController | project_ilp_risk_analyses | delete all by project_id | Multi-row destructive |
| ILP/PersonalInfoController | project_ilp_personal_infos | delete by project_id | Single-row destructive |
| ILP/BudgetController | project_ilp_budgets | delete all by project_id | Multi-row destructive |
| IAH/IAHEarningMembersController | project_iah_earning_members | delete all by project_id | Multi-row destructive |
| IAH/IAHBudgetDetailsController | project_iah_budget_details | delete all by project_id | Multi-row destructive |
| IAH/IAHSupportDetailsController | project_iah_support_details | delete by project_id | Single-row destructive |
| IAH/IAHHealthConditionController | project_iah_health_conditions | delete by project_id | Single-row destructive |
| IAH/IAHPersonalInfoController | project_iah_personal_infos | delete by project_id | Single-row destructive |
| IIES/FinancialSupportController | project_i_i_e_s_scope_financial_supports | delete by project_id | Single-row destructive |
| IIES/IIESImmediateFamilyDetailsController | project_i_i_e_s_immediate_family_details | firstOrFail then delete | Single-row destructive |
| IIES/IIESPersonalInfoController | project_i_i_e_s_personal_infos | firstOrFail then delete | Single-row destructive |
| IIES/EducationBackgroundController | project_i_i_e_s_education_backgrounds | delete by project_id | Single-row destructive |
| IES/IESImmediateFamilyDetailsController | project_i_e_s_immediate_family_details | firstOrFail then delete | Single-row destructive |
| IES/IESEducationBackgroundController | project_i_e_s_education_backgrounds | delete by project_id | Single-row destructive |
| IES/IESPersonalInfoController | project_i_e_s_personal_infos | firstOrFail then delete | Single-row destructive |
| CICBasicInfoController | project_cic_basic_infos | firstOrFail then delete | Single-row destructive |
| ProjectEduRUTBasicInfoController | project_edu_rut_basic_infos | firstOrFail then delete | Single-row destructive |
| CCI/AchievementsController | project_c_c_i_achievements | firstOrFail then delete | Single-row destructive |
| CCI/AgeProfileController | project_c_c_i_age_profiles | firstOrFail then delete (destroy only); **update uses updateOrCreate** | Single-row (update already incremental) |
| SustainabilityController | project_sustainabilities | firstOrFail then delete (destroy only) | Single-row |
| GeneralInfoController | projects | project delete (full project destroy) | Whole-entity |
| ProjectController | projects | project delete | Whole-entity |
| ILP/AttachedDocumentsController, IAHDocumentsController, IIES/IIESAttachmentsController, IES/IESAttachmentsController | Type-specific attachment/doc tables | delete record (and storage) | Single-row / container |

**Note:** Single-row “destructive” here means “delete then create” on update (no updateOrCreate). CCI AgeProfile is the only section controller in the list that uses **updateOrCreate** on update (no delete-all).

---

## 2.2 Row IDs in DB vs Forms vs JS

- **Row IDs in DB:** All section tables have primary keys (`id` or e.g. `objective_id`, `activity_id`).
- **Row IDs in forms:** **Not sent** for logical framework, budget, RST beneficiaries, IGE rows, ILP budget/revenue/strength-weakness/risk, IAH earning members/budget details, IIES/IES family members, CCI statistics/annexed target group, EduRUT target/annexed, LDP target group, RST geographical/target group annexure. Single-row sections often use updateOrCreate by project_id or firstOrFail+delete+create.
- **JS:** Clone/add row logic uses **array indices** only (e.g. `objectives[${objectiveIndex}][activities][${activityIndex}][verification]`). No preservation of backend row IDs when adding/cloning rows.

---

## 2.3 Risk Hotspots

| Risk | Severity | Reason |
|------|----------|--------|
| Logical framework (objectives/results/risks/activities/timeframes) | High | Nested; used in reporting and export; no IDs in form; cascade deletes. |
| Budget (project_budgets + sync to projects) | High | Financial impact; BudgetSyncService and reporting depend on current rows; phase-scoped delete. |
| IES/IIES expenses (parent + details) | High | Nested; financial; delete details then parent then recreate. |
| ILP RevenueGoals (plan items, income, expense) | Medium | Three tables coupled; all delete by project_id. |
| RST/DP Beneficiaries area | Medium | Multi-row; reporting/display; index-only form. |
| IGE/IAH/ILP budgets and multi-row sections | Medium | Multiple sections per type; same pattern. |
| Single-row sections (e.g. CIC basic info, RST institution info) | Lower | Easy to convert to updateOrCreate or single update; some already delete+create. |

---

# PHASE 3 — INCREMENTAL ARCHITECTURE READINESS

## 3.1 Per-Table Readiness (summary)

| Table / area | PK | FK / cascade | Can update by id? | Can diff children? | Ordering / computed | Blockers |
|--------------|----|--------------|-------------------|--------------------|----------------------|----------|
| project_objectives | id, objective_id | project_id CASCADE | Yes | Yes (results, risks, activities) | None | Form/JS index-only; no IDs in request |
| project_results | id | objective_id | Yes | N/A | None | Same |
| project_risks | id | objective_id | Yes | N/A | None | Same |
| project_activities | id | objective_id | Yes | Timeframes | None | Same |
| project_timeframes | id | activity_id | Yes | N/A | None | Same |
| project_budgets | id | project_id CASCADE | Yes | N/A | Phase; derived totals | Form has no row id; BudgetSync reads all rows |
| project_d_p_r_s_t_beneficiaries_areas | id | project_id | Yes | N/A | None | Form array-only; no row id |
| project_* (other multi-row sections) | id | project_id | Yes | Where nested, yes | Some order by id or created_at | Form/JS index-based; validation array-based |
| IES/IIES expenses + details | id | expense_id / project_id | Yes | Yes | Nested | Delete parent cascades or explicit detail delete |

**Conclusions:**  
- **DB is ready:** PKs and FKs allow update-by-id and child diffing.  
- **No soft deletes** — explicit delete is required for “removed” rows.  
- **Blockers:** Forms and JS do not send or preserve row IDs; validation and controller logic assume **array order** and **full replace**, not “list of ids to keep + updates/inserts.”

---

## 3.2 Blocker Summary

1. **Index-based form structure** — Names like `objectives[0][results][1][result]` and `project_area[]`; no `objectives[0][id]` or `beneficiaries_area[0][id]`.
2. **Validation rules** — Array/position based; no “optional id for existing row” in rules.
3. **JS clone/add** — New rows get new indices; no injection of existing row IDs.
4. **Nested structure** — Logical framework and IES/IIES expenses need consistent parent-child id handling (parent id for new children, child ids for updates).

---

# PHASE 4 — MIGRATION STRATEGY DESIGN

## Strategy A — Section-wise incremental migration

- **Approach:** Convert one **section** at a time (e.g. RST Beneficiaries Area, then Budget, then Logical Framework) across **all** project types that use it.
- **Complexity:** Medium–high (each section has its own form partial, controller, validation).
- **Risk:** Medium (localized to one section; regression in one area).
- **Effort:** High (many sections × many types; shared sections like budget/logical framework once per section).
- **Frontend:** Per-section: add hidden `id` (or equivalent) for existing rows; JS must preserve and assign ids when cloning.
- **Backend:** Per-section: replace delete-all with diff: match by id → update; no id → create; “missing” ids → delete.
- **Regression:** Section-scoped; good for rollout and rollback per section.
- **Production safety:** High (incremental by section; can feature-flag or route by section).

---

## Strategy B — Project-type-wise migration

- **Approach:** Convert **one project type** end-to-end (e.g. “Rural-Urban-Tribal” or “Individual - Livelihood Application”) to full incremental for all its sections.
- **Complexity:** High (all sections for that type in one go).
- **Risk:** High (one type fully changed; export, approval, reporting all touched).
- **Effort:** High (many sections per type; type-specific forms and controllers).
- **Frontend:** All partials for that type updated to send row ids and preserve them in JS.
- **Backend:** All section controllers for that type switched to diff/update/insert/delete.
- **Regression:** Whole type affected; harder to isolate.
- **Production safety:** Medium (big-bang per type; rollback = revert whole type).

---

## Strategy C — Core incremental engine

- **Approach:** Build a **shared RowDiffService** (or similar): input = project_id, section key, current DB rows, submitted array; output = updates, inserts, deletes. Migrate sections gradually to use this engine; frontend still needs to send ids for “existing” rows.
- **Complexity:** Medium for engine (one place for diff logic); high for full adoption (every section must integrate).
- **Risk:** Medium (engine bug affects all sections using it; can limit to few sections initially).
- **Effort:** Medium for engine + first section; then repeated per section.
- **Frontend:** Same as A: add ids per section; JS preserve ids.
- **Backend:** Controllers call engine instead of delete-all; validation can stay array-based with optional `id`.
- **Regression:** Centralized logic; testing engine once helps all sections.
- **Production safety:** High (adopt section by section; engine behind feature flag).

---

## Strategy Comparison Table

| Criteria | A – Section-wise | B – Project-type-wise | C – Core engine |
|----------|-------------------|------------------------|------------------|
| Complexity | Medium–high | High | Medium (+ adoption) |
| Risk | Medium | High | Medium |
| Effort | High (many sections) | High (many sections/type) | Medium then repeated |
| Frontend impact | Per section | Per type (all at once) | Per section (same as A) |
| Backend impact | Per section | Per type | Engine + per-section integration |
| Regression risk | Localized | Whole type | Engine + sections |
| Production safety | High | Medium | High |

---

# PHASE 5 — RECOMMENDED APPROACH

## 5.1 Recommended Strategy: **Hybrid (A + C)**

- **Primary:** **Section-wise** migration (Strategy A) so that risk and rollout are section-scoped.
- **Enabler:** Introduce a **small core diff engine** (Strategy C) for “flat” and “one-level nested” multi-row sections (e.g. “given current rows and submitted array with optional id per item, produce update/insert/delete list”). Use it for sections that are structurally similar (e.g. RST beneficiaries, IGE new beneficiaries) to avoid duplicating diff logic in every controller.
- **Avoid:** Full project-type-wise (B) for first phases to limit blast radius.

**Why:**  
- Section-wise keeps production risk low and allows rollback per section.  
- A shared engine reduces duplication and standardizes “id-based diff” behaviour.  
- Logical framework and IES/IIES expenses are nested and may use a dedicated (or extended) engine variant rather than a single flat list.

---

## 5.2 Required Frontend Changes (per section)

- Add **hidden input** (e.g. `name="objectives[$i][id]"` or `beneficiaries_area[$i][id]`) for **existing** rows, value = row PK (or stable id). New rows: omit or empty.
- **JS (add/clone row):** When duplicating a row, copy the hidden `id` only for “edit existing” behaviour if desired; for “new row” clone, do **not** send id (or send empty) so backend treats as insert.
- Ensure **removed** rows are not submitted (or submit a marker, e.g. `_delete=1`); backend will delete rows whose id was not in the submitted list (or marked delete).

---

## 5.3 Required Validation Changes

- Allow **optional** `id` per item (e.g. `objectives.*.id` nullable, sometimes present).  
- Rules that reference “current rows” (e.g. uniqueness, cross-field) may need to accept id for updates.  
- No change to “array of items” structure; only add optional id and, if used, explicit delete markers.

---

## 5.4 Required DB Migration

- **None** for incremental behaviour: existing PKs and FKs are sufficient.  
- Optional later: add **unique constraints** (e.g. project_id + sequence) where ordering must be stable; add **indexes** on (project_id, id) for diff queries if needed. Not required for feasibility.

---

## 5.5 Required Test Coverage

- **Unit:** Diff engine: given current rows and payload, correct update/insert/delete sets.  
- **Feature:** Per section: create project → add section data → edit and add/remove/change rows → assert DB state (no extra deletes, correct updates, new rows).  
- **Regression:** Export (PDF/DOC) and approval flow still correct after section migration.  
- **Budget:** BudgetController + BudgetSyncService: totals and sync behaviour unchanged when budget section becomes incremental.

---

## 5.6 Rollback Safety Plan

- **Per-section rollback:** Keep old controller path (delete-all) behind a feature flag or config (e.g. `incremental_sections.rst_beneficiaries_area = true`). If issues appear, set to false and deploy; frontend can still send ids (ignored by old path).  
- **DB:** No schema dependency on “incremental”; rollback is code-only.  
- **Data:** Avoid destructive migrations; incremental logic only **adds** update/insert/delete behaviour and does not drop columns or tables.

---

## 5.7 Execution Waves (Milestones)

| Wave | Scope | Goal |
|------|--------|------|
| M1 | Single **flat** multi-row section (e.g. RST Beneficiaries Area) | Prove frontend id + backend diff; no delete-all for that section. |
| M2 | Second section (e.g. IGE New Beneficiaries or LDP Target Group) using same or extended diff pattern. | Reuse or extend diff engine. |
| M3 | **Budget** (project_budgets) | Incremental by id; phase handling; BudgetSyncService unchanged behaviour. |
| M4 | **Logical framework** (objectives → results, risks, activities → timeframes) | Nested diff; form and JS send ids at each level. |
| M5 | IES/IIES **expenses** (parent + details) | Nested; optional shared engine variant. |
| M6 | Remaining multi-row sections (by type) | Roll out section-wise; single-row sections can move to updateOrCreate where not already. |
| M7 | Cleanup | Remove delete-all code paths and feature flags; optional indexes/constraints.

---

# PHASE 6 — VISUAL ARCHITECTURE

## 6.1 Current Destructive Flow

```
┌─────────┐     PUT /projects/{id}/update      ┌──────────────────────────────────────┐
│ Browser │ ─────────────────────────────────►│ ProjectController::update             │
│ (form   │     (array payload:               │   → GeneralInfoController::update    │
│  no ids)│      objectives[0][objective],     │   → LogicalFrameworkController::update
│         │       phases[0][budget][0][...],   │   → BudgetController::update         │
│         │       project_area[], ...)        │   → type-specific section controllers│
└─────────┘                                    └───────────────────┬──────────────────┘
                                                                   │
                    ┌──────────────────────────────────────────────┼──────────────────────────────────────────────┐
                    │                                              ▼                                              │
                    │   For each section:                                                                         │
                    │   ┌─────────────────────────────────────────────────────────────────────────────────────┐  │
                    │   │  Model::where('project_id', $id)->delete();   // delete all section rows              │  │
                    │   │  foreach ($request->input('section') as $row) {                                        │  │
                    │   │      Model::create([...]);  // recreate from request                                  │  │
                    │   │  }                                                                                     │  │
                    │   └─────────────────────────────────────────────────────────────────────────────────────┘  │
                    └──────────────────────────────────────────────────────────────────────────────────────────────┘
                                                                   │
                                                                   ▼
                                                         Redirect (success/error)
```

---

## 6.2 Proposed Incremental Flow

```
┌─────────┐     PUT /projects/{id}/update      ┌──────────────────────────────────────┐
│ Browser │ ─────────────────────────────────►│ ProjectController::update             │
│ (form   │     (payload with optional ids:    │   → GeneralInfoController::update    │
│  sends  │      objectives[0][id],            │   → LogicalFrameworkController::update
│  ids for│       objectives[0][objective],     │   → BudgetController::update         │
│  existing       phases[0][budget][0][id],    │   → type-specific section controllers│
│  rows)  │       project_area[0][id], ...)    │                                      │
└─────────┘                                    └───────────────────┬──────────────────┘
                                                                   │
                    ┌──────────────────────────────────────────────┼──────────────────────────────────────────────┐
                    │                                              ▼                                              │
                    │   For each section (using Diff Engine or section-specific diff):                             │
                    │   ┌─────────────────────────────────────────────────────────────────────────────────────┐  │
                    │   │  $current = Model::where('project_id', $id)->get()->keyBy('id');                     │  │
                    │   │  $submitted = $request->input('section');  // with optional id per row               │  │
                    │   │  $diff = RowDiffService::compute($current, $submitted);                              │  │
                    │   │  foreach ($diff->updates as $id => $attrs) { Model::where('id', $id)->update($attrs); }│  │
                    │   │  foreach ($diff->inserts as $attrs) { Model::create($attrs); }                         │  │
                    │   │  foreach ($diff->deletes as $id) { Model::where('id', $id)->delete(); }                 │  │
                    │   └─────────────────────────────────────────────────────────────────────────────────────┘  │
                    └──────────────────────────────────────────────────────────────────────────────────────────────┘
                                                                   │
                                                                   ▼
                                                         Redirect (success/error)
```

---

# APPENDIX

## A. Risk Matrix (Summary)

| Area | Data loss risk | Nested complexity | Financial impact | Reporting dependency |
|------|----------------|-------------------|------------------|------------------------|
| Logical framework | High | High | Low | Yes (export, next phase) |
| Budget | High | Low | High | Yes (sync, reports) |
| IES/IIES expenses | High | High | High | Possible |
| RST/IGE/ILP/IAH multi-row | Medium | Low–medium | Medium where budget | Yes (export) |
| Single-row sections | Low | Low | Low | Low |

---

## B. Estimated Migration Timeline (Conservative)

- **M1 (first section):** 2–3 weeks (analysis, frontend id + JS, backend diff, tests, rollout).  
- **M2:** 1–2 weeks (reuse pattern).  
- **M3 (Budget):** 2–3 weeks (phase handling, BudgetSync parity, validation).  
- **M4 (Logical framework):** 3–4 weeks (nested ids, JS, validation).  
- **M5 (IES/IIES expenses):** 2–3 weeks.  
- **M6 (remaining sections):** 4–8 weeks (depends on number of sections and types).  
- **M7 (cleanup):** 1–2 weeks.  

**Total (conservative):** ~6–9 months for full system-wide incremental, assuming part-time focus and production-safe rollouts with rollback options.

---

## C. Rollback Strategy (Summary)

- **Code rollback:** Feature flags or config to revert section(s) to delete-all behaviour; deploy without schema changes.  
- **Data rollback:** Not required for incremental logic (no destructive schema or one-way data migration).  
- **Frontend:** Sending ids is backward-compatible; old backend can ignore them.

---

## D. Hidden Coupling (Summary)

- **BudgetSyncService** depends on project relations (e.g. budgets, type-specific budget/expense tables); any change to how budget rows are stored must preserve resolver and sync behaviour.  
- **ProjectDataHydrator** calls section controllers’ `edit()`/show logic; incremental storage must not change the **shape** of data returned (same keys and structure).  
- **ExportController** and **PDF/DOC** views consume hydrated project; no change to public API of hydration.  
- **Quarterly report** (DevelopmentProjectController) already uses id-aware delete (`whereNotIn('id', $currentIds)`); similar pattern can be reused for project sections.  
- **Validation** and **normalization** (e.g. UpdateBudgetRequest) are tied to array structure; adding optional `id` must not break existing rules and normalizers.

---

*End of document. No code, migrations, or file edits are included; analysis only.*
