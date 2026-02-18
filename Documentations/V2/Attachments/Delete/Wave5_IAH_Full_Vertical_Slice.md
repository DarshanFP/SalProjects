# WAVE 5A — IAH ATTACHMENT FULL AUDIT (NO CODE CHANGES)

**Mode:** AUDIT ONLY — No code modifications, no route changes, no Blade edits, no refactors, no logging, no abstraction.

**Date:** 2026-02-17

**Target scope:**
- Controller: `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php`
- Models: `app/Models/OldProjects/IAH/ProjectIAHDocumentFile.php`, `ProjectIAHDocuments.php`
- Blade: `resources/views/projects/partials/Edit/IAH/documents.blade.php`
- Routes: `routes/web.php`
- Reference (gold standard): IES, IIES

---

## 1. EXECUTIVE SUMMARY

IAH documents are structurally similar to IES/IIES (parent document record + child file records, same handler pattern for store/update). Critical gaps:

- **Read surface (download/view):** No province check, no `canView`; any authenticated user in the same middleware group can access any IAH file by `fileId`. **High risk.**
- **Mutation surface:** No `destroyFile($fileId)`; only full `destroy($projectId)` exists. `store`/`update`/`destroy` do not use `ProjectPermissionHelper` or `ProjectStatus`. **High risk for destroy; medium for store/update** (caller may enforce, but not guaranteed).
- **UI:** No per-file Delete button, no DOM id on file row, no JS for delete; Blade otherwise aligned with IES/IIES (View/Download via `route()`).
- **Routes:** IAH has view + download only; no per-file delete route. Same middleware group as IES/IIES (`auth`, `role:executor,applicant,provincial,coordinator,general`).
- **Models:** Child has `deleting` for storage cleanup; parent uses legacy `deleteAttachments()` on delete (legacy columns only). Controller uses `Storage::deleteDirectory` on full destroy; child file rows may be orphaned if no DB CASCADE.

**Confirmation:** No code was modified in this wave. This document is analysis and implementation plan only.

---

## 2. STRUCTURAL COMPARISON: IES vs IIES vs IAH

| Aspect | IES | IIES | IAH |
|--------|-----|------|-----|
| **Controller** | IESAttachmentsController | IIESAttachmentsController | IAHDocumentsController |
| **Parent model** | ProjectIESAttachments | ProjectIIESAttachments | ProjectIAHDocuments**s** |
| **Child model** | ProjectIESAttachmentFile | ProjectIIESAttachmentFile | ProjectIAHDocumentFile |
| **Parent PK** | IES_attachment_id | IIES_attachment_id | IAH_doc_id |
| **Child FK** | IES_attachment_id | IIES_attachment_id | IAH_doc_id |
| **Storage path** | project_attachments/IES/{projectId} | project_attachments/IIES/{projectId} | project_attachments/IAH/{projectId} |
| **downloadFile** | ✅ Province + canView | ✅ Province + canView | ❌ None |
| **viewFile** | ✅ Province + canView | ✅ Province + canView | ❌ None |
| **destroyFile** | ✅ Province + status + canEdit | ✅ Province + status + canEdit | ❌ Absent |
| **destroy(projectId)** | ✅ Storage::deleteDirectory + delete | ✅ Storage::deleteDirectory + delete | ✅ Storage::deleteDirectory + delete |
| **store/update** | No ProjectPermissionHelper | No ProjectPermissionHelper | No ProjectPermissionHelper |
| **Blade DOM id** | `ies-file-{{ $file->id }}` | `iies-file-{{ $file->id }}` | ❌ None |
| **Blade Delete** | ✅ Button + JS fetch | ✅ Button + JS fetch | ❌ None |
| **Per-file delete route** | ✅ DELETE .../files/{fileId} | ✅ DELETE .../files/{fileId} | ❌ None |

---

## 3. PHASE 1 — CONTROLLER ANALYSIS

### 3.1 downloadFile($fileId)

| Question | IAH (current) | IES/IIES (reference) |
|----------|----------------|------------------------|
| How file loaded? | `ProjectIAHDocumentFile::findOrFail($fileId)` | Same pattern |
| Is project resolved? | **No** | Yes: `$project = $file->project ?? $file->iesAttachment?->project` (or iiesAttachment) |
| ProjectPermissionHelper used? | **No** | Yes: passesProvinceCheck, canView |
| Province check applied? | **No** | Yes — abort(403) |
| canView applied? | **No** | Yes — abort(403) |
| ProjectStatus used? | **No** | Not in read (only in destroyFile) |
| Response format? | 200: stream download; 404/500: JSON | Same |

**IAH code path:** Load file by ID → check disk exists → `Storage::disk('public')->download(...)`. No project or user checks.

---

### 3.2 viewFile($fileId)

| Question | IAH (current) | IES/IIES (reference) |
|----------|----------------|------------------------|
| Same as downloadFile for: project resolved? | **No** | Yes |
| Province / canView? | **No** | Yes |
| Response format? | 200: inline stream; 404/500: JSON | Same |

**IAH code path:** Same as downloadFile but returns inline response with Content-Type + Content-Disposition. No permission checks.

---

### 3.3 destroy($projectId)

| Question | IAH (current) | IES | IIES |
|----------|----------------|-----|------|
| Exists? | Yes | Yes | Yes |
| How parent delete handled? | `$documents->delete()` after directory delete | `$attachments->delete()` after directory delete | Same |
| Storage::deleteDirectory? | Yes: `Storage::deleteDirectory("project_attachments/IAH/{$projectId}")` | Yes: `\Storage::deleteDirectory("project_attachments/IES/...")` (default disk) | Yes: `Storage::deleteDirectory("project_attachments/IIES/...")` |
| ProjectPermissionHelper? | **No** | **No** | **No** |
| Relies on caller? | Yes (no guard in controller) | Yes | Yes |

So IAH `destroy` is aligned with IES/IIES: no permission helper in controller; responsibility is on the caller. Risk is consistent across the three.

---

### 3.4 destroyFile($fileId)

| Controller | destroyFile present? | Guard chain (if present) |
|------------|------------------------|---------------------------|
| IES | Yes | project resolve → passesProvinceCheck → ProjectStatus::isEditable → canEdit → $file->delete() → JSON success/error |
| IIES | Yes | Same |
| IAH | **No** | N/A |

**Conclusion:** IAH has no per-file delete; no guard chain to document.

---

### 3.5 store / update (mutation methods)

| Question | IAH | IES | IIES |
|----------|-----|-----|------|
| ProjectPermissionHelper? | **No** | **No** | **No** |
| Project existence check? | Yes (store: `Project::where('project_id', $projectId)->exists()`) | No explicit in controller | Yes (ModelNotFoundException) |
| ProjectAttachmentHandler? | Yes, AttachmentContext::forIAH() | Yes, forIES() | Yes, forIIES() |

No controller-level permission or province checks in any of the three for store/update; parity.

---

### 3.6 Controller summary table (Wave 4A style)

| Method | File load | Project resolve | Province | canView | canEdit | ProjectStatus | Response |
|--------|-----------|-----------------|----------|---------|---------|---------------|----------|
| downloadFile | findOrFail($fileId) | No | No | No | N/A | No | Stream / JSON |
| viewFile | findOrFail($fileId) | No | No | No | N/A | No | Inline / JSON |
| destroy(projectId) | N/A | firstOrFail doc | No | No | No | No | JSON |
| destroyFile | — | — | — | — | — | — | **Absent** |
| store | — | exists check only | No | No | No | No | JSON |
| update | — | No | No | No | No | No | JSON |

---

## 4. PHASE 2 — MODEL ANALYSIS

### 4.1 ProjectIAHDocumentFile (child)

| Item | Present? | Notes |
|------|----------|--------|
| project() | Yes | `belongsTo(Project::class, 'project_id', 'project_id')` |
| iahDocument() | Yes | `belongsTo(ProjectIAHDocuments::class, 'IAH_doc_id', 'IAH_doc_id')` |
| file_path column | Yes | In fillable |
| deleting event | Yes | boot(): deleting → Storage::disk('public')->delete($file->file_path) if exists |
| Public disk | Yes | Same as IES/IIES |
| Directory path pattern | project_attachments/IAH/{projectId} | From handler / controller destroy |

**Comparison:** Same structure as IES/IIES file models (project, parent relation, file_path, deleting cleanup, public disk). No differences.

---

### 4.2 ProjectIAHDocuments (parent)

| Item | Present? | Notes |
|------|----------|--------|
| project() | Yes | belongsTo Project |
| files() | Yes | hasMany ProjectIAHDocumentFile, IAH_doc_id |
| getFilesForField($fieldName) | Yes | files()->where('field_name', $fieldName)->orderBy('serial_number')->get() |
| deleting behavior | Legacy only | static::deleting → deleteAttachments() only |
| deleteAttachments() | Yes | Loops legacy columns (aadhar_copy, request_letter, medical_reports, other_docs), Storage::disk('public')->delete($this->$field) |
| Cascade via files()? | **No** | Does not call $model->files()->each(fn ($f) => $f->delete()) |
| Storage cleanup on parent delete | Legacy paths only | deleteAttachments() only; controller does deleteDirectory for full destroy |

**Comparison with IES/IIES:**

- **IES parent:** `deleting` → `$model->files()->each(function ($file) { $file->delete(); })` → each child delete triggers child's `deleting` → storage cleanup. No legacy deleteAttachments. Child rows removed explicitly.
- **IIES parent:** `deleting` → `deleteAttachments()` only (legacy columns). Does **not** delete child ProjectIIESAttachmentFile rows. Same pattern as IAH.
- **IAH parent:** Same as IIES: deleteAttachments() only; no cascade delete of child file models. Controller’s deleteDirectory removes files from disk; child DB rows may remain unless FK CASCADE exists.

**Highlight:** IES is the only one that explicitly deletes child file models on parent delete. IAH and IIES rely on controller deleteDirectory + parent row delete; child rows may be orphaned without DB CASCADE.

---

## 5. PHASE 3 — ROUTE ANALYSIS

**Search:** `routes/web.php` and `php artisan route:list | grep iah`

### 5.1 IAH routes (current)

| Purpose | Method | Path | Name |
|---------|--------|------|------|
| View file | GET | `/projects/iah/documents/view/{fileId}` | projects.iah.documents.view |
| Download file | GET | `/projects/iah/documents/download/{fileId}` | projects.iah.documents.download |

**Middleware group:** `auth`, `role:executor,applicant,provincial,coordinator,general` (same as IES/IIES in the same group).

**Prefix:** None beyond group; paths are absolute in group.

### 5.2 Consistency with IES/IIES

| Route type | IES | IIES | IAH |
|------------|-----|------|-----|
| Download path | /projects/ies/attachments/download/{fileId} | /projects/iies/attachments/download/{fileId} | /projects/iah/documents/download/{fileId} |
| View path | /projects/ies/attachments/view/{fileId} | /projects/iies/attachments/view/{fileId} | /projects/iah/documents/view/{fileId} |
| Per-file delete | DELETE /projects/ies/attachments/files/{fileId} | DELETE /projects/iies/attachments/files/{fileId} | **Missing** |

Naming: IES uses `attachments`, IAH uses `documents` (semantic only). Per-file delete route is absent for IAH.

### 5.3 artisan route:list (iah)

```
GET|HEAD   projects/iah/documents/download/{fileId}   projects.iah.documents.download
GET|HEAD   projects/iah/documents/view/{fileId}       projects.iah.documents.view
```

No DELETE route for IAH.

---

## 6. PHASE 4 — BLADE ANALYSIS

**File:** `resources/views/projects/partials/Edit/IAH/documents.blade.php`

| Item | IAH | IES | IIES |
|------|-----|-----|------|
| Files loop | `$IAHDocuments->getFilesForField($field)` → foreach $existingFiles | Same pattern with getFilesForField | Same |
| View button | `<a href="{{ route('projects.iah.documents.view', $file->id) }}" target="_blank">` | route('projects.ies.attachments.view', $file->id) | route('projects.iies.attachments.view', $file->id) |
| Download button | `route('projects.iah.documents.download', $file->id)` | route('projects.ies.attachments.download', $file->id) | route('projects.iies.attachments.download', $file->id) |
| route() vs url() | route() | route() | route() |
| File row DOM id | **None** | `id="ies-file-{{ $file->id }}"` | `id="iies-file-{{ $file->id }}"` |
| Delete button | **No** | Yes (confirmRemoveIESFile) | Yes (confirmRemoveIIESFile) |
| JS block | Add-another-file only | Add-another-file + confirmRemove + removeIESFile (fetch DELETE) | Add-another-file + confirmRemove + removeIIESFile (fetch DELETE) |
| Structural deviation | No DOM id, no Delete, no delete JS | Has id + Delete + JS | Has id + Delete + JS |

Validation: IAH Blade uses `validateIESFile` (same as IES/IIES); naming is shared, behavior is for validation only.

---

## 7. PHASE 5 — SECURITY GAP IDENTIFICATION

### 7.1 Read surface gaps

- **Province isolation missing (High):** downloadFile and viewFile do not call `ProjectPermissionHelper::passesProvinceCheck`. Any user in the same role group can access any IAH file by ID.
- **canView missing (High):** No check that the user is allowed to view the project; approved/restricted projects are still readable via direct file URL.
- **Project not resolved (Medium):** Controller never loads project; no way to run province/view logic without adding it.

### 7.2 Mutation surface gaps

- **destroy(projectId):** No ProjectPermissionHelper or ProjectStatus in controller; relies on caller. Same as IES/IIES — **Medium** (design choice).
- **destroyFile:** Absent. When added in Wave 5B, must enforce province + isEditable + canEdit to match IES/IIES — **High** to implement correctly.
- **store/update:** No permission helper in controller — **Low** (parity with IES/IIES; caller expected to gate).

### 7.3 Missing checks summary

| Check | downloadFile | viewFile | destroy | destroyFile |
|-------|--------------|----------|---------|-------------|
| Province isolation | Missing | Missing | Missing | N/A (absent) |
| canView | Missing | Missing | N/A | N/A |
| canEdit | N/A | N/A | Missing | N/A (absent) |
| ProjectStatus (isEditable) | N/A | N/A | Missing | N/A (absent) |

### 7.4 Storage risks

- **Low:** Child model deletes file from public disk on model delete. Controller deleteDirectory on full destroy is consistent with IES/IIES.
- **Note:** Parent delete does not cascade to child file models in IAH (and IIES); child rows may remain if no DB CASCADE. Consider migration or explicit cascade in parent deleting() for consistency with IES.

### 7.5 Route exposure risks

- **High:** View and download routes are exposed to all roles in the group; with no province/canView, any authenticated user in that group can hit URLs with arbitrary fileIds and read files.

### 7.6 UI inconsistency

- **Medium:** No per-file Delete in IAH Blade; users cannot remove a single file from UI even after destroyFile is implemented without Blade/JS changes.

### 7.7 Severity summary

| Category | Severity | Items |
|----------|----------|--------|
| Read: province + canView | High | downloadFile, viewFile |
| Mutation: destroyFile absent | High | No per-file delete |
| Mutation: destroy guards | Medium | Same as IES/IIES (caller) |
| UI: Delete button + JS | Medium | Missing in IAH Blade |
| Storage / cascade | Low | Optional parent cascade or FK |

---

## 8. PHASE 6 — IMPLEMENTATION PLAN (NO CODE)

### SECTION A — Read hardening plan

- **Methods to modify:** `downloadFile($fileId)`, `viewFile($fileId)`.
- **Guard chain to apply (each method):**
  1. Load file: `ProjectIAHDocumentFile::findOrFail($fileId)`.
  2. Resolve project: `$project = $file->project ?? $file->iahDocument?->project`; if !$project → 404 JSON.
  3. Province: `ProjectPermissionHelper::passesProvinceCheck($project, Auth::user())` → else `abort(403)`.
  4. canView: `ProjectPermissionHelper::canView($project, Auth::user())` → else `abort(403)`.
  5. Then existing disk check and stream response.
- **Imports to add:** `Auth`, `ProjectPermissionHelper` (and optionally `ProjectStatus` if ever needed for read).

### SECTION B — Per-file delete plan

- **Method to add:** `destroyFile($fileId)`.
- **Guard chain:** Same as IES/IIES: resolve project from file → passesProvinceCheck → ProjectStatus::isEditable → ProjectPermissionHelper::canEdit → then `$file->delete()`.
- **Response:** JSON `['success' => true, 'message' => '...']` on success; 403 with `['success' => false, 'message' => 'Forbidden.']`; 404 for not found; 500 with message on exception.
- **Route to add:**  
  - Path: `DELETE /projects/iah/documents/files/{fileId}`  
  - Name: `projects.iah.documents.files.destroy`  
  - Same middleware group as existing IAH routes.

### SECTION C — UI integration plan

- **Blade:** In `documents.blade.php`, for each file row add `id="iah-file-{{ $file->id }}"` and a Delete button calling `confirmRemoveIAHFile({{ $file->id }}, {{ json_encode($file->file_name) }})`.
- **JS:** Add `confirmRemoveIAHFile(fileId, fileName)` (confirm dialog) and `removeIAHFile(fileId)` (fetch DELETE to `route('projects.iah.documents.files.destroy', fileId)` with CSRF and Accept: application/json); on success remove element `#iah-file-` + fileId.
- **route():** Use `route('projects.iah.documents.files.destroy', ':id')` in template, replace ':id' with fileId in JS.

### SECTION D — Documentation plan

- **File to create (in Wave 5B or after):**  
  `Documentations/V2/Attachments/Delete/Wave5_IAH_Full_Vertical_Slice.md`  
  This audit document can serve as that artifact; Wave 5B can add a short “Implementation completed” section and link to this audit.

---

## 9. RISK ASSESSMENT

| Risk | Level | Mitigation (Wave 5B) |
|------|--------|---------------------|
| Cross-province / cross-role read of IAH files | High | Add province + canView in downloadFile and viewFile |
| Unauthorized per-file delete | High | Implement destroyFile with province + isEditable + canEdit |
| destroy(projectId) used without caller guard | Medium | Leave as-is (parity); ensure all callers enforce permission |
| Orphaned child file rows on parent delete | Low | Optional: parent deleting() cascade delete files(), or FK ON DELETE CASCADE |
| UI cannot delete single file | Medium | Add Delete button + DOM id + JS in IAH Blade |

---

## 10. CONFIRMATION: NO CODE MODIFIED

- No controller changes.
- No model changes.
- No Blade changes.
- No route changes.
- No new methods, no refactors, no logging added, no new abstractions.
- IES, IIES, ILP, and reports were not modified.

**This document is the only deliverable for Wave 5A.** It is intended to support Wave 5B implementation (read hardening + per-file delete + UI).

---

# WAVE 5B — IAH FULL VERTICAL SLICE (IMPLEMENTATION COMPLETE)

**Mode:** Controlled Implementation — IAH only. No abstractions. IES, IIES, ILP, generic, and reports untouched.

**Date:** 2026-02-17

---

## 1. Executive Summary

Wave 5B brings the IAH document surface to the same security and feature level as IES and IIES:

- **Read hardening:** `downloadFile` and `viewFile` now resolve the project from the file record and enforce `ProjectPermissionHelper::passesProvinceCheck` and `ProjectPermissionHelper::canView`. Unauthorized access returns 403.
- **Per-file delete:** New `destroyFile($fileId)` method enforces province, `ProjectStatus::isEditable`, and `ProjectPermissionHelper::canEdit`, then deletes the file (model `deleting` event removes storage). JSON response for success/forbidden/error.
- **Route:** `DELETE /projects/iah/documents/files/{fileId}` named `projects.iah.documents.files.destroy`, in the same middleware group as existing IAH view/download.
- **UI:** File rows have `id="iah-file-{{ $file->id }}"`, a Delete button calling `confirmRemoveIAHFile`/`removeIAHFile`, and inline JS that sends DELETE with CSRF and removes the row on success. No page reload.

Only IAH controller, IAH Blade, and routes were modified. No other project types (IES, IIES, ILP) or reports were touched.

---

## 2. Read Hardening Changes

**Methods modified:** `downloadFile($fileId)`, `viewFile($fileId)`.

**Changes applied (each method):**

1. After `$file = ProjectIAHDocumentFile::findOrFail($fileId)`:
   - Resolve project: `$project = $file->project ?? $file->iahDocument?->project`.
   - If `! $project`: return 404 JSON `['error' => 'File record not found']`.
2. `$user = Auth::user()`.
3. `if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) abort(403);`
4. `if (! ProjectPermissionHelper::canView($project, $user)) abort(403);`
5. Existing logic unchanged: disk existence check, then stream (download or inline). Response format unchanged.

**Imports added:** `Illuminate\Support\Facades\Auth`, `App\Helpers\ProjectPermissionHelper`. No `ProjectStatus` used for read (as specified).

---

## 3. Delete Backend Implementation

**Method added:** `public function destroyFile($fileId)`.

**Guard chain:**

1. `$file = ProjectIAHDocumentFile::findOrFail($fileId)`.
2. `$project = $file->project ?? $file->iahDocument?->project`; if missing → `return response()->json(['error' => 'File record not found'], 404)`.
3. `$user = Auth::user()`.
4. Province: `! ProjectPermissionHelper::passesProvinceCheck($project, $user)` → 403 JSON `['success' => false, 'message' => 'Forbidden.']`.
5. Editable: `! ProjectStatus::isEditable($project->status)` → 403 JSON same.
6. canEdit: `! ProjectPermissionHelper::canEdit($project, $user)` → 403 JSON same.
7. `$file->delete();` (model `deleting` event removes file from public disk).
8. Success: `return response()->json(['success' => true, 'message' => 'Document deleted successfully.'])`.

ModelNotFoundException → 404 JSON. Other exceptions → 500 JSON with message. Logging added for block reasons and success.

**Import added:** `App\Constants\ProjectStatus`.

**Unchanged:** `destroy($projectId)`, `store`, `update` — not modified. Parent row is not deleted by this method.

---

## 4. Route Added

| Property | Value |
|----------|--------|
| **Method** | DELETE |
| **Path** | `/projects/iah/documents/files/{fileId}` |
| **Name** | `projects.iah.documents.files.destroy` |
| **Controller** | `IAHDocumentsController@destroyFile` |
| **Middleware** | Same group as existing IAH view/download: `auth`, `role:executor,applicant,provincial,coordinator,general` |

Placed immediately after the existing IAH download route; no other route groups or prefixes changed.

---

## 5. UI Integration Details

**File:** `resources/views/projects/partials/Edit/IAH/documents.blade.php`.

- **DOM id:** Each file row div: `@if(isset($file->id)) id="iah-file-{{ $file->id }}" @endif`. Only when file has `id` (DB-backed record).
- **Delete button:** Inside the existing View/Download block, when `isset($file->id)`:  
  `<button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmRemoveIAHFile({{ $file->id }}, {{ json_encode($file->file_name) }})">` with icon and "Delete" label. Layout unchanged.
- **Legacy fallback:** When `$file->id` is not set, View/Download use `Storage::url($file->file_path)` (no Delete button), matching IES/IIES pattern.
- **Inline JS (IAH only):**  
  - `confirmRemoveIAHFile(fileId, fileName)` — confirm dialog; on confirm calls `removeIAHFile(fileId)`.  
  - `removeIAHFile(fileId)` — builds URL from `route('projects.iah.documents.files.destroy', ':id')` with `:id` replaced; `fetch(deleteUrl, { method: 'DELETE', headers: X-CSRF-TOKEN, Accept: application/json })`; on success removes `#iah-file-` + fileId; on error shows alert. No global JS extraction; script block lives in this Blade only.

---

## 6. Guard Chains (Read vs Mutation)

| Operation | Chain |
|-----------|--------|
| **Read (download/view)** | File load → resolve project → 404 if no project → passesProvinceCheck → canView → stream. No ProjectStatus. |
| **Mutation (destroyFile)** | File load → resolve project → 404 if no project → passesProvinceCheck → ProjectStatus::isEditable → canEdit → delete → JSON success. 403 JSON on any guard failure. |

---

## 7. Validation Results

Checklist to be verified manually or in QA:

- Approved project → delete returns 403.
- Cross-province → 403.
- Executor not owner/in_charge → 403.
- Owner on editable project → delete works, row removed, no reload.
- DOM row removed after successful delete.
- No page reload on delete.
- Download still works (with province/canView).
- View still works (with province/canView).
- `php artisan route:list | grep iah` shows the new DELETE route.
- No console errors during delete flow.

*(Results to be filled after QA.)*

---

## 8. Security Impact

- **Read:** Province isolation and canView prevent cross-province and unauthorized viewing/downloading of IAH files by fileId. Aligns with IES/IIES.
- **Mutation:** Per-file delete is gated by province, editable status, and canEdit; only authorized users can remove individual files. Reduces risk of abuse via direct DELETE URL.
- **UI:** Delete is available in-context with confirmation; no silent or bulk delete from this UI. Same pattern as IES/IIES.

---

## 9. Confirmation: No Other Controllers Modified

- **IES:** Not modified.
- **IIES:** Not modified.
- **ILP:** Not modified.
- **Generic attachment / reports:** Not modified.
- **Only modified:** `IAHDocumentsController.php`, `documents.blade.php` (IAH), `routes/web.php` (one new route). No model changes. No refactor of parent cascade.

---

## 10. Note on Parent Model Deletion (Legacy vs IES Cascade)

- **IAH (and IIES):** Parent `ProjectIAHDocuments` uses `deleteAttachments()` on `deleting`, which only deletes files referenced by legacy columns. It does **not** cascade-delete child `ProjectIAHDocumentFile` rows. Full destroy in the controller uses `Storage::deleteDirectory` plus `$documents->delete()`, so disk is cleared; child rows may remain unless DB has FK CASCADE.
- **IES:** Parent explicitly deletes child file models in `deleting` via `$model->files()->each(fn ($f) => $f->delete())`, so child rows and storage are cleaned in sync.
- Wave 5B did **not** change IAH parent model behavior. Optional future improvement: cascade delete of `files()` on parent delete (or FK ON DELETE CASCADE) for consistency and to avoid orphaned child rows.
