# M1 — Attachment File-Upload Forensic Audit

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield  
**Scope:** `app/Http/Controllers/Projects/` — all file-upload-based attachment/document controllers  
**Mode:** Read-only. **No code was modified.** Production DB assumed.

---

## 1. Full attachment controller inventory

Controllers that perform file uploads and/or write to attachment/document tables or file-path columns:

| # | Controller | Table(s) / storage | store() | update() | destroy() | Notes |
|---|------------|---------------------|---------|----------|-----------|--------|
| 1 | **AttachmentController** | `project_attachments` | Yes (single file) | Yes (add file) | Not in controller | General project attachments |
| 2 | **IESAttachmentsController** | `project_IES_attachments`, `project_IES_attachment_files` | Yes | Yes | Yes | IES type; uses ProjectAttachmentHandler |
| 3 | **IIESAttachmentsController** | `project_IIES_attachments`, `project_IIES_attachment_files` | Yes | Yes | Yes | IIES type; uses ProjectAttachmentHandler |
| 4 | **ILP\AttachedDocumentsController** | `project_ILP_attached_docs`, `project_ILP_document_files` | Yes | Yes | Yes | ILP type; uses ProjectAttachmentHandler |
| 5 | **IAH\IAHDocumentsController** | `project_IAH_documents`, `project_IAH_document_files` | Yes | Yes | Yes | IAH type; uses ProjectAttachmentHandler |
| 6 | **LDP\NeedAnalysisController** | `project_LDP_need_analysis` (single `document_path`) | Yes | Yes | Yes | Single file per project |
| 7 | **KeyInformationController** | `projects.problem_tree_file_path` (column) | N/A (called from update flow) | Yes (via storeProblemTreeImage) | N/A | Problem tree image; not a separate attachment table |

**Shared behaviour:** Controllers 2–5 call `ProjectAttachmentHandler::handle()`, which **always** runs `$attachmentModel::updateOrCreate(['project_id' => $projectId], [])` at the start. So if the controller does not check for file presence before calling the handler, a parent row can be created/updated with no file data.

---

## 2. Per-controller inspection (store / update)

### 2.1 AttachmentController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | No. No delete in store/update. |
| Create parent before hasFile()? | No. store(): `if (!$request->hasFile('file')) return null;` — no DB write. update(): same check; no write when no file. |
| Create DB row when no file? | No. |
| hasFile() check? | Yes — `hasFile('file')` at start of store and update. |
| Guard preventing empty mutation? | Yes. |
| Transaction wrapping delete+create? | N/A (no delete). Transaction wraps file store + insert when file present. |
| update() delegates to store()? | No. Separate methods; both check hasFile and only persist when file present. |
| No file uploaded? | store: return null. update: redirect with info. No row created/updated. |
| Empty attachments array? | Single file key `file`; if absent, no mutation. |

---

### 2.2 IESAttachmentsController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | No. No delete in store/update. |
| Create parent before hasFile()? | No. store() and update() both check `hasAnyFile` (over IES_FIELDS) **before** calling handler. If no file, return 200 and do not call handler. |
| Create DB row when no file? | No (guard prevents handler call). |
| hasFile() check? | Yes — `collect(self::IES_FIELDS)->contains(fn ($field) => $request->hasFile($field))`. |
| Guard preventing empty mutation? | Yes (store and update). |
| Transaction wrapping delete+create? | No delete. Transaction wraps handler when handler is called. |
| update() delegates to store()? | No. Both have own guard and call handler. |
| No file uploaded? | Log skip; return 200; handler not called; no parent created/updated. |
| Empty attachments array? | Guard is hasFile per field; empty array => no hasFile => skip. |

---

### 2.3 IIESAttachmentsController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | No. |
| Create parent before hasFile()? | No. store(): hasAnyFile guard; skip if no files. update(): hasAnyFile guard; skip if no files. Handler not called when no files. |
| Create DB row when no file? | No (guard in both store and update). |
| hasFile() check? | Yes — same pattern over IIES_FIELDS in store and update. |
| Guard preventing empty mutation? | Yes (store and update). |
| Transaction wrapping delete+create? | No transaction in store/update (handler has no transaction). destroy() has transaction. |
| update() delegates to store()? | No. |
| No file uploaded? | Log skip; return 200; handler not called. |
| Empty attachments array? | Same as IES; guard prevents mutation. |

---

### 2.4 ILP\AttachedDocumentsController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | No. No delete in store/update. |
| Create parent before hasFile()? | **Yes.** No hasFile/hasAnyFile check. store() and update() call `ProjectAttachmentHandler::handle()` unconditionally. Handler runs `updateOrCreate(['project_id' => $projectId], [])` first — so **parent is created/updated before any file check**. |
| Create DB row when no file? | **Yes.** Handler creates/updates parent with empty attributes; no file rows added. Result: empty/shell parent row. |
| hasFile() check? | **No** in controller. Handler checks hasFile per field internally but only to decide whether to add file rows; it does not skip updateOrCreate. |
| Guard preventing empty mutation? | **No.** |
| Transaction wrapping delete+create? | Transaction wraps handler call. No delete in store/update. |
| update() delegates to store()? | No. |
| No file uploaded? | Handler runs; updateOrCreate; parent row created/updated; no file rows; empty parent. |
| Empty attachments array? | Handler still runs; same outcome. |

---

### 2.5 IAH\IAHDocumentsController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | No. |
| Create parent before hasFile()? | **Yes.** No hasFile/hasAnyFile check. store() and update() call handler unconditionally. Handler runs updateOrCreate first. |
| Create DB row when no file? | **Yes.** Same as ILP. |
| hasFile() check? | **No** in controller. |
| Guard preventing empty mutation? | **No.** |
| Transaction wrapping delete+create? | store() and update() use transaction around handler. No delete in store/update. |
| update() delegates to store()? | No. |
| No file uploaded? | Handler runs; parent created/updated; no file rows. |
| Empty attachments array? | Same. |

---

### 2.6 LDP\NeedAnalysisController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | No. update() deletes old file only **after** hasFile (when storing new file). |
| Create parent before hasFile()? | **Yes in store().** store() does: `if ($request->hasFile('need_analysis_file')) $filePath = ...; else $filePath = null;` then **always** `ProjectLDPNeedAnalysis::updateOrCreate(['project_id' => $projectId], ['document_path' => $filePath]);`. So when no file, row is still created/updated with `document_path = null`. |
| Create DB row when no file? | **Yes in store().** update() keeps existing path when no new file, so does not create empty row; store() does. |
| hasFile() check? | Yes for deciding $filePath; **no** guard to skip updateOrCreate. |
| Guard preventing empty mutation? | **No** in store(). update() preserves existing data when no new file. |
| Transaction wrapping delete+create? | No transaction in store/update. |
| update() delegates to store()? | No. |
| No file uploaded (store)? | updateOrCreate runs with document_path = null — empty row possible. |
| No file uploaded (update)? | Keeps existing document_path; no overwrite with null. |

---

### 2.7 KeyInformationController

| Question | Answer |
|----------|--------|
| Delete existing before hasFile()? | N/A. storeProblemTreeImage is only called when `hasFile('problem_tree_image')`. |
| Create parent before hasFile()? | N/A. Updates `projects.problem_tree_file_path` only when file is present. |
| Create DB row when no file? | No. File path updated only inside storeProblemTreeImage, which is invoked only when hasFile. |
| hasFile() check? | Yes — caller checks hasFile before calling storeProblemTreeImage. |
| Guard preventing empty mutation? | Yes. |
| Table | Not an attachment table; column on `projects`. |

---

## 3. Risk classification table

| Controller | Risk | Reason |
|------------|------|--------|
| **AttachmentController** | **LOW** | hasFile before any write; no parent created without file; add-only for store/update. |
| **IESAttachmentsController** | **LOW** | hasAnyFile guard in store and update; handler not called when no files; transaction when handler runs. |
| **IIESAttachmentsController** | **LOW** | hasAnyFile guard in store and update; handler not called when no files. |
| **ILP\AttachedDocumentsController** | **CRITICAL** | No guard; handler always called; updateOrCreate creates/updates parent even when no files; empty parent rows. |
| **IAH\IAHDocumentsController** | **CRITICAL** | No guard; handler always called; updateOrCreate creates/updates parent even when no files; empty parent rows. |
| **LDP\NeedAnalysisController** | **HIGH** | store() always runs updateOrCreate; when no file, document_path = null; parent created/updated without file. update() is safe (keeps existing path). No transaction. |
| **KeyInformationController** | **LOW** | hasFile before calling file store; updates project column only when file present. |

---

## 4. Likely cause of empty DB rows

| Table / area | Likely cause |
|---------------|--------------|
| `project_IES_attachments` | **Historical:** Before M1 guard was added, store/update called handler unconditionally → updateOrCreate with no file rows. **Current:** Guard in place; no new empty rows from IES controller. |
| `project_IIES_attachments` | **Historical:** update() had no guard → handler ran with no files → updateOrCreate. store() always had guard. **Current:** Guard added to update(); no new empty rows from IIES controller. |
| `project_ILP_attached_docs` | **Current:** No guard in store() or update(). Every call to store/update runs handler → updateOrCreate. When user saves section with no files (or empty request), parent row is created/updated with no file data. |
| `project_IAH_documents` | **Current:** Same as ILP. No guard; handler always runs; empty parent rows when no files sent. |
| `project_LDP_need_analysis` | **Current:** store() always runs updateOrCreate with `document_path => $filePath`. When no file, `$filePath = null` → row created/updated with null document_path. |
| `project_attachments` | Add-only with hasFile; no empty row creation when no file. |
| `projects.problem_tree_file_path` | Updated only when hasFile; no empty path written when no file. |

---

## 5. Which need M1 guard

| Controller | Needs M1 skip-empty guard? | Notes |
|------------|----------------------------|--------|
| **AttachmentController** | No | Already guarded (hasFile); no empty row creation. |
| **IESAttachmentsController** | No | Already has guard in store and update. |
| **IIESAttachmentsController** | No | Already has guard in store and update. |
| **ILP\AttachedDocumentsController** | **Yes** | Add hasAnyFile (over ILP_FIELDS) before calling handler in **store()** and **update()**. If no file, return same success JSON and do not call handler. |
| **IAH\IAHDocumentsController** | **Yes** | Add hasAnyFile (over IAH_FIELDS) before calling handler in **store()** and **update()**. If no file, return same success JSON and do not call handler. |
| **LDP\NeedAnalysisController** | **Yes** | store(): Add guard — if !hasFile('need_analysis_file'), skip updateOrCreate (log and return 200 with same message). update() can stay as-is (keeps existing path when no new file). |
| **KeyInformationController** | No | Already guarded by caller’s hasFile check. |

---

## 6. Recommended implementation order

1. **IAH\IAHDocumentsController** — High incomplete-row rate in audit; same pattern as IES/IIES; add hasAnyFile guard in store() and update() first.
2. **ILP\AttachedDocumentsController** — Same handler pattern; add hasAnyFile guard in store() and update() second.
3. **LDP\NeedAnalysisController** — Different pattern (single file, updateOrCreate with one column). Add guard in store() only: if !hasFile('need_analysis_file'), log and return 200 without calling updateOrCreate.

**Consistency:** IES and IIES already return 200 with success message when skipping; ILP and IAH should do the same (return existing success message, do not call handler). NeedAnalysisController should return existing store success message when skipping.

---

## 7. Summary

- **Inventory:** Seven file-upload controllers in Projects: AttachmentController, IESAttachmentsController, IIESAttachmentsController, ILP AttachedDocumentsController, IAH IAHDocumentsController, LDP NeedAnalysisController, KeyInformationController (project column only).
- **Critical risk (no guard, parent created without file):** ILP AttachedDocumentsController, IAH IAHDocumentsController.
- **High risk (parent/row created with null path when no file):** LDP NeedAnalysisController (store only).
- **Low risk (guard present or hasFile before write):** AttachmentController, IESAttachmentsController, IIESAttachmentsController, KeyInformationController.
- **Empty rows:** Caused by calling ProjectAttachmentHandler (or updateOrCreate) without checking file presence, so updateOrCreate runs and creates/updates parent with no file data.
- **M1 guard needed:** ILP AttachedDocumentsController (store + update), IAH IAHDocumentsController (store + update), LDP NeedAnalysisController (store only).
- **Implementation order:** IAH → ILP → LDP.

**NO CODE WAS MODIFIED. THIS WAS A READ-ONLY FORENSIC AUDIT.**
