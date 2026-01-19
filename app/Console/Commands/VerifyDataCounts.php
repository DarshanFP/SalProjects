<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Province;
use App\Models\Center;
use App\Models\User;

class VerifyDataCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:verify-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify counts of Provinces, Centers, and User data migration status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Data Verification Report');
        $this->info('===========================');
        $this->newLine();

        // Provinces
        $provinceCount = Province::count();
        $this->line("Provinces: {$provinceCount}");

        // Centers
        $centerCount = Center::count();
        $this->line("Centers: {$centerCount}");

        $this->newLine();

        // User Data Migration Status
        $this->info('👥 User Data Migration Status');
        $this->info('-----------------------------');

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

        $this->line("Total users: {$totalUsers}");
        $this->line("Users with province_id: {$usersWithProvinceId}");
        $this->line("Users with center_id: {$usersWithCenterId}");
        $this->line("Users needing province migration: {$usersNeedingProvinceMigration}");
        $this->line("Users needing center migration: {$usersNeedingCenterMigration}");

        $this->newLine();

        // Summary
        if ($provinceCount >= 9 && $centerCount >= 78) {
            $this->info('✅ Base data (Provinces & Centers) looks good!');
        } else {
            $this->warn('⚠️  Base data may need seeding:');
            if ($provinceCount < 9) {
                $this->warn("   - Provinces: Expected 9+, found {$provinceCount}");
            }
            if ($centerCount < 78) {
                $this->warn("   - Centers: Expected 78+, found {$centerCount}");
            }
        }

        if ($usersNeedingProvinceMigration > 0 || $usersNeedingCenterMigration > 0) {
            $this->warn('⚠️  User data migration pending:');
            if ($usersNeedingProvinceMigration > 0) {
                $this->warn("   - {$usersNeedingProvinceMigration} users need province migration");
            }
            if ($usersNeedingCenterMigration > 0) {
                $this->warn("   - {$usersNeedingCenterMigration} users need center migration");
            }
        } else {
            $this->info('✅ All user data migrations appear complete!');
        }

        return 0;
    }
}
