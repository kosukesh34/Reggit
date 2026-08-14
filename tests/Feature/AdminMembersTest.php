<?php

namespace Tests\Feature;

use App\Enums\MemberNotificationPreference;
use App\Enums\MemberStatus;
use App\Enums\MemberVisitSource;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberRankHistory;
use App\Models\MemberTag;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Models\WalletPassRegistration;
use App\Services\AppleWalletPushService;
use App\Services\GoogleWalletPassService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class AdminMembersTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

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

    public function test_members_会員一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => '一覧テスト']);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('タグ');
        $response->assertSee('会員名');
        $response->assertSee('ID');
        $response->assertSee('詳細');
        $response->assertSee('編集');
        $response->assertDontSee('delete-form', false);
        $response->assertSee('ランク');
        $response->assertDontSee('ステータス');
    }

    public function test_members_有効会員のみ表示し検索と並び替えができること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $goldUser = User::factory()->create([
            'project_id' => $project->id,
            'name' => 'ゴールド会員',
            'email' => 'gold-search@example.com',
        ]);
        $gold = Member::factory()->create([
            'user_id' => $goldUser->id,
            'project_id' => $project->id,
            'rank' => 'gold',
            'points' => 200,
            'status' => MemberStatus::ACTIVE,
        ]);
        $bronzeUser = User::factory()->create(['project_id' => $project->id, 'name' => 'ブロンズ会員']);
        Member::factory()->create([
            'user_id' => $bronzeUser->id,
            'project_id' => $project->id,
            'rank' => 'bronze',
            'points' => 10,
            'status' => MemberStatus::ACTIVE,
        ]);
        $silverUser = User::factory()->create(['project_id' => $project->id, 'name' => 'シルバー会員']);
        Member::factory()->create([
            'user_id' => $silverUser->id,
            'project_id' => $project->id,
            'rank' => 'silver',
            'points' => 50,
            'status' => MemberStatus::ACTIVE,
        ]);
        $inactiveUser = User::factory()->create(['project_id' => $project->id, 'name' => '無効会員']);
        Member::factory()->create([
            'user_id' => $inactiveUser->id,
            'project_id' => $project->id,
            'status' => MemberStatus::INACTIVE,
        ]);
        $tag = MemberTag::create([
            'project_id' => $project->id,
            'name' => '一覧VIP',
            'color' => '#0d9488',
        ]);
        $gold->tags()->attach($tag->id);

        $list = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', ['project_key' => $project->project_key]));
        $list->assertStatus(200);
        $list->assertSee('ゴールド会員');
        $list->assertSee('ブロンズ会員');
        $list->assertDontSee('無効会員');

        $byRank = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', [
                'project_key' => $project->project_key,
                'rank' => 'gold',
            ]));
        $byRank->assertSee('ゴールド会員');
        $byRank->assertDontSee('ブロンズ会員');

        $byKeyword = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', [
                'project_key' => $project->project_key,
                'keyword' => 'gold-search@example.com',
            ]));
        $byKeyword->assertSee('ゴールド会員');
        $byKeyword->assertDontSee('ブロンズ会員');

        $byTag = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', [
                'project_key' => $project->project_key,
                'tag_id' => $tag->id,
            ]));
        $byTag->assertSee('ゴールド会員');
        $byTag->assertDontSee('ブロンズ会員');

        $sorted = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', [
                'project_key' => $project->project_key,
                'sort' => 'points',
                'dir' => 'desc',
            ]));
        $sorted->assertSeeInOrder(['ゴールド会員', 'シルバー会員', 'ブロンズ会員']);

        $byRankLevel = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', [
                'project_key' => $project->project_key,
                'sort' => 'rank',
                'dir' => 'desc',
            ]));
        $byRankLevel->assertSeeInOrder(['ゴールド会員', 'シルバー会員', 'ブロンズ会員']);

        $csv = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.export', [
                'project_key' => $project->project_key,
                'rank' => 'gold',
            ]));
        $csv->assertOk();
        $csvContent = $csv->streamedContent();
        $this->assertStringContainsString('ゴールド会員', $csvContent);
        $this->assertStringContainsString('最終来店', $csvContent);
        $this->assertStringNotContainsString('ブロンズ会員', $csvContent);
        $this->assertStringNotContainsString('無効会員', $csvContent);
        $this->assertStringNotContainsString('ステータス', $csvContent);
    }

    public function test_members_detail_and_edit_詳細と編集にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'name' => '登録名太郎',
            'line_name' => 'LINE表示名花子',
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $detail = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.detail', ['project_key' => $project->project_key, 'id' => $member->id]));
        $detail->assertStatus(200);
        $detail->assertSee('登録名太郎');
        $detail->assertSee('LINE名');
        $detail->assertSee('LINE表示名花子');
        $detail->assertSee('ランクアップ履歴');
        $detail->assertSee('クーポン使用履歴');
        $detail->assertSee('来店履歴');
        $detail->assertSee('ポイント履歴');
        $detail->assertSee('購入履歴');
        $detail->assertSee('member-history-shell');
        $detail->assertSee('data-history-tab-btn', false);

        $edit = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.edit', ['project_key' => $project->project_key, 'id' => $member->id]));
        $edit->assertStatus(200);
    }

    public function test_members_detail_履歴の検索とページネーションができること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        MemberVisit::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'source' => MemberVisitSource::ADMIN,
            'visited_at' => now()->subDay(),
            'note' => '検索対象メモ',
        ]);
        MemberVisit::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'source' => MemberVisitSource::ADMIN,
            'visited_at' => now()->subDays(2),
            'note' => '別メモ',
        ]);

        for ($i = 0; $i < 21; $i++) {
            $tx = PointTransaction::create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'type' => 'earned',
                'points' => 1,
                'description' => 'pt-'.$i,
            ]);
            $tx->forceFill([
                'created_at' => now()->subMinutes(21 - $i),
                'updated_at' => now()->subMinutes(21 - $i),
            ])->save();
        }

        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '利用テストクーポン']);
        CouponRedemption::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'member_id' => $member->id,
            'used_at' => now(),
        ]);
        MemberRankHistory::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'from_rank' => 'bronze',
            'to_rank' => 'gold',
            'from_rank_label' => 'ブロンズ',
            'to_rank_label' => 'ゴールド',
            'achieved_at' => now(),
        ]);

        $detail = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.detail', [
                'project_key' => $project->project_key,
                'id' => $member->id,
            ]));
        $detail->assertStatus(200);
        $detail->assertSee('member-history-panel');
        $detail->assertSee('data-history-toggle', false);
        $detail->assertSee('data-history-input', false);
        $detail->assertSee('data-history-from', false);
        $detail->assertSee('pt-20');
        $detail->assertDontSee('pt-0');

        $search = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.members.history', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'section' => 'visits',
                'q' => '検索対象',
            ]));
        $search->assertStatus(200);
        $search->assertJsonPath('total', 1);
        $this->assertStringContainsString('検索対象メモ', $search->json('html'));
        $this->assertStringNotContainsString('別メモ', $search->json('html'));

        $byDate = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.members.history', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'section' => 'visits',
                'from' => now()->subDay()->format('Y-m-d'),
                'to' => now()->subDay()->format('Y-m-d'),
            ]));
        $byDate->assertStatus(200);
        $byDate->assertJsonPath('total', 1);
        $this->assertStringContainsString('検索対象メモ', $byDate->json('html'));
        $this->assertStringNotContainsString('別メモ', $byDate->json('html'));

        $page2 = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.members.history', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'section' => 'points',
                'page' => 3,
            ]));
        $page2->assertStatus(200);
        $page2->assertJsonPath('total', 21);
        $this->assertStringContainsString('pt-0', $page2->json('html'));

        $redemptions = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.members.history', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'section' => 'redemptions',
            ]));
        $redemptions->assertStatus(200);
        $this->assertStringContainsString('利用テストクーポン', $redemptions->json('html'));

        $rank = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.members.history', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'section' => 'rank',
                'q' => 'ゴールド',
            ]));
        $rank->assertStatus(200);
        $rank->assertJsonPath('total', 1);
        $this->assertStringContainsString('ゴールド', $rank->json('html'));
    }

    public function test_members_detail_購入履歴は最新10件に制限されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        for ($i = 0; $i < 11; $i++) {
            $order = Order::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'order_number' => sprintf('hist-%02d', $i),
                'order_type' => 'take_out',
                'total_amount' => 100 + $i,
                'status' => 'completed',
                'payment_method' => 'cash',
            ]);
            $order->forceFill([
                'created_at' => now()->subMinutes(11 - $i),
                'updated_at' => now()->subMinutes(11 - $i),
            ])->save();
        }

        $detail = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.detail', [
                'project_key' => $project->project_key,
                'id' => $member->id,
            ]));
        $detail->assertStatus(200);
        $detail->assertSee('購入履歴');
        $detail->assertSee('hist-10');
        $detail->assertDontSee('hist-00');
        $detail->assertSee('残りの1件を見る');
        $detail->assertSee('すべての注文');

        $orders = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders', [
                'project_key' => $project->project_key,
                'user_id' => $user->id,
            ]));
        $orders->assertStatus(200);
        $orders->assertSee('hist-00');
        $orders->assertSee('hist-10');
        $orders->assertSee('会員の絞り込みを解除');
    }

    public function test_members_delete_会員を削除できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.members.delete', ['project_key' => $project->project_key, 'id' => $member->id]));

        $response->assertRedirect(route('admin.members', ['project_key' => $project->project_key]));
        $this->assertSoftDeleted('members', ['id' => $member->id]);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_管理画面でポイントを直接指定して更新できること(): void
    {
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'wallet_pass.enabled' => true,
            'wallet_pass.type_id' => 'pass.IDNect',
            'wallet_pass.web_service_url' => 'https://example.test/api/apple-wallet',
            'google_wallet.enabled' => true,
        ]);

        $project = $this->createProject();
        ShopSettingStore::setValue('smaregi_contract_id', 'test_contract_admin', 'string', (int) $project->id);
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => '大牟田 テスト']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '93',
            'member_number' => 'M-ADMIN-1',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 10, '初期');

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, 'test_contract_admin') && str_contains($url, '/point/add')) {
                return Http::response(['customerId' => '93', 'point' => '490'], 200);
            }
            if (str_contains($url, 'test_contract_admin') && str_contains($url, '/pos/customers/93')) {
                return Http::response(['customerId' => '93'], 200);
            }

            return Http::response([], 404);
        });

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldReceive('syncLoyaltyObjectForMember')->atLeast()->once()->andReturnTrue();
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldReceive('notifyMemberUpdated')->atLeast()->once()->andReturnTrue();
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'name' => '大牟田 テスト',
                'member_number' => 'M-ADMIN-1',
                'status' => 'active',
                'notification_preference' => MemberNotificationPreference::ALL,
                'point_set' => 500,
            ]);

        $response->assertRedirect();
        $member->refresh();
        $this->assertSame(500, $member->points);
        $this->assertSame(500, $member->getTotalPointsFromTransactions());
    }

    public function test_取引なしでカラムだけ残っている会員でもポイント直接指定が正しく反映されること(): void
    {
        config([
            'wallet_pass.enabled' => true,
            'wallet_pass.type_id' => 'pass.IDNect',
            'wallet_pass.web_service_url' => 'https://example.test/api/apple-wallet',
            'google_wallet.enabled' => true,
        ]);

        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'member_number' => 'M-ADMIN-LEDGER',
            'points' => 192,
        ]);

        WalletPassRegistration::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
            'serial_number' => 'member-'.$member->id,
            'device_library_identifier' => 'device-test-ledger',
            'push_token' => str_repeat('a', 64),
        ]);

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldReceive('syncLoyaltyObjectForMember')->atLeast()->once()->andReturnTrue();
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldReceive('notifyMemberUpdated')->atLeast()->once()->andReturnTrue();
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'name' => 'テスト会員',
                'member_number' => 'M-ADMIN-LEDGER',
                'status' => 'active',
                'notification_preference' => MemberNotificationPreference::ALL,
                'point_set' => 100,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $member->refresh();
        $this->assertSame(100, $member->points);
        $this->assertSame(100, $member->getTotalPointsFromTransactions());
        $this->assertDatabaseHas('wallet_pass_updates', [
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
        ]);
    }

    public function test_カラムだけ多いポイント残高は台帳に補完されること(): void
    {
        $member = Member::factory()->create(['points' => 250]);
        $this->assertSame(0, $member->getTotalPointsFromTransactions());

        $member->syncPointsLedger();
        $member->refresh();

        $this->assertSame(250, $member->points);
        $this->assertSame(250, $member->getTotalPointsFromTransactions());
    }

    public function test_members_detail_履歴セクションが表示されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.detail', ['project_key' => $project->project_key, 'id' => $member->id]));

        $response->assertStatus(200);
        $response->assertSee('来店履歴');
        $response->assertSee('ポイント履歴');
        $response->assertSee('クーポン発行履歴');
        $response->assertSee('クーポン使用履歴');
        $response->assertSee('ランクアップ履歴');
        $response->assertSee('スタンプ付与履歴');
        $response->assertDontSee('>配布する<', false);
        $response->assertDontSee('スタンプを更新');
    }

    public function test_編集からスタンプを加算減算でき履歴が残ること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $stampCard = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
        ]);

        $add = $this->actingAs($admin, 'admin')
            ->post(route('admin.members.adjust-stamps', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'stamp_card_id' => $stampCard->id,
                'stamp_add' => 3,
                'stamp_subtract' => 0,
                'stamp_description' => 'テスト加算',
            ]);
        $add->assertRedirect(route('admin.members.edit', ['project_key' => $project->project_key, 'id' => $member->id]));
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 3,
        ]);
        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'delta' => 3,
            'balance_after' => 3,
        ]);

        $subtract = $this->actingAs($admin, 'admin')
            ->post(route('admin.members.adjust-stamps', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'stamp_card_id' => $stampCard->id,
                'stamp_add' => 0,
                'stamp_subtract' => 1,
                'stamp_description' => 'テスト減算',
            ]);
        $subtract->assertRedirect();
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 2,
        ]);
        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'delta' => -1,
            'balance_after' => 2,
        ]);
    }

    public function test_未使用クーポンは削除でき使用済みは削除できないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $unused = MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);
        $used = MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_USED,
            'used_at' => now(),
        ]);

        $deleteUnused = $this->actingAs($admin, 'admin')
            ->delete(route('admin.members.delete-coupon', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'memberCouponId' => $unused->id,
            ]));
        $deleteUnused->assertRedirect(route('admin.members.edit', ['project_key' => $project->project_key, 'id' => $member->id]));
        $this->assertDatabaseMissing('member_coupons', ['id' => $unused->id]);

        $deleteUsed = $this->actingAs($admin, 'admin')
            ->delete(route('admin.members.delete-coupon', [
                'project_key' => $project->project_key,
                'id' => $member->id,
                'memberCouponId' => $used->id,
            ]));
        $deleteUsed->assertRedirect();
        $deleteUsed->assertSessionHas('error');
        $this->assertDatabaseHas('member_coupons', ['id' => $used->id]);
    }

    public function test_来店を手動登録できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'last_visited_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.members.record-visit', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'visited_at' => '2026-07-18 15:30',
                'note' => '手動来店',
            ]);

        $response->assertRedirect(route('admin.members.edit', ['project_key' => $project->project_key, 'id' => $member->id]));
        $this->assertDatabaseHas('member_visits', [
            'member_id' => $member->id,
            'source' => MemberVisitSource::ADMIN,
            'note' => '手動来店',
        ]);
        $member->refresh();
        $this->assertSame('2026-07-18 15:30', $member->last_visited_at->format('Y-m-d H:i'));

        $detail = $this->actingAs($admin, 'admin')
            ->get(route('admin.members.detail', [
                'project_key' => $project->project_key,
                'id' => $member->id,
            ]));
        $detail->assertStatus(200);
        $detail->assertSee('15:30');
        $detail->assertSee('来店回数');
        $detail->assertSee('1<span>回</span>', false);
    }

    public function test_不正なメールでも会員プロフィール更新が失敗しないこと(): void
    {
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
        ]);

        $project = $this->createProject();
        ShopSettingStore::setValue('smaregi_contract_id', 'test_contract_admin', 'string', (int) $project->id);
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id, 'email' => 'old@example.com']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '91',
            'member_number' => 'M-ADMIN-2',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, 'test_contract_admin') && str_contains($url, '/pos/customers/91')) {
                return Http::response(['customerId' => '91'], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'name' => '更新後 名前',
                'email' => '',
                'member_number' => 'M-ADMIN-2',
                'status' => 'active',
                'notification_preference' => MemberNotificationPreference::ALL,
            ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNull($user->email);
        $this->assertSame('更新後 名前', $user->name);
    }
}
