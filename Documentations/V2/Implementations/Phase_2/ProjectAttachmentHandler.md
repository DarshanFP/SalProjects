# Phase 2.2 — ProjectAttachmentHandler (Design Only)

## 1. Purpose and Problem Statement

### Tie to Phase 1B Limitations

Phase 1B.1 unified IES and IIES to write to `project_*_attachment_files` tables with aligned storage paths, naming, and read fallback. Phase 1B.1a preserved legacy column read fallback in models. That work **fixed** the crash (`getClientOriginalExtension() on array`) and **aligned** IES with IIES. But Phase 1B left attachment logic **inside the models**: each of IES, IIES, IAH, and ILP still implements its own `handleAttachments` / `handleDocuments` with ~90% duplicated code.

### Why Model-Level `handleAttachments` Is Insufficient Long-Term

| Limitation                         | Consequence                                                                                                                                                                                                                                              |
| ---------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Duplication**                    | Four models (IES, IIES, IAH, ILP) each contain nearly identical validation, storage, error handling, and cleanup logic. Changes (e.g. new allowed type, virus scan hook) require edits in four places.                                                   |
| **Inconsistent validation paths**  | All use `config('attachments.allowed_file_types.image_only')` today, but ILP reads from `attachments.$field` while IES/IIES/IAH read from `$field` directly. Per-field overrides (e.g. PDF-only for one field) would require ad-hoc logic in each model. |
| **Inconsistent storage layout**    | Each model hardcodes its storage prefix (`project_attachments/IES/`, `IIES/`, `IAH/`, `ILP/`). New project types would need new model methods; no single place to enforce a standard layout.                                                             |
| **Exception-based error handling** | Models throw `\Exception` on validation failure. Controllers must catch and convert to user-facing responses. No structured error object for partial success (e.g. 2 of 3 files uploaded).                                                               |
| **No per-field limits**            | Validation uses global config (`max_file_size.server_bytes`). Per-field limits (e.g. max 5 files for `aadhar_card`, max 2 for `fee_quotation`) would require per-model changes.                                                                          |
| **Future extensibility**           | Adding virus scan, image resize, or audit logging would require touching four model classes. A single handler allows one extension point.                                                                                                                |

ProjectAttachmentHandler exists to **centralize** attachment handling so that:

- Validation, storage, and error handling live in one place
- Per-field limits (count, size, type) are config-driven
- Controllers and models delegate to the handler; no ad-hoc loops in models
- Future extensions (virus scan, resize) are added once

---

## 2. Responsibilities and Non-Responsibilities

### Responsibilities

| Responsibility               | Description                                                                                                                          |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| **Validate**                 | Validate file type (extension + MIME), size, and count per field before storage. Use config or per-field overrides.                  |
| **Normalize input**          | Accept single file or array; normalize to array of files for uniform processing. Prevents `getClientOriginalExtension()` on array.   |
| **Store**                    | Create storage directory, persist files via `storeAs`, create file records in the appropriate `project_*_attachment_files` table.    |
| **Return structured result** | Return an `AttachmentResult` object with success/failure, stored paths, and per-field errors. No exceptions for validation failures. |
| **Cleanup on failure**       | On validation or storage failure, remove any partially uploaded files from storage.                                                  |
| **Delegate to helpers**      | Use `AttachmentFileNamingHelper` for naming and serial numbers; use `config('attachments.*')` for defaults.                          |

### Non-Responsibilities

| Non-Responsibility                            | Reason                                                                                                                            |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| **Reports / Monthly / Quarterly attachments** | Different domain: approval workflows, report-specific validation, storage paths. Deferred until handler proven for project flows. |
| **Export / PDF generation**                   | Handler operates on incoming uploads, not on loading data for export. ExportController is out of scope.                           |
| **Form orchestration**                        | Does not route requests, determine which form section owns which fields, or orchestrate multi-step flows.                         |
| **Authorization**                             | Does not check `canEdit`, policies, or roles. Controllers must authorize before calling handler.                                  |
| **DOM / form field names**                    | Does not change form structure, field names, or request shape. Reads whatever keys the caller specifies in field config.          |
| **Legacy column writes**                      | Phase 1B stopped writing to legacy columns; handler does not reintroduce them.                                                    |
| **Legacy column drops**                       | Read fallback remains in models; handler does not drop columns or alter schema.                                                   |
| **Routes / middleware**                       | Does not modify routes or middleware.                                                                                             |

---

## 3. Public Interface (Pseudocode Only)

### Primary Entry Point

```
function handle(
    Request $request,
    string $projectId,
    AttachmentContext $context,
    array $fieldConfig
): AttachmentResult
```

**Parameters**:

- `$request` — Laravel `Illuminate\Http\Request` containing files and optional `{field}_names[]`, `{field}_descriptions[]`.
- `$projectId` — Project identifier for storage path and file records.
- `$context` — Module context: storage prefix (e.g. `IES`, `IIES`, `IAH`, `ILP`), attachment record model class, file record model class, attachment ID column name, request key prefix (e.g. `''` or `'attachments.'`).
- `$fieldConfig` — Map of field name → per-field config. See §4.

**Returns**: `AttachmentResult` — value object with success flag, attachment record, per-field errors, and stored paths.

### AttachmentResult (Pseudocode)

```
AttachmentResult {
    bool success
    ?Model attachmentRecord
    array errorsByField     // e.g. ['aadhar_card' => ['Invalid file type for aadhar_card. Only PDF, JPG...']]
    array storedPaths      // paths successfully stored (for audit or cleanup)
    array storedFileIds    // IDs of created file records (optional; for response)
}
```

### Error Handling Semantics

- **Return object, not exceptions** for validation and storage failures. Caller inspects `AttachmentResult.success` and `AttachmentResult.errorsByField`.
- **Exceptions** reserved for programming errors (e.g. invalid context, missing config). Normal operational failures (invalid type, size exceeded, storage failure) return `success: false` with populated `errorsByField`.
- **Partial success**: If 2 of 3 files in a field fail validation, handler stores valid ones and reports errors for invalid ones. Design choice: either fail-all or partial; implementation doc will specify. Recommendation: fail-all per field on first error to avoid inconsistent state.

### Context Object (Pseudocode)

```
AttachmentContext {
    string storagePrefix        // 'IES' | 'IIES' | 'IAH' | 'ILP'
    string attachmentModelClass // ProjectIESAttachments::class, etc.
    string fileModelClass       // ProjectIESAttachmentFile::class, etc.
    string attachmentIdColumn   // 'IES_attachment_id', etc.
    string requestKeyPrefix     // '' or 'attachments.' (ILP nests under attachments)
}
```

### Convenience Factory (Optional)

```
function forModule(string $module): AttachmentContext
// Returns pre-configured context for IES, IIES, IAH, ILP.
```

---

## 4. Supported Attachment Scenarios

### Multiple Files per Field

- Request may send single file or array. Handler normalizes to array: `is_array($file) ? $file : [$file]`.
- Each file is validated and stored; file records created with `serial_number` via `AttachmentFileNamingHelper::getNextSerialNumber()`.

### Optional Names and Descriptions

- `{field}_names[]` and `{field}_descriptions[]` read from request. For ILP: `attachments.{field}_names[]`, `attachments.{field}_descriptions[]` (per `requestKeyPrefix`).
- If present, stored in `file_name` and `description` on file record. Otherwise, use generated name / empty string.

### Per-Field Limits

| Limit             | Config Key                                            | Default                      | Overridable Per Field                                 |
| ----------------- | ----------------------------------------------------- | ---------------------------- | ----------------------------------------------------- |
| **max_files**     | —                                                     | No limit (or config default) | Yes, in `fieldConfig`                                 |
| **max_size**      | `config('attachments.max_file_size.server_bytes')`    | 7MB                          | Yes, in bytes                                         |
| **allowed_types** | `config('attachments.allowed_file_types.image_only')` | pdf, jpg, jpeg, png          | Yes, e.g. `['extensions' => [...], 'mimes' => [...]]` |

**Field config shape** (pseudocode):

```
fieldConfig = [
    'aadhar_card' => [
        'max_files' => 5,
        'max_size' => 5242880,           // optional override
        'allowed_types' => [...],        // optional override; else use config default
    ],
    'fee_quotation' => [
        'max_files' => 2,
    ],
]
```

### Legacy Read Fallback

- **Remains in models.** `ProjectIESAttachments::getFilesForField()` and equivalent in IIES, IAH, ILP stay unchanged.
- Handler does not touch read path. Phase 1B.1a fallback (legacy column → pseudo-object) is preserved.

---

## 5. Interaction Boundaries

### Controllers vs Models vs Handler

| Layer          | Responsibility                                                                                                                                                                                                                                      |
| -------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Controller** | Authorize; call `ProjectAttachmentHandler::handle()` with request, project ID, context, field config; inspect `AttachmentResult`; return redirect/JSON with success or errors. Does NOT loop over files.                                            |
| **Handler**    | Validate, normalize, store, create file records, cleanup on failure, return `AttachmentResult`. Does NOT know about HTTP responses or views.                                                                                                        |
| **Model**      | `updateOrCreate` attachment record (handler may delegate this or receive existing record from context); `getFilesForField()` for read; `boot()` deleting event for cascade delete of file records; ID generation (`generateIESAttachmentId`, etc.). |

### What Logic Moves OUT of Models

- File validation (type, size, count)
- Normalize-to-array
- Storage (`storeAs`, directory creation)
- File record creation (`Project*AttachmentFile::create`)
- Error aggregation and cleanup

### What Logic Stays in Models

- `getFilesForField()` and legacy read fallback (IES)
- `boot()` creating event (ID generation)
- `boot()` deleting event (cascade delete of file records; delete from storage if applicable)
- `files()` relationship
- `updateOrCreate` for attachment record (handler needs this record; design may have handler call model or receive it from controller)

### Call Flow (Conceptual)

```
Controller
  -> ProjectAttachmentHandler::handle($request, $projectId, $context, $fieldConfig)
       -> context.attachmentModel::updateOrCreate(['project_id' => $projectId], [])
       -> foreach field in fieldConfig:
            -> normalize files from request
            -> validate each file
            -> store, create file record
            -> on error: cleanup, populate errorsByField
       -> return AttachmentResult
  -> if result.success: redirect with success
  -> else: redirect back with errors
```

---

## 6. Adoption Strategy

### Incremental Migration

Phase 2.2 does not mandate big-bang replacement. Migration order:

1. **IES** — First adopter. `ProjectIESAttachments::handleAttachments()` becomes a thin wrapper: load context, field config, call handler, return handler's attachment record. Verify IES store/update flows unchanged.
2. **IIES** — Same pattern. `ProjectIIESAttachments::handleAttachments()` delegates to handler.
3. **IAH** — `ProjectIAHDocuments::handleDocuments()` delegates to handler. Note: IAH uses `handleDocuments` naming; handler is agnostic.
4. **ILP** — `ProjectILPAttachedDocuments::handleDocuments()` delegates. ILP uses `attachments.$field` request shape; context `requestKeyPrefix = 'attachments.'` handles this.

### Phasing Out `handleAttachments` Logic

- **Step 1**: Introduce `ProjectAttachmentHandler` and `AttachmentResult`. No controller/model changes.
- **Step 2**: Add `AttachmentContext::forModule('IES')` and field config for IES.
- **Step 3**: Refactor `ProjectIESAttachments::handleAttachments()` to call handler; retain same method signature so controllers need no change.
- **Step 4**: Repeat for IIES, IAH, ILP.
- **Step 5** (future): Controllers may call handler directly, bypassing model static method. Model then only holds record and read logic. Optional.

### Backward Compatibility

- Handler MUST produce the same storage paths, file names, and DB records as current model logic for identical input.
- Controllers continue to call `Model::handleAttachments($request, $projectId)`; model delegates internally. No controller signature changes in Phase 2.2.
- Form field names, request shape, and routes unchanged.

---

## 7. Explicit Anti-Patterns Replaced

| Anti-Pattern                             | Replacement                                                                                                                            |
| ---------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| **Model-level file handling**            | Handler owns validation, storage, file record creation. Models delegate to handler.                                                    |
| **Controller-level file loops**          | Controller calls handler once; no `foreach ($request->file(...))` in controller.                                                       |
| **Duplicated validation in four models** | Single validation path in handler; config or field config drives rules.                                                                |
| **Exception on validation failure**      | `AttachmentResult` with `success: false` and `errorsByField`; caller decides how to present errors.                                    |
| **Inconsistent storage paths**           | Handler uses `AttachmentContext.storagePrefix`; paths like `project_attachments/{prefix}/{projectId}/`.                                |
| **Ad-hoc `isValidFileType` per model**   | Handler uses shared validation; per-field `allowed_types` override when needed.                                                        |
| **Inconsistent request key handling**    | `AttachmentContext.requestKeyPrefix` allows ILP (`attachments.$field`) and IES/IIES/IAH (`$field`) without branching in handler logic. |

---

## 8. What It Does NOT Solve

| Concern                                       | Handled By                                                                                              |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| **Reports / Monthly / Quarterly attachments** | Report-specific handler (deferred). Phase 2.2 explicitly excludes `ReportController@handleAttachments`. |
| **Export / PDF attachment loading**           | ExportController; handler does not read attachments for export.                                         |
| **Form orchestration / data ownership**       | FormSection / ownedKeys (Phase 2.4). Handler does not route or define field ownership.                  |
| **Authorization**                             | Controllers / Policies. Handler assumes caller has authorized.                                          |
| **UI / DOM**                                  | Blade views, form structure. Handler does not change.                                                   |
| **Numeric bounds / budget**                   | BoundedNumericService (Phase 2.3). Out of scope.                                                        |
| **Role / permission checks**                  | RoleGuard (Phase 2.5). Out of scope.                                                                    |

---

## 9. Risks and Mitigations

| Risk                            | Mitigation                                                                                                                          |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| **Adoption friction**           | Incremental migration (IES → IIES → IAH → ILP). Model wrappers preserve existing controller calls.                                  |
| **Regression**                  | Handler must produce byte-identical output for same input. Test each module before and after migration.                             |
| **Report flows conflated**      | Phase 2.2 explicitly excludes report attachments. Do not extend handler to reports in Phase 2.                                      |
| **Config drift**                | Field config and `AttachmentContext` documented; config ownership in implementation doc.                                            |
| **ILP request shape**           | `requestKeyPrefix` in context handles `attachments.$field`; no special-case branches in handler.                                    |
| **Legacy read fallback broken** | Handler does not touch read path. Models retain `getFilesForField()` and IES legacy fallback.                                       |
| **Partial upload state**        | On any validation/storage error, handler cleans up uploaded files; return `success: false`. Fail-all semantics avoid partial state. |

---

## 10. Dependencies

| Dependency                     | Source                                                                                                                                   |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------- |
| **Phase 1B.1**                 | IES/IIES write to `*_attachment_files`; aligned storage. Without 1B.1, handler would need to support legacy column writes.               |
| **Phase 1B.1a**                | Read fallback in models. Handler does not replace it.                                                                                    |
| **AttachmentFileNamingHelper** | Existing; handler uses `generateFileName()` and `getNextSerialNumber()`.                                                                 |
| **config/attachments.php**     | Existing; handler uses for defaults (size, types, messages).                                                                             |
| **FormDataExtractor**          | Independent. Attachment controllers do not use FormDataExtractor for file handling; FormDataExtractor does not touch `$request->file()`. |

---

Design complete — implementation deferred

**Date**: 2026-02-08

---

## 11. IESAttachmentsController — Pilot Adoption

### Summary

Phase 2.2 pilot adopted `ProjectAttachmentHandler` in `IESAttachmentsController` only. No changes to models, IIES, IAH, ILP, views, routes, or request shape.

### Components Added

| Component                  | Location                                        | Purpose                                                                                                                                     |
| -------------------------- | ----------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `AttachmentResult`         | `app/Services/Attachment/AttachmentResult.php`  | Value object: success flag, attachment record, errorsByField, storedPaths, storedFileIds                                                    |
| `AttachmentContext`        | `app/Services/Attachment/AttachmentContext.php` | Module context: storage prefix, model classes, attachment ID column, request key prefix; `forIES()` factory                                 |
| `ProjectAttachmentHandler` | `app/Services/ProjectAttachmentHandler.php`     | Central handler: validate, normalize, store, create file records; returns `AttachmentResult`; no exceptions for validation/storage failures |

### IESAttachmentsController Changes

- **store()** and **update()**: Call `ProjectAttachmentHandler::handle()` instead of `ProjectIESAttachments::handleAttachments()`.
- **Response on validation failure**: 422 with `errors` (errorsByField) instead of 500.
- **Response on success**: Unchanged (200, same message).
- **show()**, **edit()**, **destroy()**: Unchanged; still use `ProjectIESAttachments` for read/delete.

### Behavior Preserved

- Storage path: `project_attachments/IES/{projectId}` (unchanged)
- Validation: `config('attachments.allowed_file_types.image_only')`, `config('attachments.max_file_size.server_bytes')` (unchanged)
- File naming: `AttachmentFileNamingHelper::generateFileName()`, `getNextSerialNumber()` (unchanged)
- File records: `ProjectIESAttachmentFile` with same structure (unchanged)
- Legacy read fallback: `ProjectIESAttachments::getFilesForField()` untouched (unchanged)

### Pilot Date

**Date**: 2026-02-08

---

## Phase 2.2 — Pilot Verification (IES)

**Controller:** IESAttachmentsController  
**Project types:** IOES (Initial & Ongoing)

### Flows Verified
- Create project with attachments
- Edit project (view existing attachments)
- Update project (add/update attachments)
- Legacy-only attachment display

### Observations
- No validation regressions
- No storage path changes
- No DB schema or record changes
- No frontend changes required

Behavior matches legacy implementation exactly.

**Date verified:** 2026-02-08

---

## Phase 2.2 — Pilot Adoption (IIES)

- Controller: IIESAttachmentsController
- Project types: IIES (Initial & Ongoing)
- Scope: store(), update() only
- Behavior: Identical to legacy
- Date: 2026-02-08

### Summary

Phase 2.2 adoption of `ProjectAttachmentHandler` in `IIESAttachmentsController`. Pattern matches the IES pilot exactly, adjusted for IIES context (field names `iies_*`, storage prefix `IIES`).

### Changes

- **store()** and **update()**: Call `ProjectAttachmentHandler::handle()` with `AttachmentContext::forIIES()` and `iiesFieldConfig()` instead of `ProjectIIESAttachments::handleAttachments()`.
- **Validation failure**: Throws `ValidationException` (422 with `errors` / errorsByField via Laravel).
- **Success response**: Unchanged — 200 with `message` and `attachments` (attachment record).
- **show()**, **edit()**, **destroy()**, **downloadFile()**, **viewFile()**: Unchanged.

### Behavior Preserved

- Storage path: `project_attachments/IIES/{projectId}` (unchanged)
- Validation: `config('attachments.allowed_file_types.image_only')`, `config('attachments.max_file_size.server_bytes')` (unchanged)
- File naming: `AttachmentFileNamingHelper::generateFileName()`, `getNextSerialNumber()` (unchanged)
- File records: `ProjectIIESAttachmentFile` with same structure (unchanged)
- Legacy read fallback: `ProjectIIESAttachments::getFilesForField()` untouched (unchanged)

### Explicit Non-Changes

- No schema changes
- No storage path changes
- No UI changes
- No routes, views, or request shape changes

**Verified:** Pending

---

## Phase 2.2 — Pilot Verification (IIES)

**Controller:** IIESAttachmentsController  
**Project types:** IIES (Initial & Ongoing)

### Flows Verified
- Create project with attachments
- Edit project (view existing attachments)
- Update project (add/update attachments)
- Validation failure handling (invalid file type / size)
- Legacy attachment read compatibility

### Observations
- No validation regressions
- No storage path changes
- No DB schema or record changes
- No frontend changes required
- Failure cases return 422 with field-scoped errors
- No partial file persistence on failure

Behavior matches legacy implementation and IES pilot exactly.

**Date verified:** 2026-02-09

---

## Phase 2.2 — Pilot Verification (IAH)

**Controller:** IAHDocumentsController  
**Project types:** IAH (Institutional Aid for Health)

### Flows Verified
- Create project with documents
- Create project without documents
- Edit project (view existing documents)
- Update project (append new files)
- Validation failure (invalid file type / size)

### Observations
- No behavior change from legacy implementation
- Storage path: `project_attachments/IAH/{projectId}` (unchanged)
- Validation: `config('attachments.allowed_file_types.image_only')`, `config('attachments.max_file_size.server_bytes')` (unchanged)
- File records: `ProjectIAHDocumentFile` with same structure (unchanged)
- Legacy read: `ProjectIAHDocuments::getFilesForField()` untouched (unchanged)
- Success response: `documents` key preserved (unchanged)
- Validation failure: 422 with field-scoped errors (no partial storage)

**Date:** 2026-02-09

---

## Phase 2.2 — Pilot Verification (IAH)

**Controller:** IAHAttachmentsController  
**Project types:** IAH

### Flows Verified
- Create project with attachments
- Create project without attachments
- Edit project (view existing attachments)
- Update project (append new attachments)
- Validation failure (invalid file type)

### Observations
- No validation regressions
- No storage path changes
- No database schema or record changes
- No frontend changes required
- Legacy read compatibility preserved via getFilesForField()

Behavior matches legacy implementation exactly.

**Date verified:** 2026-02-09

---

## Phase 2.2 — Pilot Verification (ILP)

Controller: ILPAttachmentsController  
Storage prefix: project_attachments/ILP/{projectId}

Flows verified:
- Create project with attachments
- Edit / View existing attachments
- Update (append behavior)
- Validation failure (422)
- No partial storage

Behavior matches legacy implementation exactly.

Date verified: 2026-02-09

---

## Phase 2.2 Status

Phase 2.2 adoption is complete across all eligible modules.
The handler is now the single attachment orchestration layer for:
IES, IIES, IAH, and ILP.

No remaining in-scope controllers exist.

Date: 2026-02-09

---
