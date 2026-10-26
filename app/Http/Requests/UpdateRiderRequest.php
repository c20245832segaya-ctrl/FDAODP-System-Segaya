<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rider_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}