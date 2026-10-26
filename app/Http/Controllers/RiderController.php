<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRiderRequest;
use App\Http\Requests\UpdateRiderRequest;
use App\Models\Rider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RiderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $riders = Rider::withCount([
            'foodOrders as active_dispatches_count' => function ($query) {
                $query->whereIn('dispatch_status', [
                    'pending',
                    'assigned',
                    'preparing',
                    'picked_up',
                    'out_for_delivery',
                ]);
            },
        ])
            ->orderBy('rider_name')
            ->paginate($request->integer('per_page', 10));

        return response()->json($riders);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'fields' => [
                'rider_name' => 'required|string|max:255',
                'is_available' => 'boolean',
            ],
        ]);
    }

    public function store(StoreRiderRequest $request): JsonResponse
    {
        $rider = Rider::create($request->validated());

        return response()->json([
            'message' => 'Rider created successfully.',
            'data' => $rider,
        ], 201);
    }

    public function update(
        UpdateRiderRequest $request,
        Rider $rider
    ): JsonResponse {
        $data = $request->validated();

        if (
            isset($data['is_available'])
            && $data['is_available'] === false
            && $rider->foodOrders()
                ->whereIn('dispatch_status', [
                    'pending',
                    'assigned',
                    'preparing',
                    'picked_up',
                    'out_for_delivery',
                ])
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'is_available' => [
                    'A rider with active dispatches cannot be marked unavailable.',
                ],
            ]);
        }

        $rider->update($data);

        return response()->json([
            'message' => 'Rider updated successfully.',
            'data' => $rider->fresh(),
        ]);
    }
}