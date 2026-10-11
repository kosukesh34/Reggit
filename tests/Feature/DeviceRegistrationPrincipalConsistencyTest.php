<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceService;
use App\Services\UserService;
use App\UseCases\Device\Data\DeviceRegisterInput;
use App\UseCases\Device\DeviceRegisterUseCase;
use App\UseCases\Member\ClaimMemberTransferCodeUseCase;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\Util\AdminContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DeviceRegistrationPrincipalConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_resolved_before_transfer_cannot_restore_the_revoked_device(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $source = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-source-registration']);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $source->id]);
        $oldDevice = app(DeviceService::class)->register($source, ['platform' => 'ios', 'device_identifier' => 'old-registration-device']);
        $oldToken = $oldDevice->auth_token;
        $request = Request::create('/api/devices/register', 'POST', [], [], [], [
            'HTTP_X_DEVICE_IDENTIFIER' => $oldDevice->device_identifier,
            'HTTP_X_DEVICE_TOKEN' => $oldToken,
        ]);
        $resolved = app(UserService::class)->getOrCreateUser($request);
        $this->assertSame((int) $source->id, (int) $resolved->id);
        $code = app(IssueMemberTransferCodeUseCase::class)->handle($source, null)->code;
        $destination = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-destination-registration']);
        $newDevice = app(DeviceService::class)->register($destination, ['platform' => 'ios', 'device_identifier' => 'new-registration-device']);
        app(ClaimMemberTransferCodeUseCase::class)->handle($destination, $newDevice, $code, (int) $member->id);
        $this->assertNull(UserDevice::find($oldDevice->id));
        $this->assertSame(1, (int) $source->fresh()->authentication_version);
        $rejected = false;
        try {
            app(DeviceRegisterUseCase::class)->handle(new DeviceRegisterInput($resolved, ['platform' => 'ios', 'device_identifier' => 'old-registration-device'], $oldToken));
        } catch (AuthenticationException $exception) {
            $rejected = true;
        }
        $this->assertDatabaseMissing('user_devices', ['project_id' => $project->id, 'device_identifier' => 'old-registration-device']);
        $this->assertTrue($rejected);
        $this->assertSame((int) $source->id, (int) $newDevice->fresh()->user_id);
    }

    public function test_deleted_resolved_user_cannot_create_or_update_a_device(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $device = app(DeviceService::class)->register($user, ['platform' => 'ios', 'device_identifier' => 'deleted-user-device']);
        $resolved = $user->fresh();
        $user->delete();
        foreach (['deleted-user-device', 'deleted-user-new-device'] as $identifier) {
            $rejected = false;
            try {
                app(DeviceService::class)->register($resolved, ['platform' => 'android', 'device_identifier' => $identifier], $device->auth_token);
            } catch (AuthenticationException $exception) {
                $rejected = true;
            }
            $this->assertTrue($rejected);
        }
        $this->assertSame('ios', $device->fresh()->platform);
        $this->assertDatabaseMissing('user_devices', ['device_identifier' => 'deleted-user-new-device']);
    }

    public function test_current_authentication_generation_can_still_register_devices(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'authentication_version' => 1]);
        $device = app(DeviceService::class)->register($user, ['platform' => 'ios', 'device_identifier' => 'current-generation-device']);
        $this->assertSame((int) $user->id, (int) $device->user_id);
        $this->assertTrue($device->authenticates($device->auth_token));
    }
}
