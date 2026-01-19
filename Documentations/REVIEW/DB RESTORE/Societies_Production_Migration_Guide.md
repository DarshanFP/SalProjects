# Societies Production Migration Guide

## 📋 Overview

This guide provides step-by-step instructions for running the Societies table migrations and related changes in a **production environment**. This migration adds the Societies table to organize societies within provinces and updates the Centers table to support the province-society-center relationship structure.

**⚠️ IMPORTANT:** Always backup your production database before running migrations!

**Key Changes:**
- Creates `societies` table (linked to provinces)
- Adds `society_id` column to `centers` table (nullable, for backward compatibility)
- Seeds societies based on actual data from users and projects
- Enables provincial users to manage societies, centers, and provincials in their province

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
- [ ] **Verify `provinces` table exists** (prerequisite)
- [ ] **Verify `centers` table exists** (prerequisite)

### 3. Code Deployment
- [ ] Deploy all migration files to production:
  - [ ] `2026_01_13_144931_create_societies_table.php`
  - [ ] `2026_01_13_144932_add_society_id_to_centers_table.php`
- [ ] Deploy Society model file: `app/Models/Society.php`
- [ ] Deploy SocietySeeder file: `database/seeders/SocietySeeder.php`
- [ ] Deploy updated controller files:
  - [ ] `app/Http/Controllers/ProvincialController.php`
  - [ ] `app/Http/Controllers/GeneralController.php` (if updated)
- [ ] Deploy updated view files:
  - [ ] `resources/views/provincial/societies/` (all files)
  - [ ] `resources/views/provincial/provincials/` (all files)
  - [ ] `resources/views/provincial/centers/edit.blade.php`
  - [ ] `resources/views/provincial/sidebar.blade.php`
- [ ] Deploy updated routes: `routes/web.php`
- [ ] Verify code is up-to-date with latest changes

### 4. Testing on Staging
- [ ] Run migrations on staging environment first
- [ ] Test all functionality after migration
- [ ] Verify data integrity
- [ ] Test rollback procedures
- [ ] Verify societies are seeded correctly

---

## 🚀 Migration Steps

### Step 1: Enable Maintenance Mode

```bash
php artisan down --message="Database migration in progress - Adding Societies table" --retry=60
```

This puts your application in maintenance mode to prevent data changes during migration.

---

### Step 2: Verify Prerequisites

Before proceeding, verify that the required tables exist:

```bash
php artisan tinker --execute="
echo 'Checking prerequisites...' . PHP_EOL;
echo 'Provinces table exists: ' . (Schema::hasTable('provinces') ? 'YES' : 'NO') . PHP_EOL;
echo 'Centers table exists: ' . (Schema::hasTable('centers') ? 'YES' : 'NO') . PHP_EOL;
echo 'Provinces count: ' . \App\Models\Province::count() . PHP_EOL;
echo 'Centers count: ' . \App\Models\Center::count() . PHP_EOL;
"
```

**Expected Output:**
```
Checking prerequisites...
Provinces table exists: YES
Centers table exists: YES
Provinces count: 9 (or your actual count)
Centers count: 80 (or your actual count)
```

**⚠️ If prerequisites are missing:** You must run the Provinces and Centers migrations first. See `Provinces_Centers_Production_Migration_Guide.md`.

---

### Step 3: Run Database Migrations

Run migrations in the **exact order** specified below:

#### 3.1 Create Societies Table

```bash
php artisan migrate --path=database/migrations/2026_01_13_144931_create_societies_table.php
```

**Expected Output:**
```
INFO  Running migrations.
2026_01_13_144931_create_societies_table ......................... DONE
```

**What this does:**
- Creates `societies` table with:
  - `id` (primary key)
  - `province_id` (foreign key to provinces)
  - `name` (society name)
  - `is_active` (boolean, default true)
  - `created_at`, `updated_at` (timestamps)
- Adds unique constraint: `(province_id, name)` - same society name can exist in different provinces
- Adds indexes on `province_id` and `name`

#### 3.2 Add Society ID to Centers Table

```bash
php artisan migrate --path=database/migrations/2026_01_13_144932_add_society_id_to_centers_table.php
```

**Expected Output:**
```
INFO  Running migrations.
2026_01_13_144932_add_society_id_to_centers_table ................ DONE
```

**What this does:**
- Adds `society_id` column to `centers` table (nullable)
- Adds foreign key constraint to `societies` table
- Sets `onDelete('set null')` - centers belong to provinces, not directly to societies
- Adds index on `society_id`

**⚠️ Note:** This migration may take longer if you have many centers, as it modifies the centers table structure.

---

### Step 4: Seed Societies Data

#### 4.1 Seed Societies

```bash
php artisan db:seed --class=SocietySeeder
```

**Expected Output:**
```
INFO  Seeding database.
Starting Society Seeding...
Processing province: Bangalore
  ✓ Created society: St. Ann's Society Southern Region
  ...
Processing province: Vijayawada
  ✓ Created society: SARVAJANA SNEHA CHARITABLE TRUST
  ✓ Created society: ST. ANN'S EDUCATIONAL SOCIETY
Processing province: Visakhapatnam
  ✓ Created society: ST. ANN'S SOCIETY, VISAKHAPATNAM
  ✓ Created society: WILHELM MEYERS DEVELOPMENTAL SOCIETY
...

Society Seeding Complete!
  Created: X societies
  Skipped (already exist): 0 societies
  Total societies in database: X

Societies by Province:
  Vijayawada: 2 societies
    - SARVAJANA SNEHA CHARITABLE TRUST
    - ST. ANN'S EDUCATIONAL SOCIETY
  Visakhapatnam: 2 societies
    - ST. ANN'S SOCIETY, VISAKHAPATNAM
    - WILHELM MEYERS DEVELOPMENTAL SOCIETY
  ...
```

**What this does:**
- Extracts society names from existing `users` and `projects` tables
- Creates societies for each province based on actual data
- For Vijayawada: Creates 2 specific societies
- For Visakhapatnam: Creates 2 specific societies
- For other provinces: Creates societies from existing user/project data

**⚠️ Important:**
- Review the seeding output carefully
- Verify that the correct societies are created for each province
- Check that Vijayawada has exactly 2 societies
- Check that Visakhapatnam has exactly 2 societies

---

### Step 5: Verify Migration Results

#### 5.1 Quick Verification

```bash
php artisan tinker --execute="
echo '=== SOCIETIES MIGRATION VERIFICATION ===' . PHP_EOL;
echo 'Societies count: ' . \App\Models\Society::count() . PHP_EOL;
echo 'Centers with society_id: ' . \App\Models\Center::whereNotNull('society_id')->count() . PHP_EOL;
echo PHP_EOL . 'Societies by Province:' . PHP_EOL;
\App\Models\Province::with('societies')->get()->each(function(\$p) {
    if (\$p->societies->count() > 0) {
        echo '  ' . \$p->name . ': ' . \$p->societies->count() . ' societies' . PHP_EOL;
    }
});
"
```

**Expected Results:**
- Societies count: Should match the number of unique societies in your data
- Centers with society_id: Should be 0 (society_id is nullable and not set initially)
- Each province should have at least 0 societies (some may have none)

#### 5.2 Verify Specific Provinces

```bash
php artisan tinker --execute="
echo '=== VIJAYAWADA SOCIETIES ===' . PHP_EOL;
\$vijayawada = \App\Models\Province::where('name', 'Vijayawada')->first();
if (\$vijayawada) {
    \$societies = \App\Models\Society::where('province_id', \$vijayawada->id)->get(['name']);
    echo 'Count: ' . \$societies->count() . PHP_EOL;
    \$societies->each(function(\$s) { echo '  - ' . \$s->name . PHP_EOL; });
} else {
    echo 'Vijayawada province not found' . PHP_EOL;
}

echo PHP_EOL . '=== VISAKHAPATNAM SOCIETIES ===' . PHP_EOL;
\$visakhapatnam = \App\Models\Province::where('name', 'Visakhapatnam')->first();
if (\$visakhapatnam) {
    \$societies = \App\Models\Society::where('province_id', \$visakhapatnam->id)->get(['name']);
    echo 'Count: ' . \$societies->count() . PHP_EOL;
    \$societies->each(function(\$s) { echo '  - ' . \$s->name . PHP_EOL; });
} else {
    echo 'Visakhapatnam province not found' . PHP_EOL;
}
"
```

**Expected Results:**
- Vijayawada: Should have exactly 2 societies:
  - SARVAJANA SNEHA CHARITABLE TRUST
  - ST. ANN'S EDUCATIONAL SOCIETY
- Visakhapatnam: Should have exactly 2 societies:
  - ST. ANN'S SOCIETY, VISAKHAPATNAM
  - WILHELM MEYERS DEVELOPMENTAL SOCIETY

#### 5.3 Check Laravel Logs

```bash
tail -n 200 storage/logs/laravel.log | grep -i "society\|migration"
```

Look for any warnings or errors related to the migration.

#### 5.4 Verify Database Structure

```sql
-- Check societies table structure
DESCRIBE societies;

-- Check centers table has society_id
DESCRIBE centers;

-- Verify foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND (TABLE_NAME = 'societies' OR TABLE_NAME = 'centers')
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

### Step 6: Clear Application Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

This ensures all new routes, views, and configurations are loaded.

---

### Step 7: Test Application Functionality

Before disabling maintenance mode, test critical functionality:

- [ ] **Provincial User Access:**
  - [ ] Provincial user can view societies list
  - [ ] Provincial user can create new society
  - [ ] Provincial user can edit society
  - [ ] Provincial user can view provincials list
  - [ ] Provincial user can create new provincial
  - [ ] Provincial user can edit provincial
  - [ ] Provincial user can view centers list
  - [ ] Provincial user can edit center
  - [ ] Sidebar shows new management sections

- [ ] **General User Access:**
  - [ ] General user can view societies list
  - [ ] General user can create/edit/delete societies
  - [ ] General user can view centers list
  - [ ] General user can create/edit/delete centers

- [ ] **Data Integrity:**
  - [ ] Society dropdowns populate correctly
  - [ ] Center dropdowns populate correctly
  - [ ] Province-society relationships work
  - [ ] All centers in a province are available to all societies in that province

- [ ] **User Creation:**
  - [ ] Creating executor/applicant with society selection works
  - [ ] Society dropdown filters by province correctly
  - [ ] Center dropdown shows all centers from selected province

---

### Step 8: Disable Maintenance Mode

```bash
php artisan up
```

---

## 🔄 Rollback Procedures

If something goes wrong, you can rollback migrations:

### Rollback Society ID Column from Centers

```bash
php artisan migrate:rollback --step=1
```

This will:
- Remove `society_id` column from `centers` table
- Remove foreign key constraint
- Remove index

### Rollback Societies Table

```bash
php artisan migrate:rollback --step=1
```

This will:
- Drop the `societies` table
- Remove all society data

### Full Rollback (Use with Caution!)

```bash
php artisan migrate:rollback --step=2
```

This will:
1. Remove `society_id` from centers table
2. Drop societies table

**⚠️ WARNING:** Only use full rollback if absolutely necessary. Always restore from backup instead if possible.

---

## 📊 Post-Migration Verification

### 1. Data Integrity Checks

```sql
-- Check societies
SELECT COUNT(*) FROM societies;
-- Should match your expected count

-- Check societies by province
SELECT p.name, COUNT(s.id) as society_count
FROM provinces p
LEFT JOIN societies s ON p.id = s.province_id
GROUP BY p.id, p.name
ORDER BY p.name;

-- Verify foreign key constraints
SELECT COUNT(*) FROM societies s
LEFT JOIN provinces p ON s.province_id = p.id
WHERE s.province_id IS NOT NULL AND p.id IS NULL;
-- Expected: 0 (no orphaned records)

-- Check centers with society_id (should be 0 initially)
SELECT COUNT(*) FROM centers WHERE society_id IS NOT NULL;
-- Expected: 0 (society_id is nullable and not set initially)

-- Verify centers can have society_id
SELECT COUNT(*) FROM centers c
LEFT JOIN societies s ON c.society_id = s.id
WHERE c.society_id IS NOT NULL AND s.id IS NULL;
-- Expected: 0 (no orphaned records if any society_id is set)
```

### 2. Application Testing

Test the following in your application:

- [ ] **Provincial User Management:**
  - [ ] View societies in province
  - [ ] Create new society
  - [ ] Edit existing society
  - [ ] View provincials in province
  - [ ] Create new provincial user
  - [ ] Edit existing provincial user
  - [ ] View centers in province
  - [ ] Edit existing center

- [ ] **General User Management:**
  - [ ] View all societies
  - [ ] Create/edit/delete societies
  - [ ] View all centers
  - [ ] Create/edit/delete centers

- [ ] **User Creation:**
  - [ ] Society dropdown appears in user creation form
  - [ ] Society dropdown filters by province
  - [ ] Center dropdown shows all centers from selected province
  - [ ] User can be created with society selection

- [ ] **Data Relationships:**
  - [ ] Province → Societies relationship works
  - [ ] Society → Centers relationship works (all centers in province)
  - [ ] Province → Centers relationship works

---

## ⚠️ Troubleshooting

### Issue: Migration Fails with Foreign Key Error

**Cause:** `provinces` table doesn't exist or has different structure.

**Solution:**
1. Verify `provinces` table exists: `php artisan tinker --execute="echo Schema::hasTable('provinces') ? 'YES' : 'NO';"`
2. Run Provinces migration first if missing
3. Check Laravel logs for specific error

### Issue: Society Seeder Creates Wrong Societies

**Cause:** Seeder logic may need adjustment based on your actual data.

**Solution:**
1. Review `SocietySeeder.php` logic
2. Check what societies exist in your `users` and `projects` tables
3. Manually adjust seeder if needed
4. Delete incorrect societies: `php artisan tinker --execute="App\Models\Society::query()->delete();"`
5. Re-run seeder with corrected logic

### Issue: Provincial User Can't See Management Links

**Cause:** Cache not cleared or routes not registered.

**Solution:**
1. Clear all caches: `php artisan cache:clear && php artisan config:clear && php artisan view:clear && php artisan route:clear`
2. Verify routes exist: `php artisan route:list | grep provincial`
3. Check sidebar view file is updated
4. Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)

### Issue: Society Dropdown Not Filtering by Province

**Cause:** JavaScript not loading or incorrect implementation.

**Solution:**
1. Check browser console for JavaScript errors
2. Verify JavaScript code in user creation form
3. Check that province selection triggers society filter
4. Verify societies are loaded correctly

### Issue: Performance Issues

**Cause:** Large number of societies or complex queries.

**Solution:**
1. Check database indexes are created
2. Monitor query performance
3. Consider adding caching for society lists
4. Optimize queries if needed

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
- [ ] Prerequisites verified: _______________

Migration Results:
- Societies table created: [ ] YES [ ] NO
- Society ID column added to centers: [ ] YES [ ] NO
- Societies seeded: _______
- Societies by province:
  - Vijayawada: _______ (Expected: 2)
  - Visakhapatnam: _______ (Expected: 2)
  - Other provinces: _______

Issues Encountered:
_________________________________
_________________________________

Post-Migration:
- [ ] Verification completed
- [ ] Application tested
- [ ] Maintenance mode disabled
- [ ] Monitoring active
- [ ] Caches cleared

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
5. **Role Permissions**: Verify provincial users can only access their province's data

---

## 📞 Support

If you encounter issues:

1. **Check Logs**: Review `storage/logs/laravel.log`
2. **Check Database**: Verify data integrity with SQL queries
3. **Verify Prerequisites**: Ensure provinces and centers tables exist
4. **Rollback**: Use rollback procedures if needed
5. **Restore Backup**: As last resort, restore from backup

---

## ✅ Post-Migration Checklist

- [ ] All migrations completed successfully
- [ ] Societies table created and populated
- [ ] Society ID column added to centers table
- [ ] Data verified and correct
- [ ] Vijayawada has 2 societies (verified)
- [ ] Visakhapatnam has 2 societies (verified)
- [ ] Application functionality tested
- [ ] Provincial user management works
- [ ] General user management works
- [ ] User creation with societies works
- [ ] Maintenance mode disabled
- [ ] All caches cleared
- [ ] Monitoring active
- [ ] Team notified of completion
- [ ] Documentation updated
- [ ] Backup verified and stored

---

## 📚 Related Documents

- `Provinces_Centers_Production_Migration_Guide.md` - Prerequisite migration guide
- `Provinces_And_Centers_Table_Implementation_Plan.md` - Related implementation plan
- `DATABASE_MIGRATION_SUMMARY.md` - Overall migration status

---

## 🔗 Key Relationships

After migration, the following relationships are established:

1. **Province → Societies (1:N)**
   - One province can have many societies
   - Society belongs to one province

2. **Province → Centers (1:N)**
   - One province can have many centers
   - Center belongs to one province

3. **Society → Centers (Indirect)**
   - All centers in a province are available to all societies in that province
   - Centers are shared resources within a province
   - `society_id` in centers table is nullable (for backward compatibility)

4. **User → Society (via society_name)**
   - Users have a `society_name` field (string)
   - This can be linked to the `societies` table for better data integrity

---

## 🎯 Expected Results

After successful migration:

- **Tables Created:**
  - `societies` table with proper structure and constraints

- **Tables Modified:**
  - `centers` table has new `society_id` column (nullable)

- **Data Seeded:**
  - Societies created based on actual data from users and projects
  - Vijayawada: 2 societies
  - Visakhapatnam: 2 societies
  - Other provinces: Based on existing data

- **Functionality Enabled:**
  - Provincial users can manage societies in their province
  - Provincial users can manage provincials in their province
  - Provincial users can edit centers in their province
  - General users can manage all societies and centers
  - User creation forms support society selection

---

**Document Version:** 1.0  
**Created:** 2026-01-13  
**Last Updated:** 2026-01-13  
**Status:** Ready for Production Use  
**Prerequisites:** Provinces and Centers tables must exist
