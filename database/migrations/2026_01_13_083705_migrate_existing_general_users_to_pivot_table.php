<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Province;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrates existing general user assignments from province_id to pivot table.
     * This allows general users to be assigned to multiple provinces.
     */
    public function up(): void
    {
        $migrated = 0;
        $errors = 0;

        // Find all general users with province_id assigned
        $generalUsers = User::where('role', 'general')
            ->whereNotNull('province_id')
            ->get();

        Log::info('Starting general user to pivot table migration', [
            'total_general_users' => $generalUsers->count(),
        ]);

        foreach ($generalUsers as $user) {
            try {
                $province = Province::find($user->province_id);

                if (!$province) {
                    Log::warning('Province not found for general user', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'province_id' => $user->province_id,
                    ]);
                    $errors++;
                    continue;
                }

                // Insert into pivot table (ignore if already exists)
                $exists = DB::table('provincial_user_province')
                    ->where('user_id', $user->id)
                    ->where('province_id', $province->id)
                    ->exists();

                if (!$exists) {
                    DB::table('provincial_user_province')->insert([
                        'user_id' => $user->id,
                        'province_id' => $province->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('General user migrated to pivot table', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'province_id' => $province->id,
                        'province_name' => $province->name,
                    ]);

                    $migrated++;
                } else {
                    Log::info('General user already in pivot table, skipping', [
                        'user_id' => $user->id,
                        'province_id' => $province->id,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Error migrating general user to pivot table', [
                    'user_id' => $user->id,
                    'user_name' => $user->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        Log::info('General user to pivot table migration completed', [
            'migrated' => $migrated,
            'errors' => $errors,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * This rollback does NOT restore province_id assignments
     * because that data might have been changed. It only removes
     * entries from the pivot table.
     */
    public function down(): void
    {
        // Remove all general users from pivot table
        $generalUserIds = User::where('role', 'general')->pluck('id')->toArray();

        $deleted = DB::table('provincial_user_province')
            ->whereIn('user_id', $generalUserIds)
            ->delete();

        Log::info('General user pivot table migration rolled back', [
            'deleted_entries' => $deleted,
        ]);
    }
};
