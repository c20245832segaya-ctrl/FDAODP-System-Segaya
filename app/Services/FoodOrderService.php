<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FoodOrder;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FoodOrderService
{
    private const ACTIVE_STATUSES = [
        'pending',
        'assigned',
        'preparing',
        'picked_up',
        'out_for_delivery',
    ];

    public function create(array $data): FoodOrder
    {
        return DB::transaction(function () use ($data) {
            $customerName = $this->cleanText(
                $data['customer_name'],
                'customer_name'
            );

            $address = $this->cleanAddress($data['delivery_address']);

            $customer = Customer::firstOrCreate([
                'customer_name' => $customerName,
                'delivery_address' => $address,
            ]);

            $restaurant = Restaurant::firstOrCreate([
                'restaurant_name' => $this->cleanText(
                    $data['restaurant_name'],
                    'restaurant_name'
                ),
            ]);

            // Use the selected rider if supplied; otherwise assign one automatically.
            if (! empty($data['rider_id'])) {
                $rider = Rider::query()
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->find($data['rider_id']);

                if (! $rider) {
                    throw ValidationException::withMessages([
                        'rider_id' => [
                            'The selected rider is unavailable. Please choose another rider.',
                        ],
                    ]);
                }
            } else {
                $rider = $this->assignRider();
            }

            $status = $data['dispatch_status']
                ?? ($rider ? 'assigned' : 'pending');

            $order = FoodOrder::create([
                'dispatch_id' => $this->cleanText(
                    $data['dispatch_id'],
                    'dispatch_id'
                ),
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurant->id,
                'rider_id' => $rider?->id,
                'delivery_fee' => $this->money($data['delivery_fee']),
                'subtotal' => 0,
                'total_bill' => 0,
                'dispatch_status' => $status,
            ]);

            foreach ($data['items'] as $index => $item) {
                $order->items()->create([
                    'menu_item_name' => $this->cleanText(
                        $item['menu_item_name'],
                        "items.{$index}.menu_item_name"
                    ),
                    'item_price' => $this->money($item['item_price']),
                    'quantity' => (int) $item['quantity'],
                ]);
            }

            $this->recalculate($order);

            return $order->load([
                'customer',
                'restaurant',
                'rider',
                'items',
            ]);
        }, 3);
    }

    public function update(FoodOrder $order, array $data): FoodOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $order = FoodOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                isset($data['customer_name'])
                || isset($data['delivery_address'])
            ) {
                $customer = $order->customer;

                $customer->update([
                    'customer_name' => $this->cleanText(
                        $data['customer_name'] ?? $customer->customer_name,
                        'customer_name'
                    ),
                    'delivery_address' => $this->cleanAddress(
                        $data['delivery_address']
                            ?? $customer->delivery_address
                    ),
                ]);
            }

            if (isset($data['restaurant_name'])) {
                $restaurant = Restaurant::firstOrCreate([
                    'restaurant_name' => $this->cleanText(
                        $data['restaurant_name'],
                        'restaurant_name'
                    ),
                ]);

                $order->restaurant_id = $restaurant->id;
            }

            if (isset($data['dispatch_id'])) {
                $order->dispatch_id = $this->cleanText(
                    $data['dispatch_id'],
                    'dispatch_id'
                );
            }

            if (isset($data['delivery_fee'])) {
                $order->delivery_fee = $this->money(
                    $data['delivery_fee']
                );
            }

            if (isset($data['dispatch_status'])) {
                $order->dispatch_status = $data['dispatch_status'];
            }

            if (array_key_exists('rider_id', $data)) {
                if ($data['rider_id'] === null || $data['rider_id'] === '') {
                    $order->rider_id = null;
                } else {
                    $rider = Rider::query()
                        ->where('is_available', true)
                        ->lockForUpdate()
                        ->find($data['rider_id']);

                    if (! $rider) {
                        throw ValidationException::withMessages([
                            'rider_id' => [
                                'The selected rider is unavailable. Please choose another rider.',
                            ],
                        ]);
                    }

                    $order->rider_id = $rider->id;
                }
            } elseif (
                in_array($order->dispatch_status, ['pending', 'assigned'], true)
                && ! $order->rider_id
            ) {
                $rider = $this->assignRider();

                if ($rider) {
                    $order->rider_id = $rider->id;
                    $order->dispatch_status = 'assigned';
                }
            }

            $order->save();

            if (isset($data['items'])) {
                $order->items()->delete();

                foreach ($data['items'] as $index => $item) {
                    $order->items()->create([
                        'menu_item_name' => $this->cleanText(
                            $item['menu_item_name'],
                            "items.{$index}.menu_item_name"
                        ),
                        'item_price' => $this->money($item['item_price']),
                        'quantity' => (int) $item['quantity'],
                    ]);
                }
            }

            $this->recalculate($order);

            return $order->fresh([
                'customer',
                'restaurant',
                'rider',
                'items',
            ]);
        }, 3);
    }

    private function assignRider(): ?Rider
    {
        return Rider::query()
            ->where('is_available', true)
            ->withCount([
                'foodOrders as active_dispatches_count' => function ($query) {
                    $query->whereIn(
                        'dispatch_status',
                        self::ACTIVE_STATUSES
                    );
                },
            ])
            ->orderBy('active_dispatches_count')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function recalculate(FoodOrder $order): void
    {
        $subtotal = $order->items()
            ->get()
            ->sum(
                fn (OrderItem $item) =>
                    (float) $item->item_price * $item->quantity
            );

        $subtotal = round($subtotal, 2);
        $deliveryFee = round((float) $order->delivery_fee, 2);

        $order->update([
            'subtotal' => $subtotal,
            'total_bill' => round($subtotal + $deliveryFee, 2),
        ]);
    }

    private function cleanAddress(string $address): string
    {
        return $this->cleanText($address, 'delivery_address');
    }

    private function cleanText(string $value, string $field): string
    {
        $plainText = strip_tags($value);
        $cleaned = preg_replace('/\s+/', ' ', trim($plainText))
            ?? trim($plainText);

        if ($cleaned === '') {
            throw ValidationException::withMessages([
                $field => ['This field must contain non-HTML text.'],
            ]);
        }

        return $cleaned;
    }

    private function money(int|float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
