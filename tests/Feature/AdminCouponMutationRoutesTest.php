<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CouponMutationController;
use Tests\TestCase;

class AdminCouponMutationRoutesTest extends TestCase
{
    public function test_coupon_mutation_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.coupons.store' => 'store',
            'admin.coupons.update' => 'update',
            'admin.coupons.delete' => 'destroy',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(CouponMutationController::class.'@'.$method, $route->getActionName());
        }
    }
}
