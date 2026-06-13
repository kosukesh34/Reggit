<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponControllerTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000001';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_my_coupons_マイクーポン一覧を返すこと(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        MemberCoupon::factory()->create(['member_id' => $member->id, 'project_id' => 1]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me/coupons?project_key=default');

        $response->assertStatus(200)
            ->assertJsonStructure(['coupons', 'default_image_url']);
    }

    public function test_use_coupon_使用後にバーコード情報を返し使用済みにすること(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $coupon = Coupon::factory()->create([
            'project_id' => 1,
            'code' => 'WINBACK300',
            'use_display' => Coupon::USE_DISPLAY_BARCODE,
        ]);
        $memberCoupon = MemberCoupon::factory()->create([
            'member_id' => $member->id,
            'project_id' => 1,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/coupons/'.$memberCoupon->id.'/use?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'WINBACK300')
            ->assertJsonPath('data.status', 'used');

        $memberCoupon->refresh();
        $this->assertSame(MemberCoupon::STATUS_USED, $memberCoupon->status);
        $this->assertNotNull($memberCoupon->used_at);
        $this->assertDatabaseHas('coupon_redemptions', [
            'member_coupon_id' => $memberCoupon->id,
            'coupon_id' => $coupon->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_use_coupon_二重使用は拒否すること(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $coupon = Coupon::factory()->create(['project_id' => 1, 'code' => 'USEDONCE']);
        $memberCoupon = MemberCoupon::factory()->create([
            'member_id' => $member->id,
            'project_id' => 1,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_USED,
            'used_at' => now(),
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/coupons/'.$memberCoupon->id.'/use?project_key=default');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_use_coupon_利用開始前は拒否すること(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $coupon = Coupon::factory()->create(['project_id' => 1, 'code' => 'FUTURE100']);
        $memberCoupon = MemberCoupon::factory()->create([
            'member_id' => $member->id,
            'project_id' => 1,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
            'valid_from' => now()->addDay(),
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/coupons/'.$memberCoupon->id.'/use?project_key=default');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'このクーポンはまだ利用開始前です');
    }
}
