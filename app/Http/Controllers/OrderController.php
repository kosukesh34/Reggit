<?php

namespace App\Http\Controllers;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Support\OrderRequestHandler;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderRequestHandler $handler,
    ) {}

    public function index(Request $request)
    {
        try {
            $orders = $this->handler->index($request);

            return response()->json($orders);
        } catch (\Exception $e) {
            LogHelper::error('[Order] index failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '注文の取得に失敗しました'], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $order = $this->handler->show($request, $id);

            return response()->json($order);
        } catch (\Exception $e) {
            LogHelper::error('[Order] show failed', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '注文の取得に失敗しました'], 404);
        }
    }

    public function store(OrderStoreRequest $request)
    {
        try {
            $order = $this->handler->store($request, $request->validated());

            return response()->json([
                'message' => '注文が完了しました',
                'order' => $order->toArray(),
            ], 201);
        } catch (OrderOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            LogHelper::error('[Order] store failed', [
                'project_id' => $request->attributes->get('project_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '注文の作成に失敗しました',
            ], 500);
        }
    }

    public function cancelUnpaid(Request $request, int $id)
    {
        try {
            $order = $this->handler->cancelUnpaid($request, $id);

            return response()->json([
                'message' => '未決済注文をキャンセルしました',
                'order' => $order,
            ]);
        } catch (OrderOperationException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '注文のキャンセルに失敗しました',
            ], 500);
        }
    }
}
