<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OldProjects\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\OldProjects\Project::class;

    public function definition(): array
    {
        return [
            'project_type' => \App\Constants\ProjectType::DEVELOPMENT_PROJECTS,
            'project_title' => fake()->sentence(4),
            'user_id' => 1, // Will be overridden in tests
            'in_charge' => function (array $attributes) {
                return $attributes['user_id'];
            },
            'status' => 'approved_by_coordinator',
            'overall_project_budget' => '100000',
            'society_name' => fake()->company(),
            'executor_name' => fake()->name(),
            'goal' => fake()->sentence(6),
        ];
    }
}
