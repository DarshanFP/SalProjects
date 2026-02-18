# Project Delete Feature Audit

**Date:** 2026-02-16  
**Scope:** Read-only audit. No code or routes modified.  
**Objective:** Determine whether project deletion is implemented, how it is authorized, what data is affected, and what risks exist.

---

## 1. Current Delete Route Status

### Findings

- **No route is registered for project deletion** in `routes/web.php`.
- Searched for: `delete`, `destroy`, `projects/{project_id}`, `Route::delete`.
- **Project-related routes present:** GET/POST/PUT for `projects/{project_id}` (show, edit, update, submit, approve, reject, revert, add-comment, download-pdf/doc, activity-history). **No** `Route::delete` for projects.
- **Other delete routes in app:** `profile.destroy`, notification `destroy`, `general.deleteProvince`, `general.deleteSociety`, `general.deleteCenter`, report attachment remove, photos remove. None target projects.

### Conclusion

| Item | Status |
|------|--------|
| Route name for project delete | **None** |
| Middleware | N/A (route not registered) |
| Controller method | Exists (`ProjectController::destroy`) but **not exposed** |

**Delete is implemented in code but not enabled via routing.**

---

## 2. Controller Implementation

### Execution path: Route → Controller → Service → Model

- **Route:** Not registered (see §1).
- **Controller:** `App\Http\Controllers\Projects\ProjectController::destroy($project_id)`.

### Flow of `ProjectController::destroy($project_id)`

1. **Transaction:** `DB::beginTransaction()`.
2. **Load project:** `Project::where('project_id', $project_id)->firstOrFail()`.
3. **Authorization:** `ProjectPermissionHelper::canDelete($project, Auth::user())` → on failure `abort(403, '...')`.
4. **Non-individual types only:** If `project_type` is in the list (Rural-Urban-Tribal, CICI, IOGEP, LDP, RST, DP, NEXT PHASE, CIC):
   - `$this->sustainabilityController->destroy($project_id)`.
   - `$this->logicalFrameworkController->destroy($project_id)` (see risk in §5).
   - Comment in code: "Attachments are handled by the model's cascading delete (assumed via foreign key)".
5. **Type-specific deletions:** `switch ($project->project_type)` calls the corresponding section controllers’ `destroy($project_id)` (EduRUT, CIC, CCI, IGE, LDP, RST, IES, ILP, IAH, IIES). Each deletes its own child tables (and for IES/IIES/IAH/ILP, attachment files — see §6).
6. **Delete project row:** `$project->delete()` (hard delete).
7. **Commit and redirect:** `DB::commit()`, redirect to `projects.index` with success message. On exception: `DB::rollBack()`, redirect back with error.

### Alternative entry point (not routed)

- **GeneralInfoController::destroy($project_id)** exists:
  - Loads project, calls `$project->delete()` **with no authorization check**.
  - No `canDelete()` or `canEdit()`.
  - If this method were ever exposed (e.g. API), it would be an **authorization gap**.

### Summary

| Aspect | ProjectController::destroy | GeneralInfoController::destroy |
|--------|----------------------------|--------------------------------|
| Authorization | Uses `canDelete()` | **None** |
| Child cleanup | Explicit controller calls + FK cascade | None (relies on FK only) |
| File cleanup | Via type-specific controllers for IES/IIES/IAH/ILP | None |
| Exposed by route | No | No |

---

## 3. Authorization Logic

### Does delete use `ProjectPermissionHelper::canDelete()`?

- **ProjectController::destroy:** Yes. `if (!ProjectPermissionHelper::canDelete($project, Auth::user())) { abort(403, '...'); }`.
- **GeneralInfoController::destroy:** No (see §2).

### What does `canDelete()` check?

- In `App\Helpers\ProjectPermissionHelper`:
  - `canDelete(Project $project, User $user): bool` is implemented as **`return self::canEdit($project, $user);`**.

### What does `canEdit()` enforce?

1. **Province:** `passesProvinceCheck($project, $user)` — if `user->province_id !== null`, then `project->province_id` must equal `user->province_id`.
2. **Editable status:** `ProjectStatus::isEditable($project->status)` must be true (draft and all revert statuses; not submitted/forwarded/approved/rejected).
3. **Role-based:**
   - **provincial / coordinator:** can edit (and delete) any project in their province.
   - **executor / applicant:** only if `project->user_id === user->id` or `project->in_charge === user->id`.
   - **admin / general:** can edit (and delete).

### Comparison: `canDelete()` vs `canEdit()`

- **They are identical:** `canDelete()` delegates entirely to `canEdit()`.
- So delete is allowed only when edit is allowed: same province, editable status, and same role/ownership rules.

### Conclusion

- Delete authorization **matches** edit (province, status, ownership/in_charge, role).
- **Gap:** `GeneralInfoController::destroy` has no authorization; it must not be exposed without adding the same check.

---

## 4. Soft Delete vs Hard Delete

### Project model

- **File:** `App\Models\OldProjects\Project`.
- **No** `SoftDeletes` trait. No `deleted_at` column usage.
- **Delete type:** **Hard delete.** Row is removed from `projects` table.

### Child tables

- No application-level soft delete on project children; behavior is driven by DB FKs and controller logic (see §5).
- When `$project->delete()` runs, the database cascades (where defined) and controller has already deleted type-specific data for the current project type.

---

## 5. Child Table Impact

### Tables / areas checked

| Area | FK to projects | ON DELETE | Manual delete in ProjectController | Orphan risk |
|------|----------------|-----------|------------------------------------|-------------|
| project_budgets | Yes | CASCADE | No (rely on cascade) | No |
| project_attachments | Yes | CASCADE | No | No (DB). File residue: see §6 |
| project_comments | Yes | CASCADE | No | No |
| project_objectives (+ results, risks, activities, timeframes) | Yes | CASCADE | logicalFrameworkController->destroy(project_id) * | No (cascade). *Bug: see below |
| project_status_histories | Yes | CASCADE | No | No |
| activity_histories | **No** (related_id, no FK) | — | No | **Yes** (orphan rows) |
| project_IAH_document_files | Yes | CASCADE | No (IAH controller deletes dir + parent row) | No |
| project_IIES_attachment_files | Yes | CASCADE | No (IIES controller deletes dir + parent) | No |
| project_ILP_document_files | Yes | CASCADE | No (ILP controller deletes files + parent) | No |
| project_IES_attachment_files | Yes | CASCADE | No (IES controller deletes dir + parent) | No |
| DP_Reports | Yes | CASCADE | No | No |
| Report attachments / comments | Via report_id | CASCADE | No | No |
| Quarterly/Half-yearly/Annual reports | Yes | CASCADE | No | No |
| project_IIES_* / project_IES_* / project_IAH_* / project_ILP_* / project_EduRUT_* / CCI / IGE / LDP / RST / CIC | Various | Some FKs, many no FK in migrations | Yes, via section controllers | Low if controller runs; some tables may have no FK (application-level cleanup only) |

### Logical framework controller bug (non-individual types)

- For non-individual types, `ProjectController::destroy` calls `$this->logicalFrameworkController->destroy($project_id)`.
- `LogicalFrameworkController::destroy($id)` expects an **objective id** (`objective_id`), not `project_id`: it does `ProjectObjective::findOrFail($id)` then deletes that objective and its results/risks/activities.
- Passing `$project_id` (e.g. `"DP-0001"`) will typically cause `findOrFail` to throw (no objective with that id). That will roll back the whole project delete transaction.
- **Impact:** Full project delete for non-individual types may **fail** at the logical framework step. Objectives are still removed when the project row is deleted (FK `project_objectives.project_id` → `projects.project_id` ON DELETE CASCADE), but the explicit controller call is wrong and causes failure.
- **Conclusion:** Logic bug; cascade alone would remove objectives. Fix would be either to remove the `logicalFrameworkController->destroy($project_id)` call for project-wide delete or to add a dedicated method that deletes by `project_id`.

### activity_histories

- Table has `type` and `related_id` (e.g. project_id or report_id). There is **no foreign key** from `activity_histories` to `projects`.
- When a project is deleted, rows with `type = 'project'` and `related_id = project_id` are **not** removed → **orphan records**.

---

## 6. Attachment Impact

### When a project is deleted

- **ProjectController::destroy** does not call any generic “delete all project files” service. It relies on:
  1. Type-specific controllers for IES, IIES, IAH, ILP (see below).
  2. FK CASCADE for `project_attachments` (and type-specific attachment tables) so DB rows are removed when the project row is deleted.

### Type-specific attachment / document controllers (files on disk)

| Type | Controller destroy | Disk cleanup |
|------|--------------------|--------------|
| IES | IESAttachmentsController::destroy($projectId) | `Storage::deleteDirectory("project_attachments/IES/{$projectId}")` |
| IIES | IIESAttachmentsController::destroy($projectId) | `Storage::deleteDirectory("project_attachments/IIES/{$projectId}")` |
| IAH | IAHDocumentsController::destroy($projectId) | `Storage::deleteDirectory("project_attachments/IAH/{$projectId}")` |
| ILP | AttachedDocumentsController::destroy($projectId) | `$documents->deleteAttachments()` then delete directory if empty |

So for **individual types (IES, IIES, IAH, ILP)**, project delete **does** trigger controller-level destroy, which **does** remove files from storage for those types.

### Generic `project_attachments` (non-individual: e.g. Problem Tree)

- Used by `AttachmentController`; stored under `project_attachments/{$projectType}/{$project_id}` (e.g. DP, RUT, CCI).
- **No** `deleting` / `boot` logic on `ProjectAttachment` to remove files.
- When the project is deleted, FK CASCADE deletes `project_attachments` rows; the **files on disk are not deleted** → **file residue** for non-individual project attachments.

### Conclusion

| Scenario | DB | Disk |
|---------|----|------|
| Individual (IES/IIES/IAH/ILP) | Deleted (controller + cascade) | Deleted (controller) |
| Non-individual (e.g. DP, RUT) generic attachments | Deleted (cascade) | **Not deleted** (no Storage cleanup) |

---

## 7. Data Integrity Risks

Classification:

| Risk | Level | Description |
|------|--------|-------------|
| **A) Safe to enable delete** | — | Only after addressing C, D, E and the logical framework bug. |
| **B) Orphan data** | **Yes** | `activity_histories` rows with `type='project'` and deleted `related_id` remain (no FK to projects). |
| **C) File residue** | **Yes** | Generic `project_attachments` (non-individual) files under `project_attachments/{type}/{project_id}` are not removed from disk. |
| **D) Financial/report inconsistency** | **Low** | Reports and budgets are deleted via CASCADE. Deleting an approved project with reports is a business decision; the DB stays consistent. |
| **E) Missing authorization check** | **Yes** | `GeneralInfoController::destroy` has no permission check; must not be exposed. |
| **F) Logic bug** | **Yes** | `logicalFrameworkController->destroy($project_id)` is called with `project_id` but expects `objective_id`; causes project delete to fail for non-individual types. |

---

## 8. Comparison With Edit Rules

### Intended rule (from audit)

Delete should only be allowed when:

- `canEdit()` is true.
- Project status is editable.
- Executor/Applicant is owner or in_charge.
- Province matches.

### Current implementation

- **ProjectController::destroy** uses `canDelete()` which is identical to `canEdit()`.
- So the same conditions (province, editable status, role/ownership) are enforced for delete as for edit.
- **GeneralInfoController::destroy** does not enforce any of this.

### Verification

- **canDelete()** = **canEdit()** → delete and edit rules are aligned where the main controller is used.
- No extra “delete-only” restriction (e.g. “no reports” or “draft only”) is applied; if the project is editable, it is also deletable.

---

## 9. Recommendation (NO CODE APPLIED)

1. **Do not expose project delete** until:
   - GeneralInfoController::destroy is either removed or secured with `canDelete()` (and not exposed by route).
   - Logical framework: fix or remove the `logicalFrameworkController->destroy($project_id)` call so full project delete does not throw for non-individual types.
   - Orphan and file residue risks are accepted or mitigated (activity_histories, generic project_attachments files).
2. **If a route is added later,** it should:
   - Use **ProjectController::destroy** (not GeneralInfoController::destroy).
   - Use the same middleware as other project mutation routes (e.g. auth, role).
   - Use DELETE or POST to a dedicated delete URL; do not expose GeneralInfoController::destroy without authorization.
3. **Keep** using **hard delete** unless product requires soft delete and audit trail; in that case a separate design (SoftDeletes, policies, reporting) is needed.

---

## 10. Safe Implementation Strategy (Next Phase)

When enabling delete is approved:

1. **Authorization**
   - Ensure only **ProjectController::destroy** is routable.
   - Add `ProjectPermissionHelper::canDelete()` (or equivalent) to any other entry point that can delete a project (e.g. future API). Do not expose GeneralInfoController::destroy without the same check.
2. **Logical framework**
   - Either remove the call `logicalFrameworkController->destroy($project_id)` for project-wide delete (rely on FK CASCADE), or add a method that deletes all objectives (and children) by `project_id` and call that instead.
3. **Attachments (disk)**
   - Before or when deleting the project, delete files for **generic** `project_attachments`: e.g. loop attachments for the project, delete each `file_path` from storage, or delete directory `project_attachments/{type}/{project_id}` for the project’s type. Prefer doing this inside the same transaction boundary (e.g. before `$project->delete()`) or in a dedicated service called from `ProjectController::destroy`.
4. **Activity history**
   - Decide policy: either add an FK from `activity_histories` to `projects` with ON DELETE CASCADE (migration), or explicitly delete `activity_histories` rows where `type = 'project'` and `related_id = $project_id` in the same transaction before `$project->delete()`.
5. **Route**
   - Add a single route (e.g. `Route::delete('...projects/{project_id}', [ProjectController::class, 'destroy'])->name('projects.destroy')`) inside the same middleware group as other project routes.
6. **Testing**
   - Test delete for each project type (individual and non-individual).
   - Test authorization (province, status, owner/in_charge, role).
   - Test that reports, budgets, comments, and (where applicable) attachment files are gone and that no orphan rows remain in `activity_histories`.

---

**End of audit. No code or routes were modified.**
