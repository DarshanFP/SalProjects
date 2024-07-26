<?php

namespace Database\Factories\DevelopmentProjectFactories;

use App\Models\Reports\Quarterly\RQDPReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class RQDPReportFactory extends Factory
{
    protected $model = RQDPReport::class;

    public function definition()
    {
        return [
            'project_title' => $this->faker->sentence,
            'place' => $this->faker->city,
            'society_name' => $this->faker->randomElement([
                "ST. ANN'S EDUCATIONAL SOCIETY",
                "SARVAJANA SNEHA CHARITABLE TRUST",
                "ST. ANNS'S SOCIETY, VISAKHAPATNAM",
                "WILHELM MEYERS DEVELOPMENTAL SOCIETY",
                "ST.ANN'S SOCIETY, SOUTHERN REGION"
            ]),
            'commencement_month_year' => $this->faker->date('Y-m'),
            'in_charge' => $this->faker->name,
            'total_beneficiaries' => $this->faker->numberBetween(50, 500),
            'reporting_period' => $this->faker->word,
            'goal' => $this->faker->paragraph,
            'account_period_start' => $this->faker->date(),
            'account_period_end' => $this->faker->date(),
            'total_balance_forwarded' => $this->faker->randomFloat(2, 1000, 10000),
            'amount_sanctioned_overview' => $this->faker->randomFloat(2, 1000, 10000),
            'amount_forwarded_overview' => $this->faker->randomFloat(2, 1000, 10000),
            'amount_in_hand' => $this->faker->randomFloat(2, 1000, 10000),
            'user_id' => function () {
                return \App\Models\User::factory()->create()->id;
            },
        ];
    }
}
