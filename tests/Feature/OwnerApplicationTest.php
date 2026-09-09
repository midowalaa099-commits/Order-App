<?php

use App\Models\RestaurantOwnerApplication;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows a customer to submit a restaurant owner application', function () {
    $customer = User::factory()->customer()->create();

    Sanctum::actingAs($customer, ['*']);

    $response = $this->postJson('/api/v1/owner-applications', [
        'business_name' => 'Fresh Bites',
        'notes' => 'Looking to launch a delivery kitchen.',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('restaurant_owner_applications', [
        'user_id' => $customer->id,
        'status' => 'pending',
        'business_name' => 'Fresh Bites',
    ]);
});

it('prevents duplicate pending owner applications from the same customer', function () {
    $customer = User::factory()->customer()->create();

    RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($customer, ['*']);

    $response = $this->postJson('/api/v1/owner-applications', [
        'business_name' => 'Another Place',
        'notes' => 'Second attempt',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);
});

it('allows a customer to view their own owner application', function () {
    $customer = User::factory()->customer()->create();
    $application = RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
    ]);

    Sanctum::actingAs($customer, ['*']);

    $this->getJson("/api/v1/owner-applications/{$application->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $application->id)
        ->assertJsonPath('data.status', 'pending');
});

it('allows a user to view their latest owner application without knowing its id', function () {
    $customer = User::factory()->customer()->create();
    RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'rejected',
        'created_at' => now()->subDay(),
    ]);
    $latest = RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($customer, ['*']);

    $this->getJson('/api/v1/owner-applications/current')
        ->assertOk()
        ->assertJsonPath('data.id', $latest->id)
        ->assertJsonPath('data.status', 'pending');
});

it('prevents a customer from viewing another customer owner application', function () {
    $customer = User::factory()->customer()->create();
    $application = RestaurantOwnerApplication::factory()->create();

    Sanctum::actingAs($customer, ['*']);

    $this->getJson("/api/v1/owner-applications/{$application->id}")
        ->assertStatus(403);
});

it('enforces the pending application uniqueness constraint when submissions race', function () {
    $customer = User::factory()->customer()->create();

    Sanctum::actingAs($customer, ['*']);

    $this->postJson('/api/v1/owner-applications', [
        'business_name' => 'First Place',
    ])->assertStatus(201);

    $this->postJson('/api/v1/owner-applications', [
        'business_name' => 'Second Place',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    expect(RestaurantOwnerApplication::query()
        ->where('user_id', $customer->id)
        ->where('status', 'pending')
        ->count())->toBe(1);
});

it('allows an admin to view pending applications', function () {
    $admin = User::factory()->state(['role' => User::ROLE_ADMIN])->create();
    $customer = User::factory()->customer()->create();

    RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $this->getJson('/api/v1/admin/owner-applications')
        ->assertStatus(200)
        ->assertJsonPath('data.0.status', 'pending');
});

it('prevents a non-admin from viewing pending applications', function () {
    $customer = User::factory()->customer()->create();

    Sanctum::actingAs($customer, ['*']);

    $this->getJson('/api/v1/admin/owner-applications')
        ->assertStatus(403);
});

it('allows an admin to approve an owner application', function () {
    $admin = User::factory()->state(['role' => User::ROLE_ADMIN])->create();
    $customer = User::factory()->customer()->create();

    $application = RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson("/api/v1/admin/owner-applications/{$application->id}/approve");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'approved');

    $customer->refresh();
    expect($customer->role)->toBe(User::ROLE_RESTAURANT_OWNER);
});

it('allows an admin to reject an owner application', function () {
    $admin = User::factory()->state(['role' => User::ROLE_ADMIN])->create();
    $customer = User::factory()->customer()->create();

    $application = RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson("/api/v1/admin/owner-applications/{$application->id}/reject");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'rejected');

    $customer->refresh();
    expect($customer->role)->toBe(User::ROLE_CUSTOMER);
});

it('prevents an admin from approving an already approved application', function () {
    $admin = User::factory()->state(['role' => User::ROLE_ADMIN])->create();
    $customer = User::factory()->customer()->create();

    $application = RestaurantOwnerApplication::factory()->create([
        'user_id' => $customer->id,
        'status' => 'approved',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/v1/admin/owner-applications/{$application->id}/approve")
        ->assertStatus(422);
});
