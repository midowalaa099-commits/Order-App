<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $addresses = Address::query()
                ->with('user')
                ->latest()
                ->paginate(15);

            return AddressResource::collection($addresses);
        }

        return AddressResource::collection($request->user()->addresses()->latest()->paginate(15));
    }

    public function store(StoreAddressRequest $request)
    {
        Gate::authorize('create', Address::class);

        $address = $request->user()->addresses()->create($request->validated());

        return (new AddressResource($address))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Address $address)
    {
        Gate::authorize('view', $address);
        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address)
    {
        Gate::authorize('update', $address);
        $address->update($request->validated());
        return new AddressResource($address);
    }

    public function destroy(Address $address)
    {
        Gate::authorize('delete', $address);
        $address->delete();
        return response()->noContent();
    }
}