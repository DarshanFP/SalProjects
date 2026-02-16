# M1 — IAH Documents Guard Implementation

**Date:** 2026-02-14  
**Milestone:** M1 — Data Integrity Shield (Attachment Guard)  
**Controller:** `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` ONLY.

---

## 1. Before vs After

| Scenario | Before | After |
|----------|--------|--------|
| store() with no files | Handler called; updateOrCreate created/updated parent with no file data. | Guard returns 200 with message + documents: null; DB::commit(); handler not called; no parent created/updated. |
| store() with at least one file | Handler called; parent + file rows created/updated. | **Same.** |
| update() with no files | Handler called; parent created/updated with no file data. | Guard returns 200 with message + documents: null; DB::commit(); handler not called; no mutation. |
| update() with at least one file | Handler called; parent + file rows updated. | **Same.** |
| destroy() | firstOrFail, deleteDirectory, delete, commit, 200. | **Unchanged.** |

---

## 2. Exact behaviour summary

- **File fields:** Derived from `self::iahFieldConfig()` (keys: `aadhar_copy`, `request_letter`, `medical_reports`, `other_docs`).
- **store():** After project existence check, `hasAnyIAHFile($request)` is checked. If false: log "No files uploaded; skipping mutation", commit the transaction, return JSON `message` + `documents` => null, 200. If true: call handler as before; transaction, validation, and success/error responses unchanged.
- **update():** At start of try block, same guard. If no files: log, commit, return 200 with message + documents null. If files present: handler called; behaviour unchanged.
- **Transactions:** beginTransaction / commit / rollBack unchanged. When guard runs, commit is called before returning so the transaction is closed cleanly.
- **Response format:** JSON. Success still returns `message` and `documents` (null when skipping). Error 422/500 unchanged. Message text unchanged.
- **destroy():** Not modified.

---

## 3. Code diff snippet

**New import:** `use Illuminate\Http\Request;`

**store() — after project existence check, before handler:**

```php
            // M1 Data Integrity Shield: skip when no files uploaded.
            if (! $this->hasAnyIAHFile($request)) {
                Log::info('IAHDocumentsController@store - No files uploaded; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'IAH documents stored successfully.',
                    'documents' => null,
                ], 200);
            }
```

**update() — at start of try, before handler:**

```php
            // M1 Data Integrity Shield: skip when no files uploaded.
            if (! $this->hasAnyIAHFile($request)) {
                Log::info('IAHDocumentsController@update - No files uploaded; skipping mutation', [
                    'project_id' => $projectId,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'IAH documents updated successfully.',
                    'documents' => null,
                ], 200);
            }
```

**New private method (end of class):**

```php
    private function hasAnyIAHFile(Request $request): bool
    {
        $fileFields = array_keys(self::iahFieldConfig());

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                return true;
            }
        }

        return false;
    }
```

---

## 4. Manual test cases

1. **store() with no files** — POST with no IAH file fields. Expect: 200, `message` "IAH documents stored successfully.", `documents` null. Log: "No files uploaded; skipping mutation". No row in project_IAH_documents (or existing row unchanged).
2. **store() with one file** — POST with at least one of aadhar_copy, request_letter, medical_reports, other_docs. Expect: handler runs, transaction commit, 200, message + documents (attachment record). Parent and file rows created/updated as before.
3. **update() with no files** — PUT/PATCH with no IAH file fields. Expect: 200, message "IAH documents updated successfully.", documents null. Log: "No files uploaded; skipping mutation". No new parent row; existing data unchanged.
4. **update() with one file** — PUT/PATCH with at least one file. Expect: handler runs, 200, message + documents. Behaviour unchanged from before.
5. **destroy()** — DELETE. Expect: unchanged (firstOrFail, deleteDirectory, delete, commit, 200).

---

## 5. Confirmation

- **Only this controller was modified:** `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php`.
- **No other files were changed:** ProjectAttachmentHandler, FormRequests, routes, models, and all other controllers were not modified.

---

*End of M1 IAH Attachments Guard Implementation.*
