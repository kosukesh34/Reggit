<?php

namespace Tests\Unit\UseCases\Coupon;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Coupon\IssueCouponOnRankUpUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCouponOnRankUpUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_昇格時に到達ランクのクーポンが発行されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], $project->id);

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            'status' => CouponDeliveryStatus::ACTIVE,
            'segment_criteria' => ['rank' => 'gold'],
            'issued_count' => 0,
        ]);

        $user = User::factory()->create();
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'rank' => 'bronze',
        ]);

        app(IssueCouponOnRankUpUseCase::class)->handle($member, 'bronze', 'gold');

        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'project_id' => $project->id,
        ]);

        $delivery = CouponDelivery::query()->where('coupon_id', $coupon->id)->first();
        $this->assertNotNull($delivery);
        $this->assertSame(1, (int) $delivery->issued_count);
        $this->assertSame([$member->id], array_map('intval', $delivery->issued_member_ids ?? []));
    }

    public function test_同じ昇格を繰り返してもクーポンは1枚であること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], $project->id);

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            'status' => CouponDeliveryStatus::ACTIVE,
            'segment_criteria' => ['rank' => 'gold'],
            'issued_count' => 0,
        ]);

        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create()->id,
            'rank' => 'bronze',
        ]);

        $useCase = app(IssueCouponOnRankUpUseCase::class);
        $useCase->handle($member, 'bronze', 'gold');
        $useCase->handle($member, 'bronze', 'gold');

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_無関係な到達ランクでは発行されないこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 50, 'order' => 2, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 3, 'is_active' => true],
        ], $project->id);

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            'status' => CouponDeliveryStatus::ACTIVE,
            'segment_criteria' => ['rank' => 'gold'],
            'issued_count' => 0,
        ]);

        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create()->id,
            'rank' => 'bronze',
        ]);

        app(IssueCouponOnRankUpUseCase::class)->handle($member, 'bronze', 'silver');

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_初回ランク割り当てではクーポンを発行しないこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], $project->id);

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL,
            'status' => CouponDeliveryStatus::ACTIVE,
            'segment_criteria' => ['rank' => 'bronze'],
            'issued_count' => 0,
        ]);

        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create()->id,
            'rank' => 'bronze',
        ]);

        app(IssueCouponOnRankUpUseCase::class)->handle($member, '', 'bronze');

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
    }
}
