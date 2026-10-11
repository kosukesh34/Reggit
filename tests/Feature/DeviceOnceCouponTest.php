<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceOnceCouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeviceOnceCouponTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U55555555555555555555555555555555';

    public function test_同じ端末ではクーポンを一度だけ獲得できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true],
        );
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'platform' => 'ios',
            'device_identifier' => 'device-once-test',
        ]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
            'device_once_enabled' => true,
        ]);

        $headers = [
            'X-Line-User-Id' => self::LINE_USER_ID,
            'X-Project-Key' => 'default',
            'X-Device-Identifier' => 'device-once-test',
        ];
        $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders($headers)
            ->post("/api/v1/device-coupons/{$coupon->id}/claim")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders($headers)
            ->post("/api/v1/device-coupons/{$coupon->id}/claim")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('coupon_device_issues', 1);
        $this->assertDatabaseCount('member_coupons', 1);
    }

    public function test_未登録端末からの獲得を拒否すること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true],
        );
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
            'device_once_enabled' => true,
        ]);

        $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders([
                'X-Line-User-Id' => self::LINE_USER_ID,
                'X-Project-Key' => 'default',
                'X-Device-Identifier' => 'unknown-device',
            ])
            ->post("/api/v1/device-coupons/{$coupon->id}/claim")
            ->assertStatus(422);
    }

    public function test_cached_deleted_member_cannot_claim_a_device_coupon(): void
    {
        [$user, $member, $coupon] = $this->deviceCouponFixture();
        $user->load('member');
        $member->delete();

        $this->assertClaimRejected($user, $coupon);
    }

    public function test_cached_member_transferred_to_another_user_cannot_claim_a_device_coupon(): void
    {
        [$user, $member, $coupon] = $this->deviceCouponFixture();
        $user->load('member');
        $other = User::factory()->create(['project_id' => $user->project_id]);
        $member->update(['user_id' => $other->id]);

        $this->assertClaimRejected($user, $coupon);
    }

    private function deviceCouponFixture(): array
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        UserDevice::query()->create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'platform' => 'ios', 'device_identifier' => 'lifecycle-device',
        ]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id, 'is_active' => true, 'device_once_enabled' => true,
        ]);

        return [$user, $member, $coupon];
    }

    private function assertClaimRejected(User $user, Coupon $coupon): void
    {
        try {
            app(DeviceOnceCouponService::class)->claim($user, $coupon, 'lifecycle-device');
            $this->fail('A stale member must not receive a device coupon.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('member', $exception->errors());
        }
        $this->assertDatabaseCount('coupon_device_issues', 0);
        $this->assertDatabaseCount('member_coupons', 0);
    }
}
