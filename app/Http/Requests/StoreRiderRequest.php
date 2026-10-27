<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rider_name' => ['required', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'rider_name.required' => 'A rider name is required.',
            'rider_name.max' => 'The rider name may not exceed 255 characters.',
            'is_available.boolean' => 'Availability must be true or false.',
        ];
    }
}