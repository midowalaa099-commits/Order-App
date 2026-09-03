<?php

use App\Models\Address;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates an address', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/addresses', [
        'label' => 'Home',
        'address_line' => '123 Naser Street',
        'city' => 'Kafr El Sheikh',
        'phone' => '01000000000',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.address_line', '123 Naser Street');

    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'address_line' => '123 Naser Street',
        'city' => 'Kafr El Sheikh',
    ]);
});

it('fails validation when address line is missing', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/addresses', [
        'city' => 'Kafr El Sheikh',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['address_line']);
});

it('requires authentication to create an address', function () {
    $response = $this->postJson('/api/v1/addresses', [
        'address_line' => '123 Naser Street',
        'city' => 'Kafr El Sheikh',
    ]);

    $response->assertStatus(401);
});

it('prevents a user from viewing another users address', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $address = Address::factory()->create([
        'user_id' => $owner->id,
    ]);

    Sanctum::actingAs($otherUser, ['*']);

    $response = $this->getJson("/api/v1/addresses/{$address->id}");

    $response->assertStatus(403);
});