# M1 — IIES Attachments Guard Implementation (update only)

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield  
**Target:** `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php` — **update() only.**

---

## 1. Summary of Change

A **skip-empty (presence) guard** was added to **update()** so that `ProjectAttachmentHandler::handle()` is not called when no attachment files are present in the request. This prevents creating or updating an empty parent record (`project_IIES_attachments`) when the user submits no files on update.

- **Before:** update() always called the handler. The handler runs `updateOrCreate(['project_id' => $projectId], [])`, so when no files were sent, the parent was still created or updated with no file rows (empty/shell record).
- **After:** At the start of update(), the controller checks whether any IIES attachment field has a file (`$request->hasFile($field)` over `IIES_FIELDS`). If **no** files are present, it logs and returns 200 with `message` "IIES attachments updated successfully." without calling the handler. When at least one file is present, the existing flow is unchanged: handler runs, validation and JSON response (including `attachments`) are preserved.

**store()** and **destroy()** were not modified. No changes were made to `ProjectAttachmentHandler`, validation classes, transactions, or any other controller.

---

## 2. Before vs After Behaviour

| Scenario | Before | After |
|----------|--------|--------|
| update() with no files | Handler called; parent created/updated via updateOrCreate with no file rows. | Guard returns 200 with "IIES attachments updated successfully."; handler not called; no parent created/updated. |
| update() with at least one file | Handler called; parent + file rows updated; 200 with message + attachments. | **Same.** |
| store() | Unchanged (already had presence guard). | **Unchanged.** |
| destroy() | firstOrFail, deleteDirectory, delete, transaction, 200. | **Unchanged.** |

---

## 3. Code Snippet — Guard Insertion

**File:** `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`  
**Method:** `update()`

**Inserted at the beginning of update(), before the existing Log::info and try block:**

```php
    public function update(FormRequest $request, $projectId)
    {
        $hasAnyFile = collect(self::IIES_FIELDS)
            ->contains(fn ($field) => $request->hasFile($field));

        if (! $hasAnyFile) {
            Log::info('IIESAttachmentsController@update - No files present; skipping mutation', [
                'project_id' => $projectId,
            ]);
            return response()->json([
                'message' => 'IIES attachments updated successfully.'
            ], 200);
        }

        Log::info('IIESAttachmentsController@update - Start', [
            ...
        ]);
        try {
            $result = ProjectAttachmentHandler::handle(
            ...
```

---

## 4. Risk Assessment

| Risk | Mitigation |
|------|------------|
| Legitimate update with files incorrectly skipped | Guard uses same field list as handler (`IIES_FIELDS`). If any of the eight fields has a file, handler runs. |
| Response format | Skip path returns 200 with `message` only; normal success returns `message` + `attachments`. Client can treat both as success; skip path does not expose `attachments` (none created). |
| store() or destroy() behaviour | Only update() was edited; store() and destroy() were not touched. |
| Validation / error handling | Guard runs before try; validation and ValidationException behaviour unchanged when handler is called. |

---

## 5. Confirmation

- **Only update() was modified** in `IIESAttachmentsController`. store(), destroy(), show(), edit(), downloadFile(), viewFile() are unchanged.
- **No other files were changed.** No modifications to `ProjectAttachmentHandler`, FormRequests, routes, or any other controller.

---

*End of M1 IIES Attachments Guard Implementation.*
