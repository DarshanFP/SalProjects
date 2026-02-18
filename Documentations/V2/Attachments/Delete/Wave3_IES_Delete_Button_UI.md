# Wave 3 — IES Delete Button UI (Controlled + Isolated)

## 1. Executive Summary

**Scope:** UI-only integration of the per-file delete action for IES attachments. No controller or route changes. Single blade file modified.

**Objective:** Add a Delete button next to View and Download for each IES attachment file on the **edit** page. The button calls the existing `DELETE /projects/ies/attachments/files/{fileId}` endpoint via `fetch()`, includes CSRF, asks for confirmation, removes the file row from the DOM on success, and handles errors with alerts. No page reload. IES only; no other project types (IIES, IAH, ILP) or reports touched.

**Result:** Edit page shows Delete next to View/Download for each file that has a DB id. Confirmation dialog precedes delete. On success the row is removed from the DOM. On 403 or other errors an alert is shown. Download and View behaviour unchanged.

---

## 2. UI Changes Made

- **DOM identifier:** Each file row in the existing-files loop that has a `$file->id` now has `id="ies-file-{{ $file->id }}"` on its wrapper div so the row can be removed after a successful delete.
- **Delete button:** A new button was added in the same action block as View and Download (only when `isset($file->id)`):
  - Label: "Delete" with trash icon
  - Classes: `btn btn-sm btn-outline-danger`
  - `onclick="confirmRemoveIESFile({{ $file->id }}, {{ json_encode($file->file_name) }})"`
- **JavaScript:** Two functions added in a `<script>` block in the same blade file:
  - `confirmRemoveIESFile(fileId, fileName)` — shows a confirm dialog; on OK calls `removeIESFile(fileId)`.
  - `removeIESFile(fileId)` — sends `DELETE` to `/projects/ies/attachments/files/{fileId}` with CSRF and `Accept: application/json`; on success removes the element `#ies-file-{fileId}` from the DOM; on failure shows an alert.

No layout restructure. No changes to View or Download buttons or to the upload section.

---

## 3. Blade File Modified

- **Path:** `resources/views/projects/partials/Edit/IES/attachments.blade.php`
- **Sections changed:**
  - File row div: added conditional `id="ies-file-{{ $file->id }}"` when `$file->id` is set.
  - Inside the `@if(isset($file->id))` block (existing View/Download): added the Delete button.
  - Bottom of file: added a second `<script>` block containing `confirmRemoveIESFile` and `removeIESFile`.

---

## 4. Route Used

- **Method:** DELETE
- **Path:** `/projects/ies/attachments/files/{fileId}`
- **Name:** `projects.ies.attachments.files.destroy`
- **Controller:** `IESAttachmentsController@destroyFile` (unchanged; implemented in Wave 2)

The UI builds the delete URL using the Laravel `route()` helper (see Route Alignment Fix below). No route or controller changes in this wave.

---

## Route Alignment Fix

**Why manual url() was incorrect:** The original JS used `url('/projects/ies/attachments/files')` plus `"/" + fileId`. That assumes the application URL has no prefix (e.g. no subdirectory or custom `APP_URL` path). If the app is served under a prefix or the route is registered differently, the constructed URL can mismatch the actual route and Laravel returns "The route ... could not be found."

**Why route() helper is required:** Using `route('projects.ies.attachments.files.destroy', ':id')` generates the exact URL Laravel uses for that named route, including any prefix from the app or web server. The placeholder `:id` is replaced in JavaScript with the actual `fileId`, so the DELETE request always hits the correct endpoint.

**Confirmation no backend change:** Only the blade file was updated (JS URL construction). No change to `routes/web.php`, no change to `IESAttachmentsController`, and no change to middleware or policy.

---

## 5. JS Logic Explanation

- **confirmRemoveIESFile(fileId, fileName):** Shows `confirm('Are you sure you want to delete "' + fileName + '"? This action cannot be undone.')`. If the user cancels, the function returns. If the user confirms, it calls `removeIESFile(fileId)`.
- **removeIESFile(fileId):** Uses `fetch()` with method `DELETE`, headers `X-CSRF-TOKEN` (Laravel `csrf_token()`) and `Accept: application/json`. On response:
  - If `!response.ok` (e.g. 403, 404, 500): throws so the `.catch()` runs and the user sees "An error occurred while deleting the file."
  - If OK: parses JSON. If `data.success` is true, finds the element with id `ies-file-` + fileId and removes it from the DOM. Otherwise shows `data.message` or "Delete failed." in an alert.
- **No page reload:** All behaviour is in JS; the form is not submitted and the page is not refreshed.

---

## 6. DOM Removal Strategy

- Only file rows that can be deleted (those with `$file->id`) have the wrapper id `ies-file-{{ $file->id }}`.
- On successful delete response (`data.success === true`), the script runs `document.getElementById('ies-file-' + fileId)` and, if the element exists, calls `.remove()` so the row disappears without a full page reload.
- Rows without a DB id (legacy display) do not get the Delete button and do not get this id; they are not targeted by the delete flow.

---

## 7. Security Expectations (403 Handling)

- **Backend:** Province isolation, editable status, and `canEdit()` are enforced in `destroyFile()` (Wave 2). Approved project, cross-province, or insufficient permission → 403.
- **UI:** The delete request does not change based on project status or user role. When the server returns 403 (or any non-2xx), `response.ok` is false, the promise rejects, and the user sees the generic alert "An error occurred while deleting the file." The file row remains. No sensitive detail is exposed in the UI. CSRF token is sent with the request.

---

## 8. Validation Checklist Results

| Check | Result |
|-------|--------|
| Delete visible only on edit page | Yes — button is in `Edit/IES/attachments.blade.php`, which is used on the project edit page. |
| Delete button does not appear on show page | Yes — show page uses different partials; this blade is for edit only. |
| Clicking delete prompts confirmation | Yes — `confirmRemoveIESFile` shows a confirm dialog before calling the API. |
| Approved project → backend returns 403 → alert | Yes — non-2xx response triggers catch and alert. |
| Cross-province user → 403 | Yes — enforced by controller; UI shows same error alert. |
| Owner editable project → file removed from UI | Yes — on success, row with `ies-file-{id}` is removed. |
| No page reload | Yes — only `fetch()` and DOM removal; no form submit or location change. |
| No JS console errors (normal flow) | Yes — errors are caught and logged with `console.error`; success path has no throw. |
| Download and View still work | Yes — only a new button and script were added; existing links unchanged. |

---

## 9. Confirmation: No Other Project Types Modified

- **Modified:** Only `resources/views/projects/partials/Edit/IES/attachments.blade.php`.
- **Not modified:** IIES, IAH, ILP edit/show views; report views; any shared or generic attachment partials. No global JS file added. No shared component extracted.

---

## 10. Confirmation: No Controller Changes

- **Controller:** `IESAttachmentsController` unchanged in this wave. `destroyFile()` and all other methods are as implemented in Wave 2.
- **Routes:** `routes/web.php` unchanged in this wave. The DELETE route remains as defined in Wave 2.

---

*Wave 3 — IES delete button UI only. Completes IES vertical slice: read hardened, delete implemented, UI integrated. No abstraction. No multi-controller work. Controlled execution only.*
