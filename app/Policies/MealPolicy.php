<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\Restaurant;
use App\Models\User;

class MealPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Meal $meal): bool
    {
        return true;
    }

    public function create(User $user, Restaurant $restaurant): bool
    {
        return $user->isRestaurantOwner() && $user->id === $restaurant->user_id;
    }

    public function update(User $user, Meal $meal): bool
    {
        return $user->isRestaurantOwner() && $user->id === $meal->restaurant->user_id;
    }

    public function delete(User $user, Meal $meal): bool
    {
        return $user->isRestaurantOwner() && $user->id === $meal->restaurant->user_id;
    }

    public function restore(User $user, Meal $meal): bool
    {
        return false;
    }

    public function forceDelete(User $user, Meal $meal): bool
    {
        return false;
    }
}
