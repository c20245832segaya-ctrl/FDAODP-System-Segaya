<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFoodOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $orderId = $this->route('food_order');

        return [
            'dispatch_id' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('food_orders', 'dispatch_id')->ignore($orderId),
            ],
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'delivery_address' => ['sometimes', 'required', 'string', 'max:1000'],
            'restaurant_name' => ['sometimes', 'required', 'string', 'max:255'],
            'delivery_fee' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'dispatch_status' => [
                'sometimes',
                Rule::in(['pending', 'assigned', 'preparing', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled']),
            ],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.menu_item_name' => ['required', 'string', 'max:255'],
            'items.*.item_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'dispatch_id.required' => 'A dispatch ID is required.',
            'dispatch_id.unique' => 'This dispatch ID is already in use.',
            'customer_name.required' => 'A customer name is required.',
            'delivery_address.required' => 'A delivery address is required.',
            'restaurant_name.required' => 'A restaurant name is required.',
            'delivery_fee.decimal' => 'The delivery fee may have no more than 2 decimal places.',
            'dispatch_status.in' => 'The selected dispatch status is invalid.',
            'items.required' => 'Add at least one item to the order.',
            'items.min' => 'Add at least one item to the order.',
            'items.*.menu_item_name.required' => 'Each item must have a name.',
            'items.*.item_price.decimal' => 'Each item price may have no more than 2 decimal places.',
            'items.*.quantity.min' => 'Each item quantity must be at least 1.',
        ];
    }
}