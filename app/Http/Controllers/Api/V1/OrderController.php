<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Support\OrderRequestHandler;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class OrderController extends BaseApiController
{
    public function __construct(
        private OrderRequestHandler $handler,
    ) {}

    public function index(Request $request)
    {
        try {
            $orders = $this->handler->index($request);

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
                'error' => $e->getMessage(),
            ]);

            return $this->error('注文の取得に失敗しました', 500);
        }
    }

    public function show(Request $request)
    {
        $id = (int) $request->route('id');
        try {
            $order = $this->handler->show($request, $id);

            return $this->success(['order' => $order]);
        } catch (\Exception $e) {
            LogHelper::error('[Order] show failed', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('注文の取得に失敗しました', 404);
        }
    }

    public function store(OrderStoreRequest $request)
    {
        try {
            $order = $this->handler->store($request, $request->validated());

            return $this->success(
                ['order' => $order->toArray()],
                '注文が完了しました',
                201
            );
        } catch (OrderOperationException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            LogHelper::error('[Order] store failed', [
                'project_id' => $request->attributes->get('project_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->error('注文の作成に失敗しました', 500);
        }
    }

    public function cancelUnpaid(Request $request)
    {
        $id = (int) $request->route('id');
        try {
            $order = $this->handler->cancelUnpaid($request, $id);

            return $this->success(
                ['order' => $order],
                '未決済注文をキャンセルしました'
            );
        } catch (OrderOperationException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error('注文のキャンセルに失敗しました', 500);
        }
    }
}
