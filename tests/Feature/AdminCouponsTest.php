<?php

namespace Tests\Feature;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response->assertStatus(200);
    }

    public function test_bulk_issue_一括配布画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('クーポンを選ぶ')
            ->assertSee('全員にすぐ配布')
            ->assertSee('セグメント配信')
            ->assertSee('予約配信')
            ->assertSee('指定ランク')
            ->assertSee('ランクアップ達成配信')
            ->assertSee('スタンプ累計')
            ->assertSee('最終来店からN日後')
            ->assertSee('新規作成')
            ->assertSee('配信履歴');
    }

    public function test_bulk_issue_配信履歴が表示されること(): void
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
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('配信履歴');
        $response->assertSee('配信対象');
        $response->assertSee('再利用クーポン');
        $response->assertSee('REUSE10');
        $response->assertSee('配布済み');
        $response->assertSee('予約中');
        $response->assertSee('12人');
        $response->assertSee('全員（12人）');
        $response->assertSee('配信先太郎');
        $response->assertSee('M-DELIVERY-1');
        $response->assertSee('配信3回');
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
        $response->assertSee('配信対象');
        $response->assertSee('発行済み');
        $response->assertSee('詳細太郎');
        $response->assertSee('M-DETAIL-1');
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

    public function test_create_from_bulk_issue_作成後に配布画面へ戻ること(): void
    {
        config(['smaregi.contract_id' => '']);

        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $createPage = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.create', [
                'project_key' => $project->project_key,
                'return' => 'bulk-issue',
            ]));
        $createPage->assertStatus(200)
            ->assertSee('name="return"', false)
            ->assertSee('value="bulk-issue"', false);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.store', ['project_key' => $project->project_key]), [
                'code' => 'ADMIN100',
                'name' => '管理画面クーポン',
                'type' => 'fixed',
                'value' => 100,
                'is_active' => '1',
                'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
                'use_display' => Coupon::USE_DISPLAY_BARCODE,
                'design_template' => Coupon::DESIGN_CLASSIC,
                'return' => 'bulk-issue',
            ]);

        $coupon = Coupon::where('project_id', $project->id)->where('code', 'ADMIN100')->first();
        $this->assertNotNull($coupon);
        $response->assertRedirect(route('admin.coupons.bulk-issue', [
            'project_key' => $project->project_key,
            'coupon_id' => $coupon->id,
        ]));
        $this->assertSame(Coupon::USE_DISPLAY_BARCODE, $coupon->use_display);
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

    public function test_bulk_issue_クーポン一覧がページネーションされること(): void
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

        $page1 = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', ['project_key' => $project->project_key]));
        $page1->assertStatus(200);
        $page1->assertSee('配布クーポン01');
        $page1->assertDontSee('配布クーポン12');

        $page2 = $this->actingAs($admin, 'admin')
            ->get(route('admin.coupons.bulk-issue', [
                'project_key' => $project->project_key,
                'page' => 2,
            ]));
        $page2->assertStatus(200);
        $page2->assertSee('配布クーポン12');
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
        $response->assertSee(route('admin.coupons.redemptions', [
            'project_key' => $project->project_key,
            'id' => $coupon->id,
        ]), false);
    }
}
