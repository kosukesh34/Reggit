<?php

namespace Tests\Feature;

use App\Http\Support\OrderRequestHandler;
use App\Models\ApiToken;
use App\Models\MemberCoupon;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\DeviceOnceCouponService;
use App\UseCases\Announcement\AnnouncementMarkReadUseCase;
use App\UseCases\Coupon\CouponUseMemberCouponUseCase;
use App\UseCases\Coupon\Data\CouponUseMemberCouponOutput;
use App\UseCases\Lottery\NotifyLotteryWinUseCase;
use App\UseCases\Lottery\PlayLotteryEntitlementUseCase;
use App\UseCases\Product\ProductShowUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerApiRouteTargetTest extends TestCase
{
    use RefreshDatabase;

    public static function routes(): array
    {
        $cases = [];
        foreach (['shop-alpha', '71', null] as $key) {
            foreach (['product', 'order', 'cancel', 'coupon', 'announcement', 'device', 'play', 'notify'] as $operation) {
                $cases[$operation.'-'.($key ?? 'legacy')] = [$operation, $key];
            }
        }

        return $cases;
    }

    #[DataProvider('routes')]
    public function test_http_dispatch_uses_named_target_and_preserves_legacy_routes(string $operation, ?string $key): void
    {
        $project = Project::factory()->create(['project_key' => $key ?? 'legacy-shop']);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'route-user']);
        $token = ApiToken::issue($project, 'route-test');
        $this->withHeaders(['Authorization' => 'Bearer '.$token['plain'], 'X-Line-User-Id' => $user->line_user_id]);
        $id = 501;
        $method = 'post';
        switch ($operation) {
            case 'product':
                $path = '/products/'.$id;
                $method = 'get';
                $this->mock(ProductShowUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with($id)->andReturn(['id' => $id]));
                break;
            case 'order':
            case 'cancel':
                $path = '/orders/'.$id.($operation === 'cancel' ? '/cancel-unpaid' : '');
                $method = $operation === 'order' ? 'get' : 'post';
                $this->mock(OrderRequestHandler::class, fn ($mock) => $mock->shouldReceive($operation === 'order' ? 'show' : 'cancelUnpaid')->once()->with(\Mockery::any(), $id)->andReturn(new Order(['id' => $id])));
                break;
            case 'coupon':
                $path = '/member-coupons/'.$id.'/redeem';
                $this->mock(CouponUseMemberCouponUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with(\Mockery::on(fn ($actual) => $actual->id === $user->id), $id)->andReturn(new CouponUseMemberCouponOutput($id, 'Coupon', 'used', now()->toIso8601String(), 'code', 'TEST')));
                break;
            case 'announcement':
                $path = '/announcements/'.$id.'/read';
                $this->mock(AnnouncementMarkReadUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with(\Mockery::on(fn ($input) => $input->announcementId === $id && $input->user->id === $user->id))->andReturn(['announcement_id' => $id]));
                break;
            case 'device':
                $path = '/device-coupons/'.$id.'/claim';
                $this->withHeader('X-Device-Identifier', 'route-device');
                $this->mock(DeviceOnceCouponService::class, fn ($mock) => $mock->shouldReceive('claimById')->once()->with(\Mockery::on(fn ($actual) => $actual->id === $user->id), $id, 'route-device')->andReturn(new MemberCoupon(['id' => 601, 'coupon_id' => $id])));
                break;
            case 'play':
                $path = '/member-lottery/'.$id.'/play';
                $this->mock(PlayLotteryEntitlementUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with(\Mockery::on(fn ($actual) => $actual->id === $user->id), $id)->andReturn(['entitlement_id' => $id]));
                break;
            default:
                $path = '/member-lottery/'.$id.'/notify-win';
                $this->mock(NotifyLotteryWinUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with(\Mockery::on(fn ($actual) => $actual->id === $user->id), $id)->andReturnNull());
        }
        $response = $this->json(strtoupper($method), '/api/v1'.($key === null ? '' : '/'.$key).$path);
        $response->assertOk()->assertJsonPath('success', true);
    }
}
