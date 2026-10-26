<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispatch_id' => ['required', 'string', 'max:100', 'unique:food_orders,dispatch_id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'restaurant_name' => ['required', 'string', 'max:255'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'dispatch_status' => [
                'sometimes',
                Rule::in(['pending', 'assigned', 'preparing', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled']),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_name' => ['required', 'string', 'max:255'],
            'items.*.item_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}