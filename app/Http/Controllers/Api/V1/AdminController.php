<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantOwnerApplication;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function overview(Request $request)
    {
        return response()->json([
            'message' => 'Admin access granted.',
            'user' => [
                'id' => $request->user()->id,
                'role' => $request->user()->role,
                'name' => $request->user()->name,
            ],
            'stats' => [
                'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
                'restaurant_owners' => User::query()->where('role', User::ROLE_RESTAURANT_OWNER)->count(),
                'restaurants' => Restaurant::query()->count(),
                'orders' => Order::query()->count(),
                'pending_applications' => RestaurantOwnerApplication::query()->where('status', 'pending')->count(),
            ],
        ]);
    }
}
