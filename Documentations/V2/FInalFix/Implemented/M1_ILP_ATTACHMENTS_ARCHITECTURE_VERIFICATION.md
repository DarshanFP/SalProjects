# M1 — ILP Attached Documents Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Attachment Guard)  
**Controller path:** `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php`  
**Mode:** Read-only analysis. **No code was modified.**

---

## STEP 1 — Structure confirmation

| # | Question | Answer |
|---|----------|--------|
| 1 | Does store() call ProjectAttachmentHandler::handle()? | **Yes.** Lines 38–43. |
| 2 | Does update() call the same handler? | **Yes.** Lines 164–169. |
| 3 | Does update() delegate to store() or duplicate logic? | **Duplicates logic.** update() does not call store(). It has its own DB::beginTransaction(), try block, handler call, validation check, commit, and JSON return. Same pattern as store() but a separate code path. |
| 4 | Does destroy() use firstOrFail() or allow silent success? | **Uses firstOrFail().** Line 202: `ProjectILPAttachedDocuments::where('project_id', $projectId)->firstOrFail()`. If no record exists, ModelNotFoundException is thrown and the catch returns 500 JSON. So it does **not** allow silent success when no record exists. |
| 5 | Are transactions used? | **Yes.** store(): DB::beginTransaction() at line 32; commit at 56; rollBack in catch (60) and on validation failure (46). update(): beginTransaction at 161; commit at 184; rollBack on failure and in catch. destroy(): beginTransaction at 201; commit at 219; rollBack in catch. |

---

## STEP 2 — Corruption risk confirmation

| # | Question | Answer |
|---|----------|--------|
| 1 | Is ProjectAttachmentHandler::handle() called unconditionally? | **Yes.** In store(), the only condition before the handler is that the project exists (404 otherwise). In update(), there is no condition; the handler is always called. There is no check for file presence in either method. |
| 2 | Is there ANY existing file presence guard? | **No.** No `hasFile()` or `hasAnyFile()` (or equivalent) check before the handler in store() or update(). |
| 3 | Does updateOrCreate(['project_id' => $projectId], []) occur? | **Yes.** It occurs inside ProjectAttachmentHandler::handle(), which is invoked by both store() and update(). The handler runs that updateOrCreate at the start of its logic. |
| 4 | Can a request with no files still create a parent row? | **Yes.** When no files are present, the controller still calls the handler. The handler performs updateOrCreate first, then processes only fields that have files. With no files, the parent row is created or updated with no file data (and no child file rows)—i.e. an empty/shell parent record. |

---

## STEP 3 — File fields

| # | Item | Detail |
|---|------|--------|
| 1 | Config method | `self::ilpFieldConfig()` (lines 24–27). Returns `array_fill_keys(self::ILP_FIELDS, [])`. |
| 2 | File field names | `aadhar_doc`, `request_letter_doc`, `purchase_quotation_doc`, `other_doc` (from ILP_FIELDS, lines 17–21). |
| 3 | hasFile() compatibility | These four keys are the same names used by the handler for `$request->hasFile($key)` (with no prefix in ILP context). So they are the correct request field names for a file presence check. |

---

## STEP 4 — Comparison with IAH / IES / IIES

**Verdict: D) Structurally different** — same overall pattern as IAH/IES/IIES (handler, transaction, JSON), but **ILP has no skip-empty (file presence) guard**. IAH, IES, and IIES all have a guard before calling the handler when no files are present; ILP does not.

| Aspect | ILP AttachedDocumentsController | IAH (post-guard) | IES | IIES |
|--------|----------------------------------|------------------|-----|------|
| **Transactions** | store/update/destroy all use beginTransaction + commit/rollBack. | Same. | store/update: transaction wraps handler when called. | store/update: no transaction; destroy has transaction. |
| **Error responses** | 404 (project not found), 422 (validation), 500 (exception). JSON. | Same style. | Same style. | Same style; ValidationException thrown. |
| **Validation style** | Handler returns success/errors; controller checks $result->success and returns 422 with errors. | Same. | Same. | Same; throws ValidationException. |
| **Guard presence** | **None.** Handler always called when project exists (store) or always (update). | **Yes.** hasAnyIAHFile() in store and update; skip with commit + 200 when no files. | **Yes.** hasAnyFile in store and update; skip with 200 when no files. | **Yes.** hasAnyFile in store and update; skip with 200 when no files. |
| **Destroy behavior** | firstOrFail(); deleteAttachments(); delete(); optional directory cleanup; transaction. | firstOrFail(); deleteDirectory(); delete(); transaction. | firstOrFail(); deleteDirectory(); delete(); transaction. | Same as IAH. |

**Summary:** ILP matches IAH/IES/IIES in handler usage, project check (store), transaction usage, and JSON responses. The only structural difference is the **absence of a file presence guard** in ILP, so the handler (and thus updateOrCreate) runs even when no files are uploaded, creating corruption risk.

---

## STEP 5 — Documentation content

### Structure summary

- **store():** beginTransaction → try → project exists check (404 if not) → **handler call (no file check)** → on success: commit, 200 with message; on validation failure: rollBack, 422; on exception: rollBack, 500.
- **update():** beginTransaction → try → Log → **handler call (no file check)** → same success/failure handling as store().
- **destroy():** beginTransaction → try → firstOrFail → deleteAttachments → delete → optional directory delete → commit, 200; catch: rollBack, 500.
- **File fields:** aadhar_doc, request_letter_doc, purchase_quotation_doc, other_doc (from ilpFieldConfig() / ILP_FIELDS).

### Corruption risk assessment

- **Current state:** Handler is invoked unconditionally in both store() and update(). The handler runs updateOrCreate on the parent model (project_ILP_attached_docs) before processing any file fields. When the request has no files, the parent row is still created or updated, with no file columns and no child file rows. This produces empty/shell parent records and matches the pattern that led to incomplete rows in IAH/IIES before guards were added.
- **Risk level:** **CORRUPTION RISK** — empty parent rows can be created when users save the section without uploading files (or when the request omits file fields).

### Before vs After (current state only)

| Scenario | Current state (no guard) |
|----------|---------------------------|
| store() with no files | Handler called; updateOrCreate runs; parent row created/updated with no file data. |
| store() with files | Handler called; parent + file rows created/updated; 200. |
| update() with no files | Handler called; parent created/updated with no file data. |
| update() with files | Handler called; parent + file rows updated; 200. |
| destroy() | firstOrFail, deleteAttachments, delete, commit, 200 (or 500 if not found). |

*(“After” in this verification means “if a guard were added later”; for this document only current behaviour is documented.)*

### Verdict

**CORRUPTION RISK**

The controller does not check for file presence before calling ProjectAttachmentHandler::handle(). Empty parent rows can be created in both store() and update(). A skip-empty guard (e.g. hasAnyFile over ILP_FIELDS before the handler) is required to align with IAH/IES/IIES and remove this risk.

---

**NO CODE WAS MODIFIED. THIS WAS A READ-ONLY VERIFICATION.**
