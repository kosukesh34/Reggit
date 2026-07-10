<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Reservation;

use App\Enums\ReservationStatus;
use App\Infrastructure\Persistence\Eloquent\Reservation\EloquentReservationRepository;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_count_active_in_slot_自プロジェクトのみ数えること(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $otherUser = User::factory()->create(['project_id' => $other->id]);
        $slot = Carbon::parse('2026-08-01 12:00:00');

        Reservation::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'reserved_at' => $slot,
            'number_of_people' => 2,
            'status' => ReservationStatus::CONFIRMED,
        ]);
        Reservation::create([
            'project_id' => $other->id,
            'user_id' => $otherUser->id,
            'reserved_at' => $slot,
            'number_of_people' => 2,
            'status' => ReservationStatus::CONFIRMED,
        ]);

        $repository = new EloquentReservationRepository;
        $count = $repository->countActiveInSlot(
            (int) $project->id,
            $slot->copy()->startOfMinute(),
            $slot->copy()->endOfMinute(),
            null,
        );

        $this->assertSame(1, $count);
    }

    public function test_find_by_id_他プロジェクトは取得できないこと(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $reservation = Reservation::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'reserved_at' => Carbon::parse('2026-08-01 12:00:00'),
            'number_of_people' => 2,
            'status' => ReservationStatus::PENDING,
        ]);

        $repository = new EloquentReservationRepository;

        $this->assertNotNull($repository->findById((int) $reservation->id, (int) $project->id));
        $this->assertNull($repository->findById((int) $reservation->id, (int) $other->id));
    }
}
