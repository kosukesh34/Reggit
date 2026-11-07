<?php

namespace App\UseCases\Admin\Coupon;

use App\Domain\Settings\Repositories\RankSettings;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Services\Admin\CouponDeliveryDisplay;
use App\UseCases\Admin\Coupon\Data\AdminCouponDeliveryDetailOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminCouponDeliveryDetailUseCase
{
    public function __construct(
        private RankSettings $shopSettingRepository,
        private CouponDeliveryDisplay $display,
    ) {}

    public function handle(int $projectId, int $deliveryId): AdminCouponDeliveryDetailOutput
    {
        $delivery = CouponDelivery::query()
            ->where('project_id', $projectId)
            ->with('coupon')
            ->find($deliveryId);
        if ($delivery === null) {
            throw (new ModelNotFoundException)->setModel(CouponDelivery::class, [$deliveryId]);
        }

        $typeOptions = CouponDeliveryType::getValues();
        $statusLabels = CouponDeliveryStatus::getValues();
        $status = (string) $delivery->status;
        $isEvent = CouponDeliveryType::isEventRankUpArrival((string) $delivery->delivery_type);

        $targetIds = $this->normalizeIds((array) ($delivery->target_member_ids ?? []));
        $issuedIds = $this->normalizeIds((array) ($delivery->issued_member_ids ?? []));
        $issuedSet = array_fill_keys($issuedIds, true);

        $memberIds = $isEvent
            ? $issuedIds
            : array_values(array_unique(array_merge($targetIds, $issuedIds)));
        $labels = $this->resolveMemberRows($projectId, $memberIds);

        $members = [];
        foreach ($memberIds as $id) {
            $isIssued = isset($issuedSet[$id]);
            $row = $labels[$id] ?? [
                'member_id' => $id,
                'member_name' => 'ID:'.$id,
                'member_number' => '—',
            ];
            $members[] = [
                'member_id' => $row['member_id'],
                'member_name' => $row['member_name'],
                'member_number' => $row['member_number'],
                'is_issued' => $isIssued,
                'status_label' => $isIssued ? '発行済み' : '未発行',
            ];
        }

        $targetSummary = '配信対象の会員一覧';
        if ($isEvent) {
            $rankKey = trim((string) (($delivery->segment_criteria['rank'] ?? '')));
            $rankLabel = $this->resolveRankLabel($projectId, $rankKey);
            $targetSummary = $rankLabel !== ''
                ? "到達ランク「{$rankLabel}」に昇格した会員へ自動発行"
                : '到達ランクに昇格した会員へ自動発行';
        } elseif (CouponDeliveryType::isAllMembersDelivery((string) $delivery->delivery_type)) {
            $targetSummary = '登録会員全員';
        }

        return new AdminCouponDeliveryDetailOutput(
            (string) ($delivery->coupon?->name ?: $delivery->coupon?->code ?: '—'),
            (string) ($delivery->coupon?->code ?: '—'),
            $typeOptions[$delivery->delivery_type] ?? (string) $delivery->delivery_type,
            $statusLabels[$status] ?? $status,
            $this->display->statusBadgeClass($status),
            $this->display->timingLabel($delivery),
            number_format((int) $delivery->issued_count).'人',
            $targetSummary,
            $members,
            $isEvent,
        );
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $raw) {
            $id = (int) $raw;
            if ($id > 0) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, array{member_id: int, member_name: string, member_number: string}>
     */
    private function resolveMemberRows(int $projectId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = [];
        foreach (
            Member::query()
                ->where('project_id', $projectId)
                ->whereIn('id', $ids)
                ->with('user')
                ->get() as $member
        ) {
            $user = $member->user;
            $rows[(int) $member->id] = [
                'member_id' => (int) $member->id,
                'member_name' => $user !== null && trim((string) $user->name) !== ''
                    ? (string) $user->name
                    : '—',
                'member_number' => trim((string) $member->member_number) !== ''
                    ? (string) $member->member_number
                    : '—',
            ];
        }

        return $rows;
    }

    private function resolveRankLabel(int $projectId, string $rankKey): string
    {
        if ($rankKey === '') {
            return '';
        }
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') === $rankKey) {
                return (string) ($rank['label'] ?? $rankKey);
            }
        }

        return $rankKey;
    }
}
