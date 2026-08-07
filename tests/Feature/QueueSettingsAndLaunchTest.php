<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueSettingsAndLaunchTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_順番待ち設定_チャネル未選択はバリデーションエラーになること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.settings.queue', ['project_key' => $project->project_key]))
            ->post(route('admin.settings.queue.update', ['project_key' => $project->project_key]), [
                'queue_avg_serve_minutes' => 10,
                'queue_require_membership' => '0',
                'queue_channel_line' => '0',
                'queue_channel_native' => '0',
            ]);

        $response->assertRedirect(route('admin.settings.queue', ['project_key' => $project->project_key]));
        $response->assertSessionHasErrors('queue_channel_line');
    }

    public function test_順番待ち設定_正常に保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.queue.update', ['project_key' => $project->project_key]), [
                'queue_avg_serve_minutes' => 15,
                'queue_require_membership' => '1',
                'queue_channel_line' => '1',
                'queue_channel_native' => '1',
            ]);

        $response->assertRedirect(route('admin.settings.queue', ['project_key' => $project->project_key]));
        $response->assertSessionHas('success');

        $this->assertSame(15, ShopSettingStore::getQueueAvgServeMinutes((int) $project->id));
        $this->assertTrue(ShopSettingStore::getQueueRequireMembership((int) $project->id));
        $this->assertTrue(ShopSettingStore::getQueueChannelLine((int) $project->id));
        $this->assertTrue(ShopSettingStore::getQueueChannelNative((int) $project->id));
    }

    public function test_store_会員必須のとき非会員は403になること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_require_membership', true, 'boolean', (int) $project->id);

        $lineUserId = 'U14141414141414141414141414141414';
        User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => $project->id]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 2]);

        $response->assertStatus(403)
            ->assertJsonPath('error', '会員登録が必要です')
            ->assertJsonPath('requires_membership', true);
        $this->assertSame(0, QueueEntry::count());
    }

    public function test_store_会員必須でも会員なら登録できること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_require_membership', true, 'boolean', (int) $project->id);

        $lineUserId = 'U15151515151515151515151515151515';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 1]);

        $response->assertStatus(201);
        $this->assertSame(1, QueueEntry::count());
    }

    public function test_store_会員必須のときヘッダなし非会員は403になること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_require_membership', true, 'boolean', (int) $project->id);

        $response = $this->post('/api/queue?project_key=default', ['party_size' => 2]);

        $response->assertStatus(403)
            ->assertJsonPath('error', '会員登録が必要です')
            ->assertJsonPath('requires_membership', true);
        $this->assertSame(0, QueueEntry::count());
    }

    public function test_store_会員必須でもi_pad受付は非会員で登録できること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_require_membership', true, 'boolean', (int) $project->id);

        $response = $this->withHeaders(['X-Queue-Client' => 'waiting-system'])
            ->post('/api/queue?project_key=default', ['party_size' => 3]);

        $response->assertStatus(201);
        $this->assertSame(1, QueueEntry::count());
    }

    public function test_queue_open_lin_eのみならリダイレクトすること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_channel_line', true, 'boolean', (int) $project->id);
        ShopSettingStore::setValue('queue_channel_native', false, 'boolean', (int) $project->id);
        ShopSettingStore::setValue('line_liff_id', '2008962885-AbCdEfGh', 'string', (int) $project->id);

        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'qr_token' => 'tokenLineOnly99',
        ]);

        $response = $this->get('/queue/open/default/'.$entry->qr_token);

        $response->assertRedirect();
        $this->assertStringContainsString('liff.line.me', (string) $response->headers->get('Location'));
    }

    public function test_queue_open_両方なら選択画面を出すこと(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_channel_line', true, 'boolean', (int) $project->id);
        ShopSettingStore::setValue('queue_channel_native', true, 'boolean', (int) $project->id);
        ShopSettingStore::setValue('line_liff_id', '2008962885-AbCdEfGh', 'string', (int) $project->id);

        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'qr_token' => 'tokenBothChannels1',
        ]);

        $response = $this->get('/queue/open/default/'.$entry->qr_token);

        $response->assertOk();
        $response->assertSee('アプリを選択', false);
        $response->assertSee('LINEで開く', false);
        $response->assertSee('ネイティブアプリで開く', false);
        $response->assertSee('reggit://queue?', false);
    }

    public function test_queue_open_ネイティブのみならスキーム起動ページになること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('queue_channel_line', false, 'boolean', (int) $project->id);
        ShopSettingStore::setValue('queue_channel_native', true, 'boolean', (int) $project->id);

        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'qr_token' => 'tokenNativeOnly1',
        ]);

        $response = $this->get('/queue/open/default/'.$entry->qr_token);

        $response->assertOk();
        $response->assertSee('アプリを起動しています', false);
        $response->assertSee('reggit://queue?', false);
    }
}
