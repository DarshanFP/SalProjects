# Draft vs Submit Architecture Discovery

**Purpose:** Clarify existing draft/save/submit behavior before any changes. Discovery only—no assumptions, no proposed changes, no refactors.

**Output date:** 2026-02-10

---

## 1. Current Project Status System

### 1.1 Source of truth

- **Constants:** `App\Constants\ProjectStatus`
- **File:** `app/Constants/ProjectStatus.php`

### 1.2 All possible project statuses (from code)

| Constant | Value (string) |
|----------|----------------|
| `DRAFT` | `'draft'` |
| `REVERTED_BY_PROVINCIAL` | `'reverted_by_provincial'` |
| `REVERTED_BY_COORDINATOR` | `'reverted_by_coordinator'` |
| `SUBMITTED_TO_PROVINCIAL` | `'submitted_to_provincial'` |
| `FORWARDED_TO_COORDINATOR` | `'forwarded_to_coordinator'` |
| `APPROVED_BY_COORDINATOR` | `'approved_by_coordinator'` |
| `REJECTED_BY_COORDINATOR` | `'rejected_by_coordinator'` |
| `APPROVED_BY_GENERAL_AS_COORDINATOR` | `'approved_by_general_as_coordinator'` |
| `REVERTED_BY_GENERAL_AS_COORDINATOR` | `'reverted_by_general_as_coordinator'` |
| `APPROVED_BY_GENERAL_AS_PROVINCIAL` | `'approved_by_general_as_provincial'` |
| `REVERTED_BY_GENERAL_AS_PROVINCIAL` | `'reverted_by_general_as_provincial'` |
| `REVERTED_TO_EXECUTOR` | `'reverted_to_executor'` |
| `REVERTED_TO_APPLICANT` | `'reverted_to_applicant'` |
| `REVERTED_TO_PROVINCIAL` | `'reverted_to_provincial'` |
| `REVERTED_TO_COORDINATOR` | `'reverted_to_coordinator'` |

Statuses are **strings** in the database; the application uses the `ProjectStatus` class constants.

### 1.3 Editable vs submittable statuses

- **Editable:** `ProjectStatus::getEditableStatuses()` — used to allow editing (e.g. `ProjectPermissionHelper::canEdit()`).
- **Submittable:** `ProjectStatus::getSubmittableStatuses()` — used to allow "Submit to Provincial" (e.g. `ProjectPermissionHelper::canSubmit()`, `ProjectStatusService::submitToProvincial()`).

Both lists are **identical** in code:

- `DRAFT`, `REVERTED_BY_PROVINCIAL`, `REVERTED_BY_COORDINATOR`, `REVERTED_BY_GENERAL_AS_PROVINCIAL`, `REVERTED_BY_GENERAL_AS_COORDINATOR`, `REVERTED_TO_EXECUTOR`, `REVERTED_TO_APPLICANT`, `REVERTED_TO_PROVINCIAL`, `REVERTED_TO_COORDINATOR`.

So: **draft is a real status** stored in the DB, and all "revert" statuses are both editable and submittable.

### 1.4 Where status is assigned

| Context | Where | New status |
|---------|--------|------------|
| **Create (store)** | `ProjectController::applyPostCommitStatusAndRedirect()` | Always `ProjectStatus::DRAFT` (whether user chose "draft" or "create"; only redirect differs). |
| **Submit to Provincial** | `ProjectStatusService::submitToProvincial()` | `ProjectStatus::SUBMITTED_TO_PROVINCIAL` |
| **Edit (update)** | `ProjectController::update()` | **Not set.** Update does not read `save_as_draft` or change `status`. Project keeps current status. |
| **Revert / Forward / Approve / Reject** | `ProjectStatusService` (various methods) | Various revert/forward/approve/reject statuses. |

### 1.5 Status in FormRequest / validation

- **Store:** `StoreProjectRequest` does not validate `status`; status is set in the controller after commit.
- **Update:** `UpdateProjectRequest` does not validate or set `status`; it only accepts `save_as_draft` as `nullable|boolean`. The controller does not use it for status.
- **Submit:** `SubmitProjectRequest::rules()` returns an empty array; no request-level validation. Authorization uses `ProjectPermissionHelper::canSubmit()` (status must be submittable, role executor/applicant, ownership).

### 1.6 Database default vs application default

- **Migration:** `database/migrations/2024_07_20_085634_create_projects_table.php` sets `$table->string('status')->default(value: 'underwriting')`.
- **Application:** New projects are explicitly set to `ProjectStatus::DRAFT` in `applyPostCommitStatusAndRedirect()` after the first save. No migration was found that changes the projects table `status` default from `'underwriting'` to `'draft'`.

**Mismatch:** Schema default is `'underwriting'`; application always overwrites to `'draft'` on create. Any project created without going through this controller path could retain `'underwriting'`.

---

## 2. Current Draft Flow

### 2.1 Create (new project)

- **View:** `resources/views/projects/Oldprojects/createProjects.blade.php`
- **JS:** "Save as draft" adds hidden `input[name="save_as_draft"]` with value `'1'` and submits the form. Draft save is detected via `this.querySelector('input[name="save_as_draft"]')` and `value === '1'`.
- **Route:** `POST` to `projects.store` (project create).
- **Request:** `StoreProjectRequest`.
  - `$isDraft = $this->has('save_as_draft') && $this->input('save_as_draft') == '1'`.
  - **Only conditional rule:** `project_type`: if draft → `nullable|string|max:255`, else `required|string|max:255`. All other fields in this request are nullable.
- **Controller:** `ProjectController::store()` → after successful commit calls `applyPostCommitStatusAndRedirect()`.
- **Status/redirect:**
  - If `save_as_draft == '1'`: `$project->status = ProjectStatus::DRAFT`, `$project->save()`, redirect to `projects.edit` with "Project saved as draft..."
  - If not draft: same status assignment (`DRAFT`) and save; redirect to `projects.index` with "Project created successfully."

So on **create**, draft only changes the redirect and message; **status is DRAFT in both cases**.

### 2.2 Edit (existing project)

- **View:** `resources/views/projects/Oldprojects/edit.blade.php`
- **Form:** `id="editProjectForm"`, `action="{{ route('projects.update', $project->project_id) }}"`, `method="POST"`, `@method('PUT')`.
- **JS (Save as draft button, `#saveDraftBtn`):**
  - On click: prevent default, remove `required` from all elements matching `editForm.querySelectorAll('[required]')`, add or set hidden `input[name="save_as_draft"]` to `'1'`, then `editForm.submit()`.
  - On form submit: if `save_as_draft === '1'`, handler returns `true` (no HTML5 validation); otherwise runs `checkValidity()` / `reportValidity()`.
- **Route:** `PUT` to `projects.update`.
- **Request:** `UpdateProjectRequest`.
  - **Rules:** `project_type` is **always** `required|string|max:255`. There is **no** conditional relaxation for `save_as_draft`. Other fields are nullable or have other rules, but nothing is relaxed when draft is present.
- **Controller:** `ProjectController::update()` never reads `save_as_draft`, never sets `status`, and always redirects to `projects.index` with "Project updated successfully."

So on **edit**:

- **Frontend:** "Save as draft" strips HTML5 `required` and sends `save_as_draft=1` to allow incomplete form submit.
- **Backend:** Validation is **not** relaxed for draft; `project_type` remains required. If the user left required fields empty, validation can fail despite "draft" intent.
- **Status:** Unchanged by update; project keeps its current status (e.g. DRAFT or a revert status).
- **Redirect:** Always to index, never to edit with a "saved as draft" message.

### 2.3 Is draft stored in DB as status?

- **Create:** Yes. After store, status is explicitly set to `ProjectStatus::DRAFT` and saved.
- **Edit:** No. Update does not set status. Draft on edit is only a frontend flag; backend does not treat it as "keep as draft" or "set to draft."

### 2.4 Weak points / mismatches (draft)

1. **Edit vs validation:** Edit "Save as draft" suggests partial data is allowed, but `UpdateProjectRequest` does not relax rules for `save_as_draft`. Users can get validation errors when trying to "save as draft" with incomplete data.
2. **Edit status:** Saving "as draft" on edit does not set or ensure status is DRAFT; status is unchanged.
3. **Create semantics:** On create, both "draft" and "create" result in the same status (DRAFT); the only difference is redirect and message.
4. **Schema default:** DB default for `status` is `'underwriting'`, not `'draft'`; application relies on controller to set DRAFT on create.

---

## 3. Current Submit Flow

### 3.1 Where submit is triggered

- **Page:** Project **show** page only: `resources/views/projects/Oldprojects/show.blade.php` includes `@include('projects.partials.actions', ['project' => $project])`.
- **Partial:** `resources/views/projects/partials/actions.blade.php`.
- **Condition:** For role `executor` or `applicant`, if `$status` is in `ProjectStatus::getEditableStatuses()`, a form is shown: `action="{{ route('projects.submitToProvincial', $project->project_id) }}"`, method POST, button "Submit to Provincial".
- **Route:** `POST /projects/{project_id}/submit-to-provincial` → `ProjectController@submitToProvincial` (name: `projects.submitToProvincial`). Defined in `routes/web.php` (executor/project routes group).

So **submit is only available on the show page**, not on the edit page.

### 3.2 Request and validation

- **Request:** `SubmitProjectRequest`.
  - **Authorize:** `ProjectPermissionHelper::canSubmit($project, $user)` (project in submittable status, user role executor or applicant, ownership).
  - **Rules:** `return [];` — **no additional validation.** Comment in code: "No additional validation needed for submission; the authorization check ensures project is in correct status."

So submit does **not** validate project completeness (e.g. required fields, type-specific data). It only checks status and permission.

### 3.3 Controller and service

- **Controller:** `ProjectController::submitToProvincial(SubmitProjectRequest $request, $project_id)` loads project, gets current user, calls `ProjectStatusService::submitToProvincial($project, $user)`, then redirects back with success or error.
- **Service:** `ProjectStatusService::submitToProvincial()`:
  - Checks `ProjectPermissionHelper::canSubmit()` and `ProjectStatus::isSubmittable($project->status)`.
  - Sets `$project->status = ProjectStatus::SUBMITTED_TO_PROVINCIAL`, saves.
  - Logs to `ActivityHistory` (and legacy `ProjectStatusHistory`).

### 3.4 Does submit lock editing?

- **Indirectly, yes.** Editing is gated by `ProjectPermissionHelper::canEdit($project, $user)`, which requires `ProjectStatus::isEditable($project->status)`. After submit, status becomes `SUBMITTED_TO_PROVINCIAL`, which is **not** in `getEditableStatuses()`. So the Edit link/button is not shown (see `show.blade.php`: `@if($canEdit)`), and any direct PUT to update would fail authorization in `UpdateProjectRequest::authorize()` (same `canEdit` check). So submission moves the project out of "editable" status and effectively locks editing until a revert.

### 3.5 Events

- No application-level events (e.g. Laravel events) were found for submission. Status change is logged via `ProjectStatusService::logStatusChange()` (activity history and legacy table).

---

## 4. Draft vs Submit Comparison

| Layer | Draft behavior | Submit behavior |
|-------|----------------|-----------------|
| **Frontend** | Create: button adds `save_as_draft=1` and submits. Edit: "Save as draft" removes `required` and adds `save_as_draft=1`, then submits. | Show page only: form POST to `projects.submitToProvincial`. No form data except CSRF. |
| **Validation** | Create: `project_type` nullable when draft; other StoreProjectRequest fields already nullable. Edit: **no** relaxation; `project_type` required; draft can still hit validation errors. | Submit: no request validation (`SubmitProjectRequest::rules()` empty). No completeness or data validation. |
| **Controller** | Create: `store()` then `applyPostCommitStatusAndRedirect()` sets status DRAFT in both draft and non-draft; only redirect differs. Edit: `update()` ignores `save_as_draft`, does not set status, always redirects to index. | `submitToProvincial()` loads project, calls `ProjectStatusService::submitToProvincial()`, redirects back. |
| **Service** | No dedicated draft service; status set in controller. | `ProjectStatusService::submitToProvincial()` sets status to SUBMITTED_TO_PROVINCIAL, saves, logs. |
| **DB** | Create: status set to `'draft'` after insert. Edit: status not updated. | Status updated to `'submitted_to_provincial'`. |
| **Status change** | Create: always DRAFT. Edit: none. | DRAFT (or any submittable status) → SUBMITTED_TO_PROVINCIAL. |

---

## 5. Schema Alignment Analysis

### 5.1 Projects table (relevant columns)

- **Source:** `database/migrations/2024_07_20_085634_create_projects_table.php` and later migrations.
- **status:** `$table->string('status')->default(value: 'underwriting')`. No later migration found that changes this default to `'draft'`.
- **goal:** Originally `$table->text('goal');` (NOT NULL). `database/migrations/2026_01_07_162317_make_in_charge_nullable_in_projects_table.php` makes `goal` nullable (comment: "Make goal nullable to support draft saves"). So **current** schema: `goal` nullable.
- **in_charge:** Original migration: `$table->unsignedBigInteger('in_charge');` (NOT NULL, FK to users). The 2026_01_07 migration only changes `goal`; its comment states "in_charge is kept as NOT NULL". So **current** schema: `in_charge` NOT NULL (unless another migration exists that was not found).
- **project_type:** `$table->string('project_type');` — NOT NULL in schema. Application allows nullable in `StoreProjectRequest` when draft; on create with draft, project_type could be null in request but store flow may still set it (not re-checked in this discovery).

### 5.2 Alignment with status logic

- **Application** assumes status is one of `ProjectStatus` constants (e.g. `'draft'`, `'submitted_to_provincial'`). Schema default `'underwriting'` is not in `ProjectStatus::all()`. So:
  - New projects created through the app get overwritten to `'draft'`.
  - Any row with `status = 'underwriting'` (e.g. from migration or legacy) would not be considered editable or submittable by current logic (not in `getEditableStatuses()` / `getSubmittableStatuses()`).
- **Validation vs schema:** UpdateProjectRequest requires `project_type`; schema has `project_type` NOT NULL. Aligned. Goal is nullable in both schema and request. No mismatch on these.

### 5.3 Other project-type / reports tables

- **Reports (e.g. quarterly, half-yearly, annual):** Have `status` with default `'draft'` in their own tables; separate from project status.
- **DP reports:** `status` default `"draft"`.  
These are not the project-level draft/submit flow and are only noted for context.

### 5.4 Mismatches

1. **projects.status default:** DB default `'underwriting'` vs application always setting `'draft'` on create; legacy or non-controller inserts could leave `'underwriting'`.
2. **Edit draft and validation:** Frontend allows "save as draft" with required fields stripped, but backend does not relax validation, so schema/validation can block partial saves that the UI suggests are allowed.

---

## 6. Architectural Gaps Identified

1. **Edit draft has no backend effect:** `save_as_draft` on update is not used to set status or to relax validation; only redirect and messaging differ on create, not on edit.
2. **Submit does not validate completeness:** Submission only checks status and permission; it does not enforce that required fields or type-specific data are present.
3. **Draft vs create on first save:** Same status (DRAFT) and same persistence; only redirect and success message differ. No separate "created but not draft" status.
4. **Schema default vs application:** projects.status default is `'underwriting'` in DB; application uses `'draft'` for new projects. Risk for any code path that creates projects without going through the same controller logic.
5. **Edit "Save as draft" UX vs validation:** Users may expect partial data to be allowed when clicking "Save as draft" on edit; server-side validation can still fail (e.g. required `project_type`), causing confusion.

---

## 7. Clarification Questions for Domain Owner

1. **Draft on edit:** Is draft on the edit page intended to allow saving with partial/incomplete data? If yes, should backend validation be relaxed when `save_as_draft` is present (and if so, which fields)?
2. **Edit and status:** When the user clicks "Save as draft" on edit, should the project status be explicitly set (or kept) to DRAFT? Currently update does not change status.
3. **Submit validation:** Should submission to provincial enforce that the project is "complete" (e.g. required fields, type-specific sections filled)? Currently submit only checks status and permission.
4. **Create vs draft:** Should "Create" (without draft) and "Save as draft" result in different statuses or only in different redirects/messages? Currently both result in DRAFT.
5. **Status lifecycle:** Is the intended lifecycle (e.g. draft → submitted_to_provincial → …) formally defined anywhere? Should `'underwriting'` remain a valid legacy status or be migrated to `'draft'`?
6. **DB constraints:** Should the database enforce required fields (e.g. NOT NULL for project_type or goal) for non-draft projects, or should completeness be enforced only in application validation?
7. **Approval after submit:** Is there an approval workflow after "Submit to Provincial" (e.g. provincial forwards to coordinator, coordinator approves)? Code supports forward/approve/revert; confirmation is sought that this matches business expectations.
8. **Project types and submit rules:** Are there project types that should have different submission or draft rules (e.g. different required fields or different status transitions)?

---

## 8. Assumptions Explicitly Avoided

- No assumption that draft on edit is intended to allow partial data; documented as current behavior and a gap.
- No assumption that submit should validate completeness; documented as not implemented and a clarification question.
- No assumption that "underwriting" should be removed or kept; left as schema/code mismatch and question for domain owner.
- No assumption that status lifecycle is complete or correct; only current code paths and constants are documented.
- No proposed changes, refactors, or remediation; this document is discovery-only.

---

**End of discovery document.**
