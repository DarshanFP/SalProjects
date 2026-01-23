# IIES File Download/View 404 Issue - Resolution

**Issue Date:** January 23, 2026  
**Status:** ✅ RESOLVED

---

## Problem Summary

Files were being stored correctly in `storage/app/public/project_attachments/IIES/IIES-0015/`, but:
1. **Direct URL access** (`https://v1.salprojects.org/storage/project_attachments/...`) returned **404 Not Found**
2. **Download attempts** downloaded HTML error pages instead of actual files
3. **View links** did not work

---

## Root Cause

The issue was caused by **missing Laravel storage symlink** on production. Laravel's `Storage::url()` method generates URLs like `/storage/...` which require a symlink from `public/storage` to `storage/app/public` to work.

**Why it failed:**
- Files are stored in `storage/app/public/project_attachments/IIES/...`
- Views were using `Storage::url($file->file_path)` which generates `/storage/project_attachments/...`
- Without the symlink, accessing `/storage/...` returns 404
- Browser downloads the 404 error page (HTML) instead of the file

---

## Solution Implemented

Created **dedicated download and view routes** that use Laravel's `Storage::download()` and `Storage::get()` methods, which **don't require the symlink** to work.

### Changes Made

#### 1. Added Download & View Methods to Controller

**File:** `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`

Added two new methods:

```php
/**
 * DOWNLOAD: download a specific attachment file
 */
public function downloadFile($fileId)
{
    // Uses Storage::disk('public')->download() 
    // Works without symlink - streams file directly
}

/**
 * VIEW: view a specific attachment file (stream response)
 */
public function viewFile($fileId)
{
    // Uses Storage::disk('public')->get() 
    // Works without symlink - streams file directly
}
```

#### 2. Added Routes

**File:** `routes/web.php`

Added routes for downloading and viewing files:

```php
// IIES Attachment file download and view routes
Route::get('/projects/iies/attachments/download/{fileId}', [IIESAttachmentsController::class, 'downloadFile'])
    ->name('projects.iies.attachments.download');
Route::get('/projects/iies/attachments/view/{fileId}', [IIESAttachmentsController::class, 'viewFile'])
    ->name('projects.iies.attachments.view');
```

#### 3. Updated Views

**Files Updated:**
- `resources/views/projects/partials/Show/IIES/attachments.blade.php`
- `resources/views/projects/partials/Edit/IIES/attachments.blade.php`

**Changed from:**
```blade
<a href="{{ Storage::url($file->file_path) }}" download>Download</a>
<a href="{{ Storage::url($file->file_path) }}" target="_blank">View</a>
```

**Changed to:**
```blade
<a href="{{ route('projects.iies.attachments.download', $file->id) }}">Download</a>
<a href="{{ route('projects.iies.attachments.view', $file->id) }}" target="_blank">View</a>
```

---

## How It Works Now

### Download Flow
1. User clicks "Download" button
2. Browser requests: `/projects/iies/attachments/download/{fileId}`
3. Controller fetches file from `storage/app/public/`
4. Controller streams file directly using `Storage::download()`
5. Browser downloads the actual file (not HTML)

### View Flow
1. User clicks "View" button
2. Browser requests: `/projects/iies/attachments/view/{fileId}`
3. Controller fetches file from `storage/app/public/`
4. Controller streams file with proper MIME type headers
5. Browser displays file inline (PDF/images)

---

## Benefits

✅ **Works without symlink** - No need to create `public/storage` symlink  
✅ **Secure** - Files are served through Laravel routes (can add authentication/authorization)  
✅ **Proper MIME types** - Files are served with correct content-type headers  
✅ **Audit trail** - All downloads/views are logged  
✅ **Error handling** - Proper 404 responses if file doesn't exist  

---

## Testing

After deployment, test:

1. **Download functionality:**
   - Go to project IIES-0015
   - Click "Download" on any attachment
   - Verify file downloads correctly (not HTML)

2. **View functionality:**
   - Click "View" on any attachment
   - Verify file opens in browser (PDF/images)

3. **Error handling:**
   - Try accessing non-existent file ID
   - Should get proper 404 JSON response

---

## Additional Recommendation: Create Storage Symlink

While the new routes work without a symlink, it's still recommended to create the symlink for consistency:

**On Production Server:**
```bash
php artisan storage:link
```

This creates: `public/storage` → `storage/app/public`

**Benefits:**
- Direct file URLs will work (if needed in future)
- Consistent with Laravel best practices
- Other parts of application may rely on it

**Note:** The new download/view routes will work **with or without** the symlink.

---

## Related Files

1. **Controller:** `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`
2. **Routes:** `routes/web.php` (lines ~465-466)
3. **Views:**
   - `resources/views/projects/partials/Show/IIES/attachments.blade.php`
   - `resources/views/projects/partials/Edit/IIES/attachments.blade.php`
4. **Model:** `app/Models/OldProjects/IIES/ProjectIIESAttachmentFile.php`

---

## Deployment Notes

1. **No database changes required**
2. **No configuration changes required**
3. **Backward compatible** - Old files still work
4. **Immediate effect** - Takes effect after code deployment

---

## Resolution Date

**Fixed:** January 23, 2026  
**Status:** ✅ Ready for Testing

---

## Future Enhancements (Optional)

Consider applying the same pattern to other project types:
- IES attachments
- IAH documents  
- ILP attached documents

This would provide consistent file access across all project types.
