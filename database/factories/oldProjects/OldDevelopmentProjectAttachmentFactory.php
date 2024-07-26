<?php

namespace Database\Factories\OldProjects;

use App\Models\OldProjects\OldDevelopmentProjectAttachment;
use App\Models\OldProjects\OldDevelopmentProject;
use Illuminate\Database\Eloquent\Factories\Factory;

class OldDevelopmentProjectAttachmentFactory extends Factory
{
    protected $model = OldDevelopmentProjectAttachment::class;

    public function definition()
    {
        return [
            'project_id' => OldDevelopmentProject::factory(),
            // 'project_id' => $this->faker->numberBetween(1, 4),
            'file_path' => $this->faker->filePath(),
            'file_name' => $this->faker->word . '.pdf',
            'description' => $this->faker->paragraph,
        ];
    }
}
