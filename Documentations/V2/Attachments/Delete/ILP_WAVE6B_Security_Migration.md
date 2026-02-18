# WAVE 6B — ILP SECURITY MIGRATION

**Date:** 2026-02-17.  
**Scope:** Route-based read (view/download), per-file delete, Blade UI. ILP only; no IES/IIES/IAH/Reports/Generic changes. No storage config or .htaccess change.

---

## 1. EXECUTIVE SUMMARY

ILP attached documents were previously exposed via **direct public URLs** (`Storage::url($file->file_path)`), so anyone with the link could view or download files without going through the application. Wave 6B introduces **route-based streaming** for view and download, with **guard chains** (province isolation, canView/canEdit), and adds **per-file delete** with a guarded endpoint and Delete button in the edit UI. Blade now generates links using `route('projects.ilp.documents.view', $file->id)` and `route('projects.ilp.documents.download', $file->id)` instead of `Storage::url()`. Public storage remains in place; old URLs still work if already stored or shared (backward compatibility). No abstraction, no parent-cascade refactor, no changes to other attachment modules.

---

## 2. PREVIOUS PUBLIC URL EXPOSURE

- **Edit and Show** partials used `Storage::url($file->file_path)` for View and Download links.
- That produced URLs like `https://domain.com/storage/project_attachments/ILP/{projectId}/{filename}`, served directly by the web server with **no** Laravel middleware or authorization.
- Any user (or external party) with the URL could access the file; no province, canView, or project checks.

---

## 3. ROUTE-BASED READ INTRODUCTION

- **viewFile($fileId):** Resolves the file and its project (via `$file->project ?? $file->ilpDocument?->project`), runs **passesProvinceCheck** and **canView**, then streams the file with `response()->file($path)` for inline display.
- **downloadFile($fileId):** Same guard chain; returns `response()->download($path, $file->file_name)` for force-download.
- Both methods return 404 if the file record or physical file is missing, and 403 if province or canView fails. No direct URL is exposed in the UI; all read goes through these routes.

---

## 4. GUARD CHAINS (READ VS MUTATION)

| Action   | Guards applied |
|----------|------------------|
| **View** | Resolve project → **ProjectPermissionHelper::passesProvinceCheck** → **ProjectPermissionHelper::canView** → stream file. |
| **Download** | Same as View. |
| **Destroy (per-file)** | Resolve project → **passesProvinceCheck** → **ProjectStatus::isEditable** → **ProjectPermissionHelper::canEdit** → delete file (model `deleting` event removes storage). |

Read (view/download) is allowed for users who can view the project (e.g. approved projects). Mutation (delete) requires the project to be editable and the user to have canEdit.

---

## 5. ROUTES ADDED

All inside the same middleware group as IES/IAH/IIES (`auth`, `role:executor,applicant,provincial,coordinator,general`):

| Method | URI | Name |
|--------|-----|------|
| GET | `/projects/ilp/documents/view/{fileId}` | `projects.ilp.documents.view` |
| GET | `/projects/ilp/documents/download/{fileId}` | `projects.ilp.documents.download` |
| DELETE | `/projects/ilp/documents/files/{fileId}` | `projects.ilp.documents.files.destroy` |

---

## 6. BLADE CHANGES

- **Edit** (`resources/views/projects/partials/Edit/ILP/attached_docs.blade.php`):
  - Replaced `Storage::url($file->file_path)` with `route('projects.ilp.documents.view', $file->id)` for View and `route('projects.ilp.documents.download', $file->id)` for Download.
  - Added `id="ilp-file-{{ $file->id }}"` on the file row container.
  - Added Delete button calling `confirmRemoveILPFile({{ $file->id }}, {{ json_encode($file->file_name) }})`.
  - Added inline script: `confirmRemoveILPFile()` and `removeILPFile()` using `fetch()` to the destroy route with DELETE, CSRF token, and JSON; on success removes the row from the DOM.
- **Show** (`resources/views/projects/partials/Show/ILP/attached_docs.blade.php`):
  - Replaced `Storage::url($file->file_path)` with the same view and download routes. No Delete button (show is read-only).

No removal of public storage or change to `file_path`; only link generation was switched to routes.

---

## 7. DELETE IMPLEMENTATION

- **Controller:** `destroyFile($fileId)` loads the file, resolves project via `$file->project ?? $file->ilpDocument?->project`, applies province, isEditable, and canEdit, then `$file->delete()`. The model’s `deleting` event removes the file from `Storage::disk('public')`. Returns JSON `{ success: true, message: '...' }` or 403/404.
- **Blade:** Delete button (Edit only) with confirm dialog; JS calls the destroy route with method DELETE and CSRF, then removes the element `#ilp-file-{id}` on success.

---

## 8. BACKWARD COMPATIBILITY STRATEGY

- **Storage symlink and disk** are unchanged; files remain under `storage/app/public` and `public/storage`.
- **Existing public URLs** (e.g. old links in emails or DB) that point to `/storage/...` continue to work as long as the symlink exists; they are not broken.
- **New links** generated by the app use the guarded routes only; we no longer output `Storage::url()` for ILP in Edit/Show. So new usage is secure while old URLs remain valid.

---

## 9. VALIDATION RESULTS

To be confirmed manually:

- View works for authorized users; 403 for wrong province or no canView.
- Download works with same authorization.
- Cross-province access blocked (403).
- Delete blocked for approved (non-editable) projects (403).
- Delete works for editable projects with canEdit; DOM row removed on success.
- `php artisan route:list` shows the three new ILP document routes.
- No direct `Storage::url()` used in ILP Blade for file links.
- Old public URLs still accessible (backward compatibility).

---

## 10. NEXT PLANNED WAVE (CASCADE ALIGNMENT)

- Parent destroy (full ILP attached-documents record) and cleanup of `ProjectILPDocumentFile` rows / storage are **not** refactored in this wave. A future wave can align cascade or explicit cleanup when the parent document record is deleted (see Wave 6A audit: deleteAttachments() is legacy-only; file rows and storage for the multi-file table are not removed on parent delete). This migration is strictly route-based read + per-file delete + UI.
