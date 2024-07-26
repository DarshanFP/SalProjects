<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OldProjects\OldDevelopmentProjectAttachment;

class OldDevelopmentProjectAttachmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        OldDevelopmentProjectAttachment::factory()->count(20)->create(); // Adjust count as needed
    }
}
