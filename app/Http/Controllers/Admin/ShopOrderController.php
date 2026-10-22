<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderType;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Services\Admin\OrderAdminService;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;

class ShopOrderController extends AdminBaseController
{
    public function __construct(
        private OrderAdminService $orderAdminService,
    ) {}

    public function index(Request $request)
    {
        $rawUserId = $request->query('user_id');
        $userId = is_numeric($rawUserId) ? (int) $rawUserId : null;
        $status = $request->query('status');
        $orders = $this->orderAdminService->getPaginatedList(
            is_string($status) ? $status : null,
            20,
            $userId,
            OrderType::SHIPPING,
        );
        $orders->appends($request->query());

        return view('admin.shop-orders', [
            'orders' => $orders,
            'statusCounts' => $this->orderAdminService->getStatusCounts($userId, OrderType::SHIPPING),
        ]);
    }

    public function detail(string $projectKey, $id)
    {
        $order = $this->orderAdminService->findShippingOrFail((int) $id);

        return view('admin.order-detail', [
            'order' => $order,
            'shopOrdersMode' => true,
        ]);
    }

    public function updateStatus(OrderStatusRequest $request, string $projectKey, $id)
    {
        $this->orderAdminService->findShippingOrFail((int) $id);
        $status = $request->validated('status');
        try {
            $this->orderAdminService->updateStatus((int) $id, $status);
        } catch (OrderOperationException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()
            ->with('success', '注文ステータスを「'.AdminStatusLabels::orderStatusLabel($status).'」に更新しました');
    }

    public function complete(string $projectKey, $id)
    {
        $this->orderAdminService->findShippingOrFail((int) $id);
        try {
            $this->orderAdminService->complete((int) $id);
        } catch (OrderOperationException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', '注文を完了しました');
    }
}
