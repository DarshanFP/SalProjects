<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reports\Monthly\DPReport>
 */
class DPReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\Reports\Monthly\DPReport::class;

    public function definition(): array
    {
        return [
            'report_id' => 'TEST-' . fake()->unique()->numerify('####'),
            'project_id' => 'TEST-PROJECT', // Will be overridden in tests
            'user_id' => 1, // Will be overridden in tests
            'status' => \App\Models\Reports\Monthly\DPReport::STATUS_DRAFT,
            'project_type' => \App\Constants\ProjectType::DEVELOPMENT_PROJECTS,
            'project_title' => fake()->sentence(4),
            'report_month_year' => now()->format('Y-m'),
        ];
    }
}
