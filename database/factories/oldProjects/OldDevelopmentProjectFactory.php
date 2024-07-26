<?php

namespace Database\Factories\OldProjects;

use App\Models\OldProjects\OldDevelopmentProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OldDevelopmentProjectFactory extends Factory
{
    protected $model = OldDevelopmentProject::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            // 'user_id' => $this->faker->numberBetween(1, 3),
            'project_title' => $this->faker->sentence,
            'place' => $this->faker->city,
            'society_name' => $this->faker->company,
            'commencement_month_year' => $this->faker->monthName . '/' . $this->faker->year,
            'in_charge' => $this->faker->name,
            'total_beneficiaries' => $this->faker->numberBetween(50, 500),
            'reporting_period' => $this->faker->word,
            'goal' => $this->faker->paragraph,
            'total_amount_sanctioned' => $this->faker->randomFloat(2, 1000, 100000),
        ];
    }
}
