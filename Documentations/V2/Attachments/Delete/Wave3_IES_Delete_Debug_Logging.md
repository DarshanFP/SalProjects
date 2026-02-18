# Wave 3.1 — Temporary Debug Logging for IES Per-File Delete

## 1. Why Debugging Layer Added

The frontend was receiving a generic delete error ("An error occurred while deleting the file.") with no indication of whether the failure was due to a guard (403), a missing file (404), or a server exception (500). This temporary instrumentation was added to:

- Identify which guard (province, editable status, or canEdit) is failing when delete is blocked.
- Log file id, user id, and project id at attempt, block, and success.
- Surface the server’s JSON error message in the UI so the user sees "Forbidden." or "Server error during delete." instead of a generic message.
- Capture unexpected exceptions with message and trace in the log for root-cause analysis.

**Intent:** Diagnostic only. No change to permission policy, guard logic, or routes. To be removed after the issue is fixed.

---

## 2. Logs Added (Info, Warning, Error)

| Level   | Log key / context              | When |
|--------|---------------------------------|------|
| **Info**  | `IES Delete Attempt`            | Start of `destroyFile()`: `file_id`, `user_id`. |
| **Info**  | `IES Delete Success`            | After `$file->delete()`: `file_id`, `project_id`, `user_id`. |
| **Warning** | `IES Delete Blocked - province_mismatch` | When `passesProvinceCheck` fails. |
| **Warning** | `IES Delete Blocked - not_editable`       | When `ProjectStatus::isEditable` is false. |
| **Warning** | `IES Delete Blocked - cannot_edit`       | When `ProjectPermissionHelper::canEdit` is false. |
| **Error**   | `IES Delete Exception`         | In `catch (\Exception $e)`: `file_id`, `user_id`, `message`, `trace`. |

All of these are written to the default Laravel log (e.g. `storage/logs/laravel.log`).

---

## 3. Guard Reasons Logged

Each 403 is returned only after a dedicated warning log with a **reason** so logs can be filtered:

- **province_mismatch** — User’s province does not match project’s province.
- **not_editable** — Project status is not editable (e.g. approved).
- **cannot_edit** — User does not have edit permission (role/ownership).

403 responses now return JSON: `{ "success": false, "message": "Forbidden." }` instead of calling `abort(403)`, so the frontend can show the same message and the log still records the reason.

---

## 4. Temporary Frontend Change

In `removeIESFile()` in `resources/views/projects/partials/Edit/IES/attachments.blade.php`:

- **Before:** On `!response.ok` the code threw a generic `Error('Delete failed.')` and the catch showed "An error occurred while deleting the file."
- **After:** The handler parses JSON first with `response.json().then(data => { ... })`. If `!response.ok`, it throws `new Error(data.message || 'Delete failed.')`. The `.catch()` shows `alert(error.message)`.

**Effect:** The user sees the server’s `message` when present (e.g. "Forbidden." or "Server error during delete."). No other JS was modified.

---

## 5. Where to Check Logs

- **Path:** `storage/logs/laravel.log`
- **Search for:** `IES Delete Attempt`, `IES Delete Blocked`, `IES Delete Success`, `IES Delete Exception` to see attempts, which guard failed, successes, and unexpected errors.

---

## 6. Instruction: REMOVE DEBUG LOGGING AFTER ISSUE FIXED

After the root cause is identified and fixed:

1. **Controller:** Remove the temporary logging and restore the previous behaviour:
   - Remove `Log::info('IES Delete Attempt', ...)` at the start of the try.
   - Replace each guard’s `Log::warning(...)` + `return response()->json(..., 403)` with a single `abort(403)` again.
   - Remove `Log::info('IES Delete Success', ...)` after `$file->delete()`.
   - In the `catch (\Exception $e)`, remove the detailed `Log::error('IES Delete Exception', ...)` (or keep a minimal one-line log if desired) and restore the previous 500 JSON response if needed.
2. **Frontend (optional):** Either keep `alert(error.message)` so server messages still surface, or revert to the previous generic error message.
3. **This doc:** Keep for reference; mark as "superseded" or "debug layer removed" once cleanup is done.

Do not leave verbose debug logs in the production code path long term.

---

## 7. Confirmation: No Logic Changed

- **Guard logic:** Unchanged. Same checks (province, editable status, canEdit) in the same order; only the action on failure changed from `abort(403)` to log + JSON 403.
- **Route:** Unchanged.
- **Permission policy:** Unchanged. No new roles or rules.
- **Success path:** Unchanged (find file → resolve project → guards → delete → success JSON). Only logging added.

---

*Wave 3.1 — Temporary diagnostic instrumentation. IES controller and IES blade only. Remove after issue is fixed.*
