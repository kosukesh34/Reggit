<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 売上分析に基づき、条件に該当する会員ID一覧を返すサービス。
 * お知らせのセグメント配信（売上条件で対象を自動選択）で利用する。
 */
class SalesSegmentService
{
    public function __construct(
        private CouponDeliveryTargetRepository $targetRepository,
    ) {}

    /**
     * 指定期間・条件に該当する会員IDを取得する。
     *
     * @param  int  $projectId  プロジェクトID
     * @param  int  $periodDays  対象期間（直近何日間か）
     * @param  float|null  $minTotalAmount  この金額以上購入した会員（null のときは条件に含めない）
     * @param  bool  $noOrdersOnly  true のとき「期間内に1件も注文していない会員」のみ
     * @return Collection<int, int> 会員IDのコレクション
     */
    public function getMemberIdsBySalesCriteria(
        int $projectId,
        int $periodDays,
        ?float $minTotalAmount = null,
        bool $noOrdersOnly = false
    ): Collection {
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays($periodDays)->startOfDay();

        if ($noOrdersOnly) {
            return collect($this->targetRepository->findIdsWithNoOrdersInPeriod($projectId, $start, $end));
        }

        if ($minTotalAmount !== null && $minTotalAmount > 0) {
            return collect($this->targetRepository->findIdsByMinSales($projectId, $start, $end, $minTotalAmount));
        }

        return collect();
    }
}
