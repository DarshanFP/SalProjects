# ✅ Database Migration Execution Checklist

**Date:** January 2025  
**Status:** ⚠️ **READY FOR EXECUTION**  
**Pending Migrations:** 29 migrations  
**Data Migrations Required:** 2 critical data migrations  
**Existing Data to Migrate:** 5 attachment files across 4 project types

---

## 🎯 QUICK START GUIDE

### Pre-Execution Steps (5 minutes)

1. **Backup Database:**
   ```bash
   mysqldump -u root -p projectsReports > backup_before_migrations_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Verify Current State:**
   ```bash
   php artisan migrate:status | grep Pending | wc -l
   # Should show: 29
   ```

3. **Check Disk Space:**
   ```bash
   df -h
   # Ensure sufficient space for backup and migrations
   ```

---

## 📋 PHASE-WISE EXECUTION CHECKLIST

### **PHASE 1: Projects Table Enhancements** 🔴 **HIGH PRIORITY**

**Duration:** ~15 minutes  
**Migrations:** 4  
**Risk Level:** 🟢 LOW (adds columns only)

#### Step 1.1: Run Migrations
```bash
php artisan migrate --path=database/migrations/2026_01_07_000001_add_local_contribution_to_projects_table.php
php artisan migrate --path=database/migrations/2026_01_07_162317_make_in_charge_nullable_in_projects_table.php
php artisan migrate --path=database/migrations/2026_01_07_172101_add_predecessor_project_id_to_projects_table.php
php artisan migrate --path=database/migrations/2026_01_07_182657_add_key_information_fields_to_projects_table.php
```

#### Step 1.2: Verification
```bash
php artisan tinker
>>> $cols = Schema::getColumnListing('projects');
>>> echo 'local_contribution: ' . (in_array('local_contribution', $cols) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'predecessor_project_id: ' . (in_array('predecessor_project_id', $cols) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'initial_information: ' . (in_array('initial_information', $cols) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'target_beneficiaries: ' . (in_array('target_beneficiaries', $cols) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'general_situation: ' . (in_array('general_situation', $cols) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'need_of_project: ' . (in_array('need_of_project', $cols) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;

# Check in_charge is nullable
>>> $inCharge = DB::select("SHOW COLUMNS FROM projects WHERE Field = 'in_charge'")[0];
>>> echo 'in_charge nullable: ' . ($inCharge->Null == 'YES' ? '✅ YES' : '❌ NO') . PHP_EOL;
```

#### ✅ Phase 1 Checklist
- [ ] All 4 migrations executed successfully
- [ ] `local_contribution` column exists
- [ ] `in_charge` column is nullable
- [ ] `predecessor_project_id` column exists with foreign key
- [ ] All 4 Key Information fields exist (`initial_information`, `target_beneficiaries`, `general_situation`, `need_of_project`)
- [ ] All columns are nullable (TEXT type)
- [ ] Columns are in correct order (before 'goal' column)
- [ ] No errors in `storage/logs/laravel.log`

---

### **PHASE 2: Attachment Files System** 🔴 **HIGH PRIORITY**

**Duration:** ~30 minutes  
**Migrations:** 5 (4 tables + 1 data migration)  
**Risk Level:** 🟢 LOW (adds tables, then copies data - non-destructive)  
**Existing Data:** 5 files need migration (IES: 1, IIES: 2, IAH: 1, ILP: 1)

#### Step 2.1: Create Attachment File Tables (4 migrations)

```bash
php artisan migrate --path=database/migrations/2026_01_08_134425_create_project_ies_attachment_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_134429_create_project_iies_attachment_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_134432_create_project_iah_document_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_134435_create_project_ilp_document_files_table.php
```

**Verification:**
```bash
php artisan tinker
>>> echo 'project_IES_attachment_files: ' . (Schema::hasTable('project_IES_attachment_files') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'project_IIES_attachment_files: ' . (Schema::hasTable('project_IIES_attachment_files') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'project_IAH_document_files: ' . (Schema::hasTable('project_IAH_document_files') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'project_ILP_document_files: ' . (Schema::hasTable('project_ILP_document_files') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
```

#### Step 2.2: Run Data Migration ⚠️ **CRITICAL - MIGRATES EXISTING DATA**

**⚠️ IMPORTANT:** This migration copies existing attachment files to new structure.

**Existing Data Found:**
- IES attachments: 1 file
- IIES attachments: 2 files
- IAH documents: 1 file
- ILP documents: 1 file
- **Total: 5 files to migrate**

```bash
php artisan migrate --path=database/migrations/2026_01_08_135526_migrate_existing_attachments_to_multiple_files.php
```

**Check Migration Logs:**
```bash
tail -200 storage/logs/laravel.log | grep -i "migration\|attachment\|IES\|IIES\|IAH\|ILP"
```

**Verification:**
```bash
php artisan tinker

# Check IES migration
>>> $iesOld = DB::table('project_IES_attachments')->whereNotNull('aadhar_card')->orWhereNotNull('fee_quotation')->count();
>>> $iesNew = DB::table('project_IES_attachment_files')->count();
>>> echo "IES - Old records with files: $iesOld, New records: $iesNew" . PHP_EOL;
>>> echo "IES Status: " . ($iesOld == 0 || $iesNew >= 1 ? '✅ OK' : '❌ ISSUE') . PHP_EOL;

# Check IIES migration
>>> $iiesOld = DB::table('project_IIES_attachments')->whereNotNull('iies_aadhar_card')->count();
>>> $iiesNew = DB::table('project_IIES_attachment_files')->count();
>>> echo "IIES - Old records with files: $iiesOld, New records: $iiesNew" . PHP_EOL;
>>> echo "IIES Status: " . ($iiesOld == 0 || $iiesNew >= 2 ? '✅ OK' : '❌ ISSUE') . PHP_EOL;

# Check IAH migration
>>> $iahOld = DB::table('project_IAH_documents')->whereNotNull('aadhar_copy')->count();
>>> $iahNew = DB::table('project_IAH_document_files')->count();
>>> echo "IAH - Old records with files: $iahOld, New records: $iahNew" . PHP_EOL;
>>> echo "IAH Status: " . ($iahOld == 0 || $iahNew >= 1 ? '✅ OK' : '❌ ISSUE') . PHP_EOL;

# Check ILP migration
>>> $ilpOld = DB::table('project_ILP_attached_docs')->whereNotNull('aadhar_doc')->count();
>>> $ilpNew = DB::table('project_ILP_document_files')->count();
>>> echo "ILP - Old records with files: $ilpOld, New records: $ilpNew" . PHP_EOL;
>>> echo "ILP Status: " . ($ilpOld == 0 || $ilpNew >= 1 ? '✅ OK' : '❌ ISSUE') . PHP_EOL;

# Verify old data still exists (non-destructive)
>>> echo "Old data preserved: " . ($iesOld > 0 || $iiesOld > 0 || $iahOld > 0 || $ilpOld > 0 ? '✅ YES' : 'N/A') . PHP_EOL;

# Check sample migrated record
>>> $sample = DB::table('project_IES_attachment_files')->first();
>>> if($sample) { echo "Sample IES record: project_id={$sample->project_id}, field_name={$sample->field_name}, file_path={$sample->file_path}" . PHP_EOL; } else { echo "No IES records migrated yet" . PHP_EOL; }
```

#### ✅ Phase 2 Checklist
- [ ] All 4 attachment file tables created
- [ ] Data migration executed successfully
- [ ] IES files migrated (should have >= 1 record)
- [ ] IIES files migrated (should have >= 2 records)
- [ ] IAH files migrated (should have >= 1 record)
- [ ] ILP files migrated (should have >= 1 record)
- [ ] Old data still exists in source tables (non-destructive)
- [ ] File paths are correct (without 'public/' prefix)
- [ ] Serial numbers are set ('01' for migrated files)
- [ ] No errors in Laravel log
- [ ] Migration logs show successful completion

---

### **PHASE 3: Status Tracking & Completion Status** 🟡 **MEDIUM PRIORITY**

**Duration:** ~15 minutes  
**Migrations:** 2  
**Risk Level:** 🟢 LOW (adds table and column)

#### Step 3.1: Create Status History Table

```bash
php artisan migrate --path=database/migrations/2026_01_08_155250_create_project_status_histories_table.php
```

#### Step 3.2: Add Completion Status Column

```bash
php artisan migrate --path=database/migrations/2026_01_08_154137_add_completion_status_to_projects_table.php
```

**Verification:**
```bash
php artisan tinker
>>> echo 'project_status_histories table: ' . (Schema::hasTable('project_status_histories') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'completion_status column: ' . (Schema::hasColumn('projects', 'completion_status') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;

# Check status histories table structure
>>> if(Schema::hasTable('project_status_histories')) {
>>>     $cols = Schema::getColumnListing('project_status_histories');
>>>     echo 'Has project_id: ' . (in_array('project_id', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has previous_status: ' . (in_array('previous_status', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has new_status: ' . (in_array('new_status', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has changed_by_user_id: ' . (in_array('changed_by_user_id', $cols) ? '✅' : '❌') . PHP_EOL;
>>> }
```

#### ✅ Phase 3 Checklist
- [ ] `project_status_histories` table created
- [ ] Table has correct structure (project_id, previous_status, new_status, changed_by_user_id, etc.)
- [ ] Foreign keys created correctly
- [ ] Indexes created correctly
- [ ] `completion_status` column added to projects table
- [ ] No errors in Laravel log

---

### **PHASE 4: Activity History System** 🟡 **MEDIUM PRIORITY**

**Duration:** ~15 minutes  
**Migrations:** 2 (1 table + 1 data migration)  
**Risk Level:** 🟢 LOW (creates table, then copies data)

#### Step 4.1: Create Activity History Table

```bash
php artisan migrate --path=database/migrations/2026_01_09_130111_create_activity_histories_table.php
```

**Verification:**
```bash
php artisan tinker
>>> echo 'activity_histories table: ' . (Schema::hasTable('activity_histories') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;

# Check table structure
>>> if(Schema::hasTable('activity_histories')) {
>>>     $cols = Schema::getColumnListing('activity_histories');
>>>     echo 'Has type: ' . (in_array('type', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has related_id: ' . (in_array('related_id', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has previous_status: ' . (in_array('previous_status', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has new_status: ' . (in_array('new_status', $cols) ? '✅' : '❌') . PHP_EOL;
>>> }
```

#### Step 4.2: Run Data Migration ⚠️ **CRITICAL - MIGRATES STATUS HISTORY DATA**

**Note:** This migration copies data from `project_status_histories` (if it exists) to `activity_histories`. Since `project_status_histories` was just created in Phase 3, it may be empty initially. This is fine - the migration is idempotent and will copy data as it's created.

```bash
php artisan migrate --path=database/migrations/2026_01_09_130114_migrate_project_status_histories_to_activity_histories.php
```

**Verification:**
```bash
php artisan tinker

# Check both tables exist
>>> echo 'project_status_histories: ' . (Schema::hasTable('project_status_histories') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'activity_histories: ' . (Schema::hasTable('activity_histories') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;

# Check data migration (if status histories exist)
>>> $oldCount = DB::table('project_status_histories')->count();
>>> $newCount = DB::table('activity_histories')->where('type', 'project')->count();
>>> echo "Old records: $oldCount, Migrated records: $newCount" . PHP_EOL;
>>> if($oldCount > 0) {
>>>     echo "Migration status: " . ($newCount >= $oldCount ? '✅ COMPLETE' : '⚠️ PARTIAL') . PHP_EOL;
>>> } else {
>>>     echo "Migration status: ✅ N/A (no old data to migrate - will migrate as new status changes occur)" . PHP_EOL;
>>> }

# Verify sample record structure (if any exist)
>>> $sample = DB::table('activity_histories')->where('type', 'project')->first();
>>> if($sample) {
>>>     echo "Sample record: type={$sample->type}, related_id={$sample->related_id}, new_status={$sample->new_status}" . PHP_EOL;
>>> } else {
>>>     echo "No activity history records yet (normal if no status changes have occurred)" . PHP_EOL;
>>> }
```

#### ✅ Phase 4 Checklist
- [ ] `activity_histories` table created
- [ ] Table structure is correct (type, related_id, previous_status, new_status, etc.)
- [ ] Data migration executed (may have 0 records if no status histories exist yet - this is OK)
- [ ] If old data exists, it was copied correctly
- [ ] All records have `type = 'project'` (if any exist)
- [ ] Timestamps preserved (if any exist)
- [ ] No errors in Laravel log

---

### **PHASE 5: Notification System** 🟡 **MEDIUM PRIORITY**

**Duration:** ~10 minutes  
**Migrations:** 2  
**Risk Level:** 🟢 LOW (creates new tables only)

```bash
php artisan migrate --path=database/migrations/2026_01_09_000001_create_notifications_table.php
php artisan migrate --path=database/migrations/2026_01_09_000002_create_notification_preferences_table.php
```

**Verification:**
```bash
php artisan tinker
>>> echo 'notifications table: ' . (Schema::hasTable('notifications') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'notification_preferences table: ' . (Schema::hasTable('notification_preferences') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;

# Check notifications table structure
>>> if(Schema::hasTable('notifications')) {
>>>     $cols = Schema::getColumnListing('notifications');
>>>     echo 'Has user_id: ' . (in_array('user_id', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has type: ' . (in_array('type', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has is_read: ' . (in_array('is_read', $cols) ? '✅' : '❌') . PHP_EOL;
>>>     echo 'Has related_type: ' . (in_array('related_type', $cols) ? '✅' : '❌') . PHP_EOL;
>>> }
```

#### ✅ Phase 5 Checklist
- [ ] `notifications` table created
- [ ] `notification_preferences` table created
- [ ] Foreign keys created correctly
- [ ] Indexes created (user_id, is_read, created_at)
- [ ] Unique constraint on notification_preferences.user_id
- [ ] No errors in Laravel log

---

### **PHASE 6: Remaining Fixes** 🟡 **MEDIUM PRIORITY**

**Duration:** ~10 minutes  
**Migrations:** 3  
**Risk Level:** 🟡 MEDIUM (modifies existing tables)

#### Step 6.1: ILP Revenue Goals Table Fixes (2 migrations)

```bash
php artisan migrate --path=database/migrations/2025_06_27_194859_recreate_project__i_l_p_revenue_goals_table.php
php artisan migrate --path=database/migrations/2025_06_27_195007_recreate_project__i_l_p_revenue_goals_table.php
```

**⚠️ WARNING:** These migrations recreate the ILP revenue goals table. If you have data in this table, it may be lost. Check first:

```bash
php artisan tinker
>>> if(Schema::hasTable('project_ILP_revenue_goals')) {
>>>     $count = DB::table('project_ILP_revenue_goals')->count();
>>>     echo "ILP revenue goals records: $count" . PHP_EOL;
>>>     if($count > 0) {
>>>         echo "⚠️ WARNING: Table has data! Backup before running migrations!" . PHP_EOL;
>>>     }
>>> }
```

#### Step 6.2: Add is_budget_row Column to DP_AccountDetails

```bash
php artisan migrate --path=database/migrations/2025_06_29_104156_add_is_budget_row_to_dp_account_details_table.php
```

**Verification:**
```bash
php artisan tinker
>>> echo 'ILP revenue goals table: ' . (Schema::hasTable('project_ILP_revenue_goals') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'is_budget_row column: ' . (Schema::hasColumn('DP_AccountDetails', 'is_budget_row') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;

# Check is_budget_row column type
>>> if(Schema::hasColumn('DP_AccountDetails', 'is_budget_row')) {
>>>     $col = DB::select("SHOW COLUMNS FROM DP_AccountDetails WHERE Field = 'is_budget_row'")[0];
>>>     echo "Column type: {$col->Type}, Nullable: {$col->Null}, Default: {$col->Default}" . PHP_EOL;
>>> }
```

#### ✅ Phase 6 Checklist
- [ ] ILP revenue goals table recreated (if needed)
- [ ] `is_budget_row` column added to DP_AccountDetails
- [ ] Column type is correct (boolean/tinyint)
- [ ] No errors in Laravel log
- [ ] Existing DP_AccountDetails data is intact (if any)

---

### **PHASE 7: Aggregated Reports System** 🟢 **LOW PRIORITY**

**Duration:** ~30 minutes  
**Migrations:** 8  
**Risk Level:** 🟢 LOW (creates new tables only)

**Note:** These can be done later if aggregated reports are not immediately needed.

```bash
php artisan migrate --path=database/migrations/2026_01_08_172320_create_quarterly_reports_table.php
php artisan migrate --path=database/migrations/2026_01_08_172327_create_quarterly_report_details_table.php
php artisan migrate --path=database/migrations/2026_01_08_172327_create_half_yearly_reports_table.php
php artisan migrate --path=database/migrations/2026_01_08_172328_create_half_yearly_report_details_table.php
php artisan migrate --path=database/migrations/2026_01_08_172327_create_annual_reports_table.php
php artisan migrate --path=database/migrations/2026_01_08_172328_create_annual_report_details_table.php
php artisan migrate --path=database/migrations/2026_01_08_172328_create_aggregated_report_objectives_table.php
php artisan migrate --path=database/migrations/2026_01_08_172328_create_aggregated_report_photos_table.php
```

**Verification:**
```bash
php artisan tinker
>>> $tables = ['quarterly_reports', 'quarterly_report_details', 'half_yearly_reports', 'half_yearly_report_details', 'annual_reports', 'annual_report_details', 'aggregated_report_objectives', 'aggregated_report_photos'];
>>> foreach($tables as $table) {
>>>     echo "$table: " . (Schema::hasTable($table) ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> }
```

#### ✅ Phase 7 Checklist
- [ ] All 8 aggregated report tables created
- [ ] All tables have correct structure
- [ ] Foreign keys to projects table created
- [ ] Foreign keys to DP_Reports table created (for aggregated_report_objectives, aggregated_report_photos)
- [ ] Unique constraints on report_id columns
- [ ] Indexes created correctly
- [ ] No errors in Laravel log

---

### **PHASE 8: AI Report System** 🟢 **LOW PRIORITY**

**Duration:** ~15 minutes  
**Migrations:** 3  
**Risk Level:** 🟢 LOW (creates new tables only)

**Note:** These can be done later if AI reports are not immediately needed.

```bash
php artisan migrate --path=database/migrations/2026_01_09_100000_create_ai_report_insights_table.php
php artisan migrate --path=database/migrations/2026_01_09_100001_create_ai_report_titles_table.php
php artisan migrate --path=database/migrations/2026_01_09_100002_create_ai_report_validation_results_table.php
```

**Verification:**
```bash
php artisan tinker
>>> echo 'ai_report_insights: ' . (Schema::hasTable('ai_report_insights') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'ai_report_titles: ' . (Schema::hasTable('ai_report_titles') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
>>> echo 'ai_report_validation_results: ' . (Schema::hasTable('ai_report_validation_results') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
```

#### ✅ Phase 8 Checklist
- [ ] All 3 AI report tables created
- [ ] JSON columns exist (key_achievements, progress_trends, etc.)
- [ ] Unique constraints on (report_type, report_id)
- [ ] Edit tracking columns exist (is_edited, last_edited_at, last_edited_by_user_id)
- [ ] Indexes created correctly
- [ ] No errors in Laravel log

---

## 🚀 QUICK EXECUTION (ALL AT ONCE)

**If you want to run ALL pending migrations at once (recommended if you have a backup):**

```bash
# Step 1: BACKUP FIRST!
mysqldump -u root -p projectsReports > backup_$(date +%Y%m%d_%H%M%S).sql

# Step 2: Run all migrations
php artisan migrate

# Step 3: Verify
php artisan migrate:status | grep -i pending
# Should show 0 pending

# Step 4: Check for errors
tail -200 storage/logs/laravel.log | grep -i error
```

**Expected Result:**
- All 29 pending migrations run
- Total migrations run: 127 (all migrations)
- Total tables: 108 (91 existing + 17 new)
- 2 data migrations completed
- 5 attachment files migrated

---

## 📊 FINAL VERIFICATION SCRIPT

After running all migrations, execute this comprehensive verification:

```bash
php artisan tinker

# 1. Migration Status
>>> echo "=== MIGRATION STATUS ===" . PHP_EOL;
>>> $totalMigrations = DB::table('migrations')->count();
>>> echo "Total migrations run: $totalMigrations / 127" . PHP_EOL;

# 2. Table Count
>>> $tables = DB::select('SHOW TABLES');
>>> echo "Total tables: " . count($tables) . " (expected: ~108)" . PHP_EOL;

# 3. Critical Tables
>>> echo PHP_EOL . "=== CRITICAL TABLES ===" . PHP_EOL;
>>> $criticalTables = ['projects', 'users', 'DP_Reports', 'activity_histories', 'project_status_histories', 'notifications', 'notifications_preferences'];
>>> foreach($criticalTables as $table) {
>>>     echo "$table: " . (Schema::hasTable($table) ? '✅' : '❌') . PHP_EOL;
>>> }

# 4. Attachment File Tables
>>> echo PHP_EOL . "=== ATTACHMENT FILE TABLES ===" . PHP_EOL;
>>> $attachmentTables = ['project_IES_attachment_files', 'project_IIES_attachment_files', 'project_IAH_document_files', 'project_ILP_document_files'];
>>> foreach($attachmentTables as $table) {
>>>     echo "$table: " . (Schema::hasTable($table) ? '✅' : '❌') . PHP_EOL;
>>> }

# 5. Aggregated Report Tables
>>> echo PHP_EOL . "=== AGGREGATED REPORT TABLES ===" . PHP_EOL;
>>> $aggTables = ['quarterly_reports', 'half_yearly_reports', 'annual_reports', 'aggregated_report_objectives', 'aggregated_report_photos'];
>>> foreach($aggTables as $table) {
>>>     echo "$table: " . (Schema::hasTable($table) ? '✅' : '❌') . PHP_EOL;
>>> }

# 6. AI Report Tables
>>> echo PHP_EOL . "=== AI REPORT TABLES ===" . PHP_EOL;
>>> $aiTables = ['ai_report_insights', 'ai_report_titles', 'ai_report_validation_results'];
>>> foreach($aiTables as $table) {
>>>     echo "$table: " . (Schema::hasTable($table) ? '✅' : '❌') . PHP_EOL;
>>> }

# 7. Projects Table Columns
>>> echo PHP_EOL . "=== PROJECTS TABLE COLUMNS ===" . PHP_EOL;
>>> $cols = Schema::getColumnListing('projects');
>>> $newCols = ['predecessor_project_id', 'initial_information', 'target_beneficiaries', 'general_situation', 'need_of_project', 'completion_status', 'local_contribution'];
>>> foreach($newCols as $col) {
>>>     echo "$col: " . (in_array($col, $cols) ? '✅' : '❌') . PHP_EOL;
>>> }

# 8. Data Migration Status
>>> echo PHP_EOL . "=== DATA MIGRATION STATUS ===" . PHP_EOL;
>>> 
>>> # Attachment files migration
>>> $iesNew = DB::table('project_IES_attachment_files')->count();
>>> $iiesNew = DB::table('project_IIES_attachment_files')->count();
>>> $iahNew = DB::table('project_IAH_document_files')->count();
>>> $ilpNew = DB::table('project_ILP_document_files')->count();
>>> echo "IES files migrated: $iesNew" . PHP_EOL;
>>> echo "IIES files migrated: $iiesNew" . PHP_EOL;
>>> echo "IAH files migrated: $iahNew" . PHP_EOL;
>>> echo "ILP files migrated: $ilpNew" . PHP_EOL;
>>> 
>>> # Activity history migration
>>> $statusHistCount = DB::table('project_status_histories')->count();
>>> $activityHistCount = DB::table('activity_histories')->where('type', 'project')->count();
>>> echo "Status histories: $statusHistCount" . PHP_EOL;
>>> echo "Activity histories (project type): $activityHistCount" . PHP_EOL;
>>> if($statusHistCount > 0) {
>>>     echo "Migration status: " . ($activityHistCount >= $statusHistCount ? '✅ COMPLETE' : '⚠️ PARTIAL') . PHP_EOL;
>>> } else {
>>>     echo "Migration status: ✅ N/A (no old data)" . PHP_EOL;
>>> }

# 9. DP_AccountDetails Column
>>> echo PHP_EOL . "=== DP_ACCOUNT_DETAILS ===" . PHP_EOL;
>>> echo 'is_budget_row column: ' . (Schema::hasColumn('DP_AccountDetails', 'is_budget_row') ? '✅ EXISTS' : '❌ MISSING') . PHP_EOL;
```

---

## ⚠️ TROUBLESHOOTING

### Problem: Migration Fails with Foreign Key Error

**Solution:**
```bash
# Temporarily disable foreign key checks
php artisan tinker
>>> DB::statement('SET FOREIGN_KEY_CHECKS = 0');
>>> // Run migration manually if needed
>>> DB::statement('SET FOREIGN_KEY_CHECKS = 1');
```

### Problem: Migration Fails with "Table Already Exists"

**Solution:**
```bash
# Check if table exists
php artisan tinker
>>> Schema::hasTable('table_name')

# If exists, check migrations table
>>> DB::table('migrations')->where('migration', 'like', '%table_name%')->get()

# Manually mark migration as run if table already exists correctly
>>> DB::table('migrations')->insert(['migration' => '2026_01_XX_create_table_name_table', 'batch' => 9]);
```

### Problem: Data Migration Fails

**Solution:**
```bash
# Check Laravel log for specific error
tail -200 storage/logs/laravel.log | grep -i "error\|exception"

# Data migrations are idempotent - can be run multiple times
# Re-run the specific migration:
php artisan migrate --path=database/migrations/SPECIFIC_MIGRATION_FILE.php --force
```

### Problem: Missing Column After Migration

**Solution:**
```bash
# Check if migration actually ran
php artisan migrate:status | grep "migration_name"

# If not run, run it:
php artisan migrate --path=database/migrations/MIGRATION_FILE.php

# If run but column missing, check migration file for errors
# Or manually add column if migration file is correct
```

---

## ✅ COMPLETION CHECKLIST

### After All Phases Complete:

- [ ] All 29 pending migrations show as "Ran" in `php artisan migrate:status`
- [ ] Total migrations run: 127
- [ ] Total tables: 108 (91 + 17)
- [ ] All critical tables exist (projects, users, DP_Reports, activity_histories, notifications, etc.)
- [ ] All 4 attachment file tables exist
- [ ] All 8 aggregated report tables exist (if Phase 7 completed)
- [ ] All 3 AI report tables exist (if Phase 8 completed)
- [ ] All new columns exist in projects table (7 columns)
- [ ] `is_budget_row` column exists in DP_AccountDetails
- [ ] Attachment files data migrated (5 files across 4 types)
- [ ] Activity history data migrated (if any status histories existed)
- [ ] No errors in Laravel log
- [ ] Application functionality tested:
  - [ ] Create project with Key Information fields
  - [ ] Edit project with Key Information fields
  - [ ] Select predecessor project
  - [ ] Upload attachment files (IES/IIES/IAH/ILP)
  - [ ] View attachments (old and new structure)
  - [ ] Create monthly report
  - [ ] Submit report (notifications should work)

---

## 📝 NOTES

1. **Migration Order:** Follow phases in order - some migrations depend on others
2. **Data Safety:** All data migrations are non-destructive and idempotent
3. **Backup:** Always backup before running migrations
4. **Testing:** Test application functionality after each phase
5. **Errors:** Check `storage/logs/laravel.log` for any errors

---

**Last Updated:** January 2025  
**Status:** ✅ **READY FOR EXECUTION**  
**Estimated Total Duration:** ~2-3 hours (including verification and testing)  
**Recommended Approach:** Phased execution (Phases 1-6 first, then Phases 7-8 if needed)
