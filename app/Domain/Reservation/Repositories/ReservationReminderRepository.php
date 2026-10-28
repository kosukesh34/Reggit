<?php

namespace App\Domain\Reservation\Repositories;

use App\Domain\Reservation\ValueObjects\ReservationReminderCandidate;

interface ReservationReminderRepository
{
    /** @return list<ReservationReminderCandidate> 有効なプロジェクトの、until までに来店する、1回目か2回目が未送信の予約待ち・予約確定 */
    /** @param list<int> $projectIds リマインドを有効にしているプロジェクト */
    public function findUnremindedUntil(\DateTimeInterface $until, array $projectIds): array;

    /**
     * その回（ReservationReminderPolicy::STAGE_*）を送信済みにする。すでに他の処理が送っていれば false（重複実行でも1回だけ送る）。
     * 読んだあとに予約日時が変わっていても false（古い日時で送らない）
     */
    public function markReminded(int $reservationId, string $stage, \DateTimeInterface $reservedAt): bool;

    /** 送信に失敗したとき、次の実行で送り直せるよう未送信に戻す */
    public function unmarkReminded(int $reservationId, string $stage): void;
}
