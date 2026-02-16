# M2.1 — NOT NULL Write Path Audit (Controlled Audit)

**Milestone:** M2 — Validation & Schema Alignment  
**Task:** M2.1 — Controlled Audit  
**Rules:** READ-ONLY. No code changes, no refactors, no implementation suggestions, no migrations, no fixes. Forensic audit only.

---

## OBJECTIVE

Prove whether any NOT NULL database column can receive NULL from the project update path.

**In scope:**
1. `projects` table  
2. Section tables updated during `ProjectController@update`  
3. Validation layer (`UpdateProjectRequest`)  
4. `GeneralInfoController@update`  
5. Any `->update($validated)` or `->fill()->save()` usage

---

## STEP 1 — Extract Database Constraints

### Table: `projects`

*Source: `2024_07_20_085634_create_projects_table.php` + alter migrations.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | bigint unsigned | NO | auto | PK |
| project_id | varchar(255) | NO | — | unique |
| user_id | bigint unsigned | NO | — | FK users.id |
| project_type | varchar(255) | NO | — | |
| project_title | varchar(255) | YES | — | |
| society_name | varchar(255) | YES | — | |
| president_name | varchar(255) | YES | — | |
| in_charge | bigint unsigned | NO | — | FK users.id |
| in_charge_name | varchar(255) | YES | — | |
| in_charge_mobile | varchar(255) | YES | — | |
| in_charge_email | varchar(255) | YES | — | |
| executor_name | varchar(255) | YES | — | |
| executor_mobile | varchar(255) | YES | — | |
| executor_email | varchar(255) | YES | — | |
| full_address | text | YES | — | |
| overall_project_period | int | YES | — | |
| current_phase | int | YES | — | |
| commencement_month_year | date | YES | — | |
| commencement_month | tinyint unsigned | YES | — | (added later) |
| commencement_year | smallint unsigned | YES | — | (added later) |
| overall_project_budget | decimal(10,2) | NO | 0.00 | |
| amount_forwarded | decimal(10,2) | YES | — | |
| amount_sanctioned | decimal(10,2) | YES | — | |
| opening_balance | decimal(10,2) | YES | — | |
| local_contribution | decimal(15,2) | YES | 0 | (added later) |
| coordinator_india_name | varchar(255) | YES | — | |
| coordinator_india_phone | varchar(255) | YES | — | |
| coordinator_india_email | varchar(255) | YES | — | |
| coordinator_luzern_name | varchar(255) | YES | — | |
| coordinator_luzern_phone | varchar(255) | YES | — | |
| coordinator_luzern_email | varchar(255) | YES | — | |
| status | varchar(255) | NO | 'draft' | (default set in later migration) |
| goal | text | YES | — | (made nullable in 2026_01_07_162317) |
| predecessor_project_id | varchar(255) | YES | — | FK projects.project_id (added later) |
| problem_tree_file_path | varchar(255) | YES | — | (added later) |
| economic_situation | text | YES | — | (added later) |
| initial_information | text | YES | — | (added later) |
| target_beneficiaries | text | YES | — | (added later) |
| general_situation | text | YES | — | (added later) |
| need_of_project | text | YES | — | (added later) |
| completed_at | timestamp | YES | — | (added later) |
| completion_notes | text | YES | — | (added later) |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL columns (no default):** `project_id`, `user_id`, `project_type`, `in_charge`.  
**NOT NULL with default:** `overall_project_budget` (0.00), `status` ('draft').

---

### Table: `project_budgets`

*Source: `2024_07_20_085654_create_project_budgets_table.php`.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | bigint unsigned | NO | auto | PK |
| project_id | varchar(255) | NO | — | FK projects.project_id |
| phase | int | YES | — | |
| particular | varchar(255) | YES | — | |
| rate_quantity | decimal(10,2) | YES | — | |
| rate_multiplier | decimal(10,2) | YES | — | |
| rate_duration | decimal(10,2) | YES | — | |
| rate_increase | decimal(10,2) | YES | — | |
| this_phase | decimal(10,2) | YES | — | |
| next_phase | decimal(10,2) | YES | — | |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `project_id` only. All data columns nullable.

---

### Table: `project_objectives`

*Source: `2024_08_04_083634_create_project_objectives_table.php` + `2024_08_10_101234_update_project_objectives_table.php` (column renamed description → objective).*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | int unsigned | NO | auto | PK |
| project_id | varchar(255) | NO | — | FK |
| objective_id | varchar(255) | NO | — | unique |
| objective | text | NO | — | (renamed from description) |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `project_id`, `objective_id`, `objective`.

---

### Table: `project_results`

*Source: `2024_08_04_083635_create_project_results_table.php` + rename outcome → result.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | int unsigned | NO | auto | PK |
| result_id | varchar(255) | NO | — | unique |
| objective_id | varchar(255) | NO | — | FK |
| result | text | NO | — | (renamed from outcome) |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `result_id`, `objective_id`, `result`.

---

### Table: `project_risks`

*Source: `2024_08_04_083636_create_project_risks_table.php`; migration uses `description`, model uses `risk` — column assumed `risk` if renamed similarly.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | int unsigned | NO | auto | PK |
| risk_id | varchar(255) | NO | — | unique |
| objective_id | varchar(255) | NO | — | FK |
| description | text | NO | — | (model attribute: risk) |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `risk_id`, `objective_id`, `description`/`risk`.

---

### Table: `project_activities`

*Source: `2024_08_04_083637_create_project_activities_table.php` + `2024_08_10_101301_update_project_activities_table.php` (description → activity).*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | int unsigned | NO | auto | PK |
| activity_id | varchar(255) | NO | — | unique |
| objective_id | varchar(255) | NO | — | FK |
| activity | text | NO | — | (renamed from description) |
| verification | text | NO | — | |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `activity_id`, `objective_id`, `activity`, `verification`.

---

### Table: `project_timeframes`

*Source: `2024_08_04_083638_create_project_timeframes_table.php`.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | int unsigned | NO | auto | PK |
| timeframe_id | varchar(255) | NO | — | unique |
| activity_id | varchar(255) | NO | — | FK |
| month | varchar(255) | NO | — | |
| is_active | boolean | NO | false | |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `timeframe_id`, `activity_id`, `month`, `is_active`.

---

### Table: `project_RST_DP_beneficiaries_area`

*Source: `2024_10_23_032120_create_project_r_s_t_d_p_beneficiaries_areas_table.php`.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | bigint unsigned | NO | auto | PK |
| DPRST_bnfcrs_area_id | varchar(255) | NO | — | unique |
| project_id | varchar(255) | NO | — | FK |
| project_area | varchar(255) | YES | — | |
| category_beneficiary | varchar(255) | YES | — | |
| direct_beneficiaries | int | YES | — | |
| indirect_beneficiaries | int | YES | — | |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `DPRST_bnfcrs_area_id`, `project_id`. All data columns nullable.

---

### Table: `project_sustainabilities`

*Source: `2024_08_04_180601_create_project_sustainabilities_table.php`.*

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|------|
| id | bigint unsigned | NO | auto | PK |
| sustainability_id | varchar(255) | NO | — | unique |
| project_id | varchar(255) | NO | — | FK |
| sustainability | text | YES | — | |
| monitoring_process | text | YES | — | |
| reporting_methodology | text | YES | — | |
| evaluation_methodology | text | YES | — | |
| created_at | timestamp | YES | — | |
| updated_at | timestamp | YES | — | |

**NOT NULL:** `id`, `sustainability_id`, `project_id`. All content columns nullable.

---

*Other section tables (RST, IGE, IES, IAH, IIES, ILP, CCI, EduRUT, CIC, LDP) are written in the update path; many are create/delete-recreate with nullable data columns. Only the tables above and `projects` are fully documented here for NOT NULL focus. Same pattern can be applied to the rest from their migrations.*

---

## STEP 2 — Trace Update Write Path

**Flow:** `ProjectController@update` (line 837) → `UpdateProjectRequest` → `GeneralInfoController@update` → `KeyInformationController@update` → (if institutional) `LogicalFrameworkController@update`, `SustainabilityController@update`, `BudgetController@update`, `AttachmentController@update` → then type-specific section controllers.

### Write Operation Map

| File | Method | Model | Data Source | Risk Level | Why |
|------|--------|-------|------------|-----------|-----|
| GeneralInfoController.php | update | Project | $request->validated() | **HIGH** | validated() can contain null for in_charge, overall_project_budget (nullable in rules); both NOT NULL or defaulted in DB. |
| KeyInformationController.php | update | Project | $request->validate() then selective assign + save() | LOW | Only updates keys that exist in validated; goal etc. nullable in DB. |
| LogicalFrameworkController.php | update | ProjectObjective, ProjectResult, ProjectRisk, ProjectActivity, ProjectTimeframe | $request->input('objectives', []) | **HIGH** | Loops over all objectives/results/risks/activities; does not validate non-empty per row. $objectiveData['objective'], $resultData['result'], $riskData['risk'], $activityData['activity']/['verification'] can be null → NOT NULL columns. |
| SustainabilityController.php | update | Sustainability (project_sustainabilities) | request | LOW | Section content nullable in schema. |
| BudgetController.php | update | ProjectBudget | $validator->validated() | LOW | Uses defaults (e.g. ?? 0, ?? ''); project_budgets data columns nullable. |
| RST/BeneficiariesAreaController.php | update → store | ProjectDPRSTBeneficiariesArea | $request->only(...) | LOW | Table columns nullable; create() with request data. |
| IES/IESExpensesController.php | store/update | ProjectIESExpenses, ProjectIESExpenseDetail | $headerData, particulars, amounts | MEDIUM | fill($headerData) can pass nulls if keys present and null; expense detail uses empty($amount) → 0 dropped (see Step 5). |
| IES/IESFamilyWorkingMembersController.php | store/update | ProjectIESFamilyWorkingMembers | $request->only() | MEDIUM | empty($monthlyIncome) → row with 0 income skipped (numeric empty risk). |
| IAH/IAHEarningMembersController.php | store/update | ProjectIAHEarningMembers | request arrays | MEDIUM | empty($monthlyIncome) → 0 dropped. |
| IAH/IAHBudgetDetailsController.php | store/update | ProjectIAHBudgetDetails | request arrays | MEDIUM | empty($amount) → 0 dropped. |
| IIES/IIESExpensesController.php | store | ProjectIIESExpenses + expenseDetails | validated | LOW | Uses isset/!== null/!== '' for amount; 0 allowed. |
| IIES/IIESFamilyWorkingMembersController.php | update | ProjectIIESFamilyWorkingMembers | validated | LOW | array_key_exists($i, $monthlyIncomes) — does not use empty() on value; 0 allowed. |
| ProjectController.php | update | Project | — | MEDIUM | After commit, if save_as_draft: $project->status = DRAFT; $project->save(); status is NOT NULL but set explicitly. |

---

## STEP 3 — Audit UpdateProjectRequest

**File:** `app/Http/Requests/Projects/UpdateProjectRequest.php`

- **rules():**
  - `project_type`: when draft `nullable|string|max:255`, when not draft `required|string|max:255`.
  - `project_title`, `society_name`, …: all `nullable`.
  - `in_charge`: `nullable|integer|exists:users,id`.
  - `overall_project_budget`: `nullable|numeric|min:0`.
- **prepareForValidation():**
  - Runs only when `save_as_draft` is true.
  - Merges from existing project: `project_type`, `in_charge`, `overall_project_budget` only when the corresponding request key is not filled.
  - Does **not** merge `user_id`, `status`, `project_id` (and those are not in rules), so they are not sent to update from this request.

**Answers:**

1. **If a NOT NULL column key is missing from the request, what value reaches the controller?**  
   Only keys present in the request are in `validated()`. So for example if `in_charge` is missing, it is not in `validated()` and is not passed to `$project->update($validated)` — so that column is not updated. **Exception:** when the key is present and the value is `null` (e.g. user cleared the field). Then `validated()` contains `'in_charge' => null` and that is passed to `update()`. So NOT NULL columns that are **present in validated with value null** can receive NULL.

2. **Does save_as_draft allow missing required fields?**  
   Yes. When `save_as_draft` is true, `project_type` is nullable and prepareForValidation merges `project_type`, `in_charge`, `overall_project_budget` from the existing project when not filled. So for draft, those three are either from request or from DB merge. When **not** draft, nothing merges; if the user omits in_charge or overall_project_budget, they are still nullable in rules, so validated can contain null and that null is written.

3. **Are any NOT NULL columns NOT validated?**  
   - `user_id`, `status`, `project_id` are not in UpdateProjectRequest rules and are not updated by GeneralInfoController (they are not in the form/validated). So they are not at risk from this request.
   - `in_charge` and `overall_project_budget` are validated as **nullable**. So they are validated but can legally be null in validated and then written to DB where they are NOT NULL (or have default). So the risk is “validated as nullable but DB NOT NULL/default”.

### Validation Gap Analysis

| Column | Validation rule | Can be missing? | Can become NULL? | Risk rating |
|--------|----------------|----------------|------------------|-------------|
| project_type | required (when !draft), nullable (draft) | When draft, yes; then merged from project | Only if draft and not merged | MEDIUM (draft path protected by merge) |
| in_charge | nullable | Yes (when not draft) | Yes — if key present and empty/null, validated can be null | **HIGH** |
| overall_project_budget | nullable | Yes (when not draft) | Yes — if key present and null, validated can be null | **HIGH** |
| project_title | nullable | Yes | Yes | LOW (column nullable) |
| goal | nullable | Yes | Yes | LOW (column nullable) |
| status, user_id, project_id | not in rules | N/A (not in request) | Not written by GeneralInfoController | LOW |

---

## STEP 4 — Audit GeneralInfoController@update

**File:** `app/Http/Controllers/Projects/GeneralInfoController.php` (lines 107–189)

- Uses **`$validated = $request->validated()`** only (no raw `$request->all()`).
- Builds `commencement_month_year` from `commencement_year`/`commencement_month`; sets to null if either empty.
- For `amount_forwarded` and `local_contribution`: if key exists in validated, coerces to `?? 0.00`; does not add key if missing.
- Sets `executor_name`, `executor_mobile`, `executor_email` from `$request->input(..., Auth::user()->...)` — never null.
- If `goal` key not in validated, **unsets** it so existing value is not overwritten.
- Maps `predecessor_project` → `predecessor_project_id`, `gi_full_address` → `full_address`.
- Calls **`$project->update($validated)`**.

**Per-column:**

- **in_charge:** In fillable. If `validated['in_charge']` is null (allowed by request rules), it is passed to `update()` and can write NULL to a NOT NULL column. **Not guaranteed non-null.**
- **overall_project_budget:** In fillable. If `validated['overall_project_budget']` is null, it is passed to `update()` and can overwrite the default. **Not guaranteed non-null.**
- **project_type:** When not draft, required in rules so usually present; when draft, merged in prepareForValidation. So only at risk if draft and merge fails (e.g. no project found). **Conditional risk.**
- **executor_*, commencement_month_year, amount_forwarded, local_contribution:** Set or defaulted in controller; not written as null from validated for those.
- **goal:** Unset if not in validated, so missing key does not overwrite with null. If key is in validated and null, would write null (goal column is nullable — OK).

**Conclusion:** GeneralInfoController can write NULL to `in_charge` and `overall_project_budget` when the request sends or allows null for those keys (non-draft submit with empty/missing values that still pass nullable rules).

---

## STEP 5 — Section Controller Numeric Audit

Search: `empty(...)` used on numeric or amount-like fields in the project update path.

| File | Field | Risk | Explanation |
|------|--------|------|--------------|
| IES/IESFamilyWorkingMembersController.php | monthly_income | **Yes** | `!empty($monthlyIncome)` (lines 33, 97). `empty(0)` is true → row with monthly_income = 0 is skipped; 0 is dropped. |
| IES/IESExpensesController.php | amount | **Yes** | `!empty($particular) && !empty($amount)` (line 89). `empty(0)` is true → expense row with amount 0 is skipped. |
| IAH/IAHEarningMembersController.php | monthlyIncome | **Yes** | `!empty($memberName) && !empty($workType) && !empty($monthlyIncome)` (line 49). 0 monthly income drops row. |
| IAH/IAHBudgetDetailsController.php | amount | **Yes** | `!empty($particular) && !empty($amount)` (line 64). 0 amount drops row. |
| IIES/IIESFamilyWorkingMembersController.php | iies_monthly_income | No | Uses `array_key_exists($i, $monthlyIncomes)` (lines 35, 108); does not use empty() on value. 0 is kept. |
| IIES/IIESExpensesController.php | amounts[$index] | No | Uses `isset($amounts[$index]) && $amounts[$index] !== null && $amounts[$index] !== ''` (line 86). 0 is allowed. |
| GeneralInfoController.php | commencement_year, commencement_month | Low | `!empty($validated['commencement_year']) && !empty($validated['commencement_month'])` — used only to build date; 0 is invalid for month/year semantics. Not storing 0 as amount. |
| LogicalFrameworkController.php | objectives array | N/A | `empty($objectives)` — array empty check, not numeric. |
| IGE/IGEBudgetController.php | nameVal | Low | `!empty($nameVal)` — string name, not numeric. |
| IGE/NewBeneficiariesController.php | nameVal | Low | `!empty(trim($nameVal))` — string. |

**Summary:** Numeric fields at risk of “0 dropped” due to `empty()`: **monthly_income** (IES, IAH), **amount** (IES expenses, IAH budget details).

---

## STEP 6 — Final Risk Summary

### M2.1 Conclusion

- **Are NOT NULL violations possible?** **Yes.**

- **Which columns are at risk?**
  - **projects.in_charge** — NOT NULL; can receive NULL from GeneralInfoController when validated contains null (non-draft, nullable rule).
  - **projects.overall_project_budget** — NOT NULL with default; can be overwritten with NULL from GeneralInfoController when validated contains null.
  - **project_objectives.objective** — NOT NULL; LogicalFrameworkController creates rows for every entry in `objectives`; if `$objectiveData['objective']` is null/missing, it is written.
  - **project_results.result** — NOT NULL; same pattern; `$resultData['result']` can be null.
  - **project_risks** (risk/description) — NOT NULL; `$riskData['risk']` can be null.
  - **project_activities.activity / .verification** — NOT NULL; `$activityData['activity']` or `$activityData['verification']` can be null.
  - **project_timeframes.month / .is_active** — NOT NULL; from `$activityData['timeframe']['months']` — key/value can be missing or null in edge cases.

- **Is risk from Validation?**  
  Yes for **projects**: `in_charge` and `overall_project_budget` are nullable in UpdateProjectRequest while DB expects NOT NULL/default.

- **Is risk from Controller write?**  
  Yes: GeneralInfoController passes validated (including possible nulls) to `$project->update($validated)` without ensuring non-null for NOT NULL columns. LogicalFrameworkController does not validate per-row non-empty for objective/result/risk/activity/verification before writing.

- **Is risk from Draft save?**  
  Partially. Draft path is *protected* for project_type, in_charge, overall_project_budget by prepareForValidation merge. The NOT NULL risk is higher on **full submit** when those fields are nullable in rules and can be omitted or cleared.

- **Is risk from Numeric empty() usage?**  
  Yes for **data loss / business logic**: 0 can be dropped for monthly_income (IES, IAH) and amount (IES expenses, IAH budget details). This does not directly cause NOT NULL DB violations but can cause incorrect or missing rows (e.g. 0 amount not stored).

---

**End of M2.1 Audit. No code changes made. Evidence report only.**
