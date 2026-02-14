<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Util\AdminContext;
use App\UseCases\Order\OrderIndexUseCase;
use App\UseCases\Order\OrderShowUseCase;
use App\UseCases\Order\OrderStoreUseCase;
use Illuminate\Http\Request;
use App\Util\LogHelper;
use Illuminate\Validation\Rule;

class OrderController extends BaseApiController
{
    public function __construct(
        private OrderIndexUseCase $indexUseCase,
        private OrderShowUseCase $showUseCase,
        private OrderStoreUseCase $storeUseCase
    ) {
    }

    public function index(Request $request)
    {
        try {
            $orders = $this->indexUseCase->handle($request, 20);

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
            $order = $this->showUseCase->handle($request, $id);

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
            'payment_method' => 'required|in:' . implode(',', array_keys(PaymentMethod::getValues())),
            'points_used' => 'integer|min:0',
            'notes' => 'nullable|string',
            'order_type' => 'nullable|in:' . implode(',', array_keys(OrderType::getValues())),
        ]);

        try {
            $order = $this->storeUseCase->handle($request, $validated);
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
}
