<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $catalogOwner = User::query()->firstOrCreate([
            'email' => 'catalog@order-app.invalid',
        ], [
            'name' => 'Order App Catalog',
            'password' => Str::password(40),
            'role' => UserRole::RESTAURANT_OWNER->value,
        ]);

        $restaurant = $catalogOwner->restaurants()->updateOrCreate([
            'name' => 'Cairo Kitchen',
        ], [
            'address' => '12 Tahrir Street, Cairo',
            'is_open' => true,
        ]);

        foreach ([
            ['name' => 'Koshari Bowl', 'description' => 'Rice, lentils, pasta, chickpeas, and spicy tomato sauce.', 'price' => 85],
            ['name' => 'Chicken Shawarma', 'description' => 'Marinated chicken, garlic sauce, pickles, and fresh bread.', 'price' => 120],
            ['name' => 'Om Ali', 'description' => 'Warm Egyptian pastry pudding with nuts and cream.', 'price' => 70],
        ] as $meal) {
            $restaurant->meals()->updateOrCreate(
                ['name' => $meal['name']],
                [...$meal, 'is_available' => true],
            );
        }
    }
}
