<?php

namespace App\Domain\Reservation\Policies;

use App\Domain\Reservation\ValueObjects\ReservationReminderCandidate;
use App\Domain\Reservation\ValueObjects\ReservationReminderConfig;

/** 予約リマインドを送る時期と文面。1回目と2回目はそれぞれ「予約の何時間前か」で送る。 */
final class ReservationReminderPolicy
{
    /** 設定できる「何時間前」。0 は送らない */
    public const HOUR_OPTIONS = [0, 1, 3, 6, 24, 48];

    public const MAX_HOURS = 48;

    public const STAGE_FIRST = 'first';

    public const STAGE_SECOND = 'second';

    public const DEFAULT_MESSAGE = '{日時}からご予約をいただいています{詳細}。ご来店をお待ちしております。';

    /** 本文で使える差し込み */
    public const PLACEHOLDERS = ['{店舗}' => '店舗名', '{日時}' => '予約日時（例: 10月28日 18:30）', '{人数}' => '人数（例: 2名）', '{コース}' => 'コース名', '{詳細}' => '人数とコース（例:（2名・季節のコース））'];

    /**
     * 今送る回。スケジューラーが止まっていて両方の時期が来ていても、送るのは1通にする。
     *
     * @return list<string>
     */
    public function dueStages(ReservationReminderCandidate $candidate, \DateTimeImmutable $now, ReservationReminderConfig $config): array
    {
        $stages = [];
        if (! $candidate->firstReminded && $this->isDue($candidate, $now, $config->firstHours)) {
            $stages[] = self::STAGE_FIRST;
        }
        if (! $candidate->secondReminded && $this->isDue($candidate, $now, $config->secondHours)) {
            $stages[] = self::STAGE_SECOND;
        }

        return $stages;
    }

    public function isDue(ReservationReminderCandidate $candidate, \DateTimeImmutable $now, int $hours): bool
    {
        if ($hours <= 0 || $candidate->reservedAt <= $now) {
            return false;
        }
        $remindFrom = $candidate->reservedAt->modify("-{$hours} hours");

        // 直前に入った予約は、予約完了の案内で足りるため送らない
        return $remindFrom <= $now && $candidate->createdAt <= $remindFrom;
    }

    public function message(ReservationReminderCandidate $candidate, ?string $template = null): string
    {
        $details = array_filter([
            $candidate->numberOfPeople !== null ? $candidate->numberOfPeople.'名' : null,
            $candidate->courseName,
        ]);

        $template ??= ($candidate->storeName !== null ? '{店舗}で' : '').self::DEFAULT_MESSAGE;

        return strtr($template, [
            '{店舗}' => (string) $candidate->storeName,
            '{日時}' => $candidate->reservedAt->format('n月j日 H:i'),
            '{人数}' => $candidate->numberOfPeople !== null ? $candidate->numberOfPeople.'名' : '',
            '{コース}' => (string) $candidate->courseName,
            '{詳細}' => $details !== [] ? '（'.implode('・', $details).'）' : '',
        ]);
    }
}
