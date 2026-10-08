<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Project;
use App\Models\User;
use App\Services\CancelledAnnouncementIdStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['id' => 1],
            ['project_key' => 'default', 'name' => 'Default', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_index_returns_ordered_images_and_legacy_thumbnail(): void
    {
        $project = $this->createProject();
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'image_paths' => ['announcement-private/first.png', 'announcement-private/second.png'],
        ]);
        $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/announcements')
            ->assertOk()
            ->assertJsonPath('data.announcements.0.image_urls', $announcement->imageUrls())
            ->assertJsonPath('data.announcements.0.image_url', $announcement->imageUrls()[0]);
    }

    public function test_index_お知らせ一覧を返すこと(): void
    {
        $project = $this->createProject();
        Announcement::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['announcements']]);
    }

    public function test_index_ユーザーごとの既読状態が含まれること(): void
    {
        $project = $this->createProject();
        $lineUserId = 'U00000000000000000000000000000003';
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $announcement = Announcement::factory()->create(['project_id' => $project->id]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', $lineUserId)
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonPath('data.announcements.0.is_read', true);
    }

    public function test_mark_read_既読レコードが作成されること(): void
    {
        $project = $this->createProject();
        $announcement = Announcement::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', 'line-user-mark-read')
            ->post("/api/v1/announcements/{$announcement->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('announcement_reads', [
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
        ]);
    }

    public function test_cancelled_ids_取消し済みお知らせidを返すこと(): void
    {
        $project = $this->createProject();
        $store = app(CancelledAnnouncementIdStore::class);
        $store->remember((int) $project->id, 101, '削除1');
        $store->remember((int) $project->id, 202, '削除2');

        $response = $this->get('/api/v1/announcements/cancelled-ids?project_key='.$project->project_key);

        $response->assertStatus(200)
            ->assertJsonPath('cancelled_ids', [101, 202])
            ->assertJsonPath('items.0.id', 101)
            ->assertJsonPath('items.0.title', '削除1');
    }
}
