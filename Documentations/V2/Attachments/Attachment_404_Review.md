# Attachment View/Download 404 Review

**Date:** 2026-02-16  
**Status:** Review Complete  
**Scope:** All project types and report attachments — View and Download link patterns across Show and Edit views.

---

## Executive Summary

Attachment View and Download links fail with **404 Not Found** on production when the Laravel storage symlink (`public/storage` → `storage/app/public`) is missing or broken. This review scans all attachment-related views and identifies which use direct URLs (symlink-dependent) vs route-based URLs (controller-streamed, symlink-independent).

---

## Root Cause: Symlink Dependency

| URL Pattern | Requires Symlink? | Behavior Without Symlink |
|-------------|-------------------|--------------------------|
| `Storage::url($path)` → `/storage/project_attachments/...` | Yes | 404 Not Found |
| `asset('storage/' . $path)` → `/storage/...` | Yes | 404 Not Found |
| Route-based: `route('projects.ies.attachments.view', $id)` | No | Controller streams file from storage |

**Fix:** Use dedicated download/view routes that stream files through controllers via `Storage::disk('public')->download()` and `Storage::disk('public')->get()`.

---

## Project Types: Attachment Status Matrix

| Project Type | Full Name | Model (Files) | Controller | Routes | View Link Pattern | Download Link Pattern | Status |
|--------------|-----------|---------------|------------|--------|-------------------|------------------------|--------|
| **IES** | Individual - Ongoing Educational Support | `ProjectIESAttachmentFile` | `IESAttachmentsController` | `projects.ies.attachments.view`, `projects.ies.attachments.download` | Route when `$file->id` exists; `Storage::url()` fallback for legacy | Same | ✅ Fixed (with legacy fallback) |
| **IIES** | Individual - Initial Educational Support | `ProjectIIESAttachmentFile` | `IIESAttachmentsController` | `projects.iies.attachments.view`, `projects.iies.attachments.download` | Route | Route | ✅ Fixed |
| **IAH** | Individual Assistance for Health | `ProjectIAHDocumentFile` | `IAHDocumentsController` | None | `Storage::url()` | `Storage::url()` | ❌ **Not Fixed** |
| **ILP** | Individual Livelihood Project | `ProjectILPDocumentFile` | `AttachedDocumentsController` | None | `Storage::url()` | `Storage::url()` | ❌ **Not Fixed** |
| **General** | Development Projects / General Attachments | `ProjectAttachment` | `AttachmentController` | `projects.attachments.download` | `asset('storage/' . $path)` | Route | ⚠️ **View 404 risk** |
| **Report** | Monthly Report Attachments | `ReportAttachment` | `ReportAttachmentController` | `reports.attachments.download` | `asset('storage/' . $path)` | Route | ⚠️ **View 404 risk** |

---

## Detailed Findings by Project Type

### 1. IES (Individual - Ongoing Educational Support)

| View | View Link | Download Link | Notes |
|------|-----------|---------------|-------|
| `Show/IES/attachments.blade.php` | Route when `isset($file->id)`, else `Storage::url()` | Same | Legacy files (no id) still use direct URL → 404 risk |
| `Edit/IES/attachments.blade.php` | Same | Same | Same |

**Route:** `/projects/ies/attachments/view/{fileId}`, `/projects/ies/attachments/download/{fileId}`  
**Controller:** `IESAttachmentsController::viewFile`, `downloadFile`

---

### 2. IIES (Individual - Initial Educational Support)

| View | View Link | Download Link | Notes |
|------|-----------|---------------|-------|
| `Show/IIES/attachments.blade.php` | `route('projects.iies.attachments.view', $file->id)` | `route('projects.iies.attachments.download', $file->id)` | Fully route-based ✅ |
| `Edit/IIES/attachments.blade.php` | Same | Same | Fully route-based ✅ |

**Route:** `/projects/iies/attachments/view/{fileId}`, `/projects/iies/attachments/download/{fileId}`  
**Controller:** `IIESAttachmentsController::viewFile`, `downloadFile`

---

### 3. IAH (Individual Assistance for Health) — Not Fixed

| View | View Link | Download Link | Notes |
|------|-----------|---------------|-------|
| `Show/IAH/documents.blade.php` | `Storage::url($file->file_path)` | `Storage::url($file->file_path)` | Both require symlink → 404 on production |
| `Edit/IAH/documents.blade.php` | Same | Same | Same |

**Model:** `ProjectIAHDocumentFile` (table `project_IAH_document_files`)  
**Controller:** `IAHDocumentsController` — no `viewFile` or `downloadFile`  
**Routes:** None for IAH document view/download

---

### 4. ILP (Individual Livelihood Project) — Not Fixed

| View | View Link | Download Link | Notes |
|------|-----------|---------------|-------|
| `Show/ILP/attached_docs.blade.php` | `Storage::url($file->file_path)` | `Storage::url($file->file_path)` | Both require symlink → 404 on production |
| `Edit/ILP/attached_docs.blade.php` | Same | Same | Same |

**Model:** `ProjectILPDocumentFile` (table `project_ILP_document_files`)  
**Controller:** `AttachedDocumentsController` — no `viewFile` or `downloadFile`  
**Routes:** None for ILP document view/download

---

### 5. General Project Attachments (Development Projects)

| View | View Link | Download Link | Notes |
|------|-----------|---------------|-------|
| `Show/attachments.blade.php` | `asset('storage/' . $attachment->file_path)` | `route('projects.attachments.download', $attachment->id)` | View uses asset → 404 risk; Download OK |
| `Edit/attachment.blade.php` | Same | Same | Same |

**Model:** `ProjectAttachment` (table `project_attachments`)  
**Controller:** `AttachmentController::downloadAttachment` — streams file ✓  
**Route:** `projects.attachments.download` — Download works; **no View route** → View link uses asset() → 404 risk

---

### 6. Monthly Report Attachments

| View | View Link | Download Link | Notes |
|------|-----------|---------------|-------|
| `reports/monthly/partials/view/attachments.blade.php` | `asset('storage/' . $attachment->file_path)` | `route('reports.attachments.download', $attachment->id)` | View uses asset → 404 risk; Download OK |
| `reports/monthly/partials/edit/attachments.blade.php` | Same | Same | Same |

**Model:** `ReportAttachment`  
**Controller:** `ReportAttachmentController::downloadAttachment` — streams file ✓  
**Route:** `reports.attachments.download` — Download works; **no View route** → View link uses asset() → 404 risk

---

## Routes Inventory (Current)

| Route Name | URL Pattern | Controller Method | Project Type |
|------------|-------------|-------------------|--------------|
| `projects.attachments.download` | `/projects/attachments/download/{id}` | `AttachmentController::downloadAttachment` | General |
| `projects.ies.attachments.view` | `/projects/ies/attachments/view/{fileId}` | `IESAttachmentsController::viewFile` | IES |
| `projects.ies.attachments.download` | `/projects/ies/attachments/download/{fileId}` | `IESAttachmentsController::downloadFile` | IES |
| `projects.iies.attachments.view` | `/projects/iies/attachments/view/{fileId}` | `IIESAttachmentsController::viewFile` | IIES |
| `projects.iies.attachments.download` | `/projects/iies/attachments/download/{fileId}` | `IIESAttachmentsController::downloadFile` | IIES |
| `reports.attachments.download` | `reports/monthly/attachments/download/{id}` | `ReportAttachmentController::downloadAttachment` | Report |

**Missing routes:**
- IAH: `projects.iah.documents.view`, `projects.iah.documents.download`
- ILP: `projects.ilp.documents.view`, `projects.ilp.documents.download`
- General: `projects.attachments.view` (View link currently uses asset)
- Report: `reports.attachments.view` (View link currently uses asset)

---

## Recommended Fix Plan (Priority Order)

| # | Project Type | Action | Effort |
|---|--------------|--------|--------|
| 1 | **IAH** | Add `viewFile` and `downloadFile` to `IAHDocumentsController`; add routes; update Show + Edit views | Medium |
| 2 | **ILP** | Add `viewFile` and `downloadFile` to `AttachedDocumentsController`; add routes; update Show + Edit views | Medium |
| 3 | **General** | Add `viewFile` to `AttachmentController`; add route; update Show + Edit to use route for View | Small |
| 4 | **Report** | Add `viewFile` to `ReportAttachmentController`; add route; update report view/edit partials to use route for View | Small |
| 5 | **IES** | (Optional) Add View route for legacy files without id, or migrate legacy paths to `project_IES_attachment_files` | Low |

---

## Files Requiring Changes (When Fixing)

### IAH

- `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php` — add `viewFile`, `downloadFile`
- `routes/web.php` — add IAH document routes
- `resources/views/projects/partials/Show/IAH/documents.blade.php`
- `resources/views/projects/partials/Edit/IAH/documents.blade.php`

### ILP

- `app/Http/Controllers/Projects/ILP/AttachedDocumentsController.php` — add `viewFile`, `downloadFile`
- `routes/web.php` — add ILP document routes
- `resources/views/projects/partials/Show/ILP/attached_docs.blade.php`
- `resources/views/projects/partials/Edit/ILP/attached_docs.blade.php`

### General Attachments

- `app/Http/Controllers/Projects/AttachmentController.php` — add `viewFile` (or `viewAttachment`)
- `routes/web.php` — add `projects.attachments.view`
- `resources/views/projects/partials/Show/attachments.blade.php`
- `resources/views/projects/partials/Edit/attachment.blade.php`

### Report Attachments

- `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php` — add `viewFile`
- `routes/web.php` — add `reports.attachments.view`
- `resources/views/reports/monthly/partials/view/attachments.blade.php`
- `resources/views/reports/monthly/partials/edit/attachments.blade.php`

---

## Reference: IIES / IES Implementation Pattern

For each project type with file-per-row storage (`*_document_files`, `*_attachment_files`):

1. Add `downloadFile($fileId)` — `Storage::disk('public')->download($file->file_path, $file->file_name)`
2. Add `viewFile($fileId)` — `Storage::disk('public')->get()` with `Content-Type` and `Content-Disposition: inline`
3. Add routes in shared middleware group (`auth`, `role:executor,applicant,provincial,coordinator,general`)
4. Update views: replace `Storage::url()` / `asset('storage/...')` with `route('...')` when file has `id`

See: `Documentations/V2/Attachments/IES_Attachment_404_Fix_Implementation.md`
