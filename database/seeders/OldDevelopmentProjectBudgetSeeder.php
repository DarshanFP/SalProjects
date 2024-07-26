<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OldProjects\OldDevelopmentProjectBudget;

class OldDevelopmentProjectBudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        OldDevelopmentProjectBudget::factory()->count(40)->create(); // Adjust count as needed
    }
}
