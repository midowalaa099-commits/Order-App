<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not allow role to be mass assigned on the User model', function () {
    $user = \App\Models\User::create([
        'name' => 'Mass Assignment Test',
        'email' => 'mass-assign-test@example.com',
        'password' => bcrypt('secret123'),
        'role' => \App\Models\User::ROLE_ADMIN,
    ]);

    expect($user->fresh()->role)->not->toBe(\App\Models\User::ROLE_ADMIN);
});
