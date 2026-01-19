# 🗄️ Database Restoration Checklist - Complete Guide

**Date:** January 2025  
**Status:** ⚠️ **CRITICAL - Database Restored from Backup**  
**Purpose:** Comprehensive checklist to bring database to proper state after restoration

---

## ⚠️ IMPORTANT SECURITY NOTE

**TRUNCATE FILES REMOVED:**
- ❌ `truncate_all.sql` - **NEEDS MANUAL REMOVAL** (file deletion was rejected)
- ❌ `truncate_reports.php` - **NEEDS MANUAL REMOVAL** (file deletion was rejected)

**ACTION REQUIRED:**
```bash
# Manually remove these files to prevent accidental data deletion:
rm truncate_all.sql
rm truncate_reports.php

# OR move them to a safe location:
mkdir -p .backup/truncate_scripts
mv truncate_all.sql truncate_reports.php .backup/truncate_scripts/
```

---

## 📋 PRE-RESTORATION CHECKLIST

### Step 1: Verify Backup Integrity
- [ ] ✅ Database backup file exists and is accessible
- [ ] ✅ Backup file size is reasonable (not 0 bytes)
- [ ] ✅ Backup file timestamp is recent
- [ ] ✅ Can extract/view backup file structure

### Step 2: Verify Current Database State
- [ ] ✅ Check current database connection (`config/database.php`)
- [ ] ✅ Verify database name: `projectsReports`
- [ ] ✅ Check if tables exist: `users`, `projects`
- [ ] ✅ Record current table counts (if any data exists)

---

## 🔄 DATABASE RESTORATION STEPS

### Step 3: Restore Database from Backup
- [ ] ✅ Restore database from backup file
- [ ] ✅ Verify restoration completed successfully
- [ ] ✅ Check that all tables exist after restoration
- [ ] ✅ Verify table structure is intact

### Step 4: Verify Core Tables Exist
Check that these essential tables exist:

#### Core System Tables
- [ ] `users` - User accounts
- [ ] `password_reset_tokens` - Password resets
- [ ] `sessions` - User sessions
- [ ] `permissions` - Permission system
- [ ] `roles` - Role system
- [ ] `model_has_permissions` - Model permissions
- [ ] `model_has_roles` - Model roles
- [ ] `role_has_permissions` - Role permissions
- [ ] `failed_jobs` - Failed queue jobs
- [ ] `personal_access_tokens` - API tokens

#### Project Tables (Core)
- [ ] `projects` - Main projects table
- [ ] `project_budgets` - Project budgets
- [ ] `project_attachments` - Project attachments
- [ ] `project_objectives` - Project objectives
- [ ] `project_results` - Project results
- [ ] `project_risks` - Project risks
- [ ] `project_activities` - Project activities
- [ ] `project_timeframes` - Project timeframes
- [ ] `project_sustainabilities` - Project sustainability
- [ ] `project_comments` - Project comments

#### Project Type-Specific Tables

**Development Projects (DP):**
- [ ] `oldDevelopmentProjects` - Old DP structure (if exists)
- [ ] `old_DP_budgets` - Old DP budgets (if exists)
- [ ] `old_DP_attachments` - Old DP attachments (if exists)

**Child Care Institution (CCI):**
- [ ] `project_CCI_rationale`
- [ ] `project_CCI_statistics`
- [ ] `project_CCI_annexed_target_group`
- [ ] `project_CCI_age_profile`
- [ ] `project_CCI_personal_situation`
- [ ] `project_CCI_economic_background`
- [ ] `project_CCI_present_situation`
- [ ] `project_CCI_achievements`

**Crisis Intervention Center (CIC):**
- [ ] `project_cic_basic_info`

**Education Rural-Urban-Tribal (EduRUT):**
- [ ] `Project_EduRUT_Basic_Info`
- [ ] `project_edu_rut_target_groups`
- [ ] `project_edu_rut_annexed_target_groups`

**Individual Education Support (IES):**
- [ ] `project_IES_personal_infos`
- [ ] `project_IES_attachments`
- [ ] `project_IES_attachment_files` ⚠️ **NEW** (2026-01-08)
- [ ] `project_IES_education_backgrounds`
- [ ] `project_IES_expenses`
- [ ] `project__i_e_s_expense_details`
- [ ] `project_IES_immediate_family_details`
- [ ] `project_IES_family_working_members`

**Individual Immediate Education Support (IIES):**
- [ ] `project_i_i_e_s_personal_infos`
- [ ] `project_i_i_e_s_attachments`
- [ ] `project_IIES_attachment_files` ⚠️ **NEW** (2026-01-08)
- [ ] `project_i_i_e_s_education_backgrounds`
- [ ] `project_i_i_e_s_expenses`
- [ ] `project_iies_expense_details`
- [ ] `project_i_i_e_s_family_working_members`
- [ ] `project_i_i_e_s_immediate_family_details`
- [ ] `project_i_i_e_s_scope_financial_supports`

**Individual Aided Housing (IAH):**
- [ ] `project_IAH_personal_infos`
- [ ] `project_IAH_documents`
- [ ] `project_IAH_document_files` ⚠️ **NEW** (2026-01-08)
- [ ] `project_IAH_budget_details`
- [ ] `project_IAH_earning_members`
- [ ] `project_IAH_health_conditions`
- [ ] `project_IAH_support_details`

**Individual Livelihood Project (ILP):**
- [ ] `project_ILP_personal_infos`
- [ ] `project_ILP_attached_docs`
- [ ] `project_ILP_document_files` ⚠️ **NEW** (2026-01-08)
- [ ] `project_ILP_budgets`
- [ ] `project_ILP_revenue_goals`
- [ ] `project_ILP_risk_analyses`
- [ ] `project_ILP_business_strength_weaknesses`

**Institutional Group Education (IGE):**
- [ ] `project_IGE_institution_infos`
- [ ] `project_IGE_ongoing_beneficiaries`
- [ ] `project_IGE_beneficiaries_supporteds`
- [ ] `project_IGE_new_beneficiaries`
- [ ] `project_IGE_budgets`
- [ ] `project_IGE_development_monitorings`

**Livelihood Development Project (LDP):**
- [ ] `project_LDP_target_groups`
- [ ] `project_LDP_need_analyses`
- [ ] `project_LDP_intervention_logics`

**Residential Skill Training (RST):**
- [ ] `project_RST_institution_infos`
- [ ] `project_RST_target_groups`
- [ ] `project_RST_target_group_annexures`
- [ ] `project_RST_geographical_areas`
- [ ] `project_RST_DP_beneficiaries_area`

#### Report Tables

**Monthly Reports (DP Reports):**
- [ ] `DP_Reports` - Main monthly reports table
- [ ] `DP_Objectives` - Report objectives
- [ ] `DP_Activities` - Report activities
- [ ] `DP_AccountDetails` - Account details/statements
- [ ] `DP_Photos` - Report photos
- [ ] `DP_Outlooks` - Outlook/plan next month
- [ ] `report_attachments` - Report attachments
- [ ] `report_comments` - Report comments

**Aggregated Reports (NEW - 2026-01-08):**
- [ ] `quarterly_reports` ⚠️ **NEW**
- [ ] `quarterly_report_details` ⚠️ **NEW**
- [ ] `half_yearly_reports` ⚠️ **NEW**
- [ ] `half_yearly_report_details` ⚠️ **NEW**
- [ ] `annual_reports` ⚠️ **NEW**
- [ ] `annual_report_details` ⚠️ **NEW**
- [ ] `aggregated_report_objectives` ⚠️ **NEW**
- [ ] `aggregated_report_photos` ⚠️ **NEW**

**AI Report Tables (NEW - 2026-01-09):**
- [ ] `ai_report_insights` ⚠️ **NEW**
- [ ] `ai_report_titles` ⚠️ **NEW**
- [ ] `ai_report_validation_results` ⚠️ **NEW**

#### Activity & Status Tracking Tables
- [ ] `project_status_histories` - Project status change history
- [ ] `activity_histories` ⚠️ **NEW** (2026-01-09) - Unified activity history
- [ ] `notifications` ⚠️ **NEW** (2026-01-09) - Notification system
- [ ] `notification_preferences` ⚠️ **NEW** (2026-01-09) - User notification preferences

#### Request Tables (RQ)
- [ ] `rqst_trainee_profile` - Trainee profiles
- [ ] `rqis_age_profiles` - Age profiles
- [ ] `rqwd_inmates_profiles` - Inmates profiles
- [ ] `qrdl_annexure` - QRDL annexure

---

## 🔄 MIGRATION VERIFICATION & EXECUTION

### Step 5: Check Migration Status
```bash
php artisan migrate:status
```

**Expected Result:** All 127 migrations should show `[Batch] Ran`

**If migrations are missing:**
- [ ] Run missing migrations: `php artisan migrate`
- [ ] Verify each migration completes successfully
- [ ] Check for migration errors in `storage/logs/laravel.log`

### Step 6: Verify All Migrations Are Run

**Total Migrations:** 127 files

**Key Migration Categories:**

#### ✅ Core System Migrations (Should already be run)
- `2014_10_12_000000_create_users_table.php`
- `2014_10_12_100000_create_password_reset_tokens_table.php`
- `2019_08_19_000000_create_failed_jobs_table.php`
- `2019_12_14_000001_create_personal_access_tokens_table.php`
- `2024_07_09_125718_create_permission_tables.php`

#### ✅ Project Core Migrations (Should already be run)
- `2024_07_20_085634_create_projects_table.php`
- `2024_07_20_085654_create_project_budgets_table.php`
- `2024_07_20_085703_create_project_attachments_table.php`
- `2024_08_04_083634_create_project_objectives_table.php`
- `2024_08_04_083635_create_project_results_table.php`
- `2024_08_04_083636_create_project_risks_table.php`
- `2024_08_04_083637_create_project_activities_table.php`
- `2024_08_04_083638_create_project_timeframes_table.php`
- `2024_08_04_180601_create_project_sustainabilities_table.php`
- `2024_08_18_130656_create_report_comments_table.php`

#### ✅ Report Migrations (Should already be run)
- `2024_07_21_092111_create_dp_reports_table.php`
- `2024_07_21_092321_create_dp_objectives_table.php`
- `2024_07_21_092333_create_dp_activities_table.php`
- `2024_07_21_092344_create_dp_account_details_table.php`
- `2024_07_21_092352_create_dp_photos_table.php`
- `2024_07_21_092359_create_dp_outlooks_table.php`
- `2024_08_29_180251_create_report_attachments_table.php`

#### ⚠️ NEW Migrations (2026-01-07 to 2026-01-09) - **VERIFY THESE ARE RUN**

**Key Information Enhancement (2026-01-07):**
- [ ] `2026_01_07_182657_add_key_information_fields_to_projects_table.php`
  - Adds: `initial_information`, `target_beneficiaries`, `general_situation`, `need_of_project`
  - **Action:** Verify these columns exist in `projects` table

**Predecessor Project (2026-01-07):**
- [ ] `2026_01_07_172101_add_predecessor_project_id_to_projects_table.php`
  - Adds: `predecessor_project_id` column
  - **Action:** Verify column exists

**Local Contribution (2026-01-07):**
- [ ] `2026_01_07_000001_add_local_contribution_to_projects_table.php`
  - **Action:** Verify column exists

**In Charge Nullable (2026-01-07):**
- [ ] `2026_01_07_162317_make_in_charge_nullable_in_projects_table.php`
  - **Action:** Verify `in_charge` is nullable

**Completion Status (2026-01-08):**
- [ ] `2026_01_08_154137_add_completion_status_to_projects_table.php`
  - **Action:** Verify column exists

**Status History (2026-01-08):**
- [ ] `2026_01_08_155250_create_project_status_histories_table.php`
  - **Action:** Verify table exists

**Attachment Files (2026-01-08):**
- [ ] `2026_01_08_134425_create_project_ies_attachment_files_table.php`
- [ ] `2026_01_08_134429_create_project_iies_attachment_files_table.php`
- [ ] `2026_01_08_134432_create_project_iah_document_files_table.php`
- [ ] `2026_01_08_134435_create_project_ilp_document_files_table.php`
  - **Action:** Verify all 4 tables exist

**Aggregated Reports (2026-01-08):**
- [ ] `2026_01_08_172320_create_quarterly_reports_table.php`
- [ ] `2026_01_08_172327_create_quarterly_report_details_table.php`
- [ ] `2026_01_08_172327_create_half_yearly_reports_table.php`
- [ ] `2026_01_08_172328_create_half_yearly_report_details_table.php`
- [ ] `2026_01_08_172327_create_annual_reports_table.php`
- [ ] `2026_01_08_172328_create_annual_report_details_table.php`
- [ ] `2026_01_08_172328_create_aggregated_report_objectives_table.php`
- [ ] `2026_01_08_172328_create_aggregated_report_photos_table.php`
  - **Action:** Verify all 8 tables exist

**Activity History (2026-01-09):**
- [ ] `2026_01_09_130111_create_activity_histories_table.php`
  - **Action:** Verify table exists

**Notifications (2026-01-09):**
- [ ] `2026_01_09_000001_create_notifications_table.php`
- [ ] `2026_01_09_000002_create_notification_preferences_table.php`
  - **Action:** Verify both tables exist

**AI Report Tables (2026-01-09):**
- [ ] `2026_01_09_100000_create_ai_report_insights_table.php`
- [ ] `2026_01_09_100001_create_ai_report_titles_table.php`
- [ ] `2026_01_09_100002_create_ai_report_validation_results_table.php`
  - **Action:** Verify all 3 tables exist

---

## 🔄 DATA MIGRATIONS - CRITICAL TO RUN

### Step 7: Run Data Migrations

These migrations **move/copy data** from one table to another. **VERIFY THESE ARE RUN:**

#### 1. Activity History Migration (2026-01-09)
**File:** `2026_01_09_130114_migrate_project_status_histories_to_activity_histories.php`

**What it does:**
- Copies all data from `project_status_histories` table
- Inserts into `activity_histories` table with `type = 'project'`
- Preserves all timestamps and data

**Action Required:**
```bash
# Check if migration is already run
php artisan migrate:status | grep "migrate_project_status_histories_to_activity_histories"

# If NOT run, run it:
php artisan migrate --path=database/migrations/2026_01_09_130114_migrate_project_status_histories_to_activity_histories.php

# Verify data was migrated:
php artisan tinker
>>> DB::table('project_status_histories')->count() // Should match
>>> DB::table('activity_histories')->where('type', 'project')->count() // Should match
```

**Checklist:**
- [ ] Migration is run
- [ ] Data count matches between old and new tables
- [ ] No errors in migration logs
- [ ] Verify sample records are correctly migrated

---

#### 2. Attachment Files Migration (2026-01-08) - **CRITICAL**
**File:** `2026_01_08_135526_migrate_existing_attachments_to_multiple_files.php`

**What it does:**
- Migrates existing single-file-per-field attachments to new multiple-files-per-field structure
- Handles 4 project types:
  - **IES:** `project_IES_attachments` → `project_IES_attachment_files`
  - **IIES:** `project_IIES_attachments` → `project_IIES_attachment_files`
  - **IAH:** `project_IAH_documents` → `project_IAH_document_files`
  - **ILP:** `project_ILP_attached_docs` → `project_ILP_document_files`

**IMPORTANT NOTES:**
- ✅ **Safe:** Only reads from old tables, writes to new tables
- ✅ **Idempotent:** Can be run multiple times safely (checks for existing files)
- ✅ **Non-destructive:** Does NOT delete from old tables
- ✅ **File-safe:** Does NOT move/delete files from storage

**Action Required:**
```bash
# Check if migration is already run
php artisan migrate:status | grep "migrate_existing_attachments_to_multiple_files"

# If NOT run, run it:
php artisan migrate --path=database/migrations/2026_01_08_135526_migrate_existing_attachments_to_multiple_files.php

# Check Laravel log for migration output:
tail -100 storage/logs/laravel.log | grep -i "migration\|attachment"
```

**Verification Steps:**
```php
// Run in tinker to verify migration:
php artisan tinker

// IES Attachments
>>> DB::table('project_IES_attachments')->whereNotNull('aadhar_card')->count()
>>> DB::table('project_IES_attachment_files')->where('field_name', 'aadhar_card')->count()
// Should be equal (one file per record)

// IIES Attachments
>>> DB::table('project_IIES_attachments')->whereNotNull('iies_aadhar_card')->count()
>>> DB::table('project_IIES_attachment_files')->where('field_name', 'iies_aadhar_card')->count()
// Should be equal

// IAH Documents
>>> DB::table('project_IAH_documents')->whereNotNull('aadhar_copy')->count()
>>> DB::table('project_IAH_document_files')->where('field_name', 'aadhar_copy')->count()
// Should be equal

// ILP Documents
>>> DB::table('project_ILP_attached_docs')->whereNotNull('aadhar_doc')->count()
>>> DB::table('project_ILP_document_files')->where('field_name', 'aadhar_doc')->count()
// Should be equal
```

**Checklist:**
- [ ] Migration is run
- [ ] IES files migrated correctly
- [ ] IIES files migrated correctly
- [ ] IAH files migrated correctly
- [ ] ILP files migrated correctly
- [ ] No errors in migration logs
- [ ] Old data still exists (migration is non-destructive)
- [ ] Files are accessible in new structure

---

#### 3. Column Rename Migration (2025-01-09)
**File:** `2025_01_09_205026_rename_name_and_caste_columns.php`

**What it does:**
- Renames `name` → `bname` in `project_IES_personal_info`
- Renames `caste` → `bcaste` in `project_IES_personal_info`
- **Copies data** before dropping old columns

**Action Required:**
```bash
# Check if migration is already run
php artisan migrate:status | grep "rename_name_and_caste"

# If NOT run, run it:
php artisan migrate --path=database/migrations/2025_01_09_205026_rename_name_and_caste_columns.php

# Verify columns exist:
php artisan tinker
>>> Schema::hasColumn('project_IES_personal_info', 'bname') // Should be true
>>> Schema::hasColumn('project_IES_personal_info', 'bcaste') // Should be true
>>> Schema::hasColumn('project_IES_personal_info', 'name') // Should be false
>>> Schema::hasColumn('project_IES_personal_info', 'caste') // Should be false
```

**Checklist:**
- [ ] Migration is run
- [ ] Old columns (`name`, `caste`) are dropped
- [ ] New columns (`bname`, `bcaste`) exist
- [ ] Data was copied correctly
- [ ] No data loss

---

## 🔍 COLUMN VERIFICATION CHECKLIST

### Step 8: Verify Key Columns Exist

#### Projects Table Columns (Critical)
- [ ] `project_id` - Unique identifier
- [ ] `user_id` - Project owner
- [ ] `project_type` - Type of project
- [ ] `project_title` - Project title
- [ ] `status` - Project status
- [ ] `goal` - Project goal
- [ ] `predecessor_project_id` ⚠️ **NEW** (2026-01-07)
- [ ] `initial_information` ⚠️ **NEW** (2026-01-07) - TEXT
- [ ] `target_beneficiaries` ⚠️ **NEW** (2026-01-07) - TEXT
- [ ] `general_situation` ⚠️ **NEW** (2026-01-07) - TEXT
- [ ] `need_of_project` ⚠️ **NEW** (2026-01-07) - TEXT
- [ ] `in_charge` - Should be nullable ⚠️ (2026-01-07)
- [ ] `completion_status` ⚠️ **NEW** (2026-01-08)
- [ ] `local_contribution` ⚠️ **NEW** (2026-01-07)

#### DP_Reports Table Columns
- [ ] `report_id` - Unique identifier
- [ ] `project_id` - Foreign key
- [ ] `user_id` - Reporter
- [ ] `status` - Report status
- [ ] `revert_reason` ⚠️ **VERIFY EXISTS** (2025-06-27)
- [ ] `is_budget_row` ⚠️ **VERIFY EXISTS** (2025-06-29) - For account details

#### Users Table Columns
- [ ] `role` - Should include 'applicant' ⚠️ **VERIFY** (2025-06-24)
- [ ] `province` - Enum values
- [ ] `status` - active/inactive

---

## 📊 DATA INTEGRITY CHECKS

### Step 9: Verify Data Relationships

#### Foreign Key Integrity
- [ ] All `project_id` references exist in `projects` table
- [ ] All `user_id` references exist in `users` table
- [ ] All `predecessor_project_id` references exist in `projects` table (if set)
- [ ] All `report_id` references exist in `DP_Reports` table
- [ ] All `changed_by_user_id` references exist in `users` table

**Check SQL:**
```sql
-- Check for orphaned projects
SELECT * FROM projects WHERE user_id NOT IN (SELECT id FROM users);

-- Check for orphaned reports
SELECT * FROM DP_Reports WHERE project_id NOT IN (SELECT project_id FROM projects);

-- Check for invalid predecessor projects
SELECT * FROM projects WHERE predecessor_project_id IS NOT NULL 
AND predecessor_project_id NOT IN (SELECT project_id FROM projects);

-- Check for orphaned status histories
SELECT * FROM project_status_histories WHERE project_id NOT IN (SELECT project_id FROM projects);
```

#### Data Consistency Checks
- [ ] All projects have at least one objective (or null is acceptable)
- [ ] All reports have associated project
- [ ] All status histories have valid project_id
- [ ] All activity histories have valid related_id

---

## 🔧 POST-RESTORATION FIXES

### Step 10: Fix Common Issues

#### Issue 1: Missing Indexes
```sql
-- Verify key indexes exist:
SHOW INDEXES FROM projects WHERE Key_name IN ('projects_project_id_unique', 'projects_user_id_foreign');
SHOW INDEXES FROM DP_Reports WHERE Key_name IN ('dp_reports_report_id_unique', 'dp_reports_project_id_foreign');
SHOW INDEXES FROM users WHERE Key_name IN ('users_email_unique');
```

#### Issue 2: Auto-Increment Values
```sql
-- Fix auto-increment if needed:
ALTER TABLE projects AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM projects);
ALTER TABLE users AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM users);
ALTER TABLE DP_Reports AUTO_INCREMENT = (SELECT MAX(id) + 1 FROM DP_Reports);
```

#### Issue 3: Foreign Key Constraints
```sql
-- Re-enable foreign key checks if disabled:
SET FOREIGN_KEY_CHECKS = 1;

-- Verify constraints exist:
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'projectsReports'
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

## 🧪 FUNCTIONAL VERIFICATION

### Step 11: Test Application Functionality

#### User Authentication
- [ ] Users can log in
- [ ] Password reset works
- [ ] Sessions are maintained
- [ ] Role-based access works

#### Project Management
- [ ] Create new project
- [ ] Edit existing project
- [ ] View project details
- [ ] Submit project for approval
- [ ] Approve/reject/revert project
- [ ] View project status history
- [ ] Upload project attachments
- [ ] View project attachments (old and new structure)

#### Report Management
- [ ] Create monthly report
- [ ] Edit monthly report
- [ ] Submit report
- [ ] Approve/reject/revert report
- [ ] View report attachments
- [ ] Upload report photos
- [ ] Add outlook entries
- [ ] Add account details
- [ ] View aggregated reports (quarterly, half-yearly, annual)

#### Key Information Section
- [ ] All 5 fields visible in create form
- [ ] All 5 fields visible in edit form
- [ ] All 5 fields display in show view
- [ ] Predecessor project selection works
- [ ] Predecessor project populates Key Information fields

#### Activity History
- [ ] Activity history table has data
- [ ] Project status changes are logged
- [ ] Report status changes are logged
- [ ] Activity history views work (my-activities, team-activities, all-activities)

#### Notifications
- [ ] Notifications table exists
- [ ] Notification preferences table exists
- [ ] Notifications are created on project approval/rejection
- [ ] Notifications are created on report submission
- [ ] Notification dropdown works
- [ ] Notification center page loads

---

## 📝 SPECIAL MIGRATION NOTES

### Column Rename Migration (2025-01-09)
**IMPORTANT:** This migration:
1. Adds new columns (`bname`, `bcaste`)
2. **Copies data** from old columns (`name`, `caste`)
3. Drops old columns

**If migration is already run:**
- Old columns (`name`, `caste`) should NOT exist
- New columns (`bname`, `bcaste`) should exist
- Data should be in new columns

**If migration has NOT been run and old data exists:**
- Data is in old columns
- Migration will copy data automatically
- Old columns will be dropped after copy

### Attachment Files Migration (2026-01-08)
**IMPORTANT:** This migration is **idempotent** and **safe to run multiple times**:
- Checks for existing files before creating
- Does NOT delete from old tables
- Does NOT move/delete files from storage
- Can be safely re-run if needed

**If migration has NOT been run:**
- Old attachment data is in parent table columns (e.g., `project_IES_attachments.aadhar_card`)
- New file tables are empty
- Migration will copy data to new structure
- Old data remains intact

**If migration is already run:**
- New file tables should have data
- Old tables should still have data (migration is non-destructive)
- Both old and new structures coexist

---

## 🚨 TROUBLESHOOTING

### Problem: Migrations show as "Pending" after restoration
**Solution:**
```bash
# Check migrations table
php artisan tinker
>>> DB::table('migrations')->count()
>>> DB::table('migrations')->orderBy('id', 'desc')->take(10)->get()

# If migrations table is empty or incomplete:
# Option 1: Run all migrations (safe if database structure is correct)
php artisan migrate

# Option 2: Mark specific migrations as run (if already applied)
php artisan migrate:status
# Then manually insert into migrations table if needed
```

### Problem: Foreign Key Constraint Errors
**Solution:**
```sql
-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Run your fixes

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify constraints
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'projectsReports' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### Problem: Data Migration Fails
**Solution:**
```bash
# Check Laravel log for errors
tail -200 storage/logs/laravel.log

# Re-run migration (idempotent)
php artisan migrate --path=database/migrations/2026_01_08_135526_migrate_existing_attachments_to_multiple_files.php --force

# Verify data manually
php artisan tinker
# Run verification queries as shown in Step 7.2
```

### Problem: Missing Columns After Restoration
**Solution:**
```bash
# Run specific migration to add column
php artisan migrate --path=database/migrations/YYYY_MM_DD_add_column_name_to_table.php

# Or run all pending migrations
php artisan migrate
```

---

## ✅ FINAL VERIFICATION CHECKLIST

### Database Structure
- [ ] All 127 migrations are run
- [ ] All tables exist
- [ ] All key columns exist
- [ ] All foreign keys are intact
- [ ] All indexes are created
- [ ] Auto-increment values are correct

### Data Migrations
- [ ] Activity history migration is run
- [ ] Attachment files migration is run
- [ ] Column rename migration is run (if applicable)
- [ ] All data counts match expected values
- [ ] No orphaned records

### Application Functionality
- [ ] Users can log in
- [ ] Projects can be created/edited/viewed
- [ ] Reports can be created/edited/viewed
- [ ] File uploads work
- [ ] Status changes are logged
- [ ] Notifications work
- [ ] Activity history works

### Security
- [ ] Truncate scripts are removed or secured
- [ ] No sensitive data in logs
- [ ] Database credentials are secure
- [ ] File permissions are correct

---

## 📚 REFERENCE DOCUMENTS

### Migration-Related Documentation
- `Documentations/REVIEW/Reports Updates/Phase_5_Migrations_And_Models_Created.md`
- `Documentations/REVIEW/attachements Review/Migration_Safety_Analysis.md`
- `Documentations/REVIEW/5th Review/Activity report/Implementation_Status.md`
- `Documentations/REVIEW/5th Review/Notification_System_Implementation_Complete.md`
- `Documentations/REVIEW/project flow/Status_Tracking_Implementation_Summary.md`
- `Documentations/REVIEW/4th Review/Implementation_Completed_Tasks.md`

### Data Migration Details
- `Documentations/REVIEW/attachements Review/Migration_Execution_Report.md`
- `Documentations/REVIEW/5th Review/Activity report/Phase_6_Testing_Summary.md`

---

## 🎯 SUMMARY

**Total Migrations:** 127 files  
**Data Migrations:** 2 critical migrations  
**Key New Tables (2026):** 17 tables  
**Critical Columns Added:** 6+ new columns in projects table  

**Priority Actions:**
1. ✅ Verify all migrations are run
2. ✅ Run data migrations (2 critical ones)
3. ✅ Verify data integrity
4. ✅ Test application functionality
5. ✅ Remove/securely store truncate scripts

---

**Last Updated:** January 2025  
**Status:** ⚠️ **COMPREHENSIVE CHECKLIST - USE FOR DATABASE RESTORATION**
