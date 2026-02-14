<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\OrderStatusRequest;
use App\Services\Admin\OrderAdminService;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;

class OrderController extends AdminBaseController
{
    public function __construct(
        private OrderAdminService $orderAdminService
    ) {
    }

    public function orders(Request $request)
    {
        $orders = $this->orderAdminService->getPaginatedList($request->query('status'), 20);
        $orders->appends($request->query());
        $statusCounts = $this->orderAdminService->getStatusCounts();
        return view('admin.orders', compact('orders', 'statusCounts'));
    }

    public function orderDetail($id)
    {
        $order = $this->orderAdminService->findOrFail((int) $id);
        return view('admin.order-detail', compact('order'));
    }

    public function updateOrderStatus(OrderStatusRequest $request, $id)
    {
        $status = $request->validated('status');
        $this->orderAdminService->updateStatus((int) $id, $status);
        return redirect()->back()
            ->with('success', '注文ステータスを「' . AdminStatusLabels::orderStatusLabel($status) . '」に更新しました');
    }

    public function completeOrder($id)
    {
        $this->orderAdminService->complete((int) $id);
        return redirect()->back()->with('success', '注文を完了しました');
    }
}
