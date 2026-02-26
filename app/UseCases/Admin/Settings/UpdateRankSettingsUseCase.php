<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Util\StrHelper;

class UpdateRankSettingsUseCase
{
    /** @param array{rank_basis: string, point_earn_rate: int, points_valid_days: int, ranks: array<int, array{key?: string, label?: string, min_points?: int, order?: int, is_active?: bool, icon_url?: string|null, stamp_background_url?: string|null}>} $payload 検証済みランク設定 */
    public function handle(int $projectId, array $payload): void
    {
        $rows = collect($payload['ranks'] ?? [])
            ->filter(fn ($row) => !StrHelper::isEmptyStringOrNull($row['key'] ?? null))
            ->map(function ($row, $index) {
                $isActive = $row['is_active'] ?? false;
                $color = $row['card_color'] ?? null;
                return [
                    'key' => (string) $row['key'],
                    'label' => (string) ($row['label'] ?? $row['key']),
                    'min_points' => (int) ($row['min_points'] ?? 0),
                    'is_active' => $isActive === true || $isActive === 1 || $isActive === '1',
                    'icon_url' => $row['icon_url'] ?? null,
                    'stamp_background_url' => $row['stamp_background_url'] ?? null,
                    'card_color' => $color !== null && $color !== '' ? (string) $color : null,
                    'order' => (int) ($row['order'] ?? ($index + 1)),
                ];
            })
            ->values()
            ->all();

        $keys = array_map(fn ($row) => $row['key'], $rows);
        if (count($keys) !== count(array_unique($keys))) {
            throw new \InvalidArgumentException('ランクキーが重複しています');
        }

        ShopSetting::setMemberRankSettings($rows, $projectId);
        ShopSetting::setValue('member_rank_basis', (string) $payload['rank_basis'], 'string', $projectId);
        ShopSetting::setValue('point_earn_rate', (int) $payload['point_earn_rate'], 'integer', $projectId);
        ShopSetting::setValue('points_valid_days', (int) $payload['points_valid_days'], 'integer', $projectId);
    }
}
