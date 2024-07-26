<?php

namespace Database\Factories\DevelopmentProjectFactories;

use App\Models\Reports\Quarterly\RQDPActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

class RQDPActivityFactory extends Factory
{
    protected $model = RQDPActivity::class;

    public function definition()
    {
        return [
            'objective_id' => function () {
                return \App\Models\Reports\Quarterly\RQDPObjective::factory()->create()->id;
            },
            'month' => $this->faker->monthName,
            'summary_activities' => $this->faker->paragraph,
            'qualitative_quantitative_data' => $this->faker->paragraph,
            'intermediate_outcomes' => $this->faker->paragraph,
        ];
    }
}

