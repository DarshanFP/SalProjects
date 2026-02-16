# Frontend Payload System Audit

**Application:** Laravel SalProjects  
**Scope:** Project edit/create forms — Blade, JavaScript, and controller payload behaviour  
**Audit type:** Read-only diagnostic  
**Date:** 2026-02-14  

---

## 1. Executive Summary

The project edit flow uses a **single full-page form** that submits via **PUT** to `executor/projects/{project_id}/update`. All section partials are included in one `<form id="editProjectForm">`; there are **no per-section AJAX endpoints** for save. Which sections appear is determined **server-side** by `$project->project_type` in `resources/views/projects/Oldprojects/edit.blade.php`.

**Findings:**

- **Payload model:** Every section that is **in the DOM** is sent on submit. Sections that are **not rendered** for a given project type (e.g. Logical Framework for IES/IIES/ILP/IAH) do **not** send any key; the backend either does not call that section’s controller or relies on “section absent/empty” guards (M1) to skip mutation.
- **Row identity:** No section sends **database row IDs** (e.g. `objective_id`, `result_id`, `budget_id`, `area_id`) in the form. All sections use **positional arrays** only (e.g. `objectives[0][objective]`, `phases[0][budget][0][particular]`, `project_area[]`).
- **Backend strategy:** Section controllers use **delete-by-project_id** (or delete-by-project_id+phase for budget) then **recreate** from the submitted array. Order and index are the only link between submitted rows and persistence; there is no “update row by id” or “delete row by id” in the payload.
- **Risks:** (1) If a section is **absent from the request** (e.g. misconfigured Blade or JS removing nodes), the controller may **skip** mutation (when M1 guard exists) or **normalise to []** and then delete-all + create from empty, effectively **wiping** that section. (2) **Reindexing** in JavaScript (objectives, budget, attachments, RST rows) is required so indices match backend expectations; any bug in reindexing can misalign rows. (3) **Disabled fields** (e.g. budget when locked) are not submitted; the backend must not rely on them for “full state” when budget is locked.

**Conclusion:** The system is built for **full-state replace per section**: frontend sends the complete list of rows for each section, and backend replaces the section’s data with that list. Frontend-only fixes can improve reliability (e.g. ensuring section keys are always present when the section is visible, and consistent reindexing); however, **structural safety** (no accidental wipe on missing key) depends on backend M1-style guards and defaulting missing keys to “no change” or “empty array” in a controlled way. Several sections already use M1; extending that pattern and clarifying “missing key” semantics is recommended for a 6–9 month architecture shift.

---

## 2. Phase 1 — Section Entry Points

### 2.1 Project edit Blade views

| View type | Blade file | Used by |
|-----------|------------|--------|
| Executor/Applicant edit | `resources/views/projects/Oldprojects/edit.blade.php` | Executor, Applicant (single edit form) |
| Create | `resources/views/projects/Oldprojects/createProjects.blade.php` | Executor, Applicant (single create form) |

There are **no** separate “admin edit” or “provincial edit” project forms; admin/provincial/coordinator use read-only or workflow actions (approve/revert). The **only** project edit form is the executor edit view above.

### 2.2 Partials included in project edit (edit.blade.php)

- **Always:** `projects.partials.Edit.general_info`
- **Conditional (not Individual):** `projects.partials.Edit.key_information`
- **By project type:**
  - Development Projects: `Edit.RST.beneficiaries_area`
  - CHILD CARE INSTITUTION: CCI rationale, statistics, annexed_target_group, age_profile, personal_situation, economic_background, achievements, present_situation
  - Residential Skill Training Proposal 2: RST beneficiaries_area, institution_info, target_group, target_group_annexure, geographical_area
  - Rural-Urban-Tribal: Edu-RUT basic_info, target_group, annexed_target_group
  - Individual - Ongoing Educational support: IES personal_info, family_working_members, immediate_family_details, educational_background, estimated_expenses, attachments
  - Individual - Initial - Educational support: IIES personal_info, family_working_members, immediate_family_details, education_background, scope_financial_support, estimated_expenses, attachments
  - Individual - Livelihood Application: ILP personal_info, revenue_goals, strength_weakness, risk_analysis, attached_docs, budget
  - Individual - Access to Health: IAH personal_info, health_conditions, earning_members, support_details, budget_details, documents
  - Institutional Ongoing Group Educational proposal: IGE institution_info, beneficiaries_supported, ongoing_beneficiaries, new_beneficiaries, budget, development_monitoring
  - Livelihood Development Projects: LDP need_analysis, intervention_logic
  - PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER: CIC basic_info
- **Default (non-Individual):** `Edit.logical_framework`, `Edit.sustainibility`, `Edit.budget`, `Edit.attachment`

### 2.3 JavaScript affecting project forms

| File | Location | Purpose |
|------|----------|--------|
| Inline scripts in edit.blade.php | `resources/views/projects/Oldprojects/edit.blade.php` | Form submit handler, save-as-draft hidden input, project type change warning |
| scripts-edit.blade.php | `resources/views/projects/partials/scripts-edit.blade.php` | Logical Framework (objectives/results/risks/activities/timeframes) add/remove/reindex; budget add/remove/reindex; attachments add/remove/reindex; in-charge validation; phase dropdown; budget totals |
| budget-calculations.js | `public/js/budget-calculations.js` | Row/phase totals (used by scripts-edit) |
| Inline in partials | E.g. RST beneficiaries_area, IES estimated_expenses, IIES estimated_expenses, IGE new_beneficiaries | Row add/remove and totals for that section |
| attachments-validation.js | `public/js/attachments-validation.js` | File type/size validation (Edit attachment partial) |
| textarea-auto-resize.js | `public/js/textarea-auto-resize.js` | UI only |

Create flow uses `resources/views/projects/partials/scripts.blade.php` (similar budget/objective logic for create).

### 2.4 Single route and controller

- **Edit:** `PUT /executor/projects/{project_id}` → `ProjectController@update` (route name `projects.update`).
- **Create:** `POST /executor/projects/store` → `ProjectController@store` (route name `projects.store`).

All section updates on edit go through **one** `UpdateProjectRequest` and **one** `ProjectController::update()` which dispatches to section controllers based on `$project->project_type`.

### 2.5 Phase 1 summary table

| Section | Blade file(s) | JS file(s) | Controller | Route | HTTP Method |
|---------|---------------|------------|-----------|--------|-------------|
| General Information | partials/Edit/general_info.blade.php | scripts-edit (in-charge) | GeneralInfoController | projects.update | PUT |
| Key Information | partials/Edit/key_information.blade.php | — | KeyInformationController | projects.update | PUT |
| Logical Framework | partials/Edit/logical_framework.blade.php, edit_timeframe.blade.php | scripts-edit.blade.php | LogicalFrameworkController | projects.update | PUT |
| Sustainability | partials/Edit/sustainibility.blade.php | — | SustainabilityController | projects.update | PUT |
| Budget | partials/Edit/budget.blade.php | scripts-edit.blade.php, budget-calculations.js | BudgetController | projects.update | PUT |
| Attachments (default) | partials/Edit/attachment.blade.php | attachments-validation.js, inline | AttachmentController | projects.update | PUT |
| RST Beneficiaries Area | partials/Edit/RST/beneficiaries_area.blade.php | Inline in partial | RST\BeneficiariesAreaController | projects.update | PUT |
| RST Institution, TargetGroup, Annexure, Geographical | Edit/RST/*.blade.php | Inline where present | RST\*Controller | projects.update | PUT |
| IES (all sub-sections) | partials/Edit/IES/*.blade.php | Inline in IES estimated_expenses | IES\*Controller | projects.update | PUT |
| IIES (all sub-sections) | partials/Edit/IIES/*.blade.php | Inline in IIES estimated_expenses | IIES\*Controller | projects.update | PUT |
| ILP (all sub-sections) | partials/Edit/ILP/*.blade.php | scripts-edit (budget), inline | ILP\*Controller | projects.update | PUT |
| IAH (all sub-sections) | partials/Edit/IAH/*.blade.php | — | IAH\*Controller | projects.update | PUT |
| IGE (all sub-sections) | partials/Edit/IGE/*.blade.php | Inline in new_beneficiaries etc. | IGE\*Controller | projects.update | PUT |
| Edu-RUT | partials/Edit/Edu-RUT/*.blade.php | — | EduRUT*Controller | projects.update | PUT |
| CCI (all sub-sections) | partials/Edit/CCI/*.blade.php | — | CCI\*Controller | projects.update | PUT |
| LDP | partials/Edit/LDP/need_analysis.blade.php, intervention_logic.blade.php, target_group.blade.php | — | LDP\*Controller | projects.update | PUT |
| CIC | partials/Edit/CIC/basic_info.blade.php | — | CICBasicInfoController | projects.update | PUT |

---

## 3. Phase 2 — Payload Structure Audit (Per Section)

### 3.1 Logical Framework

**Blade:**  
- `objectives[{{ $objectiveIndex }}][objective]`, `objectives[{{ $objectiveIndex }}][results][{{ $resultIndex }}][result]`, `objectives[{{ $objectiveIndex }}][risks][{{ $riskIndex }}][risk]`, `objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][activity]`, `objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][verification]`, `objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][months][{{ $month }}]`, `objectives[{{ $objectiveIndex }}][activities][{{ $activityIndex }}][timeframe][description]`.  
- **No** hidden `objective_id`, `result_id`, `risk_id`, `activity_id`, `timeframe_id`.

**JS (scripts-edit.blade.php):**  
- `addObjective()`: clones existing card or uses `createNewObjectiveCard()`; `resetFormValues()` + `resetObjectiveSections()`; then `updateNameAttributes(template, objectiveCount - 1)`. **IDs are not preserved** on clone (no IDs in template).  
- `removeObjective()`: removes card, decrements `objectiveCount`, `updateObjectiveNumbers()` reindexes all objectives and calls `updateNameAttributes(objective, index)` so names become `objectives[0][...]`, `objectives[1][...]`, etc.  
- Results/risks/activities/timeframes: add/remove and then `updateNameAttributes()` so indices are 0-based and contiguous.  
- **Reindexing:** Yes, by DOM order. No cleanup before submit beyond what the form naturally sends.

**Payload scenarios:**  
1. Submit without changes: all current objectives/results/risks/activities/timeframes sent with indices 0..n.  
2. Edit one row: same; untouched rows still sent (full state).  
3. Remove one row: row removed from DOM, reindex updates names; backend receives one fewer row (delete-all + recreate matches new set).  
4. Add new row: new row has no ID; indices reindexed; backend creates new DB rows.  
5. Section not in DOM (e.g. IES project): `objectives` key **absent**; LogicalFrameworkController uses `$request->input('objectives', [])` and M1 guard `isLogicalFrameworkMeaningfullyFilled`; if empty/absent, controller **skips mutation** (no delete).

**Controller:**  
- `LogicalFrameworkController::update()`: `$objectives = $request->input('objectives', []);` then M1 guard; on success deletes `ProjectObjective::where('project_id', $project_id)->delete()` and recreates from `$objectives`. **Uses delete-by-project_id.** No row IDs; purely positional.

---

### 3.2 Budget (institutional)

**Blade:**  
- `phases[0][budget][{{ $budgetIndex }}][particular]`, `phases[0][budget][{{ $budgetIndex }}][rate_quantity]`, `rate_multiplier`, `rate_duration`, `this_phase`.  
- No `budget_id` or row ID.  
- When `$budgetLockedByApproval` is true, inputs are `readonly` and `disabled`; **disabled inputs are not submitted**, so the budget section can send nothing for locked projects.

**JS:**  
- `addBudgetRow(button)`: appends row with `phases[0][budget][${rowCount}][...]`, then `reindexBudgetRows()`.  
- `removeBudgetRow(button)`: removes row, then `reindexBudgetRows()` which rewrites every input name to `phases[0][budget][index]` (0-based).  
- No row IDs; indices are rewritten on add/remove.

**Payload scenarios:**  
1. Submit without changes: `phases[0][budget][0..n]` sent.  
2. Remove row: reindex so indices 0..n-1; backend delete-by project_id+phase then create from payload.  
3. Budget locked: budget inputs disabled → **no** `phases` budget rows in payload. ProjectController merges `phases` to `[]` if missing; BudgetController gets `phases = []`; `isBudgetSectionMeaningfullyFilled([])` is false → **skip mutation** (no wipe).  

**Controller:**  
- `ProjectController::update()` does `$request->merge(['phases' => $request->input('phases', [])]);`.  
- `BudgetController::update()`: normalises via UpdateBudgetRequest; M1 guard; then `ProjectBudget::where('project_id', ...)->where('phase', ...)->delete()` and create from `phases[0]['budget']`. **Uses delete-by-project_id+phase.**  
- BudgetSyncService called after update.

---

### 3.3 RST Beneficiaries Area

**Blade:**  
- `project_area[]`, `category_beneficiary[]`, `direct_beneficiaries[]`, `indirect_beneficiaries[]`.  
- No row IDs.

**JS (inline in partial):**  
- `addRSTProjectAreaRow()`: appends `<tr>` with same names.  
- `removeRSTProjectAreaRow(button)`: removes row. No reindex of **names** (still `project_area[]` etc.); PHP receives arrays by order.

**Controller:**  
- `BeneficiariesAreaController::update()` → `store()`: M1 guard; `ProjectDPRSTBeneficiariesArea::where('project_id', $projectId)->delete()`; then create one row per index from parallel arrays. **Delete-by-project_id.**

---

### 3.4 IES Estimated Expenses

**Blade:**  
- `particulars[]`, `amounts[]`, plus scalars e.g. `total_expenses`, `expected_scholarship_govt`, `support_other_sources`, `beneficiary_contribution`, `balance_requested`.  
- No row IDs for detail rows.

**JS:**  
- Add/remove rows with `particulars[]`, `amounts[]`; totals recalculated. No reindex of names (array submission by order).

**Controller:**  
- IESExpensesController: deletes existing ProjectIESExpenses and details for project, then creates new header + detail rows from `particulars`/`amounts`. **Delete-by-project_id** (via header then details). No M1 in IES expenses (empty array would delete all and create nothing).

---

### 3.5 IIES Estimated Expenses

**Blade:**  
- `iies_particulars[]`, `iies_amounts[]`, plus `iies_total_expenses`, `iies_expected_scholarship_govt`, etc.  
- No row IDs.

**JS:**  
- Add/remove rows; totals. No name reindex.

**Controller:**  
- IIESExpensesController: M1 guard `isIIESExpensesMeaningfullyFilled`; delete existing IIES expenses and details; create from payload. **Delete-by-project_id.**

---

### 3.6 ILP Budget / Revenue / Strength-Weakness / Risk / Attached docs

**Blade:**  
- ILP budget: similar to institutional budget (phase/budget rows).  
- Other ILP sections use their own array shapes (e.g. revenue_goals, strengths/weaknesses, risk rows, attached_docs).  
- No row IDs in audited partials.

**JS:**  
- scripts-edit provides budget add/remove/reindex for shared budget table when present; ILP-specific partials may have inline add/remove.

**Controller:**  
- ILP controllers typically delete-by-project_id and recreate from request arrays.

---

### 3.7 IAH / IGE / CCI / Edu-RUT / LDP / CIC

- Same pattern: Blade uses positional arrays (sometimes `field[]`, sometimes `section[index][field]`). No row IDs in form.  
- Controllers use delete-by-project_id (or equivalent) and recreate from payload.  
- Some have M1 “meaningfully filled” guards (e.g. RST, IIES, Logical Framework, Budget); others do not (e.g. IES expenses).

---

### 3.8 Default Attachments (institutional)

**Blade:**  
- Single new attachment: `file`, `file_name`, `attachment_description`.  
- Existing attachments are display-only (no hidden ids or checkboxes to delete).

**Controller:**  
- AttachmentController::update() is only called when `$request->hasFile('file')`. It **adds** one new attachment; it does not delete or replace existing. So default attachment section is **additive**, not full-state replace.

---

### 3.9 General Info / Key Information

- Flat fields (project_title, society_name, in_charge, etc.). No array rows.  
- Full state is sent whenever the section is in the form. No delete-by-project_id for “rows”; single project row update.

---

## 4. Phase 3 — Risk Classification

| Section | Payload integrity level | Uses delete-by-project_id? | Nested structure? | Financial impact? | Reporting impact? |
|---------|-------------------------|----------------------------|-------------------|--------------------|--------------------|
| Logical Framework | PARTIAL RISK | Yes | Yes (objectives→results/risks/activities→timeframes) | No | Yes (reports/export) |
| Budget | PARTIAL RISK | Yes (by phase) | Yes (phases[0][budget][]) | Yes | Yes |
| RST Beneficiaries Area | SAFE (if section present) | Yes | No | No | Yes |
| IES Expenses | PARTIAL RISK | Yes | No | Yes | Yes |
| IIES Expenses | PARTIAL RISK | Yes | No | Yes | Yes |
| IGE New/Ongoing Beneficiaries | PARTIAL RISK | Yes | No | No | Yes |
| ILP Budget | PARTIAL RISK | Yes | Yes | Yes | Yes |
| Default Attachments | SAFE (additive) | No (append only) | No | No | Low |
| General Info / Key Info | SAFE | N/A | No | Indirect | Yes |
| Sustainability | PARTIAL RISK | Yes (single row) | No | No | Yes |
| CCI / RST / Edu-RUT / LDP / CIC / IAH | PARTIAL RISK | Yes | Varies | Varies | Yes |

**Payload integrity levels:**

- **SAFE:** Section always sends full state when in DOM; or append-only with no replace (attachments).  
- **PARTIAL RISK:** Full-state replace; if key is missing or empty, backend may skip (M1) or wipe (no M1). Depends on controller and request merge.  
- **HIGH RISK:** Section key can be missing while section is “active” (e.g. conditional include bug); no M1.  
- **CRITICAL:** Nested + destructive delete + no IDs + no M1 (not observed as CRITICAL in current code; Logical Framework and Budget have M1).

---

## 5. Phase 4 — Structural Weakness Analysis

### 5.1 Sections not sending IDs

- **All** repeatable-row sections send **no** row IDs: Logical Framework (objectives, results, risks, activities, timeframes), Budget, RST beneficiaries area, IES/IIES expense rows, IGE new/ongoing beneficiaries, ILP budget rows, attachments (create uses `attachments[index][]` without id).  
- Backend cannot “update row 5” or “delete row 3”; it can only replace the full set by position.

### 5.2 Sections reindexing arrays

- **Logical Framework:** Yes (scripts-edit: `updateNameAttributes`, `updateObjectiveNumbers`, reindex results/risks/activities/timeframes).  
- **Budget:** Yes (`reindexBudgetRows()` in scripts-edit).  
- **Attachments (create/edit):** Yes (`updateAttachmentLabels()` in scripts-edit).  
- RST beneficiaries area, IES/IIES expense rows: no name reindex (same `[]` names; order preserved by DOM).

### 5.3 Sections that normalize to []

- **objectives:** `$request->input('objectives', [])` in LogicalFrameworkController; then M1 skips if not meaningfully filled.  
- **phases:** `$request->merge(['phases' => $request->input('phases', [])])` in ProjectController; BudgetController then uses M1 so empty phases do not wipe.  
- Other section controllers use `$request->input('section_key', [])` or equivalent; behaviour varies (some have M1, some do not).

### 5.4 Positional matching only

- Every section that stores multiple rows relies on **array order** to recreate rows. There is no “id” key in the payload to match existing DB rows.

### 5.5 JavaScript mutating DOM before submit

- **scripts-edit.blade.php:** Add/remove rows and reindex **name** attributes so indices are 0-based contiguous. No explicit “serialize and fix payload” before submit; the form’s natural submission is used.  
- **Risk:** If reindex is buggy or partial (e.g. only some inputs updated), backend can see duplicate indices or gaps and interpret as different row count.

### 5.6 Validation rules stripping keys

- **UpdateProjectRequest** does not strip `objectives` or `phases`; it validates a subset of top-level and nested keys.  
- **UpdateBudgetRequest** / **StoreBudgetRequest** normalise numeric fields (e.g. empty string → 0) but do not remove keys.  
- **LogicalFrameworkController** uses `$request->input('objectives', [])` and does not use a FormRequest that would drop nested keys.

### 5.7 Hidden coupling

- **BudgetSyncService:** Called after BudgetController::update and after IES/IIES expense updates; syncs project-level budget-related fields. Depends on correct budget/expense payload so totals are correct.  
- **ProjectDataHydrator:** Used for PDF/export; reads from DB (post-save). No direct payload dependency; indirect dependency that update payloads correctly persist.  
- **ExportController / BudgetExportController:** Consume project + related models from DB. Same indirect dependency.  
- **Approval logic / BudgetSyncGuard:** When project is approved, budget (and IES/IIES expenses) can be locked; disabled inputs are not submitted. Backend must not treat missing budget as “clear budget” (BudgetController M1 handles this).  
- **Quarterly reports:** Depend on project and section data being correct in DB; any payload drop that wipes a section affects reporting.

---

## 6. Phase 5 — Feasibility Report

### 6.1 Per-section audit table (summary)

| Section | Input names / array shape | Row ID in form? | JS reindex? | Controller replace strategy | M1 guard? |
|---------|---------------------------|------------------|-------------|-----------------------------|-----------|
| Logical Framework | objectives[i][objective], [results][j][result], [risks], [activities][k][activity|verification|timeframe] | No | Yes | Delete project objectives, recreate | Yes |
| Budget | phases[0][budget][i][particular|rate_*|this_phase] | No | Yes | Delete project_id+phase, recreate | Yes |
| RST Beneficiaries | project_area[], category_beneficiary[], direct_beneficiaries[], indirect_beneficiaries[] | No | No (array order) | Delete project_id, recreate | Yes |
| IES Expenses | particulars[], amounts[] + scalars | No | No | Delete project IES expenses, recreate | No |
| IIES Expenses | iies_particulars[], iies_amounts[] + scalars | No | No | Delete project IIES expenses, recreate | Yes |
| IGE New Beneficiaries | beneficiary_name[], caste[], address[], etc. | No | Inline (S.No only) | Delete and recreate | Varies |
| Default Attachments | file, file_name, attachment_description (one new) | N/A | N/A | Append one; no replace | N/A |
| General / Key Info | Flat fields | N/A | N/A | Update project row | N/A |

### 6.2 High-risk sections (top 10)

1. **Logical Framework** — Nested, delete-all, no IDs; M1 prevents wipe when absent/empty. Risk: reindex bug or partial submit could corrupt structure.  
2. **Budget** — Financial; delete-by project+phase; M1 when empty. Risk: locked budget sends no rows; merge to [] is correct only if M1 is used.  
3. **IES Estimated Expenses** — Financial; no M1; if key missing or empty, controller could wipe expenses.  
4. **IIES Estimated Expenses** — Financial; has M1. Risk: same as IES if M1 were removed or payload shape changed.  
5. **ILP Budget** — Financial; same pattern as institutional budget.  
6. **RST Beneficiaries Area** — Delete-all; section only in DOM for DP/RST; if key missing, RST controller could receive empty and wipe (depends on M1).  
7. **IGE New/Ongoing Beneficiaries** — Multiple rows; delete and recreate; reporting impact.  
8. **Sustainability** — Single/small set of rows; replace; reporting impact.  
9. **Key Information** — Flat but only for non-Individual; if key fields missing (e.g. draft), prepareForValidation merges some from DB.  
10. **Default Attachments** — Additive only; low risk of wipe but existing attachments not in form (no delete checkboxes).

### 6.3 Payload drop scenarios

- **Section not in DOM (correct):** e.g. IES project → no `objectives`, no `phases` (or empty). Controllers that use `input('key', [])` + M1 skip mutation.  
- **Section in DOM but JS error:** If a script removes or renames inputs before submit, payload can miss rows or have wrong indices; backend will replace with what it receives (fewer rows or wrong order).  
- **Disabled budget inputs:** Locked budget → no budget rows sent; `phases` merged to []; M1 in BudgetController → skip; no wipe.  
- **Draft save:** prepareForValidation in UpdateProjectRequest merges project_type, in_charge, overall_project_budget from DB when save_as_draft and not filled; other section keys are unchanged.

### 6.4 JS weakness summary

- **No IDs on clone:** New rows (objectives, budget, attachments, RST, IGE, etc.) never get a hidden `id`; backend cannot do patch-by-id.  
- **Reindex critical:** Logical Framework and Budget depend on correct reindex after add/remove; any bug causes position-based mismatch.  
- **No pre-submit validation of “full state”:** Form does not verify that visible sections have at least one row or that indices are contiguous before submit.  
- **Multiple inline scripts:** RST, IES, IIES, IGE have add/remove in partials; behaviour is consistent (append/remove by DOM) but duplicated patterns.

### 6.5 Frontend changes required for safe full-state submit

- Ensure every **visible** section sends its **key** even when “empty” (e.g. send `objectives: []` or `phases: [{ budget: [] }]` when section is present but user removed all rows) so backend can distinguish “section not present” from “section present, empty”.  
- Optionally add hidden input per section, e.g. `section_sent[logical_framework]=1`, so backend can treat missing key as “do not touch” when section was not rendered.  
- Keep reindex logic in one place and run it before submit for objectives and budget (or use a single “prepare form” function).  
- When budget is locked, either (a) keep a hidden copy of current phase budget for server-side reference or (b) rely on backend never running budget replace when payload has no budget rows (current M1 behaviour).

### 6.6 Is frontend-only fix viable?

- **Partially.** Frontend can guarantee that whenever a section is in the DOM, its key is present in the payload (even if empty array). That reduces “missing key” wipe risk.  
- It **cannot** fix backend logic that treats “key present but empty” as “delete all” without M1; that requires backend change (M1 or “empty = no change”).  
- It **cannot** introduce row IDs without backend supporting “update/delete by id”; that would require API/contract change.

### 6.7 Is incremental backend required?

- **Yes**, for robustness: (1) Consistently treat missing section key as “no mutation” for that section (when section is type-conditional). (2) Where “empty” should mean “clear section”, document it and use M1 only when “meaningfully filled”; otherwise skip. (3) Consider adding M1 or equivalent to IES expenses so empty payload does not wipe.

### 6.8 Recommended strategy

- **Development Project (institutional):** Ensure Logical Framework and Budget always send their keys when in DOM (even empty). Keep M1 on backend. Add automated test: submit edit with no changes and assert DB unchanged; submit with one row removed and assert row count decreases by one.  
- **IES:** Add M1 (or “empty = no change”) in IESExpensesController so that absent or empty `particulars`/`amounts` does not wipe expenses. Frontend: ensure IES expense section, when visible, always sends at least `particulars` and `amounts` (possibly empty arrays).  
- **IIES:** Already has M1. Ensure frontend sends `iies_particulars`/`iies_amounts` when section is visible; document that empty array + M1 = no mutation.

---

**End of audit.** No code was modified; this document is diagnostic only.
