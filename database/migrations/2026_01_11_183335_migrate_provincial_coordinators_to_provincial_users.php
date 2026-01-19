<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Province;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration migrates existing provincial_coordinator_id assignments
     * to provincial users (children of coordinators).
     *
     * Since coordinators should have access to ALL provinces by default,
     * this migration is mainly for data preservation. If a coordinator is
     * assigned to a province, we create a provincial user (child of coordinator)
     * for that province to preserve the relationship.
     */
    public function up(): void
    {
        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        // Find all provinces with provincial_coordinator_id set
        $provincesWithCoordinators = Province::whereNotNull('provincial_coordinator_id')
            ->with('coordinator')
            ->get();

        Log::info('Starting provincial coordinator migration', [
            'total_provinces' => $provincesWithCoordinators->count(),
        ]);

        foreach ($provincesWithCoordinators as $province) {
            try {
                $coordinator = $province->coordinator;

                if (!$coordinator) {
                    Log::warning('Province has coordinator_id but coordinator not found', [
                        'province_id' => $province->id,
                        'province_name' => $province->name,
                        'coordinator_id' => $province->provincial_coordinator_id,
                    ]);
                    $errors++;
                    continue;
                }

                // Check if provincial user already exists for this province+coordinator combination
                $provincialUser = User::where('role', 'provincial')
                    ->where('province_id', $province->id)
                    ->where('parent_id', $coordinator->id)
                    ->first();

                if ($provincialUser) {
                    Log::info('Provincial user already exists, skipping', [
                        'province_id' => $province->id,
                        'province_name' => $province->name,
                        'coordinator_id' => $coordinator->id,
                        'coordinator_name' => $coordinator->name,
                        'provincial_user_id' => $provincialUser->id,
                    ]);
                    $skipped++;
                    continue;
                }

                // Generate unique email
                $baseEmail = 'provincial_' . $province->id . '_' . $coordinator->id . '@system.local';
                $email = $baseEmail;
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = 'provincial_' . $province->id . '_' . $coordinator->id . '_' . $counter . '@system.local';
                    $counter++;
                }

                // Create provincial user
                $provincialUser = User::create([
                    'parent_id' => $coordinator->id,
                    'role' => 'provincial',
                    'province_id' => $province->id,
                    'name' => $coordinator->name . ' - ' . $province->name . ' (Provincial)',
                    'email' => $email,
                    'username' => 'provincial_' . $province->id . '_' . $coordinator->id,
                    'password' => Hash::make('temp_password_change_required'),
                    'status' => 'active',
                    'province' => $province->name, // Backward compatibility
                ]);

                // Assign provincial role
                if (class_exists(\Spatie\Permission\Models\Role::class)) {
                    try {
                        $provincialUser->assignRole('provincial');
                    } catch (\Exception $e) {
                        Log::warning('Could not assign provincial role', [
                            'user_id' => $provincialUser->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::info('Provincial user created', [
                    'province_id' => $province->id,
                    'province_name' => $province->name,
                    'coordinator_id' => $coordinator->id,
                    'coordinator_name' => $coordinator->name,
                    'provincial_user_id' => $provincialUser->id,
                    'provincial_user_email' => $provincialUser->email,
                ]);

                $migrated++;

            } catch (\Exception $e) {
                Log::error('Error migrating provincial coordinator', [
                    'province_id' => $province->id,
                    'province_name' => $province->name ?? 'Unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors++;
            }
        }

        Log::info('Provincial coordinator migration completed', [
            'migrated' => $migrated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * This rollback does NOT restore provincial_coordinator_id assignments
     * because that data is lost. It only removes the provincial users
     * that were created during migration.
     */
    public function down(): void
    {
        // Find all provincial users created by this migration (system emails)
        $provincialUsers = User::where('role', 'provincial')
            ->where('email', 'like', '%@system.local')
            ->get();

        $deleted = 0;
        foreach ($provincialUsers as $user) {
            try {
                $user->delete();
                $deleted++;
            } catch (\Exception $e) {
                Log::error('Error deleting provincial user during rollback', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Provincial coordinator migration rolled back', [
            'deleted_users' => $deleted,
        ]);
    }
};
