<?php

use App\Models\Address;
use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('prevents a restaurant owner from creating an order', function () {
    $restaurantOwner = User::factory()->restaurantOwner()->create();
    $restaurant = Restaurant::factory()->create(['user_id' => $restaurantOwner->id]);
    $meal = Meal::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 150,
    ]);
    $address = Address::factory()->create(['user_id' => $restaurantOwner->id]);

    Sanctum::actingAs($restaurantOwner, ['*']);

    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => $restaurant->id,
        'address_id' => $address->id,
        'items' => [[
            'meal_id' => $meal->id,
            'quantity' => 2,
        ]],
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseCount('orders', 0);
});

it('creates an order', function () {
    $user = User::factory()->customer()->create();

    $restaurant = Restaurant::factory()->create();

    $meal = Meal::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 150,
    ]);

    $address = Address::factory()->create([
        'user_id' => $user->id,
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => $restaurant->id,
        'address_id' => $address->id,
        'items' => [
            [
                'meal_id' => $meal->id,
                'quantity' => 2,
            ],
        ],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'restaurant_id' => $restaurant->id,
        'address_id' => $address->id,
        'status' => 'pending',
        'total_price' => 300,
    ]);
});

it('fails validation when order items are missing', function () {
    $user = User::factory()->customer()->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => 1,
        'address_id' => 1,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('prevents a restaurant owner from creating an address', function () {
    $restaurantOwner = User::factory()->restaurantOwner()->create();

    Sanctum::actingAs($restaurantOwner, ['*']);

    $response = $this->postJson('/api/v1/addresses', [
        'label' => 'Home',
        'address_line' => 'Main Street',
        'city' => 'Cairo',
        'phone' => '+966500000000',
    ]);

    $response->assertStatus(403);
    $this->assertDatabaseCount('addresses', 0);
});

it('requires authentication to create an order', function () {
    $response = $this->postJson('/api/v1/orders', [
        'restaurant_id' => 1,
        'address_id' => 1,
        'items' => [
            [
                'meal_id' => 1,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(401);
});

it('prevents a customer from performing a restaurant owner status change', function () {
    $customer = User::factory()->customer()->create();
    $restaurantOwner = User::factory()->restaurantOwner()->create();
    $restaurant = Restaurant::factory()->create(['user_id' => $restaurantOwner->id]);
    $address = Address::factory()->create(['user_id' => $customer->id]);
    $meal = Meal::factory()->create(['restaurant_id' => $restaurant->id]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'address_id' => $address->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($customer, ['*']);

    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'status' => 'delivered',
    ]);

    $response->assertStatus(403);
    expect($order->fresh()->status)->not->toBe('delivered');
});

it('prevents a user from updating another users order', function () {
    $owner = User::factory()->customer()->create();
    $otherUser = User::factory()->customer()->create();

    $order = Order::factory()->create([
        'user_id' => $owner->id,
    ]);

    Sanctum::actingAs($otherUser, ['*']);

    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'status' => 'cancelled',
    ]);

    $response->assertStatus(403);

    expect($order->fresh()->status)->not->toBe('cancelled');
});

it('rejects invalid order status values', function () {
    $customer = User::factory()->customer()->create();
    $restaurantOwner = User::factory()->restaurantOwner()->create();
    $restaurant = Restaurant::factory()->create(['user_id' => $restaurantOwner->id]);
    $address = Address::factory()->create(['user_id' => $customer->id]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'address_id' => $address->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($restaurantOwner, ['*']);

    $response = $this->putJson("/api/v1/orders/{$order->id}", [
        'status' => 'archived',
    ]);

    $response->assertStatus(422);
    expect($order->fresh()->status)->toBe('pending');
});

it('allows a restaurant owner to view orders for their own restaurant', function () {
    $owner = User::factory()->restaurantOwner()->create();
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create(['user_id' => $owner->id]);
    $address = Address::factory()->create(['user_id' => $customer->id]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'address_id' => $address->id,
    ]);

    Sanctum::actingAs($owner, ['*']);

    $response = $this->getJson("/api/v1/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $order->id);
});

it('prevents a restaurant owner from viewing another restaurant orders', function () {
    $owner = User::factory()->restaurantOwner()->create();
    $otherOwner = User::factory()->restaurantOwner()->create();
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create(['user_id' => $owner->id]);
    $address = Address::factory()->create(['user_id' => $customer->id]);
    $otherRestaurant = Restaurant::factory()->create(['user_id' => $otherOwner->id]);

    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'restaurant_id' => $otherRestaurant->id,
        'address_id' => $address->id,
    ]);

    Sanctum::actingAs($owner, ['*']);

    $response = $this->getJson("/api/v1/orders/{$order->id}");

    $response->assertStatus(403);
});