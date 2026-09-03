<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private SmsNotifier $smsNotifier,
    ) {}

    public function createOrder(User $user, array $data): Order
    {
        if (! $user->isCustomer()) {
            throw new AuthorizationException('Only customers can create orders.');
        }

        $order = DB::transaction(function () use ($user, $data) {
            $restaurant = Restaurant::query()
                ->whereKey($data['restaurant_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $restaurant->is_open) {
                throw ValidationException::withMessages([
                    'restaurant_id' => ['This restaurant is currently closed.'],
                ]);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'address_id' => $data['address_id'],
                'status' => 'pending',
                'total_price' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $meal = Meal::query()
                    ->whereKey($item['meal_id'])
                    ->where('restaurant_id', $restaurant->id)
                    ->lockForUpdate()
                    ->first();

                if (! $meal) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more meals do not belong to this restaurant.'],
                    ]);
                }

                if (! $meal->is_available) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more meals are not available right now.'],
                    ]);
                }

                $order->items()->create([
                    'meal_id' => $meal->id,
                    'quantity' => $item['quantity'],
                    'price' => $meal->price,
                ]);

                $total += $meal->price * $item['quantity'];
            }

            $order->update([
                'total_price' => $total,
            ]);

            return $order;
        });

        $this->smsNotifier->send(
            $order->address->phone ?? '',
            "Your order #{$order->id} has been placed!"
        );

        return $order;
    }
}