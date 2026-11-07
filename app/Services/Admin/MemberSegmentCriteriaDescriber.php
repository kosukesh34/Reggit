<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Domain\Settings\Repositories\ProfileSettings;
use App\Enums\MemberNotificationPreference;
use App\Enums\OrderType;
use App\Enums\RfmSegment;
use App\Enums\SegmentCombineMode;
use App\Models\AcquisitionSource;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use App\Models\StampCard;
use App\Models\Store;
use App\Models\Survey;
use App\Support\MemberChannel;
use Illuminate\Database\Eloquent\Model;

/**
 * 保存済みの segment_criteria を「誕生月：1月、2月」のような表示用の文にする。
 * 並び順と「かつ／または」は MemberSegmentService の絞り込みと揃える。
 */
class MemberSegmentCriteriaDescriber
{
    private const DISPLAY_ORDER = MemberSegmentService::DISPLAY_ORDER;

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
        if (isset($criteria['registered_from']) || isset($criteria['registered_to'])) {
            $from = isset($criteria['registered_from']) ? str_replace('-', '/', (string) $criteria['registered_from']) : '';
            $to = isset($criteria['registered_to']) ? str_replace('-', '/', (string) $criteria['registered_to']) : '';
            $lines['registered'] = '登録日：'.match (true) {
                $from !== '' && $to !== '' => "{$from}〜{$to}",
                $from !== '' => "{$from}以降",
                default => "{$to}以前",
            };
        }
        $months = is_array($criteria['birthday_months'] ?? null) ? $criteria['birthday_months'] : (isset($criteria['birthday_month']) ? [$criteria['birthday_month']] : []);
        if ($months !== []) {
            $lines['birthday'] = '誕生月：'.$this->anyOf(array_map(static fn ($month): string => (int) $month.'月', $months));
        }
        if (($range = $this->range($criteria['age_min'] ?? null, $criteria['age_max'] ?? null, '歳')) !== null) {
            $lines['age'] = '年齢：'.$range;
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
        if (($range = $this->range($criteria['points_min'] ?? null, $criteria['points_max'] ?? null, 'pt')) !== null) {
            $lines['point_balance'] = 'ポイント残高：'.$range;
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
        if (isset($criteria['read_announcement_id'], $criteria['read_announcement_status'])) {
            $title = Announcement::query()->where('project_id', $projectId)->whereKey((int) $criteria['read_announcement_id'])->value('title');
            $lines['announcement_read'] = 'お知らせ：'.($title === null ? '（削除済み）' : (string) $title)
                .'（'.($criteria['read_announcement_status'] === 'read' ? '既読' : '未読').'）';
        }
        if (is_array($criteria['store_ids'] ?? null)) {
            $lines['store'] = '利用店舗：'.$this->anyOf($this->names(Store::class, $projectId, $criteria['store_ids']));
        }
        if (is_array($criteria['acquisition_source_ids'] ?? null)) {
            $lines['acquisition'] = '登録経路：'.$this->anyOf($this->names(AcquisitionSource::class, $projectId, $criteria['acquisition_source_ids']));
        }
        if (isset($criteria['app_last_used_mode'], $criteria['app_last_used_days'])) {
            $days = number_format((int) $criteria['app_last_used_days']);
            $lines['app_last_used'] = 'アプリの利用：'.($criteria['app_last_used_mode'] === 'within_days' ? "{$days}日以内に利用あり" : "{$days}日以上利用なし");
        }
        if (isset($criteria['survey_id'], $criteria['survey_status'])) {
            $title = Survey::query()->where('project_id', $projectId)->whereKey((int) $criteria['survey_id'])->value('title');
            $lines['survey'] = 'アンケート：'.($title === null ? '（削除済み）' : (string) $title)
                .'（'.($criteria['survey_status'] === 'answered' ? '回答済み' : '未回答').'）';
        }
        if (isset($criteria['reservation_mode'], $criteria['reservation_days'])) {
            $days = number_format((int) $criteria['reservation_days']);
            $lines['reservation'] = "予約（直近{$days}日と今後）：".($criteria['reservation_mode'] === 'has' ? 'あり' : 'なし');
        }
        if (is_string($criteria['subscription_status'] ?? null)) {
            $lines['subscription'] = 'サブスク：'.match ($criteria['subscription_status']) {
                'active' => '継続中',
                'ended' => '解約済み',
                default => '加入したことがない',
            };
        }
        if (is_string($criteria['ticket_status'] ?? null)) {
            $lines['ticket'] = '回数券：'.($criteria['ticket_status'] === 'active' ? '使える回数券あり' : '使える回数券なし');
        }
        if (($range = $this->range($criteria['coupon_uses_min'] ?? null, $criteria['coupon_uses_max'] ?? null, '回')) !== null) {
            $lines['coupon_uses'] = 'クーポン利用回数：'.$range;
        }
        if (isset($criteria['rank_changed_days'])) {
            $lines['rank_changed'] = 'ランク変更：直近'.number_format((int) $criteria['rank_changed_days']).'日以内';
        }
        if (is_string($criteria['wallet_status'] ?? null)) {
            $lines['wallet'] = 'Apple Wallet：'.($criteria['wallet_status'] === 'registered' ? '会員証を追加済み' : '会員証を未追加');
        }
        if (isset($criteria['birthday_soon_mode'], $criteria['birthday_soon_days'])) {
            $days = (int) $criteria['birthday_soon_days'];
            $lines['birthday_soon'] = '誕生日：'.match (true) {
                $days === 0 => '今日',
                $criteria['birthday_soon_mode'] === 'exact' => "ちょうど{$days}日後",
                default => "今日から{$days}日以内",
            };
        }
        if (($range = $this->range($criteria['lifetime_sales_min'] ?? null, $criteria['lifetime_sales_max'] ?? null, '円')) !== null) {
            $lines['lifetime_sales'] = '累計購入額：'.$range;
        }
        if (is_array($criteria['order_types'] ?? null)) {
            $lines['order_type'] = '注文の種類：'.$this->anyOf(array_map(static fn ($type): string => OrderType::getLabel((string) $type), $criteria['order_types']));
        }
        if (isset($criteria['queue_days'])) {
            $lines['queue'] = '順番待ち：直近'.number_format((int) $criteria['queue_days']).'日以内に利用';
        }
        if (isset($criteria['points_expiring_days'])) {
            $lines['points_expiring'] = 'ポイント失効：'.$this->soonText((int) $criteria['points_expiring_days']).'に失効予定あり';
        }
        if (isset($criteria['coupon_expiring_days'])) {
            $lines['coupon_expiring'] = 'クーポン期限：'.$this->soonText((int) $criteria['coupon_expiring_days']).'に切れる未使用クーポンあり';
        }
        if (is_string($criteria['lottery_status'] ?? null)) {
            $lines['lottery'] = 'くじ：'.($criteria['lottery_status'] === 'pending' ? '引いていない抽選権あり' : '引いていない抽選権なし');
        }
        if (is_string($criteria['nps_category'] ?? null)) {
            $lines['nps'] = 'アンケートの評価：'.match ($criteria['nps_category']) {
                'promoter' => '高い（9〜10）',
                'passive' => '普通（7〜8）',
                default => '低い（0〜6）',
            };
        }
        if (isset($criteria['line_follow_days'])) {
            $lines['line_follow'] = 'LINE友だち追加：直近'.number_format((int) $criteria['line_follow_days']).'日以内';
        }
        if (is_string($criteria['notification_preference'] ?? null)) {
            $lines['notification'] = '通知設定：'.MemberNotificationPreference::getLabel($criteria['notification_preference']);
        }
        if (is_string($criteria['line_block_status'] ?? null)) {
            $lines['line_block'] = 'LINE：'.($criteria['line_block_status'] === 'blocked' ? 'ブロック中' : 'ブロックしていない');
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

    private function soonText(int $days): string
    {
        return $days === 0 ? '今日' : "今日から{$days}日以内";
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
