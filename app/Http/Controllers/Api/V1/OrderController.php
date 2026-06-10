<?php

namespace App\Http\Controllers\Api\V1;

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

class OrderController extends BaseApiController
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

            return $this->success(
                ['orders' => $orders->items()],
                null,
                200,
                [
                    'page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            );
        } catch (\Exception $e) {
            LogHelper::error('[Order] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('注文の取得に失敗しました', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = $this->showUseCase->handle(new OrderShowInput($user, $id));

            return $this->success(['order' => $order]);
        } catch (\Exception $e) {
            LogHelper::error('[Order] show failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('注文の取得に失敗しました', 404);
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

            return $this->success(
                ['order' => $order->toArray()],
                '注文が完了しました',
                201
            );
        } catch (\Exception $e) {
            LogHelper::error('[Order] store failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancelUnpaid(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $order = $this->cancelUnpaidUseCase->handle(new OrderCancelUnpaidInput($user, $id));

            return $this->success(
                ['order' => $order],
                '未決済注文をキャンセルしました'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
