<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Member;
use App\Models\MemberTransferCode;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceService;
use App\Services\Member\AuthenticatedDeviceResolver;
use App\UseCases\Member\ClaimMemberTransferCodeUseCase;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class MemberTransferConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_native_device_cannot_be_reassigned_after_its_principal_was_transferred(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $sourceA = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-source-a']);
        $sourceB = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-source-b']);
        $memberA = Member::factory()->create(['project_id' => $project->id, 'user_id' => $sourceA->id]);
        $memberB = Member::factory()->create(['project_id' => $project->id, 'user_id' => $sourceB->id]);
        $issuer = app(IssueMemberTransferCodeUseCase::class);
        $codeA = $issuer->handle($sourceA, null)->code;
        $codeB = $issuer->handle($sourceB, null)->code;
        $destination = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-native-destination']);
        $device = UserDevice::create(['project_id' => $project->id, 'user_id' => $destination->id, 'platform' => 'ios', 'device_identifier' => 'native-transfer-race']);
        $device->forceFill(['auth_token' => 'test-native-transfer-token'])->save();
        $request = Request::create('/api/members/transfer', 'POST', [], [], [], [
            'HTTP_X_DEVICE_IDENTIFIER' => $device->device_identifier,
            'HTTP_X_DEVICE_TOKEN' => 'test-native-transfer-token',
        ]);
        $staleUser = $destination->fresh();
        $staleDevice = app(AuthenticatedDeviceResolver::class)->resolve($request, (int) $destination->id);
        $this->assertNotNull($staleDevice);
        $claim = app(ClaimMemberTransferCodeUseCase::class);
        $claim->handle($destination->fresh(), $device->fresh(), $codeB, (int) $memberB->id);
        $this->assertSame((int) $sourceB->id, (int) $device->fresh()->user_id);
        $this->assertTrue(User::withTrashed()->findOrFail($destination->id)->trashed());
        $rejected = false;
        try {
            $claim->handle($staleUser, $staleDevice, $codeA, (int) $memberA->id);
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
        }
        $this->assertSame((int) $sourceB->id, (int) $device->fresh()->user_id);
        $this->assertNull(MemberTransferCode::where('code_hash', hash('sha256', $codeA))->firstOrFail()->consumed_at);
        $this->assertTrue($rejected);
    }

    #[TestWith(['authentication_version'])]
    #[TestWith(['device_owner'])]
    #[TestWith(['device_token'])]
    #[TestWith(['device_deleted'])]
    #[TestWith(['member_created'])]
    public function test_claim_rechecks_the_resolved_principal_and_device(string $change): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $source = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $source->id]);
        $code = app(IssueMemberTransferCodeUseCase::class)->handle($source, null)->code;
        $actor = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-native-actor']);
        $actor->load('member');
        $device = UserDevice::create(['project_id' => $project->id, 'user_id' => $actor->id, 'platform' => 'ios', 'device_identifier' => 'native-principal-check']);
        $device->forceFill(['auth_token' => 'test-principal-token'])->save();
        $request = Request::create('/api/members/transfer', 'POST', [], [], [], [
            'HTTP_X_DEVICE_IDENTIFIER' => $device->device_identifier, 'HTTP_X_DEVICE_TOKEN' => 'test-principal-token',
        ]);
        $resolvedDevice = app(AuthenticatedDeviceResolver::class)->resolve($request, (int) $actor->id);
        $this->assertNotNull($resolvedDevice);
        if ($change === 'authentication_version') {
            $actor->fresh()->increment('authentication_version');
        } elseif ($change === 'device_owner') {
            $other = User::factory()->create(['project_id' => $project->id]);
            app(DeviceService::class)->register($other, ['device_identifier' => $device->device_identifier, 'platform' => 'ios'], 'test-principal-token');
        } elseif ($change === 'device_token') {
            $device->forceFill(['auth_token' => 'rotated-principal-token'])->save();
        } elseif ($change === 'device_deleted') {
            $device->delete();
        } else {
            Member::factory()->create(['project_id' => $project->id, 'user_id' => $actor->id]);
        }
        $expectedDevice = UserDevice::find($device->id);
        $rejected = false;
        try {
            app(ClaimMemberTransferCodeUseCase::class)->handle($actor, $resolvedDevice, $code, (int) $member->id);
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
        }
        $this->assertTrue($rejected);
        $this->assertNull(MemberTransferCode::where('code_hash', hash('sha256', $code))->firstOrFail()->consumed_at);
        $this->assertSame(0, (int) $source->fresh()->authentication_version);
        if ($expectedDevice === null) {
            $this->assertDatabaseMissing('user_devices', ['id' => $device->id]);
        } else {
            $saved = $expectedDevice->fresh();
            $this->assertSame((int) $expectedDevice->user_id, (int) $saved->user_id);
            $this->assertSame($expectedDevice->auth_token, $saved->auth_token);
        }
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_issuing_code_rechecks_member_identity_without_revoking_admin_authority(bool $admin): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $issuer = app(IssueMemberTransferCodeUseCase::class);
        $old = $issuer->handle($user, null)->code;
        $user->fresh()->increment('authentication_version');
        $adminId = $admin ? AdminUser::factory()->create(['project_id' => $project->id])->id : null;
        $rejected = false;
        try {
            $issuer->handle($user, $adminId);
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
        }
        $this->assertSame(! $admin, $rejected);
        $consumed = MemberTransferCode::where('code_hash', hash('sha256', $old))->firstOrFail()->consumed_at;
        if ($admin) {
            $this->assertNotNull($consumed);
        } else {
            $this->assertNull($consumed);
        }
        $this->assertSame(1, MemberTransferCode::where('user_id', $user->id)->whereNull('consumed_at')->count());
    }

    public function test_failed_replacement_does_not_consume_the_existing_transfer_code(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $issuer = app(IssueMemberTransferCodeUseCase::class);
        $code = $issuer->handle($user, null)->code;
        Event::listen('eloquent.creating: '.MemberTransferCode::class, function (): void {
            throw new RuntimeException('transfer code persistence failed');
        });
        $failed = false;
        try {
            $issuer->handle($user, null);
        } catch (RuntimeException $exception) {
            $this->assertSame('transfer code persistence failed', $exception->getMessage());
            $failed = true;
        }
        $this->assertTrue($failed);
        $this->assertNull(MemberTransferCode::where('code_hash', hash('sha256', $code))->firstOrFail()->consumed_at);
    }
}
