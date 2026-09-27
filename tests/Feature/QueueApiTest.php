<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['push.fcm.enabled' => false]);
    }

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
        $expectedStored = (int) (now()->format('Ymd').'1');
        $expectedDisplay = ((int) now()->format('j')).'1';
        $this->assertSame($expectedStored, $entry->queue_number);
        $this->assertSame($expectedDisplay, $entry->formattedQueueNumber());
        $response->assertJsonPath('queue_entry.formatted_queue_number', $expectedDisplay);
    }

    public function test_store_受付番号が日と通番で採番されること(): void
    {
        $this->createProject();
        $lineUserId = 'U12121212121212121212121212121212';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $prefix = now()->format('Ymd');
        QueueEntry::factory()->create([
            'project_id' => 1,
            'queue_number' => (int) ($prefix.'12'),
            'status' => QueueStatus::WAITING,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 1]);

        $response->assertStatus(201)
            ->assertJsonPath('queue_entry.queue_number', (int) ($prefix.'13'))
            ->assertJsonPath('queue_entry.formatted_queue_number', ((int) now()->format('j')).'13');
    }

    public function test_store_受付番号が日跨ぎで衝突しないこと(): void
    {
        $this->createProject();
        $lineUserId = 'U13131313131313131313131313131313';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $previousDay = now()->subDay();
        QueueEntry::factory()->create([
            'project_id' => 1,
            'queue_number' => (int) ($previousDay->format('Ymd').'199'),
            'status' => QueueStatus::WAITING,
            'created_at' => $previousDay,
            'updated_at' => $previousDay,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->post('/api/queue?project_key=default', ['party_size' => 1]);

        $expectedStored = (int) (now()->format('Ymd').'1');
        $response->assertStatus(201)
            ->assertJsonPath('queue_entry.queue_number', $expectedStored)
            ->assertJsonPath('queue_entry.formatted_queue_number', ((int) now()->format('j')).'1');
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

    public function test_me_券がないときは待ち人数を返すこと(): void
    {
        $this->createProject();
        $lineUserId = 'U35353535353535353535353535353535';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get('/api/queue/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry', null)
            ->assertJsonPath('message', '待ち番号はありません')
            ->assertJsonPath('waiting_count', 0);
    }

    public function test_me_他店舗のqueue_tokenでは券を返さないこと(): void
    {
        $this->createProject();
        $other = Project::create([
            'project_key' => 'other',
            'name' => 'Other Project',
            'description' => 'Test',
            'is_active' => true,
        ]);
        $lineUserId = 'U36363636363636363636363636363636';
        $user = User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        QueueEntry::factory()->create([
            'project_id' => $other->id,
            'user_id' => null,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-other-project',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get('/api/queue/me?project_key=default&queue_token=token-other-project');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry', null)
            ->assertJsonPath('message', '待ち番号はありません');
    }

    public function test_me_liff_stateのqueue_tokenで券を返すこと(): void
    {
        $this->createProject();
        $lineUserId = 'U34343434343434343434343434343434';
        User::factory()->create(['line_user_id' => $lineUserId, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-from-liff-state',
            'queue_number' => 4,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => $lineUserId])
            ->get('/api/queue/me?project_key=default&'.http_build_query([
                'liff.state' => 'queue_token=token-from-liff-state&tab=queue',
            ]));

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id);

        $entry->refresh();
        $this->assertNotNull($entry->user_id);
    }

    public function test_cancel_キャンセルでステータスが更新されること(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
        ]);

        $entry->update(['qr_token' => 'cancel-owner-token']);
        $response = $this->post("/api/queue/{$entry->id}/cancel?project_key=default&queue_token={$entry->qr_token}");

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
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
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

    public function test_list_入店済みは一覧に出ないこと(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $waiting = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
            'queue_number' => 1,
            'guest_name' => 'Waiting Guest',
        ]);
        $entered = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::ENTERED,
            'queue_number' => 2,
            'guest_name' => 'Entered Guest',
            'entered_at' => now(),
        ]);

        $response = $this->get('/api/queue/list?project_key=default');

        $response->assertStatus(200);
        $ids = collect($response->json('entries') ?? [])->pluck('id');
        $this->assertTrue($ids->contains($waiting->id));
        $this->assertFalse($ids->contains($entered->id));
    }

    public function test_recall_呼び出し済みを再呼び出しできること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::CALLED,
            'queue_number' => 3,
            'called_at' => now()->subMinutes(5),
        ]);

        $response = $this->post("/api/queue/{$entry->id}/recall?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.status', QueueStatus::CALLED);

        $entry->refresh();
        $this->assertTrue($entry->called_at->gt(now()->subMinute()));
    }

    public function test_recall_待機中は422になること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
            'queue_number' => 4,
        ]);

        $response = $this->post("/api/queue/{$entry->id}/recall?project_key=default");

        $response->assertStatus(422);
    }

    public function test_enter_呼び出し済みを入店にできること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::CALLED,
            'queue_number' => 5,
            'called_at' => now()->subMinutes(2),
        ]);

        $response = $this->post("/api/queue/{$entry->id}/enter?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.status', QueueStatus::ENTERED);

        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'status' => QueueStatus::ENTERED,
        ]);

        $list = $this->get('/api/queue/list?project_key=default');
        $ids = collect($list->json('entries') ?? [])->pluck('id');
        $this->assertFalse($ids->contains($entry->id));
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

    public function test_qr_status_ネイティブ端末ヘッダがあれば_lin_eなしでも紐づくこと(): void
    {
        $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => 'guest-native-queue-1',
            'project_id' => 1,
            'name' => 'Native User',
        ]);
        $device = UserDevice::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'native-device-queue-1',
            'push_token' => 'push-token-1',
            'last_used_at' => now(),
        ]);
        $device->forceFill(['auth_token' => 'test-device-token'])->save();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-native-bind',
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => 'test-device-token',
            'X-Device-Identifier' => 'native-device-queue-1',
        ])->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.line_linked', true);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
        $this->assertDatabaseHas('user_devices', [
            'project_id' => 1,
            'user_id' => $user->id,
            'device_identifier' => 'native-device-queue-1',
            'push_token' => 'push-token-1',
        ]);
    }

    public function test_qr_status_ネイティブ端末ヘッダのみでもユーザーを作成して紐づくこと(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-native-create',
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => 'test-device-token',
            'X-Device-Identifier' => 'native-device-new-1',
        ])->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.line_linked', true);

        $entry->refresh();
        $this->assertNotNull($entry->user_id);
        $this->assertDatabaseHas('user_devices', [
            'project_id' => 1,
            'user_id' => $entry->user_id,
            'device_identifier' => 'native-device-new-1',
        ]);
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

    public function test_list_ネイティブ連携済みはline_linkedがtrueでlink_channelがappになること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $user = User::factory()->create([
            'line_user_id' => 'guest-native-list-1',
            'project_id' => 1,
            'name' => 'Native List User',
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => $user->id,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-native-list',
        ]);

        $response = $this->get('/api/queue/list?project_key=default');

        $response->assertStatus(200);
        $matched = collect($response->json('entries') ?? [])->firstWhere('id', $entry->id);
        $this->assertNotNull($matched);
        $this->assertTrue($matched['line_linked']);
        $this->assertSame('app', $matched['link_channel']);
    }

    public function test_list_lin_e連携済みはlink_channelがlineになること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $lineUserId = 'Uaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $user = User::factory()->create([
            'line_user_id' => $lineUserId,
            'project_id' => 1,
            'name' => 'Line List User',
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => $user->id,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-line-list',
        ]);

        $response = $this->get('/api/queue/list?project_key=default');

        $response->assertStatus(200);
        $matched = collect($response->json('entries') ?? [])->firstWhere('id', $entry->id);
        $this->assertNotNull($matched);
        $this->assertTrue($matched['line_linked']);
        $this->assertSame('line', $matched['link_channel']);
    }

    public function test_list_未登録はlink_channelがnoneになること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-none-list',
        ]);

        $response = $this->get('/api/queue/list?project_key=default');

        $response->assertStatus(200);
        $matched = collect($response->json('entries') ?? [])->firstWhere('id', $entry->id);
        $this->assertNotNull($matched);
        $this->assertFalse($matched['line_linked']);
        $this->assertSame('none', $matched['link_channel']);
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

    public function test_qr_status_連番idだけでは他人の受付を取得できないこと(): void
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

        $response->assertNotFound();
        $this->assertNull($entry->fresh()->qr_token);
        $this->assertNull($entry->fresh()->user_id);
    }

    public function test_store_ネイティブ会員はuser_idが紐づきline_linkedがtrueになること(): void
    {
        $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => 'guest-native-member-store-1',
            'project_id' => 1,
            'name' => 'Native Member',
            'phone' => '09012345678',
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $device = UserDevice::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'native-device-member-store-1',
            'push_token' => 'push-token-member-store-1',
            'last_used_at' => now(),
        ]);
        $device->forceFill(['auth_token' => 'test-device-token'])->save();

        $response = $this->withHeaders([
            'X-Device-Token' => 'test-device-token',
            'X-Device-Identifier' => 'native-device-member-store-1',
        ])->post('/api/queue?project_key=default', ['party_size' => 2]);

        $response->assertStatus(201)
            ->assertJsonPath('queue_entry.line_linked', true)
            ->assertJsonPath('queue_entry.link_channel', 'app');

        $entry = QueueEntry::query()->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($member->id, $entry->member_id);
    }

    public function test_me_ネイティブ会員はmemberのみ紐付けの券をuser_id補完すること(): void
    {
        $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => 'guest-native-member-me-1',
            'project_id' => 1,
            'name' => 'Native Me',
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $device = UserDevice::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'native-device-member-me-1',
            'push_token' => 'push-token-member-me-1',
            'last_used_at' => now(),
        ]);
        $device->forceFill(['auth_token' => 'test-device-token'])->save();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => $member->id,
            'status' => QueueStatus::WAITING,
            'guest_name' => 'Native Me',
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => 'test-device-token',
            'X-Device-Identifier' => 'native-device-member-me-1',
        ])->get('/api/queue/current?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.line_linked', true);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_list_memberのみ紐付けでもline_linkedがtrueになること(): void
    {
        $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', 1);
        $this->withHeader('X-Store-Token', 'test-store-token');
        $user = User::factory()->create([
            'line_user_id' => 'guest-native-member-list-1',
            'project_id' => 1,
            'name' => 'Native List Member',
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $device = UserDevice::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'native-device-member-list-1',
            'push_token' => 'push-token-member-list-1',
            'last_used_at' => now(),
        ]);
        $device->forceFill(['auth_token' => 'test-device-token'])->save();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => $member->id,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-member-only-list',
        ]);

        $response = $this->get('/api/queue/list?project_key=default');

        $response->assertStatus(200);
        $matched = collect($response->json('entries') ?? [])->firstWhere('id', $entry->id);
        $this->assertNotNull($matched);
        $this->assertTrue($matched['line_linked']);
        $this->assertSame('app', $matched['link_channel']);
    }
}
