<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMealRequest;
use App\Http\Requests\UpdateMealRequest;
use App\Http\Resources\MealResource;
use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class MealController extends Controller
{
    public function index(Request $request, Restaurant $restaurant)
    {
        $meals = $restaurant->meals()
            ->when($request->filled('is_available'), function ($query) use ($request) {
                $query->where('is_available', $request->boolean('is_available'));
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $column = ltrim($request->input('sort'), '-');
                $direction = str_starts_with($request->input('sort'), '-') ? 'desc' : 'asc';

                if (in_array($column, ['price', 'name', 'created_at'])) {
                    $query->orderBy($column, $direction);
                }
            }, function ($query) {
                $query->latest();
            })
            ->paginate(15);

        return MealResource::collection($meals);
    }
    public function show(Meal $meal)
    {
        return new MealResource($meal);
    }
    public function store(StoreMealRequest $request, Restaurant $restaurant)
    {
        Gate::authorize('create', [Meal::class, $restaurant]);

        $meal = $restaurant->meals()->create(
            $request->validated()
        );

        return (new MealResource($meal))
            ->response()
            ->setStatusCode(201);
    }
    public function update(UpdateMealRequest $request, Meal $meal)
    {
        Gate::authorize('update', $meal);

        $meal->update($request->validated());

        return (new MealResource($meal));
    }
    public function destroy(Meal $meal)
    {
        Gate::authorize('delete', $meal);

        $meal->delete();

        return response()->noContent();
    }
}
