<?php

namespace Database\Factories;

use App\Models\RestaurantOwnerApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantOwnerApplicationFactory extends Factory
{
    protected $model = RestaurantOwnerApplication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'notes' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
