<?php

namespace App\Domain\Reservation\ValueObjects;

/** 会員が予約するときの受付ルール。プロジェクトごとに決め、店舗で上書きできる。 */
final class ReservationRules
{
    /** 予約時刻の何分前まで受け付けるか（管理画面の選択肢） */
    public const LEAD_MINUTE_OPTIONS = [0, 30, 60, 120, 180, 360, 720, 1440];

    /** 変更・キャンセルの締め切り（予約時刻の何時間前か。管理画面の選択肢） */
    public const CHANGE_DEADLINE_HOUR_OPTIONS = [0, 1, 3, 6, 12, 24, 48, 72];

    public const MAX_PARTY_SIZE_LIMIT = 99;

    /** @param list<string> $timeSlots */
    public function __construct(
        public readonly array $timeSlots,
        public readonly int $advanceDays,
        public readonly int $capacity,
        /** true なら枠の上限を人数で、false なら予約件数で数える */
        public readonly bool $capacityByPeople,
        public readonly int $maxPartySize,
        public readonly bool $sameDay,
        public readonly int $leadMinutes,
        public readonly int $changeDeadlineHours,
    ) {}

    /** 1件の予約が枠を使う量 */
    public function seatsFor(int $numberOfPeople): int
    {
        return $this->capacityByPeople ? $numberOfPeople : 1;
    }
}
