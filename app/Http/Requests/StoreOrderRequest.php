<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'exists:restaurants,id'],

            'address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()?->id),
            ],

            'items' => ['required', 'array', 'min:1'],
            'items.*.meal_id' => [
                'required',
                Rule::exists('meals', 'id')->where('restaurant_id', $this->input('restaurant_id')),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
