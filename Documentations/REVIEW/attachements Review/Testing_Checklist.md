# Attachments System - Testing Checklist

**Date:** January 2025  
**Status:** In Progress  
**Purpose:** Comprehensive testing checklist for all attachment-related functionality

---

## Table of Contents

1. [Phase 1: Storage & Path Fixes Testing](#phase-1-storage--path-fixes-testing)
2. [Phase 2: Security & Validation Testing](#phase-2-security--validation-testing)
3. [Phase 3: View & UI Testing](#phase-3-view--ui-testing)
4. [Phase 4: Code Quality Testing](#phase-4-code-quality-testing)
5. [Phase 5: Enhancements Testing](#phase-5-enhancements-testing)
6. [Integration Testing](#integration-testing)
7. [Performance Testing](#performance-testing)
8. [Browser Compatibility Testing](#browser-compatibility-testing)

---

## Phase 1: Storage & Path Fixes Testing

### 1.1 IES Attachments Storage Path
- [ ] **Test:** Upload an IES attachment file
- [ ] **Verify:** File is stored in `storage/app/public/project_attachments/IES/{projectId}/`
- [ ] **Verify:** Database path does NOT have `public/` prefix
- [ ] **Verify:** File is accessible via web URL
- [ ] **Verify:** Download link works correctly
- [ ] **Expected Result:** File stored correctly, accessible, download works

### 1.2 IIES View Hardcoded Project ID
- [ ] **Test:** View IIES project with missing project_id
- [ ] **Verify:** No hardcoded fallback value is used
- [ ] **Verify:** Empty attachments array is returned
- [ ] **Verify:** Page doesn't crash
- [ ] **Expected Result:** Graceful handling, no wrong attachments displayed

### 1.3 IES View Null Checks
- [ ] **Test:** View IES project with null attachments
- [ ] **Verify:** Page loads without fatal errors
- [ ] **Verify:** "No file uploaded" message is displayed
- [ ] **Expected Result:** No errors, proper message displayed

### 1.4 File Existence Checks
- [ ] **Test:** View project with attachment where file is deleted from storage
- [ ] **Verify:** "File not found" warning is displayed
- [ ] **Verify:** Download link is not shown
- [ ] **Verify:** Page doesn't crash
- [ ] **Expected Result:** Warning message, no broken links

---

## Phase 2: Security & Validation Testing

### 2.1 File Type Validation - IES
- [ ] **Test:** Upload PDF file to IES project
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload JPG file to IES project
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload PNG file to IES project
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload .exe file to IES project
- [ ] **Expected:** File rejected with error message
- [ ] **Test:** Upload .php file to IES project
- [ ] **Expected:** File rejected with error message
- [ ] **Test:** Upload .txt file to IES project
- [ ] **Expected:** File rejected with error message

### 2.2 File Type Validation - IIES
- [ ] **Test:** Upload valid file types (PDF, JPG, PNG)
- [ ] **Expected:** Files upload successfully
- [ ] **Test:** Upload invalid file types
- [ ] **Expected:** Files rejected with error message

### 2.3 File Type Validation - IAH
- [ ] **Test:** Upload valid file types (PDF, JPG, PNG)
- [ ] **Expected:** Files upload successfully
- [ ] **Test:** Upload invalid file types
- [ ] **Expected:** Files rejected with error message

### 2.4 File Type Validation - ILP
- [ ] **Test:** Upload valid file types (PDF, JPG, PNG)
- [ ] **Expected:** Files upload successfully
- [ ] **Test:** Upload invalid file types
- [ ] **Expected:** Files rejected with error message

### 2.5 File Type Validation - Regular Attachments
- [ ] **Test:** Upload PDF file
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload DOC file
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload DOCX file
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload invalid file types
- [ ] **Expected:** Files rejected with error message

### 2.6 File Size Validation
- [ ] **Test:** Upload file under 5MB
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload file between 5MB and 7MB
- [ ] **Expected:** File uploads successfully (buffer zone)
- [ ] **Test:** Upload file over 7MB
- [ ] **Expected:** File rejected with error message
- [ ] **Test:** Upload exactly 7MB file
- [ ] **Expected:** File uploads successfully
- [ ] **Test:** Upload 7.1MB file
- [ ] **Expected:** File rejected with error message

### 2.7 Transaction Rollback
- [ ] **Test:** Upload file, simulate database error
- [ ] **Expected:** File is cleaned up from storage
- [ ] **Test:** Upload multiple files, one fails validation
- [ ] **Expected:** Previously uploaded files are cleaned up
- [ ] **Expected:** No orphaned files in storage

---

## Phase 3: View & UI Testing

### 3.1 JavaScript Validation - Client Side
- [ ] **Test:** Select invalid file type in browser
- [ ] **Expected:** Error message appears immediately
- [ ] **Expected:** File input is cleared
- [ ] **Test:** Select file over 5MB in browser
- [ ] **Expected:** Error message appears immediately
- [ ] **Expected:** File input is cleared
- [ ] **Test:** Select valid file
- [ ] **Expected:** Success indicator appears
- [ ] **Expected:** File name and size displayed

### 3.2 File Existence Checks in Views
- [ ] **Test:** View Regular attachments with existing file
- [ ] **Expected:** Download link works
- [ ] **Test:** View Regular attachments with missing file
- [ ] **Expected:** "File not found" warning displayed
- [ ] **Test:** View IES attachments with existing files
- [ ] **Expected:** All download links work
- [ ] **Test:** View IES attachments with missing files
- [ ] **Expected:** "File not found" warnings displayed
- [ ] **Test:** View IIES attachments with existing files
- [ ] **Expected:** All download links work
- [ ] **Test:** View IIES attachments with missing files
- [ ] **Expected:** "File not found" warnings displayed

### 3.3 Null Checks in Views
- [ ] **Test:** View project with null attachments
- [ ] **Expected:** Page loads without errors
- [ ] **Expected:** "No attachments" message displayed
- [ ] **Test:** View IES project with null attachments
- [ ] **Expected:** Page loads without errors
- [ ] **Expected:** "No file uploaded" messages displayed

---

## Phase 4: Code Quality Testing

### 4.1 Route Standardization
- [ ] **Test:** Click download link for project attachment
- [ ] **Expected:** Route `projects.attachments.download` works
- [ ] **Test:** Click download link for report attachment
- [ ] **Expected:** Route `reports.attachments.download` works
- [ ] **Test:** Click remove button for report attachment
- [ ] **Expected:** Route `reports.attachments.remove` works
- [ ] **Test:** Check all route references in views
- [ ] **Expected:** No broken route references

### 4.2 File Name Typo Fix
- [ ] **Test:** Load edit project page
- [ ] **Expected:** Attachment partial loads correctly
- [ ] **Expected:** No "file not found" errors
- [ ] **Test:** Check all includes
- [ ] **Expected:** All references to attachment.blade.php work

### 4.3 JavaScript Extraction
- [ ] **Test:** Validate file in create view
- [ ] **Expected:** Shared validation functions work
- [ ] **Test:** Validate file in edit view
- [ ] **Expected:** Shared validation functions work
- [ ] **Test:** Validate file in IES view
- [ ] **Expected:** Shared validation functions work
- [ ] **Test:** Check browser console
- [ ] **Expected:** No JavaScript errors

---

## Phase 5: Enhancements Testing

### 5.1 Configuration File
- [ ] **Test:** Change max_size in config/attachments.php
- [ ] **Expected:** New limit is applied
- [ ] **Test:** Change allowed file types in config
- [ ] **Expected:** New types are accepted/rejected correctly
- [ ] **Test:** Change error messages in config
- [ ] **Expected:** New messages are displayed

### 5.2 File Type Icons
- [ ] **Test:** View attachment with PDF file
- [ ] **Expected:** PDF icon (red) is displayed
- [ ] **Test:** View attachment with DOC file
- [ ] **Expected:** Word icon (blue) is displayed
- [ ] **Test:** View attachment with DOCX file
- [ ] **Expected:** Word icon (blue) is displayed
- [ ] **Test:** View attachment with JPG file
- [ ] **Expected:** Image icon is displayed
- [ ] **Test:** View attachment with unknown file type
- [ ] **Expected:** Default file icon is displayed

### 5.3 Error Messages
- [ ] **Test:** Upload invalid file type
- [ ] **Expected:** User-friendly error message from config
- [ ] **Test:** Upload file over size limit
- [ ] **Expected:** User-friendly error message from config
- [ ] **Test:** Check error message format
- [ ] **Expected:** Consistent format across all attachment types

---

## Integration Testing

### 6.1 End-to-End Upload Flow
- [ ] **Test:** Create new project, upload attachment
- [ ] **Expected:** File uploaded, stored, database updated
- [ ] **Test:** View project, download attachment
- [ ] **Expected:** File downloads correctly
- [ ] **Test:** Edit project, replace attachment
- [ ] **Expected:** Old file deleted, new file stored
- [ ] **Test:** Delete project with attachments
- [ ] **Expected:** Attachments handled correctly (if cascade delete implemented)

### 6.2 Multiple Attachment Types
- [ ] **Test:** Create IES project with all attachment types
- [ ] **Expected:** All files upload successfully
- [ ] **Test:** Create IIES project with all attachment types
- [ ] **Expected:** All files upload successfully
- [ ] **Test:** Create IAH project with all document types
- [ ] **Expected:** All files upload successfully
- [ ] **Test:** Create ILP project with all document types
- [ ] **Expected:** All files upload successfully

### 6.3 Report Attachments
- [ ] **Test:** Create monthly report, upload attachments
- [ ] **Expected:** Files upload successfully
- [ ] **Test:** View report, download attachments
- [ ] **Expected:** Download links work
- [ ] **Test:** Edit report, remove attachment
- [ ] **Expected:** Attachment removed, file deleted

---

## Performance Testing

### 7.1 File Upload Performance
- [ ] **Test:** Upload 7MB file
- [ ] **Expected:** Upload completes within reasonable time (< 30 seconds)
- [ ] **Test:** Upload multiple files simultaneously
- [ ] **Expected:** All files upload successfully
- [ ] **Test:** Upload file during high server load
- [ ] **Expected:** Upload still works (may be slower)

### 7.2 Storage Performance
- [ ] **Test:** Check storage directory structure
- [ ] **Expected:** Files organized correctly by project type and ID
- [ ] **Test:** Check for orphaned files
- [ ] **Expected:** No orphaned files in storage
- [ ] **Test:** Check disk space usage
- [ ] **Expected:** Reasonable disk usage

---

## Browser Compatibility Testing

### 8.1 Desktop Browsers
- [ ] **Test:** Chrome - Upload, view, download
- [ ] **Expected:** All functionality works
- [ ] **Test:** Firefox - Upload, view, download
- [ ] **Expected:** All functionality works
- [ ] **Test:** Safari - Upload, view, download
- [ ] **Expected:** All functionality works
- [ ] **Test:** Edge - Upload, view, download
- [ ] **Expected:** All functionality works

### 8.2 Mobile Browsers
- [ ] **Test:** Mobile Chrome - Upload, view, download
- [ ] **Expected:** All functionality works
- [ ] **Test:** Mobile Safari - Upload, view, download
- [ ] **Expected:** All functionality works

---

## Security Testing

### 9.1 File Upload Security
- [ ] **Test:** Attempt to upload malicious file (.php, .exe)
- [ ] **Expected:** File rejected by server-side validation
- [ ] **Test:** Attempt path traversal in filename
- [ ] **Expected:** Filename sanitized
- [ ] **Test:** Attempt to upload file with script tags
- [ ] **Expected:** File rejected or sanitized

### 9.2 Access Control
- [ ] **Test:** Attempt to download attachment without authentication
- [ ] **Expected:** Access denied
- [ ] **Test:** Attempt to download another user's attachment
- [ ] **Expected:** Access denied (if implemented)
- [ ] **Test:** Check file permissions
- [ ] **Expected:** Files not publicly accessible without proper routes

---

## Regression Testing

### 10.1 Existing Functionality
- [ ] **Test:** All existing projects still work
- [ ] **Expected:** No broken functionality
- [ ] **Test:** All existing attachments still accessible
- [ ] **Expected:** Download links work
- [ ] **Test:** All existing views still render
- [ ] **Expected:** No broken views

---

## Test Results Summary

### Test Execution Date: _______________
### Tester: _______________

### Summary:
- **Total Tests:** ___
- **Passed:** ___
- **Failed:** ___
- **Skipped:** ___
- **Pass Rate:** ___%

### Critical Issues Found:
1. 
2. 
3. 

### Minor Issues Found:
1. 
2. 
3. 

### Notes:
- 

---

## Sign-off

- [ ] **Developer:** All tests passed
- [ ] **QA:** All tests passed
- [ ] **Product Owner:** Approved for production

**Date:** _______________  
**Signatures:** _______________

---

**End of Testing Checklist**
