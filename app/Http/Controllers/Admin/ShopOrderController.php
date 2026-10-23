<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Services\Admin\OrderAdminService;
use App\Services\Shop\ShopOperations;
use App\UseCases\Shop\RefundShopOrderUseCase;
use App\Util\AdminStatusLabels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $order = $this->orderAdminService->findShippingOrFail((int) $id);
        $status = $request->validated('status');
        $shipping = $request->validate([
            'shipping_carrier' => ['nullable', Rule::in(array_keys(ShopOperations::CARRIERS))],
            'shipping_tracking_number' => ['nullable', 'string', 'max:64', 'regex:/^[0-9A-Za-z\-]+$/'],
        ], ['shipping_tracking_number.regex' => 'お問い合わせ番号は英数字とハイフンで入力してください。']);
        if ($status === OrderStatus::SHIPPED && $request->has('shipping_carrier')) {
            // 発送メールに載せるため、状態を変える前に保存する
            $order->forceFill([
                'shipping_carrier' => $shipping['shipping_carrier'] ?? null,
                'shipping_tracking_number' => trim((string) ($shipping['shipping_tracking_number'] ?? '')) ?: null,
            ])->save();
        }
        try {
            $this->orderAdminService->updateStatus((int) $id, $status);
        } catch (OrderOperationException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()
            ->with('success', '注文ステータスを「'.AdminStatusLabels::orderStatusLabel($status).'」に更新しました');
    }

    public function refund(string $projectKey, $id, RefundShopOrderUseCase $refunds)
    {
        $order = $this->orderAdminService->findShippingOrFail((int) $id);
        try {
            $refunds->handle($order);
        } catch (OrderOperationException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', '全額を返金し、注文をキャンセルしました'
            .($order->smaregi_transaction_head_id ? '。スマレジの取引を取消してください' : ''));
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
