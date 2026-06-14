<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private const DEFAULT_POINTS_EARN_RATE = 100;

    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    private const DESC_REFUND_CANCEL = '注文キャンセルによる返却';

    private const DESC_REVERSED_CANCEL = '注文キャンセルによる取り消し';

    public function createOrder(array $orderData, User $user): Order
    {
        DB::beginTransaction();
        try {
            $this->validateOrderTypeAndPaymentMethod($orderData, $user->project_id ?? null);
            $orderItems = $this->validateAndPrepareOrderItems($orderData['items']);
            $totalAmount = $this->calculateTotalAmount($orderItems);

            $pointsUsed = $orderData['points_used'] ?? 0;
            $finalAmount = $this->processPoints($user, $pointsUsed, $totalAmount);

            $order = $this->createOrderRecord($user, $finalAmount, $orderData, $pointsUsed);
            $this->createOrderItems($order, $orderItems);
            $this->updateProductStock($orderItems);
            $this->awardPoints($user, $order, $finalAmount);
            $this->awardStamps($user, $order, $finalAmount);

            DB::commit();

            return $order->load('items.product');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation error: '.$e->getMessage());
            throw $e;
        }
    }

    private function validateAndPrepareOrderItems(array $items): array
    {
        $orderItems = [];

        foreach ($items as $item) {
            $product = Product::with('optionGroups.options')->findOrFail($item['product_id']);

            $this->validateProductAvailability($product, $item['quantity']);

            [$optionsDelta, $optionsSummary] = $this->resolveSelectedOptions($product, $item['option_ids'] ?? []);

            $unitPrice = (float) $product->price + $optionsDelta;

            $note = $item['notes'] ?? null;
            if ($optionsSummary !== '') {
                $note = $note ? $optionsSummary.' / '.$note : $optionsSummary;
            }

            $orderItems[] = [
                'product' => $product,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $unitPrice,
                'notes' => $note,
            ];
        }

        return $orderItems;
    }

    /**
     * 選択されたオプションの追加料金合計と、人が読める要約文を返す。
     *
     * @param  array<int>  $optionIds
     * @return array{0: float, 1: string}
     */
    private function resolveSelectedOptions(Product $product, array $optionIds): array
    {
        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        if ($optionIds === []) {
            return [0.0, ''];
        }

        $validOptions = $product->optionGroups
            ->flatMap(fn ($group) => $group->options)
            ->whereIn('id', $optionIds);

        $delta = 0.0;
        $names = [];
        foreach ($validOptions as $option) {
            $delta += (float) $option->price_delta;
            $names[] = $option->name;
        }

        return [$delta, $names === [] ? '' : implode(', ', $names)];
    }

    private function validateProductAvailability(Product $product, int $quantity): void
    {
        if ($product->is_available === false) {
            throw new \Exception("商品 {$product->name} は現在利用できません");
        }

        if ($product->stock !== null && $product->stock < $quantity) {
            throw new \Exception("商品 {$product->name} の在庫が不足しています");
        }
    }

    private function calculateTotalAmount(array $orderItems): float
    {
        $total = 0;
        foreach ($orderItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    private function processPoints(User $user, int $pointsUsed, float $totalAmount): float
    {
        if ($pointsUsed <= 0) {
            return $totalAmount;
        }

        $member = $user->member;
        if ($member === null) {
            throw new \Exception('会員登録が必要です');
        }

        $member->syncPointsLedger();
        $member->refresh();
        if ($member->getTotalPointsFromTransactions() < $pointsUsed) {
            throw new \Exception('ポイントが不足しています');
        }

        $member->usePoints($pointsUsed, '注文で使用', null);

        return max(0, $totalAmount - $pointsUsed);
    }

    private function createOrderRecord(User $user, float $finalAmount, array $orderData, int $pointsUsed): Order
    {
        return Order::create([
            'project_id' => $user->project_id ?? AdminContext::getCurrentProjectId() ?? 1,
            'user_id' => $user->id,
            'total_amount' => $finalAmount,
            'status' => OrderStatus::PENDING,
            'payment_method' => $orderData['payment_method'],
            'points_used' => $pointsUsed,
            'points_earned' => 0,
            'notes' => $orderData['notes'] ?? null,
            'order_type' => $orderData['order_type'] ?? 'mobile',
        ]);
    }

    private function validateOrderTypeAndPaymentMethod(array $orderData, ?int $projectId): void
    {
        $orderType = (string) ($orderData['order_type'] ?? OrderType::MOBILE);
        $paymentMethod = (string) ($orderData['payment_method'] ?? '');
        $configuredOrderMode = ShopSetting::getOrderMode($projectId);

        if ($configuredOrderMode === ShopSetting::ORDER_MODE_MOBILE_ONLY && $orderType !== OrderType::MOBILE) {
            throw new \Exception('現在はモバイル注文のみ利用できます。');
        }
        if ($configuredOrderMode === ShopSetting::ORDER_MODE_IN_STORE_ONLY && $orderType !== OrderType::IN_STORE) {
            throw new \Exception('現在は店内注文のみ利用できます。');
        }

        if ($orderType === OrderType::IN_STORE) {
            if ($paymentMethod !== PaymentMethod::CASH) {
                throw new \Exception('店内注文は後払い（現金）で作成してください。');
            }

            return;
        }

        if ($orderType === OrderType::MOBILE) {
            if (! in_array($paymentMethod, [PaymentMethod::STRIPE, PaymentMethod::CASH], true)) {
                throw new \Exception(config('native_app.order_type_mobile_label', 'Reggit').'の決済方法が不正です。');
            }
        }
    }

    private function createOrderItems(Order $order, array $orderItems): void
    {
        foreach ($orderItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'notes' => $item['notes'],
            ]);
        }
    }

    private function updateProductStock(array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $product = $item['product'];
            if ($product->stock === null) {
                continue;
            }
            $product->stock -= $item['quantity'];
            $product->save();
        }
    }

    private function awardPoints(User $user, Order $order, float $finalAmount): void
    {
        $member = $user->member;
        if ($member === null) {
            return;
        }

        $projectId = $user->project_id ?? null;
        $pointsPerOrder = (int) ShopSetting::getPointsPerOrder($projectId);
        $rate = $this->getPointsEarnRate($projectId);
        $pointsFromAmount = $rate > 0 && $finalAmount > 0 ? (int) floor($finalAmount / $rate) : 0;
        $pointsEarned = $pointsPerOrder + $pointsFromAmount;

        if ($pointsEarned > 0) {
            $member->addPoints($pointsEarned, '購入で獲得', $order->id);
            $order->update(['points_earned' => $pointsEarned]);
        }
    }

    private function awardStamps(User $user, Order $order, float $finalAmount): void
    {
        $member = $user->member;
        if ($member === null || $finalAmount <= 0) {
            return;
        }

        $projectId = $user->project_id ?? null;
        $stampCardId = ShopSetting::getOrderStampCardId($projectId);
        $stampEarnPerAmount = (int) ShopSetting::getStampEarnPerAmount($projectId);

        if ($stampCardId === null || $stampEarnPerAmount <= 0) {
            return;
        }

        $stampCount = (int) floor($finalAmount / $stampEarnPerAmount);
        if ($stampCount > 0) {
            $this->memberAdminService->addStampsToMember($member, $stampCardId, $stampCount);
        }
    }

    private function getPointsEarnRate(?int $projectId): int
    {
        $rate = ShopSetting::getPointEarnRate($projectId);

        return $rate > 0 ? $rate : self::DEFAULT_POINTS_EARN_RATE;
    }

    public function handleOrderCancellation(Order $order): void
    {
        if ($order->status !== OrderStatus::CANCELLED) {
            return;
        }

        $order->load('user.member', 'items.product');
        $member = $order->user ? $order->user->member : null;

        DB::beginTransaction();
        try {
            if ($member !== null) {
                $this->refundPointsUsed($order, $member);
                $this->reversePointsEarned($order, $member);
                $member->recalcPointsFromTransactions();
            }

            $this->restoreProductStock($order);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation handling failed: '.$e->getMessage(), ['order_id' => $order->id]);
            throw $e;
        }
    }

    private function refundPointsUsed(Order $order, Member $member): void
    {
        if ($order->points_used <= 0) {
            return;
        }
        $already = PointTransaction::where('order_id', $order->id)
            ->where('type', 'refunded')
            ->where('description', self::DESC_REFUND_CANCEL)
            ->exists();
        if ($already) {
            return;
        }

        PointTransaction::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'type' => 'refunded',
            'points' => $order->points_used,
            'description' => self::DESC_REFUND_CANCEL,
            'order_id' => $order->id,
        ]);
    }

    private function reversePointsEarned(Order $order, Member $member): void
    {
        if ($order->points_earned <= 0) {
            return;
        }
        $already = PointTransaction::where('order_id', $order->id)
            ->where('type', 'reversed')
            ->exists();
        if ($already) {
            return;
        }

        PointTransaction::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'type' => 'reversed',
            'points' => -$order->points_earned,
            'description' => self::DESC_REVERSED_CANCEL,
            'order_id' => $order->id,
        ]);
    }

    private function restoreProductStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product !== null && $product->stock !== null) {
                $product->increment('stock', $item->quantity);
            }
        }
    }
}
