<?php

namespace Tests\Feature;

use App\Enums\CouponApplyConditionDivision;
use App\Enums\CouponCombineDivision;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Enums\OrderStatus;
use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminCouponsTest extends TestCase
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

    public function test_coupons_クーポン一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('作成')
            ->assertSee('クーポンを配信')
            ->assertSee('配布履歴')
            ->assertDontSee('お知らせで配布');
    }

    public function test_bulk_issue_一括配布画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('全会員')
            ->assertSee('条件で絞る')
            ->assertSee('予約配信')
            ->assertSee('RFMセグメント')
            ->assertSee('ランクアップ達成')
            ->assertSee('累計スタンプ以上')
            ->assertSee('最終来店')
            ->assertSee('入力内容')
            ->assertSee('クーポン発行時に通知を送る')
            ->assertDontSee('配布するクーポンを選ぶ')
            ->assertDontSee('同じ会員に1枚ずつ付与します')
            ->assertDontSee('配信履歴');
    }

    public function test_segment_preview_rfm条件の件数を返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        Member::factory()->create(['project_id' => $project->id, 'status' => 'active']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.segment.preview', [
                'project_key' => $project->project_key,
                'segment_rfm_segments' => ['never'],
            ]));

        $response->assertOk();
        $response->assertJson(['count' => 1]);
    }

    public function test_audience_preview_配信対象会員の一覧を返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'member_number' => 'CPN-PREVIEW-1',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.audience.preview', [
                'project_key' => $project->project_key,
                'audience_type' => 'immediate_bulk',
            ]));

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('members.0.id', $member->id)
            ->assertJsonPath('members.0.member_number', 'CPN-PREVIEW-1');
    }

    public function test_coupons_一覧に配布履歴が表示されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '再利用クーポン',
            'code' => 'REUSE10',
        ]);
        $user = User::factory()->create(['name' => '配信先太郎']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'member_number' => 'M-DELIVERY-1',
        ]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
            'status' => CouponDeliveryStatus::SENT,
            'sent_at' => now()->subHour(),
            'issued_count' => 12,
        ]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::IMMEDIATE_PERSONAL,
            'status' => CouponDeliveryStatus::SENT,
            'sent_at' => now()->subMinutes(30),
            'target_member_ids' => [$member->id],
            'issued_count' => 1,
        ]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'issued_count' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('配布履歴');
        $response->assertSee('配信対象');
        $response->assertSee('再利用クーポン');
        $response->assertSee('REUSE10');
        $response->assertSee('配布済み');
        $response->assertSee('予約中');
        $response->assertSee('12人');
        $response->assertSee('全員（12人）');
        $response->assertSee('配信先太郎');
        $response->assertSee('M-DELIVERY-1');
        $response->assertSee('詳細');
    }

    public function test_bulk_issue_配信詳細で対象と発行済みを表示できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '詳細確認クーポン',
            'code' => 'DETAIL10',
        ]);
        $user = User::factory()->create(['name' => '詳細太郎']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'member_number' => 'M-DETAIL-1',
        ]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::IMMEDIATE_PERSONAL,
            'status' => CouponDeliveryStatus::SENT,
            'sent_at' => now(),
            'target_member_ids' => [$member->id],
            'issued_member_ids' => [$member->id],
            'issued_count' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.deliveries.show', [
                'project_key' => $project->project_key,
                'id' => $delivery->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('配信詳細');
        $response->assertSee('詳細確認クーポン');
        $response->assertSee('対象会員');
        $response->assertSee('発行済み');
        $response->assertSee('詳細太郎');
        $response->assertSee('M-DETAIL-1');
    }

    public function test_bulk_issue_配信詳細で未発行と発行済みを区別できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'スキップ確認クーポン',
            'code' => 'SKIP10',
        ]);
        $issuedUser = User::factory()->create(['name' => '発行済太郎']);
        $skippedUser = User::factory()->create(['name' => '未発行太郎']);
        $issuedMember = Member::factory()->create([
            'user_id' => $issuedUser->id,
            'project_id' => $project->id,
            'member_number' => 'M-ISSUED-1',
        ]);
        $skippedMember = Member::factory()->create([
            'user_id' => $skippedUser->id,
            'project_id' => $project->id,
            'member_number' => 'M-SKIP-1',
        ]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::IMMEDIATE_PERSONAL,
            'status' => CouponDeliveryStatus::SENT,
            'sent_at' => now(),
            'target_member_ids' => [$issuedMember->id, $skippedMember->id],
            'issued_member_ids' => [$issuedMember->id],
            'issued_count' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.deliveries.show', [
                'project_key' => $project->project_key,
                'id' => $delivery->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('対象会員');
        $response->assertSee('発行済太郎');
        $response->assertSee('未発行太郎');
        $response->assertSee('未発行');
        $response->assertDontSee('配信対象</h3>', false);
    }

    public function test_bulk_issue_ランクアップ達成配信を登録できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
                'target_rank' => 'gold',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('coupon_deliveries', [
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            'status' => CouponDeliveryStatus::ACTIVE,
        ]);
    }

    public function test_delivery_preview_対象人数と総会員数を返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        Member::factory()->count(2)->create(['project_id' => $project->id, 'rank' => 'gold']);
        Member::factory()->create(['project_id' => $project->id, 'rank' => 'silver']);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.delivery.preview', [
                'project_key' => $project->project_key,
                'delivery_type' => CouponDeliveryType::IMMEDIATE_RANK,
                'target_rank' => 'gold',
            ]));

        $response->assertOk();
        $response->assertJson([
            'count' => 2,
            'total' => 3,
            'is_event' => false,
        ]);
    }

    public function test_delivery_preview_ランクアップ達成はイベントフラグを返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        Member::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.delivery.preview', [
                'project_key' => $project->project_key,
                'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
                'target_rank' => 'gold',
            ]));

        $response->assertOk();
        $response->assertJson([
            'count' => -1,
            'total' => 1,
            'is_event' => true,
        ]);
    }

    public function test_create_配布タイミングを設定せずクーポンだけを作成できること(): void
    {
        config(['smaregi.contract_id' => '']);

        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $createPage = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.create', ['project_key' => $project->project_key]));
        $createPage->assertStatus(200)
            ->assertDontSee('LIVE PREVIEW')
            ->assertDontSee('LINEプレビュー')
            ->assertSee('会員アプリ')
            ->assertDontSee('name="return"', false)
            ->assertSee('name="image"', false)
            ->assertSee('name="image_fit"', false)
            ->assertSee('枠いっぱい')
            ->assertSee('画像全体')
            ->assertSee('ドラッグ＆ドロップ')
            ->assertSee('割引タイプ')
            ->assertSee('発行条件')
            ->assertSee('適用条件区分')
            ->assertSee('最低対象金額')
            ->assertSee('最高対象金額')
            ->assertSee('併用区分')
            ->assertDontSee('name="issue_trigger"', false)
            ->assertDontSee('渡すタイミング')
            ->assertDontSee('NEW COUPON')
            ->assertDontSee('また来たくなるきっかけを')
            ->assertSee('id="couponLivePreview"', false)
            ->assertSee('coupon_form_preview=1', false)
            ->assertSee('店舗アプリと同じ画面・デザインです');

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.store', ['project_key' => $project->project_key]), [
                'code' => 'ADMIN100',
                'name' => '管理画面クーポン',
                'type' => 'fixed',
                'value' => 100,
                'is_active' => '1',
                'issue_trigger' => Coupon::ISSUE_TRIGGER_BIRTHDAY,
                'use_display' => Coupon::USE_DISPLAY_BARCODE,
                'image_fit' => Coupon::IMAGE_FIT_CONTAIN,
            ]);

        $coupon = Coupon::where('project_id', $project->id)->where('code', 'ADMIN100')->first();
        $this->assertNotNull($coupon);
        $response->assertRedirect(route('admin.coupons', ['project_key' => $project->project_key]));
        $this->assertSame(Coupon::ISSUE_TRIGGER_MANUAL, $coupon->issue_trigger);
        $this->assertNull($coupon->stamp_card_id);
        $this->assertSame(Coupon::USE_DISPLAY_BARCODE, $coupon->use_display);
        $this->assertSame(Coupon::IMAGE_FIT_CONTAIN, $coupon->image_fit);
        $this->assertSame(CouponApplyConditionDivision::ALL, $coupon->apply_condition_division);
        $this->assertSame(CouponCombineDivision::ALLOWED, $coupon->combine_division);
    }

    public function test_create_スマレジと同じ発行条件を保存できること(): void
    {
        config(['smaregi.contract_id' => '']);

        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.store', ['project_key' => $project->project_key]), [
                'code' => 'SMAREGI-COND',
                'name' => '発行条件クーポン',
                'type' => 'percent',
                'value' => 10,
                'is_active' => '1',
                'apply_condition_division' => CouponApplyConditionDivision::INCLUDE_TARGET_PRODUCT,
                'min_order_amount' => 1000,
                'max_order_amount' => 5000,
                'combine_division' => CouponCombineDivision::FORBIDDEN,
                'use_display' => Coupon::USE_DISPLAY_BARCODE,
                'image_fit' => Coupon::IMAGE_FIT_COVER,
            ]);

        $coupon = Coupon::where('project_id', $project->id)->where('code', 'SMAREGI-COND')->first();
        $this->assertNotNull($coupon);
        $response->assertRedirect(route('admin.coupons', ['project_key' => $project->project_key]));
        $this->assertSame(CouponApplyConditionDivision::INCLUDE_TARGET_PRODUCT, $coupon->apply_condition_division);
        $this->assertSame('1000.00', (string) $coupon->min_order_amount);
        $this->assertSame('5000.00', (string) $coupon->max_order_amount);
        $this->assertSame(CouponCombineDivision::FORBIDDEN, $coupon->combine_division);
    }

    public function test_coupon_redemptions_利用者一覧を表示できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '利用履歴対象クーポン',
            'used_count' => 1,
        ]);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'name' => '山田太郎',
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M-USE-1',
        ]);
        CouponRedemption::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'member_id' => $member->id,
            'used_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.redemptions', [
                'project_key' => $project->project_key,
                'id' => $coupon->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('利用履歴');
        $response->assertDontSee('お知らせで配布');
        $response->assertSee('利用履歴対象クーポン');
        $response->assertSee('山田太郎');
        $response->assertSee('M-USE-1');
        $response->assertSee(route('admin.members.detail', [
            'project_key' => $project->project_key,
            'id' => $member->id,
        ]), false);
    }

    public function test_coupon_redemptions_ページネーションできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'ページネーション確認クーポン',
            'used_count' => 25,
        ]);

        for ($i = 1; $i <= 25; $i++) {
            $user = User::factory()->create([
                'project_id' => $project->id,
                'name' => '利用者'.$i,
            ]);
            $member = Member::factory()->create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'member_number' => 'M-PAGE-'.$i,
            ]);
            CouponRedemption::create([
                'project_id' => $project->id,
                'coupon_id' => $coupon->id,
                'member_id' => $member->id,
                'used_at' => now()->subMinutes($i),
            ]);
        }

        $page1 = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.redemptions', [
                'project_key' => $project->project_key,
                'id' => $coupon->id,
            ]));
        $page1->assertStatus(200);
        $page1->assertSee('利用者1');
        $page1->assertDontSee('利用者25');

        $page2 = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.redemptions', [
                'project_key' => $project->project_key,
                'id' => $coupon->id,
                'page' => 2,
            ]));
        $page2->assertStatus(200);
        $page2->assertSee('利用者25');
    }

    public function test_bulk_issue_有効クーポンが選択できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        for ($i = 1; $i <= 12; $i++) {
            Coupon::factory()->create([
                'project_id' => $project->id,
                'is_active' => true,
                'name' => sprintf('配布クーポン%02d', $i),
                'code' => sprintf('BULKPAGE%02d', $i),
            ]);
        }

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]));
        $response->assertStatus(200);
        $response->assertSee('配布クーポン01');
        $response->assertSee('配布クーポン12');
    }

    public function test_bulk_issue_期限切れクーポンを表示して配布を拒否すること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '期限切れクーポン',
            'end_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('期限切れです');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]))
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
            ])
            ->assertSessionHas('error', 'このクーポンは期限切れです');
    }

    public function test_bulk_issue_期間未設定クーポンは無期限で配布できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'start_at' => null,
            'end_at' => null,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'valid_until' => null,
        ]);
    }

    public function test_bulk_issue_rfmセグメントで配布できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $neverUser = User::factory()->create(['project_id' => $project->id]);
        $neverMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $neverUser->id,
            'status' => 'active',
        ]);
        $activeUser = User::factory()->create(['project_id' => $project->id]);
        $activeMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $activeUser->id,
            'status' => 'active',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $activeUser->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => OrderStatus::COMPLETED,
        ]);
        $order->forceFill(['created_at' => now()->subDays(5)])->save();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'delivery_type' => CouponDeliveryType::IMMEDIATE_SEGMENT_ATTRIBUTES,
                'segment_rfm_segments' => ['never'],
            ]);

        $response->assertRedirect();
        $delivery = CouponDelivery::where('coupon_id', $coupon->id)->firstOrFail();
        $this->assertSame(['never'], $delivery->segment_criteria['rfm_segments'] ?? null);
        $this->assertSame([$neverMember->id], $delivery->issued_member_ids);
        $this->assertNotContains($activeMember->id, $delivery->issued_member_ids ?? []);
    }

    public function test_bulk_issue_通知オフなら発行通知ジョブを積まないこと(): void
    {
        Queue::fake();
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'audience_type' => 'immediate_bulk',
                'delivery_timing' => 'immediate',
                'send_notification' => 0,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('member_coupons', [
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
        ]);
        $delivery = CouponDelivery::where('coupon_id', $coupon->id)->firstOrFail();
        $this->assertFalse($delivery->send_notification);
        Queue::assertNotPushed(SendCouponIssuedNotificationJob::class);
    }

    public function test_coupons_一覧に利用履歴ボタンがあること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '導線確認クーポン',
            'used_count' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('利用履歴');
        $response->assertDontSee('お知らせで配布');
        $response->assertSee(route('admin.coupons.redemptions', [
            'project_key' => $project->project_key,
            'id' => $coupon->id,
        ]), false);
    }
}
