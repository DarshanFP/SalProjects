<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OldProjects\OldDevelopmentProject;

class OldDevelopmentProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        OldDevelopmentProject::factory()->count(10)->create(); // Adjust count as needed
    }
}
