<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\RankAggregationType;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerRankSyncService;
use App\Util\StrHelper;

class UpdateRankSettingsUseCase
{
    public function __construct(
        private SmaregiCustomerRankSyncService $smaregiRankSync,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /**
     * @param  array{
     *     rank_basis: string,
     *     rank_aggregation_type?: string,
     *     rank_period_days?: int,
     *     is_rank_down_enabled?: bool,
     *     use_stamps?: bool,
     *     use_transaction_stamps?: bool,
     *     use_points?: bool,
     *     point_earn_rate: int,
     *     points_valid_days: int,
     *     ranks: array<int, array{
     *         key?: string,
     *         label?: string,
     *         min_points?: int,
     *         order?: int,
     *         is_active?: bool,
     *         icon_url?: string|null,
     *         stamp_background_url?: string|null,
     *         point_earn_rate?: int|string|null,
     *         stamp_earn_per_amount?: int|string|null,
     *         card_color?: string|null
     *     }>
     * }  $payload 検証済みランク設定
     */
    public function handle(int $projectId, array $payload): void
    {
        $defaultPointEarnRate = (int) $payload['point_earn_rate'];
        $existingSmaregiIds = $this->existingSmaregiRankIdsByKey($projectId);

        $rows = collect($payload['ranks'] ?? [])
            ->filter(fn ($row) => ! StrHelper::isEmptyStringOrNull($row['key'] ?? null))
            ->map(function ($row, $index) use ($existingSmaregiIds) {
                $isActive = $row['is_active'] ?? false;
                $color = $row['card_color'] ?? null;
                $key = (string) $row['key'];
                $pointEarnRate = $row['point_earn_rate'] ?? null;
                $pointEarnRate = ($pointEarnRate !== null && $pointEarnRate !== '')
                    ? (int) $pointEarnRate
                    : null;
                if ($pointEarnRate !== null && $pointEarnRate < 1) {
                    $pointEarnRate = null;
                }
                $stampEarnPerAmount = $row['stamp_earn_per_amount'] ?? null;
                $stampEarnPerAmount = ($stampEarnPerAmount !== null && $stampEarnPerAmount !== '')
                    ? (int) $stampEarnPerAmount
                    : null;
                if ($stampEarnPerAmount !== null && $stampEarnPerAmount < 0) {
                    $stampEarnPerAmount = null;
                }

                return [
                    'key' => $key,
                    'label' => (string) ($row['label'] ?? $row['key']),
                    'min_points' => (int) ($row['min_points'] ?? 0),
                    'is_active' => $isActive === true || $isActive === 1 || $isActive === '1',
                    'icon_url' => $row['icon_url'] ?? null,
                    'stamp_background_url' => $row['stamp_background_url'] ?? null,
                    'card_color' => $color !== null && $color !== '' ? (string) $color : null,
                    'order' => (int) ($row['order'] ?? ($index + 1)),
                    'point_earn_rate' => $pointEarnRate,
                    'stamp_earn_per_amount' => $stampEarnPerAmount,
                    'smaregi_rank_id' => $existingSmaregiIds[$key] ?? null,
                ];
            })
            ->values()
            ->all();

        $keys = array_map(fn ($row) => $row['key'], $rows);
        if (count($keys) !== count(array_unique($keys))) {
            throw new \InvalidArgumentException('ランクキーが重複しています');
        }

        if ($this->smaregiRankSync->hasSmaregiContract($projectId) && $rows !== []) {
            $rowsForSync = array_map(function (array $row) use ($defaultPointEarnRate): array {
                $effectiveRate = $row['point_earn_rate'] ?? $defaultPointEarnRate;

                return array_merge($row, [
                    'point_earn_rate' => max(1, (int) $effectiveRate),
                ]);
            }, $rows);
            $rankIdsByKey = $this->smaregiRankSync->sync($projectId, $rowsForSync);
            foreach ($rows as $index => $row) {
                $key = $row['key'];
                if (isset($rankIdsByKey[$key]) && $rankIdsByKey[$key] !== '') {
                    $rows[$index]['smaregi_rank_id'] = $rankIdsByKey[$key];
                }
            }
        }

        $this->shopSettingRepository->setMemberRankSettings($rows, $projectId);
        $this->shopSettingRepository->setValue('member_rank_basis', (string) $payload['rank_basis'], 'string', $projectId);
        $this->saveLoyaltyToggles($projectId, $payload);
        $this->shopSettingRepository->setValue(
            'member_rank_aggregation_type',
            RankAggregationType::normalize((string) ($payload['rank_aggregation_type'] ?? RankAggregationType::TOTAL)),
            'string',
            $projectId,
        );
        $periodDays = (int) ($payload['rank_period_days'] ?? 365);
        $this->shopSettingRepository->setValue('member_rank_period_days', $periodDays > 0 ? $periodDays : 365, 'integer', $projectId);
        $this->shopSettingRepository->setValue('is_rank_down_enabled', (bool) ($payload['is_rank_down_enabled'] ?? false), 'boolean', $projectId);
        $this->shopSettingRepository->setValue('point_earn_rate', $defaultPointEarnRate, 'integer', $projectId);
        $this->shopSettingRepository->setValue('points_valid_days', (int) $payload['points_valid_days'], 'integer', $projectId);
        $this->shopSettingRepository->setValue('points_per_order', (int) ($payload['points_per_order'] ?? 0), 'integer', $projectId);
        $this->shopSettingRepository->setValue('stamp_earn_per_amount', (int) ($payload['stamp_earn_per_amount'] ?? 0), 'integer', $projectId);

        if (isset($payload['member_code_type'])) {
            $this->shopSettingRepository->setValue('member_code_type', (string) $payload['member_code_type'], 'string', $projectId);
        }
        if (isset($payload['member_code_format'])) {
            $this->shopSettingRepository->setValue('member_code_format', (string) $payload['member_code_format'], 'string', $projectId);
        }
        if (isset($payload['member_code_length'])) {
            $this->shopSettingRepository->setValue('member_code_length', (int) $payload['member_code_length'], 'integer', $projectId);
        }
        if (array_key_exists('member_code_prefix', $payload)) {
            $this->shopSettingRepository->setValue('member_code_prefix', (string) ($payload['member_code_prefix'] ?? ''), 'string', $projectId);
        }
        if (array_key_exists('show_points_display', $payload)) {
            $this->shopSettingRepository->setValue('show_points_display', (bool) $payload['show_points_display'], 'boolean', $projectId);
        }
        if (array_key_exists('show_rank_progress', $payload)) {
            $this->shopSettingRepository->setValue('show_rank_progress', (bool) $payload['show_rank_progress'], 'boolean', $projectId);
        }
        if (array_key_exists('show_member_card_for_guest', $payload)) {
            $this->shopSettingRepository->setShowMemberCardForGuest((bool) $payload['show_member_card_for_guest'], $projectId);
        }
        if (array_key_exists('member_card_design', $payload) && in_array($payload['member_card_design'] ?? '', ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'], true)) {
            $this->shopSettingRepository->setMemberCardDesign((string) $payload['member_card_design'], $projectId);
        }
        if (array_key_exists('member_card_display_type', $payload) && in_array($payload['member_card_display_type'] ?? '', ['card', 'sheet'], true)) {
            $this->shopSettingRepository->setMemberCardDisplayType((string) $payload['member_card_display_type'], $projectId);
        }
        if (array_key_exists('member_card_code_type', $payload) && in_array($payload['member_card_code_type'] ?? '', ['barcode', 'qrcode'], true)) {
            $this->shopSettingRepository->setMemberCardCodeType((string) $payload['member_card_code_type'], $projectId);
        }

        if ($this->smaregiRankSync->hasSmaregiContract($projectId)) {
            $this->smaregiCustomerRankAssignmentService->syncPointRatesForProject($projectId);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveLoyaltyToggles(int $projectId, array $payload): void
    {
        $useStamps = (bool) ($payload['use_stamps'] ?? true);
        $useTransactionStamps = $useStamps && (bool) ($payload['use_transaction_stamps'] ?? true);
        $usePoints = (bool) ($payload['use_points'] ?? true);
        $this->shopSettingRepository->setValue('use_stamps', $useStamps, 'boolean', $projectId);
        $this->shopSettingRepository->setValue('use_transaction_stamps', $useTransactionStamps, 'boolean', $projectId);
        $this->shopSettingRepository->setValue('use_points', $usePoints, 'boolean', $projectId);

        $flags = $this->shopSettingRepository->getFeatureFlags($projectId);
        $flags['feature_stamps_enabled'] = $useStamps;
        $this->shopSettingRepository->setFeatureFlags($flags, $projectId);
    }

    /**
     * @return array<string, string>
     */
    private function existingSmaregiRankIdsByKey(int $projectId): array
    {
        $ids = [];
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            $key = isset($rank['key']) ? (string) $rank['key'] : '';
            $smaregiRankId = isset($rank['smaregi_rank_id']) ? trim((string) $rank['smaregi_rank_id']) : '';
            if ($key === '' || $smaregiRankId === '') {
                continue;
            }
            $ids[$key] = $smaregiRankId;
        }

        return $ids;
    }
}
