# Phase-Wise Implementation Plan: Attachments System Fixes

**Date:** January 2025  
**Based on:** Attachments_Comprehensive_Review.md  
**Estimated Total Time:** 5-6 weeks  
**Priority:** High (Critical Security & Functionality Issues + Multiple File Upload Requirements)

---

## Overview

This document outlines a structured, phase-wise approach to fixing all identified issues in the attachments system. The plan is organized by priority and dependency, ensuring critical security and functionality issues are addressed first.

### Key Requirements

All attachment locations must support:
1. **Multiple file uploads per field** - Users should be able to upload multiple files for each attachment field
2. **File naming convention:** `{ProjectID}_{FieldName}_{2-digit-serial-number}.{extension}`
   - Example: `IES-0013_aadhar_card_01.pdf`, `IES-0013_aadhar_card_02.pdf`
3. **User-provided names:** If user provides a file name, use that; otherwise use the pattern above
4. **Storage structure:** Files stored in `/project_type/project_id/` folder
5. **Multiple view/edit:** Views and edit forms must display and allow editing of multiple files per field

### Current State

- **Report Attachments:** ✅ Already supports multiple files (needs naming pattern update)
- **Regular Project Attachments:** ⚠️ Database supports multiple, but controller/views need updates
- **IES/IIES/IAH/ILP Attachments:** ❌ Need complete restructuring for multiple files

---

## Phase 1: Critical Storage & Path Fixes (Week 1, Days 1-2)

**Priority:** 🔴 CRITICAL  
**Estimated Time:** 2 days  
**Dependencies:** None

### Objectives
- Fix storage path inconsistencies
- Ensure all files are stored in correct locations
- Fix file URL generation issues
- Standardize storage paths to `/project_type/project_id/` pattern

### Tasks

#### 1.1 Fix IES Attachments Storage Path
**File:** `app/Models/OldProjects/IES/ProjectIESAttachments.php`

**Current Code (Line 92-96):**
```php
$projectDir = "public/project_attachments/IES/{$projectId}";
\Storage::makeDirectory($projectDir);
```

**Fix:**
```php
$projectDir = "project_attachments/IES/{$projectId}";
Storage::disk('public')->makeDirectory($projectDir, 0755, true);
```

**Also update line 106:**
```php
// Change from:
$filePath = $file->storeAs($projectDir, $fileName);

// To:
$filePath = $file->storeAs($projectDir, $fileName, 'public');
```

**Testing:**
- [ ] Upload IES attachment
- [ ] Verify file is stored in `storage/app/public/project_attachments/IES/{projectId}/`
- [ ] Verify file is accessible via URL
- [ ] Check database stores correct path (without `public/` prefix)

---

#### 1.2 Verify All Storage Paths Are Consistent
**Files to Check:**
- `app/Models/OldProjects/IIES/ProjectIIESAttachments.php` ✅ (Already correct)
- `app/Http/Controllers/Projects/AttachmentController.php` ✅ (Already correct)
- `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php` ✅ (Already correct)

**Action:** Document that these are correct, no changes needed.

---

#### 1.3 Fix File URL Generation in Views
**File:** `resources/views/projects/partials/Show/IES/attachments.blade.php`

**Current Code (Line 20, 24, 46, 50):**
```php
<a href="{{ Storage::url($IESAttachments->$name) }}" target="_blank">
```

**Fix:** Ensure paths stored in database don't have `public/` prefix, then URLs will work correctly. If paths already have `public/`, add helper:

```php
@php
    $fileUrl = str_starts_with($IESAttachments->$name, 'public/') 
        ? Storage::url(str_replace('public/', '', $IESAttachments->$name))
        : Storage::url($IESAttachments->$name);
@endphp
<a href="{{ $fileUrl }}" target="_blank">
```

**Or better:** Fix the model (Phase 1.1) so paths are correct, then this view will work.

**Testing:**
- [ ] Verify all file links work in IES Show view
- [ ] Verify all file links work in IIES Show view
- [ ] Test download functionality

---

### Deliverables
- ✅ IES storage path fixed
- ✅ All storage paths verified consistent
- ✅ File URLs working correctly
- ✅ Test results documented

---

## Phase 2: Security & Validation Fixes (Week 1, Days 3-5)

**Priority:** 🔴 CRITICAL  
**Estimated Time:** 3 days  
**Dependencies:** Phase 1 complete

### Objectives
- Add server-side file type validation
- Add server-side file size validation
- Prevent security vulnerabilities

### Tasks

#### 2.1 Add File Type Validation to IES Model
**File:** `app/Models/OldProjects/IES/ProjectIESAttachments.php`

**Add Method:**
```php
/**
 * Validate file type
 */
private static function isValidFileType($file)
{
    $extension = strtolower($file->getClientOriginalExtension());
    $mimeType = $file->getMimeType();
    
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png'
    ];
    
    return in_array($extension, $allowedExtensions) && 
           in_array($mimeType, $allowedMimeTypes);
}
```

**Update `handleAttachments()` method (around line 100):**
```php
foreach ($fields as $field) {
    if ($request->hasFile($field)) {
        $file = $request->file($field);
        
        // Validate file type
        if (!self::isValidFileType($file)) {
            \Log::error('Invalid file type for IES attachment', [
                'field' => $field,
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]);
            throw new \Exception("Invalid file type for {$field}. Only PDF, JPG, and PNG files are allowed.");
        }
        
        // Validate file size (2MB max)
        if ($file->getSize() > 2097152) {
            throw new \Exception("File size exceeds 2MB limit for {$field}.");
        }
        
        $fileName = "{$projectId}_{$field}." . $file->getClientOriginalExtension();
        // ... rest of code
    }
}
```

**Testing:**
- [ ] Try uploading invalid file type (should fail)
- [ ] Try uploading file > 2MB (should fail)
- [ ] Upload valid PDF (should succeed)
- [ ] Upload valid JPG (should succeed)
- [ ] Upload valid PNG (should succeed)

---

#### 2.2 Add File Type Validation to IIES Model
**File:** `app/Models/OldProjects/IIES/ProjectIIESAttachments.php`

**Add Same Validation Method and Update `handleAttachments()`:**

Follow same pattern as 2.1, add validation before file storage.

**Testing:**
- [ ] Try uploading invalid file type (should fail)
- [ ] Try uploading file > 2MB (should fail)
- [ ] Upload valid files (should succeed)

---

#### 2.3 Add File Size Validation to All Attachment Types
**Files:**
- `app/Models/OldProjects/IES/ProjectIESAttachments.php` (covered in 2.1)
- `app/Models/OldProjects/IIES/ProjectIIESAttachments.php` (covered in 2.2)
- `app/Http/Controllers/Projects/AttachmentController.php` ✅ (Already has validation)
- `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php` ✅ (Already has validation)

**Action:** Verify existing validations are working, add to IES/IIES models.

---

#### 2.4 Add Transaction Rollback with File Cleanup
**File:** `app/Models/OldProjects/IES/ProjectIESAttachments.php`

**Update `handleAttachments()` to wrap in try-catch:**
```php
public static function handleAttachments($request, $projectId)
{
    $fields = [/* ... */];
    $projectDir = "project_attachments/IES/{$projectId}";
    Storage::disk('public')->makeDirectory($projectDir, 0755, true);
    
    $attachments = self::updateOrCreate(['project_id' => $projectId], []);
    $uploadedFiles = []; // Track uploaded files for cleanup
    
    try {
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                
                // Validation (from 2.1)
                if (!self::isValidFileType($file)) {
                    throw new \Exception("Invalid file type for {$field}");
                }
                if ($file->getSize() > 2097152) {
                    throw new \Exception("File size exceeds 2MB for {$field}");
                }
                
                $fileName = "{$projectId}_{$field}." . $file->getClientOriginalExtension();
                $filePath = $file->storeAs($projectDir, $fileName, 'public');
                
                if ($filePath) {
                    $uploadedFiles[] = $filePath; // Track for cleanup
                    
                    // Delete old file if exists
                    if (!empty($attachments->{$field}) && $attachments->{$field} !== $filePath) {
                        Storage::disk('public')->delete($attachments->{$field});
                    }
                    
                    $attachments->{$field} = $filePath;
                }
            }
        }
        
        $attachments->save();
        return $attachments;
        
    } catch (\Exception $e) {
        // Clean up uploaded files on error
        foreach ($uploadedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }
        throw $e;
    }
}
```

**Apply same pattern to IIES model.**

**Testing:**
- [ ] Upload file, simulate DB error, verify file is cleaned up
- [ ] Upload multiple files, verify all cleaned up on error

---

### Deliverables
- ✅ File type validation added to IES model
- ✅ File type validation added to IIES model
- ✅ File size validation added to all models
- ✅ Transaction rollback with file cleanup implemented
- ✅ Test results documented

---

## Phase 3: View & UI Fixes (Week 2, Days 1-3)

**Priority:** 🟡 HIGH  
**Estimated Time:** 3 days  
**Dependencies:** Phase 1 & 2 complete

### Objectives
- Fix view bugs and null pointer issues
- Add JavaScript validation
- Improve user experience

### Tasks

#### 3.1 Fix Hardcoded Project ID Fallback
**File:** `resources/views/projects/partials/Show/IIES/attachments.blade.php`

**Current Code (Line 3-8):**
```php
@php
    if (!isset($IIESAttachments) || empty($IIESAttachments)) {
        $controller = new \App\Http\Controllers\Projects\IIES\IIESAttachmentsController();
        $IIESAttachments = $controller->show($project->project_id ?? 'IIES-0013');
    }
@endphp
```

**Fix:**
```php
@php
    if (!isset($IIESAttachments) || empty($IIESAttachments)) {
        if (isset($project->project_id) && !empty($project->project_id)) {
            $controller = new \App\Http\Controllers\Projects\IIES\IIESAttachmentsController();
            $IIESAttachments = $controller->show($project->project_id);
        } else {
            $IIESAttachments = [];
        }
    }
@endphp
```

**Testing:**
- [ ] Test with valid project_id
- [ ] Test with null project_id (should not error)
- [ ] Test with missing project_id (should not error)

---

#### 3.2 Add Null Checks in IES Show View
**File:** `resources/views/projects/partials/Show/IES/attachments.blade.php`

**Current Code (Line 18):**
```php
@if(!empty($IESAttachments->$name))
```

**Fix:**
```php
@if(!empty($IESAttachments) && !empty($IESAttachments->$name))
```

**Apply to all 8 fields in the view.**

**Testing:**
- [ ] Test with null $IESAttachments
- [ ] Test with empty $IESAttachments
- [ ] Test with valid attachments

---

#### 3.3 Add File Existence Checks in Show Views
**Files:**
- `resources/views/projects/partials/Show/attachments.blade.php`
- `resources/views/projects/partials/Show/IIES/attachments.blade.php`
- `resources/views/projects/partials/Show/IES/attachments.blade.php`

**Pattern to Add:**
```php
@php
    $fileExists = !empty($attachment->file_path) && 
                  \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path);
@endphp

@if($fileExists)
    <a href="{{ Storage::url($attachment->file_path) }}" target="_blank">View</a>
@else
    <span class="text-danger">
        <i class="fas fa-exclamation-triangle"></i> File not found
    </span>
@endif
```

**Testing:**
- [ ] Test with existing files (should show links)
- [ ] Test with missing files (should show warning)
- [ ] Test with null file_path (should show warning)

---

#### 3.4 Add JavaScript Validation for IES/IIES Uploads
**File:** `resources/views/projects/partials/IIES/attachments.blade.php`

**Add Script Section:**
```javascript
<script>
function validateIIESFile(input) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    const maxSize = 2097152; // 2MB
    
    // Reset any previous errors
    const errorDiv = input.parentElement.querySelector('.file-error');
    if (errorDiv) errorDiv.remove();
    
    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            showFileError(input, 'Invalid file type. Only PDF, JPG, and PNG files are allowed.');
            input.value = '';
            return false;
        }
        
        // Check file size
        if (file.size > maxSize) {
            showFileError(input, 'File size must not exceed 2 MB.');
            input.value = '';
            return false;
        }
        
        // Show success indicator
        showFileSuccess(input, file.name, file.size);
    }
    return true;
}

function showFileError(input, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mt-2 file-error';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
    input.parentElement.appendChild(errorDiv);
}

function showFileSuccess(input, fileName, fileSize) {
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success mt-2 file-success';
    successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + fileName + 
                         ' (' + formatFileSize(fileSize) + ')';
    input.parentElement.appendChild(successDiv);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
```

**Update Input Fields:**
```html
<input type="file" name="{{ $field }}" 
       class="form-control-file" 
       accept=".pdf,.jpg,.jpeg,.png"
       onchange="validateIIESFile(this)">
```

**Apply same pattern to IES attachments view.**

**Testing:**
- [ ] Select invalid file type (should show error, clear input)
- [ ] Select file > 2MB (should show error, clear input)
- [ ] Select valid file (should show success message)
- [ ] Submit form with valid files (should work)

---

#### 3.5 Fix JavaScript Null Reference Issues
**File:** `resources/views/projects/partials/attachments.blade.php`

**Update `validateFile()` function:**
```javascript
function validateFile(input) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const maxSize = 2097152; // 2MB

    // Reset warnings with null checks
    const sizeWarning = document.getElementById('file-size-warning');
    const typeWarning = document.getElementById('file-type-warning');
    const preview = document.getElementById('file-preview');
    
    if (sizeWarning) sizeWarning.style.display = 'none';
    if (typeWarning) typeWarning.style.display = 'none';
    if (preview) preview.style.display = 'none';

    if (file) {
        // Check file type
        if (!validTypes.includes(file.type)) {
            if (typeWarning) typeWarning.style.display = 'block';
            input.value = '';
            return;
        }

        // Check file size
        if (file.size > maxSize) {
            if (sizeWarning) sizeWarning.style.display = 'block';
            input.value = '';
            return;
        }

        // Show file preview with null checks
        const fileNameEl = document.getElementById('file-name');
        const fileSizeEl = document.getElementById('file-size');
        const fileIcon = document.getElementById('file-icon');
        
        if (fileNameEl) fileNameEl.textContent = file.name;
        if (fileSizeEl) fileSizeEl.textContent = formatFileSize(file.size);
        
        if (fileIcon) {
            if (file.type === 'application/pdf') {
                fileIcon.className = 'fas fa-file-pdf text-danger';
            } else if (file.type === 'application/msword' || 
                       file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                fileIcon.className = 'fas fa-file-word text-primary';
            } else {
                fileIcon.className = 'fas fa-file text-secondary';
            }
        }
        
        if (preview) preview.style.display = 'block';
    }
}
```

**Testing:**
- [ ] Test in create view (should work)
- [ ] Test in edit view (should work)
- [ ] Test with missing DOM elements (should not error)

---

### Deliverables
- ✅ Hardcoded project ID fallback fixed
- ✅ Null checks added to all views
- ✅ File existence checks added
- ✅ JavaScript validation added for IES/IIES
- ✅ JavaScript null reference issues fixed
- ✅ Test results documented

---

## Phase 4: Code Quality & Standardization (Week 2, Days 4-5)

**Priority:** 🟡 HIGH  
**Estimated Time:** 2 days  
**Dependencies:** Phase 3 complete

### Objectives
- Standardize response formats
- Fix route naming inconsistencies
- Extract duplicate code
- Fix naming issues

### Tasks

#### 4.1 Standardize Route Naming
**File:** `routes/web.php`

**Current Routes:**
- `download.attachment` (project attachments)
- `monthly.report.downloadAttachment` (report attachments)
- `attachments.remove` (report attachment removal)

**Proposed Standard:**
- `projects.attachments.download`
- `reports.attachments.download`
- `reports.attachments.remove`

**Update Routes:**
```php
// Project attachments
Route::get('/projects/attachments/download/{id}', 
    [AttachmentController::class, 'downloadAttachment'])
    ->name('projects.attachments.download');

// Report attachments
Route::get('reports/monthly/attachments/download/{id}', 
    [ReportAttachmentController::class, 'downloadAttachment'])
    ->name('reports.attachments.download');

Route::delete('reports/monthly/attachments/{id}', 
    [ReportAttachmentController::class, 'remove'])
    ->name('reports.attachments.remove');
```

**Update All References:**
- Search and replace in all Blade files
- Search and replace in all controllers
- Search and replace in JavaScript files

**Files to Update:**
- `resources/views/projects/partials/Show/attachments.blade.php`
- `resources/views/projects/partials/Edit/attachement.blade.php`
- `resources/views/reports/monthly/partials/edit/attachments.blade.php`
- `resources/views/reports/monthly/partials/view/attachments.blade.php`
- Any other files using these routes

**Testing:**
- [ ] All download links work
- [ ] All remove buttons work
- [ ] No broken route references

---

#### 4.2 Fix File Name Typo
**File:** `resources/views/projects/partials/Edit/attachement.blade.php`

**Action:**
1. Rename file to `attachment.blade.php` (fix typo)
2. Update all references to this file

**Find References:**
```bash
grep -r "attachement" resources/views/
grep -r "attachement" app/
```

**Update References:**
- Search for `@include('projects.partials.Edit.attachement')`
- Replace with `@include('projects.partials.Edit.attachment')`

**Testing:**
- [ ] Edit view loads correctly
- [ ] All includes work
- [ ] No broken references

---

#### 4.3 Extract Duplicate JavaScript to Shared File
**Create:** `public/js/attachments-validation.js`

**Extract Common Functions:**
```javascript
/**
 * Shared attachment validation functions
 */

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function validateFileType(file, allowedTypes) {
    return allowedTypes.includes(file.type);
}

function validateFileSize(file, maxSize) {
    return file.size <= maxSize;
}

function showFileError(container, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mt-2 file-error';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
    container.appendChild(errorDiv);
}

function showFileSuccess(container, fileName, fileSize) {
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success mt-2 file-success';
    successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + fileName + 
                         ' (' + formatFileSize(fileSize) + ')';
    container.appendChild(successDiv);
}
```

**Update Blade Files:**
- Include script in layout or specific views:
```html
<script src="{{ asset('js/attachments-validation.js') }}"></script>
```

**Refactor Existing JavaScript:**
- Update `resources/views/projects/partials/attachments.blade.php`
- Update `resources/views/projects/partials/Edit/attachement.blade.php`
- Update `resources/views/reports/monthly/partials/create/attachments.blade.php`
- Update `resources/views/reports/monthly/partials/edit/attachments.blade.php`
- Update IES/IIES views to use shared functions

**Testing:**
- [ ] All validation still works
- [ ] No JavaScript errors
- [ ] File size formatting consistent

---

#### 4.4 Standardize Response Formats
**Files:**
- `app/Http/Controllers/Projects/AttachmentController.php`
- `app/Http/Controllers/Projects/IIES/IIESAttachmentsController.php`
- `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`
- `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php`

**Decision:** Determine if attachments should return JSON or redirects.

**Recommendation:** 
- AJAX requests → JSON responses
- Form submissions → Redirect responses

**Update Controllers:**
Add method to detect request type:
```php
private function wantsJson(Request $request)
{
    return $request->expectsJson() || $request->ajax();
}

// In store method:
if ($this->wantsJson($request)) {
    return response()->json(['message' => 'Attachment uploaded successfully'], 200);
} else {
    return redirect()->back()->with('success', 'Attachment uploaded successfully');
}
```

**Testing:**
- [ ] AJAX requests return JSON
- [ ] Form submissions redirect
- [ ] Error handling works for both

---

### Deliverables
- ✅ Routes renamed and standardized
- ✅ File name typo fixed
- ✅ Duplicate JavaScript extracted
- ✅ Response formats standardized
- ✅ Test results documented

---

## Phase 5: Enhancements & Polish (Week 3, Days 1-3)

**Priority:** 🟢 MEDIUM  
**Estimated Time:** 3 days  
**Dependencies:** Phase 4 complete

### Objectives
- Add file type icons consistently
- Move magic numbers to constants
- Improve error messages
- Add logging improvements

### Tasks

#### 5.1 Add File Type Icons Consistently
**Files:**
- `resources/views/projects/partials/Show/attachments.blade.php`
- `resources/views/projects/partials/Show/IIES/attachments.blade.php`
- `resources/views/projects/partials/Show/IES/attachments.blade.php`
- `resources/views/reports/monthly/partials/view/attachments.blade.php`

**Create Helper Function in Blade:**
```php
@php
function getFileIcon($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word text-primary';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel text-success';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return 'fas fa-file-image text-info';
        default:
            return 'fas fa-file text-secondary';
    }
}
@endphp
```

**Or Create Blade Component:**
`resources/views/components/file-icon.blade.php`:
```blade
@php
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $iconClass = match($extension) {
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc', 'docx' => 'fas fa-file-word text-primary',
        'xls', 'xlsx' => 'fas fa-file-excel text-success',
        'jpg', 'jpeg', 'png' => 'fas fa-file-image text-info',
        default => 'fas fa-file text-secondary'
    };
@endphp
<i class="{{ $iconClass }}"></i>
```

**Update Views:**
```blade
<x-file-icon :filePath="$attachment->file_path" />
{{ $attachment->file_name }}
```

**Testing:**
- [ ] Icons display correctly for all file types
- [ ] Icons are consistent across all views

---

#### 5.2 Move Magic Numbers to Constants/Config
**Create:** `config/attachments.php`

```php
<?php

return [
    'max_file_size' => 2097152, // 2MB in bytes
    
    'allowed_types' => [
        'project' => [
            'extensions' => ['pdf', 'doc', 'docx'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]
        ],
        'ies' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ]
        ],
        'iies' => [
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
            'mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ]
        ],
        'report' => [
            'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]
        ]
    ]
];
```

**Update Controllers/Models:**
```php
// In AttachmentController:
private const MAX_FILE_SIZE = config('attachments.max_file_size');
private const ALLOWED_EXTENSIONS = config('attachments.allowed_types.project.extensions');
private const ALLOWED_MIME_TYPES = config('attachments.allowed_types.project.mime_types');
```

**Update JavaScript:**
```javascript
// In shared JS file or inline:
const MAX_FILE_SIZE = {{ config('attachments.max_file_size') }};
```

**Testing:**
- [ ] Config file loads correctly
- [ ] All validations use config values
- [ ] Can change limits via config

---

#### 5.3 Improve Error Messages
**Files:** All controllers and models

**Current:**
```php
throw new \Exception("Invalid file type");
```

**Improved:**
```php
throw new \Exception("Invalid file type for {$field}. Allowed types: " . 
    implode(', ', config('attachments.allowed_types.ies.extensions')));
```

**Add User-Friendly Messages:**
```php
// In controllers, return user-friendly messages:
return redirect()->back()
    ->withErrors([
        'file' => 'The file must be a PDF, JPG, or PNG file and cannot exceed 2MB.'
    ])
    ->withInput();
```

**Testing:**
- [ ] Error messages are clear and helpful
- [ ] Users understand what went wrong
- [ ] Error messages include allowed types

---

#### 5.4 Add Comprehensive Logging
**Files:** All controllers and models

**Add Detailed Logging:**
```php
Log::info('Attachment upload started', [
    'project_id' => $projectId,
    'field' => $field,
    'file_name' => $file->getClientOriginalName(),
    'file_size' => $file->getSize(),
    'mime_type' => $file->getMimeType()
]);

Log::info('Attachment stored successfully', [
    'project_id' => $projectId,
    'field' => $field,
    'file_path' => $filePath,
    'storage_path' => $fullPath
]);

Log::error('Attachment upload failed', [
    'project_id' => $projectId,
    'field' => $field,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

**Testing:**
- [ ] Logs capture all important events
- [ ] Logs are useful for debugging
- [ ] No sensitive data in logs

---

### Deliverables
- ✅ File type icons added consistently
- ✅ Magic numbers moved to config
- ✅ Error messages improved
- ✅ Comprehensive logging added
- ✅ Test results documented

---

## Phase 6: Testing & Documentation (Week 3, Days 4-5)

**Priority:** 🟢 MEDIUM  
**Estimated Time:** 2 days  
**Dependencies:** All previous phases complete

### Objectives
- Comprehensive testing of all fixes
- Update documentation
- Create test checklist
- Performance testing

### Tasks

#### 6.1 Create Comprehensive Test Checklist
**Create:** `Documentations/REVIEW/attachements Review/Testing_Checklist.md`

**Include:**
- Unit tests for validation
- Integration tests for upload/download
- Manual testing scenarios
- Edge cases
- Error handling tests

---

#### 6.2 Execute Test Checklist
**Test All Scenarios:**

**Upload Tests:**
- [ ] Upload valid PDF (all attachment types)
- [ ] Upload valid DOC/DOCX (where allowed)
- [ ] Upload valid JPG/PNG (IES/IIES)
- [ ] Upload invalid file type (should fail)
- [ ] Upload file > 2MB (should fail)
- [ ] Upload multiple files (reports)
- [ ] Upload with missing required fields

**Download Tests:**
- [ ] Download existing attachment
- [ ] Download non-existent attachment (should handle gracefully)
- [ ] Download with invalid ID (should handle gracefully)
- [ ] Test all download routes

**Display Tests:**
- [ ] View attachments in Show views
- [ ] View with null attachments (should not error)
- [ ] View with missing files (should show warning)
- [ ] View with valid files (should show links)

**Edit Tests:**
- [ ] Edit project with existing attachment
- [ ] Replace existing attachment
- [ ] Edit with no attachment
- [ ] Edit IES attachments
- [ ] Edit IIES attachments
- [ ] Edit report attachments

**Delete Tests:**
- [ ] Delete attachment
- [ ] Delete non-existent attachment (should handle gracefully)
- [ ] Verify file is deleted from storage
- [ ] Verify database record is deleted

**Error Handling Tests:**
- [ ] Test with invalid project_id
- [ ] Test with missing project
- [ ] Test with storage errors
- [ ] Test with database errors
- [ ] Test with network errors

---

#### 6.3 Performance Testing
**Test:**
- [ ] Upload time for 2MB file
- [ ] Multiple simultaneous uploads
- [ ] Download speed
- [ ] Storage space usage
- [ ] Database query performance

---

#### 6.4 Update Documentation
**Update:**
- README files
- API documentation (if applicable)
- Code comments
- User guides (if applicable)

**Create:**
- `Documentations/REVIEW/attachements Review/Implementation_Summary.md`
- Update main review document with "FIXED" status

---

#### 6.5 Create Rollback Plan
**Document:**
- How to rollback changes if issues occur
- Database migration rollback (if any)
- File storage cleanup procedures
- Configuration rollback

---

### Deliverables
- ✅ Comprehensive test checklist created
- ✅ All tests executed and documented
- ✅ Performance testing completed
- ✅ Documentation updated
- ✅ Rollback plan created

---

## Phase 7: Multiple File Upload Implementation (Week 4, Days 1-10)

**Priority:** 🔴 CRITICAL  
**Estimated Time:** 10 days  
**Dependencies:** Phases 1-6 complete

### Objectives
- Implement multiple file upload support for all attachment types
- Create new database structures for IES, IIES, IAH, ILP
- Implement file naming pattern: `{ProjectID}_{FieldName}_{serial}.{ext}`
- Update all controllers, models, and views
- Migrate existing data to new structure

### Tasks

#### 7.1 Create Database Migrations for Multiple Files
**Files to Create:**
- `database/migrations/YYYY_MM_DD_create_project_IES_attachment_files_table.php`
- `database/migrations/YYYY_MM_DD_create_project_IIES_attachment_files_table.php`
- `database/migrations/YYYY_MM_DD_create_project_IAH_document_files_table.php`
- `database/migrations/YYYY_MM_DD_create_project_ILP_document_files_table.php`

**Structure:**
```php
Schema::create('project_IES_attachment_files', function (Blueprint $table) {
    $table->id();
    $table->string('IES_attachment_id')->nullable(); // Link to parent record
    $table->string('project_id');
    $table->string('field_name'); // 'aadhar_card', 'fee_quotation', etc.
    $table->string('file_path');
    $table->string('file_name'); // User-provided or generated
    $table->text('description')->nullable();
    $table->string('serial_number', 2); // 01, 02, 03, etc. (2-digit)
    $table->string('public_url')->nullable();
    $table->timestamps();
    
    $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
    $table->index(['project_id', 'field_name']);
});
```

**Testing:**
- [ ] Run migrations successfully
- [ ] Verify table structures
- [ ] Test foreign key constraints

---

#### 7.2 Create Models for New Tables
**Files to Create:**
- `app/Models/OldProjects/IES/ProjectIESAttachmentFile.php`
- `app/Models/OldProjects/IIES/ProjectIIESAttachmentFile.php`
- `app/Models/OldProjects/IAH/ProjectIAHDocumentFile.php`
- `app/Models/OldProjects/ILP/ProjectILPDocumentFile.php`

**Base Model Structure:**
```php
class ProjectIESAttachmentFile extends Model
{
    protected $table = 'project_IES_attachment_files';
    
    protected $fillable = [
        'IES_attachment_id',
        'project_id',
        'field_name',
        'file_path',
        'file_name',
        'description',
        'serial_number',
        'public_url'
    ];
    
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
    
    public function parentAttachment()
    {
        return $this->belongsTo(ProjectIESAttachments::class, 'IES_attachment_id', 'IES_attachment_id');
    }
}
```

**Testing:**
- [ ] Models can be instantiated
- [ ] Relationships work correctly
- [ ] Fillable attributes work

---

#### 7.3 Implement File Naming Logic
**Create:** `app/Helpers/AttachmentFileNamingHelper.php`

**Implementation:**
```php
class AttachmentFileNamingHelper
{
    /**
     * Generate file name following pattern: {ProjectID}_{FieldName}_{serial}.{ext}
     * Or use user-provided name if provided
     */
    public static function generateFileName(
        $projectId, 
        $fieldName, 
        $extension, 
        $userProvidedName = null,
        $attachmentType = 'project' // 'project', 'IES', 'IIES', 'IAH', 'ILP', 'report'
    ) {
        // If user provided name, sanitize and use it
        if ($userProvidedName && !empty(trim($userProvidedName))) {
            $safeName = self::sanitizeFilename(trim($userProvidedName));
            return $safeName . '.' . $extension;
        }
        
        // Generate name using pattern
        $serialNumber = self::getNextSerialNumber($projectId, $fieldName, $attachmentType);
        $serial = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);
        
        return "{$projectId}_{$fieldName}_{$serial}.{$extension}";
    }
    
    /**
     * Get next serial number for project + field combination
     */
    private static function getNextSerialNumber($projectId, $fieldName, $attachmentType)
    {
        $modelClass = self::getModelClass($attachmentType);
        
        $lastFile = $modelClass::where('project_id', $projectId)
            ->where('field_name', $fieldName)
            ->orderBy('serial_number', 'desc')
            ->first();
        
        if ($lastFile && is_numeric($lastFile->serial_number)) {
            return (int)$lastFile->serial_number + 1;
        }
        
        return 1;
    }
    
    /**
     * Sanitize filename to prevent path traversal
     */
    private static function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $filename);
        $filename = trim($filename, '._');
        
        if (empty($filename)) {
            $filename = 'file';
        }
        
        return $filename;
    }
    
    /**
     * Get model class based on attachment type
     */
    private static function getModelClass($attachmentType)
    {
        $models = [
            'IES' => \App\Models\OldProjects\IES\ProjectIESAttachmentFile::class,
            'IIES' => \App\Models\OldProjects\IIES\ProjectIIESAttachmentFile::class,
            'IAH' => \App\Models\OldProjects\IAH\ProjectIAHDocumentFile::class,
            'ILP' => \App\Models\OldProjects\ILP\ProjectILPDocumentFile::class,
            'project' => \App\Models\OldProjects\ProjectAttachment::class,
            'report' => \App\Models\Reports\Monthly\ReportAttachment::class,
        ];
        
        return $models[$attachmentType] ?? $models['project'];
    }
}
```

**Testing:**
- [ ] Generate file names with pattern
- [ ] Generate file names with user-provided names
- [ ] Serial numbers increment correctly
- [ ] Serial numbers are 2-digit (01, 02, etc.)

---

#### 7.4 Update IES Attachments for Multiple Files
**Files to Update:**
- `app/Models/OldProjects/IES/ProjectIESAttachments.php`
- `app/Http/Controllers/Projects/IES/IESAttachmentsController.php`
- `resources/views/projects/partials/IES/attachments.blade.php`
- `resources/views/projects/partials/Edit/IES/attachments.blade.php`
- `resources/views/projects/partials/Show/IES/attachments.blade.php`

**Model Updates:**
```php
// Add relationship
public function files()
{
    return $this->hasMany(ProjectIESAttachmentFile::class, 'IES_attachment_id', 'IES_attachment_id');
}

// Update handleAttachments to support multiple files
public static function handleAttachments($request, $projectId)
{
    $fields = [
        'aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation',
        'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter'
    ];
    
    $projectDir = "project_attachments/IES/{$projectId}";
    Storage::disk('public')->makeDirectory($projectDir, 0755, true);
    
    $attachments = self::updateOrCreate(['project_id' => $projectId], []);
    
    foreach ($fields as $field) {
        if ($request->hasFile($field)) {
            $files = is_array($request->file($field)) 
                ? $request->file($field) 
                : [$request->file($field)];
            
            $fileNames = $request->input("{$field}_names", []);
            
            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    // Validate file type and size
                    if (!self::isValidFileType($file)) {
                        throw new \Exception("Invalid file type for {$field}");
                    }
                    if ($file->getSize() > 2097152) {
                        throw new \Exception("File size exceeds 2MB for {$field}");
                    }
                    
                    $userProvidedName = $fileNames[$index] ?? null;
                    $extension = $file->getClientOriginalExtension();
                    $fileName = AttachmentFileNamingHelper::generateFileName(
                        $projectId,
                        $field,
                        $extension,
                        $userProvidedName,
                        'IES'
                    );
                    
                    $filePath = $file->storeAs($projectDir, $fileName, 'public');
                    
                    if ($filePath) {
                        ProjectIESAttachmentFile::create([
                            'IES_attachment_id' => $attachments->IES_attachment_id,
                            'project_id' => $projectId,
                            'field_name' => $field,
                            'file_path' => $filePath,
                            'file_name' => $userProvidedName ?? $fileName,
                            'description' => $request->input("{$field}_descriptions.{$index}", ''),
                            'serial_number' => AttachmentFileNamingHelper::getNextSerialNumber($projectId, $field, 'IES'),
                            'public_url' => Storage::url($filePath),
                        ]);
                    }
                }
            }
        }
    }
    
    return $attachments;
}
```

**View Updates:**
- Add multiple file inputs per field
- Add "Add Another File" buttons
- Show existing files with delete options
- Add file name input fields (optional)

**Testing:**
- [ ] Upload single file per field
- [ ] Upload multiple files per field
- [ ] Files named correctly with pattern
- [ ] User-provided names work
- [ ] Serial numbers increment correctly
- [ ] Existing files display correctly
- [ ] Can delete individual files

---

#### 7.5 Update IIES Attachments for Multiple Files
**Same pattern as IES (7.4)**
- Update model, controller, views
- Use `IIES` attachment type in helper
- Follow same structure

**Testing:**
- [ ] All IES tests apply to IIES

---

#### 7.6 Update IAH Documents for Multiple Files
**Same pattern as IES (7.4)**
- Update model, controller, views
- Use `IAH` attachment type in helper
- Fields: `aadhar_copy`, `request_letter`, `medical_reports`, `other_docs`

**Testing:**
- [ ] All IES tests apply to IAH

---

#### 7.7 Update ILP Documents for Multiple Files
**Same pattern as IES (7.4)**
- Update model, controller, views
- Use `ILP` attachment type in helper
- Fields: `aadhar_doc`, `request_letter_doc`, `purchase_quotation_doc`, `other_doc`

**Testing:**
- [ ] All IES tests apply to ILP

---

#### 7.8 Update Regular Project Attachments for Multiple Files
**Files to Update:**
- `app/Http/Controllers/Projects/AttachmentController.php`
- `resources/views/projects/partials/attachments.blade.php`
- `resources/views/projects/partials/Edit/attachement.blade.php`
- `resources/views/projects/partials/Show/attachments.blade.php`

**Controller Updates:**
```php
public function store(Request $request, Project $project)
{
    if (!$request->hasFile('file') && !$request->hasFile('files')) {
        return;
    }
    
    $files = $request->hasFile('files') 
        ? $request->file('files') 
        : [$request->file('file')];
    
    $fileNames = $request->input('file_names', []);
    $descriptions = $request->input('attachment_descriptions', []);
    
    $projectType = $this->sanitizeProjectType($project->project_type);
    $storagePath = "project_attachments/{$projectType}/{$project->project_id}";
    Storage::disk('public')->makeDirectory($storagePath, 0755, true);
    
    foreach ($files as $index => $file) {
        if ($file && $file->isValid()) {
            // Validation...
            $userProvidedName = $fileNames[$index] ?? null;
            $extension = $file->getClientOriginalExtension();
            $fileName = AttachmentFileNamingHelper::generateFileName(
                $project->project_id,
                'attachment', // Field name for regular attachments
                $extension,
                $userProvidedName,
                'project'
            );
            
            $path = $file->storeAs($storagePath, $fileName, 'public');
            
            $attachment = new ProjectAttachment([
                'project_id' => $project->project_id,
                'file_name' => $userProvidedName ?? $fileName,
                'file_path' => $path,
                'description' => $descriptions[$index] ?? '',
                'public_url' => Storage::url($path),
            ]);
            
            $attachment->save();
        }
    }
    
    return redirect()->back()->with('success', 'Attachments uploaded successfully');
}
```

**Update Method:**
- Remove logic that deletes existing files
- Allow adding new files without deleting existing

**Testing:**
- [ ] Upload multiple files
- [ ] Add files without deleting existing
- [ ] File naming follows pattern
- [ ] User-provided names work

---

#### 7.9 Update Report Attachments File Naming
**Files to Update:**
- `app/Http/Controllers/Reports/Monthly/ReportAttachmentController.php`

**Update store method:**
```php
// Use helper for file naming
$fileName = AttachmentFileNamingHelper::generateFileName(
    $report->report_id,
    'attachment',
    $file->getClientOriginalExtension(),
    $request->input('file_name'),
    'report'
);
```

**Testing:**
- [ ] File names follow pattern: `{ReportID}_attachment_{serial}.{ext}`
- [ ] User-provided names work
- [ ] Serial numbers increment correctly

---

#### 7.10 Create Data Migration Script
**Create:** `database/migrations/YYYY_MM_DD_migrate_existing_attachments_to_multiple_files.php`

**Migration Logic:**
```php
// Migrate IES attachments
$iesAttachments = ProjectIESAttachments::all();
foreach ($iesAttachments as $attachment) {
    $fields = ['aadhar_card', 'fee_quotation', /* ... */];
    foreach ($fields as $field) {
        if ($attachment->$field) {
            ProjectIESAttachmentFile::create([
                'IES_attachment_id' => $attachment->IES_attachment_id,
                'project_id' => $attachment->project_id,
                'field_name' => $field,
                'file_path' => $attachment->$field,
                'file_name' => basename($attachment->$field),
                'serial_number' => '01',
                'public_url' => Storage::url($attachment->$field),
                'created_at' => $attachment->created_at,
                'updated_at' => $attachment->updated_at,
            ]);
        }
    }
}

// Similar for IIES, IAH, ILP
```

**Testing:**
- [ ] Migration runs successfully
- [ ] All existing files migrated
- [ ] File paths preserved
- [ ] No data loss

---

### Deliverables
- ✅ New database tables created
- ✅ New models created
- ✅ File naming helper implemented
- ✅ All attachment types support multiple files
- ✅ All views support multiple file inputs
- ✅ Existing data migrated
- ✅ Test results documented

---

## Phase 8: Optional Enhancements (Week 5)

**Priority:** 🔵 LOW  
**Estimated Time:** 3-5 days  
**Dependencies:** All previous phases complete

### Objectives
- Add upload progress indicators
- Improve UX
- Add advanced features

### Tasks

#### 7.1 Add Upload Progress Indicators
**Implementation:**
- Use XMLHttpRequest or fetch API with progress events
- Show progress bar during upload
- Display upload percentage
- Show success/error states

**Files to Update:**
- All attachment upload forms
- Add JavaScript for progress tracking
- Add CSS for progress bars

---

#### 7.2 Add File Preview for Images
**For IES/IIES image uploads:**
- Show thumbnail preview before upload
- Allow users to see what they're uploading
- Improve user confidence

---

#### 7.3 Add Batch Upload Feature
**For Reports:**
- Allow drag-and-drop multiple files
- Show all selected files before upload
- Upload all at once with progress

---

#### 7.4 Add File Versioning
**Optional:**
- Keep old versions when replacing files
- Allow viewing/downloading previous versions
- Add version history

---

### Deliverables
- ✅ Upload progress indicators (if implemented)
- ✅ File previews (if implemented)
- ✅ Other enhancements (if implemented)

---

## Implementation Timeline

| Phase | Duration | Priority | Status |
|-------|----------|----------|--------|
| Phase 1: Critical Storage & Path Fixes | 2 days | 🔴 Critical | ⏳ Pending |
| Phase 2: Security & Validation Fixes | 3 days | 🔴 Critical | ⏳ Pending |
| Phase 3: View & UI Fixes | 3 days | 🟡 High | ⏳ Pending |
| Phase 4: Code Quality & Standardization | 2 days | 🟡 High | ⏳ Pending |
| Phase 5: Enhancements & Polish | 3 days | 🟢 Medium | ⏳ Pending |
| Phase 6: Testing & Documentation | 2 days | 🟢 Medium | ⏳ Pending |
| Phase 7: Multiple File Upload Implementation | 10 days | 🔴 Critical | ⏳ Pending |
| Phase 8: Optional Enhancements | 3-5 days | 🔵 Low | ⏳ Optional |

**Total Estimated Time:** 28-30 days (5-6 weeks)

---

## Risk Assessment

### High Risk Items
1. **Storage Path Changes** - May affect existing files
   - **Mitigation:** Test thoroughly, create backup before changes
   
2. **Database Path Updates** - May need data migration
   - **Mitigation:** Create migration script to update existing paths

3. **Route Changes** - May break existing links
   - **Mitigation:** Keep old routes temporarily, add redirects

4. **Database Structure Changes (Phase 7)** - Major schema changes for multiple files
   - **Risk:** Data migration complexity, potential data loss
   - **Mitigation:** 
     - Create comprehensive backup before migration
     - Test migration script on copy of production data
     - Run migration in transaction with rollback capability
     - Verify all files migrated correctly
     - Keep old tables temporarily for rollback option

5. **File Naming Changes** - Existing files may have different naming
   - **Risk:** Broken references, file access issues
   - **Mitigation:**
     - Preserve existing file paths in database
     - Update file naming only for new uploads
     - Create mapping table if needed for old files

### Medium Risk Items
1. **Validation Changes** - May reject previously valid files
   - **Mitigation:** Review existing files, update validation if needed

2. **JavaScript Changes** - May break existing functionality
   - **Mitigation:** Test all views thoroughly

---

## Success Criteria

### Phase 1-2 (Critical)
- ✅ All files stored in correct locations
- ✅ All file URLs work correctly
- ✅ Server-side validation prevents invalid uploads
- ✅ No security vulnerabilities
- ✅ Storage paths standardized to `/project_type/project_id/`

### Phase 3-4 (High Priority)
- ✅ All views handle null/empty data gracefully
- ✅ JavaScript validation works for all upload types
- ✅ Routes are standardized
- ✅ Code is DRY (no duplication)

### Phase 5-6 (Medium Priority)
- ✅ Consistent UI/UX across all attachment types
- ✅ All tests pass
- ✅ Documentation is complete

### Phase 7 (Multiple File Upload - Critical)
- ✅ Database structures support multiple files per field
- ✅ All attachment types support multiple file uploads
- ✅ File naming follows pattern: `{ProjectID}_{FieldName}_{serial}.{ext}`
- ✅ User-provided names work correctly
- ✅ Serial numbers generate correctly (01, 02, 03...)
- ✅ Existing data migrated successfully
- ✅ All views support multiple file inputs
- ✅ Individual file deletion works

### Phase 8 (Optional)
- ✅ Enhanced features implemented (if chosen)

---

## Notes

1. **Backup Before Starting:** Create full backup of database and storage files
2. **Test Environment:** Test all changes in development environment first
3. **Incremental Deployment:** Deploy phases incrementally, not all at once
4. **Monitor Logs:** Watch application logs during and after deployment
5. **User Communication:** Inform users of any changes that affect their workflow
6. **Phase 7 Critical:** Multiple file upload implementation is a major change requiring:
   - Database schema changes
   - Data migration
   - Complete controller/model/view refactoring
   - Extensive testing
   - Plan for rollback if issues occur
7. **File Naming:** Ensure all new uploads follow the pattern, but preserve existing file names to avoid breaking links
8. **Serial Numbers:** Must be 2-digit (01, 02, 03...) and sequential per project+field combination

---

## Sign-off

**Prepared by:** Code Review System  
**Date:** January 2025  
**Status:** Ready for Implementation

---

**End of Implementation Plan**
