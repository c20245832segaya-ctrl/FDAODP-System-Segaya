<?php

namespace App\Services;

use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RiderService
{
    private const ACTIVE_STATUSES = [
        'pending',
        'assigned',
        'preparing',
        'picked_up',
        'out_for_delivery',
    ];

    public function create(array $data): Rider
    {
        return DB::transaction(function () use ($data) {
            return Rider::create([
                'rider_name' => $this->cleanText($data['rider_name']),
                'is_available' => $data['is_available'] ?? true,
            ]);
        }, 3);
    }

    public function update(Rider $rider, array $data): Rider
    {
        return DB::transaction(function () use ($rider, $data) {
            $rider = Rider::query()
                ->whereKey($rider->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                array_key_exists('is_available', $data)
                && ! $data['is_available']
                && $rider->foodOrders()
                    ->whereIn('dispatch_status', self::ACTIVE_STATUSES)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'is_available' => [
                        'A rider with active dispatches cannot be marked unavailable.',
                    ],
                ]);
            }

            $updates = $data;

            if (array_key_exists('rider_name', $updates)) {
                $updates['rider_name'] = $this->cleanText($updates['rider_name']);
            }

            $rider->update($updates);

            return $rider->fresh();
        }, 3);
    }

    private function cleanText(string $value): string
    {
        $plainText = strip_tags($value);
        $cleaned = preg_replace('/\s+/', ' ', trim($plainText)) ?? trim($plainText);

        if ($cleaned === '') {
            throw ValidationException::withMessages([
                'rider_name' => ['The rider name must contain non-HTML text.'],
            ]);
        }

        return $cleaned;
    }
}
