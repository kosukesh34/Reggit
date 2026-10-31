<?php

namespace App\Services;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\Repositories\ProductStockRepository;
use App\Enums\PointTransactionType;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/** キャンセル済み注文の利用ポイント返却・獲得ポイント取消・ガチャ取消・在庫戻し（冪等）。 */
class OrderCancellationReverser
{
    private const DESC_REFUND_CANCEL = '注文キャンセルによる返却';

    private const DESC_REVERSED_CANCEL = '注文キャンセルによる取り消し';

    public function __construct(
        private MemberRepository $memberRepository,
        private OrderRewardService $rewards,
        private OrderRepository $orderRepository,
        private ProductStockRepository $productStockRepository,
    ) {}

    public function handleOrderCancellation(Order $order): void
    {
        $projectId = (int) ($order->project_id ?? 0);
        $aggregate = $this->orderRepository->findById((int) $order->id, $projectId);
        if ($aggregate === null || $aggregate->isCancelled() === false) {
            return;
        }

        $order->load('user', 'items.product');

        DB::transaction(function () use ($order, $projectId): void {
            if ($order->user !== null) {
                $member = $this->memberRepository->findByUserIdForUpdate((int) $order->user_id, $projectId);
                if ($member !== null) {
                    $this->refundPointsUsed($order, $member);
                    $this->reversePointsEarned($order, $member);
                    $this->memberRepository->save($member);
                }
                if ($order->user->member !== null) {
                    $this->rewards->revokeLottery($order);
                }
            }

            $this->restoreProductStock($order, $projectId);
        });
        if ($order->user !== null) {
            $this->rewards->syncMemberRank($order->user);
        }
        // 未決済キャンセル専用。作成時にスマレジ未同期のため差分同期しない。
    }

    private function refundPointsUsed(Order $order, MemberAggregate $member): int
    {
        if ($order->points_used <= 0) {
            return 0;
        }

        if ($this->memberRepository->hasPointTransactionForOrder(
            (int) $order->id,
            PointTransactionType::REFUNDED,
            self::DESC_REFUND_CANCEL,
        )) {
            return 0;
        }

        $points = (int) $order->points_used;
        $member->refundUsedPointsForOrder(
            (int) $order->id,
            $points,
            self::DESC_REFUND_CANCEL,
        );

        return $points;
    }

    private function reversePointsEarned(Order $order, MemberAggregate $member): int
    {
        if ($order->points_earned <= 0) {
            return 0;
        }

        if ($this->memberRepository->hasPointTransactionForOrder(
            (int) $order->id,
            PointTransactionType::REVERSED,
        )) {
            return 0;
        }

        $points = (int) $order->points_earned;
        $member->reverseEarnedPointsForOrder(
            (int) $order->id,
            $points,
            self::DESC_REVERSED_CANCEL,
        );

        return $points;
    }

    private function restoreProductStock(Order $order, int $projectId): void
    {
        foreach ($order->items as $item) {
            $this->productStockRepository->restore(
                (int) $item->product_id,
                $projectId,
                (int) $item->quantity,
            );
        }
    }
}
