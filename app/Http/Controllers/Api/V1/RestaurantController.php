<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $restaurants = Restaurant::query()
            ->when($request->filled('is_open'), function ($query) use ($request) {
                $query->where('is_open', $request->boolean('is_open'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('search') . '%');
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $column = ltrim($request->input('sort'), '-');
                $direction = str_starts_with($request->input('sort'), '-') ? 'desc' : 'asc';

                if (in_array($column, ['name', 'created_at'])) {
                    $query->orderBy($column, $direction);
                }
            }, function ($query) {
                $query->latest();
            })
            ->with('meals')
            ->paginate(15);

        return RestaurantResource::collection($restaurants);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request)
    {
        Gate::authorize('create', Restaurant::class);

        $restaurant = $request->user()
            ->restaurants()
            ->create($request->validated());

        return (new RestaurantResource($restaurant))
            ->response()
            ->setStatusCode(201);
    }
    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant)
    {
        $restaurant->load('meals');

        return new RestaurantResource($restaurant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        Gate::authorize('update', $restaurant);

        $restaurant->update($request->validated());

        return (new RestaurantResource($restaurant));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant)
    {
        Gate::authorize('delete', $restaurant);

        $restaurant->delete();

        return response()->noContent();
    }
}
