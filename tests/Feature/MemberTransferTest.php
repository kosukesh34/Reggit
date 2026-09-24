<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTransferTest extends TestCase
{
    use RefreshDatabase;

    private const SOURCE_LINE_ID = 'U00000000000000000000000000000021';

    private const DESTINATION_LINE_ID = 'U00000000000000000000000000000022';

    public function test_別端末のコードで会員を引き継げること(): void
    {
        $project = $this->createProject();
        $source = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::SOURCE_LINE_ID,
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $source->id,
        ]);

        $this->registerDevice(self::SOURCE_LINE_ID, 'device-source');
        $issued = $this->withHeaders(['X-Line-User-Id' => self::SOURCE_LINE_ID])
            ->postJson('/api/members/transfer-code?project_key=default');
        $issued->assertOk();
        $code = $issued->json('data.code');
        $this->assertIsString($code);
        $this->assertSame(8, strlen($code));
        $issued->assertJsonPath('data.member_id', $member->id);

        $destinationDevice = $this->registerDevice(self::DESTINATION_LINE_ID, 'device-destination');
        $destination = User::query()->where('line_user_id', self::DESTINATION_LINE_ID)->first();
        $this->assertNotNull($destination);

        $claimed = $this->withHeaders([
            'X-Line-User-Id' => self::DESTINATION_LINE_ID,
            'X-Device-Identifier' => 'device-destination',
            'X-Device-Token' => $destinationDevice,
        ])->postJson('/api/members/transfer?project_key=default', [
            'member_id' => $member->id,
            'code' => $code,
        ]);

        $claimed->assertOk();
        $this->assertSame(self::DESTINATION_LINE_ID, $source->fresh()->line_user_id);
        $this->assertTrue(User::withTrashed()->find($destination->id)->trashed());
        $this->assertSame($source->id, UserDevice::query()->where('device_identifier', 'device-destination')->value('user_id'));
        $this->assertNull(UserDevice::query()->where('device_identifier', 'device-source')->first());

        $this->withHeaders([
            'X-Line-User-Id' => self::DESTINATION_LINE_ID,
            'X-Device-Identifier' => 'device-destination',
            'X-Device-Token' => $destinationDevice,
        ])->postJson('/api/members/transfer?project_key=default', [
            'member_id' => $member->id,
            'code' => $code,
        ])
            ->assertStatus(422);
    }

    public function test_別プロジェクトの引き継ぎコードは使えないこと(): void
    {
        $sourceProject = $this->createProject();
        $otherProject = Project::factory()->create([
            'project_key' => 'other',
            'is_active' => true,
        ]);
        $source = User::factory()->create([
            'project_id' => $sourceProject->id,
            'line_user_id' => self::SOURCE_LINE_ID,
        ]);
        $member = Member::factory()->create([
            'project_id' => $sourceProject->id,
            'user_id' => $source->id,
        ]);
        $this->registerDevice(self::SOURCE_LINE_ID, 'device-source');
        $issued = $this->withHeaders(['X-Line-User-Id' => self::SOURCE_LINE_ID])
            ->postJson('/api/members/transfer-code?project_key=default');
        $issued->assertOk();

        $destinationDevice = $this->registerDevice(self::DESTINATION_LINE_ID, 'device-other', 'other');
        $this->withHeaders([
            'X-Line-User-Id' => self::DESTINATION_LINE_ID,
            'X-Device-Identifier' => 'device-other',
            'X-Device-Token' => $destinationDevice,
        ])->postJson('/api/members/transfer?project_key=other', [
            'member_id' => $member->id,
            'code' => $issued->json('data.code'),
        ])->assertStatus(422);

        $this->assertSame(self::SOURCE_LINE_ID, $source->fresh()->line_user_id);
        $this->assertDatabaseHas('member_transfer_codes', [
            'project_id' => $sourceProject->id,
            'user_id' => $source->id,
            'consumed_at' => null,
        ]);
        $this->assertDatabaseMissing('users', [
            'project_id' => $otherProject->id,
            'line_user_id' => self::SOURCE_LINE_ID,
        ]);
    }

    public function test_管理画面から引き継ぎコードを発行できること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.members.transfer-code', [
                'project_key' => $project->project_key,
                'id' => $member->id,
            ]));

        $response->assertRedirect(route('admin.members.detail', [
            'project_key' => $project->project_key,
            'id' => $member->id,
        ]));
        $response->assertSessionHas('transfer_code');
        $response->assertSessionHas('transfer_member_id', $member->id);
        $this->assertDatabaseHas('member_transfer_codes', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'issued_by_admin_user_id' => $admin->id,
        ]);
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function registerDevice(string $lineUserId, string $deviceIdentifier, string $projectKey = 'default'): string
    {
        $response = $this->withHeaders([
            'X-Line-User-Id' => $lineUserId,
            'X-Device-Identifier' => $deviceIdentifier,
        ])->postJson('/api/devices/register?project_key='.$projectKey, [
            'platform' => 'ios',
            'device_identifier' => $deviceIdentifier,
        ]);
        $response->assertOk();

        return (string) $response->json('device_token');
    }
}
