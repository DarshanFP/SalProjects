# WAVE 6A — ILP FULL SECURITY & ARCHITECTURE AUDIT

**Mode:** Audit only — no code changes.  
**Date:** 2026-02-17.  
**Scope:** ILP Attached Documents (controller, models, Blade, routes, storage).  
**Reference gold standard:** IES, IIES, IAH.

---

## 1. EXECUTIVE SUMMARY

ILP (Individual Livelihood Project) attached documents are served and mutated **without controller-level authorization**. File **read** is via **direct public URLs** (`Storage::url()`); there are **no** `downloadFile`/`viewFile` routes or methods. **No** province check, **no** `canView`/`canEdit`, and **no** per-file delete route or Blade delete button. This matches **direct storage exposure** and is a **high security risk** compared to IES/IIES/IAH, which use route-based streaming and full guard chains.

**Naming clarification:** The audit target is **ILP Attached Documents** (not "ILP Attachments"). Actual artifacts:

- **Controller:** `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php`
- **Models:** `ProjectILPAttachedDocuments`, `ProjectILPDocumentFile` (table `project_ILP_document_files`)
- **Blade:** `resources/views/projects/partials/Edit/ILP/attached_docs.blade.php` and `Show/ILP/attached_docs.blade.php`

**Bottom line:** ILP file access **bypasses the application layer**. Anyone with a URL (or able to guess paths) can view/download files. Mutations (store/update/destroy) are only protected by project existence and whatever middleware wraps the project edit flow; there is no file-level or province-aware authorization in the ILP controller.

---

## 2. STRUCTURAL COMPARISON: IES vs IIES vs IAH vs ILP

| Feature | IES | IIES | IAH | ILP |
|--------|-----|------|-----|-----|
| **Controller** | IESAttachmentsController | IIESAttachmentsController | IAHDocumentsController | AttachedDocumentsController |
| **downloadFile($fileId)** | ✅ Streams, guarded | ✅ Streams, guarded | ✅ Streams, guarded | ❌ **Missing** |
| **viewFile($fileId)** | ✅ Streams, guarded | ✅ Streams, guarded | ✅ Streams, guarded | ❌ **Missing** |
| **destroyFile($fileId)** | ✅ Province + isEditable + canEdit | ✅ Same | ✅ Same | ❌ **Missing** |
| **destroy($projectId)** | ✅ | ✅ | ✅ | ✅ (no province/canEdit) |
| **Project from file** | ✅ project / iesAttachment→project | ✅ project / iiesAttachment→project | ✅ project / iahDocument→project | N/A (no file endpoints) |
| **ProjectPermissionHelper** | ✅ passesProvinceCheck, canView, canEdit | ✅ Same | ✅ Same | ❌ **Not used** |
| **Province check** | ✅ On read + delete | ✅ Same | ✅ Same | ❌ **None** |
| **canView on read** | ✅ | ✅ | ✅ | ❌ **None** |
| **canEdit / isEditable on delete** | ✅ | ✅ | ✅ | ❌ **None** |
| **Blade file links** | route() view/download | route() view/download | route() view/download | **Storage::url()** |
| **Blade Delete button** | ✅ + JS | ✅ + JS | ✅ + JS | ❌ **None** |
| **Route list (file ops)** | 3 routes (view, download, destroyFile) | 3 routes | 3 routes | **0 routes** |

---

## 3. CONTROLLER FINDINGS

**File:** `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php`

| # | Question | Answer |
|---|----------|--------|
| 1 | Does downloadFile($fileId) exist? | **No.** |
| 2 | Does viewFile($fileId) exist? | **No.** |
| 3 | Does controller stream files or return Storage::url()? | It does **neither**. It does not serve files; Blade uses `Storage::url()` directly. |
| 4 | Is project resolved from file? | N/A (no file-level endpoints). For store/update/destroy: project is resolved only by `Project::where('project_id', $projectId)->exists()` (store/update) or `ProjectILPAttachedDocuments::where('project_id', $projectId)` (destroy). |
| 5 | Is ProjectPermissionHelper used? | **No.** |
| 6 | Is province check applied? | **No.** |
| 7 | Is canView applied? | **No.** |
| 8 | Is destroyFile($fileId) present? | **No.** |
| 9 | Is destroy($projectId) present? | **Yes.** Deletes `ProjectILPAttachedDocuments` record, calls `deleteAttachments()` and optionally deletes empty directory. No province/canEdit. |
| 10 | Does any method directly expose file path? | No controller method returns a path or URL; **Blade** exposes URLs via `Storage::url($file->file_path)`. |

**Conclusion:** Files are accessed **directly via public URLs**; the controller does not participate in read or per-file delete.

---

## 4. MODEL ANALYSIS

### 4.1 ProjectILPDocumentFile

| Item | Status |
|------|--------|
| **project()** | ✅ `belongsTo(Project::class, 'project_id', 'project_id')` |
| **ilpAttachment()** | N/A — relationship is **ilpDocument()** to `ProjectILPAttachedDocuments` |
| **ilpDocument()** | ✅ `belongsTo(ProjectILPAttachedDocuments::class, 'ILP_doc_id', 'ILP_doc_id')` |
| **file_path** | ✅ Column and usage in boot |
| **deleting event** | ✅ Removes file from `Storage::disk('public')` when model is deleted |
| **Disk** | `public` |
| **Directory pattern** | Stored under `project_attachments/ILP/{projectId}` (via AttachmentContext/Handler); model uses `file_path` |
| **getUrlAttribute / URL exposure** | ✅ `return Storage::url($this->file_path)` — **direct public URL** |

### 4.2 ProjectILPAttachedDocuments

| Item | Status |
|------|--------|
| **project()** | ✅ `belongsTo(Project::class, 'project_id', 'project_id')` |
| **files()** | ✅ `hasMany(ProjectILPDocumentFile::class, 'ILP_doc_id', 'ILP_doc_id')` |
| **deleting behavior** | Calls `deleteAttachments()` in `deleting` callback |
| **deleteAttachments()** | **Legacy only:** iterates `aadhar_doc`, `request_letter_doc`, `purchase_quotation_doc`, `other_doc` and calls `Storage::delete($this->$field)`. Does **not** delete `ProjectILPDocumentFile` rows or their physical files. |
| **Cascade via files()** | **No.** Migration `project_ILP_document_files` has FK to `projects` with `onDelete('cascade')`, not to `project_ILP_attached_docs`. So when `ProjectILPAttachedDocuments` is deleted, child `ProjectILPDocumentFile` rows are **not** removed; their files remain on disk. **Data/storage inconsistency risk.** |

Comparison with IES/IIES/IAH: Those modules use route-based streaming, guarded download/view/destroyFile, and Blade uses `route()`; IES/IIES/IAH parent destroy either cascade to file records or explicitly clean up. ILP has no per-file delete and parent destroy does not clean up the multi-file table or storage for it.

---

## 5. ROUTE ANALYSIS

**Command run:** `php artisan route:list | grep -i ilp`  
**Result:** No ILP routes found.

**From routes/web.php:**

- IES: `projects.ies.attachments.download`, `projects.ies.attachments.view`, `projects.ies.attachments.files.destroy`
- IAH: `projects.iah.documents.view`, `projects.iah.documents.download`, `projects.iah.documents.files.destroy`
- IIES: `projects.iies.attachments.download`, `projects.iies.attachments.view`, `projects.iies.attachments.files.destroy`
- **ILP:** **No** download, view, or per-file delete routes. ILP controller is invoked only via `ProjectController` (e.g. store/update) and show/edit for the project page; there are no dedicated ILP file routes.

**Conclusion:** File access is **not** route-based; Blade uses **Storage::url()** only.

---

## 6. BLADE FINDINGS

**Edit:** `resources/views/projects/partials/Edit/ILP/attached_docs.blade.php`  
**Show:** `resources/views/projects/partials/Show/ILP/attached_docs.blade.php`

| Question | Answer |
|----------|--------|
| How are file links rendered? | `Storage::url($file->file_path)` for both View and Download. |
| Are links route('projects.ilp...')? | **No.** No such routes exist. |
| Are links Storage::url($file->file_path)? | **Yes.** Edit (lines 58–63) and Show (55–59). |
| target="_blank" to /storage/...? | **Yes.** View uses `target="_blank"`; href is public storage URL. |
| Delete button? | **No.** |
| JS for delete? | **No.** (Only add/remove file input UI.) |
| DOM id for file row? | **No** (e.g. no `id="ilp-file-{{ $file->id }}"`). |
| Inline URL exposure? | **Yes.** Full public URL in HTML; anyone with the link can access the file. |

**Conclusion:** ILP read **bypasses the controller entirely**; access is direct to the public disk.

---

## 7. STORAGE EXPOSURE ANALYSIS

| Item | Finding |
|------|---------|
| **Files on public disk?** | **Yes.** Stored via `Storage::disk('public')` (e.g. `storage/app/public`). |
| **Accessible via https://domain.com/storage/...?** | **Yes.** With `php artisan storage:link`, `public/storage` → `storage/app/public`, so URLs like `https://domain.com/storage/project_attachments/ILP/{projectId}/{filename}` serve the file. |
| **.htaccess / middleware blocking /storage?** | **No.** `public/.htaccess` only sends non-file, non-dir requests to `index.php`; it does not restrict `public/storage`. No middleware protects the symlink. |
| **Symlink active?** | Assumed yes when `storage:link` is run (standard Laravel). |
| **ILP URLs guessable?** | **Partially.** Path pattern is `project_attachments/ILP/{project_id}/{filename}`. If `project_id` or filenames are predictable or enumerable, risk increases. |

**Verdict:** **HIGH RISK** — direct public URL access with no application-level authorization.

---

## 8. SECURITY GAP TABLE (SEVERITY RATED)

| # | Gap | Severity | Notes |
|---|-----|----------|--------|
| 1 | **Direct URL exposure** | **Critical** | Files reachable without going through Laravel; no auth, no audit. |
| 2 | **Province bypass** | **Critical** | Any user who obtains the URL can access files from any province. |
| 3 | **canView missing** | **Critical** | No check that the user is allowed to view the project/document. |
| 4 | **canEdit missing** | **High** | store/update/destroy do not enforce canEdit; reliance on project edit page access only. |
| 5 | **Approval bypass** | **High** | No project status / approval check on read or delete. |
| 6 | **ID/path enumeration risk** | **Medium** | Predictable path pattern could allow enumeration of project IDs and files. |
| 7 | **Missing per-file delete** | **High** | No way to remove a single file via UI or guarded API; only full document destroy exists. |
| 8 | **Parent deletion inconsistencies** | **Medium** | destroy($projectId) does not delete `ProjectILPDocumentFile` rows or their files; only legacy columns in deleteAttachments(). Orphaned records and files possible. |

---

## 9. RECOMMENDED REDESIGN PLAN (NO CODE — PROPOSAL ONLY)

### SECTION A — Read Redesign Plan

- **Replace** direct `Storage::url()` with **route-based streaming**.
- **Add** in `AttachedDocumentsController`:
  - `downloadFile($fileId)` — resolve file → project (via `$file->project ?? $file->ilpDocument?->project`), then **passesProvinceCheck** → **canView** → stream file (e.g. `Storage::disk('public')->download(...)`).
  - `viewFile($fileId)` — same guard chain, then stream with `Content-Disposition: inline`.
- **Guard chain:** resolve project → **ProjectPermissionHelper::passesProvinceCheck** → **ProjectPermissionHelper::canView**.

### SECTION B — Mutation Plan

- **Add** `destroyFile($fileId)` with: resolve project from file → **passesProvinceCheck** → **ProjectStatus::isEditable** → **canEdit** → delete file model (storage cleanup via existing `ProjectILPDocumentFile::deleting`).
- **Optionally harden** `store`/`update`/`destroy($projectId)` with same province + canEdit (and, for destroy, isEditable) as used in IES/IIES/IAH.

### SECTION C — Route Plan

- Add under the same middleware group as IES/IAH/IIES (e.g. `auth`, role-based):
  - `GET /projects/ilp/attachments/view/{fileId}` → `viewFile`
  - `GET /projects/ilp/attachments/download/{fileId}` → `downloadFile`
  - `DELETE /projects/ilp/attachments/files/{fileId}` → `destroyFile`
- Name routes e.g. `projects.ilp.attachments.view`, `projects.ilp.attachments.download`, `projects.ilp.attachments.files.destroy`.

### SECTION D — Blade Plan

- **Replace** `Storage::url($file->file_path)` with:
  - View: `route('projects.ilp.attachments.view', $file->id)`
  - Download: `route('projects.ilp.attachments.download', $file->id)`
- Add **DOM id** per file row, e.g. `id="ilp-file-{{ $file->id }}"`.
- Add **Delete** button and JS (e.g. `confirmRemoveILPFile(id, name)`) that call the new destroy route (with CSRF and method DELETE).

### SECTION E — Migration Risk (if moving off direct URLs)

- **Backward compatibility:** Existing links (e.g. in emails or DB) that point to `/storage/...` will continue to work as long as the public disk and symlink remain. New Blade links will use routes; old links remain high risk until deprecated or disabled.
- **DB:** Check if any table stores full `public_url` or `Storage::url()` for ILP files (e.g. `ProjectILPDocumentFile.public_url`); if so, consider whether to stop writing new public URLs and/or migrate links to route-based (e.g. store only file id and build URL from route in app).
- **Orphan cleanup:** Fix parent destroy to delete all related `ProjectILPDocumentFile` rows (and rely on their `deleting` event for storage) or add FK from `project_ILP_document_files` to `project_ILP_attached_docs` with `onDelete('cascade')` in a future migration.

---

## 10. RISK ASSESSMENT

- **Current state:** ILP document files are effectively **public** for anyone with the URL. No province, role, or project-level enforcement on read; mutations are only as safe as the project edit flow and middleware.
- **After redesign:** Read and per-file delete would be consistent with IES/IIES/IAH: province isolation, canView/canEdit, and no direct storage URLs from the app. Remaining risk would be legacy links and any remaining use of `public_url` or direct paths.

---

## 11. CONFIRMATION: NO CODE MODIFIED

This audit did **not** modify:

- Controllers  
- Routes  
- Blade views  
- Models  
- Storage or filesystem config  

Only this documentation file was added. Implementation is left for **Wave 6B** with the above plan as reference.

---

# WAVE 6A.1 — FUNCTIONAL & LAYOUT FINDINGS (UPLOAD FAILURE + SECTION ORDER)

**Mode:** Audit only — no code changes.  
**Date:** 2026-02-17.  
**Scope:** Upload pipeline, edit/view render, section order, root cause, fix plan.

---

## 1. EXECUTIVE SUMMARY (6A.1)

- **Upload failure:** ILP file uploads never reach the handler because the controller’s `hasAnyILPFile()` checks **bare field names** (e.g. `aadhar_doc`) while the form and `AttachmentContext::forILP()` use **prefixed keys** (`attachments.aadhar_doc`). The controller always takes the “no files present” branch and skips `ProjectAttachmentHandler::handle()`.
- **Edit/view not showing files:** Once no file rows exist (because uploads are skipped), the edit and show partials correctly show “No files” or empty lists. The **render path is correct**; the missing data is a consequence of the upload bug. Optional improvement: `edit()` does not eager-load `files` (unlike `show()`), which can cause N+1 when files exist.
- **Section order:** In `resources/views/projects/Oldprojects/edit.blade.php`, ILP sections are included in this order: personal_info → revenue_goals → strength_weakness → risk_analysis → **attached_docs** → **budget**. Requirement: **Attachments must come AFTER Budget**. So the order should be: … risk_analysis → **budget** → **attached_docs**.
- **Form and route:** The edit form has `enctype="multipart/form-data"`, submits via full POST (PUT), and the update flow correctly calls `ilpAttachedDocumentsController->update($request, $project_id)`. No CSRF or route issue identified.

---

## 2. UPLOAD FLOW ANALYSIS

### 2.1 Blade form

| Item | Finding |
|------|---------|
| **Form** | `resources/views/projects/Oldprojects/edit.blade.php`: `<form id="editProjectForm" ... method="POST" enctype="multipart/form-data">` ✅ |
| **Submit** | Button “Save Changes” (`#saveDraftBtn`) adds hidden `save_as_draft` and calls `editForm.submit()` — **full page submit**, not AJAX. Files are sent. ✅ |
| **File input name** | In `partials/Edit/ILP/attached_docs.blade.php`: `name="attachments[{{ $field }}][]"` → e.g. `attachments[aadhar_doc][]`. ✅ |
| **Match controller expectation** | **No.** Controller uses `hasAnyILPFile($request)` which checks `$request->hasFile($field)` with `$field` = `aadhar_doc`, `request_letter_doc`, etc. (no prefix). Laravel receives the file under key `attachments.aadhar_doc` (dot notation for nested). So `hasFile('aadhar_doc')` is **false**. |

### 2.2 Route and method

| Item | Finding |
|------|---------|
| **Form action** | `route('projects.update', $project->project_id)` → `ProjectController@update`. |
| **ILP upload path** | `ProjectController@update` (PUT) calls `$this->ilpAttachedDocumentsController->update($request, $project_id)`. So **update()** handles ILP attachment upload (same request as full edit). ✅ |

### 2.3 Controller

| Item | Finding |
|------|---------|
| **Uses ProjectAttachmentHandler?** | Yes. When `hasAnyILPFile($request)` is true, it calls `ProjectAttachmentHandler::handle($request, $projectId, AttachmentContext::forILP(), self::ilpFieldConfig())`. |
| **hasAnyILPFile()** | **Root cause.** It checks `$request->hasFile($field)` for `$field` in `ILP_FIELDS` (bare names). Form sends `attachments[aadhar_doc][]`, so the request key is `attachments.aadhar_doc`. So `hasAnyILPFile()` is always false → handler is **never** called. |
| **Manual store** | No; handler does all storage. |
| **Validation blocking** | No. Validation runs inside the handler when it is called; it is never reached. |
| **Try/catch** | store/update have try/catch; they do not swallow the “no files” case — they explicitly skip the handler and return 200 when no files are “detected.” |

### 2.4 Handler and context

| Item | Finding |
|------|---------|
| **AttachmentContext::forILP()** | `requestKeyPrefix: 'attachments.'`. So handler builds key as `attachments.` + `aadhar_doc` = `attachments.aadhar_doc`. ✅ Matches form. |
| **ProjectAttachmentHandler** | Uses `$key = $prefix ? $prefix . $field : $field` and `$request->hasFile($key)`. For ILP this is correct. **Break point is in the controller**, not the handler. |

### 2.5 Model and storage

| Item | Finding |
|------|---------|
| **File row created?** | Only when the handler runs; it never runs due to hasAnyILPFile(). So in current state, no new file rows are created on upload. |
| **Parent attachment record** | Handler uses `updateOrCreate(['project_id' => $projectId], [])` on `ProjectILPAttachedDocuments`. So parent would be created/updated if handler ran. |
| **Relationships** | Handler creates rows on `ProjectILPDocumentFile` with correct `ILP_doc_id`, `project_id`, `field_name`, `file_path`, etc. ✅ |
| **Storage** | Handler uses `Storage::disk('public')` and `storeAs($projectDir, $fileName, 'public')` with `$projectDir = "project_attachments/ILP/{$projectId}"`. So files would appear under `storage/app/public/project_attachments/ILP/{projectId}/` if the handler ran. |

**Exact break point:** `AttachedDocumentsController::hasAnyILPFile()` uses the wrong request key (bare field name instead of `attachments.<field>`), so the controller never calls the handler.

---

## 3. RENDER FLOW ANALYSIS (EDIT + VIEW)

| Item | Finding |
|------|---------|
| **How attachments loaded in edit()** | `AttachedDocumentsController::edit($projectId)` returns `ProjectILPAttachedDocuments::where('project_id', $projectId)->first()` (no `->with('files')`). |
| **show()** | Uses `->with('files')`. edit() does not; causes N+1 when Blade iterates over files per field. |
| **Passed to view?** | Yes. `ProjectController@edit` sets `$ILPAttachedDocuments = $this->ilpAttachedDocumentsController->edit($project->project_id) ?? []` and passes it in `compact(...)`. Edit Blade includes `@include('...attached_docs', ['attachedDocs' => $ILPAttachedDocuments])`. ✅ |
| **getFilesForField()** | Partial uses `$ILPDocuments->getFilesForField($field)`. Model has `getFilesForField($fieldName)` which returns `$this->files()->where('field_name', $fieldName)->orderBy('serial_number')->get()`. ✅ |
| **Blade iteration** | Edit and Show partials iterate `$fields` (aadhar_doc, request_letter_doc, etc.) and call `getFilesForField($field)`. **Field keys match DB `field_name`.** ✅ |
| **Why files not visible** | Either (1) no parent record (no uploads ever), or (2) parent exists but no rows in `project_ILP_document_files` because uploads were never processed. So “not visible” is a **consequence of upload never running**, not a separate render bug. |

**Conclusion:** Render logic and variable passing are correct. Fixing the upload (hasAnyILPFile key) will allow file rows to be created; then existing Blade will show them. Optionally add `->with('files')` in `edit()` for consistency and to avoid N+1.

---

## 4. LAYOUT ORDER ANALYSIS

| Item | Finding |
|------|---------|
| **Where ILP edit sections are included** | `resources/views/projects/Oldprojects/edit.blade.php`, inside `@elseif ($project->project_type === 'Individual - Livelihood Application')` (lines 70–76). |
| **Current order** | 1. personal_info, 2. revenue_goals, 3. strength_weakness, 4. risk_analysis, 5. **attached_docs**, 6. **budget**. |
| **Required order** | Attachments section must come **after** Budget: … risk_analysis, **budget**, **attached_docs**. |
| **Defined in** | Order is **only** in this Blade file via the sequence of `@include` statements. No config array or tab layout for this order. |
| **Where to change** | In `edit.blade.php`, swap the two `@include` lines so that `Edit.ILP.budget` is included **before** `Edit.ILP.attached_docs`. Same swap is already correct in Show and PDF (show: 209 attached_docs, 210 budget — so there Show has attached_docs before budget; user asked for “attachments after budget” for **edit**). Create/edit flow in `createProjects.blade.php` (lines 84–89) also has attached_docs before budget; if requirement is “attachments after budget” everywhere, that order could be swapped there too. |

**Safe change:** Reorder the two `@include` lines in `edit.blade.php` only (no controller or config change). No data or routing impact.

---

## 5. ROOT CAUSE SUMMARY

| Issue | Root cause |
|-------|------------|
| **Upload “fails”** | Controller `hasAnyILPFile()` checks `$request->hasFile($field)` with bare `$field`. Form and handler use `attachments.<field>`. So the controller never detects files and never calls the handler. |
| **Files not showing** | No file rows (and possibly no parent record) because uploads are never processed. |
| **Section order** | Edit layout includes attached_docs before budget; requirement is budget then attached_docs. |

No evidence that upload failure is due to: missing project resolution (project is resolved in update), missing parent record (handler would create it), mismatched project_id, wrong disk, validation rule mismatch (handler not reached), or CSRF mismatch. The only functional bug identified is the **request key mismatch in hasAnyILPFile()**.

---

## 6. REQUIRED FIX PLAN (NO CODE YET)

### SECTION A — Upload fix plan

| What | Where | Detail |
|------|--------|--------|
| **File** | `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` | |
| **Method** | `hasAnyILPFile(Request $request)` | |
| **Change** | Use the same request key as `ProjectAttachmentHandler` and `AttachmentContext::forILP()`. `AttachmentContext::forILP()` uses `requestKeyPrefix: 'attachments.'`. So the key for each field must be `'attachments.' . $field` (e.g. `attachments.aadhar_doc`). |
| **Exact fix** | In the loop, replace `$request->hasFile($field)` with `$request->hasFile('attachments.' . $field)` (or use a constant/variable for the prefix to match the context). So store() and update() will call the handler when the user actually uploads files. |

### SECTION B — Render fix plan

| What | Where | Detail |
|------|--------|--------|
| **Optional** | `AttachedDocumentsController::edit()` | Add `->with('files')` when loading the document record so that `getFilesForField()` does not trigger N+1 queries. Align with `show()` which already uses `->with('files')`. |
| **No change** | Blade partials | Edit/Show partials and variable passing are correct; no change required for render once upload is fixed. |

### SECTION C — Layout order fix plan

| What | Where | Detail |
|------|--------|--------|
| **File** | `resources/views/projects/Oldprojects/edit.blade.php` | |
| **Location** | Block `@elseif ($project->project_type === 'Individual - Livelihood Application')` (lines 70–76). | |
| **Change** | Include **Budget** before **Attached docs**. Current: `@include(...attached_docs)` then `@include(...budget)`. Required: `@include(...budget)` then `@include(...attached_docs)`. |
| **Why safe** | Only the order of two partials changes; no data or routing. Same form and POST payload. |

### SECTION D — Security migration reminder

- Route-based read (view/download) and per-file delete remain required as per Wave 6A. This fix only restores **upload and display**; it does not add guarded view/download/destroyFile routes or remove direct `Storage::url()` usage. Wave 6B should still implement the security redesign (route-based streaming, province/canView/canEdit, Blade links and delete button).

---

## 7. CONFIRMATION

- **Audit file updated:** This section was appended to `Documentations/V2/Attachments/Delete/ILP_WAVE6A_Security_Architecture_Audit.md`.
- **No code modified:** No controller, Blade, route, or config changes were made. Diagnosis only; fixes to be applied after this audit.

---

## Wave 6A.2 — Functional Fix Implemented

**Date:** 2026-02-17.  
**Scope:** Upload detection, eager load, section order only. No security redesign.

### 1. Upload detection bug

- **Description:** The controller’s `hasAnyILPFile()` was checking `$request->hasFile($field)` with bare field names (`aadhar_doc`, etc.). The form sends files under `attachments[field][]`, so the Laravel request key is `attachments.aadhar_doc`. The check always failed, so `ProjectAttachmentHandler::handle()` was never called and uploads were skipped.
- **Exact fix applied:** In `AttachedDocumentsController::hasAnyILPFile()`, replaced `$request->hasFile($field)` with `$request->hasFile('attachments.' . $field)` in the loop. No change to `ILP_FIELDS`, handler, `AttachmentContext`, or store/update structure.

### 2. Eager load improvement

- In `AttachedDocumentsController::edit($projectId)`, the query was changed from `ProjectILPAttachedDocuments::where('project_id', $projectId)->first()` to `ProjectILPAttachedDocuments::with('files')->where('project_id', $projectId)->first()`. This aligns with `show()` and avoids N+1 when Blade calls `getFilesForField()` for each field.

### 3. Section order correction

- **File:** `resources/views/projects/Oldprojects/edit.blade.php`
- **Change:** In the `Individual - Livelihood Application` block, the last two includes were swapped so that **Budget** is included before **Attached docs**. Order is now: personal_info → revenue_goals → strength_weakness → risk_analysis → **budget** → **attached_docs**. No other project types or create/show views were modified.

### 4. Validation results

- To be confirmed manually after deployment:
  - Upload a file in ILP edit and confirm handler runs (file saved).
  - Confirm row in `project_ILP_document_files` and file on disk.
  - Confirm file appears on edit and show pages.
  - Confirm no console errors and form submission works.
  - Confirm ILP attachment section appears after Budget.

### 5. Confirmation: no security changes

- **No** viewFile or downloadFile route added.
- **No** destroyFile or delete button.
- **No** Blade link changes; `Storage::url()` still used for View/Download.
- **No** storage disk or security redesign. Security migration remains for Wave 6B.
