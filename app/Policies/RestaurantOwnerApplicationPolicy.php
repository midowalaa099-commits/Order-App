<?php

namespace App\Policies;

use App\Models\RestaurantOwnerApplication;
use App\Models\User;

class RestaurantOwnerApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, RestaurantOwnerApplication $application): bool
    {
        return $user->isAdmin() || $user->id === $application->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, RestaurantOwnerApplication $application): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, RestaurantOwnerApplication $application): bool
    {
        return $user->isAdmin();
    }
}
