# Wave 2 — IES Per-File Delete (Production Safe)

## 1. Executive Summary

**Scope:** Backend-only addition of per-file delete for IES attachments in `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` and one new route in `routes/web.php`.

**Objective:** Allow authorised users to delete a single IES attachment file when the project is in an editable state. Delete is a **mutation** and enforces province isolation, editable status, and `canEdit()`. Approved projects and cross-province / ID-guessing access are blocked with 403.

**Result:** New method `destroyFile($fileId)` and route `DELETE /projects/ies/attachments/files/{fileId}` named `projects.ies.attachments.files.destroy`. Download/view logic unchanged. No UI changes. No other controllers modified.

---

## 2. Why Delete is Mutation and Requires Stricter Guards Than Read

- **Read (download/view):** Intended for viewing evidence; allowed for anyone with **view** access to the project, including when the project is approved. Guards: province + `canView()`.
- **Mutation (store, update, per-file delete):** Changes project data. Must be restricted to projects that are still **editable** and to users who have **edit** permission. Guards: province + `ProjectStatus::isEditable()` + `canEdit()`.

Per-file delete alters both storage and the database. If we did not enforce editable status, users could remove attachment files from approved projects, undermining audit and approval integrity. Therefore delete uses the same strict guards as other IES mutation surfaces (e.g. store/update), not the read guards.

---

## 3. Guard Chain Implemented

Order applied in `destroyFile($fileId)`:

1. `$file = ProjectIESAttachmentFile::findOrFail($fileId)`
2. `$project = $file->project ?? $file->iesAttachment?->project` — if no project → 404 JSON
3. `$user = Auth::user()`
4. `if (!ProjectPermissionHelper::passesProvinceCheck($project, $user)) abort(403)`
5. `if (!ProjectStatus::isEditable($project->status)) abort(403)`
6. `if (!ProjectPermissionHelper::canEdit($project, $user)) abort(403)`
7. `$file->delete()` (model `deleting` event removes file from storage)
8. Return JSON: `{ "success": true, "message": "Attachment deleted successfully." }`

---

## 4. Route Added

| Item   | Value |
|--------|--------|
| **Path** | `DELETE /projects/ies/attachments/files/{fileId}` |
| **Name** | `projects.ies.attachments.files.destroy` |
| **Controller** | `IESAttachmentsController@destroyFile` |
| **Middleware** | Same group as IES download/view: `auth`, `role:executor,applicant,provincial,coordinator,general` (web group, CSRF protected) |

---

## 5. Controller Method Added

- **File:** `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`
- **Method:** `public function destroyFile($fileId)`
- **Behaviour:** Guard chain as above; then `$file->delete()`; JSON response on success; 404 for missing file/project; 403 on guard failure; 500 on unexpected exception. Parent `destroy($projectId)` and read methods (`downloadFile`, `viewFile`) unchanged.

---

## 6. Storage Deletion Strategy Used

- **Strategy:** Rely on the **model deleting event** on `ProjectIESAttachmentFile`.
- **Implementation:** In `ProjectIESAttachmentFile::boot()`, a `deleting` listener runs and, if the file path exists on `Storage::disk('public')`, deletes it. The controller does **not** call `Storage::disk('public')->delete()` explicitly; calling `$file->delete()` is sufficient to remove both the DB row and the file from disk.
- **Rationale:** Single place for storage cleanup; avoids duplicate delete logic and ensures storage is cleaned even if the controller is bypassed (e.g. model deleted elsewhere in future).

---

## 7. Security Scenarios Validated

| Scenario | Expected | Implementation |
|----------|----------|-----------------|
| Owner of editable project, same province | Allowed | passesProvinceCheck, isEditable, canEdit pass → delete runs. |
| In-charge of editable project, same province | Allowed | Same as above. |
| Provincial user, editable project in their province | Allowed | canEdit true for provincial → delete allowed. |
| Approved project | 403 | `ProjectStatus::isEditable($project->status)` false → abort(403). |
| Cross-province user | 403 | `passesProvinceCheck` fails → abort(403). |
| Random file ID from another project (same province, executor not owner/in_charge) | 403 | canEdit false → abort(403). |
| Executor not owner/in_charge (same province) | 403 | canEdit false → abort(403). |

---

## 8. Confirmation: No Other Controllers Modified

- **Modified:** `App\Http\Controllers\Projects\IES\IESAttachmentsController` (added `destroyFile` only) and `routes/web.php` (added one route).
- **Not modified:** IIES, IAH, ILP, Generic, Reports, or any other controller. No shared traits or abstractions introduced.

---

## 9. Confirmation: No Parent Rows Auto-Deleted

- **Per-file delete** removes only the `ProjectIESAttachmentFile` row (and its file on disk via the model event). The parent `ProjectIESAttachments` row is **not** deleted. No cascading delete was added. The existing `destroy($projectId)` method (which deletes the whole IES attachment set for a project) was not changed.

---

## 10. Risk Assessment

| Risk | Mitigation |
|------|-------------|
| **IDOR (guessing file IDs)** | Project is resolved from the file; province, editable status, and canEdit are enforced. A file ID from another project or another province yields 403 (or 404 if no project). |
| **Province bypass** | `passesProvinceCheck($project, $user)` runs before any mutation; cross-province users receive 403. |
| **Approved project mutation** | `ProjectStatus::isEditable($project->status)` blocks delete on approved (and other non-editable) projects; 403 returned. |
| **Unauthorised edit (wrong role/ownership)** | `canEdit($project, $user)` ensures only users who may edit the project (owner, in_charge, or provincial/coordinator/general/admin in province) can delete the file. |

---

*Wave 2 — Backend-only per-file delete. Mutation surface with stricter guards than read. No UI. No abstraction. IES controller and routes only.*
