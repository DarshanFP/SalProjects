# Problem Tree Image Upload - Comprehensive Logging Implementation

**Date:** 2026-02-28  
**Purpose:** Debug production-only issue with Problem Tree image replacement  
**Status:** Logging Added - Ready for Production Testing

---

## Overview

This document describes the comprehensive logging added to the Problem Tree image upload process to diagnose why replacements fail in production but work in development.

### Problem Context

Based on the architectural audit (`PROBLEM_TREE_IMAGE_REPLACEMENT_ARCHITECTURAL_AUDIT.md`), the suspected root cause is a **delete-before-write race condition** that manifests differently in production vs development due to:

- Slower I/O in production (shared hosting vs local SSD)
- Filesystem caching delays
- Permission mismatches
- Silent failures due to `throw: false` in filesystem config

---

## Files Modified

### 1. `app/Http/Controllers/Projects/KeyInformationController.php`

#### Changes Made

**Updated Methods:**
- `store()` - Added logging before/after `storeProblemTreeImage()` and before `save()`
- `update()` - Added logging before/after `storeProblemTreeImage()` and before `save()`
- `storeProblemTreeImage()` - Added comprehensive step-by-step logging

#### Logging Added to `storeProblemTreeImage()`

**Entry Point:**
```php
Log::info('ProblemTreeImage - Starting upload process', [
    'project_id' => $project->project_id,
    'folder' => $folder,
    'uploaded_filename' => $file->getClientOriginalName(),
    'uploaded_size' => $file->getSize(),
    'uploaded_mime' => $file->getMimeType(),
    'old_path' => $project->problem_tree_file_path,
]);
```

**Delete Operation (Critical for Race Condition):**
```php
// Before delete
Log::info('ProblemTreeImage - Old file exists, attempting delete', [
    'project_id' => $project->project_id,
    'old_path' => $project->problem_tree_file_path,
]);

// After delete
Log::info('ProblemTreeImage - Delete operation completed', [
    'project_id' => $project->project_id,
    'old_path' => $project->problem_tree_file_path,
    'deleted' => $deleted,
    'still_exists_after_delete' => $disk->exists($project->problem_tree_file_path),
]);
```

**Write Operation (Optimized Path):**
```php
// Before write
Log::info('ProblemTreeImage - About to write optimized file', [
    'project_id' => $project->project_id,
    'path' => $path,
    'size' => strlen($optimized),
    'file_exists_before_write' => $disk->exists($path),
]);

// After write
Log::info('ProblemTreeImage - Write operation completed', [
    'project_id' => $project->project_id,
    'path' => $path,
    'put_result' => $putResult,
    'file_exists_after_write' => $disk->exists($path),
    'file_size_after_write' => $disk->exists($path) ? $disk->size($path) : null,
]);
```

**Write Operation (Original File Path):**
```php
// storeAs result
Log::info('ProblemTreeImage - storeAs completed', [
    'project_id' => $project->project_id,
    'path' => $path,
    'storeAs_result' => $path !== false,
    'file_exists_after' => $path !== false && $disk->exists($path),
    'file_size_after' => ($path !== false && $disk->exists($path)) ? $disk->size($path) : null,
]);
```

**Final Status:**
```php
Log::info('ProblemTreeImage - Process completed successfully', [
    'project_id' => $project->project_id,
    'final_path' => $project->problem_tree_file_path,
    'final_file_exists' => $disk->exists($project->problem_tree_file_path),
    'final_file_size' => $disk->exists($project->problem_tree_file_path) ? $disk->size($project->problem_tree_file_path) : null,
]);
```

### 2. `app/Services/ProblemTreeImageService.php`

#### Changes Made

**Updated Method:**
- `optimize()` - Added detailed logging for the entire optimization pipeline

#### Logging Added

**Entry:**
```php
Log::info('ProblemTreeImageService - Starting optimization', [
    'enabled' => $this->enabled,
    'max_dimension' => $this->maxDimension,
    'jpeg_quality' => $this->jpegQuality,
    'fallback_to_original' => $this->fallbackToOriginal,
    'original_filename' => $file->getClientOriginalName(),
    'original_size' => $file->getSize(),
]);
```

**Image Processing:**
```php
Log::info('ProblemTreeImageService - Image read successfully', [
    'width' => $image->width(),
    'height' => $image->height(),
]);

Log::info('ProblemTreeImageService - Image scaled', [
    'new_width' => $image->width(),
    'new_height' => $image->height(),
]);
```

**Success:**
```php
Log::info('ProblemTreeImageService - Optimization completed successfully', [
    'original_size' => $originalSize,
    'optimized_size' => $encodedSize,
    'size_reduction_percent' => $reduction,
]);
```

**Failures:**
```php
Log::warning('Problem Tree image optimization failed, will use original', [
    'path' => $path,
    'message' => $e->getMessage(),
    'exception_class' => get_class($e),
    'line' => $e->getLine(),
    'file' => $e->getFile(),
]);
```

---

## How to Use This Logging in Production

### Step 1: Deploy to Production

Push these changes to your production server.

### Step 2: Reproduce the Issue

1. Log into production application
2. Navigate to a project (e.g., DP-0036)
3. Go to Key Information edit page
4. Upload a new Problem Tree image
5. Submit the form
6. Note if the image appears changed or unchanged

### Step 3: Check Laravel Logs

```bash
# SSH into production server
ssh your-production-server

# Navigate to Laravel project
cd /path/to/your/laravel/project

# Tail the log in real-time (or check after upload)
tail -f storage/logs/laravel.log

# Or search for specific project
grep "DP-0036" storage/logs/laravel.log | grep "ProblemTreeImage"
```

### Step 4: Analyze Log Output

Look for the sequence of events. A **successful** upload should show:

```
[2026-02-28 ...] local.INFO: KeyInformationController@update - Problem tree image file detected {"project_id":"DP-0036",...}
[2026-02-28 ...] local.INFO: ProblemTreeImage - Starting upload process {"project_id":"DP-0036",...}
[2026-02-28 ...] local.INFO: ProblemTreeImage - Old file exists, attempting delete {...}
[2026-02-28 ...] local.INFO: ProblemTreeImage - Delete operation completed {"deleted":true,"still_exists_after_delete":false}
[2026-02-28 ...] local.INFO: ProblemTreeImageService - Starting optimization {...}
[2026-02-28 ...] local.INFO: ProblemTreeImageService - Image read successfully {...}
[2026-02-28 ...] local.INFO: ProblemTreeImageService - Image scaled {...}
[2026-02-28 ...] local.INFO: ProblemTreeImageService - Optimization completed successfully {...}
[2026-02-28 ...] local.INFO: ProblemTreeImage - About to write optimized file {...}
[2026-02-28 ...] local.INFO: ProblemTreeImage - Write operation completed {"put_result":true,"file_exists_after_write":true,...}
[2026-02-28 ...] local.INFO: ProblemTreeImage - Process completed successfully {"final_file_exists":true,...}
[2026-02-28 ...] local.INFO: KeyInformationController@update - After storeProblemTreeImage {...}
[2026-02-28 ...] local.INFO: KeyInformationController@update - About to save project {...}
[2026-02-28 ...] local.INFO: KeyInformationController@update - Data saved successfully {...}
```

---

## Key Diagnostic Points

### 1. Delete-Before-Write Race Condition

**Look for:**
```json
"still_exists_after_delete": false  // File successfully deleted
"file_exists_before_write": false   // About to write to empty path
"put_result": true                  // Write claimed success
"file_exists_after_write": false    // ❌ FILE NOT FOUND!
```

**What this means:**  
The write operation returned `true` but the file doesn't exist afterward. This is the silent failure due to `throw: false` in config.

### 2. Permission Denial

**Look for:**
```json
"deleted": false  // Delete failed
"still_exists_after_delete": true  // Old file still there
```

**What this means:**  
The PHP process doesn't have permission to delete the old file.

### 3. Filesystem Cache Lag

**Look for:**
```json
"deleted": true
"still_exists_after_delete": true  // ❌ ANOMALY!
```

**What this means:**  
Delete returned success but `exists()` check immediately after still reports file present. This indicates filesystem caching issues.

### 4. Write Failure (Optimized Path)

**Look for:**
```json
"put_result": false  // Write failed
"file_exists_after_write": false
"file_size_after_write": null
```

**What this means:**  
The `put()` operation failed (returned false).

### 5. Write Failure (Original File Path)

**Look for:**
```json
"storeAs_result": false  // storeAs failed
"file_exists_after": false
```

**What this means:**  
The `storeAs()` operation failed.

### 6. Optimization Failure

**Look for:**
```
[WARNING] Problem Tree image optimization failed, will use original
```

**What this means:**  
The image optimization crashed. The system falls back to storing the original file.

---

## Expected Failure Patterns in Production

Based on the architectural audit, we expect to see one of these patterns:

### Pattern A: Silent Write Failure
```
DELETE: success
WRITE: put_result = false (but no exception!)
FILE EXISTS AFTER: false
DB UPDATED: with non-existent path
```

### Pattern B: Filesystem Cache Confusion
```
DELETE: success
WRITE: put_result = true
FILE EXISTS IMMEDIATELY AFTER: false (cache lag)
FILE EXISTS LATER: true (cache catches up)
```

### Pattern C: Permission Denied on Write
```
DELETE: success
WRITE: put_result = false
ERROR LOG: Permission denied
```

### Pattern D: Same-Filename Overwrite Conflict
```
OLD PATH: project_attachments/.../DP-0036_Problem_Tree.jpg
NEW PATH: project_attachments/.../DP-0036_Problem_Tree.jpg  (SAME!)
DELETE: success
WRITE: attempts to write to now-empty inode
RESULT: race condition
```

---

## Next Steps After Log Analysis

Once you've identified the failure pattern from the logs, refer to the architectural audit document for the recommended fixes:

### If Pattern A (Silent Failure):
→ **Implement Fix #1:** Change `throw: false` to `throw: true` in `config/filesystems.php`

### If Pattern B or D (Cache/Race Condition):
→ **Implement Strategy A:** Write-then-delete with unique filenames (lines 432-528 in audit)

### If Pattern C (Permission Issue):
→ **Fix server permissions:**
```bash
# Check current permissions
ls -la storage/app/public/project_attachments/Development_Projects/DP-0036/

# Fix if needed
chown -R www-data:www-data storage/app/public
chmod -R 775 storage/app/public
```

---

## Production Monitoring Script

You can use this script to monitor uploads in real-time:

```bash
#!/bin/bash
# monitor-problem-tree-uploads.sh

# Follow logs for Problem Tree operations
tail -f storage/logs/laravel.log | grep --line-buffered "ProblemTreeImage" | while read line; do
    echo "$line"
    
    # Alert on failures
    if echo "$line" | grep -q '"file_exists_after_write":false'; then
        echo "⚠️  ALERT: Write verification failed!"
    fi
    
    if echo "$line" | grep -q '"put_result":false'; then
        echo "⚠️  ALERT: put() returned false!"
    fi
    
    if echo "$line" | grep -q '"storeAs_result":false'; then
        echo "⚠️  ALERT: storeAs() returned false!"
    fi
done
```

Usage:
```bash
chmod +x monitor-problem-tree-uploads.sh
./monitor-problem-tree-uploads.sh
```

---

## Log Extraction for Analysis

To extract all Problem Tree logs for a specific project:

```bash
grep "DP-0036" storage/logs/laravel.log | \
  grep "ProblemTreeImage" | \
  jq -r '[.time, .message, .context] | @tsv' > problem_tree_dp0036.log
```

To extract all Problem Tree failures:

```bash
grep "ProblemTreeImage" storage/logs/laravel.log | \
  grep -E 'false|fail|error' > problem_tree_failures.log
```

---

## Performance Impact

The added logging is **INFO** level and writes to the standard Laravel log file. 

**Estimated overhead:**
- ~15 log entries per upload
- ~2KB of log data per upload
- Negligible performance impact (<10ms)

**Recommendation:**  
After diagnosing the production issue, you may want to reduce logging verbosity by changing some `Log::info()` to `Log::debug()` or removing them entirely.

---

## Summary

✅ **Comprehensive logging added** to track every step of the Problem Tree image upload process  
✅ **No logic changes** - only observability improvements  
✅ **Ready for production deployment** to diagnose the race condition  
✅ **Clear diagnostic patterns** documented for quick issue identification  

The logs will help identify:
- Whether files are being deleted successfully
- Whether writes are actually completing
- What the filesystem reports immediately after operations
- Whether optimization is working
- Exact file sizes and paths at each step

Once the logs reveal the exact failure point, implement the corresponding fix from the architectural audit document.

---

**Next Actions:**
1. Deploy to production
2. Reproduce the issue
3. Collect logs
4. Analyze using patterns above
5. Implement appropriate fix from audit document
6. Re-test with logging still enabled
7. Monitor for 24-48 hours
8. Remove/reduce logging verbosity if desired
