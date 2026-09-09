<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantOwnerApplicationRequest;
use App\Http\Resources\RestaurantOwnerApplicationResource;
use App\Models\RestaurantOwnerApplication;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OwnerApplicationController extends Controller
{
    public function current(Request $request)
    {
        $application = $request->user()
            ->restaurantOwnerApplications()
            ->latest()
            ->first();

        if (! $application) {
            return response()->json(['data' => null]);
        }

        return new RestaurantOwnerApplicationResource($application);
    }

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

        try {
            $application = RestaurantOwnerApplication::create([
                'user_id' => $request->user()->id,
                'business_name' => $request->validated('business_name'),
                'notes' => $request->validated('notes'),
                'status' => 'pending',
            ]);
        } catch (QueryException $exception) {
            if (! $this->isPendingApplicationUniqueViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'user_id' => 'A pending owner application already exists for this user.',
            ]);
        }

        return (new RestaurantOwnerApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    private function isPendingApplicationUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && (
                str_contains($message, 'restaurant_owner_applications_one_pending_user_unique')
                || (
                    str_contains($message, 'restaurant_owner_applications')
                    && str_contains($message, 'user_id')
                    && (str_contains($message, 'unique') || str_contains($message, 'duplicate'))
                )
            );
    }

    public function show(Request $request, RestaurantOwnerApplication $application)
    {
        Gate::authorize('view', $application);

        return new RestaurantOwnerApplicationResource($application);
    }

    public function approve(Request $request, RestaurantOwnerApplication $application)
    {
        Gate::authorize('update', $application);

        DB::transaction(function () use ($application): void {
            $application = RestaurantOwnerApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($application->status !== 'pending') {
                abort(422, 'Only pending applications can be approved.');
            }

            $application->update(['status' => 'approved']);
            $application->user()->update(['role' => User::ROLE_RESTAURANT_OWNER]);
        });

        return new RestaurantOwnerApplicationResource($application->fresh()->load('user'));
    }

    public function reject(Request $request, RestaurantOwnerApplication $application)
    {
        Gate::authorize('update', $application);

        DB::transaction(function () use ($application): void {
            $application = RestaurantOwnerApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($application->status !== 'pending') {
                abort(422, 'Only pending applications can be rejected.');
            }

            $application->update(['status' => 'rejected']);
        });

        return new RestaurantOwnerApplicationResource($application->fresh()->load('user'));
    }
}
