# V1 Subdomain Data Migration Guide

**Date:** January 2025  
**Status:** 📋 **READY FOR DATA MIGRATION**  
**Database:** `u160871038_salpro`  
**All migrations completed:** ✅ Yes (127/127 migrations ran)

---

## 📊 Current Status

### ✅ Migrations Completed

-   All 127 migrations have been run successfully
-   All new tables created
-   All new columns added

### ⚠️ Data Migration Status

From your migration output, I can see:

1. **Attachment Files Migration:** ✅ **COMPLETED** (`2026_01_08_135526_migrate_existing_attachments_to_multiple_files`)
2. **Activity History Migration:** ✅ **COMPLETED** (`2026_01_09_130114_migrate_project_status_histories_to_activity_histories`)
3. **Province/Center Data Migration:** ⚠️ **FAILED** - `2026_01_11_170202_migrate_existing_provinces_and_centers_data` showed:

    - **Province migration: 0 users migrated, 85 failed**
    - **Center migration: 0 users migrated, 0 failed**
    - **Reason:** Provinces and Centers tables were empty when migration ran

4. **Provincial Coordinators Migration:** ✅ **COMPLETED** (likely no data to migrate)
5. **General Users Migration:** ✅ **COMPLETED** (likely no data to migrate)

---

## 🎯 Data Migration Tasks

### Task 1: Seed Provinces and Centers (CRITICAL - Must Do First!)

The province/center data migration failed because the `provinces` and `centers` tables are empty. You need to seed them first, then manually migrate the user data.

#### Step 1.1: Seed Provinces

```bash
cd public_html/V1
/opt/alt/php83/usr/bin/php artisan db:seed --class=ProvinceSeeder
```

**Expected Output:**

```
INFO  Seeding database.
Provinces seeded successfully: 9 provinces
```

#### Step 1.2: Seed Centers

```bash
/opt/alt/php83/usr/bin/php artisan db:seed --class=CenterSeeder
```

**Expected Output:**

```
INFO  Seeding database.
Centers seeded successfully: X centers
Total centers in database: X
```

#### Step 1.3: Verify Seeds

**Option 1: Standalone PHP Script (Recommended - No setup required)**

```bash
/opt/alt/php83/usr/bin/php verify_data_counts.php
```

This script will show:

-   Province count
-   Center count
-   User data migration status
-   Any pending migrations

**Option 2: Using Custom Artisan Command (Requires file upload and cache clear)**

```bash
# First, ensure the command file is uploaded, then:
/opt/alt/php83/usr/bin/php artisan config:clear
/opt/alt/php83/usr/bin/php artisan data:verify-counts
```

**Option 3: Single-line tinker command**

```bash
/opt/alt/php83/usr/bin/php artisan tinker --execute="echo 'Provinces: ' . \App\Models\Province::count() . PHP_EOL; echo 'Centers: ' . \App\Models\Center::count() . PHP_EOL;"
```

**Option 4: Interactive tinker (May have permission issues on shared hosting)**

```bash
/opt/alt/php83/usr/bin/php artisan tinker
```

Then run:

```php
echo 'Provinces: ' . \App\Models\Province::count() . PHP_EOL;
echo 'Centers: ' . \App\Models\Center::count() . PHP_EOL;
exit
```

**Expected Output:**

-   Provinces: 9 (or more)
-   Centers: 78+ (or more)

---

### Task 2: Migrate User Province/Center Data (After Seeding)

Since the migration already ran (but failed because tables were empty), you need to manually migrate the user data. The migration code is already in the migration file, but since it marked as "Ran", Laravel won't run it again automatically.

**You have two options:**

#### Option A: Use Tinker to Migrate Data (Recommended)

Create a quick script to run the migration logic manually:

```bash
/opt/alt/php83/usr/bin/php artisan tinker
```

Then run:

```php
// Migrate Province Data
$users = \App\Models\User::whereNotNull('province')
    ->where('province', '!=', '')
    ->whereNull('province_id')
    ->get();

$migrated = 0;
$failed = 0;

foreach ($users as $user) {
    $province = \App\Models\Province::whereRaw('UPPER(name) = ?', [strtoupper($user->province)])->first();

    if ($province) {
        $user->province_id = $province->id;
        $user->save();
        $migrated++;
    } else {
        $failed++;
        echo "Failed: User {$user->id} - Province '{$user->province}' not found\n";
    }
}

echo "Province migration: {$migrated} migrated, {$failed} failed\n";

// Migrate Center Data
$users = \App\Models\User::whereNotNull('center')
    ->where('center', '!=', '')
    ->whereNotNull('province_id')
    ->whereNull('center_id')
    ->get();

$migrated = 0;
$failed = 0;

foreach ($users as $user) {
    $province = \App\Models\Province::find($user->province_id);

    if (!$province) continue;

    $center = \App\Models\Center::where('province_id', $province->id)
        ->whereRaw('UPPER(name) = ?', [strtoupper($user->center)])
        ->first();

    if ($center) {
        $user->center_id = $center->id;
        $user->save();
        $migrated++;
    } else {
        $failed++;
    }
}

echo "Center migration: {$migrated} migrated, {$failed} failed\n";
```

#### Option B: Create a Custom Artisan Command (Alternative)

This would require creating a command file, but Option A is simpler.

---

### Task 3: Seed Societies (If Needed)

If you're using the societies feature:

```bash
/opt/alt/php83/usr/bin/php artisan db:seed --class=SocietySeeder
```

---

## 📋 Complete Data Migration Process

### Step 1: Seed Base Data

```bash
cd public_html/V1

# Seed provinces
/opt/alt/php83/usr/bin/php artisan db:seed --class=ProvinceSeeder

# Seed centers
/opt/alt/php83/usr/bin/php artisan db:seed --class=CenterSeeder

# Verify (using standalone script - recommended)
/opt/alt/php83/usr/bin/php verify_data_counts.php
```

### Step 2: Migrate User Data (Using Tinker)

```bash
/opt/alt/php83/usr/bin/php artisan tinker
```

Then paste the migration code from "Option A" above.

### Step 3: Verify User Data Migration

```bash
/opt/alt/php83/usr/bin/php verify_data_counts.php
```

This script shows all verification data including user migration status.

### Step 4: Seed Societies (Optional)

```bash
/opt/alt/php83/usr/bin/php artisan db:seed --class=SocietySeeder
```

---

## 🔍 Verification Checklist

After data migration, verify:

-   [ ] Provinces table has data (9+ provinces)
-   [ ] Centers table has data (78+ centers)
-   [ ] Users have `province_id` set (where applicable)
-   [ ] Users have `center_id` set (where applicable)
-   [ ] Attachment files migrated (if applicable)
-   [ ] Activity histories migrated (if applicable)
-   [ ] Societies seeded (if using societies)

---

## 📝 Quick Reference Commands

```bash
# Navigate to V1 directory
cd public_html/V1

# Seed provinces
/opt/alt/php83/usr/bin/php artisan db:seed --class=ProvinceSeeder

# Seed centers
/opt/alt/php83/usr/bin/php artisan db:seed --class=CenterSeeder

# Seed societies (if needed)
/opt/alt/php83/usr/bin/php artisan db:seed --class=SocietySeeder

# Check data counts (Recommended - standalone script, no setup needed)
/opt/alt/php83/usr/bin/php verify_data_counts.php

# Alternative: Using artisan command (requires file upload)
/opt/alt/php83/usr/bin/php artisan data:verify-counts

# Alternative: Using tinker (may have permission issues)
/opt/alt/php83/usr/bin/php artisan tinker --execute="echo 'Provinces: ' . \App\Models\Province::count() . PHP_EOL; echo 'Centers: ' . \App\Models\Center::count() . PHP_EOL; echo 'Users with province_id: ' . \App\Models\User::whereNotNull('province_id')->count() . PHP_EOL; echo 'Users with center_id: ' . \App\Models\User::whereNotNull('center_id')->count() . PHP_EOL;"
```

---

## ⚠️ Important Notes

1. **Seed Order Matters:** Provinces must be seeded before Centers (centers reference provinces)
2. **Migration Already Ran:** The data migration already ran but failed - you need to manually migrate user data using tinker
3. **Data Migration Logic:** The migration code tries to match user province/center strings to database records - if tables are empty, it fails

---

## 🚀 Next Steps

1. **Seed Provinces** using `ProvinceSeeder`
2. **Seed Centers** using `CenterSeeder`
3. **Manually migrate user data** using the tinker code provided above
4. **Verify** the data migration was successful
5. **Seed Societies** if needed

Let me know when you're ready to start seeding, and I can help you with the process!
