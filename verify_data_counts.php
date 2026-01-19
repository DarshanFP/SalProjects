<?php

/**
 * Standalone script to verify data counts
 * Run with: /opt/alt/php83/usr/bin/php verify_data_counts.php
 *
 * This script can be run directly without needing Laravel's command system
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap Laravel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Province;
use App\Models\Center;
use App\Models\User;

echo "📊 Data Verification Report\n";
echo "===========================\n\n";

// Provinces
$provinceCount = Province::count();
echo "Provinces: {$provinceCount}\n";

// Centers
$centerCount = Center::count();
echo "Centers: {$centerCount}\n";

echo "\n";

// User Data Migration Status
echo "👥 User Data Migration Status\n";
echo "-----------------------------\n";

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
echo "Users needing province migration: {$usersNeedingProvinceMigration}\n";
echo "Users needing center migration: {$usersNeedingCenterMigration}\n";

echo "\n";

// Summary
if ($provinceCount >= 9 && $centerCount >= 78) {
    echo "✅ Base data (Provinces & Centers) looks good!\n";
} else {
    echo "⚠️  Base data may need seeding:\n";
    if ($provinceCount < 9) {
        echo "   - Provinces: Expected 9+, found {$provinceCount}\n";
    }
    if ($centerCount < 78) {
        echo "   - Centers: Expected 78+, found {$centerCount}\n";
    }
}

if ($usersNeedingProvinceMigration > 0 || $usersNeedingCenterMigration > 0) {
    echo "⚠️  User data migration pending:\n";
    if ($usersNeedingProvinceMigration > 0) {
        echo "   - {$usersNeedingProvinceMigration} users need province migration\n";
    }
    if ($usersNeedingCenterMigration > 0) {
        echo "   - {$usersNeedingCenterMigration} users need center migration\n";
    }
} else {
    echo "✅ All user data migrations appear complete!\n";
}
