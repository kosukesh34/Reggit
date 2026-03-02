<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000011';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_register_端末情報を登録できること(): void
    {
        $project = $this->createProject();

        $response = $this->withHeaders([
            'X-Line-User-Id' => self::LINE_USER_ID,
            'X-Device-Identifier' => 'device-abc',
        ])->post('/api/devices/register?project_key=default', [
            'platform' => 'ios',
            'device_identifier' => 'device-abc',
            'push_token' => 'token-1',
            'os_version' => 'iOS 18.0',
            'app_version' => '1.2.3',
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $user = User::where('project_id', $project->id)->where('line_user_id', self::LINE_USER_ID)->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('user_devices', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'device-abc',
            'push_token' => 'token-1',
            'os_version' => 'iOS 18.0',
            'app_version' => '1.2.3',
        ]);
    }

    public function test_register_同じ端末_i_dなら更新されること(): void
    {
        $this->createProject();

        $headers = [
            'X-Line-User-Id' => self::LINE_USER_ID,
            'X-Device-Identifier' => 'device-xyz',
        ];

        $this->withHeaders($headers)->post('/api/devices/register?project_key=default', [
            'platform' => 'android',
            'device_identifier' => 'device-xyz',
            'push_token' => 'token-old',
            'os_version' => 'Android 15',
            'app_version' => '1.0.0',
        ])->assertStatus(200);

        $this->withHeaders($headers)->post('/api/devices/register?project_key=default', [
            'platform' => 'android',
            'device_identifier' => 'device-xyz',
            'push_token' => 'token-new',
            'os_version' => 'Android 16',
            'app_version' => '1.1.0',
        ])->assertStatus(200);

        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', [
            'device_identifier' => 'device-xyz',
            'push_token' => 'token-new',
            'os_version' => 'Android 16',
            'app_version' => '1.1.0',
        ]);
    }
}
