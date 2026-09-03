<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('defaults new users to the customer role', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test Customer',
        'email' => 'customer-role@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.role', User::ROLE_CUSTOMER);
});

it('rejects client role assignment during public registration', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test Customer',
        'email' => 'client-role@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'restaurant_owner',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

it('prevents a customer from accessing the admin route', function () {
    $customer = User::factory()->customer()->create();

    Sanctum::actingAs($customer, ['*']);

    $this->getJson('/api/v1/admin/overview')
        ->assertStatus(403);
});

it('allows an admin to access the admin route', function () {
    $admin = User::factory()->state(['role' => User::ROLE_ADMIN])->create();

    Sanctum::actingAs($admin, ['*']);

    $this->getJson('/api/v1/admin/overview')
        ->assertStatus(200)
        ->assertJsonPath('user.role', User::ROLE_ADMIN);
});
