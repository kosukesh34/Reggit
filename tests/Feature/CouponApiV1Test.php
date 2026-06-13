<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000005';

    public function test_my_coupons_マイクーポン一覧を返すこと(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        MemberCoupon::factory()->create(['member_id' => $member->id, 'project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/me/coupons');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['coupons']]);
    }

    public function test_use_coupon_使用後にコードを返すこと(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $coupon = Coupon::factory()->create([
            'project_id' => 1,
            'code' => 'APP300',
            'use_display' => Coupon::USE_DISPLAY_BARCODE,
        ]);
        $memberCoupon = MemberCoupon::factory()->create([
            'member_id' => $member->id,
            'project_id' => 1,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/v1/members/me/coupons/'.$memberCoupon->id.'/use');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'APP300')
            ->assertJsonPath('data.status', 'used');
    }
}
