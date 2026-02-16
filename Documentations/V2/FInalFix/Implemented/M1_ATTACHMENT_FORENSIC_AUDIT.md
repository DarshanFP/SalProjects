# M1 — Attachment Forensic Audit

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield  
**Mode:** Read-only architecture and data audit. **No code was modified. No data was deleted.**

---

## 1. Table-by-table breakdown

### 1.1 project_attachments

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_attachments` |
| **Columns** | `id`, `project_id`, `file_path` (nullable), `file_name` (nullable), `description` (nullable), `public_url` (nullable), `created_at`, `updated_at` |
| **Nullable** | `file_path`, `file_name`, `description`, `public_url` |
| **Foreign key** | `project_id` → `projects.project_id` (onDelete cascade) |
| **Model** | `App\Models\OldProjects\ProjectAttachment` |

---

### 1.2 project_IES_attachments

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_IES_attachments` |
| **Columns** | `id`, `IES_attachment_id` (unique), `project_id`, `aadhar_card`, `fee_quotation`, `scholarship_proof`, `medical_confirmation`, `caste_certificate`, `self_declaration`, `death_certificate`, `request_letter` (all nullable), `created_at`, `updated_at` |
| **Nullable** | All 8 file columns |
| **Foreign key** | None in migration (project_id references projects) |
| **Model** | `App\Models\OldProjects\IES\ProjectIESAttachments` |
| **Child table** | `project_IES_attachment_files` (optional; 0 rows in DB at audit time) |

---

### 1.3 project_IIES_attachments

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_IIES_attachments` |
| **Columns** | `id`, `IIES_attachment_id` (unique), `project_id`, `iies_aadhar_card`, `iies_fee_quotation`, `iies_scholarship_proof`, `iies_medical_confirmation`, `iies_caste_certificate`, `iies_self_declaration`, `iies_death_certificate`, `iies_request_letter` (all nullable), `created_at`, `updated_at` |
| **Nullable** | All 8 file columns |
| **Foreign key** | None in migration |
| **Model** | `App\Models\OldProjects\IIES\ProjectIIESAttachments` |
| **Child table** | `project_IIES_attachment_files` |

---

### 1.4 project_ILP_attached_docs

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_ILP_attached_docs` |
| **Columns** | `id`, `ILP_doc_id` (unique), `project_id`, `aadhar_doc`, `request_letter_doc`, `purchase_quotation_doc`, `other_doc` (all nullable), `created_at`, `updated_at` |
| **Nullable** | All 4 doc columns |
| **Foreign key** | None in migration |
| **Model** | `App\Models\OldProjects\ILP\ProjectILPAttachedDocuments` |
| **Child table** | `project_ILP_document_files` |

---

### 1.5 project_IAH_documents

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_IAH_documents` |
| **Columns** | `id`, `IAH_doc_id` (unique), `project_id`, `aadhar_copy`, `request_letter`, `medical_reports`, `other_docs` (all nullable), `created_at`, `updated_at` |
| **Nullable** | All 4 doc columns |
| **Foreign key** | None in migration |
| **Model** | `App\Models\OldProjects\IAH\ProjectIAHDocuments` |
| **Child table** | `project_IAH_document_files` |

---

### 1.6 project_IES_attachment_files

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_IES_attachment_files` |
| **Columns** | `id`, `IES_attachment_id`, `project_id`, `field_name`, `file_path`, `file_name`, `description` (nullable), `serial_number`, `public_url` (nullable), `created_at`, `updated_at` |
| **Nullable** | `description`, `public_url` |
| **Foreign key** | `project_id` → `projects.project_id` (cascade) |
| **Model** | `App\Models\OldProjects\IES\ProjectIESAttachmentFile` |

---

### 1.7 project_IIES_attachment_files

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_IIES_attachment_files` |
| **Columns** | `id`, `IIES_attachment_id`, `project_id`, `field_name`, `file_path`, `file_name`, `description` (nullable), `serial_number`, `public_url` (nullable), `created_at`, `updated_at` |
| **Nullable** | `description`, `public_url` |
| **Foreign key** | `project_id` → `projects.project_id` (cascade) |
| **Model** | `App\Models\OldProjects\IIES\ProjectIIESAttachmentFile` |

---

### 1.8 project_IAH_document_files

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_IAH_document_files` |
| **Columns** | `id`, `IAH_doc_id`, `project_id`, `field_name`, `file_path`, `file_name`, `description` (nullable), `serial_number`, `public_url` (nullable), `created_at`, `updated_at` |
| **Nullable** | `description`, `public_url` |
| **Foreign key** | `project_id` → `projects.project_id` (cascade) |
| **Model** | `App\Models\OldProjects\IAH\ProjectIAHDocumentFile` |

---

### 1.9 project_ILP_document_files

| Attribute | Detail |
|-----------|--------|
| **Table** | `project_ILP_document_files` |
| **Columns** | `id`, `ILP_doc_id`, `project_id`, `field_name`, `file_path`, `file_name`, `description` (nullable), `serial_number`, `public_url` (nullable), `created_at`, `updated_at` |
| **Nullable** | `description`, `public_url` |
| **Foreign key** | `project_id` → `projects.project_id` (cascade) |
| **Model** | `App\Models\OldProjects\ILP\ProjectILPDocumentFile` |

---

### 1.10 report_attachments

| Attribute | Detail |
|-----------|--------|
| **Table** | `report_attachments` |
| **Columns** | `id`, `attachment_id` (unique), `report_id`, `file_path` (nullable), `file_name` (nullable), `description` (nullable), `public_url` (nullable), `created_at`, `updated_at` |
| **Nullable** | `file_path`, `file_name`, `description`, `public_url` |
| **Foreign key** | `report_id` → `DP_Reports.report_id` (cascade) |
| **Model** | `App\Models\Reports\Monthly\ReportAttachment` |

---

### 1.11 old_DP_attachments

| Attribute | Detail |
|-----------|--------|
| **Table** | `old_DP_attachments` |
| **Columns** | `id`, `project_id`, `file_path`, `file_name`, `description` (nullable), `created_at`, `updated_at` |
| **Nullable** | `description`; `file_path`/`file_name` not nullable in schema |
| **Foreign key** | `project_id` → `oldDevelopmentProjects.id` (cascade) |
| **Model** | `App\Models\OldProjects\OldDevelopmentProjectAttachment` |

---

## 2. Data integrity metrics

**Definition of incomplete row:** Only `id` and parent/scope key(s) (e.g. `project_id`, and where applicable `*_attachment_id` / `*_doc_id`) are populated; all other content columns are NULL or empty string.

### 2.1 project_attachments

| Metric | Value |
|--------|--------|
| Total rows | 34 |
| Incomplete rows | 0 |
| Percentage incomplete | 0% |
| Systematic? | No |

*Incomplete = (COALESCE(file_path,'') = '' AND COALESCE(file_name,'') = '').*

---

### 2.2 project_IES_attachments

| Metric | Value |
|--------|--------|
| Total rows | 24 |
| Incomplete rows | 19 |
| Percentage incomplete | **79.2%** |
| Systematic? | **Yes** (>30%) |

**Sample incomplete rows (id, IES_attachment_id, project_id):**  
(2, IES-ATTACH-0002, IOES-0002), (4, IES-ATTACH-0004, IOES-0004), (10, IES-ATTACH-0008, IOES-0008), (11, IES-ATTACH-0009, IOES-0009), (12, IES-ATTACH-0010, IOES-0010).

*Incomplete = all 8 file columns null/empty. No child rows in `project_IES_attachment_files` at audit time.*

---

### 2.3 project_IIES_attachments

| Metric | Value |
|--------|--------|
| Total rows | 37 |
| Incomplete rows | 25 |
| Percentage incomplete | **67.6%** |
| Systematic? | **Yes** (>30%) |

**Sample incomplete rows (id, IIES_attachment_id, project_id):**  
(3, IIES-ATTACH-0003, IIES-0003), (6, IIES-ATTACH-0006, IIES-0006), (19, IIES-ATTACH-0015, IIES-0015), (20, IIES-ATTACH-0016, IIES-0016), (22, IIES-ATTACH-0017, IIES-0018).

*Note: Some of these parents have child rows in `project_IIES_attachment_files` (multi-file model). “Incomplete” here means parent’s legacy file columns are all null/empty; actual files may exist in the child table.*

---

### 2.4 project_ILP_attached_docs

| Metric | Value |
|--------|--------|
| Total rows | 1 |
| Incomplete rows | 0 |
| Percentage incomplete | 0% |
| Systematic? | No |

---

### 2.5 project_IAH_documents

| Metric | Value |
|--------|--------|
| Total rows | 11 |
| Incomplete rows | 7 |
| Percentage incomplete | **63.6%** |
| Systematic? | **Yes** (>30%) |

**Sample incomplete rows (id, IAH_doc_id, project_id):**  
(1, IAH-DOC-0001, IAH-0001), (5, IAH-DOC-0005, IAH-0005), (7, IAH-DOC-0007, IAH-0007), (8, IAH-DOC-0008, IAH-0008), (9, IAH-DOC-0009, IAH-0009).

---

### 2.6 File child tables (project_*_attachment_files / document_files)

| Table | Total | Incomplete (file_path or file_name null/empty) | % |
|-------|--------|-------------------------------------------------|---|
| project_IES_attachment_files | 0 | 0 | — |
| project_IIES_attachment_files | 167 | 0 | 0% |
| project_IAH_document_files | 20 | 0 | 0% |
| project_ILP_document_files | 4 | 0 | 0% |

---

### 2.7 report_attachments / old_DP_attachments

| Table | Total | Incomplete | Note |
|-------|--------|------------|------|
| report_attachments | 0 | 0 | No rows at audit time |
| old_DP_attachments | 0 | 0 | No rows at audit time |

---

## 3. Controller behaviour summary

### 3.1 AttachmentController (project_attachments)

| Method | Behaviour |
|--------|-----------|
| **store()** | Checks `hasFile('file')` first; if no file, returns `null` (no DB write). If file present: transaction, store file, create one `ProjectAttachment` row with path/name/description/public_url. **Does not delete** existing. |
| **update()** | Checks `hasFile('file')`; if no file, redirect back with info. If file present: **adds** a new attachment row (does not replace). Transaction. |
| **destroy()** | Not present in this controller (delete may be elsewhere or not exposed). |

**Pattern:** Add-only; no delete-then-recreate. File creation only when file is present.  
**Delete before hasFile?** N/A (no delete in store/update).  
**Creates parent when no file?** No.  
**Transaction:** Yes in store/update.

---

### 3.2 IESAttachmentsController (project_IES_attachments + project_IES_attachment_files)

| Method | Behaviour |
|--------|-----------|
| **store()** | No `hasFile` check. Calls `ProjectAttachmentHandler::handle()`. Handler does `updateOrCreate(['project_id' => $projectId], [])` then processes only fields that `hasFile()`. **Can create/update parent with no files** (all file columns null). Transaction. |
| **update()** | Same: no hasFile check; handler updateOrCreate; can persist empty parent. |
| **destroy()** | `firstOrFail()` then `Storage::deleteDirectory(...)`, then `$attachments->delete()`. Delete runs only when record exists; no delete in store/update. |

**Pattern:** updateOrCreate (create parent even when no files). No bulk delete in store/update.  
**Delete before hasFile?** No (no delete in store).  
**Creates parent when no file?** **Yes.**  
**Transaction:** Yes in store, update, destroy.

---

### 3.3 IIESAttachmentsController (project_IIES_attachments + project_IIES_attachment_files)

| Method | Behaviour |
|--------|-----------|
| **store()** | **Checks `hasAnyFile`** over IIES fields; if no file, returns 200 JSON “skipped (no files present)” **without** calling handler. Does not create parent. |
| **update()** | **No hasFile check.** Calls `ProjectAttachmentHandler::handle()`; handler does `updateOrCreate`. **Can create/update parent with no files.** No transaction in controller (handler has no transaction). |
| **destroy()** | `firstOrFail()` then `Storage::deleteDirectory(...)`, then `$attachments->delete()`. Transaction. |

**Pattern:** store guarded; update not guarded — empty parent possible on update.  
**Delete before hasFile?** No.  
**Creates parent when no file?** In **store** no; in **update** yes.  
**Transaction:** destroy yes; store/update no (handler does not use transaction).

---

### 3.4 AttachedDocumentsController (ILP) (project_ILP_attached_docs + project_ILP_document_files)

| Method | Behaviour |
|--------|-----------|
| **store()** | No hasFile check. Calls `ProjectAttachmentHandler::handle()` → updateOrCreate. **Can create parent with no files.** Transaction. |
| **update()** | Same; can create/update empty parent. Transaction. |
| **destroy()** | `firstOrFail()`, then `$documents->deleteAttachments()`, `$documents->delete()`, optional directory cleanup. Transaction. |

**Pattern:** Same as IES: updateOrCreate without file guard.  
**Creates parent when no file?** **Yes.**  
**Transaction:** Yes.

---

### 3.5 IAHDocumentsController (project_IAH_documents + project_IAH_document_files)

| Method | Behaviour |
|--------|-----------|
| **store()** | No hasFile check. Calls `ProjectAttachmentHandler::handle()` → updateOrCreate. **Can create parent with no files.** Transaction. |
| **update()** | Same. Transaction. |
| **destroy()** | `firstOrFail()`, `Storage::deleteDirectory(...)`, `$documents->delete()`. Transaction. |

**Pattern:** Same as IES/ILP.  
**Creates parent when no file?** **Yes.**  
**Transaction:** Yes.

---

### 3.6 ReportAttachmentController (report_attachments)

| Method | Behaviour |
|--------|-----------|
| **store()** | Validates `file` as required. Only creates attachment when file is present. Add-only. Transaction. |
| **destroy()** | Exists (not fully read in audit); typically deletes one attachment. |

**Pattern:** Add-only; file required for create.  
**Creates parent when no file?** No.  
**Transaction:** Yes.

---

## 4. Risk classification

| Controller / Table | Classification | Reason |
|--------------------|----------------|--------|
| **AttachmentController** (project_attachments) | **A — Safe** | Add-only; hasFile before create; no delete in store/update; 0 incomplete rows. |
| **IESAttachmentsController** (project_IES_attachments) | **D — Corrupted data risk** | Creates parent with no files (store/update); **79% incomplete rows**; no hasFile guard. |
| **IIESAttachmentsController** (project_IIES_attachments) | **D — Corrupted data risk** | store() guarded; **update() can create empty parent**; **68% parent rows incomplete** (legacy columns empty; some have child files). |
| **AttachedDocumentsController** (ILP) | **C — High risk** | Can create empty parent; no hasFile guard. Currently 0 incomplete (1 row only). |
| **IAHDocumentsController** (project_IAH_documents) | **D — Corrupted data risk** | Can create empty parent; **64% incomplete rows**; no hasFile guard. |
| **ReportAttachmentController** (report_attachments) | **A — Safe** | File required; add-only; 0 rows at audit. |
| **old_DP_attachments** | N/A | 0 rows; controller not audited in detail. |

---

## 5. Recommendations

| Area | Recommendation |
|------|----------------|
| **project_IES_attachments** | **Needs M1 guard** (skip store/update when no files present). **Needs cleanup / review:** 19 incomplete parent rows (legacy or shell records); decide whether to backfill from child table, leave as-is, or mark for cleanup. |
| **project_IIES_attachments** | **Needs M1 guard on update()** (skip mutation when no files present, mirror store()). Consider **validation tightening** so update does not call handler with no files. Incomplete count is high but some parents have child file rows; treat as legacy column state. |
| **project_IAH_documents** | **Needs M1 guard** (skip store/update when no files). **Needs cleanup migration or review:** 7 incomplete parent rows. |
| **project_ILP_attached_docs** | **Needs M1 guard** (skip store/update when no files) to prevent future empty parents. |
| **project_attachments** | **Safe** — keep current hasFile behaviour. |
| **report_attachments** | **Safe** — keep file-required validation. |
| **File child tables** | No incomplete rows detected; **safe** from empty file_path/file_name in this audit. |

---

## 6. Confirmation

**NO CODE WAS MODIFIED. NO DATA WAS DELETED. THIS WAS A READ-ONLY FORENSIC AUDIT.**

No changes were made to controllers, models, migrations, or configuration. No guards were added. No cleanup or schema changes were performed.

---

*End of M1 Attachment Forensic Audit.*
