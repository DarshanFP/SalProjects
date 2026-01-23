# Comprehensive Review: Data Sent, Stored, and Tables for Creating Report ID DP-0002-02

**Document version:** 1.0  
**Scope:** Monthly Development Project Report creation (Report ID format: `{project_id}-{NN}`)  
**Example:** `DP-0002-02` = 2nd report for project `DP-0002`  
**Primary controller:** `App\Http\Controllers\Reports\Monthly\ReportController`  
**Form Request:** `App\Http\Requests\Reports\Monthly\StoreMonthlyReportRequest`

---

## 1. Report ID Format and Generation

For **DP-0002-02**:

- **`project_id`:** `DP-0002`
- **Suffix:** `02` (second report for that project)
- **Generation:** `ReportController::generateReportId($project_id)`  
  - Finds latest `DP_Reports.report_id` with `LIKE 'DP-0002-%'`, takes numeric part after last `-`, increments, pads to 2 digits.  
  - If no prior report: suffix `01`; next is `02`.

---

## 2. Request Data (Input) — What Is Sent

Data is validated by `StoreMonthlyReportRequest` and read from `$request` in the controller.  
Fields **not** in the Form Request are read via `$request->input()` (e.g. `project_objective_id`, `project_activity_id`, `account_detail_id`, `is_budget_row`).

### 2.1 Core / Header (stored in `DP_Reports`)

| Request field | Validation | Stored column | Notes |
|---------------|------------|---------------|-------|
| `project_id` | required, string, max 255 | `project_id` | FK to `projects.project_id` |
| `save_as_draft` | nullable boolean | — | If `1`/true: status set to `draft`, some blocks skipped when empty |
| `project_title` | nullable, string, max 255 | `project_title` | |
| `project_type` | nullable, string, max 255 | `project_type` | Drives type-specific handlers |
| `place` | nullable, string, max 255 | `place` | |
| `society_name` | nullable, string, max 255 | `society_name` | |
| `commencement_month_year` | nullable, date | `commencement_month_year` | |
| `in_charge` | nullable, string, max 255 | `in_charge` | |
| `total_beneficiaries` | nullable, integer, min 0 | `total_beneficiaries` | |
| `report_month` | required* (nullable if draft), 1–12 | — | Combined into `report_month_year` |
| `report_year` | required* (nullable if draft), 2020–(Y+1) | — | Combined into `report_month_year` |
| `goal` | nullable, string | `goal` | |
| `account_period_start` | nullable, date | `account_period_start` | |
| `account_period_end` | nullable, date, ≥ start | `account_period_end` | |
| `amount_sanctioned_overview` | nullable, numeric, min 0 | `amount_sanctioned_overview` | |
| `amount_forwarded_overview` | nullable, numeric, min 0 | `amount_forwarded_overview` | Always set to `0` in code |
| `amount_in_hand` | nullable, numeric, min 0 | `amount_in_hand` | |
| `total_balance_forwarded` | nullable, numeric, min 0 | `total_balance_forwarded` | |

\* Required when **not** `save_as_draft`.

- **`user_id`:** From `auth()->id()` (nullable if unauthenticated).  
- **`report_id`:** Generated, not from request.  
- **`report_month_year`:** `Carbon::createFromDate(report_year, report_month, 1)`.  
- **`status`:** `'draft'` initially; if `save_as_draft` → remains `draft`; else can be left as `draft` until submit (post-create logic may change it).

### 2.2 Objectives and Activities (stored in `DP_Objectives`, `DP_Activities`)

Skipped when `save_as_draft` and `objective` is empty.

| Request field | Validation / source | Stored table.column | Notes |
|---------------|---------------------|----------------------|-------|
| `objective` | nullable array, `objective.*` string | `DP_Objectives.objective` | |
| `expected_outcome` | nullable array, `*.*` string | `DP_Objectives.expected_outcome` | JSON-encoded |
| `project_objective_id` | from `$request->input()` (not in Form Request) | `DP_Objectives.project_objective_id` | |
| `not_happened` | nullable array | `DP_Objectives.not_happened` | |
| `why_not_happened` | nullable array | `DP_Objectives.why_not_happened` | |
| `changes` | nullable, `*` in `yes,no` | `DP_Objectives.changes` | Stored as boolean (`=== 'yes'`) |
| `why_changes` | nullable array | `DP_Objectives.why_changes` | |
| `lessons_learnt` | nullable array | `DP_Objectives.lessons_learnt` | |
| `todo_lessons_learnt` | nullable array | `DP_Objectives.todo_lessons_learnt` | |
| `activity` | `activity.*.*` string | `DP_Activities.activity` | |
| `month` | `month.*.*` 1–12 | `DP_Activities.month` | |
| `summary_activities` | `summary_activities.*.*.1` (index 1) | `DP_Activities.summary_activities` | |
| `qualitative_quantitative_data` | `qualitative_quantitative_data.*.*.1` | `DP_Activities.qualitative_quantitative_data` | |
| `intermediate_outcomes` | `intermediate_outcomes.*.*.1` | `DP_Activities.intermediate_outcomes` | |
| `project_activity_id` | from `$request->input('project_activity_id.$objIdx')` | `DP_Activities.project_activity_id` | |

- **IDs:**  
  - `objective_id`: `{report_id}-{001,002,…}` (3-digit).  
  - `activity_id`: `{objective_id}-{001,002,…}` (3-digit).  
- **Activity rows:** Only stored if at least one of `month`, `summary_activities`, `qualitative_quantitative_data`, `intermediate_outcomes` is non-empty, or it is an “Add Other” activity (no `project_activity_id` and `activity` filled).

### 2.3 Statements of Account (stored in `DP_AccountDetails`)

Skipped when `save_as_draft` and `particulars` is empty.  
For create, `account_detail_id` is not sent; new rows get auto-increment `account_detail_id`.  
`account_detail_id` and `is_budget_row` are from `$request->input()` (not in Form Request).

| Request field | Stored column | Notes |
|---------------|---------------|-------|
| `particulars` | `particulars` | |
| `amount_sanctioned.{i}` | `amount_sanctioned` | |
| `total_amount.{i}` | `total_amount` | Defaults to `amount_sanctioned` if null |
| `expenses_last_month.{i}` | `expenses_last_month` | |
| `expenses_this_month.{i}` | `expenses_this_month` | |
| `total_expenses.{i}` | `total_expenses` | Defaults to `expenses_last_month + expenses_this_month` if null |
| `balance_amount.{i}` | `balance_amount` | Defaults to `total_amount - total_expenses` if null |
| `account_detail_id.{i}` | — | For **update** only; on create, new row gets new `account_detail_id` |
| `is_budget_row.{i}` | `is_budget_row` | Boolean; from `$request->input('is_budget_row')` |

- `amount_forwarded`: always `0`.  
- `project_id`, `report_id`: from report.

### 2.4 Outlooks (stored in `DP_Outlooks`)

| Request field | Stored column | Notes |
|---------------|---------------|-------|
| `date` | `date` | |
| `plan_next_month` | `plan_next_month` | |

- **`outlook_id`:** `{report_id}-{001,002,…}` (3-digit).

### 2.5 Photos (stored in `DP_Photos` + filesystem)

| Request field / source | Stored column / location | Notes |
|------------------------|--------------------------|-------|
| `photos` (file groups) | — | Array of arrays of uploaded images |
| `photo_activity_id.{groupIndex}` or `photo_activity_id[groupIndex]` | `activity_id` | Resolved to `DP_Activities.activity_id` or `null` (unassigned) |
| `photo_descriptions.{groupIndex}` | `description` | Only when `activity_id` is null (unassigned) |

- **`photo_id`:** `{report_id}-{0001,0002,…}` (4-digit), unique per report.  
- **`photo_path`:** under `storage/app/public/REPORTS/{project_id}/{report_id}/photos/{m_Y}/`.  
- **`photo_location`:** From `ReportPhotoOptimizationService` (EXIF etc.) when optimization runs.  
- **File naming:** Activity-based, e.g. `{report_id}_{mY}_{obj}_{act}_{inc}.{ext}`; unassigned: `_00_00_`.  
- **Limit:** Max 3 photos per `activity_id`; unassigned group shares one “virtual” group.  
- **`reporting_period_from`:** Used in `handlePhotos` and `HandlesReportPhotoActivity` for `m_Y`/folder; `DP_Reports` has `report_month_year` only. If no accessor exists, this may need to use `report_month_year` instead.

### 2.6 Attachments (stored in `report_attachments` + filesystem)

| Request field | Stored / usage | Notes |
|---------------|----------------|-------|
| `attachment_files` | File stored under `REPORTS/{project_id}/{report_id}/attachments/{m_Y}/` | |
| `attachment_names` | `file_name` (after sanitization) | |
| `attachment_descriptions` | `description` | |
| Legacy: `file`, `file_name`, `description` | Same table | Single file |

- **`attachment_id`:** Set in `ReportAttachment::creating` as `{report_id}.{01,02,…}`.

### 2.7 Type-Specific Data (only when `project_type` matches)

| project_type | Handler | Table(s) | Main request fields |
|--------------|---------|----------|----------------------|
| Livelihood Development Projects | `LivelihoodAnnexureController::handleLivelihoodAnnexure` | `qrdl_annexure` | `dla_beneficiary_name`, `dla_support_date`, `dla_self_employment`, `dla_amount_sanctioned`, `dla_monthly_profit`, `dla_annual_profit`, `dla_impact`, `dla_challenges` |
| Institutional Ongoing Group Educational proposal | `InstitutionalOngoingGroupController::handleInstitutionalGroup` | `rqis_age_profiles` | `age_group`, `education`, `up_to_previous_year`, `present_academic_year` |
| Residential Skill Training Proposal 2 | `ResidentialSkillTrainingController::handleTraineeProfiles` | `rqst_trainee_profile` | `education_category`, `number` |
| PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER | `CrisisInterventionCenterController::handleInmateProfiles` | `rqwd_inmates_profiles` | `age_category`, `status`, `number`, `total` |

---

## 3. Database Tables and Columns

### 3.1 Core

#### `DP_Reports` (model: `DPReport`, key: `report_id`)

| Column | Type | Set on create |
|--------|------|----------------|
| `id` | bigint, PK, auto-increment | auto |
| `report_id` | string, unique | generated |
| `project_id` | string, FK → `projects.project_id` | from request |
| `user_id` | FK → `users.id`, nullable | `auth()->id()` |
| `project_title` | string, nullable | from request |
| `project_type` | string, nullable | from request |
| `place` | string, nullable | from request |
| `society_name` | string, nullable | from request |
| `commencement_month_year` | date, nullable | from request |
| `in_charge` | string, nullable | from request |
| `total_beneficiaries` | int, nullable | from request |
| `report_month_year` | date, nullable | from `report_month`+`report_year` |
| `report_before_id` | string, nullable | not set on create |
| `goal` | text, nullable | from request |
| `account_period_start` | date, nullable | from request |
| `account_period_end` | date, nullable | from request |
| `amount_sanctioned_overview` | decimal(15,2), default 0 | from request |
| `amount_forwarded_overview` | decimal(15,2), default 0 | 0 in code |
| `amount_in_hand` | decimal(15,2), default 0 | from request |
| `total_balance_forwarded` | decimal(15,2), default 0 | from request |
| `status` | string, default `draft` | `draft` (or kept `draft` if save_as_draft) |
| `revert_reason` | string, nullable | not set on create |
| `created_at`, `updated_at` | timestamps | auto |

### 3.2 Objectives and Activities

#### `DP_Objectives` (model: `DPObjective`, key: `objective_id`)

| Column | Type | Set on create |
|--------|------|----------------|
| `id` | bigint, PK | auto |
| `objective_id` | string, unique | `{report_id}-{001,…}` |
| `report_id` | string, FK → `DP_Reports.report_id` | report’s `report_id` |
| `project_objective_id` | string, nullable | from request |
| `objective` | text, nullable | from request |
| `expected_outcome` | text, nullable | JSON from request |
| `not_happened` | text, nullable | from request |
| `why_not_happened` | text, nullable | from request |
| `changes` | boolean, nullable | from request (`=== 'yes'`) |
| `why_changes` | text, nullable | from request |
| `lessons_learnt` | text, nullable | from request |
| `todo_lessons_learnt` | text, nullable | from request |
| `created_at`, `updated_at` | timestamps | auto |

#### `DP_Activities` (model: `DPActivity`, key: `activity_id`)

| Column | Type | Set on create |
|--------|------|----------------|
| `id` | bigint, PK | auto |
| `activity_id` | string, unique | `{objective_id}-{001,…}` |
| `objective_id` | string, FK → `DP_Objectives.objective_id` | parent objective |
| `project_activity_id` | string, nullable | from request |
| `activity` | string, nullable | from request |
| `month` | string, nullable | from request |
| `summary_activities` | text, nullable | from request |
| `qualitative_quantitative_data` | text, nullable | from request |
| `intermediate_outcomes` | text, nullable | from request |
| `created_at`, `updated_at` | timestamps | auto |

### 3.3 Account, Outlook, Photos, Attachments

#### `DP_AccountDetails` (model: `DPAccountDetail`, key: `account_detail_id`)

| Column | Type | Set on create |
|--------|------|----------------|
| `account_detail_id` | bigint, PK | auto |
| `project_id` | string, FK → `projects.project_id` | from report |
| `report_id` | string, FK → `DP_Reports.report_id` | from report |
| `particulars` | string, nullable | from request |
| `amount_forwarded` | decimal(15,2), default 0 | 0 |
| `amount_sanctioned` | decimal(15,2), nullable | from request |
| `total_amount` | decimal(15,2), nullable | from request or computed |
| `expenses_last_month` | decimal(15,2), nullable | from request |
| `expenses_this_month` | decimal(15,2), nullable | from request |
| `total_expenses` | decimal(15,2), nullable | from request or computed |
| `balance_amount` | decimal(15,2), nullable | from request or computed |
| `is_budget_row` | boolean, default false | from request |
| `created_at`, `updated_at` | timestamps | auto |

#### `DP_Outlooks` (model: `DPOutlook`, key: `outlook_id`)

| Column | Type | Set on create |
|--------|------|----------------|
| `id` | bigint, PK | auto |
| `outlook_id` | string, unique | `{report_id}-{001,…}` |
| `report_id` | string, FK → `DP_Reports.report_id` | from report |
| `date` | date, nullable | from request |
| `plan_next_month` | text, nullable | from request |
| `created_at`, `updated_at` | timestamps | auto |

#### `DP_Photos` (model: `DPPhoto`, key: `photo_id`)

| Column | Type | Set on create |
|--------|------|----------------|
| `id` | bigint, PK | auto |
| `photo_id` | string, unique | `{report_id}-{0001,…}` |
| `report_id` | string, FK → `DP_Reports.report_id` | from report |
| `activity_id` | string, nullable, FK → `DP_Activities.activity_id` | from `photo_activity_id` |
| `photo_path` | string, nullable | under `REPORTS/…/photos/{m_Y}/` |
| `photo_name` | string, nullable | not set in create (path used) |
| `description` | text, nullable | for unassigned only |
| `photo_location` | string(500), nullable | from optimizer when used |
| `created_at`, `updated_at` | timestamps | auto |

#### `report_attachments` (model: `ReportAttachment`)

| Column | Type | Set on create |
|--------|------|----------------|
| `id` | bigint, PK | auto |
| `attachment_id` | string, unique | `{report_id}.{01,02,…}` in `creating` |
| `report_id` | string, FK → `DP_Reports.report_id` | from report |
| `file_path` | string, nullable | under `REPORTS/…/attachments/{m_Y}/` |
| `file_name` | string, nullable | from request (sanitized) |
| `description` | text, nullable | from request |
| `public_url` | string, nullable | `Storage::url($path)` |
| `created_at`, `updated_at` | timestamps | auto |

### 3.4 Type-Specific

#### `qrdl_annexure` (Livelihood; model: `QRDLAnnexure`)

| Column | Type |
|--------|------|
| `id` | bigint, PK |
| `report_id` | string, FK → `DP_Reports.report_id` |
| `dla_beneficiary_name` | string, nullable |
| `dla_support_date` | date, nullable |
| `dla_self_employment` | text, nullable |
| `dla_amount_sanctioned` | decimal(10,2), nullable |
| `dla_monthly_profit` | decimal(10,2), nullable |
| `dla_annual_profit` | decimal(10,2), nullable |
| `dla_impact` | text, nullable |
| `dla_challenges` | text, nullable |
| `created_at`, `updated_at` | timestamps |

#### `rqis_age_profiles` (Institutional Ongoing; model: `RQISAgeProfile`)

| Column | Type |
|--------|------|
| `id` | bigint, PK |
| `report_id` | string, FK → `DP_Reports.report_id` |
| `age_group` | string, nullable |
| `education` | string, nullable |
| `up_to_previous_year` | int, nullable |
| `present_academic_year` | int, nullable |
| `created_at`, `updated_at` | timestamps |

#### `rqst_trainee_profile` (Residential Skill Training; model: `RQSTTraineeProfile`)

| Column | Type |
|--------|------|
| `id` | bigint, PK |
| `report_id` | string, FK → `DP_Reports.report_id` |
| `education_category` | text, nullable |
| `number` | int, nullable |
| `created_at`, `updated_at` | timestamps |

#### `rqwd_inmates_profiles` (Crisis Intervention; model: `RQWDInmatesProfile`)

| Column | Type |
|--------|------|
| `id` | bigint, PK |
| `report_id` | string, FK → `DP_Reports.report_id` |
| `age_category` | string, nullable |
| `status` | string, nullable |
| `number` | int, nullable |
| `total` | int, nullable |
| `created_at`, `updated_at` | timestamps |

### 3.5 Activity Log

#### `activity_histories` (model: `ActivityHistory`)

After a successful create, `ActivityHistoryService::logReportCreate` inserts:

| Column | Value |
|--------|-------|
| `type` | `'report'` |
| `related_id` | `report_id` (e.g. `DP-0002-02`) |
| `previous_status` | `null` |
| `new_status` | report’s `status` (e.g. `draft`) |
| `action_type` | `'status_change'` |
| `changed_by_user_id` | `$user->id` |
| `changed_by_user_role` | `$user->role` |
| `changed_by_user_name` | `$user->name` |
| `notes` | e.g. `'Report created'` or `'Report saved as draft'` |

---

## 4. Create Flow (ReportController::store)

1. **Validate** via `StoreMonthlyReportRequest`.
2. **Transaction start.**
3. **`generateReportId(project_id)`** → e.g. `DP-0002-02`.
4. **`createReport(validatedData, report_id)`** → 1 row in `DP_Reports`.
5. **`storeObjectivesAndActivities`** (unless draft with no objectives):
   - Upsert `DP_Objectives`, then **`storeActivities`** for `DP_Activities`; delete objectives/activities no longer in request.
6. **`handleAccountDetails`** (unless draft with no particulars):
   - For each particular: update if `account_detail_id` given, else create in `DP_AccountDetails`; then delete rows not in the request.
7. **`handleOutlooks`**:
   - Upsert `DP_Outlooks` by `outlook_id`; delete outlooks not in the request.
8. **`handlePhotos`**:
   - For each photo group: resolve `activity_id`, apply 3-per-activity limit, optimize (optional), store file under `REPORTS/{project_id}/{report_id}/photos/{m_Y}/`, insert `DP_Photos`.
9. **`handleSpecificProjectData`**:
   - By `project_type`: call Livelihood / Institutional / RST / CIC handler → `qrdl_annexure`, `rqis_age_profiles`, `rqst_trainee_profile`, `rqwd_inmates_profiles`.
10. **`handleAttachments`**:
    - For each `attachment_files` (and legacy `file`): `ReportAttachmentController::store` → `report_attachments` + files under `REPORTS/…/attachments/{m_Y}/`.
11. If **`save_as_draft`**: set `status = draft` and `save()`.
12. **Transaction commit.**
13. If not draft: **notifications** (coordinators, provincial if applicable).
14. **`ActivityHistoryService::logReportCreate`** → `activity_histories`.
15. **Redirect:** draft → `monthly.report.edit`; else → `monthly.report.index`.

---

## 5. File Storage Layout (for DP-0002-02)

- **Photos:**  
  `storage/app/public/REPORTS/DP-0002/DP-0002-02/photos/{m_Y}/`  
  - Example: `012026` for Jan 2026.  
  - Naming: `{report_id}_{mY}_{obj}_{act}_{inc}.{ext}` or `{report_id}_{mY}_00_00_{inc}.{ext}` for unassigned.
- **Attachments:**  
  `storage/app/public/REPORTS/DP-0002/DP-0002-02/attachments/{m_Y}/`

`{m_Y}` is derived from the report’s reporting period. `ReportAttachmentController` uses `report_month_year`; `handlePhotos` / `HandlesReportPhotoActivity` use `reporting_period_from`, which is not a column on `DP_Reports`—likely should be `report_month_year` for consistency.

---

## 6. Logs Reviewed

From `storage/logs/laravel.log`:

- **Create (GET):** `Entering create method`, `project_id` (e.g. `DP-0002`), project, budgets, objectives.
- **Store (POST):** `Store method initiated` with `project_id`, `report_month`, `report_year`, `save_as_draft`.
- **After create:** `Report created successfully` with `report_id`; `Transaction committed and report created successfully`; optional `Report saved as draft`; `Processing objective/activity/account detail/outlook`; `handlePhotos` / `handleAttachments` traces.

No specific log line for `DP-0002-02` was found in the searched logs; the structure above is inferred from the code paths that would run when creating that report.

---

## 7. Summary: Tables Touched for DP-0002-02 Create

| Table | When |
|-------|------|
| `DP_Reports` | Always (1 row) |
| `DP_Objectives` | When objectives sent (and not draft with empty objectives) |
| `DP_Activities` | When activities under objectives are filled |
| `DP_AccountDetails` | When particulars sent (and not draft with empty particulars) |
| `DP_Outlooks` | When `date`/`plan_next_month` sent |
| `DP_Photos` | When `photos` uploaded |
| `report_attachments` | When `attachment_files` or legacy `file` sent |
| `qrdl_annexure` | Only if `project_type` = Livelihood Development Projects |
| `rqis_age_profiles` | Only if `project_type` = Institutional Ongoing Group Educational proposal |
| `rqst_trainee_profile` | Only if `project_type` = Residential Skill Training Proposal 2 |
| `rqwd_inmates_profiles` | Only if `project_type` = PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER |
| `activity_histories` | Always after successful create |

For **project `DP-0002`** (“Development Projects”), the type-specific tables **qrdl_annexure, rqis_age_profiles, rqst_trainee_profile, rqwd_inmates_profiles** are **not** used on create; only the first seven tables and `activity_histories` are.

---

## 8. Notes and Inconsistencies

1. **`reporting_period_from` vs `report_month_year`**  
   - `DP_Reports` has `report_month_year`.  
   - `handlePhotos` and `HandlesReportPhotoActivity` use `$report->reporting_period_from` for folder and filename. If there is no accessor, this can be null or wrong; it should likely use `report_month_year`.

2. **`project_objective_id` and `project_activity_id`**  
   - Read in `storeObjectivesAndActivities` via `$request->input()`, not validated in `StoreMonthlyReportRequest`. They are effectively optional from a validation perspective.

3. **`account_detail_id` and `is_budget_row`**  
   - Same: from `$request->input()`, not in Form Request. On create, `account_detail_id` is not used (new rows get new IDs).

4. **`amount_forwarded` / `amount_forwarded_overview`**  
   - Intentionally set to `0` in code for backward compatibility.
