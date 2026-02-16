# M1 — IES Attachments Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Target:** `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` ONLY.

---

## 1. Summary of Change

A **skip-empty (presence) guard** was added to `IESAttachmentsController::store()` and `::update()` so that `ProjectAttachmentHandler::handle()` is **not** called when no attachment files are present in the request. This prevents creation or update of an empty parent record (`project_IES_attachments`) with no file data.

- **Before:** Every call to `store()` or `update()` invoked the handler. The handler performs `updateOrCreate(['project_id' => $projectId], [])`, so when no files were sent, a parent row was still created or updated with no file columns and no child file rows (empty/shell record).
- **After:** Before starting a transaction or calling the handler, the controller checks whether **any** IES attachment field has a file (`$request->hasFile($field)` over `IES_FIELDS`). If **no** files are present, it logs and returns the **same** success JSON response as the normal path, without calling the handler and without starting a transaction. When at least one file is present, behaviour is unchanged: transaction wraps the handler, validation and persistence run as before.

No changes were made to `destroy()`, `ProjectAttachmentHandler`, validation rules, transaction boundaries (when the handler is called), or response format. Existing attachments are not deleted when no files are sent; the guard only avoids creating/updating an empty parent when no files are uploaded.

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| No files in request (store) | Handler runs; parent created/updated with no file data. | Guard returns 200 with "IES attachments saved successfully."; handler not called; no parent created/updated. |
| No files in request (update) | Handler runs; parent updated (still empty). | Guard returns 200 with "IES Attachments updated successfully."; handler not called; no mutation. |
| At least one file in request (store) | Handler runs; transaction; parent + file rows created/updated. | **Same.** |
| At least one file in request (update) | Handler runs; transaction; parent + file rows updated. | **Same.** |
| destroy() | firstOrFail, deleteDirectory, parent delete, 200. | **Unchanged.** |

---

## 3. Exact Files Modified

| File | Change |
|------|--------|
| `app/Http/Controllers/Projects/IES/IESAttachmentsController.php` | In `store()`: added `$hasAnyFile` check over `IES_FIELDS` before `DB::beginTransaction()`; if no files, log and return success JSON. In `update()`: same guard and early return. |

No other files were modified.

---

## 4. Confirmation

- **Only IESAttachmentsController was modified.** No other controllers, services, or models were changed.
- **No schema change.** No migrations or table definitions were touched.
- **No handler modification.** `ProjectAttachmentHandler` was not modified.
- **No delete logic change.** `destroy()` and any delete behaviour in store/update are unchanged. Existing attachments are not deleted when no files are sent.
- **No transaction change.** When the handler is invoked, it is still wrapped in `DB::beginTransaction()` / commit / rollBack. The guard runs **before** the transaction; when the guard skips, no transaction is started.

---

## 5. Manual Test Cases

1. **No files submitted → no parent created**  
   Call store (or update) with no file inputs for any IES attachment field.  
   **Expect:** Response 200 with `message` "IES attachments saved successfully." (store) or "IES Attachments updated successfully." (update). Log entry "IESAttachmentsController@store - No files present; skipping mutation" (or @update). No new row in `project_IES_attachments`; no call to the handler.

2. **At least one file submitted → handler runs normally**  
   Call store (or update) with at least one of the IES fields containing an uploaded file.  
   **Expect:** Transaction runs; handler runs; parent created or updated; file rows created as per validation; response 200 with same success message as before.

3. **Existing attachments + no new files (update)**  
   Project already has IES attachments. Call update with no new files.  
   **Expect:** Guard triggers; response 200 "IES Attachments updated successfully."; no handler call; existing attachments and parent row unchanged (not deleted).

4. **destroy() still deletes correctly**  
   Call destroy(projectId) for a project that has IES attachments.  
   **Expect:** Transaction; firstOrFail; storage directory deleted; parent deleted; response 200 "IES attachments deleted successfully." Behaviour unchanged from before the guard.

---

*End of M1 IES Attachments Guard Implementation.*
