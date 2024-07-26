<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'parent_id' => null,
            'name' => $this->faker->name,
            'username' => $this->faker->unique()->userName,
            'role' => 'executor', // Default role set to executor
            'province' => $this->faker->randomElement(['Bangalore', 'Vijayawada', 'Visakhapatnam', 'Generalate', 'none']),
            'society_name' => $this->faker->randomElement([
                "ST. ANN'S EDUCATIONAL SOCIETY",
                "SARVAJANA SNEHA CHARITABLE TRUST",
                "ST. ANNS'S SOCIETY, VISAKHAPATNAM",
                "WILHELM MEYERS DEVELOPMENTAL SOCIETY",
                "ST.ANN'S SOCIETY, SOUTHERN REGION"
            ]),
            'center' => $this->faker->city,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // password
            'address' => $this->faker->address,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'remember_token' => Str::random(10),
        ];
    }
}
