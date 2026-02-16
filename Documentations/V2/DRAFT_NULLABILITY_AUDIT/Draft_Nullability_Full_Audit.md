# Draft Nullability Audit

**Purpose:** Identify all NOT NULL / required columns across models and migrations to support making the system fully draft-safe. No code or migration changes—analysis and documentation only.

**Output date:** 2026-02-10

**Domain rule:** Draft must allow partial data; database must NOT block draft saves due to NOT NULL business fields; only structural integrity should remain enforced.

---

## 1. Migration Inventory (All NOT NULL Columns)

The following tables contain columns defined **without** `->nullable()` in migrations. Columns that were later altered to nullable (e.g. `goal` in projects) are noted. **Default** means the column has `->default(...)` but no `->nullable()` (still NOT NULL unless default is applied on insert).

### 1.1 Core application tables

| Table | Column | Type | Nullable? | Default | FK? | Unique? | Notes |
|-------|--------|------|-----------|---------|-----|---------|-------|
| **projects** | id | bigint | No | auto | PK | Yes | Structural |
| projects | project_id | string | No | — | — | Yes | Structural |
| projects | user_id | unsignedBigInteger | No | — | Yes (users.id) | No | Structural |
| projects | project_type | string | No | — | No | No | **Business** (draft may omit) |
| projects | in_charge | unsignedBigInteger | No | — | Yes (users.id) | No | **Decision area** (see §6) |
| projects | overall_project_budget | decimal(10,2) | No | 0.00 | No | No | Business (schema allows 0) |
| projects | status | string | No | 'underwriting' | No | No | Workflow |
| projects | created_at | timestamp | No | — | No | No | Structural |
| projects | updated_at | timestamp | No | — | No | No | Structural |
| projects | goal | text | **Yes** | — | No | No | Made nullable in 2026_01_07 migration |

| **users** | id | bigint | No | auto | PK | Yes | Structural |
| users | name | string | No | — | No | No | Business (auth) |
| users | email | string | No | — | No | Yes | Structural (auth) |
| users | password | string | No | — | No | No | Structural (auth) |
| users | role | enum | No | 'executor' | No | No | Structural |
| users | province | enum | No | — | No | No | Business |
| users | status | enum | No | 'inactive' | No | No | Structural |

### 1.2 Project child / type-specific tables (NOT NULL columns only)

| Table | Column | Type | Nullable? | Default | FK? | Notes |
|-------|--------|------|-----------|---------|-----|--------|
| project_budgets | id | bigint | No | auto | PK | Structural |
| project_budgets | project_id | string | No | — | Yes | Structural |
| project_attachments | id | bigint | No | auto | PK | Structural |
| project_attachments | project_id | string | No | — | Yes | Structural |
| project_objectives | id | int | No | auto | PK | Structural |
| project_objectives | project_id | string | No | — | Yes | Structural |
| project_objectives | objective_id | string | No | — | No | Unique, structural |
| project_objectives | description | text | No | — | No | **Business** |
| project_results | result_id | string | No | — | No | Unique |
| project_results | objective_id | string | No | — | Yes | Structural |
| project_results | outcome | text | No | — | No | **Business** |
| project_risks | risk_id | string | No | — | No | Unique |
| project_risks | objective_id | string | No | — | Yes | Structural |
| project_risks | description | text | No | — | No | **Business** |
| project_activities | activity_id | string | No | — | No | Unique |
| project_activities | objective_id | string | No | — | Yes | Structural |
| project_activities | description | text | No | — | No | **Business** |
| project_activities | verification | text | **Yes** | — | No | Made nullable in 2024_09_22 migration |
| project_timeframes | month | string | No | — | No | **Business** |
| project_sustainabilities | sustainability_id | string | No | — | No | Unique |
| project_sustainabilities | project_id | string | No | — | Yes | Structural |
| Project_EduRUT_Basic_Info | project_id | string | No | — | Yes | Structural |
| Project_EduRUT_Basic_Info | operational_area_id | string | No | — | No | Unique |
| project_edu_rut_* (target_groups, annexed) | project_id | string | No | — | Yes | Structural |
| project_cic_basic_info | project_id | string | No | — | Yes | Structural |
| project_CCI_* (all) | project_id | string | No | — | Yes | Structural |
| project_RST_* (all) | project_id | string | No | — | Yes | Structural |
| project_LDP_* (all) | project_id | string | No | — | Yes | Structural |
| project_IGE_* (all) | project_id | string | No | — | Yes | Structural |
| project_IES_personal_info | project_id | string | No | — | Yes | Structural |
| project_IES_personal_info | IES_personal_id | string | No | — | No | Unique |
| project_IES_personal_info | **contact** | string | No | — | No | **Business** |
| project_ILP_* (all) | project_id | string | No | — | Yes | Structural |
| project_IAH_* (most) | project_id | string | No | — | Yes | Structural (IAH_personal_info has project_id nullable) |
| project_IIES_* (all) | project_id | string | No | — | Yes | Structural |
| project_IIES_personal_info | **iies_bname** | string | No | — | No | **Business** |
| project_IIES_family_working_members | iies_member_name | string | No | — | No | Business |
| project_IIES_family_working_members | iies_work_nature | string | No | — | No | Business |
| project_IIES_family_working_members | iies_monthly_income | decimal | No | — | No | Business |
| project_IIES_expense_details (project_IIES_expense_details) | iies_particular | string | No | — | No | Business |
| project_IIES_expense_details | iies_amount | decimal | No | — | No | Business |
| project_IES_expense_details | particular | string | No | — | No | Business |
| project_IES_expense_details | amount | decimal | No | — | No | Business |
| project_comments | project_id | string | No | — | Yes | Structural |
| project_comments | comment | text | No | — | No | Business |

### 1.3 Report tables (DP, quarterly, half_yearly, annual)

| Table | Column | Type | Nullable? | Default | FK? | Notes |
|-------|--------|------|-----------|---------|-----|--------|
| DP_Reports | project_id | string | No | — | Yes | Structural |
| DP_Reports | report_id | string | No | — | No | Unique |
| quarterly_reports | project_id | string | No | — | Yes | Structural |
| quarterly_reports | report_id | string | No | — | No | Unique |
| quarterly_reports | quarter | tinyInteger | No | — | No | Business |
| quarterly_reports | period_from | date | No | — | No | Business |
| quarterly_reports | period_to | date | No | — | No | Business |
| half_yearly_reports | project_id, report_id, period_from, period_to | — | No | — | — | Same pattern |
| annual_reports | project_id, report_id, period_from, period_to | — | No | — | — | Same pattern |
| quarterly_report_details | quarterly_report_id | unsignedBigInteger | No | — | Yes | Structural |
| half_yearly_report_details | half_yearly_report_id | string | No | — | Yes | Structural |
| annual_report_details | annual_report_id | unsignedBigInteger | No | — | Yes | Structural |
| aggregated_report_photos | photo_path | string | No | — | No | Business |
| activity_histories | type | enum | No | — | No | Structural |
| activity_histories | related_id | string | No | — | No | Structural |
| activity_histories | new_status | string | No | — | No | Structural |
| activity_histories | changed_by_user_id | unsignedBigInteger | No | — | Yes | Structural |
| activity_histories | changed_by_user_role | string | No | — | No | Structural |
| activity_histories | changed_by_user_name | string | No | — | No | Structural |
| notifications | type | string | No | — | No | Structural |
| notifications | title | string | No | — | No | Business |
| notifications | message | text | No | — | No | Business |
| project_status_histories | project_id | string | No | — | Yes | Structural |
| project_status_histories | new_status | string | No | — | No | Structural |
| project_status_histories | changed_by_user_id | unsignedBigInteger | No | — | Yes | Structural |
| budget_correction_audit | project_id | unsignedBigInteger | No | — | — | Structural |
| budget_correction_audit | admin_user_id | unsignedBigInteger | No | — | Yes | Structural |
| budget_correction_audit | action_type | string | No | — | No | Structural |
| societies | province_id | unsignedBigInteger | No | — | Yes | Structural |
| societies | name | string | No | — | No | Business |
| centers | province_id | unsignedBigInteger | No | — | Yes | Structural |
| centers | name | string | No | — | No | Business |
| provinces | name | string | No | — | No | Unique |
| provincial_user_province | user_id | unsignedBigInteger | No | — | Yes | Structural |
| provincial_user_province | province_id | unsignedBigInteger | No | — | Yes | Structural |

### 1.4 File / attachment child tables (project_*_files)

| Table | Column | Nullable? | Notes |
|-------|--------|-----------|--------|
| project_IES_attachment_files | IES_attachment_id, project_id, field_name, file_path, file_name | No | All NOT NULL; file_path/file_name are business content but required for storage |
| project_IIES_attachment_files | IIES_attachment_id, project_id, field_name, file_path, file_name | No | Same |
| project_IAH_document_files | IAH_doc_id, project_id, field_name, file_path, file_name | No | Same |
| project_ILP_document_files | ILP_doc_id, project_id, field_name, file_path, file_name | No | Same |

### 1.5 ILP revenue tables (project_ILP_revenue_plan_items, revenue_income, revenue_expenses)

| Table | Column | Nullable? | Notes |
|-------|--------|-----------|--------|
| project_ILP_revenue_plan_items | project_id, item | string | No | Business |
| project_ILP_revenue_income | project_id, description | string | No | Business |
| project_ILP_revenue_expenses | project_id, description | string | No | Business |

---

## 2. Structural vs Business Classification

### 2.1 STRUCTURAL (Must remain NOT NULL)

- **Primary keys:** `id`, `project_id` (where it is the table PK or unique identifier), `report_id`, `objective_id`, `activity_id`, `risk_id`, `result_id`, and all similar unique row identifiers.
- **Foreign keys required for relationship integrity:**  
  - `projects.user_id` (project must belong to a user).  
  - `projects.project_id` (unique business key).  
  - All `project_id` / `report_id` / `objective_id` / `activity_id` in child tables that enforce parent-child links and cascades.  
  - `activity_histories.changed_by_user_id`, `project_status_histories.changed_by_user_id`, `report_comments.user_id`, etc.
- **Timestamps:** `created_at`, `updated_at` (Laravel convention; required unless explicitly designed nullable).
- **Workflow/audit:** `activity_histories.type`, `new_status`, `changed_by_user_*`; `project_status_histories.new_status`, `changed_by_user_id`; `notifications.type` (needed for routing).
- **Auth/system:** `users.email`, `users.password`, `users.role`; `sessions.payload`, `last_activity`.

**Reasoning:** These columns are required for referential integrity, audit trail, or authentication. Allowing NULL would break joins, cascades, or security.

### 2.2 BUSINESS CONTENT (Candidate for nullable for draft)

- **projects:** `project_type`, `goal` (already nullable), `overall_project_budget` (has default 0; could be nullable with app default), `in_charge` (see §6).
- **project_objectives:** `description`.
- **project_results:** `outcome`.
- **project_risks:** `description`.
- **project_activities:** `description` (verification already nullable).
- **project_timeframes:** `month`.
- **project_IES_personal_info:** `contact`.
- **project_IIES_personal_info:** `iies_bname`.
- **project_IIES_family_working_members:** `iies_member_name`, `iies_work_nature`, `iies_monthly_income`.
- **project_IIES_expense_details:** `iies_particular`, `iies_amount`.
- **project_IES_expense_details:** `particular`, `amount`.
- **project_comments:** `comment` (debatable; comment might be required for the feature).
- **Report tables:** `period_from`, `period_to`, `quarter` (business rules; draft reports might not have period yet).
- **notifications:** `title`, `message` (content; structural is `type`).
- **aggregated_report_photos:** `photo_path` (content; could be optional for draft).
- **societies / centers:** `name` (business; not project-draft but same principle).
- **ILP revenue tables:** `item`, `description` (business content).

**Reasoning:** These hold user- or report-specific content. Draft flows may save rows before this content is filled; making them nullable (or relaxing validation when `save_as_draft`) avoids DB errors while preserving structural FKs.

### 2.3 UNCERTAIN / DECISION AREAS

- **projects.in_charge:** Currently NOT NULL and FK to users. Comment in migration 2026_01_07 states it was "kept as NOT NULL since we always set it to the logged-in user." If draft allows no in-charge yet, this would need to become nullable and code paths that assume `in_charge` (e.g. permission checks) must handle null. **Decision area.**  
- **project_attachments / project_*_files:** `file_path`, `file_name` are NOT NULL. For draft, "attachment row without file yet" could be represented by nullable path/name or a placeholder; current design requires path/name. **Decision area.**  
- **project_comments.comment:** Required for "comment" feature; nullable would allow empty comment rows. **Product decision.**

---

## 3. Model Mapping Analysis

| Model | Table | NOT NULL Fields (from schema) | Risk Level | Notes |
|-------|--------|-------------------------------|------------|--------|
| **Project** (OldProjects\Project) | projects | project_type, in_charge, overall_project_budget (default 0), status | **High** (project_type, in_charge) | PHPDoc: `goal` nullable; `overall_project_budget` string (cast to decimal). Model does not enforce nullability; fillable includes all. If project_type or in_charge become nullable, every switch/case on `$project->project_type` and every use of `$project->in_charge` must be reviewed. |
| ProjectObjective | project_objectives | description | Medium | Used in logical framework; description required when objective exists. Draft may create empty objectives. |
| ProjectResult | project_results | outcome | Medium | Same pattern. |
| ProjectRisk | project_risks | description | Medium | Same pattern. |
| ProjectActivity | project_activities | description | Medium | verification already nullable. |
| ProjectBudget | project_budgets | project_id | Structural | All other columns nullable. |
| ProjectAttachment | project_attachments | project_id | Structural | file_path, file_name nullable in schema. |
| ProjectIESPersonalInfo | project_IES_personal_info | project_id, contact | **High** (contact) | contact NOT NULL; validation/views use it. Making contact nullable requires view/validation review. |
| ProjectIIESPersonalInfo | project_IIES_personal_info | project_id, iies_bname | **High** (iies_bname) | IIES create flow and validation require iies_bname; schema NOT NULL. |
| ProjectIIESFamilyWorkingMembers | project_IIES_family_working_members | project_id, iies_member_name, iies_work_nature, iies_monthly_income | Medium | Business fields; draft may omit. |
| ProjectComment | project_comments | project_id, comment | Medium | comment required by feature. |
| ProjectStatusHistory | project_status_histories | project_id, new_status, changed_by_user_id | Structural | Audit; must remain NOT NULL. |
| ActivityHistory | activity_histories | type, related_id, new_status, changed_by_user_id, changed_by_user_role, changed_by_user_name | Structural | Same. |
| Notification | notifications | type, title, message | Medium (title, message) | title/message are content; type is structural. |
| DPReport | DP_Reports | project_id, report_id | Structural | Other columns nullable or default. |
| QuarterlyReport / HalfYearlyReport / AnnualReport | quarterly_reports etc. | project_id, report_id, period_from, period_to (and quarter for quarterly) | High (periods) | Period required for reporting; draft might not set. |

**Risk levels:**  
- **High:** Changing to nullable would affect many controllers/views or permission logic; requires broad review.  
- **Medium:** Localized impact; a few places need guards or validation relaxation.  
- **Structural:** Do not make nullable without architectural decision.

---

## 4. Validation Layer Mismatch Report

| Request Class | Field | Required? | Schema Nullable? | Mismatch? | Notes |
|---------------|-------|-----------|------------------|-----------|--------|
| StoreProjectRequest | project_type | Conditional (nullable when save_as_draft) | No (NOT NULL) | **Yes** | Draft can submit without project_type; DB will reject if null inserted. Store flow may set project_type before insert; must verify. |
| UpdateProjectRequest | project_type | Always required | No (NOT NULL) | No | Aligned. |
| UpdateGeneralInfoRequest | project_type | required | No | No | Aligned. |
| StoreGeneralInfoRequest | project_type | required | No | No | Aligned. |
| StoreKeyInformationRequest | goal | required | **Yes** (nullable in DB) | **Yes** | Validation requires goal; schema allows NULL. If draft relaxes validation, schema already supports. |
| UpdateKeyInformationRequest | goal | required | Yes | Yes | Same. |
| UpdateIIESPersonalInfoRequest | iies_bname | required | No (NOT NULL) | No | Aligned; both require. |
| StoreIIESPersonalInfoRequest | iies_bname | required | No | No | Aligned. |
| UpdateIIESFinancialSupportRequest | govt_eligible_scholarship, other_eligible_scholarship | required boolean | N/A (boolean in schema) | No | Booleans have default in schema. |
| StoreIIESExpensesRequest / UpdateIIESExpensesRequest | iies_* (totals, balance) | Conditional ($required) | Various | Possible | IIES expense details table has iies_particular, iies_amount NOT NULL. |
| ApproveProjectRequest | commencement_month, commencement_year | required | projects have nullable commencement_month, commencement_year (added in migration) | No | Approval flow requires them; schema allows null until approval. |
| StoreLogicalFrameworkRequest | project_id | required | No | No | Structural. |
| UpdateRSTBeneficiariesAreaRequest | project_area | required array | — | — | Array; not column nullability. |
| UpdateRSTGeographicalAreaRequest | mandal, village, town, no_of_beneficiaries | required array | — | — | Same. |
| StoreEduRUTAnnexedTargetGroupRequest | project_id | required | No | No | Structural. |
| StoreMonthlyReportRequest / UpdateMonthlyReportRequest | project_id | required | No | No | Structural. |

**Summary:**  
- **StoreProjectRequest + projects.project_type:** Risk that draft submit omits project_type; insert could fail if code path does not set a default.  
- **goal:** Schema already nullable; validation is stricter. Relaxing validation for draft (key information) is safe from schema perspective.  
- **IIES/IES personal and expense fields:** Validation and schema largely aligned; making schema nullable for draft would require relaxing these request rules when `save_as_draft` is present.

---

## 5. Runtime Assumption Risk Analysis

| File | Line (approx) | Field | Risk | Suggested Guard Needed? |
|------|----------------|-------|------|-------------------------|
| ExportController | 457, 873, 878, 883, etc. | project_type | High | If project_type nullable: guard or default before switch/in_array (e.g. `$project->project_type ?? ''`). |
| ExportController | 965 | project_type | Medium | Direct interpolation; use `{{ $project->project_type ?? 'N/A' }}` or similar. |
| ExportController | 981 | overall_project_budget | Low | NumberFormatHelper may accept null; resolver uses `?? 0`. Confirm helper handles null. |
| ExportController | 1055–1057, 1064 | goal | Low | Already checks `if ($project->goal)`; safe if goal nullable. |
| ProjectController | 473, 482, 489, 556, 567, etc. | project_type, in_charge, goal, overall_project_budget | High (project_type) | Many switch/case and in_array on project_type; add null coalescing or early return if project_type null. in_charge used for permission; must handle null if made nullable. |
| ProjectFundFieldsResolver | 69–71 | overall_project_budget, amount_forwarded, etc. | None | Already uses `?? 0` for all resolved values. |
| resources/views/projects/partials/Edit/key_information.blade.php | 77, 183 | goal | Low | old('goal', $project->goal) and {{ $project->goal }}; safe for null (empty string). |
| resources/views/projects/Oldprojects/pdf.blade.php | 796 | amount_sanctioned, overall_project_budget, amount_forwarded, local_contribution | Low | Uses ?? 0 and max(0, ...). |
| resources/views/projects/Oldprojects/edit.blade.php | 28, 33, etc. | project_type | High | Multiple @if (project_type === ...). If project_type null, sections may not show; ensure draft UX is intended. |
| resources/views/projects/Oldprojects/show.blade.php | 129, 141, etc. | project_type | High | Same; conditional partials. |
| resources/views/projects/Oldprojects/pdf.blade.php | 222, 234, etc. | project_type | High | Same. |
| resources/views (IES/IIES personal info) | — | contact, personalInfo->contact | Medium | Some views use `$personalInfo->contact ?? 'Not provided'`; others use `old('contact', $personalInfo->contact)`. If contact nullable, ensure no strict comparison or length assumption. |
| GeneralInfoController / KeyInformationController | — | goal, project_type | Medium | Update flows read from request; if request allows null for draft, DB and model accept. Check that no code assumes non-empty string. |

**Summary:**  
- **project_type:** Highest risk. Used in many controllers and views for branching; making it nullable requires guards or defaults everywhere it is used.  
- **in_charge:** Used in permission helpers and controller; if nullable, `ProjectPermissionHelper::isOwnerOrInCharge` and any direct `$project->in_charge` must handle null.  
- **goal, overall_project_budget:** Already guarded or coalesced in critical paths (ExportController, resolver, pdf view).  
- **contact (IES), iies_bname (IIES):** Localized to personal info forms and IIES flows; add null coalescing in views and validation when relaxing for draft.

---

## 6. Foreign Key Integrity Assessment

### 6.1 Clearly structural (must remain NOT NULL)

- **projects.user_id:** Every project must belong to a user. **Keep NOT NULL.**  
- **projects.project_id:** Business primary key; must be unique and not null. **Keep NOT NULL.**  
- All child tables’ **project_id** (project_budgets, project_objectives, project_attachments, project_* type tables, project_comments, etc.): Required for cascade and relationship. **Keep NOT NULL** (except where design explicitly allows orphan rows, which is not current design).  
- **report_id / objective_id / activity_id** in report and objective/activity child tables: **Keep NOT NULL.**  
- **activity_histories.changed_by_user_id**, **project_status_histories.changed_by_user_id**: **Keep NOT NULL.**  
- **report_comments.user_id**: **Keep NOT NULL.**

### 6.2 Decision area: projects.in_charge

- **Current:** NOT NULL, FK to users. Migration comment: "in_charge is kept as NOT NULL since we always set it to the logged-in user."  
- **If draft allows "no in-charge yet":** Making in_charge nullable would allow draft projects without an assigned in-charge. Then:  
  - Permission logic (`ProjectPermissionHelper::isOwnerOrInCharge`) must treat null in_charge (e.g. only owner can edit, or no one).  
  - Controllers that set `in_charge` on create/update must only set it when provided; create draft might set to current user id or leave null by policy.  
- **Recommendation:** Document as **decision area**. If product requires "draft without in-charge," then make nullable and update permission and create/update logic; otherwise keep NOT NULL and ensure draft always sets in_charge (e.g. to current user).

### 6.3 Optional / already nullable FKs

- **project_IAH_personal_info.project_id:** Already nullable in migration (exception among project_* tables).  
- **DP_Reports.user_id:** Nullable.  
- **annual_reports / quarterly_reports.generated_by_user_id:** Nullable.  
- **provinces.provincial_coordinator_id,** **provinces.created_by:** Nullable.  
- **centers.society_id:** Nullable.  
- **notification_preferences.user_id** (if present): Typically required; not project-draft specific.

---

## 7. High-Risk Fields to Review Before Making Nullable

1. **projects.project_type**  
   - Used in: ProjectController (store, update, show, edit), ExportController, all project-type-specific partials and views.  
   - Risk: Branching and type-specific logic assume non-null.  
   - Before nullable: Add `$project->project_type ?? ''` or equivalent in every switch/in_array; decide behavior when empty (e.g. hide type-specific sections, or show "select type" state).

2. **projects.in_charge**  
   - Used in: ProjectPermissionHelper::isOwnerOrInCharge, ProjectController (create payload, logs), views that show in-charge.  
   - Risk: Permission and display logic assume a user id.  
   - Before nullable: Define policy for "no in-charge" (who can edit/view); update helper and any direct comparison.

3. **project_IES_personal_info.contact**  
   - NOT NULL in schema; required in forms.  
   - Risk: Views and validation assume present.  
   - Before nullable: Relax validation when draft; ensure Blade uses `?? 'Not provided'` or similar.

4. **project_IIES_personal_info.iies_bname**  
   - NOT NULL; required in StoreIIESPersonalInfoRequest / UpdateIIESPersonalInfoRequest.  
   - Risk: IIES create/update flow and sub-controller logic assume bname exists.  
   - Before nullable: Relax for draft; audit IIES controllers and views for null.

5. **project_objectives.description, project_results.outcome, project_risks.description, project_activities.description**  
   - Logical framework and activity blocks.  
   - Risk: Export and display may assume non-empty text.  
   - Before nullable: Add guards in export/views; allow empty rows for draft.

6. **project_IIES_family_working_members / expense_details**  
   - iies_member_name, iies_work_nature, iies_monthly_income; iies_particular, iies_amount.  
   - Risk: Creating rows with null in these columns may break validation or display.  
   - Before nullable: Relax validation for draft; ensure list/table views handle null.

7. **project_comments.comment**  
   - Business decision: allow empty comment or keep required.  
   - Before nullable: Product decision; if nullable, ensure listing/display handle null.

8. **Report period columns (period_from, period_to, quarter)**  
   - Required for reporting semantics.  
   - Before nullable: Define whether "draft report" can have no period; if yes, make nullable and guard aggregation/export.

---

## 8. Safe-to-Relax Field List (Business Fields Only)

These are business content fields that can be made nullable for draft **after** the corresponding validation and runtime checks are updated. No structural or FK change.

- **projects:** Already relaxed for draft: `goal` (nullable). Candidate: `project_type` (high impact, see §7), `in_charge` (decision area, see §6).  
- **project_objectives:** `description`.  
- **project_results:** `outcome`.  
- **project_risks:** `description`.  
- **project_activities:** `description` (verification already nullable).  
- **project_timeframes:** `month`.  
- **project_IES_personal_info:** `contact`.  
- **project_IIES_personal_info:** `iies_bname`.  
- **project_IIES_family_working_members:** `iies_member_name`, `iies_work_nature`, `iies_monthly_income`.  
- **project_IIES_expense_details (project_IIES_expense_details):** `iies_particular`, `iies_amount`.  
- **project_IES_expense_details:** `particular`, `amount`.  
- **project_ILP_revenue_plan_items:** `item`.  
- **project_ILP_revenue_income:** `description`.  
- **project_ILP_revenue_expenses:** `description`.  
- **notifications:** `title`, `message` (if product allows notifications with empty content).  
- **aggregated_report_photos:** `photo_path` (only if design allows "photo row without file yet").

**Not listed here:** project_id, user_id, report_id, objective_id, activity_id, and all other structural and FK columns; they belong in §9.

---

## 9. Fields That MUST Remain NOT NULL

- **Primary keys:** All `id`, and business PKs like `project_id`, `report_id`, `objective_id`, `activity_id`, `risk_id`, `result_id` where they are the row identifier.  
- **projects:** `id`, `project_id`, `user_id`, `created_at`, `updated_at`. Also `status` (workflow). **in_charge** and **project_type** are decision areas; see §6 and §7.  
- **users:** `id`, `name`, `email`, `password`, `role`, `status`, `remember_token`, `timestamps`.  
- **All child tables:** `project_id` (or equivalent parent FK) and timestamps where applicable.  
- **activity_histories:** `type`, `related_id`, `new_status`, `changed_by_user_id`, `changed_by_user_role`, `changed_by_user_name`.  
- **project_status_histories:** `project_id`, `new_status`, `changed_by_user_id`.  
- **sessions:** `id`, `payload`, `last_activity`.  
- **password_reset_tokens:** `email`, `token`.  
- **budget_correction_audit:** `project_id`, `admin_user_id`, `action_type`.  
- **societies / centers / provinces:** Structural FKs and names that are part of core entity identity (e.g. provinces.name unique).  
- **File/attachment tables:** At minimum the parent FK (e.g. project_id, attachment_id); file_path/file_name are design-dependent (see §2.3).

---

## 10. Final Risk Summary

| Category | Count / Scope | Risk |
|----------|----------------|------|
| **Structural NOT NULL** | All PKs, FKs, timestamps, workflow/audit columns | Do not relax without architectural decision. |
| **Business NOT NULL (high impact)** | projects.project_type, projects.in_charge | Relaxing requires codebase-wide review (controllers, views, permission). |
| **Business NOT NULL (localized)** | project_IES.contact, project_IIES.iies_bname, objective/result/risk/activity description/outcome, IIES family/expense fields, project_comments.comment | Relaxing requires validation + view/controller review per area. |
| **Validation vs schema** | StoreProjectRequest allows null project_type when draft; schema does not. goal required in validation but nullable in schema. | Fix either validation (draft must send default) or schema (allow null and ensure insert path supports it). |
| **Runtime assumptions** | project_type used in many switch/in_array; in_charge in permission; goal/budget mostly guarded. | Add null coalescing and branches for project_type and in_charge before making nullable. |
| **Already draft-safe** | goal (nullable), ProjectFundFieldsResolver (?? 0), ExportController goal check, pdf view numeric ?? 0 | No change needed for these. |
| **Uncertain** | in_charge (product policy), file_path/file_name (design for "attachment without file"), report period columns | Document and decide before changing. |

**Recommendation order for draft-safety (no migrations in this doc):**  
1. Resolve **StoreProjectRequest + project_type** mismatch (either require project_type on insert or make column nullable and audit store flow).  
2. Relax **validation** for draft where schema already allows null (e.g. goal in KeyInformation when save_as_draft).  
3. Add **runtime guards** for project_type and in_charge wherever they are used, if product decides to allow null for draft.  
4. Then consider **migrations** to make business fields nullable (description, outcome, contact, iies_bname, etc.) and relax corresponding FormRequest rules when `save_as_draft` is set.  
5. Keep all structural and FK columns NOT NULL unless an explicit design decision is made and documented.

---

**End of audit. No migrations or code changes were suggested; only analysis and documentation.**
