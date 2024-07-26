<?php

namespace Database\Factories\OldProjects;

use App\Models\OldProjects\OldDevelopmentProjectBudget;
use App\Models\OldProjects\OldDevelopmentProject;
use Illuminate\Database\Eloquent\Factories\Factory;

class OldDevelopmentProjectBudgetFactory extends Factory
{
    protected $model = OldDevelopmentProjectBudget::class;

    public function definition()
    {
        return [
            'project_id' => OldDevelopmentProject::factory(),
            // 'project_id' => $this->faker->numberBetween(1, 3),,
            'phase' => $this->faker->numberBetween(1, 4),
            'description' => $this->faker->sentence,
            'rate_quantity' => $this->faker->randomFloat(2, 1, 100),
            'rate_multiplier' => $this->faker->randomFloat(2, 1, 5),
            'rate_duration' => $this->faker->randomFloat(2, 1, 12),
            'rate_increase' => $this->faker->randomFloat(2, 0, 10),
            'this_phase' => $this->faker->randomFloat(2, 1000, 10000),
            'next_phase' => $this->faker->randomFloat(2, 1000, 10000),
        ];
    }
}
