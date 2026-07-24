<?php

namespace App\Services\Admin;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\MemberLotteryEntitlement;
use App\Models\Settings\ProjectLotterySetting;
use App\Services\Admin\Data\LotterySettingsData;
use App\Services\Admin\Data\LotteryStatsData;
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
            $this->getStats($projectId),
        );
    }

    public function getStats(int $projectId): LotteryStatsData
    {
        $base = MemberLotteryEntitlement::query()->where('project_id', $projectId);

        $grantedTotal = (clone $base)->count();
        $pendingCount = (clone $base)->where('is_done', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
        $playedCount = (clone $base)->where('is_done', true)->count();
        $winCount = (clone $base)->where('is_done', true)->whereNotNull('won_coupon_id')->count();
        $loseCount = (clone $base)->where('is_done', true)->whereNull('won_coupon_id')->count();
        $todayPlayedCount = (clone $base)->where('is_done', true)
            ->whereDate('done_at', today())
            ->count();
        $todayWinCount = (clone $base)->where('is_done', true)
            ->whereNotNull('won_coupon_id')
            ->whereDate('done_at', today())
            ->count();

        $typeRows = MemberLotteryEntitlement::query()
            ->where('project_id', $projectId)
            ->selectRaw('reward_type')
            ->selectRaw('COUNT(*) as granted')
            ->selectRaw('SUM(CASE WHEN is_done = 0 AND (expires_at IS NULL OR expires_at > ?) THEN 1 ELSE 0 END) as pending', [now()])
            ->selectRaw('SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) as played')
            ->selectRaw('SUM(CASE WHEN is_done = 1 AND won_coupon_id IS NOT NULL THEN 1 ELSE 0 END) as win')
            ->selectRaw('SUM(CASE WHEN is_done = 1 AND won_coupon_id IS NULL THEN 1 ELSE 0 END) as lose')
            ->groupBy('reward_type')
            ->get()
            ->keyBy('reward_type');

        $byRewardType = [];
        foreach (LotteryRewardType::keys() as $rewardType) {
            $row = $typeRows->get($rewardType);
            $byRewardType[] = [
                'reward_type' => $rewardType,
                'reward_type_label' => LotteryRewardType::getValues()[$rewardType] ?? $rewardType,
                'granted' => (int) ($row->granted ?? 0),
                'pending' => (int) ($row->pending ?? 0),
                'played' => (int) ($row->played ?? 0),
                'win' => (int) ($row->win ?? 0),
                'lose' => (int) ($row->lose ?? 0),
            ];
        }

        $couponNames = Coupon::query()
            ->where('project_id', $projectId)
            ->pluck('name', 'id');

        $winByCoupon = MemberLotteryEntitlement::query()
            ->where('project_id', $projectId)
            ->where('is_done', true)
            ->selectRaw('reward_type, won_coupon_id, COUNT(*) as cnt')
            ->groupBy('reward_type', 'won_coupon_id')
            ->orderByDesc('cnt')
            ->get()
            ->map(function ($row) use ($couponNames) {
                $couponId = $row->won_coupon_id !== null ? (int) $row->won_coupon_id : null;
                $isLose = $couponId === null;
                $rewardType = (string) $row->reward_type;

                return [
                    'coupon_id' => $couponId,
                    'coupon_name' => $isLose
                        ? 'ハズレ'
                        : (string) ($couponNames[$couponId] ?? ('クーポン#'.$couponId)),
                    'is_lose' => $isLose,
                    'reward_type' => $rewardType,
                    'reward_type_label' => LotteryRewardType::getValues()[$rewardType] ?? $rewardType,
                    'count' => (int) $row->cnt,
                ];
            })
            ->values()
            ->all();

        return new LotteryStatsData(
            $grantedTotal,
            $pendingCount,
            $playedCount,
            $winCount,
            $loseCount,
            $todayPlayedCount,
            $todayWinCount,
            $byRewardType,
            $winByCoupon,
        );
    }

    /**
     * @param  array{
     *     gacha_enabled: bool,
     *     scratch_enabled: bool,
     *     grant_trigger: string,
     *     min_amount: float|null,
     *     entitlement_expires_hours: int|null,
     *     prizes: list<array{reward_type: string, coupon_id: int|null, is_lose: bool, probability: int, sort_order?: int}>
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
                $isLose = ($prize['is_lose'] ?? false) === true;
                LotteryPrize::query()->create([
                    'project_id' => $projectId,
                    'reward_type' => $prize['reward_type'],
                    'coupon_id' => $isLose ? null : (int) $prize['coupon_id'],
                    'is_lose' => $isLose,
                    'probability' => (int) $prize['probability'],
                    'sort_order' => (int) ($prize['sort_order'] ?? $index),
                    'is_active' => true,
                ]);
            }
        });
    }

    /**
     * @param  list<array{reward_type: string, coupon_id: int|null, is_lose?: bool, probability: int}>  $prizes
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
