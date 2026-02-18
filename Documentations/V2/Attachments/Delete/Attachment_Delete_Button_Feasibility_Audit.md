# Attachment Delete Button Feasibility Audit

**Date:** 2026-02-17  
**Scope:** Executor and Applicant users — project attachments delete functionality  
**Purpose:** Audit feasibility of adding delete buttons for attachment sections where Executor/Applicant users manage their projects (owner or in-charge)  
**Constraint:** AUDIT ONLY — No code changes performed

---

## Executive Summary

| Aspect | Finding |
|--------|---------|
| **Overall Feasibility** | **FEASIBLE** with moderate implementation effort |
| **Existing Backend Support** | IES, IIES, IAH, ILP controllers already have `destroy()` methods; routes not exposed; no per-file delete |
| **Permission Model** | `ProjectPermissionHelper::canEdit()` already gates Executor/Applicant by owner/in-charge |
| **Gap** | No routes for project attachment destroy; no delete buttons in project attachment views; no per-file delete API |
| **Reference Implementation** | Report attachments already have per-file delete (`ReportAttachmentController::remove`) and UI delete button |

---

## 1. Project Types and Attachment Architecture

### 1.1 Individual Project Types (Executor/Applicant in Scope)

| Project Type | Constant | Attachment Controller | Storage Prefix | Edit Partial |
|--------------|----------|------------------------|----------------|--------------|
| Individual - Ongoing Educational support | IES | `IESAttachmentsController` | IES | `Edit.IES.attachments` |
| Individual - Initial - Educational support | IIES | `IIESAttachmentsController` | IIES | `Edit.IIES.attachments` |
| Individual - Livelihood Application | ILP | `AttachedDocumentsController` | ILP | `Edit.ILP.attached_docs` |
| Individual - Access to Health | IAH | `IAHDocumentsController` | IAH | `Edit.IAH.documents` |

### 1.2 Institutional Project Types (Generic Attachments)

| Project Type(s) | Attachment Source | Controller | Notes |
|-----------------|-------------------|------------|-------|
| Development Projects, CCI, RST, RUT, IGE, LDP, CIC, Next Phase | `projects.attachments` (ProjectAttachment) | `AttachmentController` | Uses `Edit.attachment` partial; no destroy method |

---

## 2. Current CRUD Operations per Attachment Type

### 2.1 Individual Project Attachments (IES, IIES, IAH, ILP)

| Operation | IES | IIES | IAH | ILP |
|-----------|-----|------|-----|-----|
| **Store** | ✅ Via ProjectController update | ✅ Via ProjectController update | ✅ Via ProjectController update | ✅ Via ProjectController update |
| **Show** | ✅ Edit/Show partials | ✅ Edit/Show partials | ✅ Edit/Show partials | ✅ Edit/Show partials |
| **Update** | ✅ Add files (multi-file per field) | ✅ Add files | ✅ Add files | ✅ Add files |
| **Download** | ✅ Route + Controller | ✅ Route + Controller | ✅ Route + Controller | ❌ Direct Storage::url (no route) |
| **View** | ✅ Route + Controller | ✅ Route + Controller | ✅ Route + Controller | ❌ Direct Storage::url |
| **Destroy (section)** | ✅ `destroy($projectId)` | ✅ `destroy($projectId)` | ✅ `destroy($projectId)` | ✅ `destroy($projectId)` |
| **Destroy (per-file)** | ❌ Not implemented | ❌ Not implemented | ❌ Not implemented | ❌ Not implemented |
| **Destroy route** | ❌ Not registered | ❌ Not registered | ❌ Not registered | ❌ Not registered |
| **Delete button (UI)** | ❌ None | ❌ None | ❌ None | ❌ None |

### 2.2 Generic Project Attachments (Institutional Types)

| Operation | AttachmentController |
|-----------|-----------------------|
| Store | ✅ Via project update |
| Update | ✅ Add new files |
| Download | ✅ `projects.attachments.download` |
| Destroy (per-file) | ❌ No method |
| Destroy (section) | ❌ No method |
| Delete button (UI) | ❌ None |

### 2.3 Report Attachments (Monthly Reports — Reference Implementation)

| Operation | ReportAttachmentController |
|-----------|----------------------------|
| Store | ✅ |
| Update | ✅ Add new files |
| Download | ✅ `reports.attachments.download` |
| **Remove (per-file)** | ✅ **`remove($id)`** — deletes single attachment |
| Route | `Route::delete('/reports/monthly/attachments/{id}', ...)` |
| Delete button (UI) | ✅ In `reports/monthly/partials/edit/attachments.blade.php` |
| Middleware | executor, applicant (inside reports prefix) |

---

## 3. Executor and Applicant Access Model

### 3.1 Permission Rules

**Source:** `app/Helpers/ProjectPermissionHelper.php`

- **canEdit:** Executor/Applicant can edit projects where `user_id === user->id` OR `in_charge === user->id`
- **canUpdate:** Same as canEdit
- **canDelete:** Same as canEdit
- **passesProvinceCheck:** `project.province_id === user->province_id` (or null for admin/general)
- **Project status:** Must be in `ProjectStatus::getEditableStatuses()` (not approved)

### 3.2 Routes and Middleware

- Project edit: `executor/projects/{project_id}/edit` under `role:executor,applicant`
- Project update: `PUT executor/projects/{project_id}/update`
- Attachment download/view: Shared route group `role:executor,applicant,provincial,coordinator,general`
- Report attachment remove: Inside `reports/monthly` prefix with `role:executor,applicant`

### 3.3 Where Executor/Applicant Can Act

- **Project edit page:** Gated by `ProjectPermissionHelper::canEdit()` in `ProjectController@edit`
- **Project update:** Gated by `ProjectPermissionHelper::canUpdate()` in `ProjectController@update`
- **Attachment sections:** Rendered only when user has edit access; no separate attachment-level authorization

---

## 4. Feasibility Analysis: Delete Button for Attachments

### 4.1 Option A: Per-File Delete (Single attachment)

**Use case:** User removes one uploaded file from a field (e.g., one of several Aadhar copies).

| Factor | Assessment |
|--------|------------|
| **Backend** | ❌ Not implemented. IES/IIES/IAH/ILP use `*AttachmentFile` / `*DocumentFile` models; no `deleteFile($fileId)` in controllers |
| **Data model** | ✅ File records have `id`; parent attachments record exists; `files` relationship available |
| **Storage** | ✅ Per-file path stored; `Storage::disk('public')->delete($file->file_path)` is straightforward |
| **Reference** | Report attachments: `ReportAttachmentController::remove($id)` + `ReportAttachment::findOrFail()->delete()` |
| **Feasibility** | **FEASIBLE** — Add `destroyFile($fileId)` to each controller; add `DELETE /projects/{type}/attachments/files/{fileId}` routes; add delete button next to each file in Edit partials |

**Risks:**
- Authorization: Must ensure `$file->project_id` belongs to a project the user can edit. Controllers do **not** currently enforce project-level checks for `downloadFile`/`viewFile`; adding delete would require `ProjectPermissionHelper::canEdit($project, $user)` before delete.
- ILP uses `Storage::url()` in views; may need route-based download/view for consistency before adding delete.

### 4.2 Option B: Section-Level Delete (All attachments for project type)

**Use case:** User clears all IES/IIES/IAH/ILP attachments for the project at once.

| Factor | Assessment |
|--------|------------|
| **Backend** | ✅ `destroy($projectId)` already exists in IES, IIES, IAH, ILP controllers |
| **Routes** | ❌ No routes registered for these destroy methods |
| **Authorization** | Must run in context of project update; `ProjectController@update` already checks `canUpdate`. Destroy route would need same check |
| **Feasibility** | **FEASIBLE** — Add `DELETE /projects/ies/attachments/{projectId}`, etc.; gate with `canEdit`; add "Clear all attachments" button in section header |

**Risks:**
- Destructive: Deletes all files in the section. UX should require confirmation.
- Used internally by `ProjectForceDeleteCleanupService` for project hard delete; ensure no conflict.

### 4.3 Option C: Hybrid (Per-file + optional section clear)

Both Options A and B can coexist. Per-file delete is more granular and aligns with report attachment UX.

---

## 5. Implementation Considerations

### 5.1 Authorization (Critical)

- **Executor/Applicant:** Only for projects where `user_id === user->id` OR `in_charge === user->id`
- **Status:** Project must be editable (`ProjectStatus::isEditable`)
- **Province:** Must pass `passesProvinceCheck`
- **Recommendation:** Create middleware or inline check: load file → load project → `ProjectPermissionHelper::canEdit($project, Auth::user())` before any delete

### 5.2 Routes to Add (if implementing)

```
DELETE /projects/ies/attachments/files/{fileId}      → IESAttachmentsController::destroyFile
DELETE /projects/iies/attachments/files/{fileId}     → IIESAttachmentsController::destroyFile
DELETE /projects/iah/documents/files/{fileId}        → IAHDocumentsController::destroyFile
DELETE /projects/ilp/attached-documents/files/{fileId} → AttachedDocumentsController::destroyFile
```

(Optional section-level:)
```
DELETE /projects/ies/attachments/{projectId}         → IESAttachmentsController::destroy
DELETE /projects/iies/attachments/{projectId}        → IIESAttachmentsController::destroy
...
```

All must live in the shared middleware group `role:executor,applicant,provincial,coordinator,general` (or a subset) and enforce `canEdit` inside the controller.

### 5.3 UI Placement

- **Per-file delete:** Next to each file in the "Existing Files" block, alongside View/Download
- **Pattern:** Match `reports/monthly/partials/edit/attachments.blade.php` (Remove button + fetch to `reports.attachments.remove`)
- **Confirmation:** Use `confirm()` or modal before DELETE request

### 5.4 Data Integrity

- Deleting last file in a field: Parent `*Attachments` / `*Documents` record may remain with no files. Current `destroy($projectId)` deletes parent + storage dir. Per-file delete should not delete parent unless it's the last file — optional cleanup.
- `ProjectAttachmentHandler::handle` uses `updateOrCreate`; multiple files per field are stored in `*AttachmentFile` / `*DocumentFile` tables. Deleting a file row and its storage path is safe.

---

## 6. Summary Table: Feasibility by Project Type

| Project Type | Per-File Delete | Section Delete | Notes |
|--------------|-----------------|----------------|-------|
| IES | Feasible | Feasible (backend ready) | Add route, controller method, UI |
| IIES | Feasible | Feasible (backend ready) | Same |
| IAH | Feasible | Feasible (backend ready) | Same |
| ILP | Feasible | Feasible (backend ready) | Consider adding download/view routes for consistency |
| Generic (Institutional) | Feasible | Requires new destroy logic | AttachmentController has no destroy; ProjectAttachment model |
| Report Attachments | ✅ **Already exists** | N/A | Reference for Executor/Applicant delete UX |

---

## 7. Recommended Approach

1. **Per-file delete** for IES, IIES, IAH, ILP — highest value, matches report attachment UX.
2. **Reuse pattern** from `ReportAttachmentController::remove` and `reports/monthly/partials/edit/attachments.blade.php`.
3. **Enforce** `ProjectPermissionHelper::canEdit($project, $user)` in each new delete action.
4. **Scope** Executor and Applicant to projects where they are owner or in-charge; permission helper already supports this.
5. **Optional:** Section-level delete for power users; add confirmation modal given destructive nature.

---

## 8. Files Referenced in Audit

| Category | Path |
|----------|------|
| Permission helper | `app/Helpers/ProjectPermissionHelper.php` |
| IES controller | `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` |
| IIES controller | `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` |
| IAH controller | `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` |
| ILP controller | `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` |
| Generic attachment | `app/Http/Controllers/Projects/AttachmentController.php` |
| Report attachment | `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php` |
| Routes | `routes/web.php` |
| IES Edit partial | `resources/views/projects/partials/Edit/IES/attachments.blade.php` |
| IIES Edit partial | `resources/views/projects/partials/Edit/IIES/attachments.blade.php` |
| IAH Edit partial | `resources/views/projects/partials/Edit/IAH/documents.blade.php` |
| ILP Edit partial | `resources/views/projects/partials/Edit/ILP/attached_docs.blade.php` |
| Report edit attachments | `resources/views/reports/monthly/partials/edit/attachments.blade.php` |
| Project types | `app/Constants/ProjectType.php` |

---

**End of Audit** — No code changes were made. This document serves as a feasibility assessment for future implementation.
