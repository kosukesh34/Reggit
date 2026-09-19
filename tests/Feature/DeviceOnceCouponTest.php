<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
