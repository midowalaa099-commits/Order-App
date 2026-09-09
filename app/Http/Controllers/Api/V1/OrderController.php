<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $orders = Order::query()
                ->with('items.meal', 'restaurant', 'address')
                ->latest()
                ->paginate(15);

            return OrderResource::collection($orders);
        }

        if ($request->user()->isRestaurantOwner()) {
            $restaurantIds = $request->user()->restaurants()->pluck('id');

            $orders = Order::query()
                ->whereIn('restaurant_id', $restaurantIds)
                ->with('items.meal', 'restaurant', 'address')
                ->latest()
                ->paginate(15);

            return OrderResource::collection($orders);
        }

        $orders = $request->user()->orders()
            ->with('items.meal', 'restaurant', 'address')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load('items.meal', 'restaurant', 'address'));
    }

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        Gate::authorize('create', Order::class);

        $order = $orderService->createOrder(
            $request->user(),
            $request->validated()
        );

        return new OrderResource(
            $order->load('items.meal', 'restaurant', 'address')
        );
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,preparing,on_the_way,delivered,cancelled'],
        ]);

        Gate::authorize('update', [$order, $validated['status']]);

        $order->update($validated);

        return new OrderResource($order->fresh());
    }

    public function destroy(Order $order)
    {
        Gate::authorize('delete', $order);
        $order->delete();

        return response()->noContent();
    }
}
