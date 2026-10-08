<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\UseCases\Admin\Order\AdminOrderCompleteUseCase;
use App\UseCases\Admin\Order\AdminOrderDetailUseCase;
use App\UseCases\Admin\Order\AdminOrderIndexUseCase;
use App\UseCases\Admin\Order\AdminOrderUpdateStatusUseCase;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;

class OrderController extends AdminBaseController
{
    private AdminOrderIndexUseCase $indexUseCase;

    private AdminOrderDetailUseCase $detailUseCase;

    private AdminOrderUpdateStatusUseCase $updateStatusUseCase;

    private AdminOrderCompleteUseCase $completeUseCase;

    public function __construct(
        AdminOrderIndexUseCase $indexUseCase,
        AdminOrderDetailUseCase $detailUseCase,
        AdminOrderUpdateStatusUseCase $updateStatusUseCase,
        AdminOrderCompleteUseCase $completeUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->detailUseCase = $detailUseCase;
        $this->updateStatusUseCase = $updateStatusUseCase;
        $this->completeUseCase = $completeUseCase;
    }

    public function orders(Request $request)
    {
        $rawUserId = $request->query('user_id');
        $userId = is_numeric($rawUserId) ? (int) $rawUserId : null;
        $output = $this->indexUseCase->handle($request->query('status'), $userId);
        $output->orders->appends($request->query());

        return view('admin.orders', [
            'orders' => $output->orders,
            'statusCounts' => $output->statusCounts,
        ]);
    }

    public function orderDetail(string $projectKey, $id)
    {
        $order = $this->detailUseCase->handle((int) $id);

        return view('admin.order-detail', compact('order'));
    }

    public function updateOrderStatus(OrderStatusRequest $request, string $projectKey, $id)
    {
        $status = $request->validated('status');
        try {
            $this->updateStatusUseCase->handle((int) $id, $status);
        } catch (OrderOperationException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()
            ->with('success', '注文ステータスを「'.AdminStatusLabels::orderStatusLabel($status).'」に更新しました');
    }

    public function completeOrder(string $projectKey, $id)
    {
        try {
            $this->completeUseCase->handle((int) $id);
        } catch (OrderOperationException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', '注文を完了しました');
    }
}
