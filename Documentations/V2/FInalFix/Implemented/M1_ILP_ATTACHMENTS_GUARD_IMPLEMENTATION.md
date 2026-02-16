# M1 — ILP Attached Documents Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Skip-Empty-Files)

**Controller:** `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` ONLY.

---

## 1. Summary of change

A **skip-empty-files guard** was added to `AttachedDocumentsController::store()` and `::update()` so that `ProjectAttachmentHandler::handle()` is called only when at least one ILP file field is present in the request. This prevents creating or updating an empty parent row in `project_ILP_attached_docs` when no files are uploaded.

- **Before:** store() and update() always called the handler. The handler runs `updateOrCreate(['project_id' => $projectId], [])`, so when no files were sent, a parent row was still created or updated with no file data.
- **After:** After the project existence check (store) or at the start of the try block (update), the controller checks `hasAnyILPFile($request)`. If false, it logs, commits the already-started transaction, and returns 200 with the specified success message without calling the handler. When at least one file is present, behaviour is unchanged: handler runs, validation, commit, and success response as before.

---

## 2. Before vs After behaviour table

| Scenario | Before | After |
|----------|--------|--------|
| store() with no files | Handler called; parent created/updated with no file data. | Guard: log, DB::commit(), return 200 "ILP attached documents saved successfully."; handler not called. |
| store() with at least one file | Handler called; parent + file rows created; 200. | **Same.** |
| update() with no files | Handler called; parent created/updated with no file data. | Guard: log, DB::commit(), return 200 "ILP attached documents updated successfully."; handler not called. |
| update() with at least one file | Handler called; parent + file rows updated; 200. | **Same.** |
| destroy() | firstOrFail, deleteAttachments, delete, commit, 200. | **Unchanged.** |

---

## 3. Code diff snippet

**New import:** `use Illuminate\Http\Request;`

**store() — after project existence check, before handler:**

```php
            if (! $this->hasAnyILPFile($request)) {
                Log::info('ILPAttachedDocumentsController@store - No files present; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'ILP attached documents saved successfully.'
                ], 200);
            }
```

**update() — before handler:**

```php
            if (! $this->hasAnyILPFile($request)) {
                Log::info('ILPAttachedDocumentsController@update - No files present; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'ILP attached documents updated successfully.'
                ], 200);
            }
```

**New private method (end of class):**

```php
    private function hasAnyILPFile(Request $request): bool
    {
        foreach (self::ILP_FIELDS as $field) {
            if ($request->hasFile($field)) {
                return true;
            }
        }
        return false;
    }
```

---

## 4. Manual test cases

1. **store() with no files** — POST with no ILP file fields. Expect: 200, message "ILP attached documents saved successfully."; log "No files present; skipping mutation"; no row created in project_ILP_attached_docs (or existing unchanged).
2. **store() with one file** — POST with at least one of aadhar_doc, request_letter_doc, purchase_quotation_doc, other_doc. Expect: handler runs, commit, 200 "Attached Documents saved successfully." (unchanged success path).
3. **update() with no files** — PUT/PATCH with no ILP file fields. Expect: 200, message "ILP attached documents updated successfully."; log skip; no handler call; no empty parent created/updated.
4. **update() with one file** — PUT/PATCH with at least one file. Expect: handler runs, 200 "Attached Documents updated successfully." (unchanged).
5. **destroy()** — Unchanged: firstOrFail, deleteAttachments, delete, commit, 200.

---

## 5. Risk assessment

| Risk | Mitigation |
|------|------------|
| Legitimate upload incorrectly skipped | Guard uses same ILP_FIELDS as handler; if any of the four fields has a file, handler runs. |
| Transaction left open on skip | Skip path calls DB::commit() before return (transaction was already started). |
| Response format change | Skip returns same JSON shape (message, 200); only message text differs for skip vs normal success path. |
| destroy() or handler changed | destroy() and ProjectAttachmentHandler were not modified. |

---

## 6. Confirmation

- **Only this controller was modified:** `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php`.
- **No other files were changed:** ProjectAttachmentHandler, FormRequests, routes, and all other controllers were not modified.

---

*End of M1 ILP Attachments Guard Implementation.*
