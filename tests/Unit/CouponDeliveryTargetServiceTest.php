<?php

namespace Tests\Unit;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberStamp;
use App\Models\Project;
use App\Models\StampCard;
use App\Services\Admin\CouponDeliveryTargetService;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponDeliveryTargetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_build_resolve_params_from_delivery_売上セグメントの保存条件を復元すること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_SEGMENT_SALES,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'segment_criteria' => [
                'period_days' => 60,
                'condition' => 'no_orders',
                'min_total_amount' => null,
            ],
        ]);

        $params = app(CouponDeliveryTargetService::class)->buildResolveParamsFromDelivery($delivery);

        $this->assertSame(60, $params['segment_period_days']);
        $this->assertSame('no_orders', $params['segment_condition']);
        $this->assertArrayNotHasKey('segment_min_amount', $params);
    }

    public function test_handle_絞り込み即時配布で対象0人の場合は例外になること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('配布対象の会員がいません');

        app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
            'delivery_type' => CouponDeliveryType::IMMEDIATE_BIRTHDAY,
            'birthday_month' => 2,
        ]);
    }

    public function test_handle_全員即時配布では対象会員_i_dを保存すること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $memberA = Member::factory()->create(['project_id' => $project->id]);
        $memberB = Member::factory()->create(['project_id' => $project->id]);

        $result = app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
            'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
        ]);

        $this->assertSame(2, $result['issued']);
        $this->assertFalse($result['scheduled']);

        $delivery = CouponDelivery::query()
            ->where('project_id', $project->id)
            ->where('coupon_id', $coupon->id)
            ->first();
        $this->assertNotNull($delivery);
        $this->assertEqualsCanonicalizing(
            [$memberA->id, $memberB->id],
            array_values(array_map('intval', $delivery->target_member_ids ?? [])),
        );
        $this->assertEqualsCanonicalizing(
            [$memberA->id, $memberB->id],
            array_values(array_map('intval', $delivery->issued_member_ids ?? [])),
        );
    }

    public function test_handle_予約配布は即時発行せず予約のまま残ること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id]);

        $result = app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
            'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this->assertTrue($result['scheduled']);
        $this->assertSame(0, $result['issued']);
        $this->assertSame(0, MemberCoupon::query()->where('coupon_id', $coupon->id)->count());
        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupon->id,
            'status' => CouponDeliveryStatus::SCHEDULED,
        ]);
    }

    public function test_handle_ランクアップ達成配信は有効ステータスで登録されること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $result = app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
            'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            'target_rank' => 'gold',
        ]);

        $this->assertTrue($result['scheduled']);
        $delivery = CouponDelivery::query()
            ->where('project_id', $project->id)
            ->where('coupon_id', $coupon->id)
            ->first();
        $this->assertNotNull($delivery);
        $this->assertSame(CouponDeliveryStatus::ACTIVE, $delivery->status);
        $this->assertSame(['rank' => 'gold'], $delivery->segment_criteria);
    }

    public function test_resolve_全員配信は全会員_i_dを返すこと(): void
    {
        $project = $this->createProject();
        $memberA = Member::factory()->create(['project_id' => $project->id]);
        $memberB = Member::factory()->create(['project_id' => $project->id]);

        $resolved = app(CouponDeliveryTargetService::class)->resolve(
            $project->id,
            CouponDeliveryType::IMMEDIATE_ALL,
            [],
        );

        $this->assertNull($resolved['error']);
        $this->assertSame([$memberA->id, $memberB->id], $resolved['member_ids']);
    }

    public function test_resolve_指定ランクの会員のみ返すこと(): void
    {
        $project = $this->createProject();
        $gold = Member::factory()->create(['project_id' => $project->id, 'rank' => 'gold']);
        Member::factory()->create(['project_id' => $project->id, 'rank' => 'silver']);

        $resolved = app(CouponDeliveryTargetService::class)->resolve(
            $project->id,
            CouponDeliveryType::IMMEDIATE_RANK,
            ['target_rank' => 'gold'],
        );

        $this->assertNull($resolved['error']);
        $this->assertSame([$gold->id], $resolved['member_ids']);
        $this->assertSame(['rank' => 'gold'], $resolved['segment_criteria']);
    }

    public function test_resolve_スタンプ累計条件の会員を返すこと(): void
    {
        $project = $this->createProject();
        $card = StampCard::factory()->create(['project_id' => $project->id, 'required_stamps' => 10]);
        $matched = Member::factory()->create(['project_id' => $project->id]);
        $other = Member::factory()->create(['project_id' => $project->id]);
        MemberStamp::factory()->create([
            'project_id' => $project->id,
            'member_id' => $matched->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 5,
        ]);
        MemberStamp::factory()->create([
            'project_id' => $project->id,
            'member_id' => $other->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 2,
        ]);

        $resolved = app(CouponDeliveryTargetService::class)->resolve(
            $project->id,
            CouponDeliveryType::IMMEDIATE_STAMP,
            [
                'stamp_card_id' => $card->id,
                'stamp_count_min' => 5,
            ],
        );

        $this->assertNull($resolved['error']);
        $this->assertSame([$matched->id], $resolved['member_ids']);
    }

    public function test_resolve_最終来店からちょうど_n日後の会員を返すこと(): void
    {
        $project = $this->createProject();
        $matched = Member::factory()->create([
            'project_id' => $project->id,
            'last_visited_at' => now()->subDays(7)->toDateString(),
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'last_visited_at' => now()->subDays(3)->toDateString(),
        ]);

        $resolved = app(CouponDeliveryTargetService::class)->resolve(
            $project->id,
            CouponDeliveryType::IMMEDIATE_DAYS_AFTER_VISIT,
            ['days_after_last_visit' => 7],
        );

        $this->assertNull($resolved['error']);
        $this->assertSame([$matched->id], $resolved['member_ids']);
        $this->assertSame(['days_after_last_visit' => 7], $resolved['segment_criteria']);
    }

    public function test_build_resolve_params_from_delivery_新配信条件を復元すること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_DAYS_AFTER_VISIT,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'segment_criteria' => [
                'days_after_last_visit' => 10,
            ],
        ]);

        $params = app(CouponDeliveryTargetService::class)->buildResolveParamsFromDelivery($delivery);

        $this->assertSame(10, $params['days_after_last_visit']);
    }
}
