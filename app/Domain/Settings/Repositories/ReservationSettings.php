<?php

namespace App\Domain\Settings\Repositories;

use App\Domain\Reservation\ValueObjects\ReservationReminderConfig;
use App\Domain\Reservation\ValueObjects\ReservationRules;

interface ReservationSettings
{
    /** @return list<string> */
    public function getReservationTimeSlots(int $projectId): array;

    public function getAdvanceBookingDays(int $projectId): int;

    public function getReservationCapacityPerSlot(int $projectId): int;

    /** 選択中の店舗で上書きした値を含む、会員の予約の受付ルール */
    public function getReservationRules(int $projectId): ReservationRules;

    /** プロジェクト共通の予約リマインドの設定 */
    public function getReservationReminderConfig(int $projectId): ReservationReminderConfig;

    /** @return array<int, ReservationReminderConfig> 店舗ID => 店舗で上書きした設定（上書きしていない項目は共通の値） */
    public function getReservationReminderConfigsByStore(int $projectId): array;

    /** @return list<int> プロジェクトか店舗のどちらかでリマインドを有効にしているプロジェクト */
    public function getProjectIdsWithReservationReminders(): array;

    public function isDateAvailable(string $date, int $projectId): bool;
}
