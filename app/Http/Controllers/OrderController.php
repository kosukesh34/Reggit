<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private OrderService $orderService;
    private UserService $userService;

    public function __construct(OrderService $orderService, UserService $userService)
    {
        $this->orderService = $orderService;
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $orders = Order::where('user_id', $user->id)
                ->with('items.product')
                ->latest()
                ->paginate(20);
            
            return response()->json($orders);
        } catch (\Exception $e) {
            Log::error('Failed to fetch orders: ' . $e->getMessage());
            return response()->json(['error' => '注文の取得に失敗しました'], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = Order::where('user_id', $user->id)
                ->with('items.product')
                ->findOrFail($id);
            
            return response()->json($order);
        } catch (\Exception $e) {
            Log::error('Failed to fetch order: ' . $e->getMessage());
            return response()->json(['error' => '注文の取得に失敗しました'], 404);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card,line_pay,points,stripe',
            'points_used' => 'integer|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = $this->orderService->createOrder($validated, $user);

            return response()->json([
                'message' => '注文が完了しました',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

