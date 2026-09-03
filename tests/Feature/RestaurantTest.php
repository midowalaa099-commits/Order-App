<?php

use App\Models\Restaurant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('prevents a customer from creating a restaurant', function () {
    $user = User::factory()->customer()->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/restaurants', [
        'name' => 'Pizza Palace',
        'address' => '123 Main St',
    ]);

    $response->assertStatus(403);
});

it('lets a restaurant owner create a restaurant', function () {
    $user = User::factory()->restaurantOwner()->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/restaurants', [
        'name' => 'Pizza Palace',
        'address' => '123 Main St',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Pizza Palace');

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Pizza Palace',
        'user_id' => $user->id,
    ]);
});

it('rejects restaurant creation without a name', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/restaurants', [
        'address' => '123 Main St',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('rejects restaurant creation for guests', function () {
    $response = $this->postJson('/api/v1/restaurants', [
        'name' => 'Pizza Palace',
        'address' => '123 Main St',
    ]);

    $response->assertStatus(401);
});

it("prevents a user from deleting another user's restaurant", function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $restaurant = Restaurant::factory()->for($owner)->create();

    Sanctum::actingAs($intruder, ['*']);

    $response = $this->deleteJson("/api/v1/restaurants/{$restaurant->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('restaurants', ['id' => $restaurant->id]);
});