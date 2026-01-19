<?php

/**
 * Province-Coordinator Relationship Fix - Verification Script
 *
 * This script verifies the implementation of the province-coordinator relationship fix.
 * Run this script AFTER running migrations to verify the changes.
 *
 * Usage:
 *   php artisan tinker < Province_Coordinator_Relationship_Phase_7_Verification_Script.php
 *
 * Or copy the queries and run them manually.
 */

use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== Province-Coordinator Relationship Fix Verification ===\n\n";

// 1. Verify column is removed
echo "1. Verifying column removal...\n";
$hasColumn = Schema::hasColumn('provinces', 'provincial_coordinator_id');
if ($hasColumn) {
    echo "   ❌ ERROR: Column 'provincial_coordinator_id' still exists!\n";
} else {
    echo "   ✅ Column 'provincial_coordinator_id' successfully removed.\n";
}
echo "\n";

// 2. Check provincial users
echo "2. Checking provincial users...\n";
$provincialUsers = User::where('role', 'provincial')->count();
echo "   Total provincial users: {$provincialUsers}\n";

$migratedUsers = User::where('role', 'provincial')
    ->where('email', 'like', '%@system.local')
    ->count();
echo "   Migrated provincial users: {$migratedUsers}\n";
echo "\n";

// 3. Check provinces
echo "3. Checking provinces...\n";
$provinces = Province::count();
echo "   Total provinces: {$provinces}\n";

$provincesWithProvincialUsers = Province::has('provincialUsers')->count();
echo "   Provinces with provincial users: {$provincesWithProvincialUsers}\n";
echo "\n";

// 4. Verify relationships
echo "4. Verifying relationships...\n";
$province = Province::with('provincialUsers')->first();
if ($province) {
    try {
        $provincialUsers = $province->provincialUsers;
        echo "   ✅ provincialUsers() relationship works.\n";
        echo "   Province '{$province->name}' has {$provincialUsers->count()} provincial user(s).\n";
    } catch (\Exception $e) {
        echo "   ❌ ERROR: provincialUsers() relationship failed: {$e->getMessage()}\n";
    }

    // Try to access old coordinator relationship (should fail)
    try {
        $coordinator = $province->coordinator;
        echo "   ❌ ERROR: coordinator() relationship still exists!\n";
    } catch (\Exception $e) {
        echo "   ✅ coordinator() relationship correctly removed.\n";
    }
} else {
    echo "   ⚠️  No provinces found in database.\n";
}
echo "\n";

// 5. Check data integrity
echo "5. Checking data integrity...\n";

// Check for orphaned province_id in users
$orphanedUsers = DB::table('users')
    ->leftJoin('provinces', 'users.province_id', '=', 'provinces.id')
    ->whereNotNull('users.province_id')
    ->whereNull('provinces.id')
    ->count();

if ($orphanedUsers > 0) {
    echo "   ❌ ERROR: Found {$orphanedUsers} users with orphaned province_id!\n";
} else {
    echo "   ✅ No orphaned province_id in users table.\n";
}

// Check for orphaned province_id in centers
$orphanedCenters = DB::table('centers')
    ->leftJoin('provinces', 'centers.province_id', '=', 'provinces.id')
    ->whereNull('provinces.id')
    ->count();

if ($orphanedCenters > 0) {
    echo "   ❌ ERROR: Found {$orphanedCenters} centers with orphaned province_id!\n";
} else {
    echo "   ✅ No orphaned province_id in centers table.\n";
}

// Check provincial users have valid parent_id
$provincialUsersWithoutParent = User::where('role', 'provincial')
    ->whereNull('parent_id')
    ->count();

if ($provincialUsersWithoutParent > 0) {
    echo "   ⚠️  WARNING: Found {$provincialUsersWithoutParent} provincial users without parent_id.\n";
} else {
    echo "   ✅ All provincial users have valid parent_id.\n";
}

// Check provincial users have valid province_id
$provincialUsersWithoutProvince = User::where('role', 'provincial')
    ->whereNull('province_id')
    ->count();

if ($provincialUsersWithoutProvince > 0) {
    echo "   ⚠️  WARNING: Found {$provincialUsersWithoutProvince} provincial users without province_id.\n";
} else {
    echo "   ✅ All provincial users have valid province_id.\n";
}
echo "\n";

// 6. Summary by province
echo "6. Summary by province:\n";
$provinces = Province::with('provincialUsers')->get();
foreach ($provinces as $province) {
    $count = $province->provincialUsers->count();
    echo "   - {$province->name}: {$count} provincial user(s)\n";
    foreach ($province->provincialUsers as $user) {
        $parent = $user->parent;
        $parentRole = $parent ? $parent->role : 'none';
        echo "     • {$user->name} (parent: {$parentRole})\n";
    }
}
echo "\n";

// 7. Final verification
echo "7. Final verification:\n";
$allChecksPassed = !$hasColumn && $orphanedUsers == 0 && $orphanedCenters == 0;

if ($allChecksPassed) {
    echo "   ✅ All checks passed! Implementation verified.\n";
} else {
    echo "   ❌ Some checks failed. Please review the errors above.\n";
}

echo "\n=== Verification Complete ===\n";
