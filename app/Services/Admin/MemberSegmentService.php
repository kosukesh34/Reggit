<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\MemberNotificationPreference;
use App\Enums\OrderType;
use App\Enums\RfmSegment;
use App\Enums\SegmentCombineMode;
use App\Models\MemberAudienceGroup;
use App\Services\Member\MemberPointLedger;
use App\Support\MemberChannel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * 会員属性によるセグメント絞り込み。お知らせ・クーポン配布で共有する。
 */
class MemberSegmentService
{
    public const LAST_VISIT_INACTIVE = 'inactive_days';

    public const LAST_VISIT_WITHIN = 'visited_within_days';

    public const LAST_VISIT_DAYS_AFTER = 'days_after';

    /** 保有クーポンの使用状態。expired=未使用のまま期限切れ、not_issued=未配布 */
    public const HELD_COUPON_STATUSES = ['unused', 'used', 'expired', 'not_issued'];

    /** お知らせの既読状態。unread は配信先のうち未読 */
    public const ANNOUNCEMENT_READ_STATUSES = ['read', 'unread'];

    /** アプリ（端末）の最終利用 */
    public const APP_LAST_USED_MODES = ['within_days', 'inactive_days'];

    public const SURVEY_STATUSES = ['answered', 'unanswered'];

    public const RESERVATION_MODES = ['has', 'none'];

    public const SUBSCRIPTION_STATUSES = ['active', 'ended', 'none'];

    public const TICKET_STATUSES = ['active', 'none'];

    public const WALLET_STATUSES = ['registered', 'not_registered'];

    /** 「直近N日」で選べる日数 */
    public const PERIOD_DAYS = [7, 30, 60, 90, 180, 365];

    /** 「今日からN日後まで」で選べる日数（0 は今日） */
    public const SOON_DAYS = [0, 3, 7, 14, 30];

    public const BIRTHDAY_SOON_MODES = ['within', 'exact'];

    public const LOTTERY_STATUSES = ['pending', 'none'];

    /** promoter=9〜10、passive=7〜8、detractor=0〜6 */
    public const NPS_CATEGORIES = ['promoter', 'passive', 'detractor'];

    public const LINE_BLOCK_STATUSES = ['blocked', 'not_blocked'];

    /** 同じ種類の条件を重ねて追加する入力（segment_more[n][segment_*]）の上限 */
    public const MAX_EXTRA_CONDITIONS = 20;

    /** 1つ目以外の「条件のまとまり」（segment_blocks[k][segment_*]）の上限。まとまり同士は常にすべて一致 */
    public const MAX_EXTRA_BLOCKS = 10;

    /**
     * 条件を並べる順（「かつ／または」の結合順。MemberSegmentCriteriaDescriber の表示順も同じ）。
     * 保存済みの条件の結果を変えないよう、既存の groups〜sales の相対順は変えずに新しい条件を差し込む。
     */
    public const DISPLAY_ORDER = [
        'groups', 'channel', 'registered', 'acquisition', 'store', 'birthday', 'birthday_soon', 'age', 'sex', 'rfm', 'tags',
        'visits', 'rank', 'rank_changed', 'points', 'point_balance', 'points_expiring',
        'last_visit', 'reservation', 'queue', 'product', 'order_type',
        'held_coupon', 'coupon_expiring', 'coupon_uses', 'stamp', 'ticket', 'subscription', 'lottery',
        'sales', 'lifetime_sales',
        'announcement_read', 'survey', 'nps', 'app_last_used', 'wallet', 'line_follow', 'line_block', 'notification',
    ];

    /**
     * 配信フォーム（お知らせ・クーポン・スケジュール配信）で共通の、追加した条件の入力ルール。
     *
     * @return array<string, list<mixed>>
     */
    public static function inputRules(): array
    {
        $in = static fn (array $values): array => ['nullable', 'string', 'in:'.implode(',', $values)];
        $days = ['nullable', 'integer', 'in:'.implode(',', self::PERIOD_DAYS)];
        $soon = ['nullable', 'integer', 'in:'.implode(',', self::SOON_DAYS)];

        return [
            'segment_announcement_id' => ['nullable', 'integer', 'min:1'],
            'segment_announcement_status' => $in(self::ANNOUNCEMENT_READ_STATUSES),
            'segment_registered_from' => ['nullable', 'date_format:Y-m-d'],
            'segment_registered_to' => ['nullable', 'date_format:Y-m-d'],
            'segment_age_min' => ['nullable', 'integer', 'min:0', 'max:150'],
            'segment_age_max' => ['nullable', 'integer', 'min:0', 'max:150'],
            'segment_points_min' => ['nullable', 'integer', 'min:0'],
            'segment_points_max' => ['nullable', 'integer', 'min:0'],
            'segment_store_ids' => ['nullable', 'array'],
            'segment_store_ids.*' => ['integer', 'min:1', 'distinct'],
            'segment_acquisition_source_ids' => ['nullable', 'array'],
            'segment_acquisition_source_ids.*' => ['integer', 'min:1', 'distinct'],
            'segment_app_last_used_mode' => $in(self::APP_LAST_USED_MODES),
            'segment_app_last_used_days' => $days,
            'segment_survey_id' => ['nullable', 'integer', 'min:1'],
            'segment_survey_status' => $in(self::SURVEY_STATUSES),
            'segment_reservation_mode' => $in(self::RESERVATION_MODES),
            'segment_reservation_days' => $days,
            'segment_subscription_status' => $in(self::SUBSCRIPTION_STATUSES),
            'segment_ticket_status' => $in(self::TICKET_STATUSES),
            'segment_coupon_uses_min' => ['nullable', 'integer', 'min:0'],
            'segment_coupon_uses_max' => ['nullable', 'integer', 'min:0'],
            'segment_rank_changed_days' => $days,
            'segment_wallet_status' => $in(self::WALLET_STATUSES),
            'segment_birthday_soon_mode' => $in(self::BIRTHDAY_SOON_MODES),
            'segment_birthday_soon_days' => $soon,
            'segment_lifetime_sales_min' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_sales_max' => ['nullable', 'integer', 'min:0'],
            'segment_order_types' => ['nullable', 'array'],
            'segment_order_types.*' => ['string', 'in:'.implode(',', array_keys(OrderType::getValues())), 'distinct'],
            'segment_queue_days' => $days,
            'segment_points_expiring_days' => $soon,
            'segment_coupon_expiring_days' => $soon,
            'segment_lottery_status' => $in(self::LOTTERY_STATUSES),
            'segment_nps_category' => $in(self::NPS_CATEGORIES),
            'segment_line_follow_days' => $days,
            'segment_notification_preference' => $in(MemberNotificationPreference::keys()),
            'segment_line_block_status' => $in(self::LINE_BLOCK_STATUSES),
        ];
    }

    /**
     * 追加した範囲の条件で、下限が上限より大きいもの（そのままでは対象が0人になる）を項目ごとの文で返す。
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string> 入力名 => エラー文
     */
    public static function invertedRangeErrors(array $input): array
    {
        $ranges = [
            'segment_registered' => ['from', 'to', '登録日の終わりは始まり以降の日付にしてください'],
            'segment_age' => ['min', 'max', '年齢の上限は下限以上にしてください'],
            'segment_points' => ['min', 'max', 'ポイント残高の上限は下限以上にしてください'],
            'segment_coupon_uses' => ['min', 'max', 'クーポン利用回数の上限は下限以上にしてください'],
            'segment_lifetime_sales' => ['min', 'max', '累計購入額の上限は下限以上にしてください'],
        ];
        $errors = [];
        foreach ($ranges as $prefix => [$lowKey, $highKey, $message]) {
            $low = $input[$prefix.'_'.$lowKey] ?? null;
            $high = $input[$prefix.'_'.$highKey] ?? null;
            if (! is_scalar($low) || ! is_scalar($high) || (string) $low === '' || (string) $high === '') {
                continue;
            }
            // 日付（Y-m-d）は文字列のまま比べても前後が正しく出る
            $inverted = $lowKey === 'from' ? (string) $low > (string) $high : (int) $low > (int) $high;
            if ($inverted) {
                $errors[$prefix.'_'.$highKey] = $message;
            }
        }

        return $errors;
    }

    public function __construct(
        private CouponDeliveryTargetRepository $targetRepository,
        private RfmAnalysisService $rfmAnalysisService,
        private MemberPointLedger $pointLedger,
    ) {}

    /**
     * リクエスト/フォーム値から segment_criteria を組み立てる。有効な条件がなければ null。
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    public function buildCriteriaFromInput(array $input): ?array
    {
        // 上限を超えた分は切り捨てる。保存する入口では exceedsConditionLimits() で先に入力エラーにする
        $criteria = [];

        $matchMode = $input['segment_match_mode'] ?? null;
        if (in_array($matchMode, SegmentCombineMode::keys(), true)) {
            $criteria['match_mode'] = $matchMode;
        }

        $operators = collect((array) ($input['segment_operators'] ?? []))
            ->mapWithKeys(fn ($value, $key): array => [(string) $key => $value === 'or' ? 'or' : 'and'])
            ->all();
        if ($operators !== []) {
            $criteria['operators'] = $operators;
        }

        $channel = trim((string) ($input['segment_channel'] ?? $input['channel'] ?? ''));
        if (in_array($channel, [MemberChannel::LINE, MemberChannel::APP, MemberChannel::BOTH], true)) {
            $criteria['channel'] = $channel;
        }

        $birthdayMonths = $this->normalizeIntList(
            $input['segment_birthday_months'] ?? $input['birthday_months'] ?? [],
            1,
            12,
        );
        if ($birthdayMonths !== []) {
            $criteria['birthday_months'] = $birthdayMonths;
        } else {
            $birthdayMonth = $input['segment_birthday_month'] ?? $input['birthday_month'] ?? null;
            if ($birthdayMonth !== null && $birthdayMonth !== '') {
                $month = (int) $birthdayMonth;
                if ($month >= 1 && $month <= 12) {
                    $criteria['birthday_month'] = $month;
                }
            }
        }

        $tagIds = $this->normalizeIntList($input['segment_tag_ids'] ?? $input['tag_ids'] ?? []);
        if ($tagIds !== []) {
            $criteria['tag_ids'] = $tagIds;
            if ($this->listMode($input, 'tags') === SegmentCombineMode::ALL) {
                $criteria['tag_ids_mode'] = SegmentCombineMode::ALL;
            }
        }

        $visitMin = $input['segment_visit_count_min'] ?? $input['visit_count_min'] ?? null;
        $visitMax = $input['segment_visit_count_max'] ?? $input['visit_count_max'] ?? null;
        if ($visitMin !== null && $visitMin !== '') {
            $criteria['visit_count_min'] = max(0, (int) $visitMin);
        }
        if ($visitMax !== null && $visitMax !== '') {
            $criteria['visit_count_max'] = max(0, (int) $visitMax);
        }

        $pointsMin = $input['segment_lifetime_points_min'] ?? $input['lifetime_points_min'] ?? null;
        $pointsMax = $input['segment_lifetime_points_max'] ?? $input['lifetime_points_max'] ?? null;
        if ($pointsMin !== null && $pointsMin !== '') {
            $criteria['lifetime_points_min'] = max(0, (int) $pointsMin);
        }
        if ($pointsMax !== null && $pointsMax !== '') {
            $criteria['lifetime_points_max'] = max(0, (int) $pointsMax);
        }

        $lastVisitMode = trim((string) ($input['segment_last_visit_mode'] ?? $input['last_visit_mode'] ?? ''));
        $lastVisitDays = $input['segment_last_visit_days'] ?? $input['last_visit_days'] ?? null;
        if (in_array($lastVisitMode, [self::LAST_VISIT_INACTIVE, self::LAST_VISIT_WITHIN, self::LAST_VISIT_DAYS_AFTER], true)
            && $lastVisitDays !== null && $lastVisitDays !== '') {
            $criteria['last_visit_mode'] = $lastVisitMode;
            $criteria['last_visit_days'] = max(1, (int) $lastVisitDays);
        }

        $sex = trim((string) ($input['segment_sex'] ?? $input['sex'] ?? ''));
        if ($sex === '1' || $sex === '2') {
            $criteria['sex'] = $sex;
        }

        $rank = trim((string) ($input['segment_rank'] ?? $input['rank'] ?? $input['target_rank'] ?? ''));
        if ($rank !== '') {
            $criteria['rank'] = $rank;
        }

        $rfmSegments = $this->normalizeRfmSegments($input['segment_rfm_segments'] ?? $input['rfm_segments'] ?? []);
        if ($rfmSegments !== []) {
            $criteria['rfm_segments'] = $rfmSegments;
        }

        $productId = $input['segment_product_id'] ?? $input['product_id'] ?? null;
        if ($productId !== null && $productId !== '') {
            $parsedProductId = (int) $productId;
            if ($parsedProductId > 0) {
                $criteria['product_id'] = $parsedProductId;
            }
        }

        $heldCouponId = $input['segment_coupon_id'] ?? $input['held_coupon_id'] ?? null;
        $heldCouponStatus = trim((string) ($input['segment_coupon_status'] ?? $input['coupon_status'] ?? ''));
        if ($heldCouponId !== null && $heldCouponId !== '' && in_array($heldCouponStatus, self::HELD_COUPON_STATUSES, true)) {
            $parsedHeldCouponId = (int) $heldCouponId;
            if ($parsedHeldCouponId > 0) {
                $criteria['held_coupon_id'] = $parsedHeldCouponId;
                $criteria['held_coupon_status'] = $heldCouponStatus;
            }
        }

        $announcementId = (int) ($input['segment_announcement_id'] ?? 0);
        $announcementStatus = trim((string) ($input['segment_announcement_status'] ?? ''));
        if ($announcementId > 0 && in_array($announcementStatus, self::ANNOUNCEMENT_READ_STATUSES, true)) {
            $criteria['read_announcement_id'] = $announcementId;
            $criteria['read_announcement_status'] = $announcementStatus;
        }

        foreach (['from', 'to'] as $edge) {
            $date = trim((string) ($input['segment_registered_'.$edge] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
                $criteria['registered_'.$edge] = $date;
            }
        }

        $enumInputs = [
            'app_last_used_mode' => self::APP_LAST_USED_MODES,
            'survey_status' => self::SURVEY_STATUSES,
            'reservation_mode' => self::RESERVATION_MODES,
            'subscription_status' => self::SUBSCRIPTION_STATUSES,
            'ticket_status' => self::TICKET_STATUSES,
            'wallet_status' => self::WALLET_STATUSES,
            'birthday_soon_mode' => self::BIRTHDAY_SOON_MODES,
            'lottery_status' => self::LOTTERY_STATUSES,
            'nps_category' => self::NPS_CATEGORIES,
            'notification_preference' => MemberNotificationPreference::keys(),
            'line_block_status' => self::LINE_BLOCK_STATUSES,
        ];
        foreach ($enumInputs as $key => $allowed) {
            $value = trim((string) ($input['segment_'.$key] ?? ''));
            if (in_array($value, $allowed, true)) {
                $criteria[$key] = $value;
            }
        }
        // 日数とセットで意味を持つ条件。日数がなければ条件ごと外す
        foreach (['app_last_used_mode' => 'app_last_used_days', 'reservation_mode' => 'reservation_days'] as $modeKey => $daysKey) {
            $days = (int) ($input['segment_'.$daysKey] ?? 0);
            if (isset($criteria[$modeKey]) && in_array($days, self::PERIOD_DAYS, true)) {
                $criteria[$daysKey] = $days;
            } else {
                unset($criteria[$modeKey]);
            }
        }
        $surveyId = (int) ($input['segment_survey_id'] ?? 0);
        if ($surveyId > 0 && isset($criteria['survey_status'])) {
            $criteria['survey_id'] = $surveyId;
        } else {
            unset($criteria['survey_status']);
        }
        // 日数だけで条件になる入力。0（今日）も有効な値なので、空かどうかで判定する
        $dayInputs = [
            'rank_changed_days' => self::PERIOD_DAYS,
            'queue_days' => self::PERIOD_DAYS,
            'line_follow_days' => self::PERIOD_DAYS,
            'points_expiring_days' => self::SOON_DAYS,
            'coupon_expiring_days' => self::SOON_DAYS,
        ];
        foreach ($dayInputs as $key => $allowed) {
            $value = $input['segment_'.$key] ?? null;
            if ($value !== null && $value !== '' && in_array((int) $value, $allowed, true)) {
                $criteria[$key] = (int) $value;
            }
        }
        $birthdaySoonDays = $input['segment_birthday_soon_days'] ?? null;
        if (isset($criteria['birthday_soon_mode']) && $birthdaySoonDays !== null && $birthdaySoonDays !== '' && in_array((int) $birthdaySoonDays, self::SOON_DAYS, true)) {
            $criteria['birthday_soon_days'] = (int) $birthdaySoonDays;
        } else {
            unset($criteria['birthday_soon_mode']);
        }
        $orderTypes = collect((array) ($input['segment_order_types'] ?? []))
            ->map(fn ($type): string => trim((string) $type))
            ->filter(fn (string $type): bool => array_key_exists($type, OrderType::getValues()))
            ->unique()
            ->values()
            ->all();
        if ($orderTypes !== []) {
            $criteria['order_types'] = $orderTypes;
        }
        foreach (['store_ids', 'acquisition_source_ids'] as $key) {
            $ids = $this->normalizeIntList($input['segment_'.$key] ?? []);
            if ($ids !== []) {
                $criteria[$key] = $ids;
            }
        }

        foreach (['age_min', 'age_max', 'points_min', 'points_max', 'coupon_uses_min', 'coupon_uses_max', 'lifetime_sales_min', 'lifetime_sales_max'] as $key) {
            $value = $input['segment_'.$key] ?? null;
            if ($value !== null && $value !== '') {
                $criteria[$key] = max(0, (int) $value);
            }
        }

        $stampCardId = $input['segment_stamp_card_id'] ?? $input['stamp_card_id'] ?? null;
        $stampCountMin = $input['segment_stamp_count_min'] ?? $input['stamp_count_min'] ?? null;
        $stampCompleted = $input['segment_stamp_completed'] ?? $input['stamp_completed'] ?? null;
        $isStampCompleted = $stampCompleted === true || $stampCompleted === 1 || $stampCompleted === '1' || $stampCompleted === 'on';
        if ($stampCardId !== null && $stampCardId !== '') {
            $parsedStampCardId = (int) $stampCardId;
            if ($parsedStampCardId > 0) {
                $criteria['stamp_card_id'] = $parsedStampCardId;
            }
        }
        if ($stampCountMin !== null && $stampCountMin !== '') {
            $criteria['stamp_count_min'] = max(0, (int) $stampCountMin);
        }
        if ($isStampCompleted) {
            $criteria['stamp_completed'] = true;
        }

        $salesCondition = trim((string) ($input['segment_condition'] ?? ''));
        $periodDays = $input['segment_period_days'] ?? null;
        if ($salesCondition === 'no_orders' || $salesCondition === 'min_amount') {
            $criteria['period_days'] = max(1, min(365, (int) ($periodDays ?? 30)));
            $criteria['no_orders'] = $salesCondition === 'no_orders';
            if ($salesCondition === 'min_amount') {
                $minAmount = isset($input['segment_min_amount']) ? (float) $input['segment_min_amount'] : null;
                if ($minAmount !== null && $minAmount >= 0) {
                    $criteria['min_total_amount'] = $minAmount;
                }
            }
        }

        $profileFilters = [];
        foreach ((array) ($input['segment_profile'] ?? []) as $key => $value) {
            if (preg_match('/^[a-z0-9_]+$/', (string) $key) !== 1) {
                continue;
            }
            $values = collect(is_array($value) ? $value : [$value])
                ->map(fn ($item): string => trim((string) $item))
                ->filter(fn (string $item): bool => $item !== '')
                ->unique()
                ->values()
                ->all();
            if ($values !== []) {
                $profileFilters[(string) $key] = $values;
                if ($this->profileListMode($input, (string) $key) === SegmentCombineMode::ALL) {
                    $criteria['profile_modes'][(string) $key] = SegmentCombineMode::ALL;
                }
            }
        }
        if ($profileFilters !== []) {
            $criteria['profile'] = $profileFilters;
        }

        $groupIds = $this->normalizeIntList($input['segment_audience_group_ids'] ?? []);
        if ($groupIds !== []) {
            $criteria['audience_group_ids'] = $groupIds;
        }

        // 2つ目以降の同じ種類の条件。組み合わせ方は1つ目と同じ「すべて一致／いずれか一致」に従う
        $more = [];
        foreach (array_slice((array) ($input['segment_more'] ?? []), 0, self::MAX_EXTRA_CONDITIONS) as $extraInput) {
            $extra = is_array($extraInput)
                ? $this->buildCriteriaFromInput(array_diff_key($extraInput, array_flip(['segment_more', 'segment_blocks', 'segment_match_mode', 'segment_operators', 'segment_audience_group_ids'])))
                : null;
            if ($extra !== null) {
                $more[] = $extra;
            }
        }
        if ($more !== []) {
            $criteria['more'] = $more;
        }

        $blocks = [];
        foreach (array_slice((array) ($input['segment_blocks'] ?? []), 0, self::MAX_EXTRA_BLOCKS) as $blockInput) {
            $block = is_array($blockInput)
                // 保存したグループは まとまり1 だけで扱う（グループ同士の循環参照を防ぐ）
                ? $this->buildCriteriaFromInput(array_diff_key($blockInput, ['segment_blocks' => true, 'segment_audience_group_ids' => true]))
                : null;
            if ($block !== null) {
                $blocks[] = $block;
            }
        }
        if ($blocks !== []) {
            $criteria['blocks'] = $blocks;
        }

        return array_diff_key($criteria, ['operators' => true, 'match_mode' => true]) === [] ? null : $criteria;
    }

    /**
     * 同じ種類の条件・まとまりの数が上限を超えているか。超えた分を黙って捨てると、選んだより広い対象になるため。
     *
     * @param  array<string, mixed>  $input
     */
    public function exceedsConditionLimits(array $input): bool
    {
        $tooMany = static fn (mixed $list, int $max): bool => is_array($list) && count($list) > $max;
        if ($tooMany($input['segment_more'] ?? null, self::MAX_EXTRA_CONDITIONS)
            || $tooMany($input['segment_blocks'] ?? null, self::MAX_EXTRA_BLOCKS)) {
            return true;
        }
        foreach ((array) ($input['segment_blocks'] ?? []) as $block) {
            if (is_array($block) && $tooMany($block['segment_more'] ?? null, self::MAX_EXTRA_CONDITIONS)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, int>
     */
    public function getMemberIds(int $projectId, array $criteria): Collection
    {
        $sets = $this->collectIdSets($projectId, $criteria);
        $resolved = $sets === [] ? null : $this->combineInDisplayOrder($sets, $criteria);
        foreach ((array) ($criteria['blocks'] ?? []) as $block) {
            if (is_array($block)) {
                $blockIds = $this->getMemberIds($projectId, $block)->all();
                $resolved = $resolved === null ? $blockIds : array_values(array_intersect($resolved, $blockIds));
            }
        }

        return collect($resolved ?? [])->map(fn ($id) => (int) $id)->values();
    }

    /**
     * 指定された条件ごとに、当てはまる会員IDの集合を作る。
     *
     * @return list<array{key: string, ids: list<int>}>
     */
    private function collectIdSets(int $projectId, array $criteria): array
    {
        /** @var list<array{key: string, ids: list<int>}> $sets */
        $sets = [];
        $add = static function (string $key, array $ids) use (&$sets): void {
            $sets[] = ['key' => $key, 'ids' => $ids];
        };

        if (isset($criteria['channel']) && is_string($criteria['channel'])) {
            $add('channel', $this->targetRepository->findIdsByChannel($projectId, $criteria['channel']));
        }

        if (isset($criteria['birthday_month'])) {
            $add('birthday', $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $criteria['birthday_month']));
        }

        if (isset($criteria['birthday_months']) && is_array($criteria['birthday_months'])) {
            $birthdaySets = collect($criteria['birthday_months'])
                ->map(fn ($month) => $this->targetRepository->findIdsByBirthdayMonth($projectId, (int) $month))
                ->all();
            $add('birthday', $this->combineIdSets($birthdaySets, SegmentCombineMode::ANY));
        }

        if (isset($criteria['tag_ids']) && is_array($criteria['tag_ids'])) {
            if (SegmentCombineMode::fromInput($criteria['tag_ids_mode'] ?? null) === SegmentCombineMode::ALL) {
                $tagSets = collect($criteria['tag_ids'])
                    ->map(fn ($tagId) => $this->targetRepository->findIdsByTagIds($projectId, [(int) $tagId]))
                    ->all();
                $add('tags', $this->combineIdSets($tagSets, SegmentCombineMode::ALL));
            } else {
                $add('tags', $this->targetRepository->findIdsByTagIds($projectId, $criteria['tag_ids']));
            }
        }

        $hasVisitMin = array_key_exists('visit_count_min', $criteria);
        $hasVisitMax = array_key_exists('visit_count_max', $criteria);
        if ($hasVisitMin || $hasVisitMax) {
            $add('visits', $this->targetRepository->findIdsByVisitCount(
                $projectId,
                $hasVisitMin ? (int) $criteria['visit_count_min'] : null,
                $hasVisitMax ? (int) $criteria['visit_count_max'] : null,
            ));
        }

        $hasPointsMin = array_key_exists('lifetime_points_min', $criteria);
        $hasPointsMax = array_key_exists('lifetime_points_max', $criteria);
        if ($hasPointsMin || $hasPointsMax) {
            $add('points', $this->targetRepository->findIdsByLifetimeGrantedPoints(
                $projectId,
                $hasPointsMin ? (int) $criteria['lifetime_points_min'] : null,
                $hasPointsMax ? (int) $criteria['lifetime_points_max'] : null,
            ));
        }

        if (isset($criteria['last_visit_mode'], $criteria['last_visit_days'])) {
            $add('last_visit', $this->targetRepository->findIdsByLastVisited(
                $projectId,
                (string) $criteria['last_visit_mode'],
                (int) $criteria['last_visit_days'],
            ));
        }

        if (isset($criteria['sex']) && is_string($criteria['sex'])) {
            $add('sex', $this->targetRepository->findIdsBySex($projectId, $criteria['sex']));
        }

        if (isset($criteria['rank']) && is_string($criteria['rank'])) {
            $add('rank', $this->targetRepository->findIdsByRank($projectId, $criteria['rank']));
        }

        if (isset($criteria['rfm_segments']) && is_array($criteria['rfm_segments'])) {
            $add('rfm', $this->rfmAnalysisService->findMemberIdsBySegments($projectId, $criteria['rfm_segments']));
        }

        if (isset($criteria['product_id'])) {
            $add('product', $this->targetRepository->findIdsByPurchasedProduct($projectId, (int) $criteria['product_id']));
        }

        if (isset($criteria['held_coupon_id'], $criteria['held_coupon_status'])) {
            $add('held_coupon', $this->targetRepository->findIdsByCouponStatus(
                $projectId,
                (int) $criteria['held_coupon_id'],
                (string) $criteria['held_coupon_status'],
            ));
        }

        if (isset($criteria['read_announcement_id'], $criteria['read_announcement_status'])) {
            $add('announcement_read', $this->targetRepository->findIdsByAnnouncementRead(
                $projectId,
                (int) $criteria['read_announcement_id'],
                (string) $criteria['read_announcement_status'],
            ));
        }

        if (isset($criteria['registered_from']) || isset($criteria['registered_to'])) {
            $add('registered', $this->targetRepository->findIdsByRegisteredDate(
                $projectId,
                isset($criteria['registered_from']) ? (string) $criteria['registered_from'] : null,
                isset($criteria['registered_to']) ? (string) $criteria['registered_to'] : null,
            ));
        }

        if (isset($criteria['age_min']) || isset($criteria['age_max'])) {
            $add('age', $this->targetRepository->findIdsByAge(
                $projectId,
                isset($criteria['age_min']) ? (int) $criteria['age_min'] : null,
                isset($criteria['age_max']) ? (int) $criteria['age_max'] : null,
            ));
        }

        if (isset($criteria['points_min']) || isset($criteria['points_max'])) {
            $add('point_balance', $this->targetRepository->findIdsByPointBalance(
                $projectId,
                isset($criteria['points_min']) ? (int) $criteria['points_min'] : null,
                isset($criteria['points_max']) ? (int) $criteria['points_max'] : null,
            ));
        }

        if (is_array($criteria['store_ids'] ?? null)) {
            $add('store', $this->targetRepository->findIdsByStores($projectId, $criteria['store_ids']));
        }
        if (is_array($criteria['acquisition_source_ids'] ?? null)) {
            $add('acquisition', $this->targetRepository->findIdsByAcquisitionSources($projectId, $criteria['acquisition_source_ids']));
        }
        if (isset($criteria['app_last_used_mode'], $criteria['app_last_used_days'])) {
            $add('app_last_used', $this->targetRepository->findIdsByAppLastUsed($projectId, (string) $criteria['app_last_used_mode'], (int) $criteria['app_last_used_days']));
        }
        if (isset($criteria['survey_id'], $criteria['survey_status'])) {
            $add('survey', $this->targetRepository->findIdsBySurveyResponse($projectId, (int) $criteria['survey_id'], (string) $criteria['survey_status']));
        }
        if (isset($criteria['reservation_mode'], $criteria['reservation_days'])) {
            $add('reservation', $this->targetRepository->findIdsByReservation($projectId, (string) $criteria['reservation_mode'], (int) $criteria['reservation_days']));
        }
        if (isset($criteria['subscription_status'])) {
            $add('subscription', $this->targetRepository->findIdsBySubscriptionStatus($projectId, (string) $criteria['subscription_status']));
        }
        if (isset($criteria['ticket_status'])) {
            $add('ticket', $this->targetRepository->findIdsByTicketStatus($projectId, (string) $criteria['ticket_status']));
        }
        if (isset($criteria['coupon_uses_min']) || isset($criteria['coupon_uses_max'])) {
            $add('coupon_uses', $this->targetRepository->findIdsByCouponUseCount(
                $projectId,
                isset($criteria['coupon_uses_min']) ? (int) $criteria['coupon_uses_min'] : null,
                isset($criteria['coupon_uses_max']) ? (int) $criteria['coupon_uses_max'] : null,
            ));
        }
        if (isset($criteria['rank_changed_days'])) {
            $add('rank_changed', $this->targetRepository->findIdsByRankChangedWithin($projectId, (int) $criteria['rank_changed_days']));
        }
        if (isset($criteria['wallet_status'])) {
            $add('wallet', $this->targetRepository->findIdsByWalletStatus($projectId, (string) $criteria['wallet_status']));
        }

        if (isset($criteria['birthday_soon_mode'], $criteria['birthday_soon_days'])) {
            $add('birthday_soon', $this->targetRepository->findIdsByBirthdaySoon($projectId, (string) $criteria['birthday_soon_mode'], (int) $criteria['birthday_soon_days']));
        }
        if (isset($criteria['lifetime_sales_min']) || isset($criteria['lifetime_sales_max'])) {
            $add('lifetime_sales', $this->targetRepository->findIdsByLifetimeSales(
                $projectId,
                isset($criteria['lifetime_sales_min']) ? (int) $criteria['lifetime_sales_min'] : null,
                isset($criteria['lifetime_sales_max']) ? (int) $criteria['lifetime_sales_max'] : null,
            ));
        }
        if (is_array($criteria['order_types'] ?? null)) {
            $add('order_type', $this->targetRepository->findIdsByOrderTypes($projectId, array_map('strval', $criteria['order_types'])));
        }
        if (isset($criteria['queue_days'])) {
            $add('queue', $this->targetRepository->findIdsByQueueWithin($projectId, (int) $criteria['queue_days']));
        }
        if (isset($criteria['points_expiring_days'])) {
            $add('points_expiring', $this->memberIdsWithPointsExpiring($projectId, (int) $criteria['points_expiring_days']));
        }
        if (isset($criteria['coupon_expiring_days'])) {
            $add('coupon_expiring', $this->targetRepository->findIdsByCouponExpiring($projectId, (int) $criteria['coupon_expiring_days']));
        }
        if (isset($criteria['lottery_status'])) {
            $add('lottery', $this->targetRepository->findIdsByLotteryStatus($projectId, (string) $criteria['lottery_status']));
        }
        if (isset($criteria['nps_category'])) {
            $add('nps', $this->targetRepository->findIdsByNpsCategory($projectId, (string) $criteria['nps_category']));
        }
        if (isset($criteria['line_follow_days'])) {
            $add('line_follow', $this->targetRepository->findIdsByLineFollowWithin($projectId, (int) $criteria['line_follow_days']));
        }
        if (isset($criteria['notification_preference'])) {
            $add('notification', $this->targetRepository->findIdsByNotificationPreference($projectId, (string) $criteria['notification_preference']));
        }
        if (isset($criteria['line_block_status'])) {
            $add('line_block', $this->targetRepository->findIdsByLineBlockStatus($projectId, (string) $criteria['line_block_status']));
        }

        $hasStampFilter = isset($criteria['stamp_card_id'])
            || isset($criteria['stamp_count_min'])
            || (($criteria['stamp_completed'] ?? false) === true);
        if ($hasStampFilter) {
            $add('stamp', $this->targetRepository->findIdsByStampProgress(
                $projectId,
                isset($criteria['stamp_card_id']) ? (int) $criteria['stamp_card_id'] : null,
                isset($criteria['stamp_count_min']) ? (int) $criteria['stamp_count_min'] : null,
                ($criteria['stamp_completed'] ?? false) === true,
            ));
        }

        $periodDays = isset($criteria['period_days']) ? (int) $criteria['period_days'] : 0;
        $noOrders = (bool) ($criteria['no_orders'] ?? false);
        $minAmount = isset($criteria['min_total_amount']) ? (float) $criteria['min_total_amount'] : null;
        if ($periodDays > 0 && ($noOrders || ($minAmount !== null && $minAmount >= 0))) {
            $end = Carbon::today()->endOfDay();
            $start = Carbon::today()->subDays($periodDays)->startOfDay();
            if ($noOrders) {
                $add('sales', $this->targetRepository->findIdsWithNoOrdersInPeriod($projectId, $start, $end));
            } elseif ($minAmount !== null) {
                $add('sales', $this->targetRepository->findIdsByMinSales($projectId, $start, $end, $minAmount));
            }
        }

        $profileModes = is_array($criteria['profile_modes'] ?? null) ? $criteria['profile_modes'] : [];
        foreach ((array) ($criteria['profile'] ?? []) as $key => $values) {
            if (is_array($values)) {
                $add('profile_'.$key, $this->targetRepository->findIdsByProfileField(
                    $projectId,
                    (string) $key,
                    $values,
                    SegmentCombineMode::fromInput($profileModes[$key] ?? null) === SegmentCombineMode::ALL,
                ));
            }
        }

        if (isset($criteria['audience_group_ids']) && is_array($criteria['audience_group_ids'])) {
            $groupIds = collect($this->memberIdsForAudienceGroups($projectId, $criteria['audience_group_ids']));
            $add('groups', $groupIds->all());
        }

        foreach ((array) ($criteria['more'] ?? []) as $extra) {
            if (is_array($extra)) {
                array_push($sets, ...$this->collectIdSets($projectId, $extra));
            }
        }

        return $sets;
    }

    /**
     * 条件の集合を表示順に並べ、条件ごとの AND / OR で1つにまとめる。
     *
     * @param  list<array{key: string, ids: list<int>}>  $sets
     * @return list<int>
     */
    private function combineInDisplayOrder(array $sets, array $criteria): array
    {
        $displayOrder = self::DISPLAY_ORDER;
        usort($sets, static function (array $left, array $right) use ($displayOrder): int {
            $leftPosition = array_search($left['key'], $displayOrder, true);
            $rightPosition = array_search($right['key'], $displayOrder, true);
            $leftPosition = $leftPosition === false ? count($displayOrder) : $leftPosition;
            $rightPosition = $rightPosition === false ? count($displayOrder) : $rightPosition;

            return $leftPosition <=> $rightPosition;
        });

        $first = array_shift($sets);
        $resolved = $first['ids'];
        $operators = is_array($criteria['operators'] ?? null) ? $criteria['operators'] : [];
        foreach ($sets as $set) {
            $operator = match ($criteria['match_mode'] ?? null) {
                SegmentCombineMode::ANY => 'or',
                SegmentCombineMode::ALL => 'and',
                default => $operators[$set['key']] ?? 'and',
            };
            $resolved = $operator === 'or'
                ? array_values(array_unique(array_merge($resolved, $set['ids'])))
                : array_values(array_intersect($resolved, $set['ids']));
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function countMembers(int $projectId, array $criteria): int
    {
        return $this->getMemberIds($projectId, $criteria)->count();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function listMode(array $input, string $key): string
    {
        $modes = $input['segment_value_mode'] ?? [];

        return SegmentCombineMode::fromInput(is_array($modes) ? ($modes[$key] ?? null) : null);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function profileListMode(array $input, string $key): string
    {
        $modes = $input['segment_value_mode'] ?? [];
        $profileModes = is_array($modes) && is_array($modes['profile'] ?? null) ? $modes['profile'] : [];

        return SegmentCombineMode::fromInput($profileModes[$key] ?? null);
    }

    /**
     * @param  list<list<int>>  $sets
     * @return list<int>
     */
    private function combineIdSets(array $sets, string $mode): array
    {
        if ($sets === []) {
            return [];
        }
        $resolved = array_map('intval', array_shift($sets));
        foreach ($sets as $set) {
            $ids = array_map('intval', $set);
            $resolved = $mode === SegmentCombineMode::ALL
                ? array_values(array_intersect($resolved, $ids))
                : array_values(array_unique(array_merge($resolved, $ids)));
        }

        return $resolved;
    }

    /**
     * 今日から N 日後までに失効するポイントが残っている会員。
     * SQL で期限の近い付与がある会員に絞ってから、使用分を差し引いた残りを台帳でまとめて計算する（失効のお知らせと同じ計算）。
     *
     * @return list<int>
     */
    private function memberIdsWithPointsExpiring(int $projectId, int $days): array
    {
        $deadline = Carbon::today()->addDays($days)->endOfDay();
        $ids = [];
        foreach ($this->pointLedger->balancesFor($this->targetRepository->findIdsWithPointsExpiringCandidates($projectId, $days)) as $memberId => $balance) {
            if ($balance['points_expiring'] > 0 && $balance['expires_at'] !== null && $balance['expires_at'] <= $deadline) {
                $ids[] = $memberId;
            }
        }

        return $ids;
    }

    /**
     * @param  list<int|string>  $groupIds
     * @return list<int>
     */
    private function memberIdsForAudienceGroups(int $projectId, array $groupIds): array
    {
        $ids = [];
        $groups = MemberAudienceGroup::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $groupIds)
            ->get();
        foreach ($groups as $group) {
            $criteria = is_array($group->criteria) ? $group->criteria : [];
            unset($criteria['audience_group_ids']);
            if ($criteria === []) {
                continue;
            }
            foreach ($this->getMemberIds($projectId, $criteria) as $memberId) {
                $ids[] = (int) $memberId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<string>
     */
    private function normalizeRfmSegments(mixed $values): array
    {
        $items = is_array($values) ? $values : [$values];

        return collect($items)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => in_array($value, RfmSegment::keys(), true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function normalizeIntList(mixed $values, int $min = 1, ?int $max = null): array
    {
        $items = is_array($values) ? $values : [$values];

        return collect($items)
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value): bool => $value >= $min && ($max === null || $value <= $max))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
