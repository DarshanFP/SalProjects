# Provincial Society Update — Feasibility Analysis

**Feature:** Allow Provincial users to edit project `society_id` before submission to Coordinator.  
**Analysis type:** Read-only feasibility. No code was modified.

---

## 1. Current Behavior

### 1.1 Where `society_id` Is Handled

| Location | Behavior |
|---------|----------|
| **StoreProjectRequest** | `society_id` required; validated with `Rule::in($allowedSocietyIds)` from `SocietyVisibilityHelper::getAllowedSocietyIds()`. Province-scoped. |
| **UpdateProjectRequest** | `society_id` merged from existing project in `prepareForValidation()` when key is missing. Rules: draft → `nullable\|exists:societies,id`; full submit → `required\|exists:societies,id`. **No `Rule::in($allowedSocietyIds)`** — any existing society ID is accepted. |
| **UpdateGeneralInfoRequest** | `society_id` => `['required', Rule::in($allowedSocietyIds)]`. Province-scoped. **Not used by any route**; ProjectController uses `UpdateProjectRequest` for the full edit form. |
| **GeneralInfoController::update()** | Receives validated data from `UpdateProjectRequest`. Dual-writes `society_id` and `society_name` from selected society. **Does not set `province_id`** when `society_id` changes. |
| **GeneralInfoController::store()** | Sets `province_id` from `Society::find(society_id)->province_id` (or user's province). |

### 1.2 Is `society_id` Currently Editable on Update?

- **Yes.** The edit form includes a society dropdown; when the user submits, `society_id` is in the request and is validated and persisted.
- It is **merged from the existing project** in `UpdateProjectRequest::prepareForValidation()` only when the key is **absent** (so existing value is preserved when field is omitted).

### 1.3 SocietyVisibilityHelper Restriction

- **Create (StoreProjectRequest):** Society choice is restricted to `getAllowedSocietyIds()` (user's province; admin/general see all).
- **Update (UpdateProjectRequest):** Society is **not** restricted by province; only `exists:societies,id` is enforced. So in theory a Provincial could submit a society_id from another province if the request were tampered with.
- **UpdateGeneralInfoRequest** does restrict to `$allowedSocietyIds` but is not used by the edit flow.

### 1.4 Can Provincial Currently Update Society?

- **Yes, when the project is editable.** Provincial has edit rights on all projects in their province when `ProjectStatus::isEditable($project->status)` is true (see Section 3).
- The edit view passes `$societies = SocietyVisibilityHelper::getSocietiesForProjectForm($user)`, so the dropdown only shows societies in the user's province. So via the normal UI, Provincial can only choose a society in their province.
- **Gap:** Backend validation in `UpdateProjectRequest` does not enforce province scope for `society_id`.

---

## 2. Authorization Review

### 2.1 ProjectPermissionHelper::canEdit()

- Province check: `project.province_id === user.province_id` (or user has no province → allow).
- Status check: `ProjectStatus::isEditable($project->status)` must be true.
- Role: For `provincial` and `coordinator`, returns `true` if province and status checks pass.

So **Provincial can edit (including General Info) for any project in their province that is in an editable status.**

### 2.2 UpdateProjectRequest::authorize()

- Loads project by `project_id` and calls `ProjectPermissionHelper::canEdit($project, $user)`.
- No extra restriction on the General Info section for Provincial.

**Conclusion:** There is **no authorization rule** preventing Provincial from editing the General Info section or `society_id` when the project is editable. The only missing piece is validation (province-scoped society list) and province sync when society changes.

---

## 3. Status Constraints

### 3.1 Editable Statuses

`ProjectStatus::getEditableStatuses()`:

- `draft`
- `reverted_by_provincial`, `reverted_by_coordinator`
- `reverted_by_general_as_provincial`, `reverted_by_general_as_coordinator`
- `reverted_to_executor`, `reverted_to_applicant`, `reverted_to_provincial`, `reverted_to_coordinator`

**Not editable:** `submitted_to_provincial`, `forwarded_to_coordinator`, `approved_by_*`, `rejected_by_coordinator`.

### 3.2 When Is the Project “Sent to Coordinator”?

- **Submitted by Executor:** status = `submitted_to_provincial`.
- **Forwarded to Coordinator:** status = `forwarded_to_coordinator` (after Provincial forwards).

So “before submission to Coordinator” means: project is either in **Draft** or **reverted** states (or equivalent), i.e. **before** it is in `forwarded_to_coordinator`. It also implies that once status is `submitted_to_provincial`, the project is **not** editable today (so Provincial cannot change society after the executor has submitted, until the project is reverted).

### 3.3 Feature Requirement vs Current Behavior

- **Likely requirement:** Provincial can change society when status is Draft, or after Executor has submitted but before Provincial forwards (i.e. when status is `submitted_to_provincial`).
- **Current behavior:** Provincial can edit (and thus change society) **only** when status is in `getEditableStatuses()` (draft + reverted). They **cannot** edit when status is `submitted_to_provincial`.
- So if the business wants society edits **after** Executor has submitted but **before** forwarding, that would require a **status/authorization change** (e.g. allow Provincial to edit only certain fields when status is `submitted_to_provincial`). That is out of scope for “minimal” society-update feasibility and is noted as an optional extension.

**Documented current behavior:** Provincial can change society only when the project is in an **editable** status (draft or one of the reverted statuses). They cannot change society once the project is `submitted_to_provincial` or `forwarded_to_coordinator` because the whole project is non-editable in those statuses.

---

## 4. Province Integrity Analysis

### 4.1 Does the New Society Always Belong to the Same Province?

- **UI:** Yes. The society dropdown is built from `SocietyVisibilityHelper::getSocietiesForProjectForm($user)`, which for a user with `province_id` returns only societies where `society.province_id === user.province_id`.
- **Backend:** `UpdateProjectRequest` does **not** validate `society_id` against `getAllowedSocietyIds()`. So if the request is tampered, a society from another province could be accepted. Integrity is currently enforced by the UI, not by the request validation.

### 4.2 What Happens to `project.province_id`?

- **Store:** Set from `Society::find(society_id)->province_id` (or fallback to user’s province). Not taken from the client.
- **Update:** `GeneralInfoController::update()` **does not** set `province_id` when `society_id` changes. So `project.province_id` is left unchanged.

### 4.3 If `project.province_id` ≠ New Society’s Province

- With the current UI-only restriction, the chosen society is always in the user’s province, and the project is already in that province, so in practice they match.
- If backend allowed a society from another province (e.g. via missing validation), then after update we would have `project.society_id` pointing to a society in province B and `project.province_id` still set to province A. That would **break province integrity** (project would appear in wrong province in province-scoped queries and access checks).

**Conclusion:** To keep province integrity and defend against tampering, (1) validation must restrict `society_id` to `getAllowedSocietyIds()`, and (2) when `society_id` changes on update, `province_id` should be set from the new society’s province (defensive sync).

---

## 5. Downstream Impact

### 5.1 Usage of `project->society_id` / `project->society_name`

- **Project model:** `society_id`, `society_name` are fillable; relation `belongsTo(Society::class, 'society_id')`.
- **GeneralInfoController (store/update):** Dual-writes `society_id` and `society_name`.
- **ProjectController (getProjectDetails):** Returns `society_id`, `society_name` (from relation or snapshot).
- **Reports:** AnnualReportService, HalfYearlyReportService, QuarterlyReportService use `optional($project->society)->name ?? $project->society_name`. DPReport has `society_name`; monthly report controllers use project or report `society_name`. They all use **current** project (or report) state.
- **ExportController:** Uses `optional($project->society)->name ?? $project->society_name` for PDF/text.
- **ProjectQueryService:** Search joins `projects.society_id` and falls back to `projects.society_name`.
- **GeneralController / ProvincialController:** Various create/update flows set `society_id` and `society_name`.
- **LogHelper:** Logs include `society_name`.

### 5.2 Assumption of Immutability

- No code path assumes that `society_id` or `society_name` is immutable after creation. Reports and exports read the **current** project (or report) values.
- DPReport and other report models store a **snapshot** (e.g. `society_name`) at report creation; changing the project’s society later does not alter existing reports. New reports use the updated project society. No logic depends on society never changing.

### 5.3 Other Dependencies

- **Attachments / Budgets:** Tied to `project_id`, not `society_id`. No impact.
- **DP_Reports / Status history:** Do not store society; they reference project. No impact.
- **Notifications / PDFs:** Use current project data; no immutability assumption.

**Conclusion:** Allowing Provincial to update `society_id` (with `society_name` and `province_id` kept in sync) does **not** break downstream reports, attachments, budgets, status history, or PDF generation. Existing report snapshots remain as-is; new reports and exports will use the updated society.

---

## 6. Data Integrity Risks

### 6.1 Could Provincial Change Society After Coordinator Review?

- **Currently:** No. Once the project is in a non-editable status (e.g. `forwarded_to_coordinator`, `approved_by_coordinator`), `canEdit()` is false and the update request is unauthorized. So Provincial cannot change society after Coordinator review.

### 6.2 Could Society Change Affect Financial Reports?

- Financial aggregation uses project and report data. Reports store snapshots at creation time. Changing project society does not rewrite past reports. Future reports and aggregations will use the new society. No structural risk if province and validation rules are enforced.

### 6.3 Should the Change Be Logged in Status History?

- `ProjectStatusHistory` records status transitions (previous_status, new_status, user, notes). It does not record field-level changes (e.g. society_id). Adding an optional note or a dedicated audit log when society is changed would improve traceability but is not required for correctness.

### 6.4 Is an Audit Trail Required?

- Not implemented today for society change. For compliance or auditing, consider logging society_id (and optionally society_name) changes (e.g. in activity log or status history notes). This is a recommendation, not a feasibility blocker.

---

## 7. UI Impact

### 7.1 Edit Project General Info View

- **File:** `resources/views/projects/partials/Edit/general_info.blade.php`.
- Society field: `<select name="society_id" id="society_id" class="form-select" required>` populated from `$societies`.
- No role-based disabling or readonly for Provincial; the dropdown is editable when the form is editable.

### 7.2 Society Dropdown Visibility Rules

- `$societies` is set in `ProjectController` (edit flow) via `SocietyVisibilityHelper::getSocietiesForProjectForm($user)`. Provincial users see only societies in their province.

### 7.3 Does Provincial See the Dropdown? Is It Disabled/Readonly?

- Provincial sees the same General Info form as other roles with edit access. The society dropdown is **visible and editable** (not disabled or readonly). So **no UI change is required** to “allow” Provincial to update society; the only requirements are backend validation and province sync.

---

## 8. Feasibility Conclusion

### 8.1 Classification: **B) Requires province sync logic update**

- **A) Safe with minimal change:** No — current update path does not validate society against province and does not sync `province_id` when society changes.
- **B) Requires province sync logic update:** Yes — validation must restrict `society_id` to allowed societies (e.g. `Rule::in($allowedSocietyIds)` in UpdateProjectRequest), and `GeneralInfoController::update()` should set `province_id` from the new society when `society_id` changes.
- **C) Requires migration:** No — no schema change needed.
- **D) High risk due to downstream dependencies:** No — downstream uses current project state or report snapshots; no immutability assumption.

### 8.2 Summary

- Provincial **already has** permission to edit General Info (including society) when the project is in an editable status, and the UI already shows a province-scoped society dropdown.
- To make the feature **safe and consistent**, two changes are needed: (1) **Validation:** restrict `society_id` in `UpdateProjectRequest` to `SocietyVisibilityHelper::getAllowedSocietyIds()` (and optionally keep draft rule relaxed). (2) **Province sync:** in `GeneralInfoController::update()`, when `society_id` is present and changed, set `validated['province_id']` from the selected society’s province.
- No migration, no change to status rules, and no assumption that society is immutable elsewhere. Optional: status rule change if business wants Provincial to edit society when status is `submitted_to_provincial` (before forward); and optional audit log for society changes.

---

## 9. Recommended Safe Implementation Plan (No Code)

### 9.1 Required Code Changes (High Level)

1. **UpdateProjectRequest**
   - In `rules()`, for `society_id` (when not draft): add `Rule::in(SocietyVisibilityHelper::getAllowedSocietyIds())` in addition to required/exists so that province scope is enforced on update.
   - Optionally: when draft, keep nullable but still restrict to `getAllowedSocietyIds()` if present.

2. **GeneralInfoController::update()**
   - When `society_id` is in `$validated` and not empty, resolve the society and set `$validated['province_id'] = $society->province_id` so that province stays in sync with the chosen society (and is robust if validation or UI is bypassed).

### 9.2 Validation

- Ensure `UpdateProjectRequest` uses the same province-scoped society list as `StoreProjectRequest` and `UpdateGeneralInfoRequest` for `society_id` on full submit (and optionally when present on draft).

### 9.3 Authorization

- No change. Provincial already has edit access to projects in their province when status is editable; no extra rule is needed for society.

### 9.4 UI

- No change. Society dropdown is already visible and province-scoped for Provincial.

### 9.5 Logging / Audit

- Optional: when `society_id` (or `society_name`) changes in `GeneralInfoController::update()`, log the change (e.g. old/new society_id or names) in application log or activity/audit table. Optional: add a note in `ProjectStatusService::logStatusChange()` when the change is part of a status transition (if applicable).

### 9.6 Regression Tests

- Request validation: Provincial (and other roles) cannot submit `society_id` outside `getAllowedSocietyIds()` for update.
- Province sync: after updating `society_id` to a society in the same province, `project.province_id` equals that society’s `province_id`.
- Authorization: Provincial can still edit General Info (including society) when status is editable; cannot edit when status is not editable.
- Optional: test that when status is `submitted_to_provincial`, Provincial cannot edit (unless a separate status/field-level rule is introduced).

---

## 10. Risk Mitigation Checklist

- [ ] **Validation:** Add `Rule::in(SocietyVisibilityHelper::getAllowedSocietyIds())` for `society_id` in `UpdateProjectRequest` (for non-draft, and optionally when present for draft) so cross-province society cannot be submitted.
- [ ] **Province sync:** In `GeneralInfoController::update()`, set `province_id` from the selected society when `society_id` is updated so `project.province_id` always matches `project.society.province_id`.
- [ ] **Authorization:** Confirm no new restriction is added that would block Provincial from editing General Info when status is editable.
- [ ] **Status rules:** If business requires Provincial to change society when status is `submitted_to_provincial`, plan a separate change (e.g. allow limited field edits in that status or a dedicated “edit society” action) and document it.
- [ ] **Audit:** Decide whether to log society_id/society_name changes for audit; if yes, add logging in update path.
- [ ] **Regression:** Add/run tests for validation, province sync, and authorization as above.

---

**Document generated:** Feasibility analysis only. **No code was modified.**  
**Feasibility classification:** **B) Requires province sync logic update.**  
**Top 3 risks:** (1) Missing province-scoped validation in UpdateProjectRequest. (2) province_id not synced on society change. (3) Optional: allowing society edit when status is `submitted_to_provincial` would require status/authorization changes.
