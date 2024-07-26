<?php

namespace Database\Factories\DevelopmentProjectFactories;

use App\Models\Reports\Quarterly\QRDLAnnexure;
use Illuminate\Database\Eloquent\Factories\Factory;

class QRDLAnnexureFactory extends Factory
{
    protected $model = QRDLAnnexure::class;

    public function definition()
    {
        return [
            'report_id' => function () {
                return \App\Models\Reports\Quarterly\RQDPReport::factory()->create()->id;
            },
            'beneficiary_name' => $this->faker->name,
            'support_date' => $this->faker->date(),
            'self_employment' => $this->faker->sentence,
            'amount_sanctioned' => $this->faker->randomFloat(2, 1000, 10000),
            'monthly_profit' => $this->faker->randomFloat(2, 1000, 10000),
            'annual_profit' => $this->faker->randomFloat(2, 1000, 10000),
            'impact' => $this->faker->paragraph,
            'challenges' => $this->faker->paragraph,
        ];
    }
}
