<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\Society;
use App\Models\User;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\DB;

class SocietySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates societies based on actual data:
     * - Vijayawada: SARVAJANA SNEHA CHARITABLE TRUST, ST. ANN'S EDUCATIONAL SOCIETY
     * - Visakhapatnam: ST. ANN'S SOCIETY, VISAKHAPATNAM, WILHELM MEYERS DEVELOPMENTAL SOCIETY
     */
    public function run(): void
    {
        $this->command->info('Starting Society Seeding...');

        $totalCreated = 0;
        $totalSkipped = 0;

        // Get all provinces
        $provinces = Province::active()->get();

        if ($provinces->isEmpty()) {
            $this->command->warn('No active provinces found. Please create provinces first.');
            return;
        }

        // Province-specific society mapping based on actual data from CenterSeeder and projects
        $provinceSocieties = [
            'Vijayawada' => [
                'SARVAJANA SNEHA CHARITABLE TRUST',
                "ST. ANN'S EDUCATIONAL SOCIETY"
            ],
            'Visakhapatnam' => [
                "ST. ANN'S SOCIETY, VISAKHAPATNAM",
                'WILHELM MEYERS DEVELOPMENTAL SOCIETY'
            ],
        ];

        // For each province
        foreach ($provinces as $province) {
            $this->command->info("Processing province: {$province->name}");

            // If province has predefined societies, use those
            if (isset($provinceSocieties[$province->name])) {
                foreach ($provinceSocieties[$province->name] as $societyName) {
                    $society = Society::firstOrCreate(
                        [
                            'province_id' => $province->id,
                            'name' => $societyName
                        ],
                        [
                            'is_active' => true
                        ]
                    );

                    if ($society->wasRecentlyCreated) {
                        $totalCreated++;
                        $this->command->info("  ✓ Created society: {$societyName}");
                    } else {
                        $totalSkipped++;
                        $this->command->line("  - Society already exists: {$societyName}");
                    }
                }
            } else {
                // For other provinces, extract from users and projects
                $societiesFromUsers = User::where(function($query) use ($province) {
                        $query->where('province_id', $province->id)
                              ->orWhere('province', $province->name);
                    })
                    ->whereNotNull('society_name')
                    ->where('society_name', '!=', '')
                    ->distinct()
                    ->pluck('society_name')
                    ->filter()
                    ->map(function($name) {
                        return trim($name);
                    })
                    ->unique()
                    ->values();

                $societiesFromProjects = Project::whereHas('user', function($query) use ($province) {
                        $query->where(function($q) use ($province) {
                            $q->where('province_id', $province->id)
                              ->orWhere('province', $province->name);
                        });
                    })
                    ->whereNotNull('society_name')
                    ->where('society_name', '!=', '')
                    ->distinct()
                    ->pluck('society_name')
                    ->filter()
                    ->map(function($name) {
                        return trim($name);
                    })
                    ->unique()
                    ->values();

                $allSocietiesInProvince = $societiesFromUsers->merge($societiesFromProjects)
                    ->unique()
                    ->values();

                if ($allSocietiesInProvince->isEmpty()) {
                    $this->command->warn("  No societies found for province: {$province->name}");
                    continue;
                }

                // Create societies from actual data
                foreach ($allSocietiesInProvince as $societyName) {
                    if (empty(trim($societyName))) {
                        continue;
                    }

                    $society = Society::firstOrCreate(
                        [
                            'province_id' => $province->id,
                            'name' => trim($societyName)
                        ],
                        [
                            'is_active' => true
                        ]
                    );

                    if ($society->wasRecentlyCreated) {
                        $totalCreated++;
                        $this->command->info("  ✓ Created society: {$societyName}");
                    } else {
                        $totalSkipped++;
                        $this->command->line("  - Society already exists: {$societyName}");
                    }
                }
            }
        }

        $this->command->info('');
        $this->command->info("Society Seeding Complete!");
        $this->command->info("  Created: {$totalCreated} societies");
        $this->command->info("  Skipped (already exist): {$totalSkipped} societies");
        $this->command->info("  Total societies in database: " . Society::count());

        // Show summary by province
        $this->command->info('');
        $this->command->info("Societies by Province:");
        foreach ($provinces as $province) {
            $count = Society::where('province_id', $province->id)->count();
            if ($count > 0) {
                $societies = Society::where('province_id', $province->id)->orderBy('name')->pluck('name')->toArray();
                $this->command->info("  {$province->name}: {$count} societies");
                foreach ($societies as $society) {
                    $this->command->line("    - {$society}");
                }
            }
        }
    }
}
