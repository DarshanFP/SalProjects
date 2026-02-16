# M1 — IES Attachments File Path Forensic Report

**Date:** 2026-02-14  
**Scope:** Read-only forensic audit. No code modified.  
**Target:** Why file paths are not being saved in table `project_IES_attachments`.

---

## 1. File Storage Mechanism Found

- **Location:** `App\Services\ProjectAttachmentHandler::handle()` (used by both store() and update() in IESAttachmentsController).
- **Storage call:** Line 89: `$filePath = $file->storeAs($projectDir, $fileName, 'public');`
- **Directory:** `$projectDir = "project_attachments/{$context->storagePrefix}/{$projectId}"` → `project_attachments/IES/{project_id}` on disk `public` (storage/app/public).
- **Variable holding path:** `$filePath` (string return from `storeAs()`). Used in existence check and passed to child model create.

---

## 2. Insert Mechanism Found

- **Parent row:** Line 23: `$attachments = $attachmentModel::updateOrCreate(['project_id' => $projectId], []);`  
  Second argument is **empty array `[]`**. No path or other attributes are passed. Parent is created/updated with `project_id` only (and `IES_attachment_id` from model boot).

- **Child row (file):** Lines 104–113: `$fileModel::create([...'file_path' => $filePath,...]);`  
  `ProjectIESAttachmentFile::create()` is called with `file_path` => `$filePath`. Insert is into table **project_IES_attachment_files**, not into project_IES_attachments.

- **Parent save:** Line 133: `$attachments->save();`  
  No path (or other) attributes are set on `$attachments` before this. Only timestamps may change.

---

## 3. Path Variable at Insert Time

- **Child table:** `$filePath` is set by `storeAs()` and passed to `$fileModel::create()`. If the code reaches create(), it has already passed `if (!$filePath || !Storage::disk('public')->exists($filePath))`; so at insert time for the **file** row, `$filePath` is non-null and non-empty for that code path.
- **Parent table:** No path variable is ever assigned to the parent model. Parent insert/update uses only `updateOrCreate(['project_id' => $projectId], [])`. So for the **parent** row, path columns are never set; they remain null/empty.

---

## 4. Column Mismatch

- **project_IES_attachments (parent):** Migration defines columns `aadhar_card`, `fee_quotation`, `scholarship_proof`, `medical_confirmation`, `caste_certificate`, `self_declaration`, `death_certificate`, `request_letter` (all nullable string). Model fillable includes these same names. No name mismatch.
- **project_IES_attachment_files (child):** Migration defines `file_path` (string, not nullable). Model fillable includes `file_path`. No mismatch.
- **Design mismatch:** Parent table has path-like columns; the handler never writes to them. Paths are written only to the **child** table column `file_path`. So the “path columns” that are empty are the **parent** columns; the handler is written to persist paths only in the **child** table.

---

## 5. Does create() Insert Null for Path?

- **Parent:** No create() with path data. Parent is `updateOrCreate(..., [])`, so path columns are never in the insert/update array; they stay null or default.
- **Child:** create() receives `'file_path' => $filePath`. In the current handler flow, $filePath is non-empty when create() runs. So the handler does not insert null into the child `file_path` column for the same request that stored the file.

---

## 6. Does Update Wipe Path?

- **Controller:** update() does not call delete() on the attachment or file models. It calls the same `ProjectAttachmentHandler::handle()` as store(). No delete-then-recreate of attachment rows in the controller.
- **Handler:** No delete of existing attachment or file rows. It uses `updateOrCreate(['project_id' => $projectId], [])` for the parent (so parent path columns are never updated, only left as-is or null) and creates **new** file rows for each uploaded file. Existing file rows are not deleted by the handler in the reviewed code.
- **Parent path columns:** Never written, so they are not “wiped” by update; they remain empty because they are never set.

---

## 7. Controller Flow Summary

**store():**
1. If no file in request → early return 200 (skip mutation).
2. beginTransaction.
3. `ProjectAttachmentHandler::handle(...)`.
4. On success: commit, return 200. On failure: rollBack, return 422/500.

**update():**
1. Same as store: if no file → early return 200.
2. Same handler call. No delete of attachments or files in controller.

**destroy():**
1. firstOrFail() on ProjectIESAttachments.
2. Storage::deleteDirectory for IES project folder.
3. $attachments->delete() (cascade deletes file children via model boot).

No delete-then-recreate in store/update. No code path in the controller writes path data to the parent table.

---

## 8. Model Verification

**ProjectIESAttachments (parent):**
- Table: `project_IES_attachments`.
- Fillable: `project_id`, `IES_attachment_id`, `aadhar_card`, `fee_quotation`, `scholarship_proof`, `medical_confirmation`, `caste_certificate`, `self_declaration`, `death_certificate`, `request_letter`. Path columns are in fillable; no guarded override that would block them.
- Path columns are never passed to this model by ProjectAttachmentHandler; the handler only calls updateOrCreate(..., []).

**ProjectIESAttachmentFile (child):**
- Table: `project_IES_attachment_files`.
- Fillable includes `file_path`. Handler passes `file_path` => `$filePath` into create(). No guarded that would block it.

---

## 9. Database Structure

**project_IES_attachments:**
- Columns: id, IES_attachment_id, project_id, aadhar_card, fee_quotation, scholarship_proof, medical_confirmation, caste_certificate, self_declaration, death_certificate, request_letter, timestamps. All path columns nullable string. No default. Path column present but unused by handler.

**project_IES_attachment_files:**
- Columns: id, IES_attachment_id, project_id, field_name, file_path, file_name, description, serial_number, public_url, timestamps. file_path string not nullable. Present and used by handler.

---

## 10. Request / Validation Flow

- Controller uses FormRequest. No use of `validated()` in the controller for building the insert; the handler uses `$request->hasFile()`, `$request->file()`, `$request->input()` for names/descriptions. Files are not put into a validated array and then mass-assigned; they are processed in a loop and stored, and only the child model is created with path. So validation is not stripping path; the parent simply never receives any path data from the handler.

---

## 11. Conclusion

- **Create:** Parent row is created/updated with `updateOrCreate(..., [])`. Path columns on the parent are never set. Child row is created with `file_path` => `$filePath`; path is persisted in **project_IES_attachment_files**, not in project_IES_attachments.
- **Update:** Same handler; no delete of parent or child. Parent path columns again never set.
- **Root cause (if “file path columns” = parent table):** ProjectAttachmentHandler never assigns or writes any path to the parent model. It only ensures a parent row exists and then writes paths into the child table. So empty path columns in **project_IES_attachments** are by current implementation: paths are stored only in **project_IES_attachment_files**.
- **If “file path columns” = child table:** In the current handler, child create() receives a non-empty `$filePath` when the code path reaches it. If production child rows have empty file_path, that would imply another code path, legacy data, or migration/seed not shown here; within the controller and ProjectAttachmentHandler as read, child file_path is set.

---

**ROOT CAUSE:** ProjectAttachmentHandler only creates/updates the parent row with `updateOrCreate(['project_id' => $projectId], [])` and never sets any of the path columns (aadhar_card, fee_quotation, etc.) on the parent. File paths are persisted only to the child table `project_IES_attachment_files` via `ProjectIESAttachmentFile::create([..., 'file_path' => $filePath, ...])`. The parent table `project_IES_attachments` has path columns but they are not written by this flow.

**SCOPE:** Both Create and Update — same handler is used for both; neither path populates the parent table’s path columns.

**RISK LEVEL:** Medium — display logic uses `getFilesForField()` which reads from the child table first and falls back to parent path columns; so if child table is populated, display can work. If any consumer or report reads path only from the parent table, those columns will remain empty.
