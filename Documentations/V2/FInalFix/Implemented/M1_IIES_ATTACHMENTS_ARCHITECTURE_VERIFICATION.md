# M1 — IIES Attachments Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield  
**Target:** `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`  
**Mode:** Read-only architecture verification. **No code was modified.**

---

## PHASE 1 — STRUCTURAL ANALYSIS

### 1.1 Method existence and delegation

| Question | Answer |
|----------|--------|
| Does **store()** exist? | **Yes.** Lines 34–86. |
| Does **update()** delegate to store()? | **No.** update() is a separate method (lines 162–209); it does not call store(). It calls `ProjectAttachmentHandler::handle()` directly. |
| Does **destroy()** exist? | **Yes.** Lines 214–239. |
| Are responses JSON or redirect? | **JSON only.** store(): 200 with `message` (and optionally `attachments`), or ValidationException, or 500. update(): same. destroy(): 200 with `message` or 500. show/edit: return model or null; downloadFile/viewFile: file response or JSON error. No redirects. |

### 1.2 Attachment field keys

| Question | Answer |
|----------|--------|
| Is there a constant like IIES_FIELDS? | **Yes.** `private const IIES_FIELDS = ['iies_aadhar_card', 'iies_fee_quotation', 'iies_scholarship_proof', 'iies_medical_confirmation', 'iies_caste_certificate', 'iies_self_declaration', 'iies_death_certificate', 'iies_request_letter'];` |
| Are fields passed directly to handler? | Fields are passed via `self::iiesFieldConfig()`, which returns `array_fill_keys(self::IIES_FIELDS, [])`. The handler is called with `AttachmentContext::forIIES()` and this config. |

### 1.3 Validation method

| Question | Answer |
|----------|--------|
| FormRequest? | **Yes.** store() and update() type-hint `FormRequest $request`. Route/resolver typically injects `StoreIIESAttachmentsRequest` and `UpdateIIESAttachmentsRequest` (imported at top). |
| Validator? | Not used directly in the controller. Handler performs validation; on failure the controller throws `ValidationException::withMessages($result->errorsByField)`. |
| Custom normalization? | None in the controller. Handler uses request keys as-is (with context prefix for IIES). |

---

## PHASE 2 — MUTATION FLOW ANALYSIS

### store()

1. **Does it always call ProjectAttachmentHandler::handle()?**  
   **No.** It calls the handler only when at least one file is present. Before the handler, there is a presence check (see below).

2. **Is there any presence check before calling the handler?**  
   **Yes.**  
   `$hasAnyFile = collect(self::IIES_FIELDS)->contains(fn ($field) => $request->hasFile($field));`  
   If `!$hasAnyFile`, the controller logs and returns `response()->json(['message' => 'IIES attachments skipped (no files present).'], 200)` and **does not** call the handler.

3. **If no files are present:**  
   - Handler is **not** called.  
   - No `updateOrCreate` runs for this request, so no new empty parent row is created by this path. Existing data is unchanged.

4. **Does it delete existing attachments before handling?**  
   **No.** There is no `delete()`, `forceDelete()`, `sync()`, or `detach()` in store(). The handler uses `updateOrCreate(['project_id' => $projectId], [])` and only adds file rows for fields that have uploads; it does not delete existing attachment rows in store.

### update()

1. **Does it always call ProjectAttachmentHandler::handle()?**  
   **Yes.** There is no presence check. The handler is always invoked when update() is executed.

2. **Is there any presence check before calling the handler?**  
   **No.** No `$request->hasFile(...)` or equivalent before `ProjectAttachmentHandler::handle()`.

3. **If no files are present:**  
   - Handler **is** called.  
   - Handler performs `updateOrCreate(['project_id' => $projectId], [])`, so the parent record is created or updated. No file rows are added when no files are sent. Result: **empty parent row can be created or updated** (shell record with no child file rows).

4. **Does it delete existing attachments before handling?**  
   **No.** Same as store(); no delete/sync/detach in the controller or in the handler’s normal flow for update.

---

## PHASE 3 — DATA LOSS RISK

### 3.1 Empty request behaviour

| Question | Answer |
|----------|--------|
| Can an empty request **delete** existing attachments? | **No.** store() and update() do not delete. Only destroy() deletes. |
| Can an empty request **create empty parent** records? | **store(): No** (guard prevents handler call). **update(): Yes** (handler is called and runs updateOrCreate). |
| Can an empty request **corrupt** attachment structure? | Not in the sense of deleting or overwriting existing file rows. Risk is limited to **creating/updating a parent with no file data** when update() is called with no files. |

### 3.2 Comparison with IESAttachmentsController

| Aspect | IESAttachmentsController (current) | IIESAttachmentsController |
|--------|-----------------------------------|----------------------------|
| store() presence check | **Yes** — hasAnyFile; skip and return success if no files | **Yes** — hasAnyFile; skip and return “skipped (no files present)” if no files |
| update() presence check | **Yes** — hasAnyFile; skip and return success if no files | **No** — no check; handler always called |
| update() delegates to store()? | No | No |
| Handler | ProjectAttachmentHandler | Same |
| Transaction in store/update | Yes (wraps handler) | No (no DB::beginTransaction in store/update) |
| destroy() | firstOrFail, deleteDirectory, delete, transaction | firstOrFail, deleteDirectory, delete, transaction |
| Empty parent on no files (store) | No (guard) | No (guard) |
| Empty parent on no files (update) | No (guard) | **Yes** (no guard) |

**Structural similarity:** Same handler, same updateOrCreate pattern, same IIES_FIELDS-style config, same JSON responses. Both have store() guarded.  
**Structural difference:** IIES **update()** has no presence check; IES update() has one. IIES uses no transaction in store/update; IES uses a transaction when the handler runs.

**Corruption risk:**  
- **IES:** LOW (both store and update guarded).  
- **IIES:** **MEDIUM** — store is guarded; update is not, so empty parent creation/update is possible when update() is called with no files.

---

## PHASE 4 — EDGE BEHAVIOUR

### 4.1 Budget lock handling

- **Returns 403 JSON?** Not present in this controller. No budget lock or BudgetSyncGuard in IIESAttachmentsController.
- **Throws HttpResponseException?** No. Validation failures throw `ValidationException`; other errors are rethrown or logged.

### 4.2 destroy()

- **firstOrFail()?** **Yes.** `ProjectIIESAttachments::where('project_id', $projectId)->firstOrFail()`. If no record exists, ModelNotFoundException is thrown and the catch returns 500 JSON. So it is **not** a no-op when not found; it throws and returns an error response.
- **No-op if not found?** **No.**

### 4.3 Transaction usage

- **store():** **No** `DB::beginTransaction()`. Handler is called without a controller-level transaction.
- **update():** **No** `DB::beginTransaction()`.
- **destroy():** **Yes** — `DB::beginTransaction()`, commit on success, rollBack in catch.

So: delete + create (in handler) in store/update run **without** a controller transaction in IIES; destroy() is wrapped in a transaction.

---

## STRUCTURAL SUMMARY

- **store():** Exists; has presence check over IIES_FIELDS; skips handler and returns 200 when no files; otherwise calls handler; JSON responses; no transaction; no delete.
- **update():** Exists; does not delegate to store(); **no** presence check; always calls handler; handler can create/update empty parent when no files; JSON responses; no transaction; no delete.
- **destroy():** Exists; firstOrFail, deleteDirectory, parent delete, transaction, JSON.
- **Field keys:** Constant IIES_FIELDS; config via iiesFieldConfig().
- **Validation:** FormRequest; handler returns success/errors; controller throws ValidationException on failure.

---

## MUTATION FLOW SUMMARY

- **store():** Check hasAnyFile → if false: log, return 200, no handler. If true: call handler (updateOrCreate + add file rows for present fields). No delete.
- **update():** No presence check. Call handler (updateOrCreate + add file rows for present fields). When no files, parent is still created/updated with no file rows. No delete.
- **destroy():** Transaction; firstOrFail; delete storage directory; delete parent (cascade removes child file rows); commit; 200.

---

## DATA LOSS RISK ASSESSMENT

- **Deletion of existing attachments by store/update:** None. No delete in store/update.
- **Empty parent creation:**  
  - **store():** Prevented by presence guard.  
  - **update():** Possible. Handler is always called; updateOrCreate runs; with no files, parent exists with no new file rows (and may be a new empty shell if no parent existed).
- **Corruption of existing file data:** Handler does not delete or replace existing file rows in the normal flow; risk is limited to empty/shell parent records on update with no files.

**Overall data-loss risk for IIES:** **MEDIUM** — driven by update() allowing empty parent creation when no files are sent. store() is already safe.

---

## BEHAVIOUR COMPARISON WITH IESAttachmentsController

- **Same:** Both use ProjectAttachmentHandler, same updateOrCreate pattern, constant-based field list, FormRequest, JSON responses. Both have a presence guard in **store()**.
- **Different:**  
  - IES has a presence guard in **update()**; IIES does not.  
  - IES wraps handler in DB::transaction in store/update; IIES does not.  
  - IIES store() returns a distinct message when skipping (“IIES attachments skipped (no files present)”); IES returns the same success message when skipping.

So: **structurally similar**, with the important difference that **IIES update() lacks the skip-empty guard** that IES update() has, and IIES does not use a transaction around the handler.

---

## FINAL VERDICT

**STRUCTURALLY DIFFERENT**

- Same handler and overall pattern, but:
  - **IIES update()** has no presence check and can create/update an empty parent when no files are sent.
  - **IIES** does not use a controller-level transaction in store/update; IES does when the handler runs.
  - Guard coverage differs: IES has guard on both store and update; IIES has guard on store only.

So the two controllers are not structurally identical; the missing guard on IIES update() is the main behavioural gap for M1 (Data Integrity Shield).

---

## RECOMMENDATION

**Guard required.**

- **store():** Already has a presence check; no change required for the “skip when no files” behaviour.
- **update():** Should add the same presence check before calling the handler. If no file is present for any IIES field, log and return the same success JSON (e.g. “IIES attachments updated successfully.”) without calling the handler, so that an empty parent is not created or updated when the user submits no files. This aligns IIES with IES and with M1 skip-empty intent.

No code was modified in this verification. This is analysis-only.

---

**NO CODE WAS MODIFIED. THIS WAS A READ-ONLY ARCHITECTURE VERIFICATION.**
