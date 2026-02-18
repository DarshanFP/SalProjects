# Per-File Attachment Delete — Full Audit & Architectural Strategy

**Date:** 2026-02-17  
**Mode:** AUDIT + ARCHITECTURAL STRATEGY ONLY  
**Constraint:** NO CODE MODIFICATIONS • NO REFACTOR • VERIFIED PER CONTROLLER

---

## 1. Executive Summary

| Aspect | Finding |
|--------|---------|
| **Goal** | Add DELETE button next to each uploaded file (alongside Show/Download) for Executor/Applicant on projects they own or are in-charge of |
| **Scope** | Per-file delete only (one file at a time); append-only upload model preserved |
| **Security Posture** | **CRITICAL GAPS** — IES, IIES, IAH, ILP, AttachmentController, ReportAttachmentController: download/view/remove do NOT enforce `ProjectPermissionHelper::canEdit()`, project status, or province isolation at controller level. Authorization relies on middleware + edit-page gate only. |
| **Reference Implementation** | Report attachments: `ReportAttachmentController::remove($id)` — per-file delete exists; **no** project-level authorization; route under `executor,applicant` middleware |
| **Data Model** | IES/IIES/IAH/ILP: File models have `project_id`; `File → Parent → Project` chain resolvable; per-file delete is data-safe |
| **ILP Distinct** | ILP uses `Storage::url()` for View/Download (direct URL); no route-based streaming; **SECURITY GAP: Direct URL exposure** |

---

## 2. Attachment Architecture Map

### 2.1 Individual Project Types (IES, IIES, IAH, ILP)

| Controller | File Model | Parent Model | FK to Parent | Project FK | Storage Path | Storage Disk |
|------------|------------|--------------|--------------|------------|--------------|--------------|
| IESAttachmentsController | ProjectIESAttachmentFile | ProjectIESAttachments | IES_attachment_id | project_id | project_attachments/IES/{projectId} | public |
| IIESAttachmentsController | ProjectIIESAttachmentFile | ProjectIIESAttachments | IIES_attachment_id | project_id | project_attachments/IIES/{projectId} | public |
| IAHDocumentsController | ProjectIAHDocumentFile | ProjectIAHDocuments | IAH_doc_id | project_id | project_attachments/IAH/{projectId} | public |
| AttachedDocumentsController (ILP) | ProjectILPDocumentFile | ProjectILPAttachedDocuments | ILP_doc_id | project_id | project_attachments/ILP/{projectId} | public |

**Relationship Chain (verified):**

- `ProjectIESAttachmentFile` → `project()`, `iesAttachment()` → `Project` via `project_id` or via `iesAttachment->project`
- `ProjectIIESAttachmentFile` → `project()`, `iiesAttachment()` → `Project`
- `ProjectIAHDocumentFile` → `project()`, `iahDocument()` → `Project`
- `ProjectILPDocumentFile` → `project()`, `ilpDocument()` → `Project`

### 2.2 Generic Project Attachments (Institutional)

| Controller | File Model | Parent | Project FK | Storage Path | Storage Disk |
|------------|------------|--------|------------|--------------|--------------|
| AttachmentController | ProjectAttachment | N/A (flat) | project_id | project_attachments/{projectType}/{projectId} | public |

**Note:** ProjectAttachment has no parent; each row = one file. `project_id` on file directly.

### 2.3 Report Attachments (Reference)

| Controller | File Model | Parent | Project FK | Storage Path | Storage Disk |
|------------|------------|--------|------------|--------------|--------------|
| ReportAttachmentController | ReportAttachment | DPReport (report) | None on model | REPORTS/{project_id}/{report_id}/attachments/{monthYear} | public |

**Chain:** ReportAttachment → report() → DPReport; DPReport has `project_id`. Project resolved via `$attachment->report->project_id`.

---

## 3. Authorization Audit Findings

### 3.1 Download/View — Per Controller

| Controller | Method | ProjectPermissionHelper | Status Check | Province Check | Notes |
|------------|--------|-------------------------|--------------|----------------|-------|
| IESAttachmentsController | downloadFile, viewFile | ❌ No | ❌ No | ❌ No | Middleware only |
| IIESAttachmentsController | downloadFile, viewFile | ❌ No | ❌ No | ❌ No | Middleware only |
| IAHDocumentsController | downloadFile, viewFile | ❌ No | ❌ No | ❌ No | Middleware only |
| AttachedDocumentsController (ILP) | — | N/A | N/A | N/A | No download/view routes; uses Storage::url() |
| AttachmentController | downloadAttachment | ❌ No | ❌ No | ❌ No | Middleware only |
| ReportAttachmentController | downloadAttachment | ❌ No | ❌ No | ❌ No | Report-level auth in edit; remove has none |
| ReportAttachmentController | remove | ❌ No | ❌ No | ❌ No | No project/report permission check |

### 3.2 Middleware & Route Placement

| Route / Action | Middleware | Controller-Level Auth |
|----------------|------------|------------------------|
| projects.ies.attachments.download, view | auth, role:executor,applicant,provincial,coordinator,general | None |
| projects.iies.attachments.download, view | Same | None |
| projects.iah.documents.download, view | Same | None |
| projects.attachments.download | Same | None |
| reports.attachments.remove | auth, role:executor,applicant (inside reports/monthly prefix) | None |
| reports.attachments.download | auth, role:executor,applicant,provincial,coordinator,general | None |

### 3.3 Where Authorization IS Enforced

- **Project edit page:** `ProjectController@edit` — uses `ProjectPermissionHelper::canEdit($project, $user)` before rendering form
- **Project update:** `ProjectController@update` — uses `ProjectPermissionHelper::canUpdate($project, $user)`
- **Report edit:** `ReportController@edit` — filters by `ProjectQueryService::getProjectIdsForUser($user)` for executor/applicant

**Gap:** Once on edit page, user can call attachment download/view/remove URLs directly (e.g. via devtools or shared link) for any file ID they guess. No controller-level validation that the file belongs to a project they can edit.

---

## 4. Identified Security Gaps

| # | Gap | Severity | Affected |
|---|-----|----------|----------|
| 1 | Download/view/remove do not resolve project and call `canEdit()` | **HIGH** | IES, IIES, IAH, ILP, AttachmentController, ReportAttachmentController |
| 2 | No status check — approved projects can still allow attachment ops if route is hit | **HIGH** | All project attachment controllers |
| 3 | No province check — cross-province access if ID guessed | **HIGH** | All project attachment controllers |
| 4 | ILP uses `Storage::url()` — direct URL to file; no controller gate | **HIGH** | ILP only |
| 5 | Report remove has no report-level permission check | **MEDIUM** | ReportAttachmentController::remove |
| 6 | ID guessing — predictable IDs could allow enumeration | **MEDIUM** | All (mitigated by adding canEdit) |

---

## 5. ProjectAttachmentHandler & Data Integrity

### 5.1 Handler Behavior

- **Location:** `App\Services\ProjectAttachmentHandler`
- **Role:** Validates, stores files, creates `*AttachmentFile` / `*DocumentFile` rows
- **Storage:** One row per file; `file_path`, `file_name`, `field_name`, `serial_number`, `project_id`, parent FK
- **Append-only:** No replace; new files added via `updateOrCreate` on parent + new file rows

### 5.2 Per-File Delete Safety

- Each file row is independent; deleting one does not corrupt others
- File models (IES, IIES, IAH, ILP) have `deleting` boot: remove from storage on model delete
- `getFilesForField()` returns collection; removing one file does not break aggregation
- Parent record (`ProjectIESAttachments` etc.) remains; no FK cascade on parent from files table

### 5.3 Last-File Deletion

- **Parent row:** Can remain with zero files
- **DB:** No NOT NULL on child count; parent is valid with no files
- **Recommendation:** Leave parent row; optional cleanup in a later phase

---

## 6. UI Audit

### 6.1 Edit Partials — Project Attachments

| Partial | Show | Download | Delete | Route Used |
|---------|------|----------|--------|------------|
| Edit/IES/attachments.blade.php | ✅ route projects.ies.attachments.view | ✅ route projects.ies.attachments.download | ❌ None | $file->id |
| Edit/IIES/attachments.blade.php | ✅ route projects.iies.attachments.view | ✅ route projects.iies.attachments.download | ❌ None | $file->id |
| Edit/IAH/documents.blade.php | ✅ route projects.iah.documents.view | ✅ route projects.iah.documents.download | ❌ None | $file->id |
| Edit/ILP/attached_docs.blade.php | `Storage::url()` | `Storage::url()` download | ❌ None | Direct URL |
| Edit/attachment.blade.php (generic) | asset('storage/...') | route projects.attachments.download | ❌ None | $attachment->id |

### 6.2 Report Edit Attachments (Reference)

| Partial | Show | Download | Delete |
|---------|------|----------|--------|
| reports/monthly/partials/edit/attachments.blade.php | asset('storage/...') | route reports.attachments.download | ✅ Button calling `confirmRemoveAttachment(id, name)` |

### 6.3 Report Delete UX Pattern

- **Confirmation:** `confirm('Are you sure...')` before delete
- **Request:** `fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf_token(), 'Accept': 'application/json' } })`
- **Route:** `reports.attachments.remove` with `:id` placeholder
- **Response:** JSON `{ success, message }`; on success, remove DOM element
- **CSRF:** Token from `{{ csrf_token() }}`
- **Method spoofing:** DELETE via fetch (no form)

---

## 7. Proposed Delete Strategy

### 7.1 Controller Layer — destroyFile($fileId)

**Guard Order (strict sequence):**

1. Load file by ID (`findOrFail`)
2. Resolve project from file (`$file->project` or via parent)
3. Load user (`Auth::user()`)
4. **Guard 1:** `ProjectPermissionHelper::passesProvinceCheck($project, $user)` → abort 403 if false
5. **Guard 2:** `ProjectStatus::isEditable($project->status)` → abort 403 if false
6. **Guard 3:** `ProjectPermissionHelper::canEdit($project, $user)` → abort 403 if false
7. Delete file from storage (`Storage::disk('public')->delete($file->file_path)`)
8. Delete file record (`$file->delete()`)
9. Return JSON success (parent row left as-is)

**Note:** File model `deleting` event already removes storage; controller can also delete explicitly for clarity. Avoid double-delete.

### 7.2 Route Strategy

**Convention (aligned with existing download/view):**

```
DELETE /projects/ies/attachments/files/{fileId}
DELETE /projects/iies/attachments/files/{fileId}
DELETE /projects/iah/documents/files/{fileId}
DELETE /projects/ilp/attached-documents/files/{fileId}
```

- Place inside shared group: `auth`, `role:executor,applicant,provincial,coordinator,general`
- No `project_id` in URL; only `fileId` — prevents path manipulation
- CSRF via VerifyCsrfToken middleware (all web routes)

**ILP:** No view/download routes today. For delete, add route and controller method. Consider adding view/download routes in same wave for consistency.

### 7.3 UI Strategy

- **Placement:** Delete button next to View/Download in the "Existing Files" block
- **Confirmation:** `confirm('Are you sure you want to remove this file? This cannot be undone.')` (or similar)
- **Request:** `fetch(route, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf_token(), 'Accept': 'application/json' } })`
- **Response:** JSON `{ success, message }`
- **On success:** Remove `.file-item` (or equivalent) from DOM
- **On failure:** Restore button state; show `message` or generic error

Match report attachment pattern: button → confirm → fetch DELETE → DOM removal.

### 7.4 Data Integrity Strategy

- **Last file deleted:** Parent attachment row stays
- **Parent auto-delete:** Not required
- **Constraints:** None affected
- **Orphan storage:** File model `deleting` removes storage; no orphans

---

## 8. Route Structure Proposal

| Route Name | Method | Path | Controller::Method |
|------------|--------|------|--------------------|
| projects.ies.attachments.files.destroy | DELETE | /projects/ies/attachments/files/{fileId} | IESAttachmentsController::destroyFile |
| projects.iies.attachments.files.destroy | DELETE | /projects/iies/attachments/files/{fileId} | IIESAttachmentsController::destroyFile |
| projects.iah.documents.files.destroy | DELETE | /projects/iah/documents/files/{fileId} | IAHDocumentsController::destroyFile |
| projects.ilp.attached-documents.files.destroy | DELETE | /projects/ilp/attached-documents/files/{fileId} | AttachedDocumentsController::destroyFile |

Place in the same middleware group as existing project attachment download/view routes.

---

## 9. Controller Guard Pattern

```
destroyFile($fileId):
  1. $file = FileModel::findOrFail($fileId)
  2. $project = $file->project OR $file->iesAttachment->project (per type)
  3. $user = Auth::user()
  4. if (!ProjectPermissionHelper::passesProvinceCheck($project, $user)) abort(403)
  5. if (!ProjectStatus::isEditable($project->status)) abort(403)
  6. if (!ProjectPermissionHelper::canEdit($project, $user)) abort(403)
  7. Storage::disk('public')->delete($file->file_path)  // optional if model does it
  8. $file->delete()
  9. return response()->json(['success' => true, 'message' => '...'])
```

---

## 10. UI Pattern Proposal

**Blade (per file in loop):**

```blade
<button type="button" class="btn btn-sm btn-outline-danger"
        onclick="confirmRemoveProjectFile('{{ $fileType }}', {{ $file->id }}, '{{ e($file->file_name) }}')">
    <i class="fas fa-trash"></i> Remove
</button>
```

**JS (pattern from report attachments):**

- `confirmRemoveProjectFile(type, fileId, fileName)` → confirm → `removeProjectFile(type, fileId, event)`
- `removeProjectFile` → fetch DELETE to route built from type + fileId; on success remove `.file-item`; on error alert and restore button

**Route helper:** Pass type (ies, iies, iah, ilp) and fileId to build correct route name.

---

## 11. Risk Matrix

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| ID guessing attack | Medium | High | Resolve project from file; enforce canEdit; 403 on failure |
| Province bypass | Medium | High | passesProvinceCheck before any mutation |
| Approved project bypass | Medium | High | ProjectStatus::isEditable before delete |
| Storage orphan | Low | Low | Model deleting event + controller delete |
| Race condition | Low | Low | Single delete; no complex aggregation |
| Direct URL exposure (ILP) | High | Medium | Add route-based view/download for ILP; remove direct Storage::url() |
| CSRF bypass | Low | High | Laravel VerifyCsrfToken on web routes |

---

## 12. Controlled Implementation Plan

**Principle:** One controller first; no bulk refactor; incremental hardening.

### Wave 1 — Harden Download/View (If Required)

- **Goal:** Add `canEdit` + status + province to download/view for one controller (e.g. IES) as proof-of-concept
- **Files:** IESAttachmentsController::downloadFile, viewFile
- **Effort:** Small; validates pattern

### Wave 2 — Add destroyFile to IES (Pilot)

- **Goal:** Implement destroyFile in IESAttachmentsController only
- **Steps:**
  1. Add `destroyFile($fileId)` with full guard sequence
  2. Add route `DELETE /projects/ies/attachments/files/{fileId}`
  3. Test manually

### Wave 3 — Add UI Delete Button (IES Only)

- **Goal:** Add Remove button in Edit/IES/attachments.blade.php
- **Steps:**
  1. Add button with onclick
  2. Add JS confirmRemoveProjectFile / removeProjectFile for IES
  3. Use route `projects.ies.attachments.files.destroy`
  4. Verify CSRF, DOM removal

### Wave 4 — Smoke Tests

- **Goal:** Manual or automated smoke tests
- **Cases:**
  - Executor, own project, editable status → delete succeeds
  - Executor, other’s project → 403
  - Executor, approved project → 403
  - Applicant, in-charge, editable → delete succeeds
  - Provincial, same province → delete succeeds (if in scope)
  - Cross-province → 403

### Wave 5 — Replicate to IIES, IAH, ILP

- **Goal:** Same pattern for each controller
- **Order:** IIES → IAH → ILP (ILP last; may need view/download routes first)
- **No shared trait/abstract initially** — copy pattern per controller

### Wave 6 (Optional) — ILP View/Download Routes

- Add viewFile, downloadFile to AttachedDocumentsController
- Add routes
- Update ILP partial to use routes instead of Storage::url()

---

## 13. Files Referenced

| Category | Path |
|----------|------|
| IES Controller | app/Http/Controllers/Projects/IES/IESAttachmentsController.php |
| IIES Controller | app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php |
| IAH Controller | app/Http/Controllers/Projects/IAH/IAHDocumentsController.php |
| ILP Controller | app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php |
| Generic Controller | app/Http/Controllers/Projects/AttachmentController.php |
| Report Controller | app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php |
| Permission Helper | app/Helpers/ProjectPermissionHelper.php |
| Status Constant | app/Constants/ProjectStatus.php |
| Project Handler | app/Services/ProjectAttachmentHandler.php |
| Attachment Context | app/Services/Attachment/AttachmentContext.php |
| IES File Model | app/Models/OldProjects/IES/ProjectIESAttachmentFile.php |
| IES Parent Model | app/Models/OldProjects/IES/ProjectIESAttachments.php |
| IIES File Model | app/Models/OldProjects/IIES/ProjectIIESAttachmentFile.php |
| IAH File Model | app/Models/OldProjects/IAH/ProjectIAHDocumentFile.php |
| ILP File Model | app/Models/OldProjects/ILP/ProjectILPDocumentFile.php |
| Report Edit Partial | resources/views/reports/monthly/partials/edit/attachments.blade.php |
| Routes | routes/web.php |

---

**End of Audit** — No code changes were made.
