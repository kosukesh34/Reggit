<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Domain\Settings\Repositories\ProfileSettings;
use App\Enums\RfmSegment;
use App\Enums\SegmentCombineMode;
use App\Models\Coupon;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use App\Models\StampCard;
use App\Support\MemberChannel;
use Illuminate\Database\Eloquent\Model;

/**
 * 保存済みの segment_criteria を「誕生月：1月、2月」のような表示用の文にする。
 * 並び順と「かつ／または」は MemberSegmentService の絞り込みと揃える。
 */
class MemberSegmentCriteriaDescriber
{
    private const DISPLAY_ORDER = ['groups', 'channel', 'birthday', 'sex', 'rfm', 'tags', 'visits', 'rank', 'points', 'last_visit', 'product', 'held_coupon', 'stamp', 'sales'];

    public function __construct(
        private MemberCardPreviewSettings $memberCardSettings,
        private ProfileSettings $profileSettings,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<string> 2件目以降は先頭に「かつ」「または」が付く。まとまりが複数あれば、まとまりごとに1行
     */
    public function describe(int $projectId, array $criteria): array
    {
        $blocks = array_values(array_filter((array) ($criteria['blocks'] ?? []), 'is_array'));
        if ($blocks === []) {
            return $this->describeBlock($projectId, $criteria);
        }

        $described = [];
        foreach ([$criteria, ...$blocks] as $block) {
            $lines = $this->describeBlock($projectId, $block);
            if ($lines !== []) {
                $text = count($lines) > 1 ? '（'.implode(' ', $lines).'）' : $lines[0];
                $described[] = ($described === [] ? '' : 'かつ ').$text;
            }
        }

        return $described;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return list<string>
     */
    private function describeBlock(int $projectId, array $criteria): array
    {
        $lines = [];
        foreach ([$criteria, ...array_filter((array) ($criteria['more'] ?? []), 'is_array')] as $part) {
            foreach ($this->conditionLines($projectId, $part) as $key => $line) {
                $lines[] = [$key, $line];
            }
        }
        $position = static fn (string $key): int => ($index = array_search($key, self::DISPLAY_ORDER, true)) === false ? count(self::DISPLAY_ORDER) : $index;
        usort($lines, static fn (array $left, array $right): int => $position($left[0]) <=> $position($right[0]));

        $operators = is_array($criteria['operators'] ?? null) ? $criteria['operators'] : [];
        $described = [];
        foreach ($lines as [$key, $line]) {
            if ($described !== []) {
                $operator = match ($criteria['match_mode'] ?? null) {
                    SegmentCombineMode::ANY => 'or',
                    SegmentCombineMode::ALL => 'and',
                    default => $operators[$key] ?? 'and',
                };
                $line = ($operator === 'or' ? 'または ' : 'かつ ').$line;
            }
            $described[] = $line;
        }

        return $described;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, string>
     */
    private function conditionLines(int $projectId, array $criteria): array
    {
        $lines = [];

        if (is_array($criteria['audience_group_ids'] ?? null)) {
            $lines['groups'] = '保存したグループ：'.$this->anyOf($this->names(MemberAudienceGroup::class, $projectId, $criteria['audience_group_ids']));
        }
        if (is_string($criteria['channel'] ?? null)) {
            $lines['channel'] = '登録チャネル：'.(MemberChannel::filterOptions()[$criteria['channel']] ?? $criteria['channel']);
        }
        $months = is_array($criteria['birthday_months'] ?? null) ? $criteria['birthday_months'] : (isset($criteria['birthday_month']) ? [$criteria['birthday_month']] : []);
        if ($months !== []) {
            $lines['birthday'] = '誕生月：'.$this->anyOf(array_map(static fn ($month): string => (int) $month.'月', $months));
        }
        if (is_string($criteria['sex'] ?? null)) {
            $lines['sex'] = '性別：'.($criteria['sex'] === '1' ? '男性' : '女性');
        }
        if (is_array($criteria['rfm_segments'] ?? null)) {
            $lines['rfm'] = 'RFMセグメント：'.$this->anyOf(array_map(static fn ($key): string => RfmSegment::getLabel((string) $key), $criteria['rfm_segments']));
        }
        if (is_array($criteria['tag_ids'] ?? null)) {
            $tags = $this->names(MemberTag::class, $projectId, $criteria['tag_ids']);
            $lines['tags'] = '会員タグ：'.(($criteria['tag_ids_mode'] ?? null) === SegmentCombineMode::ALL ? $this->allOf($tags) : $this->anyOf($tags));
        }
        if (($range = $this->range($criteria['visit_count_min'] ?? null, $criteria['visit_count_max'] ?? null, '回')) !== null) {
            $lines['visits'] = '来店回数：'.$range;
        }
        if (is_string($criteria['rank'] ?? null)) {
            $lines['rank'] = '会員ランク：'.$this->rankLabel($projectId, $criteria['rank']);
        }
        if (($range = $this->range($criteria['lifetime_points_min'] ?? null, $criteria['lifetime_points_max'] ?? null, 'pt')) !== null) {
            $lines['points'] = '累計付与ポイント：'.$range;
        }
        if (isset($criteria['last_visit_mode'], $criteria['last_visit_days'])) {
            $days = number_format((int) $criteria['last_visit_days']);
            $lines['last_visit'] = '最終来店：'.match ($criteria['last_visit_mode']) {
                MemberSegmentService::LAST_VISIT_INACTIVE => "{$days}日以上来店なし",
                MemberSegmentService::LAST_VISIT_WITHIN => "{$days}日以内に来店あり",
                default => "ちょうど{$days}日後",
            };
        }
        if (isset($criteria['product_id'])) {
            $lines['product'] = '購入商品：'.implode('', $this->names(Product::class, $projectId, [$criteria['product_id']]));
        }
        if (isset($criteria['held_coupon_id'], $criteria['held_coupon_status'])) {
            $lines['held_coupon'] = '保有クーポン：'.implode('', $this->names(Coupon::class, $projectId, [$criteria['held_coupon_id']]))
                .'（'.match ($criteria['held_coupon_status']) {
                    'used' => '使用済み',
                    'expired' => '期限切れ',
                    'not_issued' => '未配布',
                    default => '未使用',
                }.'）';
        }
        $stamp = array_values(array_filter([
            isset($criteria['stamp_card_id']) ? implode('', $this->names(StampCard::class, $projectId, [$criteria['stamp_card_id']])) : null,
            isset($criteria['stamp_count_min']) ? '累計'.number_format((int) $criteria['stamp_count_min']).'個以上' : null,
            ($criteria['stamp_completed'] ?? false) === true ? '達成済み' : null,
        ]));
        if ($stamp !== []) {
            $lines['stamp'] = 'スタンプ：'.implode('・', $stamp);
        }
        $periodDays = (int) ($criteria['period_days'] ?? 0);
        if ($periodDays > 0 && ($criteria['no_orders'] ?? false) === true) {
            $lines['sales'] = "購入履歴（直近{$periodDays}日間）：なし";
        } elseif ($periodDays > 0 && isset($criteria['min_total_amount'])) {
            $lines['sales'] = "購入額（直近{$periodDays}日間）：".number_format((float) $criteria['min_total_amount']).'円以上';
        }

        $profileLabels = collect($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? [])
            ->filter(fn ($field): bool => is_array($field) && ($field['key'] ?? '') !== '')
            ->mapWithKeys(fn (array $field): array => [(string) $field['key'] => (string) ($field['label'] ?? $field['key'])]);
        $profileModes = is_array($criteria['profile_modes'] ?? null) ? $criteria['profile_modes'] : [];
        foreach ((array) ($criteria['profile'] ?? []) as $key => $values) {
            if (is_array($values)) {
                $values = array_map('strval', $values);
                $lines['profile_'.$key] = ($profileLabels[$key] ?? $key).'：'
                    .(($profileModes[$key] ?? null) === SegmentCombineMode::ALL ? $this->allOf($values) : $this->anyOf($values));
            }
        }

        return $lines;
    }

    /**
     * 選んだ後に消された項目は「削除済み」と出す。
     *
     * @param  class-string<Model>  $model
     * @param  array<mixed>  $ids
     * @return list<string>
     */
    private function names(string $model, int $projectId, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $names = $model::query()->where('project_id', $projectId)->whereIn('id', $ids)->pluck('name', 'id');

        return array_map(static fn (int $id): string => (string) ($names[$id] ?? '（削除済み）'), $ids);
    }

    private function rankLabel(int $projectId, string $rank): string
    {
        foreach ($this->memberCardSettings->getMemberRankSettings($projectId) as $setting) {
            if (trim((string) ($setting['key'] ?? '')) === $rank) {
                return (string) ($setting['label'] ?? $rank);
            }
        }

        return $rank;
    }

    private function range(mixed $min, mixed $max, string $unit): ?string
    {
        if ($min !== null && $max !== null) {
            return number_format((int) $min).'〜'.number_format((int) $max).$unit;
        }
        if ($min !== null) {
            return number_format((int) $min).$unit.'以上';
        }

        return $max !== null ? number_format((int) $max).$unit.'以下' : null;
    }

    /** @param  list<string>  $values */
    private function anyOf(array $values): string
    {
        return implode('、', $values).(count($values) > 1 ? '（いずれか）' : '');
    }

    /** @param  list<string>  $values */
    private function allOf(array $values): string
    {
        return implode('、', $values).(count($values) > 1 ? '（すべて）' : '');
    }
}
