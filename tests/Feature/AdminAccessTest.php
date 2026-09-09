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

it('registers a restaurant partner as a customer with a pending application', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Restaurant Partner',
        'email' => 'partner@example.com',
        'password' => 'strong-password-123',
        'password_confirmation' => 'strong-password-123',
        'account_type' => 'restaurant_owner',
        'business_name' => 'Partner Kitchen',
    ]);

    $response->assertCreated()->assertJsonPath('data.role', User::ROLE_CUSTOMER);
    $this->assertDatabaseHas('restaurant_owner_applications', [
        'business_name' => 'Partner Kitchen',
        'status' => 'pending',
    ]);
});

it('requires a business name for restaurant partner registration', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Restaurant Partner',
        'email' => 'missing-business@example.com',
        'password' => 'strong-password-123',
        'password_confirmation' => 'strong-password-123',
        'account_type' => 'restaurant_owner',
    ])->assertUnprocessable()->assertJsonValidationErrors(['business_name']);
});

it('does not allow mass assignment of an administrative role', function () {
    $user = User::create([
        'name' => 'Mass Assignment Test',
        'email' => 'mass-assignment@example.com',
        'password' => 'secret123',
        'role' => User::ROLE_ADMIN,
    ]);

    expect($user->fresh()->role)->not->toBe(User::ROLE_ADMIN);
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

it('requires authentication for the admin route', function () {
    $this->getJson('/api/v1/admin/overview')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'You must be logged in to do that.');
});

it('allows an admin to access the admin route', function () {
    $admin = User::factory()->state(['role' => User::ROLE_ADMIN])->create();

    Sanctum::actingAs($admin, ['*']);

    $this->getJson('/api/v1/admin/overview')
        ->assertStatus(200)
        ->assertJsonPath('user.role', User::ROLE_ADMIN);
});
