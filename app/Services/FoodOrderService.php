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
            $customer = Customer::firstOrCreate(
                [
                    'customer_name' => trim($data['customer_name']),
                    'delivery_address' => $this->cleanAddress($data['delivery_address']),
                ]
            );

            $restaurant = Restaurant::firstOrCreate([
                'restaurant_name' => trim($data['restaurant_name']),
            ]);

            $rider = $this->assignRider();

            $order = FoodOrder::create([
                'dispatch_id' => trim($data['dispatch_id']),
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurant->id,
                'rider_id' => $rider?->id,
                'delivery_fee' => $this->money($data['delivery_fee']),
                'subtotal' => 0,
                'total_bill' => 0,
                'dispatch_status' => $data['dispatch_status']
                    ?? ($rider ? 'assigned' : 'pending'),
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'menu_item_name' => trim($item['menu_item_name']),
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

            if (isset($data['customer_name']) || isset($data['delivery_address'])) {
                $customer = $order->customer;

                $customer->update([
                    'customer_name' => trim(
                        $data['customer_name'] ?? $customer->customer_name
                    ),
                    'delivery_address' => $this->cleanAddress(
                        $data['delivery_address'] ?? $customer->delivery_address
                    ),
                ]);
            }

            if (isset($data['restaurant_name'])) {
                $restaurant = Restaurant::firstOrCreate([
                    'restaurant_name' => trim($data['restaurant_name']),
                ]);

                $order->restaurant_id = $restaurant->id;
            }

            if (isset($data['dispatch_id'])) {
                $order->dispatch_id = trim($data['dispatch_id']);
            }

            if (isset($data['delivery_fee'])) {
                $order->delivery_fee = $this->money($data['delivery_fee']);
            }

            if (isset($data['dispatch_status'])) {
                $order->dispatch_status = $data['dispatch_status'];
            }

            if (
                in_array($order->dispatch_status, ['pending', 'assigned'], true)
                && !$order->rider_id
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

                foreach ($data['items'] as $item) {
                    $order->items()->create([
                        'menu_item_name' => trim($item['menu_item_name']),
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
        $rider = Rider::query()
            ->where('is_available', true)
            ->withCount([
                'foodOrders as active_dispatches_count' => function ($query) {
                    $query->whereIn('dispatch_status', self::ACTIVE_STATUSES);
                },
            ])
            ->orderBy('active_dispatches_count')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        return $rider;
    }

    private function recalculate(FoodOrder $order): void
    {
        $subtotal = $order->items()
            ->get()
            ->sum(fn (OrderItem $item) =>
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
        return preg_replace('/\s+/', ' ', trim($address)) ?? trim($address);
    }

    private function money(int|float|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}