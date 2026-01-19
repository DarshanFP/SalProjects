# Production Deployment Guide - Multiple File Upload System

**Date:** January 2025  
**Purpose:** Step-by-step guide for deploying the multiple file upload system to production  
**Status:** Ready for Production Deployment

---

## ⚠️ IMPORTANT: Pre-Deployment Checklist

Before proceeding with production deployment, ensure you have:

- [ ] **Database Backup** - Full backup of production database
- [ ] **Storage Backup** - Backup of `storage/app/public/project_attachments/` directory
- [ ] **Code Backup** - Git commit/tag of current production code
- [ ] **Maintenance Window** - Scheduled downtime window (recommended: 30-60 minutes)
- [ ] **Rollback Plan** - Know how to rollback if issues occur
- [ ] **Testing Environment** - Tested in staging/dev environment first

---

## 📋 Deployment Steps

### Step 1: Pre-Deployment Verification

#### 1.1 Verify Current Production State

```bash
# Connect to production server
ssh user@production-server

# Navigate to project directory
cd /path/to/production/project

# Check current Git status
git status

# Check current branch
git branch

# Verify current Laravel version
php artisan --version
```

#### 1.2 Check Current Database State

```sql
-- Connect to production database
-- Run these queries to record current state

-- Count existing records
SELECT 
    'IES' as type, COUNT(*) as count FROM project_IES_attachments
UNION ALL
SELECT 'IIES', COUNT(*) FROM project_IIES_attachments
UNION ALL
SELECT 'IAH', COUNT(*) FROM project_IAH_documents
UNION ALL
SELECT 'ILP', COUNT(*) FROM project_ILP_attached_docs;

-- Sample file paths (to verify format)
SELECT project_id, aadhar_card, fee_quotation 
FROM project_IES_attachments 
WHERE aadhar_card IS NOT NULL 
LIMIT 5;
```

**Record these numbers** - You'll verify them after migration.

---

### Step 2: Create Backups

#### 2.1 Database Backup

```bash
# MySQL/MariaDB backup
mysqldump -u [username] -p [database_name] > backup_attachments_$(date +%Y%m%d_%H%M%S).sql

# Example:
mysqldump -u root -p sal_projects > backup_attachments_20250108_143000.sql

# Verify backup file was created
ls -lh backup_attachments_*.sql
```

#### 2.2 Storage Backup

```bash
# Backup storage directory
cd /path/to/production/project

# Create backup directory
mkdir -p backups/storage_backup_$(date +%Y%m%d_%H%M%S)

# Copy storage directory
cp -r storage/app/public/project_attachments backups/storage_backup_$(date +%Y%m%d_%H%M%S)/

# Verify backup
ls -lh backups/storage_backup_*/
```

#### 2.3 Code Backup (Git Tag)

```bash
# Create a tag for current production state
git tag -a v1.0.0-pre-multiple-files -m "Production state before multiple files deployment"

# Push tag to remote
git push origin v1.0.0-pre-multiple-files

# Verify tag created
git tag -l
```

---

### Step 3: Deploy Code Changes

#### 3.1 Pull Latest Code

```bash
# Navigate to project directory
cd /path/to/production/project

# Pull latest code from repository
git pull origin main  # or your production branch

# Verify new files are present
ls -la database/migrations/2026_01_08_*
ls -la app/Models/OldProjects/*/Project*AttachmentFile.php
ls -la app/Helpers/AttachmentFileNamingHelper.php
```

#### 3.2 Install Dependencies (if needed)

```bash
# Install/update Composer dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache
```

---

### Step 4: Run Migrations

#### 4.1 Check Migration Status

```bash
# Check which migrations are pending
php artisan migrate:status | grep "2026_01_08"

# Expected output should show 5 migrations as "Pending":
# - create_project_ies_attachment_files_table
# - create_project_iies_attachment_files_table
# - create_project_iah_document_files_table
# - create_project_ilp_document_files_table
# - migrate_existing_attachments_to_multiple_files
```

#### 4.2 Run Migrations

```bash
# Run migrations (this will create tables and migrate data)
php artisan migrate

# Expected output:
# INFO  Running migrations.
# 2026_01_08_134425_create_project_ies_attachment_files_table ... DONE
# 2026_01_08_134429_create_project_iies_attachment_files_table ... DONE
# 2026_01_08_134432_create_project_iah_document_files_table ... DONE
# 2026_01_08_134435_create_project_ilp_document_files_table ... DONE
# 2026_01_08_135526_migrate_existing_attachments_to_multiple_files ... DONE
```

#### 4.3 Verify Migration Success

```bash
# Check new tables exist and have data
php artisan tinker --execute="
echo 'Migration Verification:' . PHP_EOL;
echo 'IES Files: ' . DB::table('project_IES_attachment_files')->count() . PHP_EOL;
echo 'IIES Files: ' . DB::table('project_IIES_attachment_files')->count() . PHP_EOL;
echo 'IAH Files: ' . DB::table('project_IAH_document_files')->count() . PHP_EOL;
echo 'ILP Files: ' . DB::table('project_ILP_document_files')->count() . PHP_EOL;
echo PHP_EOL;
echo 'Original Tables (should be unchanged):' . PHP_EOL;
echo 'IES Attachments: ' . DB::table('project_IES_attachments')->count() . PHP_EOL;
echo 'IIES Attachments: ' . DB::table('project_IIES_attachments')->count() . PHP_EOL;
echo 'IAH Documents: ' . DB::table('project_IAH_documents')->count() . PHP_EOL;
echo 'ILP Documents: ' . DB::table('project_ILP_attached_docs')->count() . PHP_EOL;
"
```

**Expected Results:**
- New tables should have migrated file records
- Original tables should have same count as before migration
- No errors in output

---

### Step 5: Verify Data Integrity

#### 5.1 Check Sample Migrated Files

```bash
# Check sample migrated files
php artisan tinker --execute="
echo 'Sample Migrated Files:' . PHP_EOL;
DB::table('project_IES_attachment_files')->limit(3)->get(['project_id', 'field_name', 'file_name', 'serial_number'])->each(function(\$f) {
    echo \"  - {$f->project_id} / {$f->field_name} / {$f->file_name} (Serial: {$f->serial_number})\" . PHP_EOL;
});
"
```

#### 5.2 Verify File Paths

```bash
# Check file paths are correct (without 'public/' prefix)
php artisan tinker --execute="
\$file = DB::table('project_IES_attachment_files')->first();
if (\$file) {
    echo 'Sample file path: ' . \$file->file_path . PHP_EOL;
    echo 'Path starts with public/: ' . (strpos(\$file->file_path, 'public/') === 0 ? 'YES (WRONG)' : 'NO (CORRECT)') . PHP_EOL;
}
"
```

**Expected:** File paths should NOT start with "public/"

#### 5.3 Verify File Existence

```bash
# Check if migrated files exist in storage
php artisan tinker --execute="
use Illuminate\Support\Facades\Storage;
\$file = DB::table('project_IES_attachment_files')->first();
if (\$file) {
    \$exists = Storage::disk('public')->exists(\$file->file_path);
    echo 'File exists in storage: ' . (\$exists ? 'YES' : 'NO') . PHP_EOL;
    echo 'File path: ' . \$file->file_path . PHP_EOL;
}
"
```

**Expected:** Files should exist in storage

---

### Step 6: Test Functionality

#### 6.1 Test File Display

1. **Access a project with existing attachments** (IES, IIES, IAH, or ILP)
2. **View the project** - Verify existing files display correctly
3. **Check file links** - Verify View/Download buttons work
4. **Verify file icons** - Check icons display correctly

#### 6.2 Test Multiple File Upload

1. **Edit a project** with attachments
2. **Upload a new file** to an existing field
3. **Click "Add Another File"** button
4. **Upload second file** to same field
5. **Submit form**
6. **Verify both files** appear in show view
7. **Check file names** follow pattern: `{ProjectID}_{FieldName}_{serial}.{ext}`

#### 6.3 Test File Naming

1. **Upload file with custom name** (enter name in "Custom file name" field)
2. **Upload file without name** (leave blank)
3. **Verify:**
   - Custom name is used when provided
   - Pattern name is used when not provided
   - Serial numbers increment correctly (01, 02, 03...)

---

### Step 7: Post-Deployment Verification

#### 7.1 Check Application Logs

```bash
# Check for any errors in logs
tail -100 storage/logs/laravel.log | grep -i "error\|exception\|failed"

# Check migration logs
tail -50 storage/logs/laravel.log | grep -i "migrated\|migration"
```

**Expected:** No critical errors, migration success messages

#### 7.2 Verify Database Integrity

```sql
-- Check foreign key constraints
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = '[your_database_name]'
AND TABLE_NAME LIKE '%attachment_files%'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Check indexes exist
SHOW INDEX FROM project_IES_attachment_files;
SHOW INDEX FROM project_IIES_attachment_files;
SHOW INDEX FROM project_IAH_document_files;
SHOW INDEX FROM project_ILP_document_files;
```

#### 7.3 Performance Check

```bash
# Test query performance
php artisan tinker --execute="
\$start = microtime(true);
\$files = DB::table('project_IES_attachment_files')
    ->where('project_id', 'IES-0001')
    ->where('field_name', 'aadhar_card')
    ->get();
\$time = (microtime(true) - \$start) * 1000;
echo 'Query time: ' . round(\$time, 2) . 'ms' . PHP_EOL;
echo 'Files found: ' . \$files->count() . PHP_EOL;
"
```

**Expected:** Query should complete quickly (< 100ms)

---

## 🔄 Rollback Procedure

If issues occur and you need to rollback:

### Option 1: Rollback Migrations Only

```bash
# Rollback only the new migrations
php artisan migrate:rollback --step=5

# This will:
# - Drop the 4 new tables
# - Old data remains intact
# - System returns to single-file-per-field mode
```

### Option 2: Full Rollback (Code + Database)

```bash
# 1. Rollback code
git checkout v1.0.0-pre-multiple-files

# 2. Rollback database
mysql -u [username] -p [database_name] < backup_attachments_YYYYMMDD_HHMMSS.sql

# 3. Restore storage (if needed)
cp -r backups/storage_backup_YYYYMMDD_HHMMSS/project_attachments storage/app/public/

# 4. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## 📊 Post-Deployment Monitoring

### Monitor for 24-48 Hours

#### 1. Error Monitoring
- Check application logs daily
- Monitor for any file upload errors
- Watch for database errors

#### 2. User Feedback
- Monitor user reports
- Check for any complaints about file uploads
- Verify file downloads work

#### 3. Performance Monitoring
- Monitor database query performance
- Check storage usage
- Monitor server resources

#### 4. Data Integrity Checks

```sql
-- Daily check: Verify file counts match
SELECT 
    'IES' as type,
    (SELECT COUNT(*) FROM project_IES_attachments WHERE aadhar_card IS NOT NULL) as old_count,
    (SELECT COUNT(*) FROM project_IES_attachment_files WHERE field_name = 'aadhar_card') as new_count
UNION ALL
SELECT 
    'IIES',
    (SELECT COUNT(*) FROM project_IIES_attachments WHERE iies_aadhar_card IS NOT NULL),
    (SELECT COUNT(*) FROM project_IIES_attachment_files WHERE field_name = 'iies_aadhar_card')
UNION ALL
SELECT 
    'IAH',
    (SELECT COUNT(*) FROM project_IAH_documents WHERE aadhar_copy IS NOT NULL),
    (SELECT COUNT(*) FROM project_IAH_document_files WHERE field_name = 'aadhar_copy')
UNION ALL
SELECT 
    'ILP',
    (SELECT COUNT(*) FROM project_ILP_attached_docs WHERE aadhar_doc IS NOT NULL),
    (SELECT COUNT(*) FROM project_ILP_document_files WHERE field_name = 'aadhar_doc');
```

---

## ✅ Deployment Checklist

Use this checklist during deployment:

### Pre-Deployment
- [ ] Database backup created and verified
- [ ] Storage backup created and verified
- [ ] Git tag created for rollback
- [ ] Maintenance window scheduled
- [ ] Team notified of deployment

### Deployment
- [ ] Code deployed to production
- [ ] Dependencies installed
- [ ] Caches cleared
- [ ] Migrations run successfully
- [ ] Migration verification passed
- [ ] Data integrity verified

### Post-Deployment
- [ ] Functionality tested
- [ ] File uploads tested
- [ ] File downloads tested
- [ ] Logs checked for errors
- [ ] Performance verified
- [ ] Users notified of new features

### Monitoring (24-48 hours)
- [ ] Error logs monitored
- [ ] User feedback collected
- [ ] Performance metrics checked
- [ ] Data integrity verified daily

---

## 🆘 Troubleshooting

### Issue: Migration Fails

**Symptoms:** Migration stops with error

**Solution:**
1. Check error message in logs
2. Verify database connection
3. Check disk space
4. Verify file permissions
5. Check if tables already exist

**Rollback:**
```bash
php artisan migrate:rollback --step=1
```

### Issue: Files Not Migrating

**Symptoms:** New tables created but empty

**Possible Causes:**
1. File paths in database have "public/" prefix
2. Files don't exist in storage
3. Migration script error

**Solution:**
1. Check file paths in database
2. Verify files exist in storage
3. Check migration logs
4. Re-run migration (it's idempotent)

### Issue: Files Not Displaying

**Symptoms:** Files migrated but not showing in views

**Possible Causes:**
1. Views not updated
2. Controller not returning files
3. Cache issues

**Solution:**
1. Clear all caches: `php artisan cache:clear && php artisan view:clear`
2. Verify views are updated
3. Check controller `show()` methods
4. Verify file paths in database

### Issue: Upload Not Working

**Symptoms:** Can't upload multiple files

**Possible Causes:**
1. JavaScript errors
2. Form not submitting arrays
3. Validation errors

**Solution:**
1. Check browser console for errors
2. Verify form has `name="field[]"` format
3. Check server logs for validation errors
4. Verify file size/type limits

---

## 📞 Support Contacts

If issues occur during deployment:

1. **Check Documentation:**
   - `Migration_Safety_Analysis.md` - Safety verification
   - `Migration_Execution_Report.md` - Execution details
   - `Phase7_Completion_Summary.md` - Implementation details

2. **Review Logs:**
   - Application logs: `storage/logs/laravel.log`
   - Migration logs: Check for "Migrated" entries

3. **Database Queries:**
   - Use verification queries in Step 4.3
   - Check data integrity queries in Step 7.2

---

## 📝 Deployment Notes

### Important Notes:

1. **Migration is Idempotent**
   - Safe to run multiple times
   - Checks for existing files before creating
   - Won't create duplicates

2. **No Data Loss**
   - Old tables remain untouched
   - Files remain in storage
   - Both old and new structures work

3. **Path Format**
   - New tables store paths without "public/" prefix
   - Migration script handles conversion automatically
   - Files remain in same storage location

4. **Backward Compatibility**
   - Old views still work (reading from old columns)
   - New views read from new tables
   - Both can coexist

---

## 🎯 Success Criteria

Deployment is successful when:

- ✅ All migrations completed without errors
- ✅ New tables created and populated
- ✅ Existing data preserved
- ✅ Files accessible through new structure
- ✅ Multiple file uploads working
- ✅ No errors in logs
- ✅ Performance acceptable
- ✅ Users can upload/view/download files

---

## 📅 Deployment Timeline

**Estimated Time:** 30-60 minutes

- **Backup:** 5-10 minutes
- **Code Deployment:** 5 minutes
- **Migrations:** 5-15 minutes (depends on data volume)
- **Verification:** 10-15 minutes
- **Testing:** 10-15 minutes
- **Monitoring Setup:** 5 minutes

**Total:** ~45 minutes (with buffer)

---

## 🔐 Security Considerations

1. **File Permissions**
   - Ensure storage directory has correct permissions (755)
   - Verify web server can write to storage

2. **Database Access**
   - Use read-only user for verification queries if possible
   - Limit migration script execution time

3. **Backup Security**
   - Store backups securely
   - Encrypt sensitive backup files
   - Don't commit backups to Git

---

## 📚 Additional Resources

- **Migration Safety Analysis:** `Migration_Safety_Analysis.md`
- **Migration Execution Report:** `Migration_Execution_Report.md`
- **Phase 7 Completion Summary:** `Phase7_Completion_Summary.md`
- **Implementation Summary:** `Implementation_Summary.md`
- **Testing Checklist:** `Testing_Checklist.md`

---

**Document Status:** ✅ Ready for Production  
**Last Updated:** January 2025  
**Version:** 1.0

---

**End of Production Deployment Guide**
