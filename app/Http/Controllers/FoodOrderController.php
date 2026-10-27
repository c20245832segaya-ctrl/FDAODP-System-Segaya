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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FoodOrderController extends Controller
{
    public function __construct(
        private FoodOrderService $foodOrderService
    ) {}

    /**
     * Display the food orders table.
     */
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        $orders = FoodOrder::with([
            'customer',
            'restaurant',
            'rider',
            'items',
        ])
            ->latest()
            ->paginate($request->integer('per_page', 10));

        if (! $request->expectsJson()) {
            return Inertia::render('food-orders/index', [
                'foodOrders' => $orders,
                'success' => $request->session()->get('success'),
            ]);
        }

        return response()->json($orders);
    }

    /**
     * Display the create food order form.
     */
    public function create(Request $request): JsonResponse|InertiaResponse
    {
        $customers = Customer::orderBy('customer_name')->get();

        $restaurants = Restaurant::orderBy('restaurant_name')->get();

        $riders = Rider::where('is_available', true)->get();

        $statuses = [
            'pending',
            'assigned',
            'preparing',
            'picked_up',
            'out_for_delivery',
            'delivered',
            'cancelled',
        ];

        if (! $request->expectsJson()) {
            return Inertia::render('food-orders/create', compact('statuses'));
        }

        return response()->json([
            'customers' => $customers,
            'restaurants' => $restaurants,
            'available_riders' => $riders,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Save a new food order.
     */
    public function store(
        StoreFoodOrderRequest $request
    ): JsonResponse|RedirectResponse {
        $order = $this->foodOrderService->create(
            $request->validated()
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->route('food-orders.index')
                ->with('success', 'Food order created successfully.');
        }

        return response()->json([
            'message' => 'Food order created successfully.',
            'data' => $order,
        ], 201);
    }

    /**
     * Update an existing food order.
     */
    public function update(
        UpdateFoodOrderRequest $request,
        FoodOrder $food_order
    ): JsonResponse|RedirectResponse {
        $order = $this->foodOrderService->update(
            $food_order,
            $request->validated()
        );

        if (! $request->expectsJson()) {
            return redirect()
                ->route('food-orders.index')
                ->with('success', 'Food order updated successfully.');
        }

        return response()->json([
            'message' => 'Food order updated successfully.',
            'data' => $order,
        ]);
    }
}
