<?php

namespace App\Domain\Coupon\ValueObjects;

/**
 * クーポンを使える曜日と時間帯。どちらも空なら制限なし。
 * 祝日は「祝日」を選んだときだけ使える（曜日より優先。土日の祝日も同じ）。
 * 時間帯は開始を含み終了を含まない（10:00〜12:00 は 11:59 まで）。
 */
final class CouponUsageWindow
{
    public const HOLIDAY = 'holiday';

    private const WEEKDAYS = [1, 2, 3, 4, 5];

    private const DAY_LABELS = [1 => '月', 2 => '火', 3 => '水', 4 => '木', 5 => '金', 6 => '土', 0 => '日'];

    /**
     * @param  list<int|string>  $days  0(日)〜6(土) と "holiday"
     * @param  list<array{start: string, end: string}>  $timeRanges  H:i
     */
    public function __construct(
        public readonly array $days = [],
        public readonly array $timeRanges = [],
    ) {}

    /**
     * @param  array<int, mixed>|null  $days
     * @param  array<int, mixed>|null  $timeRanges
     */
    public static function fromArrays(?array $days, ?array $timeRanges): self
    {
        $normalizedDays = [];
        foreach ($days ?? [] as $day) {
            $normalizedDays[] = $day === self::HOLIDAY ? self::HOLIDAY : (int) $day;
        }
        $ranges = [];
        foreach ($timeRanges ?? [] as $range) {
            if (is_array($range) && isset($range['start'], $range['end'])) {
                $ranges[] = ['start' => (string) $range['start'], 'end' => (string) $range['end']];
            }
        }

        return new self(array_values(array_unique($normalizedDays, SORT_REGULAR)), $ranges);
    }

    public function isRestricted(): bool
    {
        return $this->days !== [] || $this->timeRanges !== [];
    }

    public function allows(\DateTimeInterface $at, bool $isHoliday): bool
    {
        if ($this->days !== []) {
            $dayAllowed = $isHoliday
                ? in_array(self::HOLIDAY, $this->days, true)
                : in_array((int) $at->format('w'), $this->days, true);
            if (! $dayAllowed) {
                return false;
            }
        }
        if ($this->timeRanges === []) {
            return true;
        }
        $time = $at->format('H:i');
        foreach ($this->timeRanges as $range) {
            if ($range['start'] <= $time && $time < $range['end']) {
                return true;
            }
        }

        return false;
    }

    /** 例: 「平日（祝日を除く） 10:00〜12:00」「土日・祝日」「月・水 11:00〜14:00、17:00〜20:00」 */
    public function label(): ?string
    {
        if (! $this->isRestricted()) {
            return null;
        }
        $parts = [];
        if ($this->days !== []) {
            $weekdays = array_values(array_filter($this->days, 'is_int'));
            sort($weekdays);
            $holiday = in_array(self::HOLIDAY, $this->days, true);
            $dayLabel = match (true) {
                $weekdays === self::WEEKDAYS => $holiday ? '平日・祝日' : '平日（祝日を除く）',
                $weekdays === [0, 6] => $holiday ? '土日・祝日' : '土日（祝日を除く）',
                default => implode('・', array_map(
                    fn (int $day) => self::DAY_LABELS[$day],
                    array_values(array_filter(array_keys(self::DAY_LABELS), fn (int $day) => in_array($day, $weekdays, true))),
                )).($holiday ? ($weekdays === [] ? '祝日' : '・祝日') : ($weekdays === [] ? '' : '（祝日を除く）')),
            };
            $parts[] = $dayLabel;
        }
        if ($this->timeRanges !== []) {
            $parts[] = implode('、', array_map(fn (array $range) => $range['start'].'〜'.$range['end'], $this->timeRanges));
        }

        return implode(' ', $parts);
    }
}
