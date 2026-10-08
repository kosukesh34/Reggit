<?php

namespace App\Services;

use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Domain\Reservation\ValueObjects\PendingReservation;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationCourse;
use App\Models\User;
use App\Util\AdminContext;
use App\Util\DateUtil;
use App\Util\LogHelper;
use App\Util\StoreContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private StoreContext $storeContext,
    ) {}

    public function createReservation(User $user, array $data): Reservation
    {
        return DB::transaction(function () use ($user, $data): Reservation {
            $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
            $reservedAt = DateUtil::nullableCarbon($data['reserved_at']);
            if ($reservedAt === null) {
                throw new \InvalidArgumentException('予約日時が不正です');
            }

            $course = $this->resolveCourse($projectId, $data['reservation_course_id'] ?? null);

            $reservationId = $this->reservationRepository->persistPending(new PendingReservation(
                $projectId,
                (int) $user->id,
                $reservedAt,
                (int) ($data['number_of_people'] ?? 1),
                $data['notes'] ?? null,
                $this->storeContext->resolveForWrite($projectId),
            ));

            LogHelper::info('[Reservation] created', [
                'reservation_id' => $reservationId,
                'user_id' => $user->id,
            ]);

            $reservation = Reservation::findOrFail($reservationId);
            $this->applyCourse($reservation, $course);

            return $reservation;

        }, 3);
    }

    public function cancelReservation(int $reservationId, User $user): Reservation
    {
        return DB::transaction(function () use ($reservationId, $user): Reservation {
            $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
            $aggregate = $this->reservationRepository->findByIdForUpdate(
                $reservationId,
                $projectId,
                (int) $user->id,
            );
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Reservation::class, [$reservationId]);
            }
            $aggregate->cancel();
            $this->reservationRepository->save($aggregate);

            LogHelper::info('[Reservation] cancelled', [
                'reservation_id' => $aggregate->id()->value(),
                'user_id' => $user->id,
                'project_id' => $projectId,
            ]);

            return Reservation::findOrFail($aggregate->id()->value());

        }, 3);
    }

    public function updateReservation(int $reservationId, User $user, array $data): Reservation
    {
        return DB::transaction(function () use ($reservationId, $user, $data): Reservation {
            $projectId = (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null));
            $aggregate = $this->reservationRepository->findByIdForUpdate(
                $reservationId,
                $projectId,
                (int) $user->id,
            );
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Reservation::class, [$reservationId]);
            }

            $reservedAt = DateUtil::nullableCarbon($data['reserved_at']);
            if ($reservedAt === null) {
                throw new \InvalidArgumentException('予約日時が不正です');
            }

            $course = array_key_exists('reservation_course_id', $data)
                ? $this->resolveCourse($projectId, $data['reservation_course_id'])
                : null;

            $aggregate->updateDetails(
                $reservedAt,
                (int) ($data['number_of_people'] ?? $aggregate->numberOfPeople()),
                array_key_exists('notes', $data) ? $data['notes'] : $aggregate->notes(),
            );
            $this->reservationRepository->save($aggregate);

            $reservation = Reservation::findOrFail($aggregate->id()->value());
            if (array_key_exists('reservation_course_id', $data)) {
                $this->applyCourse($reservation, $course);
            }

            LogHelper::info('[Reservation] updated', [
                'reservation_id' => $aggregate->id()->value(),
                'user_id' => $user->id,
            ]);

            return $reservation;

        }, 3);
    }

    public function getUserReservations(User $user): array
    {
        try {
            $reservations = $user->reservations()
                ->orderBy('reserved_at', 'desc')
                ->get();

            return $reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'reservation_number' => $reservation->reservation_number ?? '',
                    'reserved_at' => DateUtil::nullableCarbon($reservation->reserved_at)?->format('c'),
                    'number_of_people' => $reservation->number_of_people ?? 1,
                    'status' => $reservation->status ?? ReservationStatus::PENDING,
                    'notes' => $reservation->notes ?? null,
                    'reservation_course_id' => $reservation->reservation_course_id,
                    'course_name' => $reservation->course_name,
                    'course_price' => $reservation->course_price,
                ];
            })->toArray();
        } catch (\Exception $e) {
            LogHelper::error('[Reservation] get user reservations failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function resolveCourse(int $projectId, int|string|null $courseId): ?ReservationCourse
    {
        if ($courseId === null || $courseId === '') {
            return null;
        }

        return ReservationCourse::where('project_id', $projectId)->where('is_active', true)->findOrFail((int) $courseId);
    }

    private function applyCourse(Reservation $reservation, ?ReservationCourse $course): void
    {
        $reservation->update([
            'reservation_course_id' => $course?->id,
            'course_name' => $course?->name,
            'course_price' => $course?->price,
        ]);
    }
}
