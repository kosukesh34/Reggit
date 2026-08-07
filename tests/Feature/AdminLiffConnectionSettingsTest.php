<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLiffConnectionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::factory()->create([
            'project_key' => 'store-liff',
            'name' => 'LIFF店舗',
            'is_active' => true,
        ]);
    }

    private function createAdmin(int $projectId): AdminUser
    {
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $projectId,
        ]);
        $admin->projects()->sync([$projectId]);

        return $admin;
    }

    public function test_liff設定画面が表示されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin((int) $project->id);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.liff-connection', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('LIFF設定');
    }

    public function test_liff設定を更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin((int) $project->id);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-connection.update', ['project_key' => $project->project_key]), [
                'line_liff_id' => '2000000000-abcdef',
                'line_liff_url' => 'https://miniapp.line.me/2000000000-abcdef',
                'line_channel_access_token' => 'test-channel-access-token',
                'line_channel_secret' => 'test-channel-secret',
            ]);

        $response->assertRedirect(route('admin.settings.liff-connection', ['project_key' => $project->project_key]));
        $this->assertSame('2000000000-abcdef', ShopSettingStore::getLineLiffId((int) $project->id));
        $this->assertSame('https://miniapp.line.me/2000000000-abcdef', ShopSettingStore::getLineLiffUrl((int) $project->id));
        $this->assertSame('test-channel-access-token', ShopSettingStore::getLineChannelAccessToken((int) $project->id));
        $this->assertSame('test-channel-secret', ShopSettingStore::getLineChannelSecret((int) $project->id));
    }

    public function test_liff設定画面に_messaging_ap_i項目があること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin((int) $project->id);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.liff-connection', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('チャネルアクセストークン');
        $response->assertSee('Messaging API');
    }
}
