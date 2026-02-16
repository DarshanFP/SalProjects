# M1 — IES Attachments Architecture Verification

**Date:** 2026-02-14  
**Target:** `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`  
**Mode:** Read-only architecture verification. **No code was modified.**

---

## Summary

IESAttachmentsController uses a **shared handler** for both store and update: both call `ProjectAttachmentHandler::handle()` with IES context. There is **no delete** in `store()` or `update()`. The handler uses **updateOrCreate** on the parent and only processes fields for which `$request->hasFile($key)` is true; it does not delete existing attachment rows. **destroy()** is the only place that deletes: it loads the parent by `project_id`, deletes the storage directory, then deletes the parent (cascade/relationship removes child file rows). The main risk is **empty parent creation**: when no files are sent, the controller still runs the handler, which creates or updates the parent record with no file data (and no child file rows), leading to incomplete/shell rows in the database. There is **no presence check** in the controller before calling the handler.

---

## Step 1 — Mutation Pattern

### store() and update()

- **store():** Lines 26–59. Calls `ProjectAttachmentHandler::handle()` inside `DB::beginTransaction()` / try. Does **not** call any delete. Does **not** delegate to update().
- **update():** Lines 103–145. Separate method; also calls `ProjectAttachmentHandler::handle()` inside a transaction. Does **not** delegate to store(). Does **not** call any delete.

### Where delete() is called

- **Only in destroy()** (lines 151–165):  
  `$attachments = ProjectIESAttachments::where('project_id', $projectId)->firstOrFail();`  
  then `\Storage::deleteDirectory("project_attachments/IES/{$projectId}");`  
  then `$attachments->delete();`  
  (Model deleting cascade/relationship deletes child `ProjectIESAttachmentFile` rows.)

- **Not in store() or update().** The handler does **not** perform any delete; it uses `updateOrCreate(['project_id' => $projectId], [])` and then only adds/creates file rows for fields that have uploaded files.

### Delete pattern

- **Exact pattern in destroy():** `where('project_id', $projectId)->firstOrFail()` then `$attachments->delete()` (and storage directory delete). No `where()->delete()` in store/update; no relationship delete in store/update.
- **Does delete run even if no attachments provided?** In **store/update**, delete **never** runs (there is no delete). In **destroy()**, delete runs only when the method is explicitly invoked (user deletes attachments); it does not run as a side effect of store/update with no files.

**Documented conclusion:** Store and update do **not** delete existing attachments. Delete runs only in `destroy()`, using `firstOrFail()` then model `delete()` (and directory delete).

---

## Step 2 — Parent/Child Pattern

- **Pattern:** **D) updateOrCreate** (inside `ProjectAttachmentHandler`).

  - Handler: `$attachments = $attachmentModel::updateOrCreate(['project_id' => $projectId], []);`  
  - Then, for each field where `$request->hasFile($key)`, it stores the file and creates a **child** row via the context’s file model.

- **Parent model:** `App\Models\OldProjects\IES\ProjectIESAttachments` (table `project_IES_attachments`).

- **Child model:** `App\Models\OldProjects\IES\ProjectIESAttachmentFile` (table `project_IES_attachment_files`). Child rows are created in the handler when files are present.

- **Relationship:** Parent hasMany files (e.g. `files()` on `ProjectIESAttachments`). Handler creates child rows with `$fileModel::create([...])`; parent is found/created by `updateOrCreate` on `project_id`.

So: **parent record is created/updated with updateOrCreate; then attachment (child) rows are created only when files are uploaded.** No sync, no delete-then-recreate in store/update.

---

## Step 3 — Presence Detection

- **In the controller:** There is **no** `$request->hasFile(...)` (or similar) check before calling `ProjectAttachmentHandler::handle()` in either `store()` or `update()`.

- **In the handler:** Presence is detected per field with `if (!$request->hasFile($key)) { continue; }`. So if no field has a file, the handler still runs: it performs `updateOrCreate` (creating or updating the parent) and then the loop does nothing; it then calls `$attachments->save()`.

**Confirmed:** If the request contains **no files**, the controller still runs the handler, so **mutation still executes**: the parent record is created or updated, with no file columns and no child file rows (empty/shell record).

---

## Step 4 — Empty Mutation Risk Analysis

1. **Can existing attachments be deleted if no files are sent / attachment key is absent / empty array is sent?**  
   **No.** Store and update do not call any delete. Existing parent and child rows are not removed when the request has no files or empty data. Only **destroy()** deletes, and that is a separate user action.

2. **Can a parent record be created with no file data?**  
   **Yes.** When no files are sent, the handler still runs: `updateOrCreate` ensures a parent row exists, and no child file rows are created. The parent is then saved. So a parent record **can** be created (or updated) with no file data and no child rows.

3. **Does destroy() behave differently from store()?**  
   **Yes.** destroy() deletes the parent and storage directory (and thus existing attachments). store() and update() never delete; they only create/update the parent and add child rows when files are present. So destroy() is the only path that removes data.

---

## Step 5 — Transaction Safety

- **Is DB::transaction used?**  
  **Yes** in both store() and update(): `DB::beginTransaction()` at the start of try, `DB::commit()` on success, `DB::rollBack()` in catch.

- **Does early return inside transaction risk partial commit?**  
  The only early returns are on validation failure (`if (!$result->success)`), and they call `DB::rollBack()` before returning. So no early return commits; rollback is explicit.

- **Does delete + create run outside transaction?**  
  In store/update there is no delete. All work (handler’s updateOrCreate and file creation) runs inside the same transaction. destroy() has its own transaction and only deletes.

---

## Step 6 — Comparison with IIESAttachmentsController

| Aspect | IESAttachmentsController | IIESAttachmentsController |
|--------|---------------------------|----------------------------|
| update() delegates to store()? | No (separate methods, both call handler) | No (separate methods) |
| Delete in store/update? | No | No |
| Presence check before handler in **store()**? | **No** | **Yes** — `$hasAnyFile` over IIES_FIELDS; early return 200 “skipped (no files present)” |
| Presence check before handler in **update()**? | **No** | **No** |
| Handler | ProjectAttachmentHandler (updateOrCreate) | Same |
| Transaction in store/update? | Yes (controller wraps handler) | No (store/update do not use DB::transaction) |
| destroy() | firstOrFail, deleteDirectory, parent delete, transaction | firstOrFail, deleteDirectory, parent delete, transaction |
| Error handling (validation failure) | return JSON 422, rollBack | throw ValidationException (no rollBack; no transaction in store) |

**Same pattern:** Both use the same handler and updateOrCreate; neither deletes in store/update.  
**Main difference:** IIES **store()** has a skip-empty guard (`hasAnyFile`); IES has **no** presence check, so IES always runs the handler and can create/update an empty parent when no files are sent.  
**Guard applicability:** A skip-empty guard **identical in intent** to IIES (check `hasFile` for any IES field before calling the handler) can be applied to IES store() and update() so that when no files are present, the controller returns success without calling the handler, avoiding empty parent creation.

---

## Data Loss Risk Rating

**Medium (empty parent creation; no accidental delete in store/update).**

- **Low** would apply if no mutation ran when no files are sent.  
- **High** would apply if store/update could delete existing attachments when no files are sent.  
- Here: existing attachments are **not** deleted by store/update when no files are sent. The risk is **creating/updating an empty parent** (incomplete/shell row), which matches observed high rates of incomplete rows in the IES attachments table (see M1 Attachment Forensic Audit). Hence **Medium** data loss risk.

---

## Safe to Apply Skip-Empty Guard Identical to IIES (Expenses) Pattern?

**Yes.** The controller does not perform delete-then-recreate in store/update; it only calls a handler that uses updateOrCreate and adds file rows when files exist. Adding a guard that:

- Before calling the handler in **store()** and **update()**,
- Checks that at least one IES attachment field has a file (e.g. `$request->hasFile(...)` over IES_FIELDS),
- And if none, returns the same success JSON (e.g. 200 with “IES attachments saved/updated successfully”) **without** calling the handler,

would prevent empty parent creation while keeping behaviour unchanged when files are present. No change to delete logic or transaction structure is required for this guard. The pattern is the same as IIES Attachments **store()** (hasAnyFile then skip), not the expenses “delete-then-recreate” pattern; the guard only needs to prevent calling the handler when no files are present.

---

## Recommendation for Next Prompt

**Simple guard.**

- Add a **skip-empty** check before calling `ProjectAttachmentHandler::handle()` in both **store()** and **update()** (e.g. “if no IES field has a file, log and return 200 with same success message, do not call handler”).
- Use the same pattern as IIESAttachmentsController **store()**: e.g. `collect(self::IES_FIELDS)->contains(fn ($field) => $request->hasFile($field))`; if false, return success and skip handler.
- Do **not** add delete logic; do **not** change validation, transaction boundaries, or response format beyond the early return when no files are present.
- Optional: Align IES with IIES by returning a distinct message when skipped (e.g. “IES attachments skipped (no files present)”) or keep the same message as current success for backward compatibility; either is a simple guard.

**No special guard** (no conditional delete, no schema change, no different logic per project type). **No “no guard needed”** — the audit shows empty parent creation is possible and systematic incomplete rows exist; a simple presence check is sufficient.

---

**NO CODE WAS MODIFIED. THIS WAS AN ARCHITECTURE VERIFICATION ONLY.**
