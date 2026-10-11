<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\DeviceService;
use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\ClaimMemberTransferCodeUseCase;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MemberProfileImageAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['upload'])]
    #[TestWith(['delete'])]
    public function test_request_resolved_before_transfer_cannot_write_after_revocation(string $action): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $settings = ShopSettingStore::getProfileFieldSettings((int) $project->id);
        $settings['smaregi_fields']['profile_image']['enabled'] = true;
        ShopSettingStore::setProfileFieldSettings($settings, (int) $project->id);
        $path = 'member-profile-images/'.$project->id.'/current.png';
        Storage::disk('public')->put($path, 'current image');
        $source = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'guest-auth-source', 'name' => 'Current member', 'profile_image_path' => $path]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $source->id]);
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
        $rejected = false;
        try {
            if ($action === 'upload') {
                app(MemberService::class)->saveProfileImage($resolved, UploadedFile::fake()->image('replacement.png'));
            } else {
                app(MemberService::class)->deleteProfileImage($resolved);
            }
        } catch (ValidationException $exception) {
            $rejected = true;
        }
        $this->assertSame($path, $source->fresh()->profile_image_path);
        Storage::disk('public')->assertExists($path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->assertTrue($rejected);
    }

    #[TestWith(['upload', 'delete'])]
    #[TestWith(['delete', 'delete'])]
    #[TestWith(['upload', 'transfer'])]
    #[TestWith(['delete', 'transfer'])]
    public function test_changed_member_cannot_modify_profile_image(string $action, string $change): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $settings = ShopSettingStore::getProfileFieldSettings((int) $project->id);
        $settings['smaregi_fields']['profile_image']['enabled'] = true;
        ShopSettingStore::setProfileFieldSettings($settings, (int) $project->id);
        $path = 'member-profile-images/'.$project->id.'/current.png';
        Storage::disk('public')->put($path, 'current image');
        $user = User::factory()->create(['project_id' => $project->id, 'profile_image_path' => $path]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->load('member');
        if ($change === 'delete') {
            $member->delete();
        } else {
            $other = User::factory()->create(['project_id' => $project->id]);
            $member->forceFill(['user_id' => $other->id])->save();
        }
        $rejected = false;
        try {
            if ($action === 'upload') {
                app(MemberService::class)->saveProfileImage($user, UploadedFile::fake()->image('replacement.png'));
            } else {
                app(MemberService::class)->deleteProfileImage($user);
            }
        } catch (ValidationException $exception) {
            $rejected = true;
        }
        $this->assertSame($path, $user->fresh()->profile_image_path);
        Storage::disk('public')->assertExists($path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->assertTrue($rejected);
    }
}
