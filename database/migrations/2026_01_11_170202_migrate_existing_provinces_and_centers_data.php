<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Province;
use App\Models\Center;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrates existing province and center data from VARCHAR fields to foreign keys.
     */
    public function up(): void
    {
        // Step 1: Migrate Province Data
        $this->migrateProvinceData();

        // Step 2: Migrate Center Data
        $this->migrateCenterData();

        // Step 3: Log migration results
        $this->logMigrationResults();
    }

    /**
     * Migrate province data from users.province to users.province_id
     */
    private function migrateProvinceData(): void
    {
        $users = User::whereNotNull('province')
            ->where('province', '!=', 'none')
            ->where('province', '!=', '')
            ->get();

        $migrated = 0;
        $failed = 0;
        $failedUsers = [];

        foreach ($users as $user) {
            // Try to find province by exact name match first
            $province = Province::where('name', $user->province)->first();

            // If not found, try case-insensitive match
            if (!$province) {
                $province = Province::whereRaw('UPPER(name) = ?', [strtoupper($user->province)])->first();
            }

            if ($province) {
                $user->province_id = $province->id;
                $user->save();
                $migrated++;
            } else {
                $failed++;
                $failedUsers[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'province' => $user->province
                ];
            }
        }

        // Log results
        if ($failed > 0) {
            \Log::warning('Province migration: Some users could not be matched', [
                'failed_count' => $failed,
                'failed_users' => $failedUsers
            ]);
        }

        echo "Province migration: {$migrated} users migrated, {$failed} failed\n";
    }

    /**
     * Migrate center data from users.center to users.center_id
     */
    private function migrateCenterData(): void
    {
        $users = User::whereNotNull('center')
            ->where('center', '!=', '')
            ->whereNotNull('province_id') // Only migrate centers for users with valid province_id
            ->get();

        $migrated = 0;
        $failed = 0;
        $failedUsers = [];

        foreach ($users as $user) {
            // Get the province for this user
            $province = Province::find($user->province_id);

            if (!$province) {
                $failed++;
                $failedUsers[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'center' => $user->center,
                    'reason' => 'No valid province_id'
                ];
                continue;
            }

            // Try to find center by exact name match within the province
            $center = Center::where('province_id', $province->id)
                ->where('name', $user->center)
                ->first();

            // If not found, try case-insensitive match
            if (!$center) {
                $center = Center::where('province_id', $province->id)
                    ->whereRaw('UPPER(name) = ?', [strtoupper($user->center)])
                    ->first();
            }

            // If still not found, try to find by partial match (for centers with slight variations)
            if (!$center) {
                $center = Center::where('province_id', $province->id)
                    ->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($user->center) . '%'])
                    ->first();
            }

            if ($center) {
                $user->center_id = $center->id;
                $user->save();
                $migrated++;
            } else {
                $failed++;
                $failedUsers[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'center' => $user->center,
                    'province' => $province->name,
                    'reason' => 'Center not found in database for this province'
                ];
            }
        }

        // Log results
        if ($failed > 0) {
            \Log::warning('Center migration: Some users could not be matched', [
                'failed_count' => $failed,
                'failed_users' => $failedUsers
            ]);
        }

        echo "Center migration: {$migrated} users migrated, {$failed} failed\n";
    }

    /**
     * Log migration results for verification
     */
    private function logMigrationResults(): void
    {
        $stats = [
            'total_users' => User::count(),
            'users_with_province_id' => User::whereNotNull('province_id')->count(),
            'users_with_center_id' => User::whereNotNull('center_id')->count(),
            'users_with_province_string' => User::whereNotNull('province')
                ->where('province', '!=', 'none')
                ->where('province', '!=', '')
                ->count(),
            'users_with_center_string' => User::whereNotNull('center')
                ->where('center', '!=', '')
                ->count(),
        ];

        \Log::info('Province and Center data migration completed', $stats);

        echo "\nMigration Statistics:\n";
        echo "Total users: {$stats['total_users']}\n";
        echo "Users with province_id: {$stats['users_with_province_id']}\n";
        echo "Users with center_id: {$stats['users_with_center_id']}\n";
        echo "Users with province (string): {$stats['users_with_province_string']}\n";
        echo "Users with center (string): {$stats['users_with_center_string']}\n";
    }

    /**
     * Reverse the migrations.
     * Note: This does not remove the foreign key data, just clears it.
     * The foreign keys will remain in the database structure.
     */
    public function down(): void
    {
        // Clear foreign key data (but keep the columns)
        User::whereNotNull('province_id')->update(['province_id' => null]);
        User::whereNotNull('center_id')->update(['center_id' => null]);

        echo "Province and center foreign keys cleared (columns remain)\n";
    }
};
