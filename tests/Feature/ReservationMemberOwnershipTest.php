<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\BusinessSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\User;
use App\UseCases\Reservation\Data\ReservationStoreInput;
use App\UseCases\Reservation\Data\ReservationUpdateInput;
use App\UseCases\Reservation\ReservationStoreUseCase;
use App\UseCases\Reservation\ReservationUpdateUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ReservationMemberOwnershipTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['create', 'delete'])]
    #[TestWith(['create', 'transfer'])]
    #[TestWith(['update', 'delete'])]
    #[TestWith(['update', 'transfer'])]
    #[TestWith(['create', 'user_deleted'])]
    #[TestWith(['update', 'user_deleted'])]
    #[TestWith(['create', 'authentication_version'])]
    #[TestWith(['update', 'authentication_version'])]
    public function test_stale_membership_cannot_create_or_change_a_reservation(string $action, string $change): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $store = $project->stores()->firstOrFail();
        app(StoreContext::class)->bind(Request::create('/', 'POST', ['store_id' => $store->id]), $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->load('member');
        $slot = app(BusinessSettingStore::class)->getReservationTimeSlots($project->id)[0];
        $payload = ['reserved_at' => now()->addDay()->format('Y-m-d').' '.$slot, 'number_of_people' => 1];
        $reservation = $action === 'update' ? Reservation::factory()->create(['project_id' => $project->id, 'store_id' => $store->id, 'user_id' => $user->id, 'number_of_people' => 2]) : null;
        if ($change === 'delete') {
            $member->delete();
        } elseif ($change === 'user_deleted') {
            $user->fresh()->delete();
        } elseif ($change === 'authentication_version') {
            $user->fresh()->increment('authentication_version');
        } else {
            $other = User::factory()->create(['project_id' => $project->id]);
            $member->forceFill(['user_id' => $other->id])->save();
        }
        $result = $action === 'create'
            ? app(ReservationStoreUseCase::class)->handle(new ReservationStoreInput($user, $payload))
            : app(ReservationUpdateUseCase::class)->handle(new ReservationUpdateInput($user, (int) $reservation->id, $payload));
        $this->assertTrue($result->requiresMembership);
        $this->assertNull($result->reservation);
        if ($reservation === null) {
            $this->assertDatabaseMissing('reservations', ['project_id' => $project->id, 'user_id' => $user->id]);
        } else {
            $this->assertSame(2, (int) $reservation->fresh()->number_of_people);
        }
    }
}
