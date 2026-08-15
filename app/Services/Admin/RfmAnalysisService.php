<?php

namespace App\Services\Admin;

use App\Enums\MemberStatus;
use App\Enums\OrderStatus;
use App\Enums\RfmSegment;
use App\Models\Member;
use App\Models\Order;
use App\Util\DateUtil;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * RFM分析（Recency / Frequency / Monetary）による会員セグメント分類サービス。
 */
class RfmAnalysisService
{
    private const RECENCY_ACTIVE_DAYS = 30;

    private const RECENCY_SLEEPING_DAYS = 90;

    private const FREQUENCY_HIGH = 5;

    private const MONETARY_HIGH = 10000;

    /**
     * プロジェクト全会員のRFMセグメント一覧を返す。
     *
     * @return Collection<int, array{member_id: int, member_number: string, name: string, recency_days: int|null, frequency: int, monetary: float, segment: string, segment_label: string}>
     */
    public function analyzeByProject(int $projectId): Collection
    {
        $members = Member::with('user:id,name')
            ->where('project_id', $projectId)
            ->where('status', MemberStatus::ACTIVE)
            ->get(['id', 'user_id', 'member_number']);

        if ($members->isEmpty()) {
            return collect();
        }

        $orderStats = $this->fetchOrderStats($projectId, $members->pluck('user_id')->filter()->all());

        return $members->map(function (Member $member) use ($orderStats): array {
            $stats = $orderStats[$member->user_id] ?? null;
            $recencyDays = $stats !== null ? (int) now()->diffInDays($stats['last_order_at'], true) : null;
            $frequency = $stats !== null ? (int) $stats['order_count'] : 0;
            $monetary = $stats !== null ? (float) $stats['total_amount'] : 0.0;
            $segment = $this->resolveSegment($recencyDays, $frequency, $monetary);

            return [
                'member_id' => (int) $member->id,
                'member_number' => (string) ($member->member_number ?? ''),
                'name' => (string) ($member->user !== null ? $member->user->name : ''),
                'recency_days' => $recencyDays,
                'frequency' => $frequency,
                'monetary' => $monetary,
                'segment' => $segment,
                'segment_label' => RfmSegment::getLabel($segment),
            ];
        });
    }

    /**
     * 指定RFMセグメントのいずれかに属する会員IDを返す。
     *
     * @param  list<string>  $segments
     * @return list<int>
     */
    public function findMemberIdsBySegments(int $projectId, array $segments): array
    {
        $wanted = array_values(array_intersect($segments, RfmSegment::keys()));
        if ($wanted === []) {
            return [];
        }

        return $this->analyzeByProject($projectId)
            ->filter(fn (array $row): bool => in_array($row['segment'], $wanted, true))
            ->pluck('member_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{label: string, color: string}>
     */
    public function segmentOptions(): array
    {
        $options = [];
        foreach (RfmSegment::keys() as $key) {
            $options[$key] = [
                'label' => RfmSegment::getLabel($key),
                'color' => $this->segmentColor($key),
            ];
        }

        return $options;
    }

    /**
     * セグメント別件数サマリーを返す。
     *
     * @param  Collection<int, array{segment: string, segment_label: string}>  $rows
     * @return array<string, array{label: string, count: int, color: string}>
     */
    public function buildSegmentSummary(Collection $rows): array
    {
        $summary = [];
        foreach ($rows as $row) {
            $seg = $row['segment'];
            if (! isset($summary[$seg])) {
                $summary[$seg] = ['label' => $row['segment_label'], 'count' => 0, 'color' => self::segmentColor($seg)];
            }
            $summary[$seg]['count']++;
        }

        return $summary;
    }

    /**
     * @param  int[]  $userIds
     * @return array<int, array{last_order_at: Carbon, order_count: int, total_amount: float}>
     */
    private function fetchOrderStats(int $projectId, array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = Order::query()
            ->select('user_id', DB::raw('MAX(created_at) as last_order_at'), DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_amount) as total_amount'))
            ->where('project_id', $projectId)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->user_id] = [
                'last_order_at' => DateUtil::nullableCarbon($row->last_order_at),
                'order_count' => (int) $row->order_count,
                'total_amount' => (float) $row->total_amount,
            ];
        }

        return $result;
    }

    private function resolveSegment(?int $recencyDays, int $frequency, float $monetary): string
    {
        if ($recencyDays === null) {
            return RfmSegment::NEVER;
        }
        if ($recencyDays <= self::RECENCY_ACTIVE_DAYS && $frequency >= self::FREQUENCY_HIGH && $monetary >= self::MONETARY_HIGH) {
            return RfmSegment::CHAMPION;
        }
        if ($recencyDays <= self::RECENCY_ACTIVE_DAYS && $frequency >= self::FREQUENCY_HIGH) {
            return RfmSegment::LOYAL;
        }
        if ($recencyDays <= self::RECENCY_ACTIVE_DAYS) {
            return RfmSegment::ACTIVE;
        }
        if ($recencyDays <= self::RECENCY_SLEEPING_DAYS) {
            return RfmSegment::AT_RISK;
        }

        return RfmSegment::DORMANT;
    }

    public function segmentLabel(string $segment): string
    {
        return RfmSegment::getLabel($segment);
    }

    public function segmentColor(string $segment): string
    {
        $map = [
            RfmSegment::CHAMPION => '#059669',
            RfmSegment::LOYAL => '#0891b2',
            RfmSegment::ACTIVE => '#16a34a',
            RfmSegment::AT_RISK => '#ca8a04',
            RfmSegment::DORMANT => '#dc2626',
            RfmSegment::NEVER => '#78716c',
        ];

        return $map[$segment] ?? '#6b7280';
    }
}
