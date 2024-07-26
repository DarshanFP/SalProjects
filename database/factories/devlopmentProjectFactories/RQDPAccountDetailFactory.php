<?php

namespace Database\Factories\DevelopmentProjectFactories;

use App\Models\Reports\Quarterly\RQDPAccountDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class RQDPAccountDetailFactory extends Factory
{
    protected $model = RQDPAccountDetail::class;

    public function definition()
    {
        return [
            'report_id' => function () {
                return \App\Models\Reports\Quarterly\RQDPReport::factory()->create()->id;
            },
            'particulars' => $this->faker->sentence,
            'amount_forwarded' => $this->faker->randomFloat(2, 1000, 10000),
            'amount_sanctioned' => $this->faker->randomFloat(2, 1000, 10000),
            'total_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'expenses_last_month' => $this->faker->randomFloat(2, 1000, 10000),
            'expenses_this_month' => $this->faker->randomFloat(2, 1000, 10000),
            'total_expenses' => $this->faker->randomFloat(2, 1000, 10000),
            'balance_amount' => $this->faker->randomFloat(2, 1000, 10000),
        ];
    }
}

