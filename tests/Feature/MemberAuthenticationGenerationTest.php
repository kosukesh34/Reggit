<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\User;
use App\Services\DeviceService;
use App\Services\Member\MemberProfileUpdater;
use App\Services\UserService;
use App\UseCases\Member\ClaimMemberTransferCodeUseCase;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\UseCases\Reservation\ReservationCancelUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MemberAuthenticationGenerationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['profile'])]
    #[TestWith(['reservation_cancel'])]
    public function test_request_resolved_before_transfer_cannot_write_after_revocation(string $action): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $source = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-auth-source', 'name' => 'Current member']);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $source->id]);
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $source->id, 'status' => ReservationStatus::PENDING]);
        $oldDevice = app(DeviceService::class)->register($source, ['platform' => 'ios', 'device_identifier' => 'auth-source-device']);
        $request = Request::create('/api/members/me', 'GET', [], [], [], [
            'HTTP_X_DEVICE_IDENTIFIER' => $oldDevice->device_identifier,
            'HTTP_X_DEVICE_TOKEN' => $oldDevice->auth_token,
        ]);
        $resolved = app(UserService::class)->findUser($request);
        $this->assertNotNull($resolved);
        $resolved->load('member');
        $code = app(IssueMemberTransferCodeUseCase::class)->handle($source, null)->code;
        $destination = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-auth-destination']);
        $newDevice = app(DeviceService::class)->register($destination, ['platform' => 'ios', 'device_identifier' => 'auth-destination-device']);
        app(ClaimMemberTransferCodeUseCase::class)->handle($destination, $newDevice, $code, (int) $member->id);
        $this->assertSame(1, (int) $source->fresh()->authentication_version);
        $this->assertSame(0, (int) $resolved->authentication_version);
        if ($action === 'profile') {
            $rejected = false;
            try {
                app(MemberProfileUpdater::class)->updateProfile($resolved, ['name' => 'Revoked request']);
            } catch (ValidationException $exception) {
                $rejected = true;
            }
            $this->assertSame('Current member', $source->fresh()->name);
            $this->assertTrue($rejected);
        } else {
            $result = app(ReservationCancelUseCase::class)->handle($resolved, (int) $reservation->id);
            $this->assertSame(ReservationStatus::PENDING, $reservation->fresh()->status);
            $this->assertTrue($result->requiresMembership);
            $this->assertNull($result->reservation);
        }
    }

    #[TestWith(['profile'])]
    #[TestWith(['reservation_cancel'])]
    public function test_current_generation_can_update_profile_or_cancel_reservation(string $action): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'authentication_version' => 1]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        if ($action === 'profile') {
            app(MemberProfileUpdater::class)->updateProfile($user, ['name' => 'Current generation']);
            $this->assertSame('Current generation', $user->fresh()->name);
        } else {
            $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'status' => ReservationStatus::PENDING]);
            $result = app(ReservationCancelUseCase::class)->handle($user, (int) $reservation->id);
            $this->assertFalse($result->requiresMembership);
            $this->assertSame(ReservationStatus::CANCELLED, $reservation->fresh()->status);
        }
    }

    public function test_deleted_cached_member_cannot_cancel_reservation(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->load('member');
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'status' => ReservationStatus::PENDING]);
        $member->delete();
        $result = app(ReservationCancelUseCase::class)->handle($user, (int) $reservation->id);
        $this->assertTrue($result->requiresMembership);
        $this->assertNull($result->reservation);
        $this->assertSame(ReservationStatus::PENDING, $reservation->fresh()->status);
    }
}
