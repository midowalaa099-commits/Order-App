<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'account_type' => ['sometimes', Rule::in(['customer', 'restaurant_owner'])],
            'business_name' => [
                Rule::requiredIf($this->input('account_type') === 'restaurant_owner'),
                'nullable',
                'string',
                'max:255',
            ],

            'role' => ['prohibited'],
        ];
    }
}
