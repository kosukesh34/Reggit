<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\StampCard;
use App\Models\Store;
use App\Models\User;
use App\Services\Chat\ChatReplyNotifier;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Util\AdminContext;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MemberChatTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000001';

    private Project $project;

    private Store $a;

    private Store $b;

    private string $publicKey;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::create(['project_key' => 'brand', 'name' => 'Brand', 'is_active' => true]);
        $this->a = $this->project->stores()->firstOrFail();
        $this->b = Store::create(['project_id' => $this->project->id, 'store_key' => 'branch', 'name' => '支店B', 'is_active' => true]);
        $this->withHeader('X-Project-Key', 'brand');
        AdminContext::setCurrentProject($this->project);
        ShopSettingStore::setValue('feature_chat_enabled', true, 'boolean', $this->project->id);

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        $this->publicKey = openssl_pkey_get_details($key)['key'];
        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'firebase-chat-');
        file_put_contents($this->credentialsPath, json_encode(['client_email' => 'chat@test.iam.gserviceaccount.com', 'private_key' => $privateKey]));
        config([
            'services.firebase.project_id' => 'reggit-test',
            'services.firebase.credentials' => $this->credentialsPath,
            'services.firebase.web_api_key' => 'web-key',
            'services.firebase.storage_bucket' => 'reggit-test.appspot.com',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    private function member(array $storeIds = [], ?string $lineUserId = null): Member
    {
        $user = User::factory()->create(['project_id' => $this->project->id, 'name' => '山田 花子', ...($lineUserId === null ? [] : ['line_user_id' => $lineUserId])]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $this->project->id]);
        foreach ($storeIds as $storeId) {
            $member->stores()->attach($storeId, ['project_id' => $this->project->id]);
        }

        return $member;
    }

    private function admin(string $role, array $permissions = []): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => $role, 'project_id' => $this->project->id, 'name' => '店長']);
        $admin->projects()->sync([$this->project->id]);
        if ($role !== AdminUser::ROLE_PROJECT_ADMIN) {
            $admin->stores()->sync([$this->a->id => ['project_id' => $this->project->id]]);
        }
        if ($permissions !== []) {
            $admin->syncPermissions($permissions);
        }

        return $admin;
    }

    /** @return array<string, mixed> */
    private function claims(string $token): array
    {
        $decoded = (array) JWT::decode($token, new Key($this->publicKey, 'RS256'));

        return ['uid' => $decoded['uid'], ...json_decode(json_encode($decoded['claims']), true)];
    }

    public function test_会員には本人の会話だけを示すトークンを発行する(): void
    {
        $member = $this->member([$this->a->id]);
        $this->actingAs($member->user);

        $response = $this->postJson('/api/member-chat/token', ['member_id' => 999])->assertOk();

        $this->assertSame($member->id, $response->json('data.member_id'));
        $this->assertSame('reggit-test', $response->json('data.firebase.projectId'));
        $this->assertSame('reggit-test.appspot.com', $response->json('data.firebase.storageBucket'));
        $claims = $this->claims($response->json('data.token'));
        $this->assertSame("m-{$this->project->id}-{$member->id}", $claims['uid']);
        $this->assertSame('member', $claims['role']);
        $this->assertSame((string) $this->project->id, $claims['pid']);
        $this->assertSame((string) $member->id, $claims['mid']);
        $this->assertSame('山田 花子', $claims['label']);
        $this->assertSame([$this->a->id], $claims['stores']);
        $this->assertEqualsWithDelta((time() + 8 * 3600) * 1000, $claims['until'], 5000);
    }

    public function test_会員登録前と機能無効と未設定ではトークンを発行しない(): void
    {
        $guest = User::factory()->create(['project_id' => $this->project->id]);
        $this->actingAs($guest);
        $this->postJson('/api/member-chat/token')->assertForbidden();
        $this->postJson('/api/member-chat/token', [], ['X-Device-Identifier' => 'unknown'])->assertForbidden();

        $member = $this->member();
        $this->actingAs($member->user);
        config(['services.firebase.web_api_key' => '']);
        $this->postJson('/api/member-chat/token')->assertNotFound();

        config(['services.firebase.web_api_key' => 'web-key']);
        // Storage を使わない運用でもチャットは使え、画像だけ無効になる
        config(['services.firebase.storage_bucket' => '']);
        $this->assertArrayNotHasKey('storageBucket', $this->postJson('/api/member-chat/token')->assertOk()->json('data.firebase'));

        ShopSettingStore::setValue('feature_chat_enabled', false, 'boolean', $this->project->id);
        $this->postJson('/api/member-chat/token')->assertNotFound();
    }

    public function test_全店舗の管理者は全会話を担当店舗の管理者は担当店舗分だけを読める(): void
    {
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin');
        $claims = $this->claims($this->postJson('/admin/brand/chats/token')->assertOk()->json('token'));
        $this->assertSame('staff', $claims['role']);
        $this->assertTrue($claims['all_stores']);
        $this->assertSame('店長', $claims['label']);

        $this->actingAs($this->admin(AdminUser::ROLE_ADMIN), 'admin');
        $claims = $this->claims($this->postJson('/admin/brand/chats/token')->assertOk()->json('token'));
        $this->assertFalse($claims['all_stores']);
        $this->assertSame([$this->a->id], $claims['stores']);
    }

    public function test_チャット権限のないスタッフは画面もトークンも使えない(): void
    {
        $this->actingAs($this->admin(AdminUser::ROLE_STAFF, ['members.view']), 'admin');
        $this->get('/admin/brand/chats')->assertForbidden();
        $this->postJson('/admin/brand/chats/token')->assertForbidden();

        $this->actingAs($this->admin(AdminUser::ROLE_STAFF, ['chat.manage']), 'admin');
        $this->get('/admin/brand/chats')->assertOk()->assertSee('adminChatConfig', false);
        $this->postJson('/admin/brand/chats/token')->assertOk();

        ShopSettingStore::setValue('feature_chat_enabled', false, 'boolean', $this->project->id);
        $this->get('/admin/brand/chats')->assertNotFound();
        $this->postJson('/admin/brand/chats/token')->assertNotFound();
    }

    public function test_返信通知は本文を載せず担当外の会員には送らない(): void
    {
        config(['line.channel_access_token' => 'line-token']);
        ShopSettingStore::setValue('feature_chat_liff_enabled', true, 'boolean', $this->project->id);
        $this->mock(PushNotificationService::class)->shouldReceive('sendToUser')->once()
            ->withArgs(fn (int $projectId, int $userId, string $title, string $body, array $data, ?string $collapseId, ?int $badge) => $title === 'お店からメッセージが届きました' && $body === 'チャットを開いてご確認ください' && $badge === 1)
            ->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);
        $line = $this->mock(LineService::class);
        $line->shouldReceive('createTextMessage')->andReturnUsing(fn (string $text) => ['type' => 'text', 'text' => $text]);
        $line->shouldReceive('pushMessage')->once()
            ->withArgs(fn (string $to, array $messages) => $to === self::LINE_USER_ID && str_starts_with($messages[0]['text'], "お店からメッセージが届きました\n"))
            ->andReturn([]);

        $visible = $this->member([$this->a->id], self::LINE_USER_ID);
        $other = $this->member([$this->b->id]);
        $this->actingAs($this->admin(AdminUser::ROLE_ADMIN), 'admin');

        $this->postJson("/admin/brand/chats/{$other->id}/notify")->assertNotFound();
        $this->postJson("/admin/brand/chats/{$visible->id}/notify")->assertOk();
        // 続けて返信しても通知は1分に1回
        $this->postJson("/admin/brand/chats/{$visible->id}/notify")->assertOk();

        // 届いた返信の数はアプリのバッジに使い、読んだら0に戻す
        $this->actingAs($visible->user);
        $this->getJson('/api/member-chat/unread')->assertOk()->assertJsonPath('data.count', 2);
        $this->actingAs($other->user);
        $this->getJson('/api/member-chat/unread')->assertOk()->assertJsonPath('data.count', 0);
        $this->actingAs($visible->user);
        $this->postJson('/api/member-chat/read')->assertOk();
        $this->getJson('/api/member-chat/unread')->assertJsonPath('data.count', 0);
    }

    public function test_チャットを開くと未読数を数え直す(): void
    {
        $member = $this->member();
        Cache::put(ChatReplyNotifier::unreadKey($this->project->id, $member->id), 3);
        $this->actingAs($member->user);
        $this->getJson('/api/member-chat/unread')->assertJsonPath('data.count', 3);
        $this->postJson('/api/member-chat/token')->assertOk();
        $this->getJson('/api/member-chat/unread')->assertJsonPath('data.count', 0);

        ShopSettingStore::setValue('feature_chat_enabled', false, 'boolean', $this->project->id);
        Cache::put(ChatReplyNotifier::unreadKey($this->project->id, $member->id), 3);
        $this->getJson('/api/member-chat/unread')->assertJsonPath('data.count', 0);
    }

    public function test_会話の横に予約とクーポンを出しチャットからクーポンを配布できる(): void
    {
        $member = $this->member([$this->a->id]);
        $upcoming = Reservation::factory()->create(['project_id' => $this->project->id, 'store_id' => $this->a->id, 'user_id' => $member->user_id, 'reserved_at' => now()->addDays(2), 'number_of_people' => 3]);
        Reservation::factory()->create(['project_id' => $this->project->id, 'store_id' => $this->a->id, 'user_id' => $member->user_id, 'status' => 'cancelled']);
        Reservation::factory()->create(['project_id' => $this->project->id, 'store_id' => $this->a->id, 'reserved_at' => now()->addDays(2)]);
        $coupon = Coupon::factory()->create(['project_id' => $this->project->id, 'name' => '10%OFF']);
        Coupon::factory()->create(['project_id' => $this->project->id, 'name' => '停止中', 'is_active' => false]);
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin');

        $context = $this->getJson("/admin/brand/chats/{$member->id}/context")->assertOk();
        $this->assertSame([$upcoming->id], array_column($context->json('reservations'), 'id'));
        $this->assertSame(3, $context->json('reservations.0.people'));
        $this->assertSame(['10%OFF'], array_column($context->json('coupons'), 'name'));

        $this->postJson("/admin/brand/chats/{$member->id}/issue-coupon/{$coupon->id}")->assertOk()->assertJsonPath('name', '10%OFF');
        $this->assertDatabaseHas('member_coupons', ['member_id' => $member->id, 'coupon_id' => $coupon->id]);
    }

    public function test_担当外の会員と権限のないスタッフはクーポンを配布できない(): void
    {
        $other = $this->member([$this->b->id]);
        $coupon = Coupon::factory()->create(['project_id' => $this->project->id]);
        $this->actingAs($this->admin(AdminUser::ROLE_ADMIN), 'admin');
        $this->getJson("/admin/brand/chats/{$other->id}/context")->assertNotFound();
        $this->postJson("/admin/brand/chats/{$other->id}/issue-coupon/{$coupon->id}")->assertNotFound();
        $this->assertDatabaseMissing('member_coupons', ['coupon_id' => $coupon->id]);

        // 会員詳細からの配布と同じく、支店の管理者も担当店舗の会員には配布できる
        $mine = $this->member([$this->a->id]);
        $this->postJson("/admin/brand/chats/{$mine->id}/issue-coupon/{$coupon->id}")->assertOk();

        $this->actingAs($this->admin(AdminUser::ROLE_STAFF, ['chat.manage']), 'admin');
        $this->getJson("/admin/brand/chats/{$mine->id}/context")->assertOk()
            ->assertJsonPath('can_issue_coupons', false)->assertJsonPath('coupons', [])->assertJsonPath('reservations', []);
        $this->postJson("/admin/brand/chats/{$mine->id}/issue-coupon/{$coupon->id}")->assertForbidden();
        $this->assertDatabaseCount('member_coupons', 1);
    }

    public function test_line内に出さない設定ではliffにチャットを出さずlineにも通知しない(): void
    {
        $member = $this->member([$this->a->id], self::LINE_USER_ID);
        $this->actingAs($member->user);

        $tabs = fn (string $query) => collect($this->getJson('/api/v1/app-config?project_key=brand'.$query)->assertOk()->json('tabs'))->pluck('key')->all();
        $this->assertContains('chat', $tabs(''));
        $this->assertNotContains('chat', $tabs('&surface=liff'));
        $this->postJson('/api/member-chat/token', ['surface' => 'liff'])->assertNotFound();
        $this->postJson('/api/member-chat/token')->assertOk();

        $this->mock(PushNotificationService::class)->shouldReceive('sendToUser')->once()->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);
        $this->mock(LineService::class)->shouldNotReceive('pushMessage');
        config(['line.channel_access_token' => 'line-token']);
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin');
        $this->postJson("/admin/brand/chats/{$member->id}/notify")->assertOk();

        ShopSettingStore::setValue('feature_chat_liff_enabled', true, 'boolean', $this->project->id);
        $this->actingAs($member->user);
        $this->assertContains('chat', $tabs('&surface=liff'));
        $this->postJson('/api/member-chat/token', ['surface' => 'liff'])->assertOk();
    }

    public function test_会員の送信をメニューの未読数に出し店舗が読むか返信すると消す(): void
    {
        $this->mock(PushNotificationService::class)->shouldReceive('sendToUser')->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);
        $mine = $this->member([$this->a->id]);
        $other = $this->member([$this->b->id]);
        foreach ([$mine, $other] as $member) {
            $this->actingAs($member->user);
            $this->postJson('/api/member-chat/sent')->assertOk();
        }

        // 全店舗の管理者には2件、担当店舗の管理者には担当店舗の会員の1件だけ
        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin');
        $this->get('/admin/brand/chats')->assertOk()->assertSee('aria-label="未読 2件"', false);
        $branch = $this->admin(AdminUser::ROLE_ADMIN);
        $this->actingAs($branch, 'admin');
        $this->get('/admin/brand/chats')->assertOk()->assertSee('aria-label="未読 1件"', false);

        $this->postJson("/admin/brand/chats/{$other->id}/read")->assertNotFound();
        $this->postJson("/admin/brand/chats/{$mine->id}/read")->assertOk();
        $this->get('/admin/brand/chats')->assertSee('aria-label="未読 0件"', false);

        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin');
        $this->postJson("/admin/brand/chats/{$other->id}/notify")->assertOk();
        $this->get('/admin/brand/chats')->assertSee('aria-label="未読 0件"', false);
    }

    public function test_会話一覧の写真は見られる会員の分だけ返す(): void
    {
        $mine = $this->member([$this->a->id]);
        $mine->user->update(['profile_image_path' => 'profiles/me.jpg']);
        $other = $this->member([$this->b->id]);
        $this->actingAs($this->admin(AdminUser::ROLE_ADMIN), 'admin');

        $members = $this->getJson("/admin/brand/chats/members?ids[]={$mine->id}&ids[]={$other->id}")->assertOk()->json('members');
        $this->assertSame([(string) $mine->id], array_map('strval', array_keys($members)));
        $this->assertStringEndsWith('/storage/profiles/me.jpg', $members[$mine->id]['avatar_url']);
        $this->assertSame('山田 花子', $members[$mine->id]['name']);

        ShopSettingStore::setValue('frontend_logo_url', '/storage/logo.png', 'string', $this->project->id);
        $this->actingAs($mine->user);
        $this->assertSame('/storage/logo.png', $this->postJson('/api/member-chat/token')->json('data.store_icon_url'));
    }

    public function test_チャットからスタンプを付与でき担当外と権限のないスタッフは付与できない(): void
    {
        $card = StampCard::create(['project_id' => $this->project->id, 'name' => 'コーヒーカード', 'required_stamps' => 10, 'is_active' => true]);
        $mine = $this->member([$this->a->id]);
        $other = $this->member([$this->b->id]);

        $this->actingAs($this->admin(AdminUser::ROLE_PROJECT_ADMIN), 'admin');
        $this->getJson("/admin/brand/chats/{$mine->id}/context")->assertOk()
            ->assertJsonPath('stamp_cards.0.name', 'コーヒーカード')->assertJsonPath('stamp_cards.0.current', 0);
        $this->postJson("/admin/brand/chats/{$mine->id}/grant-stamps", ['stamp_card_id' => $card->id, 'count' => 2])->assertOk()
            ->assertJsonPath('current', 2)->assertJsonPath('required', 10);
        $this->postJson("/admin/brand/chats/{$mine->id}/grant-stamps", ['stamp_card_id' => $card->id, 'count' => 0])->assertUnprocessable();
        $this->postJson("/admin/brand/chats/{$mine->id}/grant-stamps", ['stamp_card_id' => $card->id, 'count' => 21])->assertUnprocessable();

        // 支店の管理者は担当店舗の会員だけ（会員詳細の調整と同じ）
        $this->actingAs($this->admin(AdminUser::ROLE_ADMIN), 'admin');
        $this->postJson("/admin/brand/chats/{$other->id}/grant-stamps", ['stamp_card_id' => $card->id, 'count' => 1])->assertNotFound();
        $this->postJson("/admin/brand/chats/{$mine->id}/grant-stamps", ['stamp_card_id' => $card->id, 'count' => 1])->assertOk()->assertJsonPath('current', 3);

        $this->actingAs($this->admin(AdminUser::ROLE_STAFF, ['chat.manage']), 'admin');
        $this->postJson("/admin/brand/chats/{$mine->id}/grant-stamps", ['stamp_card_id' => $card->id, 'count' => 1])->assertForbidden();
        $this->assertSame(3, (int) $mine->memberStamps()->where('stamp_card_id', $card->id)->value('current_stamps'));
    }
}
