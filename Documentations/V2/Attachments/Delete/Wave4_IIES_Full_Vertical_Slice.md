# WAVE 4A + 4B — IIES ATTACHMENT FULL AUDIT & IMPLEMENTATION

**Date:** 2026-02-17  
**Wave 4A:** AUDIT ONLY (no code changes).  
**Wave 4B:** IMPLEMENTATION COMPLETED — Read hardening, per-file delete, route, UI (DOM id, Delete button, JS).  
**Reference:** IES controller (hardened + per-file delete + UI complete).

---

## 1. Executive Summary

| Aspect | Finding |
|--------|---------|
| **Objective** | Verify IIES structural parity with IES before implementing IIES hardening + per-file delete + UI. |
| **Controller** | IIES has `downloadFile`, `viewFile`, `destroy($projectId)`; **no** `destroyFile($fileId)`. Read methods have **no** ProjectPermissionHelper, **no** province isolation, **no** canView. |
| **Models** | Structural parity with IES: `project()`, `iiesAttachment()` / `files()`, `file_path`, public disk, `project_attachments/IIES/{projectId}`. Parent delete uses legacy `deleteAttachments()` (column paths) and does not cascade delete via `files()` first — **difference** from IES. |
| **Routes** | IIES: only `download` and `view` in shared middleware group; **no** per-file delete route. Path prefix: `/projects/iies/` (name: `projects.iies.attachments.*`). IES has `/projects/ies/` + `projects.ies.attachments.files.destroy`. |
| **Blade** | IIES: View/Download via `route()`; **no** Delete button; **no** `id="iies-file-{id}"`; **no** confirmRemove/remove JS. IES has Delete button + DOM id + JS. |
| **Security** | **Critical:** IIES download/view are ID-only; any authenticated user in role group can access any file by ID. No province or canView. |
| **Next step** | Wave 4B: implement read hardening, `destroyFile`, route, and Blade/JS for IIES mirroring IES. |

---

## 2. Structural Comparison: IES vs IIES

### 2.1 Controller

| Item | IES (Reference) | IIES (Audited) |
|------|------------------|----------------|
| **downloadFile($fileId)** | Resolves project via `$file->project ?? $file->iesAttachment?->project`. Uses `ProjectPermissionHelper::passesProvinceCheck`, `ProjectPermissionHelper::canView`. `abort(403)` on failure. | Loads file by ID only. **No** project resolution. **No** ProjectPermissionHelper. **No** province or canView. |
| **viewFile($fileId)** | Same guard chain as downloadFile. | Same as IIES downloadFile — ID-only, no guards. |
| **destroy($projectId)** | Full record delete: `firstOrFail` by project_id, `Storage::deleteDirectory`, `$attachments->delete()`. **No** ProjectPermissionHelper in controller (relies on caller). | Same pattern: `firstOrFail`, `Storage::deleteDirectory("project_attachments/IIES/{$projectId}")`, `$attachments->delete()`. **No** permission helper. |
| **Per-file delete** | **Yes:** `destroyFile($fileId)`. Resolves project; applies province, `ProjectStatus::isEditable`, `ProjectPermissionHelper::canEdit`; then `$file->delete()`. | **No** `destroyFile` method. |
| **Project resolution** | In download/view/destroyFile: `$file->project ?? $file->iesAttachment?->project`. | Only in store: `Project::where('project_id', $projectId)->exists()`. Not used in download/view. |
| **ProjectPermissionHelper** | Used in downloadFile, viewFile, destroyFile. | **Not used** anywhere. |
| **ProjectStatus** | Used in destroyFile (`isEditable`). | **Not used** anywhere. |
| **Province isolation** | Applied in downloadFile, viewFile, destroyFile. | **Not applied** in any method. |

### 2.2 Models

| Item | IES | IIES |
|------|-----|------|
| **ProjectIIESAttachmentFile / ProjectIESAttachmentFile** | | |
| `project()` | Yes | Yes |
| `iesAttachment()` / `iiesAttachment()` | Yes | Yes |
| `file_path` column | Yes | Yes |
| `deleting` event (storage cleanup) | Yes | Yes |
| Public disk | Yes | Yes |
| **ProjectIIESAttachments / ProjectIESAttachments** | | |
| `project()` | Yes | Yes |
| `files()` | Yes | Yes |
| Parent `deleting` | Cascades: `$model->files()->each(fn($f) => $f->delete())` (so each file’s deleting runs). | Calls `$model->deleteAttachments()` which deletes by **legacy column paths** (iies_*), **not** via `files()` relationship. So file rows in `project_IIES_attachment_files` may remain; storage cleanup is by directory in controller. |

### 2.3 Storage

| Item | IES | IIES |
|------|-----|------|
| Directory | `project_attachments/IES/{projectId}` | `project_attachments/IIES/{projectId}` |
| Disk | public | public |
| Controller destroy | `Storage::deleteDirectory("project_attachments/IES/{$projectId}")` | `Storage::deleteDirectory("project_attachments/IIES/{$projectId}")` |

### 2.4 Routes (web.php)

| Route | IES | IIES |
|-------|-----|------|
| Download | `GET /projects/ies/attachments/download/{fileId}` → `projects.ies.attachments.download` | `GET /projects/iies/attachments/download/{fileId}` → `projects.iies.attachments.download` |
| View | `GET /projects/ies/attachments/view/{fileId}` → `projects.ies.attachments.view` | `GET /projects/iies/attachments/view/{fileId}` → `projects.iies.attachments.view` |
| Per-file delete | `DELETE /projects/ies/attachments/files/{fileId}` → `projects.ies.attachments.files.destroy` | **None** |
| Middleware | `auth`, `role:executor,applicant,provincial,coordinator,general` | Same group (shared block) |
| Prefix | `/projects/ies/` | `/projects/iies/` |

Route names mirror pattern: `projects.{ies|iies}.attachments.{download|view}`; IIES has no `files.destroy`.

### 2.5 Blade (Edit partial)

| Item | IES | IIES |
|------|-----|------|
| Files loop | `$IESAttachments->getFilesForField($field)` | `$IIESAttachments->getFilesForField($field)` |
| View button | `route('projects.ies.attachments.view', $file->id)` | `route('projects.iies.attachments.view', $file->id)` |
| Download button | `route('projects.ies.attachments.download', $file->id)` | `route('projects.iies.attachments.download', $file->id)` |
| DOM wrapper | `id="ies-file-{{ $file->id }}"` on file-item div | **No** `id="iies-file-{{ $file->id }}"` |
| Delete button | Yes; `confirmRemoveIESFile({{ $file->id }}, …)` | **No** Delete button |
| JS | `confirmRemoveIESFile`, `removeIESFile`, `route('projects.ies.attachments.files.destroy', ':id')` | **No** delete JS |
| route() vs url() | route() | route() |

---

## 3. Identified Differences

1. **Read surface (download/view):** IIES does not resolve project or apply province/canView; IES does. **Risk:** Cross-tenant read by file ID.
2. **Per-file delete:** IIES has no `destroyFile`, no route, no UI. IES has full vertical slice.
3. **Parent model delete:** IIES `ProjectIIESAttachments::deleting` uses `deleteAttachments()` (legacy columns); IES uses `files()->each->delete()` so file records and storage are cleaned via child model events. IIES controller also deletes directory in `destroy($projectId)`, so storage is cleared but file rows may be orphaned if delete path bypasses controller.
4. **Blade:** IIES missing file-row id, Delete button, and delete JS.
5. **store:** IIES has explicit `Project::where('project_id', $projectId)->exists()`; IES does not (relies on handler/caller). Neither uses ProjectPermissionHelper in store/update.

---

## 4. Security Gaps

### 4.1 Read surface

- **downloadFile / viewFile:** No project resolution; no `ProjectPermissionHelper::passesProvinceCheck` or `canView`. Any user in the shared role group can access any IIES file by guessing or enumerating `fileId`.

### 4.2 Mutation surface

- **destroy($projectId):** No ProjectPermissionHelper or ProjectStatus in controller. Relies on caller (e.g. ProjectController); if ever exposed by URL, no controller-level guard.
- **Per-file delete:** Absent; no risk from this path until implemented (then must add province + isEditable + canEdit).

### 4.3 Missing guards

- Province isolation: not applied in IIES controller.
- canView / canEdit: not applied in IIES controller.
- ProjectStatus::isEditable: not used in IIES.

### 4.4 Storage

- Directory deletion in `destroy($projectId)` is correct. Child file model has `deleting` and removes single file from storage; parent does not cascade delete file rows via `files()` (see Model difference above). For per-file delete, `$file->delete()` will run model event and clean storage.

### 4.5 UI alignment

- No Delete button or DOM id for IIES file rows; no JS for per-file delete. UI cannot support delete until Blade/JS and backend are added.

---

## 5. Implementation Plan (No Code)

### SECTION A — Read Hardening Plan

- **Methods to modify:** `downloadFile($fileId)`, `viewFile($fileId)`.
- **Guard chain to apply (mirror IES):**
  1. Load file: `ProjectIIESAttachmentFile::findOrFail($fileId)`.
  2. Resolve project: `$project = $file->project ?? $file->iiesAttachment?->project`; if missing, 404.
  3. `ProjectPermissionHelper::passesProvinceCheck($project, Auth::user())` → abort(403) if false.
  4. `ProjectPermissionHelper::canView($project, Auth::user())` → abort(403) if false.
  5. Then existing storage exists check and stream/download response.
- **Imports to add:** `ProjectPermissionHelper`, `Auth`, and optionally `ProjectStatus` if needed later.

### SECTION B — Per-File Delete Plan

- **New method:** `destroyFile($fileId)` in `IIESAttachmentsController`.
- **Guard chain:** Same project resolution as read; then:
  1. `ProjectPermissionHelper::passesProvinceCheck` → 403 if false.
  2. `ProjectStatus::isEditable($project->status)` → 403 if false.
  3. `ProjectPermissionHelper::canEdit($project, $user)` → 403 if false.
  4. `$file->delete()` (model `deleting` event removes storage).
- **Response:** JSON `{ success: true, message: '...' }` or 403/404/500 as in IES.
- **Route to add:** `DELETE /projects/iies/attachments/files/{fileId}` → `destroyFile` → name `projects.iies.attachments.files.destroy`, in same middleware group as download/view.

### SECTION C — UI Integration Plan

- **Blade (Edit/IIES/attachments.blade.php):**
  - Add `id="iies-file-{{ $file->id }}"` to the file-item div (when `$file->id` exists).
  - Add Delete button next to View/Download: same pattern as IES (e.g. `confirmRemoveIIESFile({{ $file->id }}, {{ json_encode($file->file_name) }})`).
  - Use `route('projects.iies.attachments.files.destroy', ':id')` in JS (replace `:id` with fileId).
- **JS:** Add `confirmRemoveIIESFile(fileId, fileName)` and `removeIIESFile(fileId)` (fetch DELETE, then remove DOM element `#iies-file-{fileId}` on success).
- **DOM id strategy:** `iies-file-{id}` for each file row so JS can remove row after successful delete.
- **Route helper:** Use `route('projects.iies.attachments.files.destroy', ':id')`; no url().

### SECTION D — Documentation Plan

- **File to create/update:** `Documentations/V2/Attachments/Delete/Wave4_IIES_Full_Vertical_Slice.md` (this document).
- After Wave 4B: add a short “Wave 4B implemented” section or separate doc linking to this audit and listing changes (controller, route, blade, JS).

---

## 6. Risk Assessment

| Risk | Level | Note |
|------|--------|------|
| Cross-tenant read (download/view by file ID) | High | Until read hardening is applied, any authenticated user in the role group can access any IIES file by ID. |
| Unauthorized full attachment delete | Medium | `destroy($projectId)` is not routed in the shared group; likely only via ProjectController or internal flow. If a route is added without guards, risk increases. |
| Orphaned file rows on parent delete | Low | Controller deletes directory; parent model does not cascade delete file rows. Acceptable if project delete always goes through controller; consider aligning parent deleting with IES (cascade via `files()`) in a later cleanup. |
| UI inconsistency / no delete | Low | Functional gap only; no extra security exposure until per-file delete is implemented with correct guards. |

---

## 7. Confirmation: No Code Modified

This Wave 4A deliverable is **analysis and documentation only**. No code, routes, or Blade/JS were modified. The repository state is unchanged except for the addition of this document.

**Next:** Use this audit and the implementation plan above to generate the **Wave 4B** implementation prompt for IIES hardening + per-file delete + UI.

---

# WAVE 4B — IMPLEMENTATION (COMPLETED)

## 1. Executive Summary (Wave 4B)

IIES attachment surface is now aligned with IES:

- **Read hardening:** `downloadFile` and `viewFile` resolve project from file, then enforce `ProjectPermissionHelper::passesProvinceCheck` and `ProjectPermissionHelper::canView`; `abort(403)` on failure. No ProjectStatus check on read.
- **Per-file delete:** New `destroyFile($fileId)` with province, `ProjectStatus::isEditable`, and `ProjectPermissionHelper::canEdit`; then `$file->delete()` (model `deleting` event cleans storage). JSON response `{ success, message }` or 403/404/500.
- **Route:** `DELETE /projects/iies/attachments/files/{fileId}` → `projects.iies.attachments.files.destroy`, same middleware group as download/view.
- **UI:** File row has `id="iies-file-{{ $file->id }}"` when `$file->id` exists; Delete button calls `confirmRemoveIIESFile`; inline JS `removeIIESFile` uses fetch DELETE and removes DOM row on success.
- **Scope:** IIES only. No changes to IES, IAH, ILP, generic attachments, or reports.

---

## 2. Read Hardening Changes

- **File:** `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`
- **Methods:** `downloadFile($fileId)`, `viewFile($fileId)`
- **Changes:**
  1. After `findOrFail($fileId)`, resolve project: `$project = $file->project ?? $file->iiesAttachment?->project`. If missing, return 404 JSON.
  2. `$user = Auth::user()`.
  3. `if (! ProjectPermissionHelper::passesProvinceCheck($project, $user)) abort(403)`.
  4. `if (! ProjectPermissionHelper::canView($project, $user)) abort(403)`.
  5. Existing logic unchanged: storage exists check, then stream/download or view response.
- **Imports added:** `App\Constants\ProjectStatus`, `App\Helpers\ProjectPermissionHelper`, `Illuminate\Support\Facades\Auth`.

---

## 3. Delete Backend Implementation

- **Method:** `destroyFile($fileId)` in `IIESAttachmentsController`.
- **Guard chain:**
  1. `$file = ProjectIIESAttachmentFile::findOrFail($fileId)`.
  2. `$project = $file->project ?? $file->iiesAttachment?->project`; if missing → 404.
  3. `$user = Auth::user()`.
  4. `ProjectPermissionHelper::passesProvinceCheck($project, $user)` → 403 JSON `{ success: false, message: 'Forbidden.' }` (with Log::warning).
  5. `ProjectStatus::isEditable($project->status)` → 403 same.
  6. `ProjectPermissionHelper::canEdit($project, $user)` → 403 same.
  7. `$file->delete()` (model `deleting` removes file from storage).
  8. Return `response()->json(['success' => true, 'message' => 'Attachment deleted successfully.'])`.
- **Exceptions:** ModelNotFoundException → 404; other Exception → 500 with message.
- **Parent row:** Not deleted. `destroy($projectId)` unchanged.

---

## 4. Route Added

- **Path:** `DELETE /projects/iies/attachments/files/{fileId}`
- **Name:** `projects.iies.attachments.files.destroy`
- **Action:** `IIESAttachmentsController@destroyFile`
- **Placement:** Inside the same middleware group as IIES download/view (`auth`, `role:executor,applicant,provincial,coordinator,general`).
- **web.php:** One line added immediately after the IIES view route.

---

## 5. UI Integration Details

- **File:** `resources/views/projects/partials/Edit/IIES/attachments.blade.php`
- **DOM id:** On the file-item div: `id="iies-file-{{ $file->id }}"` when `isset($file->id)`.
- **Delete button:** Inside the View/Download block, when `isset($file->id)`:  
  `<button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmRemoveIIESFile({{ $file->id }}, {{ json_encode($file->file_name) }})">` with icon and "Delete" label. When `$file->id` is not set (legacy), only View/Download via `Storage::url($file->file_path)` are shown.
- **JS:** Inline script at bottom of blade (before `attachments-validation.js`):
  - `confirmRemoveIIESFile(fileId, fileName)` — confirm dialog, then `removeIIESFile(fileId)`.
  - `removeIIESFile(fileId)` — build URL from `route('projects.iies.attachments.files.destroy', ':id')` with `:id` replaced; `fetch(deleteUrl, { method: 'DELETE', headers: X-CSRF-TOKEN, Accept: application/json })`; on success and `data.success`, remove `#iies-file-{fileId}`; on error, `alert(error.message)`.

---

## 6. Guard Chains (Read vs Mutation)

| Operation | Chain |
|----------|--------|
| **Read (download/view)** | Resolve project → passesProvinceCheck → canView → stream/download. No ProjectStatus. |
| **Mutation (destroyFile)** | Resolve project → passesProvinceCheck → ProjectStatus::isEditable → canEdit → $file->delete(). |

---

## 7. Validation Results

| Check | Expected | Status (to be verified) |
|-------|----------|-------------------------|
| Approved project → delete | 403 | ☐ |
| Cross-province → delete | 403 | ☐ |
| Executor not owner/in_charge → delete | 403 | ☐ |
| Owner editable → delete | Success, row removed | ☐ |
| DOM row removed after delete | No page reload | ☐ |
| Download still works | 200 / file stream | ☐ |
| View still works | 200 / inline | ☐ |
| Route in route:list | `projects.iies.attachments.files.destroy` | ☐ |

*(Validation to be filled after manual/automated testing.)*

---

## 8. Security Impact

- **Before (Wave 4A):** Download/view were ID-only; any user in the role group could access any IIES file by ID. No per-file delete.
- **After (Wave 4B):** Download and view enforce province and canView; cross-province or no-view users receive 403. Per-file delete enforces province, editable status, and canEdit; only authorized users can delete a file. Response format unchanged (JSON/stream as before).

---

## 9. Confirmation: No Other Controllers Modified

- **Touched:** `IIESAttachmentsController.php` only (read hardening + destroyFile).
- **Not touched:** IES, IAH, ILP, AttachmentController, ReportAttachmentController, ProjectController (except no change), or any other project-type controller. No global JS. No shared Blade outside Edit/IIES/attachments.blade.php. No route group changes beyond adding one IIES route.

---

## 10. Notes on Parent Model Deletion Difference

- **IES:** `ProjectIESAttachments::deleting` cascades via `$model->files()->each->delete()`, so each file row is deleted and each file’s `deleting` event runs (storage cleanup).
- **IIES:** `ProjectIIESAttachments::deleting` still calls `deleteAttachments()` (legacy column-based paths). The controller’s `destroy($projectId)` deletes the storage directory and then the parent record; file rows in `project_IIES_attachment_files` are not cascade-deleted by the model. This was not changed in Wave 4B. For full parity, a future change could align IIES parent `deleting` with IES (cascade via `files()`); until then, storage is cleared by the controller’s `Storage::deleteDirectory` and per-file delete uses the file model’s `deleting` event.
