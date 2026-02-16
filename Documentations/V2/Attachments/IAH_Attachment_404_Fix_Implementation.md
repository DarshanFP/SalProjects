# IAH Attachment 404 Fix — Implementation Summary

**Date:** 2026-02-16  
**Status:** ✅ Implemented  
**Project Type:** IAH (Individual Assistance for Health)

---

## Problem Description

Users could not view or download IAH project documents (aadhar copy, request letter, medical reports, other docs). Clicking View or Download resulted in **404 Not Found**.

**Example failing URL:**
```
https://v1.salprojects.org/storage/project_attachments/IAH/IAH-0001/IAH-0001_aadhar_copy.jpg
```

---

## Root Cause

**Symlink dependency:** Views used `Storage::url($file->file_path)`, which generates direct `/storage/...` URLs. These require the Laravel storage symlink (`public/storage` → `storage/app/public`). Without it, requests return 404.

---

## Solution Implemented

Following the exact architectural pattern used in `IESAttachmentsController` (V2 implementation):

1. Added dedicated **download** and **view** controller methods that stream files via `Storage::disk('public')`
2. Registered routes under the shared middleware group (auth + role:executor,applicant,provincial,coordinator,general)
3. Updated Show and Edit views to use route-based URLs

---

## Controller Changes

**File:** `app/Http/Controllers/Projects/IAH/IAHDocumentsController.php`

**Import added:**
```php
use App\Models\OldProjects\IAH\ProjectIAHDocumentFile;
```

(Storage and Log were already imported.)

**New methods:**

### `downloadFile($fileId)`
- Fetches `ProjectIAHDocumentFile::findOrFail($fileId)`
- Logs start and file found
- Checks `Storage::disk('public')->exists($file->file_path)` → returns 404 JSON if not
- Returns `Storage::disk('public')->download($file->file_path, $file->file_name)`
- Catches `ModelNotFoundException` and `Exception`; logs and returns appropriate JSON errors

### `viewFile($fileId)`
- Fetches `ProjectIAHDocumentFile::findOrFail($fileId)`
- Logs start and file found
- Checks `Storage::disk('public')->exists($file->file_path)` → returns 404 JSON if not
- Reads content via `Storage::disk('public')->get($file->file_path)`
- Gets MIME type via `Storage::disk('public')->mimeType($file->file_path)`
- Returns `response($fileContent, 200)` with headers:
  - `Content-Type`: MIME type
  - `Content-Disposition`: inline; filename="original_name"
- Catches `ModelNotFoundException` and `Exception`; logs and returns appropriate JSON errors

---

## Routes Added

**File:** `routes/web.php`

**Import added:**
```php
use App\Http\Controllers\Projects\IAH\IAHDocumentsController;
```

**Routes** (inside shared middleware group, adjacent to IES/IIES routes):
```php
// IAH Document file download and view routes (Individual Assistance for Health)
Route::get('/projects/iah/documents/view/{fileId}', [IAHDocumentsController::class, 'viewFile'])
    ->name('projects.iah.documents.view');
Route::get('/projects/iah/documents/download/{fileId}', [IAHDocumentsController::class, 'downloadFile'])
    ->name('projects.iah.documents.download');
```

---

## Blade Changes

### Show View: `resources/views/projects/partials/Show/IAH/documents.blade.php`

**Before:**
```blade
<a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
    <i class="fas fa-eye"></i> View
</a>
<a href="{{ Storage::url($file->file_path) }}" download class="btn btn-sm btn-secondary">
    <i class="fas fa-download"></i> Download
</a>
```

**After:**
```blade
<a href="{{ route('projects.iah.documents.view', $file->id) }}" target="_blank" class="btn btn-sm btn-primary">
    <i class="fas fa-eye"></i> View
</a>
<a href="{{ route('projects.iah.documents.download', $file->id) }}" class="btn btn-sm btn-secondary">
    <i class="fas fa-download"></i> Download
</a>
```

### Edit View: `resources/views/projects/partials/Edit/IAH/documents.blade.php`

Same transformation applied for existing file View/Download links.

**Removed:** All direct `Storage::url($file->file_path)` usage.  
**Added:** Route-based View and Download links using `$file->id`.

IAH files are stored in `project_IAH_document_files` and always have `id`. No legacy fallback required.

---

## Before vs After Flow

| Action | Before | After |
|--------|--------|-------|
| User clicks **View** | Browser requests `/storage/project_attachments/IAH/...` → 404 if no symlink | Browser requests `/projects/iah/documents/view/{fileId}` → Controller streams file → 200 |
| User clicks **Download** | Same direct URL → 404 | Browser requests `/projects/iah/documents/download/{fileId}` → Controller streams download → 200 |

---

## Deployment Checklist

1. Deploy updated code
2. On production:
   - `php artisan route:clear`
   - `php artisan view:clear`
   - If using route cache: `php artisan route:cache`
3. Verify:
   - Open any IAH project (e.g. IAH-0001)
   - Click View on a document → file opens in new tab
   - Click Download on a document → file downloads

---

## Edge Cases

| Case | Behavior |
|------|----------|
| File from `project_IAH_document_files` (has `id`) | Uses route → works without symlink |
| File record exists but file missing on disk | Controller returns 404 JSON |
| Invalid `fileId` | Controller returns 404 JSON (ModelNotFoundException) |
| Storage/disk error | Controller returns 500 JSON |

---

## Related References

- `Documentations/V2/Attachments/IES_Attachment_404_Fix_Implementation.md` — IES implementation (same pattern)
- `Documentations/V2/Attachments/Attachment_404_Review.md` — Full review of all project types
