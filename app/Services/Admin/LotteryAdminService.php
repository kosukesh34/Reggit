<?php

namespace App\Services\Admin;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Models\LotteryPrize;
use App\Models\Settings\ProjectLotterySetting;
use App\Services\Admin\Data\LotterySettingsData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotteryAdminService
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    public function getSettings(int $projectId): LotterySettingsData
    {
        $settings = ProjectLotterySetting::query()->firstOrCreate(
            ['project_id' => $projectId],
            ProjectLotterySetting::getDefaultAttributes()
        );

        $prizes = LotteryPrize::query()
            ->where('project_id', $projectId)
            ->orderBy('reward_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return new LotterySettingsData(
            $settings,
            $prizes,
            $this->memberAdminService->getActiveCouponsForSelect($projectId),
        );
    }

    /**
     * @param  array{
     *     gacha_enabled: bool,
     *     scratch_enabled: bool,
     *     grant_trigger: string,
     *     min_amount: float|null,
     *     entitlement_expires_hours: int|null,
     *     prizes: list<array{reward_type: string, coupon_id: int, probability: int, sort_order?: int}>
     * }  $data
     */
    public function updateSettings(int $projectId, array $data): void
    {
        $this->assertPrizeProbabilities($data['prizes'], (bool) $data['gacha_enabled'], (bool) $data['scratch_enabled']);

        DB::transaction(function () use ($projectId, $data): void {
            $settings = ProjectLotterySetting::query()->firstOrCreate(
                ['project_id' => $projectId],
                ProjectLotterySetting::getDefaultAttributes()
            );
            $settings->fill([
                'gacha_enabled' => (bool) $data['gacha_enabled'],
                'scratch_enabled' => (bool) $data['scratch_enabled'],
                'grant_trigger' => $data['grant_trigger'],
                'min_amount' => $data['grant_trigger'] === LotteryGrantTrigger::MIN_AMOUNT
                    ? $data['min_amount']
                    : null,
                'entitlement_expires_hours' => $data['entitlement_expires_hours'],
            ]);
            $settings->save();

            LotteryPrize::query()->where('project_id', $projectId)->delete();

            foreach ($data['prizes'] as $index => $prize) {
                LotteryPrize::query()->create([
                    'project_id' => $projectId,
                    'reward_type' => $prize['reward_type'],
                    'coupon_id' => (int) $prize['coupon_id'],
                    'probability' => (int) $prize['probability'],
                    'sort_order' => (int) ($prize['sort_order'] ?? $index),
                    'is_active' => true,
                ]);
            }
        });
    }

    /**
     * @param  list<array{reward_type: string, coupon_id: int, probability: int}>  $prizes
     */
    private function assertPrizeProbabilities(array $prizes, bool $gachaEnabled, bool $scratchEnabled): void
    {
        $sums = [
            LotteryRewardType::GACHA => 0,
            LotteryRewardType::SCRATCH => 0,
        ];
        $counts = [
            LotteryRewardType::GACHA => 0,
            LotteryRewardType::SCRATCH => 0,
        ];

        foreach ($prizes as $prize) {
            $type = $prize['reward_type'];
            if (! in_array($type, LotteryRewardType::keys(), true)) {
                throw ValidationException::withMessages([
                    'prizes' => '景品の種別が不正です。',
                ]);
            }
            $sums[$type] += (int) $prize['probability'];
            $counts[$type]++;
        }

        if ($gachaEnabled) {
            if ($counts[LotteryRewardType::GACHA] < 1) {
                throw ValidationException::withMessages([
                    'prizes' => 'ガチャを有効にする場合は景品を1件以上設定してください。',
                ]);
            }
            if ($sums[LotteryRewardType::GACHA] !== 100) {
                throw ValidationException::withMessages([
                    'prizes' => 'ガチャ景品の確率合計は100にしてください（現在: '.$sums[LotteryRewardType::GACHA].'）。',
                ]);
            }
        } elseif ($counts[LotteryRewardType::GACHA] > 0 && $sums[LotteryRewardType::GACHA] !== 100) {
            throw ValidationException::withMessages([
                'prizes' => 'ガチャ景品の確率合計は100にしてください（現在: '.$sums[LotteryRewardType::GACHA].'）。',
            ]);
        }

        if ($scratchEnabled) {
            if ($counts[LotteryRewardType::SCRATCH] < 1) {
                throw ValidationException::withMessages([
                    'prizes' => 'スクラッチを有効にする場合は景品を1件以上設定してください。',
                ]);
            }
            if ($sums[LotteryRewardType::SCRATCH] !== 100) {
                throw ValidationException::withMessages([
                    'prizes' => 'スクラッチ景品の確率合計は100にしてください（現在: '.$sums[LotteryRewardType::SCRATCH].'）。',
                ]);
            }
        } elseif ($counts[LotteryRewardType::SCRATCH] > 0 && $sums[LotteryRewardType::SCRATCH] !== 100) {
            throw ValidationException::withMessages([
                'prizes' => 'スクラッチ景品の確率合計は100にしてください（現在: '.$sums[LotteryRewardType::SCRATCH].'）。',
            ]);
        }
    }
}
