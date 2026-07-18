<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_store_待ち合わせ登録が作成されること(): void
    {
        $this->createProject();
        $lineUserId = 'U11111111111111111111111111111111';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 2]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'queue_entry' => ['id', 'queue_number', 'party_size', 'status']]);

        $this->assertDatabaseHas('queue_entries', [
            'project_id' => 1,
            'party_size' => 2,
            'status' => QueueStatus::WAITING,
        ]);

        $entry = QueueEntry::first();
        $this->assertNotNull($entry);
        $this->assertNotNull($entry->qr_token);
        $this->assertSame($user->id, $entry->user_id);
        $expectedNumber = (int) now()->format('d') * 100 + 1;
        $this->assertSame($expectedNumber, $entry->queue_number);
        $this->assertSame(sprintf('%04d', $expectedNumber), $entry->formattedQueueNumber());
    }

    public function test_store_受付番号が日と通番で採番されること(): void
    {
        $this->createProject();
        $lineUserId = 'U12121212121212121212121212121212';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $dayPrefix = (int) now()->format('d') * 100;
        QueueEntry::factory()->create([
            'project_id' => 1,
            'queue_number' => $dayPrefix + 12,
            'status' => QueueStatus::WAITING,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 1]);

        $response->assertStatus(201)
            ->assertJsonPath('queue_entry.queue_number', $dayPrefix + 13);
    }

    public function test_store_同一_lin_eユーザーで複数件登録できること(): void
    {
        $this->createProject();
        $lineUserId = 'U22222222222222222222222222222222';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $first = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 2]);
        $first->assertStatus(201);

        $second = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 3]);
        $second->assertStatus(201);

        $this->assertDatabaseCount('queue_entries', 2);
        $this->assertDatabaseHas('queue_entries', [
            'project_id' => 1,
            'user_id' => $user->id,
            'party_size' => 2,
            'status' => QueueStatus::WAITING,
        ]);
        $this->assertDatabaseHas('queue_entries', [
            'project_id' => 1,
            'user_id' => $user->id,
            'party_size' => 3,
            'status' => QueueStatus::WAITING,
        ]);
    }

    public function test_me_現在の待ち合わせ情報を返すこと(): void
    {
        $this->createProject();
        $lineUserId = 'U33333333333333333333333333333333';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'member_id' => $member->id,
            'status' => QueueStatus::WAITING,
            'queue_number' => 3,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get('/api/queue/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id);
    }

    public function test_cancel_キャンセルでステータスが更新されること(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
        ]);

        $response = $this->post("/api/queue/{$entry->id}/cancel?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.status', 'cancelled');

        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'status' => QueueStatus::CANCELLED,
        ]);
    }

    public function test_call_next_次の呼び出しでcalledになること(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
            'queue_number' => 1,
        ]);

        $response = $this->post('/api/queue/call-next?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.status', QueueStatus::CALLED);
    }

    public function test_qr_status_lin_eログイン必須で未ログインなら403を返すこと(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'qr_token' => 'token-no-line',
        ]);

        $response = $this->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(403)
            ->assertJsonPath('error', 'LINEログインが必要です');
    }

    public function test_qr_status_q_rアクセスでユーザーが紐づくこと(): void
    {
        $this->createProject();
        $lineUserId = 'U44444444444444444444444444444444';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-bind',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.qr_token', $entry->qr_token);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_qr_status_同一ユーザーで複数件あっても紐づくこと(): void
    {
        $this->createProject();
        $lineUserId = 'U55555555555555555555555555555555';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => $user->id,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-existing',
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-new',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_qr_status_別ユーザーの_q_rは403を返すこと(): void
    {
        $this->createProject();
        $ownerLineUserId = 'U66666666666666666666666666666666';
        $otherLineUserId = 'U77777777777777777777777777777777';
        $owner = User::factory()->create(['line_user_id' => $ownerLineUserId, 'project_id' => 1]);
        $other = User::factory()->create(['line_user_id' => $otherLineUserId, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => $owner->id,
            'qr_token' => 'token-locked',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $otherLineUserId])
            ->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(403)
            ->assertJsonPath('error', '別のLINEアカウントで登録済みです');
    }

    public function test_qr_status_queue_idパラメータで指定した待ち合わせを返すこと(): void
    {
        $this->createProject();
        $lineUserId = 'U88888888888888888888888888888888';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'qr_token' => null,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get("/api/queue/qr/{$entry->id}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id);
        $entry->refresh();
        $this->assertNotNull($entry->qr_token);
    }
}
