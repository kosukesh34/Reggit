<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\User;
use App\UseCases\Coupon\CouponUseMemberCouponUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_my_coupons_利用可能なクーポンだけコードを返すこと(): void
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $coupon = Coupon::factory()->create(['project_id' => 1, 'code' => 'SHARED300']);
        $states = [
            'available' => ['status' => MemberCoupon::STATUS_UNUSED, 'valid_until' => now()->addDay()],
            'expired' => ['status' => MemberCoupon::STATUS_UNUSED, 'valid_until' => now()->subMinute()],
            'upcoming' => ['status' => MemberCoupon::STATUS_UNUSED, 'valid_from' => now()->addDay()],
            'used' => ['status' => MemberCoupon::STATUS_USED, 'used_at' => now()],
        ];
        foreach ($states as $attributes) {
            MemberCoupon::factory()->create(['member_id' => $member->id, 'project_id' => 1, 'coupon_id' => $coupon->id] + $attributes);
        }

        $coupons = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/me/coupons')
            ->assertOk()
            ->json('data.coupons');

        $this->assertEqualsCanonicalizing(
            ['available' => 'SHARED300', 'expired' => null, 'upcoming' => null, 'used' => null],
            collect($coupons)->mapWithKeys(fn (array $coupon) => [$coupon['status'] => $coupon['code']])->all(),
        );
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

    public function test_cached_deleted_member_cannot_use_a_coupon(): void
    {
        $this->assertStaleMemberCannotUseCoupon('delete');
    }

    public function test_cached_transferred_member_cannot_use_a_coupon(): void
    {
        $this->assertStaleMemberCannotUseCoupon('transfer');
    }

    public function test_revoked_user_cannot_use_a_coupon(): void
    {
        $this->assertStaleMemberCannotUseCoupon('revoked');
    }

    private function assertStaleMemberCannotUseCoupon(string $change): void
    {
        $user = User::factory()->create(['project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $coupon = Coupon::factory()->create(['project_id' => 1, 'used_count' => 0]);
        $issued = MemberCoupon::factory()->create([
            'member_id' => $member->id, 'project_id' => 1, 'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED, 'used_at' => null,
        ]);
        $user->load('member');
        if ($change === 'revoked') {
            $user->fresh()->increment('authentication_version');
        } elseif ($change === 'transfer') {
            $other = User::factory()->create(['project_id' => 1]);
            $member->update(['user_id' => $other->id]);
        } else {
            $member->delete();
        }
        try {
            app(CouponUseMemberCouponUseCase::class)->handle($user, (int) $issued->id);
            $this->fail('A stale member must not redeem a coupon.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('member', $exception->errors());
        }
        $this->assertSame(MemberCoupon::STATUS_UNUSED, $issued->fresh()->status);
        $this->assertNull($issued->fresh()->used_at);
        $this->assertDatabaseCount('coupon_redemptions', 0);
        $this->assertSame(0, (int) $coupon->fresh()->used_count);
    }
}
