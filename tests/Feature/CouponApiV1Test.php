<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'line-coupon-v1';

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
}
