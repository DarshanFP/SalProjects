<?php

/**
 * Standalone script to migrate user province and center data
 * Run with: php migrate_user_province_center_data.php
 *
 * This script migrates existing user data from VARCHAR fields (province, center)
 * to foreign key fields (province_id, center_id)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap Laravel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Province;
use App\Models\Center;
use App\Models\User;

echo "🔄 User Data Migration Script\n";
echo "=============================\n\n";

// Migrate Province Data
echo "📋 Step 1: Migrating Province Data...\n";
$users = User::whereNotNull('province')
    ->where('province', '!=', '')
    ->where('province', '!=', 'none')
    ->whereNull('province_id')
    ->get();

$migrated = 0;
$failed = 0;
$failedUsers = [];

foreach ($users as $user) {
    $province = Province::whereRaw('UPPER(name) = ?', [strtoupper(trim($user->province))])->first();

    if ($province) {
        $user->province_id = $province->id;
        $user->save();
        $migrated++;
    } else {
        $failed++;
        $failedUsers[] = [
            'id' => $user->id,
            'name' => $user->name,
            'province' => $user->province
        ];
        echo "  ⚠️  Failed: User {$user->id} ({$user->name}) - Province '{$user->province}' not found\n";
    }
}

echo "\n✅ Province migration complete: {$migrated} migrated, {$failed} failed\n";

if ($failed > 0) {
    echo "\n📝 Failed Users (Province not found in database):\n";
    foreach ($failedUsers as $failedUser) {
        echo "   - User ID {$failedUser['id']}: {$failedUser['name']} - Province: '{$failedUser['province']}'\n";
    }
}

echo "\n";

// Migrate Center Data
echo "📋 Step 2: Migrating Center Data...\n";
$users = User::whereNotNull('center')
    ->where('center', '!=', '')
    ->whereNotNull('province_id')
    ->whereNull('center_id')
    ->get();

$migrated = 0;
$failed = 0;
$failedCenters = [];

foreach ($users as $user) {
    $province = Province::find($user->province_id);

    if (!$province) {
        echo "  ⚠️  User {$user->id} has province_id but province not found, skipping...\n";
        continue;
    }

    $center = Center::where('province_id', $province->id)
        ->whereRaw('UPPER(name) = ?', [strtoupper(trim($user->center))])
        ->first();

    if ($center) {
        $user->center_id = $center->id;
        $user->save();
        $migrated++;
    } else {
        $failed++;
        $failedCenters[] = [
            'id' => $user->id,
            'name' => $user->name,
            'center' => $user->center,
            'province' => $province->name
        ];
        echo "  ⚠️  Failed: User {$user->id} ({$user->name}) - Center '{$user->center}' not found in province '{$province->name}'\n";
    }
}

echo "\n✅ Center migration complete: {$migrated} migrated, {$failed} failed\n";

if ($failed > 0) {
    echo "\n📝 Failed Users (Center not found in database):\n";
    foreach ($failedCenters as $failedCenter) {
        echo "   - User ID {$failedCenter['id']}: {$failedCenter['name']} - Center: '{$failedCenter['center']}' (Province: {$failedCenter['province']})\n";
    }
}

echo "\n";

// Summary
echo "📊 Migration Summary\n";
echo "===================\n";
$totalUsers = User::count();
$usersWithProvinceId = User::whereNotNull('province_id')->count();
$usersWithCenterId = User::whereNotNull('center_id')->count();
$usersNeedingProvinceMigration = User::whereNotNull('province')
    ->where('province', '!=', 'none')
    ->where('province', '!=', '')
    ->whereNull('province_id')
    ->count();
$usersNeedingCenterMigration = User::whereNotNull('center')
    ->where('center', '!=', '')
    ->whereNotNull('province_id')
    ->whereNull('center_id')
    ->count();

echo "Total users: {$totalUsers}\n";
echo "Users with province_id: {$usersWithProvinceId}\n";
echo "Users with center_id: {$usersWithCenterId}\n";
echo "Users still needing province migration: {$usersNeedingProvinceMigration}\n";
echo "Users still needing center migration: {$usersNeedingCenterMigration}\n";

if ($usersNeedingProvinceMigration == 0 && $usersNeedingCenterMigration == 0) {
    echo "\n🎉 All user data migrations complete!\n";
} else {
    echo "\n⚠️  Some users still need migration. Review the failed users above.\n";
}
