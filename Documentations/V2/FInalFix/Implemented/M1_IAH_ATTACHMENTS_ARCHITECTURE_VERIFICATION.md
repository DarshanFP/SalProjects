# M1 — IAH Documents Architecture Verification

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Sections)  
**Controller path:** `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php`  
**Mode:** Read-only architectural verification. No code was modified.

---

## 1. Objective checklist

| # | Question | Answer |
|---|----------|--------|
| 1 | Calls ProjectAttachmentHandler::handle() in store() and/or update() **without** checking for file presence first? | **Yes.** Both store() and update() call the handler with no `hasFile()` or `hasAnyFile()` check before the call. |
| 2 | Uses hasFile() or hasAnyFile() guard before calling the handler? | **No.** There is no presence check in either store() or update(). |
| 3 | Always executes updateOrCreate through the handler even when no file is uploaded? | **Yes.** The handler is invoked unconditionally (subject only to project existence in store()). The handler performs `updateOrCreate(['project_id' => $projectId], [])` at the start, so it runs even when no files are present. |
| 4 | Wraps store/update in DB::transaction? | **Yes.** store(): DB::beginTransaction() at line 37, commit/rollBack in try/catch. update(): DB::beginTransaction() at line 161, commit/rollBack in try/catch. |
| 5 | Delegates update() to store()? | **No.** update() is a separate method and does not call store(). It calls ProjectAttachmentHandler::handle() directly. |
| 6 | Deletes any existing parent record before file check? | **No.** Neither store() nor update() deletes the parent. Only destroy() deletes (firstOrFail then delete). |
| 7 | Can create a parent attachment row when no file is uploaded? | **Yes.** When no file is uploaded, the handler is still called, runs updateOrCreate, and creates or updates the parent row with no file data (and no child file rows). |

---

## 2. Step-by-step flow of store()

| Step | Line(s) | Action |
|------|---------|--------|
| 1 | 34–36 | Log::info('IAHDocumentsController@store - Start', ['project_id' => $projectId]). |
| 2 | 37 | DB::beginTransaction(). |
| 3 | 38–41 | try block: if Project::where('project_id', $projectId)->exists() is false, return response()->json(['error' => 'Project not found.'], 404). |
| 4 | 44–48 | **Call ProjectAttachmentHandler::handle()** with $request, $projectId, AttachmentContext::forIAH(), self::iahFieldConfig(). **No hasFile/hasAnyFile check before this.** |
| 5 | 50–60 | If !$result->success: DB::rollBack(), Log::warning, return response()->json(['error' => 'Failed to store IAH documents.', 'errors' => ...], 422). |
| 6 | 62–66 | DB::commit(), Log::info('IAHDocumentsController@store - Success', ...). |
| 7 | 69–72 | return response()->json(['message' => 'IAH documents stored successfully.', 'documents' => $result->attachmentRecord], 200). |
| 8 | 74–80 | catch (\Throwable): DB::rollBack(), Log::error, return response()->json(['error' => 'Failed to store IAH documents.'], 500). |

**Handler call:** Lines **44–48**. Invoked whenever the project exists; there is no check for file presence before this call.

---

## 3. Step-by-step flow of update()

| Step | Line(s) | Action |
|------|---------|--------|
| 1 | 156–158 | Log::info('IAHDocumentsController@update - Start', ['project_id' => $projectId]). |
| 2 | 161 | DB::beginTransaction(). |
| 3 | 162–168 | try block: **Call ProjectAttachmentHandler::handle()** with $request, $projectId, AttachmentContext::forIAH(), self::iahFieldConfig(). **No hasFile/hasAnyFile check before this.** |
| 4 | 170–180 | If !$result->success: DB::rollBack(), Log::warning, return response()->json(['error' => 'Failed to update IAH documents.', 'errors' => ...], 422). |
| 5 | 182–186 | DB::commit(), Log::info('IAHDocumentsController@update - Success', ...). |
| 6 | 188–191 | return response()->json(['message' => 'IAH documents updated successfully.', 'documents' => $result->attachmentRecord], 200). |
| 7 | 203–200 | catch (\Throwable): DB::rollBack(), Log::error, return response()->json(['error' => 'Failed to update IAH documents.'], 500). |

**Handler call:** Lines **163–168**. Invoked on every update(); there is no check for file presence before this call.

---

## 4. Structured findings

| Finding | Detail |
|---------|--------|
| **Handler called unconditionally?** | **Yes.** In store(), the only condition before the handler is that the project exists (404 otherwise). In update(), there is no condition; the handler is always called. |
| **Empty DB rows can currently be created?** | **Yes.** When the request contains no files, the handler still runs. It performs updateOrCreate on the parent model (project_IAH_documents), then processes only fields that have files. With no files, the parent row is created or updated with no file columns and no child file rows (empty/shell record). |
| **Exact line numbers where handler is called** | **store():** lines **44–48**. **update():** lines **163–168**. |
| **Response format** | **JSON only.** Success: 200 with `message` and `documents` (store) or `message` and `documents` (update). Error: 404, 422, or 500 with `error` (and optionally `errors`). No redirects in store/update. |
| **Current success message (store)** | `'IAH documents stored successfully.'` |
| **Current success message (update)** | `'IAH documents updated successfully.'` |
| **Transaction** | Both store() and update() use DB::beginTransaction(), DB::commit() in try, and DB::rollBack() on failure or in catch. |
| **Delete before file check** | Neither store() nor update() deletes the parent. destroy() (lines 205–234) is the only method that deletes; it uses firstOrFail() then Storage::deleteDirectory and $documents->delete(). |

---

## 5. Final verdict

**CRITICAL**

The controller calls `ProjectAttachmentHandler::handle()` in both store() and update() with **no** file-presence check. The handler always runs `updateOrCreate(['project_id' => $projectId], [])`, so a parent row in `project_IAH_documents` can be created or updated even when no file is uploaded. This leads to empty/shell parent records and matches the high rate of incomplete rows observed in the IAH documents table in prior audits. Risk is **CRITICAL** for data integrity (empty row creation); there is no accidental deletion of existing attachments in store/update, but the absence of a skip-empty guard is the critical issue.

---

**NO CODE WAS MODIFIED. THIS WAS A READ-ONLY AUDIT.**
