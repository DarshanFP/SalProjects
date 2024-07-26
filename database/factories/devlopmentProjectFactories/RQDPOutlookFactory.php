<?php

namespace Database\Factories\DevelopmentProjectFactories;

use App\Models\Reports\Quarterly\RQDPOutlook;
use Illuminate\Database\Eloquent\Factories\Factory;

class RQDPOutlookFactory extends Factory
{
    protected $model = RQDPOutlook::class;

    public function definition()
    {
        return [
            'report_id' => function () {
                return \App\Models\Reports\Quarterly\RQDPReport::factory()->create()->id;
            },
            'date' => $this->faker->date(),
            'plan_next_month' => $this->faker->paragraph,
        ];
    }
}
