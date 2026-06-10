<?php

namespace App\Http\Controllers;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Services\UserService;
use App\UseCases\Order\Data\OrderCancelUnpaidInput;
use App\UseCases\Order\Data\OrderIndexInput;
use App\UseCases\Order\Data\OrderShowInput;
use App\UseCases\Order\Data\OrderStoreInput;
use App\UseCases\Order\OrderCancelUnpaidUseCase;
use App\UseCases\Order\OrderIndexUseCase;
use App\UseCases\Order\OrderShowUseCase;
use App\UseCases\Order\OrderStoreUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private OrderIndexUseCase $indexUseCase,
        private OrderShowUseCase $showUseCase,
        private OrderStoreUseCase $storeUseCase,
        private OrderCancelUnpaidUseCase $cancelUnpaidUseCase,
        private UserService $userService,
    ) {}

    public function index(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $orders = $this->indexUseCase->handle(new OrderIndexInput($user));

            return response()->json($orders);
        } catch (\Exception $e) {
            LogHelper::error('[Order] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '注文の取得に失敗しました'], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = $this->showUseCase->handle(new OrderShowInput($user, $id));

            return response()->json($order);
        } catch (\Exception $e) {
            LogHelper::error('[Order] show failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '注文の取得に失敗しました'], 404);
        }
    }

    public function store(Request $request)
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('project_id', $projectId),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:'.implode(',', array_keys(PaymentMethod::getValues())),
            'points_used' => 'integer|min:0',
            'notes' => 'nullable|string',
            'order_type' => 'nullable|in:'.implode(',', array_keys(OrderType::getValues())),
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = $this->storeUseCase->handle(new OrderStoreInput($user, $validated));

            return response()->json([
                'message' => '注文が完了しました',
                'order' => $order->toArray(),
            ], 201);
        } catch (\Exception $e) {
            LogHelper::error('[Order] store failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancelUnpaid(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = $this->cancelUnpaidUseCase->handle(new OrderCancelUnpaidInput($user, $id));

            return response()->json([
                'message' => '未決済注文をキャンセルしました',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
