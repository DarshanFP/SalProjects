# Provinces & Centers Production Migration Guide

## 📋 Overview

This guide provides step-by-step instructions for running the Provinces and Centers table migrations in a **production environment**. Follow these steps carefully to ensure a safe and successful migration.

**⚠️ IMPORTANT:** Always backup your production database before running migrations!

---

## 🔒 Pre-Migration Checklist

### 1. Database Backup
- [ ] **CRITICAL**: Create a full database backup
- [ ] Verify backup is complete and accessible
- [ ] Store backup in a safe location
- [ ] Test backup restoration on staging if possible

### 2. Environment Verification
- [ ] Verify you're connected to the correct database
- [ ] Check current database size and available space
- [ ] Verify Laravel environment is set correctly
- [ ] Check application is in maintenance mode (recommended)

### 3. Code Deployment
- [ ] Deploy all migration files to production
- [ ] Deploy all model files to production
- [ ] Deploy all seeder files to production
- [ ] Verify code is up-to-date with latest changes

### 4. Testing on Staging
- [ ] Run migrations on staging environment first
- [ ] Test all functionality after migration
- [ ] Verify data integrity
- [ ] Test rollback procedures

---

## 🚀 Migration Steps

### Step 1: Enable Maintenance Mode

```bash
php artisan down --message="Database migration in progress" --retry=60
```

This puts your application in maintenance mode to prevent data changes during migration.

---

### Step 2: Run Database Migrations

Run migrations in the **exact order** specified below:

#### 2.1 Create Provinces Table
```bash
php artisan migrate --path=database/migrations/2026_01_11_165554_create_provinces_table.php
```

**Expected Output:**
```
INFO  Running migrations.
2026_01_11_165554_create_provinces_table ......................... DONE
```

#### 2.2 Create Centers Table
```bash
php artisan migrate --path=database/migrations/2026_01_11_165556_create_centers_table.php
```

**Expected Output:**
```
INFO  Running migrations.
2026_01_11_165556_create_centers_table ........................... DONE
```

#### 2.3 Add Foreign Keys to Users Table
```bash
php artisan migrate --path=database/migrations/2026_01_11_165558_add_province_center_foreign_keys_to_users_table.php
```

**Expected Output:**
```
INFO  Running migrations.
2026_01_11_165558_add_province_center_foreign_keys_to_users_table ... DONE
```

**⚠️ Note:** This migration may take longer if you have many users, as it modifies the users table structure.

---

### Step 3: Seed Initial Data

#### 3.1 Seed Provinces
```bash
php artisan db:seed --class=ProvinceSeeder
```

**Expected Output:**
```
INFO  Seeding database.
Provinces seeded successfully: 9 provinces
```

#### 3.2 Seed Centers
```bash
php artisan db:seed --class=CenterSeeder
```

**Expected Output:**
```
INFO  Seeding database.
Centers seeded successfully: 78 centers
Total centers in database: 78
```

---

### Step 4: Migrate Existing User Data

This is the **critical step** that migrates your existing user data:

```bash
php artisan migrate --path=database/migrations/2026_01_11_170202_migrate_existing_provinces_and_centers_data.php
```

**Expected Output:**
```
INFO  Running migrations.
Province migration: X users migrated, 0 failed
Center migration: Y users migrated, Z failed

Migration Statistics:
Total users: X
Users with province_id: X
Users with center_id: Y
Users with province (string): X
Users with center (string): X
... DONE
```

**⚠️ Important:**
- Review the migration output carefully
- Check for any failed migrations
- Note the success rates
- Check Laravel logs for any warnings

---

### Step 5: Verify Migration Results

#### 5.1 Quick Verification
```bash
php artisan tinker --execute="
echo 'Provinces: ' . \App\Models\Province::count() . PHP_EOL;
echo 'Centers: ' . \App\Models\Center::count() . PHP_EOL;
echo 'Users with province_id: ' . \App\Models\User::whereNotNull('province_id')->count() . PHP_EOL;
echo 'Users with center_id: ' . \App\Models\User::whereNotNull('center_id')->count() . PHP_EOL;
"
```

**Expected Results:**
- Provinces: 9
- Centers: 78
- Users with province_id: Should match your user count (minus admin/system users)
- Users with center_id: Should match users with centers

#### 5.2 Detailed Verification
```bash
php artisan tinker --execute="
// Check for unmigrated provinces
\$unmigrated = \App\Models\User::whereNotNull('province')
    ->where('province', '!=', 'none')
    ->whereNull('province_id')
    ->count();
echo 'Unmigrated provinces: ' . \$unmigrated . PHP_EOL;

// Check for unmigrated centers
\$unmigrated = \App\Models\User::whereNotNull('center')
    ->where('center', '!=', '')
    ->where('center', '!=', 'NONE')
    ->whereNull('center_id')
    ->count();
echo 'Unmigrated centers: ' . \$unmigrated . PHP_EOL;
"
```

#### 5.3 Check Laravel Logs
```bash
tail -n 100 storage/logs/laravel.log | grep -i "migration\|province\|center"
```

Look for any warnings or errors related to the migration.

---

### Step 6: Test Application Functionality

Before disabling maintenance mode, test critical functionality:

- [ ] User login works
- [ ] Province-related pages load
- [ ] Center-related dropdowns work
- [ ] User creation/editing works
- [ ] Reports with province/center filters work

---

### Step 7: Disable Maintenance Mode

```bash
php artisan up
```

---

## 🔄 Rollback Procedures

If something goes wrong, you can rollback migrations:

### Rollback Data Migration
```bash
php artisan migrate:rollback --step=1
```

This will clear the foreign key data but keep the table structure.

### Full Rollback (Use with Caution!)
```bash
php artisan migrate:rollback --step=4
```

This will:
1. Clear foreign key data
2. Remove foreign keys from users table
3. Drop centers table
4. Drop provinces table

**⚠️ WARNING:** Only use full rollback if absolutely necessary. Always restore from backup instead if possible.

---

## 📊 Post-Migration Verification

### 1. Data Integrity Checks

```sql
-- Check provinces
SELECT COUNT(*) FROM provinces;
-- Expected: 9

-- Check centers
SELECT COUNT(*) FROM centers;
-- Expected: 78

-- Check users with province_id
SELECT COUNT(*) FROM users WHERE province_id IS NOT NULL;
-- Should match your user count

-- Check users with center_id
SELECT COUNT(*) FROM users WHERE center_id IS NOT NULL;
-- Should match users with centers

-- Verify foreign key constraints
SELECT COUNT(*) FROM users u
LEFT JOIN provinces p ON u.province_id = p.id
WHERE u.province_id IS NOT NULL AND p.id IS NULL;
-- Expected: 0 (no orphaned records)

SELECT COUNT(*) FROM users u
LEFT JOIN centers c ON u.center_id = c.id
WHERE u.center_id IS NOT NULL AND c.id IS NULL;
-- Expected: 0 (no orphaned records)
```

### 2. Application Testing

Test the following in your application:

- [ ] **Province Management**
  - List provinces page loads
  - Create province works
  - Edit province works
  - Assign coordinator works

- [ ] **User Management**
  - Create user with province selection
  - Create user with center selection
  - Edit user province/center
  - Province/center dropdowns populate correctly

- [ ] **Filtering**
  - Filter by province works
  - Filter by center works
  - Reports filter correctly

- [ ] **Data Display**
  - Province names display correctly
  - Center names display correctly
  - Relationships work in views

---

## ⚠️ Troubleshooting

### Issue: Migration Fails with Foreign Key Error

**Cause:** Existing data doesn't match province/center names exactly.

**Solution:**
1. Check the migration logs
2. Manually fix data mismatches
3. Re-run the migration

### Issue: Some Centers Not Migrated

**Expected:** Some centers may not migrate if:
- Center name doesn't match exactly
- Province doesn't have that center in seed data
- Center is a placeholder value (like "NONE")

**Solution:**
1. Check Laravel logs for details
2. Manually add missing centers if needed
3. Re-run center migration for specific users

### Issue: Performance Issues

**Cause:** Large user table may cause slow migrations.

**Solution:**
1. Run migrations during low-traffic period
2. Consider running in batches for very large tables
3. Monitor server resources

### Issue: Seeder Fails

**Cause:** Duplicate data or constraint violations.

**Solution:**
1. Check if provinces/centers already exist
2. Use `firstOrCreate` (already implemented)
3. Check for unique constraint violations

---

## 📝 Migration Log Template

Use this template to document your production migration:

```
Migration Date: _______________
Migration Time: _______________
Performed By: _______________

Pre-Migration:
- [ ] Backup created: _______________
- [ ] Maintenance mode enabled: _______________
- [ ] Code deployed: _______________

Migration Results:
- Provinces created: _______
- Centers created: _______
- Users with province_id: _______ / _______
- Users with center_id: _______ / _______
- Migration success rate: _______%

Issues Encountered:
_________________________________
_________________________________

Post-Migration:
- [ ] Verification completed
- [ ] Application tested
- [ ] Maintenance mode disabled
- [ ] Monitoring active

Notes:
_________________________________
_________________________________
```

---

## 🔐 Security Considerations

1. **Database Access**: Ensure only authorized personnel have database access
2. **Backup Security**: Store backups securely
3. **Log Files**: Review logs for any sensitive data exposure
4. **Maintenance Mode**: Keep maintenance mode message user-friendly

---

## 📞 Support

If you encounter issues:

1. **Check Logs**: Review `storage/logs/laravel.log`
2. **Check Database**: Verify data integrity with SQL queries
3. **Rollback**: Use rollback procedures if needed
4. **Restore Backup**: As last resort, restore from backup

---

## ✅ Post-Migration Checklist

- [ ] All migrations completed successfully
- [ ] Data verified and correct
- [ ] Application functionality tested
- [ ] Maintenance mode disabled
- [ ] Monitoring active
- [ ] Team notified of completion
- [ ] Documentation updated
- [ ] Backup verified and stored

---

## 📚 Related Documents

- `Provinces_Centers_Migration_Completion_Summary.md` - Completion summary
- `Provinces_And_Centers_Table_Implementation_Plan.md` - Full implementation plan
- `Provinces_Centers_File_Checklist.md` - File-by-file checklist

---

**Document Version:** 1.0  
**Created:** 2026-01-11  
**Last Updated:** 2026-01-11  
**Status:** Ready for Production Use
