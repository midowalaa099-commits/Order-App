<?php

use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a meal for a restaurant owned by the restaurant owner', function () {
    $user = User::factory()->restaurantOwner()->create();

    $restaurant = Restaurant::factory()->create([
        'user_id' => $user->id,
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson(
        "/api/v1/restaurants/{$restaurant->id}/meals",
        [
            'name' => 'Pizza Margherita',
            'description' => 'Fresh pizza',
            'price' => 150,
            'is_available' => true,
        ]
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Pizza Margherita');

    $this->assertDatabaseHas('meals', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Pizza Margherita',
        'price' => 150,
    ]);
});

it('fails validation when meal name is missing', function () {
    $user = User::factory()->create();

    $restaurant = Restaurant::factory()->create([
        'user_id' => $user->id,
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson(
        "/api/v1/restaurants/{$restaurant->id}/meals",
        [
            'price' => 150,
        ]
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('requires authentication to create a meal', function () {
    $restaurant = Restaurant::factory()->create();

    $response = $this->postJson(
        "/api/v1/restaurants/{$restaurant->id}/meals",
        [
            'name' => 'Pizza',
            'price' => 150,
        ]
    );

    $response->assertStatus(401);
});

it('prevents a customer from creating a meal in another users restaurant', function () {
    $owner = User::factory()->restaurantOwner()->create();
    $customer = User::factory()->customer()->create();

    $restaurant = Restaurant::factory()->create([
        'user_id' => $owner->id,
    ]);

    Sanctum::actingAs($customer, ['*']);

    $response = $this->postJson(
        "/api/v1/restaurants/{$restaurant->id}/meals",
        [
            'name' => 'Pizza',
            'price' => 150,
        ]
    );

    $response->assertStatus(403);

    $this->assertDatabaseMissing('meals', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Pizza',
    ]);
});

it('prevents a restaurant owner from managing another owners meal', function () {
    $owner = User::factory()->restaurantOwner()->create();
    $otherOwner = User::factory()->restaurantOwner()->create();

    $restaurant = Restaurant::factory()->create([
        'user_id' => $owner->id,
    ]);

    $meal = Meal::factory()->create([
        'restaurant_id' => $restaurant->id,
    ]);

    Sanctum::actingAs($otherOwner, ['*']);

    $response = $this->putJson("/api/v1/meals/{$meal->id}", [
        'name' => 'Changed meal',
    ]);

    $response->assertStatus(403);
});