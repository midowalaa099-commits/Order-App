<?php

namespace App\Http\Requests;

use App\Models\RestaurantOwnerApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantOwnerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isCustomer();
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if (! $user) {
                return;
            }

            if (RestaurantOwnerApplication::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists()) {
                $validator->errors()->add('user_id', 'A pending owner application already exists for this user.');
            }
        });
    }
}
