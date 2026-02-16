# IES Attachment 404 Fix — Implementation Summary

**Date:** 2026-02-16  
**Status:** ✅ Implemented  
**Project Type:** IES (Individual - Ongoing Educational Support) — e.g. IOES-0043

---

## Problem

Applicants and other users could not view or download IES project attachments (e.g. caste certificate, fee quotation). Clicking View or Download resulted in **404 Not Found**.

**Example failing URL:**
```
https://v1.salprojects.org/storage/project_attachments/IES/IOES-0043/IOES-0043_caste_certificate_02.jpg
```

**Root cause:** Views used `Storage::url($file->file_path)`, which generates direct `/storage/...` URLs. These require the Laravel storage symlink (`public/storage` → `storage/app/public`). Without it, requests return 404.

---

## Solution Implemented

Following the same pattern used for IIES (see `Documentations/V1/ProjectsPhotos/IIES_File_Download_View_404_Fix.md`):

1. Added dedicated **download** and **view** controller methods that stream files via `Storage::disk('public')`
2. Registered routes under the shared middleware group
3. Updated Show and Edit views to use route-based URLs when the file has an `id`

---

## Changes Made

### 1. Controller: `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`

**Imports added:**
```php
use App\Models\OldProjects\IES\ProjectIESAttachmentFile;
use Illuminate\Support\Facades\Storage;
```

**New methods:**

#### `downloadFile($fileId)`
- Fetches `ProjectIESAttachmentFile::findOrFail($fileId)`
- Checks file exists on disk; returns 404 JSON if not
- Returns `Storage::disk('public')->download($file->file_path, $file->file_name)`
- Logs start, file found, download, and errors

#### `viewFile($fileId)`
- Fetches `ProjectIESAttachmentFile::findOrFail($fileId)`
- Checks file exists on disk; returns 404 JSON if not
- Reads content via `Storage::disk('public')->get()` and MIME type
- Returns `response($fileContent, 200)` with `Content-Type` and `Content-Disposition: inline`
- Logs start, file found, view, and errors

---

### 2. Routes: `routes/web.php`

**Import added:**
```php
use App\Http\Controllers\Projects\IES\IESAttachmentsController;
```

**Routes added** (inside shared middleware group, before IIES routes):
```php
// IES Attachment file download and view routes (Individual - Ongoing Educational Support)
Route::get('/projects/ies/attachments/download/{fileId}', [IESAttachmentsController::class, 'downloadFile'])
    ->name('projects.ies.attachments.download');
Route::get('/projects/ies/attachments/view/{fileId}', [IESAttachmentsController::class, 'viewFile'])
    ->name('projects.ies.attachments.view');
```

---

### 3. Views Updated

#### `resources/views/projects/partials/Show/IES/attachments.blade.php`

**Before:**
```blade
<a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View</a>
<a href="{{ Storage::url($file->file_path) }}" download class="btn btn-sm btn-secondary">Download</a>
```

**After:**
```blade
@if(isset($file->id))
    <a href="{{ route('projects.ies.attachments.view', $file->id) }}" target="_blank" class="btn btn-sm btn-primary">View</a>
    <a href="{{ route('projects.ies.attachments.download', $file->id) }}" class="btn btn-sm btn-secondary">Download</a>
@else
    <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View</a>
    <a href="{{ Storage::url($file->file_path) }}" download class="btn btn-sm btn-secondary">Download</a>
@endif
```

**Rationale:** Files from `project_IES_attachment_files` have `id`; legacy files (from `getFilesForField` fallback) are stdClass objects without `id`. Route-based URLs only when `$file->id` exists.

#### `resources/views/projects/partials/Edit/IES/attachments.blade.php`

Same pattern applied for existing file View/Download links.

---

## Flow After Fix

| Action | Before | After |
|--------|--------|-------|
| User clicks **View** | Browser requests `/storage/project_attachments/IES/IOES-0043/...` → 404 if no symlink | Browser requests `/projects/ies/attachments/view/123` → Controller streams file → 200 |
| User clicks **Download** | Same direct URL → 404 | Browser requests `/projects/ies/attachments/download/123` → Controller streams download → 200 |

---

## Deployment Checklist

1. Deploy updated code
2. On production:
   - `php artisan route:clear`
   - `php artisan view:clear`
   - If using route cache: `php artisan route:cache`
3. Verify:
   - Applicant can open project IOES-0043 (or any IES project)
   - Click View on an attachment → file opens in new tab
   - Click Download on an attachment → file downloads

---

## Edge Cases

| Case | Behavior |
|------|----------|
| File from `project_IES_attachment_files` (has `id`) | Uses route → works without symlink |
| Legacy file (from `project_IES_attachments` column, no `id`) | Uses `Storage::url()` → still 404 if symlink missing |
| File record exists but file missing on disk | Controller returns 404 JSON |

---

## Related Documentation

- `Documentations/V1/ProjectsPhotos/IIES_File_Download_View_404_Fix.md` — Original IIES fix
- `Documentations/V2/Attachments/Attachment_404_Review.md` — Full review of all project types
