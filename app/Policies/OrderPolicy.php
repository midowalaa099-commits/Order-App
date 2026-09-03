<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->id === $order->user_id) {
            return $user->isCustomer();
        }

        if ($user->id === $order->restaurant?->user_id) {
            return $user->isRestaurantOwner();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, Order $order, ?string $newStatus = null): bool
    {
        $currentStatus = $order->status;
        $newStatus = $newStatus ?? $currentStatus;

        if ($user->id === $order->user_id) {
            return $user->isCustomer() && $this->canCustomerChangeStatus($order, $newStatus);
        }

        if ($user->id === $order->restaurant?->user_id) {
            return $user->isRestaurantOwner() && $this->canRestaurantOwnerChangeStatus($order, $newStatus);
        }

        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isCustomer()
            && $user->id === $order->user_id
            && in_array($order->status, ['pending', 'cancelled'], true);
    }

    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }

    protected function canCustomerChangeStatus(Order $order, string $newStatus): bool
    {
        if ($newStatus !== OrderStatus::CANCELLED->value) {
            return false;
        }

        return $order->status === OrderStatus::PENDING->value;
    }

    protected function canRestaurantOwnerChangeStatus(Order $order, string $newStatus): bool
    {
        $currentStatus = $order->status;
        $allowed = [
            OrderStatus::PENDING->value => [OrderStatus::PREPARING->value],
            OrderStatus::PREPARING->value => [OrderStatus::ON_THE_WAY->value],
            OrderStatus::ON_THE_WAY->value => [OrderStatus::DELIVERED->value],
        ];

        return isset($allowed[$currentStatus]) && in_array($newStatus, $allowed[$currentStatus], true);
    }
}
