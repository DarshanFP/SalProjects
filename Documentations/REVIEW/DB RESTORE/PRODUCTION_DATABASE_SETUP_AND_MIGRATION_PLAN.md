# Production Database Setup and Migration Plan

**Date:** January 2025  
**Purpose:** Switch to production database `u160871038_salprojects` and run pending migrations  
**Status:** 📋 **PLANNING**

---

## Executive Summary

This document outlines the plan to switch the Laravel application environment to use the production database `u160871038_salprojects` and execute all pending migrations as documented in the DB RESTORE documentation.

**Current Database:** `projectsReports` (development/local)  
**Target Database:** `u160871038_salprojects` (production)  
**Pending Migrations:** 29-34 migrations (as per documentation)  
**Risk Level:** 🟡 **MEDIUM** (production database requires careful handling)

---

## Pre-Execution Requirements

### 1. Database Credentials Required

To switch to production database, we need:

- **DB_DATABASE:** `u160871038_salprojects`
- **DB_HOST:** (Production database host - typically provided by hosting provider)
- **DB_USERNAME:** (Production database username)
- **DB_PASSWORD:** (Production database password)
- **DB_PORT:** (Typically 3306 for MySQL)

**⚠️ IMPORTANT:** These credentials should be obtained from:
- Hosting provider (cPanel, hosting panel, etc.)
- Database administrator
- Production server documentation

---

## Current Environment Configuration

### Current .env Settings (Development)

Based on current configuration:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=projectsReports
DB_USERNAME=root
DB_PASSWORD=root
```

### Target .env Settings (Production)

```
DB_CONNECTION=mysql
DB_HOST=<PRODUCTION_HOST>          # To be provided
DB_PORT=3306                       # Typically 3306
DB_DATABASE=u160871038_salprojects
DB_USERNAME=<PRODUCTION_USERNAME>  # To be provided
DB_PASSWORD=<PRODUCTION_PASSWORD>  # To be provided
```

---

## Step-by-Step Execution Plan

### Phase 1: Preparation ⚠️ **CRITICAL**

#### Step 1.1: Verify Production Database Access

**Actions:**
1. Obtain production database credentials from hosting provider/admin
2. Test database connection:
   ```bash
   mysql -h <PRODUCTION_HOST> -u <PRODUCTION_USERNAME> -p u160871038_salprojects
   ```
3. Verify database exists and is accessible
4. Check current database state:
   ```bash
   # Connect to database and check tables
   mysql -h <PRODUCTION_HOST> -u <PRODUCTION_USERNAME> -p u160871038_salprojects -e "SHOW TABLES;" | wc -l
   ```

**Verification:**
- [ ] Database credentials obtained
- [ ] Database connection successful
- [ ] Database exists and is accessible
- [ ] Current table count verified

---

#### Step 1.2: Backup Production Database ⚠️ **MANDATORY**

**Actions:**
1. Create full database backup before any changes:
   ```bash
   mysqldump -h <PRODUCTION_HOST> -u <PRODUCTION_USERNAME> -p u160871038_salprojects > backup_production_$(date +%Y%m%d_%H%M%S).sql
   ```

2. Verify backup file created:
   ```bash
   ls -lh backup_production_*.sql
   ```

3. Store backup in safe location (separate from project directory)

**Verification:**
- [ ] Backup file created successfully
- [ ] Backup file size is reasonable (not empty)
- [ ] Backup stored in safe location
- [ ] Backup can be restored if needed

---

#### Step 1.3: Check Current Migration Status

**Before switching database, check current status:**
```bash
# Save current status for reference
php artisan migrate:status > migration_status_before.txt
```

**Actions:**
1. Document current migration status
2. Count pending migrations
3. Note which migrations are already run

**Verification:**
- [ ] Current migration status documented
- [ ] Pending migrations identified
- [ ] Status saved for reference

---

### Phase 2: Environment Configuration Update

#### Step 2.1: Update .env File

**Actions:**
1. **BACKUP current .env file:**
   ```bash
   cp .env .env.backup_$(date +%Y%m%d_%H%M%S)
   ```

2. **Update .env file with production database credentials:**
   ```bash
   # Update these lines in .env:
   DB_HOST=<PRODUCTION_HOST>
   DB_DATABASE=u160871038_salprojects
   DB_USERNAME=<PRODUCTION_USERNAME>
   DB_PASSWORD=<PRODUCTION_PASSWORD>
   DB_PORT=3306
   ```

3. **Clear configuration cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

**Verification:**
- [ ] Current .env backed up
- [ ] Production credentials added to .env
- [ ] Configuration cache cleared
- [ ] Old .env file preserved

---

#### Step 2.2: Test Database Connection

**Actions:**
1. Test connection using Laravel:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   >>> DB::select('SELECT DATABASE() as db');
   >>> DB::select('SHOW TABLES');
   ```

2. Verify connection successful and correct database

**Verification:**
- [ ] Connection successful
- [ ] Connected to correct database (`u160871038_salprojects`)
- [ ] Can query database
- [ ] Tables are accessible

---

### Phase 3: Migration Status Check

#### Step 3.1: Check Migration Status on Production Database

**Actions:**
1. Check current migration status:
   ```bash
   php artisan migrate:status
   ```

2. Count pending migrations:
   ```bash
   php artisan migrate:status | grep -i "Pending\|Not Run" | wc -l
   ```

3. List pending migrations:
   ```bash
   php artisan migrate:status | grep -i "Pending\|Not Run"
   ```

**Expected Results:**
- Should show pending migrations (29-34 as per documentation)
- Current database state documented

**Verification:**
- [ ] Migration status checked
- [ ] Pending migrations identified
- [ ] Status documented

---

### Phase 4: Run Migrations

#### Option A: Run All Migrations at Once (Recommended if backup exists)

**Actions:**
1. Run all pending migrations:
   ```bash
   php artisan migrate
   ```

2. Monitor for errors during migration

3. Note any warnings or errors

**Duration:** ~30-45 minutes  
**Risk:** 🟢 LOW (all migrations are safe, backup exists)

---

#### Option B: Phased Approach (Recommended for careful execution)

**Phase 4.1: Foundation (Projects Table Enhancements)** - 🔴 HIGH PRIORITY
```bash
php artisan migrate --path=database/migrations/2026_01_07_000001_add_local_contribution_to_projects_table.php
php artisan migrate --path=database/migrations/2026_01_07_162317_make_in_charge_nullable_in_projects_table.php
php artisan migrate --path=database/migrations/2026_01_07_172101_add_predecessor_project_id_to_projects_table.php
php artisan migrate --path=database/migrations/2026_01_07_182657_add_key_information_fields_to_projects_table.php
```

**Phase 4.2: Attachment Files** - 🔴 HIGH PRIORITY
```bash
php artisan migrate --path=database/migrations/2026_01_08_134425_create_project_ies_attachment_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_134429_create_project_iies_attachment_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_134432_create_project_iah_document_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_134435_create_project_ilp_document_files_table.php
php artisan migrate --path=database/migrations/2026_01_08_135526_migrate_existing_attachments_to_multiple_files.php
```

**Phase 4.3: Status Tracking** - 🟡 MEDIUM
```bash
php artisan migrate --path=database/migrations/2026_01_08_154137_add_completion_status_to_projects_table.php
php artisan migrate --path=database/migrations/2026_01_08_155250_create_project_status_histories_table.php
```

**Phase 4.4: Activity History** - 🟡 MEDIUM
```bash
php artisan migrate --path=database/migrations/2026_01_09_130000_create_activity_histories_table.php
php artisan migrate --path=database/migrations/2026_01_09_130114_migrate_project_status_histories_to_activity_histories.php
```

**Phase 4.5: Notifications** - 🟡 MEDIUM
```bash
php artisan migrate --path=database/migrations/2026_01_09_000001_create_notifications_table.php
php artisan migrate --path=database/migrations/2026_01_09_000002_create_notification_preferences_table.php
```

**Phase 4.6: Remaining Fixes** - 🟡 MEDIUM
```bash
php artisan migrate --path=database/migrations/2025_06_27_194859_recreate_project__i_l_p_revenue_goals_table.php
php artisan migrate --path=database/migrations/2025_06_27_195007_recreate_project__i_l_p_revenue_goals_table.php
php artisan migrate --path=database/migrations/2025_06_29_104156_add_is_budget_row_to_dp_account_details_table.php
```

**Phase 4.7: Aggregated Reports** - 🟢 LOW (can wait)
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

**Phase 4.8: AI Reports** - 🟢 LOW (can wait)
```bash
php artisan migrate --path=database/migrations/2026_01_09_140000_create_ai_report_insights_table.php
php artisan migrate --path=database/migrations/2026_01_09_140001_create_ai_report_titles_table.php
php artisan migrate --path=database/migrations/2026_01_09_140002_create_ai_report_validation_results_table.php
```

---

### Phase 5: Verification

#### Step 5.1: Verify All Migrations Completed

**Actions:**
1. Check migration status:
   ```bash
   php artisan migrate:status | grep -i "Pending\|Not Run"
   ```

2. Should show 0 pending migrations

3. Count total migrations:
   ```bash
   php artisan migrate:status | wc -l
   ```

**Expected:** 127 migrations total (all run)

**Verification:**
- [ ] No pending migrations
- [ ] All migrations show "Ran" status
- [ ] Total migrations: 127

---

#### Step 5.2: Verify Database Structure

**Actions:**
1. Check table count:
   ```bash
   php artisan tinker
   >>> DB::select('SHOW TABLES');
   >>> count(DB::select('SHOW TABLES')); // Should be ~108 tables
   ```

2. Verify critical tables exist:
   ```bash
   >>> Schema::hasTable('activity_histories'); // Should be true
   >>> Schema::hasTable('project_status_histories'); // Should be true
   >>> Schema::hasTable('notifications'); // Should be true
   >>> Schema::hasTable('project_IES_attachment_files'); // Should be true
   >>> Schema::hasTable('project_IIES_attachment_files'); // Should be true
   >>> Schema::hasTable('project_IAH_document_files'); // Should be true
   >>> Schema::hasTable('project_ILP_document_files'); // Should be true
   ```

3. Verify projects table columns:
   ```bash
   >>> $cols = Schema::getColumnListing('projects');
   >>> in_array('predecessor_project_id', $cols); // Should be true
   >>> in_array('initial_information', $cols); // Should be true
   >>> in_array('target_beneficiaries', $cols); // Should be true
   >>> in_array('general_situation', $cols); // Should be true
   >>> in_array('need_of_project', $cols); // Should be true
   >>> in_array('completion_status', $cols); // Should be true
   >>> in_array('local_contribution', $cols); // Should be true
   ```

4. Verify DP_AccountDetails column:
   ```bash
   >>> Schema::hasColumn('DP_AccountDetails', 'is_budget_row'); // Should be true
   ```

**Verification:**
- [ ] Table count is ~108
- [ ] All critical tables exist
- [ ] All new columns exist in projects table
- [ ] is_budget_row column exists

---

#### Step 5.3: Verify Data Migrations

**Actions:**
1. Check attachment files migration:
   ```bash
   >>> DB::table('project_IES_attachment_files')->count();
   >>> DB::table('project_IIES_attachment_files')->count();
   >>> DB::table('project_IAH_document_files')->count();
   >>> DB::table('project_ILP_document_files')->count();
   ```

2. Check activity history migration:
   ```bash
   >>> DB::table('activity_histories')->where('type', 'project')->count();
   >>> DB::table('project_status_histories')->count(); // Old data should still exist
   ```

**Expected:**
- Attachment files: Count depends on existing data (documentation mentions 5 files)
- Activity history: Should have records if project_status_histories had data

**Verification:**
- [ ] Attachment files migrated (or no files to migrate)
- [ ] Activity history migration completed
- [ ] Old data still exists (non-destructive)

---

#### Step 5.4: Check for Errors

**Actions:**
1. Check Laravel log for errors:
   ```bash
   tail -200 storage/logs/laravel.log | grep -i error
   ```

2. Check for warnings:
   ```bash
   tail -200 storage/logs/laravel.log | grep -i warning
   ```

**Verification:**
- [ ] No errors in log
- [ ] No critical warnings
- [ ] Migrations completed successfully

---

### Phase 6: Testing

#### Step 6.1: Application Functionality Test

**Recommended Tests:**
1. **Database Connection:** Verify application can connect to database
2. **Dashboard Routes:** Test all user dashboards
3. **Project Management:** Create/edit/view projects
4. **Report Management:** Create/edit/view reports
5. **File Uploads:** Test attachment file uploads (if Phase 4.2 completed)

**Verification:**
- [ ] Application connects to database successfully
- [ ] All routes accessible
- [ ] Core functionality works
- [ ] No fatal errors

---

## Rollback Plan

### If Issues Occur

**Step 1: Restore .env File**
```bash
cp .env.backup_* .env
php artisan config:clear
```

**Step 2: Restore Database (if needed)**
```bash
mysql -h <PRODUCTION_HOST> -u <PRODUCTION_USERNAME> -p u160871038_salprojects < backup_production_*.sql
```

**Step 3: Verify Restoration**
```bash
php artisan tinker
>>> DB::select('SHOW TABLES');
>>> DB::table('migrations')->count();
```

---

## Critical Notes

### 1. Production Database Safety

- ⚠️ **ALWAYS BACKUP FIRST:** Production database requires backup before any changes
- ⚠️ **TEST CONNECTION:** Verify connection works before running migrations
- ⚠️ **MONITOR CLOSELY:** Watch for errors during migration execution
- ⚠️ **HAVE ROLLBACK PLAN:** Be prepared to restore if issues occur

### 2. Migration Safety

According to documentation:
- ✅ All migrations are **non-destructive** (old data remains)
- ✅ Data migrations are **idempotent** (can run multiple times)
- ✅ Safe to run on production (with backup)

### 3. Data Migrations

Two critical data migrations:
1. **Attachment Files Migration:** Migrates existing attachment files to new structure
2. **Activity History Migration:** Copies project status histories to activity histories

Both are safe and non-destructive.

---

## Execution Checklist

### Pre-Execution
- [ ] Production database credentials obtained
- [ ] Database connection tested
- [ ] Production database backup created
- [ ] Current .env file backed up
- [ ] Current migration status documented

### Execution
- [ ] .env file updated with production credentials
- [ ] Configuration cache cleared
- [ ] Database connection verified
- [ ] Migration status checked
- [ ] Migrations executed (Option A or Option B)
- [ ] Migration completion verified

### Post-Execution
- [ ] All migrations show "Ran" status
- [ ] All tables created (108 tables)
- [ ] All columns added to projects table
- [ ] Data migrations completed
- [ ] No errors in Laravel log
- [ ] Application functionality tested

---

## Reference Documents

1. **`DATABASE_MIGRATION_SUMMARY.md`** - Quick reference for migrations
2. **`DATABASE_AUDIT_REPORT_AND_IMPLEMENTATION_PLAN.md`** - Detailed migration plan
3. **`DATABASE_MIGRATION_EXECUTION_CHECKLIST.md`** - Step-by-step execution guide
4. **`DATABASE_RESTORATION_CHECKLIST.md`** - Comprehensive restoration guide

---

## Next Steps

1. **Obtain Production Database Credentials** ⚠️ REQUIRED
   - Get credentials from hosting provider/admin
   - Verify database access

2. **Create Backup** ⚠️ MANDATORY
   - Backup production database
   - Store backup safely

3. **Update .env File**
   - Add production credentials
   - Backup current .env

4. **Execute Migrations**
   - Choose Option A (all at once) or Option B (phased)
   - Monitor for errors

5. **Verify Completion**
   - Check migration status
   - Verify database structure
   - Test application

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** 📋 **PLANNING - AWAITING PRODUCTION CREDENTIALS**  
**Next Step:** Obtain production database credentials and create backup
