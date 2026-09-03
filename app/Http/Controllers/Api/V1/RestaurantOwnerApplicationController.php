<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantOwnerApplicationRequest;
use App\Http\Resources\RestaurantOwnerApplicationResource;
use App\Models\RestaurantOwnerApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RestaurantOwnerApplicationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', RestaurantOwnerApplication::class);

        $applications = RestaurantOwnerApplication::query()
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(15);

        return RestaurantOwnerApplicationResource::collection($applications);
    }

    public function store(StoreRestaurantOwnerApplicationRequest $request)
    {
        Gate::authorize('create', RestaurantOwnerApplication::class);

        $existing = RestaurantOwnerApplication::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            abort(422, 'A pending application already exists for this user.');
        }

        $application = RestaurantOwnerApplication::create([
            'user_id' => $request->user()->id,
            'business_name' => $request->validated('business_name'),
            'notes' => $request->validated('notes'),
            'status' => 'pending',
        ]);

        return (new RestaurantOwnerApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function approve(Request $request, RestaurantOwnerApplication $application)
    {
        Gate::authorize('update', $application);

        if ($application->status !== 'pending') {
            abort(422, 'Only pending applications can be approved.');
        }

        $application->update(['status' => 'approved']);

        $application->user()->update(['role' => User::ROLE_RESTAURANT_OWNER]);

        return new RestaurantOwnerApplicationResource($application->fresh());
    }

    public function reject(Request $request, RestaurantOwnerApplication $application)
    {
        Gate::authorize('update', $application);

        if ($application->status !== 'pending') {
            abort(422, 'Only pending applications can be rejected.');
        }

        $application->update(['status' => 'rejected']);

        return new RestaurantOwnerApplicationResource($application->fresh());
    }
}
