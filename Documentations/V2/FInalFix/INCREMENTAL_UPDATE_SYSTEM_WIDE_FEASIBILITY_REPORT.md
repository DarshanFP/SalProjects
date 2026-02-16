# Incremental Update System-Wide Feasibility Report

**Document type:** Read-only architectural audit  
**Scope:** Migration from DELETE-AND-RECREATE to INCREMENTAL UPDATE (row-level patch model)  
**Date:** 2026-02-14  
**Critical rule:** No code modifications; analysis only. Production data assumed critical.

---

## 1. Executive Summary

The project uses **delete-and-recreate** as the dominant persistence pattern for project-scoped child data. This audit evaluates feasibility of moving to an **incremental update** model (if id present → update; if id missing → create; explicit or dedicated delete for removals).

**Findings in brief:**

- **50+ controller locations** perform bulk or single-row delete (project- or report-scoped) before or instead of insert/update.
- **Database:** All affected tables have a numeric primary key (`id` or equivalent); all have `project_id` (or `report_id`) foreign keys; **no application models use SoftDeletes** — all deletes are hard.
- **Frontend:** Most multi-row forms use **parallel arrays** or **index-based** payloads (`phases[0][budget][index]`, `objectives[i][results][j]`, `project_area[]` / `direct_beneficiaries[]`). **Row IDs are not sent** for budget, logical framework, or RST beneficiaries.
- **Services:** BudgetSyncService and ProjectFinancialResolver **aggregate from current relations**; they do not assume full replacement. Export and reporting **read** relations; they do not depend on absence of historical rows.
- **Feasibility:** Incremental update is **technically feasible** after frontend and validation changes. Risk is **moderate to high** where payloads are index/parallel-array only; **lower** where IDs can be added and backend can adopt an id-based upsert + explicit-delete strategy. One subsystem (Quarterly Development Project reports) **already uses** incremental update with `whereNotIn('id', $currentIds)->delete()`.

**Recommendation:** Proceed in waves: (1) Add row IDs to payloads and adopt incremental logic for sections with lowest coupling (e.g. single-row type-specific blocks); (2) Multi-row sections (budget, logical framework, beneficiaries) require Blade/JS and validation redesign before switching from delete-recreate to incremental update.

---

## 2. Phase 1 — All Delete-and-Recreate Locations

Table of every controller/service using destructive replace or bulk/single-row delete. Excludes tests and vendor.

| File path | Method | Table / Model affected | Pattern type | Risk |
|-----------|--------|------------------------|--------------|------|
| LogicalFrameworkController.php | update | project_objectives (cascade: results, risks, activities, timeframes) | Bulk delete by project_id then insert all | **High** |
| LogicalFrameworkController.php | destroy | project_objectives (nested: results, risks, activities) | Single objective + nested delete | Medium |
| RST/BeneficiariesAreaController.php | store, update, destroy | project_RST_DP_beneficiaries_area | Bulk delete by project_id then insert | **High** |
| BudgetController.php | update | project_budgets | Bulk delete by project_id + phase then insert | **High** |
| IIES/IIESExpensesController.php | store, update, destroy | project_i_i_e_s_expenses + expense_details | Parent + relation delete | High |
| IIES/IIESFamilyWorkingMembersController.php | store, update, destroy | project_i_i_e_s_family_working_members | Bulk delete by project_id then insert | High |
| IIES/FinancialSupportController.php | destroy | project_i_i_e_s_scope_financial_supports | Bulk delete by project_id | Medium |
| IIES/EducationBackgroundController.php | store | project_i_i_e_s_education_backgrounds | Bulk delete by project_id then insert | High |
| IIES/IIESPersonalInfoController.php | destroy | project_i_i_e_s_personal_infos | Single row delete | Low |
| IIES/IIESImmediateFamilyDetailsController.php | destroy | project_i_i_e_s_immediate_family_details | Single row delete | Low |
| IES/IESExpensesController.php | store, update | project_i_e_s_expenses + expense_details | Parent + relation delete | High |
| IES/IESFamilyWorkingMembersController.php | store, update, destroy | project_i_e_s_family_working_members | Bulk delete by project_id then insert | High |
| IES/IESEducationBackgroundController.php | store | project_i_e_s_education_backgrounds | Bulk delete by project_id then insert | High |
| IES/IESPersonalInfoController.php | destroy | project_i_e_s_personal_infos | Single row delete | Low |
| IES/IESImmediateFamilyDetailsController.php | destroy | project_i_e_s_immediate_family_details | Single row delete | Low |
| IES/IESAttachmentsController.php | destroy | project_i_e_s_attachments | Single row + storage | Low |
| IIES/IIESAttachmentsController.php | destroy | project_i_i_e_s_attachments | Single row + storage | Low |
| IAH/IAHDocumentsController.php | destroy | project_i_a_h_documents | Single row + storage | Low |
| ILP/AttachedDocumentsController.php | destroy | project_i_l_p_attached_documents | Single row delete | Low |
| LDP/InterventionLogicController.php | store | project_l_d_p_intervention_logics | Bulk delete by project_id then insert | Medium |
| LDP/TargetGroupController.php | store, destroy | project_l_d_p_target_groups | Bulk delete by project_id then insert | High |
| LDP/NeedAnalysisController.php | destroy | project_l_d_p_need_analyses | Single row + file delete | Low |
| RST/TargetGroupController.php | store, destroy | project_r_s_t_target_groups | Bulk delete by project_id then insert | High |
| RST/InstitutionInfoController.php | store, destroy | project_r_s_t_institution_infos | Bulk delete by project_id then insert | Medium |
| RST/TargetGroupAnnexureController.php | store, destroy | project_r_s_t_target_group_annexures | Bulk delete by project_id then insert | High |
| RST/GeographicalAreaController.php | store, destroy | project_r_s_t_geographical_areas | Bulk delete by project_id then insert | High |
| CCI/RationaleController.php | store, destroy | project_c_c_i_rationales | Bulk delete by project_id then insert | Medium |
| CCI/PresentSituationController.php | store, destroy | project_c_c_i_present_situations | Bulk delete by project_id then insert | Medium |
| CCI/AnnexedTargetGroupController.php | store, destroy | project_c_c_i_annexed_target_groups | Bulk delete by project_id then insert | High |
| CCI/AchievementsController.php | destroy | project_c_c_i_achievements | Single row delete | Low |
| CCI/StatisticsController.php | store, destroy | project_c_c_i_statistics | Bulk delete by project_id then insert | Medium |
| CCI/EconomicBackgroundController.php | store, destroy | project_c_c_i_economic_backgrounds | Bulk delete by project_id then insert | Medium |
| CCI/PersonalSituationController.php | store, destroy | project_c_c_i_personal_situations | Bulk delete by project_id then insert | Medium |
| CCI/AgeProfileController.php | destroy | project_c_c_i_age_profiles | Single row delete | Low |
| IGE/DevelopmentMonitoringController.php | store, destroy | project_i_g_e_development_monitorings | Bulk delete by project_id then insert | Medium |
| IGE/InstitutionInfoController.php | store, destroy | project_i_g_e_institution_infos | Bulk delete by project_id then insert | Medium |
| IGE/IGEBeneficiariesSupportedController.php | store, destroy | project_i_g_e_beneficiaries_supporteds | Bulk delete by project_id then insert | High |
| IGE/OngoingBeneficiariesController.php | store, destroy | project_i_g_e_ongoing_beneficiaries | Bulk delete by project_id then insert | High |
| IGE/NewBeneficiariesController.php | store, destroy | project_i_g_e_new_beneficiaries | Bulk delete by project_id then insert | High |
| IGE/IGEBudgetController.php | store, destroy | project_i_g_e_budgets | Bulk delete by project_id then insert | High |
| ILP/RiskAnalysisController.php | store, destroy | project_i_l_p_risk_analyses | Bulk delete by project_id then insert | Medium |
| ILP/PersonalInfoController.php | store, destroy | project_i_l_p_personal_infos | Bulk delete by project_id then insert | Medium |
| ILP/StrengthWeaknessController.php | store, destroy | project_i_l_p_business_strength_weaknesses | Bulk delete by project_id then insert | High |
| ILP/BudgetController.php | store, destroy | project_i_l_p_budgets | Bulk delete by project_id then insert | High |
| ILP/RevenueGoalsController.php | store, destroy | project_i_l_p_revenue_plan_items, revenue_income, revenue_expense | Bulk delete 3 tables by project_id then insert | High |
| IAH/IAHSupportDetailsController.php | store, destroy | project_i_a_h_support_details | Bulk delete / single delete | Medium |
| IAH/IAHHealthConditionController.php | store, destroy | project_i_a_h_health_conditions | Bulk delete / single delete | Medium |
| IAH/IAHPersonalInfoController.php | store, destroy | project_i_a_h_personal_infos | Bulk delete / single delete | Medium |
| IAH/IAHEarningMembersController.php | store, destroy | project_i_a_h_earning_members | Bulk delete by project_id then insert | High |
| IAH/IAHBudgetDetailsController.php | store, destroy | project_i_a_h_budget_details | Bulk delete by project_id then insert | High |
| EduRUTTargetGroupController.php | store, destroy | project_edu_rut_target_groups | Bulk delete by project_id then insert | High |
| EduRUTAnnexedTargetGroupController.php | store, destroy | project_edu_rut_annexed_target_groups | Bulk delete / single delete | High |
| CICBasicInfoController.php | destroy | project_cic_basic_info | Single row delete | Low |
| ProjectEduRUTBasicInfoController.php | destroy | project_edurut_basic_info | Single row delete | Low |
| ProjectController.php | destroy | projects | Single project delete | N/A (entity delete) |
| GeneralInfoController.php | destroy (general info) | projects | Single project delete | N/A |
| GeneralController.php | province/society/center destroy | provinces, societies, centers | Single entity delete | N/A |
| SustainabilityController.php | destroy | project_sustainabilities | Single row delete | Low |
| Reports/Quarterly/DevelopmentProjectController.php | update (objectives, activities, account details, outlooks, photos) | RQDP objectives, activities, account_details, outlooks, photos | **Incremental:** update by id, create if no id, whereNotIn delete | Low (already incremental) |
| Reports/Monthly/* (ResidentialSkillTraining, InstitutionalOngoingGroup, CrisisInterventionCenter) | — | report-scoped trainee/profile tables | Bulk delete by report_id | Medium |

**Pattern types:**

- **Bulk delete:** `Model::where('project_id', $id)->delete()` then insert new rows.
- **Single row delete:** `firstOrFail()->delete()` or `findOrFail($id)->delete()` (often for 1:1 or explicit destroy).
- **Nested delete:** Delete parent then children (e.g. objective → results, risks, activities) or expense header → expense details.

---

## 3. Phase 2 — Database Structure Audit

### 3.1 Primary keys and identity

- **project_objectives:** `id` (increments), plus `objective_id` (string, unique) used as FK by results/risks/activities.
- **project_budgets:** `id` (bigInteger, auto-increment).
- **project_RST_DP_beneficiaries_area:** `id` (bigInteger), `DPRST_bnfcrs_area_id` (unique string).
- All other project-scoped tables inspected use `$table->id()` (bigInteger, auto-increment). No composite primary keys found for these tables. No UUID primary keys in migrations.

### 3.2 Foreign keys

- All project child tables have `project_id` (string) with `->references('project_id')->on('projects')->onDelete('cascade')`.
- Report-scoped tables use `report_id` with cascade where applicable.
- Logical framework: `project_results`, `project_risks`, `project_activities` use `objective_id` (string) referencing `project_objectives.objective_id`; `project_timeframes` use `activity_id` referencing `project_activities.activity_id`.

### 3.3 Soft deletes

- **No** application model in `app/Models` uses `SoftDeletes`. All deletes are **hard deletes**.

### 3.4 Unique constraints

- `project_objectives.objective_id` unique.
- Various type-specific tables have generated unique string IDs (e.g. RST `DPRST_bnfcrs_area_id`, IES/IIES attachment IDs). No unique constraint on (project_id, order) or (project_id, index) for multi-row sections.

### 3.5 Cascading

- Child tables use `onDelete('cascade')` from `projects`. Deleting a project removes all child rows. Orphan rows are not left by current design when project is deleted; for incremental update, **rows not sent in payload** would become orphans unless explicitly deleted or flagged.

---

## 4. Phase 3 — Model Structure Audit

### 4.1 Primary key and fillable

- **ProjectObjective:** `id` (increments), `objective_id` generated in `creating`; fillable: `objective_id`, `project_id`, `objective`.
- **ProjectBudget:** `id`; fillable includes `project_id`, `phase`, `particular`, `rate_*`, `this_phase`, `next_phase`.
- **ProjectDPRSTBeneficiariesArea:** `id`, table `project_RST_DP_beneficiaries_area`; fillable includes `project_area`, `category_beneficiary`, `direct_beneficiaries`, `indirect_beneficiaries`, `project_id`, `DPRST_bnfcrs_area_id`.
- Other models follow same pattern: numeric `id`, fillable/guarded as per usage.

### 4.2 Relationships

- **ProjectObjective:** hasMany results, risks, activities; belongsTo project. Results/risks/activities use `objective_id` (string).
- **ProjectBudget:** belongsTo project. Phase-based strategy filters by `project->budgets` and `phase`.
- **ProjectDPRSTBeneficiariesArea:** belongsTo project.
- Type-specific models (IGE, ILP, IAH, etc.) have belongsTo project and, where applicable, hasMany child (e.g. expense details).

### 4.3 Global scopes, observers, model events

- **No** Eloquent observers registered in AppServiceProvider for these models.
- **Model events:** Many models use `boot()` and `creating` to set generated IDs (e.g. objective_id, DPRST_bnfcrs_area_id, RST target group IDs, IES/IIES/IAH/ILP document IDs). Some attachment/file models use `deleting` to remove related files or DB rows. No global scopes that would hide rows for incremental update.

### 4.4 Aggregate / redundant fields

- **Project-level:** `overall_project_budget`, `amount_forwarded`, `local_contribution`, `amount_sanctioned`, `opening_balance` are **derived** by BudgetSyncService / ProjectFinancialResolver from relations (e.g. budgets, type-specific expenses). They are not stored per-row; incremental update of budget rows would still be aggregated correctly by existing resolvers.

---

## 5. Phase 4 — Frontend Payload Structure Audit

### 5.1 Budget (Development / phase-based)

- **Blade:** `resources/views/projects/partials/Edit/budget.blade.php`, `partials/budget.blade.php`, `partials/scripts-edit.blade.php`, `partials/scripts.blade.php`.
- **Payload:** `phases[0][budget][{{ $budgetIndex }}][particular]`, `rate_quantity`, `rate_multiplier`, `rate_duration`, `this_phase`. **No `id` field.** Rows identified by **array index** only. New rows added via JS get new index.
- **Category:** **B) Index-based (structural redesign required)** for incremental — need to add `phases[0][budget][*][id]` and send existing row IDs; backend must upsert by id and only delete rows not in payload or via explicit _delete.

### 5.2 Logical framework (objectives, results, risks, activities, timeframes)

- **Blade:** `createProjects.blade.php`, `partials/scripts-edit.blade.php` (clone objective card, update names by index).
- **Payload:** `objectives[${objectiveIndex}][objective]`, `objectives[${objectiveIndex}][results][${resultIndex}][result]`, `objectives[${objectiveIndex}][risks][${riskIndex}][risk]`, `objectives[${objectiveIndex}][activities][${activityIndex}][activity]`, `verification`, `timeframe[months][*]`. **No objective_id, result_id, activity_id, or timeframe id** in form names. Order and index define structure.
- **Category:** **B) Parallel / nested arrays (structural redesign required).** Full incremental would require sending objective_id, result_id, activity_id, timeframe id (or equivalent) and backend matching; otherwise distinction between “update” and “create” is not possible from payload alone.

### 5.3 RST Beneficiaries area

- **Blade:** `partials/Edit/RST/beneficiaries_area.blade.php`, `partials/RST/beneficiaries_area.blade.php`.
- **Payload:** `project_area[]`, `category_beneficiary[]`, `direct_beneficiaries[]`, `indirect_beneficiaries[]`. **No row id.** Rows are parallel arrays; correlation by index only.
- **Category:** **B) Parallel arrays (structural redesign required).** Need at least `id[]` or `beneficiaries_area_id[]` for existing rows to support incremental update.

### 5.4 Other multi-row sections (IGE, ILP, IAH, EduRUT, LDP, RST target groups, etc.)

- Same pattern: forms use index-based or parallel array names; **row IDs are not present** in the payloads. Edit views often loop over `$project->relation` by index and output `name="...[$index][field]"` without hidden `id`.
- **Category:** **B or C)** — mostly **B)** (redesign required); **C)** where a single-row block is updated (e.g. single institution info) and only “replace one” is needed.

### 5.5 Quarterly Development Project report (already incremental)

- **Payload:** Uses `objective_id`, `activity_id.$objectiveIndex.$activityIndex`, `account_detail_id.$index`, `outlook_id.$index`, `existing_photo_ids`, `photos_to_delete`. Backend uses “if id present update else create” and “whereNotIn('id', currentIds)->delete()”.
- **Category:** **A) ID-based (incremental possible).** Reference implementation for the rest of the app.

---

## 6. Phase 5 — Service & Coupling Analysis

### 6.1 BudgetSyncService

- **Behaviour:** After type-specific budget save or before approval, resolves fund fields (via ProjectFundFieldsResolver → ProjectFinancialResolver) and **updates project** columns: `overall_project_budget`, `amount_forwarded`, `local_contribution`, and optionally `amount_sanctioned`, `opening_balance`.
- **Coupling:** Reads `$project->budgets` (and type-specific relations). **Does not assume full replace.** Any set of budget rows for the project/phase that exists after save will be aggregated. **Incremental update of budget rows is compatible** as long as the correct set of rows (current phase) is present; no dependency on “all rows were just recreated”.

### 6.2 ProjectFinancialResolver / PhaseBasedBudgetStrategy

- **Behaviour:** Loads `project->budgets`, filters by `current_phase`, sums `this_phase` (via DerivedCalculationService). **Read-only aggregation.**
- **Coupling:** **No assumption of full replacement.** Incremental update that keeps phase budget set consistent is fine.

### 6.3 DerivedCalculationService

- **Behaviour:** Pure calculation (row total, phase total, project total). No DB or delete logic.
- **Coupling:** **None** to delete-recreate.

### 6.4 ExportController

- **Behaviour:** Loads project with relations (`objectives`, `budgets`, type-specific relations) and exports to Word/PDF. **Read-only.**
- **Coupling:** **None** to delete-recreate. Exports whatever is in DB; no reliance on “historical rows removed”.

### 6.5 Reporting / Dashboard

- **Behaviour:** Queries projects and relations for aggregates and lists. No evidence of logic that “recomputes because data was replaced”.
- **Coupling:** **Low.** Incremental update that preserves correct current set of rows does not break reporting.

### 6.6 BudgetValidationService

- **Behaviour:** Validates budget totals/sanity. Reads project and budgets. **No dependency** on how rows were written.

**Summary:** No service **assumes** full replacement. Moving to incremental update does not require changing aggregation or export logic, provided the **set of rows** after save is the intended set (either by id-based upsert + explicit delete, or by leaving “not sent” rows and defining a clear orphan policy).

---

## 7. Phase 6 — Data Consistency Risk Analysis

| Section | Row IDs in payload? | Uniqueness / order | Risk rating |
|---------|--------------------|--------------------|-------------|
| Project budgets (phase-based) | No (index only) | No unique (project_id, phase, index); order implicit | **HIGH RISK** — redesign; add id, upsert, explicit delete or “replace phase” semantics |
| Logical framework | No | objective_id/activity_id in DB; not in form | **HIGH RISK** — nested structure; need ids in payload and backend upsert + cascade deletes |
| RST Beneficiaries area | No | Parallel arrays | **HIGH RISK** — add row id; then SAFE FOR INCREMENTAL |
| IGE beneficiaries / budget / new/ongoing | No | Index-based | **HIGH RISK** — same as above |
| ILP budget, strength-weakness, revenue goals | No | Index-based | **HIGH RISK** |
| IAH earning members, budget details | No | Index-based | **HIGH RISK** |
| IIES/IES family working members, education background, expenses | No | Index-based / nested | **HIGH RISK** |
| LDP/RST target groups, geographical area, annexures | No | Index-based | **HIGH RISK** |
| CCI/IGE single-row blocks (rationale, institution info, etc.) | 1:1 per project | Single row | **MODERATE RISK** — can treat as “replace one”; optional id for clarity |
| Single-row destroy (personal info, basic info, sustainability) | N/A (destroy only) | Single row | **SAFE** — no incremental “list” logic needed |
| Quarterly DP report (objectives, activities, account details, outlooks) | Yes | ID-based | **SAFE FOR INCREMENTAL** — already implemented |

**Tables where order matters:** Logical framework (objectives/activities order may be meaningful for display); budget rows (order often reflected in UI). Incremental update should preserve or explicitly set order (e.g. `sort_order` column) if required.

**Orphan risk:** If backend switches to “only update/create what is sent” and does **not** delete rows not in payload, rows removed in the UI would remain in DB (orphans). So either: (1) **Explicit _delete** list or dedicated DELETE endpoint per row/section, or (2) **Replace set** semantics (e.g. “replace all beneficiaries for this project with these rows” by sending all current ids and deleting `whereNotIn('id', sent_ids)`). Both are compatible with incremental update.

---

## 8. Phase 7 — Architectural Redesign Blueprint

### 8.1 Per-section strategy (multi-row)

**Current:**

- Delete all rows for project (and scope, e.g. phase).
- Insert all rows from request.

**Target:**

- For each row in request:
  - If `id` (or type-specific id) is present and exists → **update** that row.
  - If `id` is missing or not found → **create** new row (and optionally return id to client for next edit).
- Rows that **exist in DB but are not in request:** do **not** delete automatically unless a clear rule is chosen:
  - **Option A — Explicit delete:** Request carries `_delete: [id1, id2]` or equivalent; backend deletes only those.
  - **Option B — “Replace set”:** Request carries full list of current ids; backend deletes `where project_id = X and id not in (sent_ids)` for that section.

### 8.2 Safe deletion

- **Explicit _delete:** Add a request array (e.g. `budget_delete_ids[]`, `beneficiary_delete_ids[]`) populated when user removes a row in UI; backend deletes only those ids. No “delete all then insert” for that section.
- **Or** **dedicated DELETE endpoint** per resource (e.g. `DELETE /projects/{id}/budget-rows/{rowId}`) and call it when user removes a row. Then save payload only contains “current” rows; backend treats save as upsert by id and does not delete by default.

### 8.3 Orphan prevention

- **Replace-set semantics:** For a given section (e.g. “budget rows for phase 1”), after upserting, delete rows for that project+scope where `id not in (list of ids from request)`. Ensures no orphan rows for that section.
- **Explicit _delete only:** Orphans are avoided only if client always sends _delete for removed rows. If client forgets, orphans remain; optional nightly job or validation could detect orphans (e.g. budget rows not referenced in last submitted form — advanced).

### 8.4 Order

- Where order matters (e.g. objectives, budget rows), add a **sort_order** or **position** column and send it in payload; backend sets it on create/update. Display and export sort by it.

---

## 9. Phase 8 — Migration Impact Plan

### 9.1 Validation changes required

- **Accept optional `id`** (or type-specific id) for each row in multi-row sections. Rules: “id if present must exist and belong to this project” (and scope). New rows have no id.
- **Accept optional `_delete`** (or equivalent) array of ids; validate that each id exists and belongs to the project/section.
- **No change** to business rules (amounts, required fields); only structure of payload and presence of id/delete.

### 9.2 Blade / frontend changes required

- **Add hidden input** (or equivalent) for row id in every multi-row form: e.g. `phases[0][budget][*][id]`, `objectives[*][id]`, `project_area_id[]` or a single structure that carries id per row. When cloning rows via JS, new rows get no id (or id=0); existing rows keep id.
- **Optional:** Add “delete” checkbox or button that pushes id to `_delete[]` and removes row from DOM so it is not submitted as “current”.
- **Logical framework:** Add objective_id, result_id, activity_id (and timeframe id if needed) to payload and to JS when cloning; backend will need to create/update by these ids and delete “not in request” for nested children (or use explicit _delete for nested).

### 9.3 Route changes required

- **None** strictly required. Existing store/update routes can accept the new payload shape. Optional: add `DELETE /projects/{project}/sections/{section}/rows/{id}` for explicit row delete if chosen.

### 9.4 Testing impact

- **Feature/unit tests** that currently assert “after update, only N rows exist” or “row content equals request” must be updated to cover: (1) update by id, (2) create new row (no id), (3) delete by _delete or whereNotIn. Regression tests for BudgetSyncService, ProjectFinancialResolver, and export should still pass once payload and backend produce the same “current set” of rows.

### 9.5 Backward compatibility

- **Option A:** Backend detects payload shape: if no ids present, fall back to current delete-recreate behaviour; if ids present, use incremental. Allows gradual frontend rollout.
- **Option B:** Deploy backend and frontend together; old clients that don’t send ids would create duplicates or incorrect state unless blocked. **Not recommended** without versioning or feature flag.

### 9.6 Rollback plan

- Keep existing delete-recreate code path behind a feature flag or config; if incremental causes issues, switch back to delete-recreate. DB schema already supports both (tables have ids); no migration rollback needed for “revert behaviour only”.

### 9.7 Deployment sequence (wave strategy)

1. **Wave 1 — Single-row and low-risk:** Switch single-row “replace one” sections to update-if-exists (optional id). No payload id required for 1:1. Low risk.
2. **Wave 2 — One multi-row section (e.g. RST beneficiaries):** Add row ids to Blade/JS; backend: upsert by id, delete by “replace set” or _delete. Validate export and reporting.
3. **Wave 3 — Budget:** Add `phases[0][budget][*][id]`; backend: delete current phase budget rows not in request, upsert by id. Re-run BudgetSyncService tests.
4. **Wave 4 — Logical framework:** Add objective/result/activity (and timeframe) ids; backend: update objectives/results/risks/activities/timeframes by id; delete children not in request (or explicit _delete). Highest complexity.
5. **Wave 5 — Remaining type-specific multi-row sections** (IGE, ILP, IAH, IIES/IES, LDP, RST, CCI, EduRUT) following same pattern.

---

## 10. Phase 9 — Final Feasibility Scoring & Execution Order

### 10.1 Feasibility scoring table

| Area | Feasibility | Blocker / enabler |
|------|-------------|--------------------|
| Budget (project_budgets) | Feasible | Add id to payload; backend upsert + replace-set delete for phase |
| Logical framework | Feasible | Add ids at all levels; nested upsert + delete not in request |
| RST Beneficiaries area | Feasible | Add id; backend upsert + replace-set or _delete |
| IGE / ILP / IAH / IIES / IES multi-row | Feasible | Same as above per section |
| LDP / RST / CCI / EduRUT multi-row | Feasible | Same |
| Single-row sections | Feasible | Optional id; update-if-exists |
| BudgetSyncService / Resolver / Export | No change | Already compatible with incremental data |
| Reporting (Quarterly DP) | Already incremental | Reference pattern |

### 10.2 Risk rating (overall)

- **Technical risk:** Moderate — DB and models support id-based updates; no SoftDeletes or hidden assumptions in services.
- **Delivery risk:** High for frontend — many Blade files and JS flows must be updated to send and manage row ids and optional _delete.
- **Data risk:** High if rollout is partial (e.g. only backend changed without sending ids) — duplicates or orphans. Mitigation: backward-compatible detection (no id → delete-recreate) or coordinated deploy with feature flag.

### 10.3 Recommended execution order

1. **Document and agree** payload contract (id, _delete, replace-set) per section.
2. **Implement** backward-compatible backend (if no ids → current behaviour; if ids → incremental) for one section (e.g. RST beneficiaries).
3. **Add** row ids and optional _delete to that section’s Blade/JS; test.
4. **Roll out** remaining multi-row sections in waves (budget → logical framework → others).
5. **Remove** fallback delete-recreate once all sections send ids and are tested.

---

**End of report.** No code was modified; this document is analysis only.
