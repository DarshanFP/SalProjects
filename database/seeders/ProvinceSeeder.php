<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Province;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds all provinces from hardcoded lists in controllers.
     */
    public function run(): void
    {
        $provinces = [
            ['name' => 'Bangalore', 'is_active' => true],
            ['name' => 'Vijayawada', 'is_active' => true],
            ['name' => 'Visakhapatnam', 'is_active' => true],
            ['name' => 'Generalate', 'is_active' => true],
            ['name' => 'Divyodaya', 'is_active' => true],
            ['name' => 'Indonesia', 'is_active' => true],
            ['name' => 'East Timor', 'is_active' => true],
            ['name' => 'East Africa', 'is_active' => true],
            ['name' => 'Luzern', 'is_active' => true],
        ];

        foreach ($provinces as $province) {
            Province::firstOrCreate(
                ['name' => $province['name']],
                $province
            );
        }

        $this->command->info('Provinces seeded successfully: ' . Province::count() . ' provinces');
    }
}
