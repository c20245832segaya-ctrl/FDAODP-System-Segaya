<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoodOrderRequest;
use App\Http\Requests\UpdateFoodOrderRequest;
use App\Models\Customer;
use App\Models\FoodOrder;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Services\FoodOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodOrderController extends Controller
{
    public function __construct(
        private FoodOrderService $foodOrderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = FoodOrder::with([
            'customer',
            'restaurant',
            'rider',
            'items',
        ])
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json($orders);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'customers' => Customer::orderBy('customer_name')->get(),
            'restaurants' => Restaurant::orderBy('restaurant_name')->get(),
            'available_riders' => Rider::where('is_available', true)->get(),
            'statuses' => [
                'pending',
                'assigned',
                'preparing',
                'picked_up',
                'out_for_delivery',
                'delivered',
                'cancelled',
            ],
        ]);
    }

    public function store(StoreFoodOrderRequest $request): JsonResponse
    {
        $order = $this->foodOrderService->create($request->validated());

        return response()->json([
            'message' => 'Food order created successfully.',
            'data' => $order,
        ], 201);
    }

    public function update(
        UpdateFoodOrderRequest $request,
        FoodOrder $food_order
    ): JsonResponse {
        $order = $this->foodOrderService->update(
            $food_order,
            $request->validated()
        );

        return response()->json([
            'message' => 'Food order updated successfully.',
            'data' => $order,
        ]);
    }
}