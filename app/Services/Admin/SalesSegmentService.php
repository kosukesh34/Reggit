<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 売上分析に基づき、条件に該当する会員ID一覧を返すサービス。
 * お知らせのセグメント配信（売上条件で対象を自動選択）で利用する。
 */
class SalesSegmentService
{
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
            return $this->getMembersWithNoOrdersInPeriod($projectId, $start, $end);
        }

        if ($minTotalAmount !== null && $minTotalAmount > 0) {
            return $this->getMembersWithMinTotalInPeriod($projectId, $start, $end, $minTotalAmount);
        }

        return collect();
    }

    /**
     * 期間内に指定金額以上購入した会員のID一覧。
     */
    private function getMembersWithMinTotalInPeriod(
        int $projectId,
        Carbon $start,
        Carbon $end,
        float $minTotalAmount
    ): Collection {
        $userIds = Order::query()
            ->where('project_id', $projectId)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->selectRaw('user_id, SUM(total_amount) as total')
            ->groupBy('user_id')
            ->havingRaw('SUM(total_amount) >= ?', [(string) $minTotalAmount])
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', $userIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * 期間内に1件も注文していない会員のID一覧。
     */
    private function getMembersWithNoOrdersInPeriod(int $projectId, Carbon $start, Carbon $end): Collection
    {
        $userIdsWithOrders = Order::query()
            ->where('project_id', $projectId)
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('user_id');

        $query = Member::query()->where('project_id', $projectId);

        if ($userIdsWithOrders->isNotEmpty()) {
            $query->whereNotIn('user_id', $userIdsWithOrders->all());
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values();
    }
}
