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
            foreach (['product', 'order', 'cancel', 'coupon', 'coupon-alias', 'announcement', 'device', 'play', 'play-alias', 'notify', 'notify-alias'] as $operation) {
                $cases[$operation.'-'.($key ?? 'legacy')] = [$operation, $key];
            }
        }

        return $cases;
    }

    #[DataProvider('routes')]
    public function test_malformed_target_is_rejected_before_dispatch(string $operation, ?string $key): void
    {
        $paths = [
            'product' => '/products/501garbage',
            'order' => '/orders/501garbage',
            'cancel' => '/orders/501garbage/cancel-unpaid',
            'coupon' => '/member-coupons/501garbage/redeem',
            'coupon-alias' => '/members/me/coupons/501garbage/use',
            'announcement' => '/announcements/501garbage/read',
            'device' => '/device-coupons/501garbage/claim',
            'play' => '/member-lottery/501garbage/play',
            'play-alias' => '/members/me/lottery/501garbage/play',
            'notify' => '/member-lottery/501garbage/notify-win',
            'notify-alias' => '/members/me/lottery/501garbage/notify-win',
        ];
        $this->mock(ProductShowUseCase::class, fn ($mock) => $mock->shouldNotReceive('handle'));
        $this->mock(OrderRequestHandler::class, function ($mock): void {
            $mock->shouldNotReceive('show');
            $mock->shouldNotReceive('cancelUnpaid');
        });
        foreach ([CouponUseMemberCouponUseCase::class, AnnouncementMarkReadUseCase::class, PlayLotteryEntitlementUseCase::class, NotifyLotteryWinUseCase::class] as $class) {
            $this->mock($class, fn ($mock) => $mock->shouldNotReceive('handle'));
        }
        $this->mock(DeviceOnceCouponService::class, fn ($mock) => $mock->shouldNotReceive('claimById'));
        $method = in_array($operation, ['product', 'order'], true) ? 'GET' : 'POST';
        $this->json($method, '/api/v1'.($key === null ? '' : '/'.$key).$paths[$operation])->assertNotFound();
    }

    #[DataProvider('routes')]
    public function test_http_dispatch_uses_named_target_and_preserves_legacy_routes(string $operation, ?string $key): void
    {
        $project = Project::factory()->create(['project_key' => $key ?? 'legacy-shop']);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000071']);
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
            case 'coupon-alias':
                $path = $operation === 'coupon' ? '/member-coupons/'.$id.'/redeem' : '/members/me/coupons/'.$id.'/use';
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
            case 'play-alias':
                $path = ($operation === 'play' ? '/member-lottery/' : '/members/me/lottery/').$id.'/play';
                $this->mock(PlayLotteryEntitlementUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with(\Mockery::on(fn ($actual) => $actual->id === $user->id), $id)->andReturn(['entitlement_id' => $id]));
                break;
            default:
                $path = ($operation === 'notify' ? '/member-lottery/' : '/members/me/lottery/').$id.'/notify-win';
                $this->mock(NotifyLotteryWinUseCase::class, fn ($mock) => $mock->shouldReceive('handle')->once()->with(\Mockery::on(fn ($actual) => $actual->id === $user->id), $id)->andReturnNull());
        }
        $response = $this->json(strtoupper($method), '/api/v1'.($key === null ? '' : '/'.$key).$path);
        $response->assertOk()->assertJsonPath('success', true);
    }
}
