# WAVE 7 — COMMON PROJECT ATTACHMENTS IMPLEMENTATION

**Date:** 2026-02-17.  
**Scope:** Common/generic project attachments only (non–IES/IIES/IAH/ILP). Read hardening, per-file delete, storage cleanup, UI.

---

## 1. EXECUTIVE SUMMARY

Common project attachments (used for Development Projects, CCI, RST, Edu-RUT, IGE, LDP, CIC, etc.) were brought to the same security level as IES, IIES, IAH, and ILP. A **guarded view route** (province + canView) was added; the existing **download** method was **hardened** with the same guards; **per-file delete** (province + isEditable + canEdit) and a DELETE route were added; the **Edit** Blade now uses route-based View/Download links and a **Delete** button with DOM removal; the **Show** Blade uses the view route for View. **Storage cleanup** on delete is implemented via a `deleting` event on the `ProjectAttachment` model so that the file on disk is removed when a row is deleted. No IES/IIES/IAH/ILP code was modified; no storage config or approval lifecycle change.

---

## 2. READ HARDENING IMPLEMENTATION

- **viewAttachment($id):** New method. Loads `ProjectAttachment::findOrFail($id)`, resolves `$project = $attachment->project`, then enforces `ProjectPermissionHelper::passesProvinceCheck($project, $user)` and `ProjectPermissionHelper::canView($project, $user)`; aborts 403 on failure. Checks `file_path` and file existence, then returns `response()->file(storage_path('app/public/' . $attachment->file_path))` for inline viewing. Route: GET `projects/attachments/view/{id}`, name `projects.attachments.view`.

- **downloadAttachment($id):** Hardened. After `findOrFail($id)`, resolves `$project = $attachment->project` and `$user = Auth::user()`, then adds the same province and canView checks; 403 on failure. Existing file-existence check and `Storage::disk('public')->download(...)` logic unchanged. Route name and response type unchanged.

---

## 3. DELETE BACKEND IMPLEMENTATION

- **destroyAttachment($id):** New method. Loads attachment, resolves project; returns 404 if no project. Enforces `passesProvinceCheck`, `ProjectStatus::isEditable($project->status)`, and `ProjectPermissionHelper::canEdit($project, $user)`; aborts 403 on failure. Calls `$attachment->delete()` (model `deleting` event then removes the file from storage). Returns JSON `{ success: true, message: 'Attachment deleted successfully.' }`. Route: DELETE `projects/attachments/files/{id}`, name `projects.attachments.files.destroy`.

---

## 4. STORAGE CLEANUP VIA MODEL EVENT

- In `App\Models\OldProjects\ProjectAttachment`, added `boot()` with a `deleting` event that checks `$attachment->file_path` and `Storage::disk('public')->exists($attachment->file_path)`, then calls `Storage::disk('public')->delete($attachment->file_path)`. This prevents orphaned files when an attachment row is deleted (by the new per-file delete or any future delete path).

---

## 5. ROUTES ADDED

| Method | URI | Name |
|--------|-----|------|
| GET | `/projects/attachments/view/{id}` | `projects.attachments.view` |
| DELETE | `/projects/attachments/files/{id}` | `projects.attachments.files.destroy` |

Existing route unchanged: GET `/projects/attachments/download/{id}` → `projects.attachments.download`. All are in the same middleware group (auth, role: executor, applicant, provincial, coordinator, general).

---

## 6. BLADE CHANGES

- **Edit** (`resources/views/projects/partials/Edit/attachment.blade.php`):
  - Replaced View link from `asset('storage/' . $attachment->file_path)` to `route('projects.attachments.view', $attachment->id)`.
  - Added `id="common-attachment-{{ $attachment->id }}"` on the wrapper div for each attachment row.
  - Added Delete button calling `confirmRemoveCommonAttachment({{ $attachment->id }}, {{ json_encode($attachment->file_name) }})`.
  - Added script: `confirmRemoveCommonAttachment()` and `removeCommonAttachment()` using `fetch()` to the destroy route (DELETE, CSRF, JSON); on success removes the element `#common-attachment-{id}` from the DOM.

- **Show** (`resources/views/projects/partials/Show/attachments.blade.php`):
  - Replaced View link from `asset('storage/' . $attachment->file_path)` to `route('projects.attachments.view', $attachment->id)`. No Delete button.

---

## 7. GUARD CHAINS (READ VS MUTATION)

| Action | Guards |
|--------|--------|
| **View** | Resolve project → passesProvinceCheck → canView → stream file. |
| **Download** | Same as View. |
| **Delete** | Resolve project → passesProvinceCheck → ProjectStatus::isEditable → canEdit → delete row (model event removes storage). |

---

## 8. VALIDATION RESULTS

To be confirmed manually:

- View works for authorized users; 403 for wrong province or no canView.
- Download works with same authorization.
- Cross-province access blocked (403).
- Delete blocked for approved (non-editable) projects (403).
- Delete works for editable projects with canEdit; DOM row removed on success.
- No direct `asset('storage/...')` for common attachment View links in Edit/Show.
- `php artisan route:list` shows the new view and destroy routes.
- When an attachment is deleted, the file is removed from `storage/app/public` (model deleting event).

---

## 9. RISK REDUCTION SUMMARY

- **Before:** Download was IDOR-prone (no project/province/canView); View used direct public URL; no per-file delete; storage could be orphaned if rows were ever deleted elsewhere.
- **After:** View and Download are route-based and guarded (province + canView); per-file delete is guarded (province + isEditable + canEdit); storage is cleaned on delete via model event. Legacy public URLs remain valid if already stored; new links use routes only.

---

## 10. CONFIRMATION NO OTHER CONTROLLERS MODIFIED

- Only `app/Http/Controllers/Projects/AttachmentController.php` was modified (common attachments). IES, IIES, IAH, and ILP controllers and their routes/Blade were not changed. No AttachmentHandler, storage config, or approval lifecycle change.
