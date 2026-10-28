<?php

namespace App\Infrastructure\Persistence\Eloquent\Reservation;

use App\Domain\Reservation\Policies\ReservationReminderPolicy;
use App\Domain\Reservation\Repositories\ReservationReminderRepository;
use App\Domain\Reservation\ValueObjects\ReservationReminderCandidate;
use App\Enums\MemberStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class EloquentReservationReminderRepository implements ReservationReminderRepository
{
    public function findUnremindedUntil(\DateTimeInterface $until, array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        return DB::table('reservations')
            ->join('projects', 'projects.id', '=', 'reservations.project_id')
            ->leftJoin('stores', 'stores.id', '=', 'reservations.store_id')
            ->leftJoin('members', fn ($join) => $join->on('members.user_id', '=', 'reservations.user_id')
                ->on('members.project_id', '=', 'reservations.project_id')
                ->where('members.status', MemberStatus::ACTIVE)
                ->whereNull('members.deleted_at'))
            ->where('projects.is_active', true)
            ->whereIn('reservations.project_id', $projectIds)
            ->whereIn('reservations.status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
            ->where(fn ($query) => $query->whereNull('reservations.reminder_sent_at')->orWhereNull('reservations.second_reminder_sent_at'))
            ->where('reservations.reserved_at', '>', now())
            ->where('reservations.reserved_at', '<=', $until)
            ->get(['reservations.id', 'reservations.project_id', 'reservations.store_id', 'stores.name as store_name', 'members.id as member_id',
                'reservations.reserved_at', 'reservations.created_at', 'reservations.number_of_people', 'reservations.course_name',
                'reservations.reminder_sent_at', 'reservations.second_reminder_sent_at'])
            ->map(fn (object $row) => new ReservationReminderCandidate(
                reservationId: (int) $row->id,
                projectId: (int) $row->project_id,
                storeId: $row->store_id !== null ? (int) $row->store_id : null,
                storeName: $row->store_name,
                memberId: $row->member_id !== null ? (int) $row->member_id : null,
                reservedAt: new \DateTimeImmutable((string) $row->reserved_at),
                createdAt: new \DateTimeImmutable((string) $row->created_at),
                numberOfPeople: $row->number_of_people !== null ? (int) $row->number_of_people : null,
                courseName: $row->course_name,
                firstReminded: $row->reminder_sent_at !== null,
                secondReminded: $row->second_reminder_sent_at !== null,
            ))
            ->all();
    }

    public function markReminded(int $reservationId, string $stage, \DateTimeInterface $reservedAt): bool
    {
        $column = self::column($stage);

        return Reservation::withoutGlobalScopes()->whereKey($reservationId)->whereNull($column)
            ->where('reserved_at', $reservedAt->format('Y-m-d H:i:s'))
            ->update([$column => now()]) === 1;
    }

    public function unmarkReminded(int $reservationId, string $stage): void
    {
        Reservation::withoutGlobalScopes()->whereKey($reservationId)->update([self::column($stage) => null]);
    }

    private static function column(string $stage): string
    {
        return match ($stage) {
            ReservationReminderPolicy::STAGE_FIRST => 'reminder_sent_at',
            ReservationReminderPolicy::STAGE_SECOND => 'second_reminder_sent_at',
            default => throw new \InvalidArgumentException('Unknown reminder stage: '.$stage),
        };
    }
}
