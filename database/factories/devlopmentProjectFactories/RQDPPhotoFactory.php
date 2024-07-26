<?php

namespace Database\Factories\DevelopmentProjectFactories;

use App\Models\Reports\Quarterly\RQDPPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

class RQDPPhotoFactory extends Factory
{
    protected $model = RQDPPhoto::class;

    public function definition()
    {
        return [
            'report_id' => function () {
                return \App\Models\Reports\Quarterly\RQDPReport::factory()->create()->id;
            },
            'photo_path' => $this->faker->imageUrl(),
            'description' => $this->faker->sentence,
        ];
    }
}

