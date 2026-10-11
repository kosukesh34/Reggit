<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\RankSettings;
use App\Enums\RankAggregationType;
use App\Models\Project;
use App\Services\Smaregi\SmaregiRankSettingsRecoveryService;
use App\UseCases\Admin\Settings\Data\UpdateRankSettingsInput;
use App\Util\StrHelper;
use Illuminate\Support\Facades\DB;

class UpdateRankSettingsUseCase
{
    public function __construct(
        private RankSettings $shopSettingRepository,
        private SmaregiRankSettingsRecoveryService $recovery,
    ) {}

    public function handle(UpdateRankSettingsInput $input): void
    {
        $projectId = $input->projectId;
        $defaultPointEarnRate = (int) $input->pointEarnRate;
        $defaultStampEarnPerAmount = (int) $input->stampEarnPerAmount;
        $existingByKey = $this->existingRankMetaByKey($projectId);

        $rows = collect($input->ranks)
            ->filter(fn ($row) => ! StrHelper::isEmptyStringOrNull($row['key'] ?? null))
            ->map(function ($row, $index) use ($existingByKey, $defaultPointEarnRate, $defaultStampEarnPerAmount) {
                $isActive = $row['is_active'] ?? false;
                $color = $row['card_color'] ?? null;
                $key = (string) $row['key'];
                $pointEarnRate = $row['point_earn_rate'] ?? null;
                $pointEarnRate = ($pointEarnRate !== null && $pointEarnRate !== '')
                    ? (int) $pointEarnRate
                    : $defaultPointEarnRate;
                if ($pointEarnRate < 1) {
                    $pointEarnRate = $defaultPointEarnRate;
                }
                $stampEarnPerAmount = $row['stamp_earn_per_amount'] ?? null;
                $stampEarnPerAmount = ($stampEarnPerAmount !== null && $stampEarnPerAmount !== '')
                    ? (int) $stampEarnPerAmount
                    : $defaultStampEarnPerAmount;
                if ($stampEarnPerAmount < 0) {
                    $stampEarnPerAmount = $defaultStampEarnPerAmount;
                }
                $existing = $existingByKey[$key] ?? null;

                return [
                    'key' => $key,
                    'label' => (string) ($row['label'] ?? $row['key']),
                    'min_points' => (int) ($row['min_points'] ?? 0),
                    'is_active' => $isActive === true || $isActive === 1 || $isActive === '1',
                    'card_color' => $color !== null && $color !== '' ? (string) $color : null,
                    'order' => (int) ($row['order'] ?? ($index + 1)),
                    'point_earn_rate' => $pointEarnRate,
                    'stamp_earn_per_amount' => $stampEarnPerAmount,
                    'smaregi_rank_id' => is_array($existing) ? ($existing['smaregi_rank_id'] ?? null) : null,
                ];
            })
            ->values()
            ->all();

        $keys = array_map(fn ($row) => $row['key'], $rows);
        if (count($keys) !== count(array_unique($keys))) {
            throw new \InvalidArgumentException('ランクキーが重複しています');
        }

        DB::transaction(function () use ($input, $projectId, $rows, $defaultPointEarnRate): void {
            Project::whereKey($projectId)->lockForUpdate()->firstOrFail();
            $currentMeta = $this->existingRankMetaByKey($projectId);
            foreach ($rows as $index => $row) {
                $rows[$index]['smaregi_rank_id'] = $currentMeta[$row['key']]['smaregi_rank_id'] ?? null;
            }
            $this->shopSettingRepository->setMemberRankSettings($rows, $projectId);
            $this->shopSettingRepository->setValue('member_rank_basis', (string) $input->rankBasis, 'string', $projectId);
            $this->shopSettingRepository->setValue(
                'member_rank_aggregation_type',
                RankAggregationType::normalize($input->aggregationType),
                'string',
                $projectId,
            );
            $periodDays = (int) $input->periodDays;
            $this->shopSettingRepository->setValue('member_rank_period_days', $periodDays > 0 ? $periodDays : 365, 'integer', $projectId);
            $this->shopSettingRepository->setValue('is_rank_down_enabled', (bool) $input->rankDownEnabled, 'boolean', $projectId);
            $this->shopSettingRepository->setValue('point_earn_rate', $defaultPointEarnRate, 'integer', $projectId);
            $this->shopSettingRepository->setValue('points_valid_days', (int) $input->pointsValidDays, 'integer', $projectId);
            $this->shopSettingRepository->setValue('points_per_order', (int) $input->pointsPerOrder, 'integer', $projectId);
            $this->shopSettingRepository->setValue('stamp_earn_per_amount', (int) $input->stampEarnPerAmount, 'integer', $projectId);
            $this->recovery->record($projectId);
            DB::afterCommit(fn () => $this->recovery->retryPending($projectId));
        });
    }

    /**
     * @return array<string, array{smaregi_rank_id: string|null}>
     */
    private function existingRankMetaByKey(int $projectId): array
    {
        $meta = [];
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            $key = isset($rank['key']) ? (string) $rank['key'] : '';
            if ($key === '') {
                continue;
            }
            $smaregiRankId = isset($rank['smaregi_rank_id']) ? trim((string) $rank['smaregi_rank_id']) : '';
            $meta[$key] = [
                'smaregi_rank_id' => $smaregiRankId !== '' ? $smaregiRankId : null,
            ];
        }

        return $meta;
    }
}
