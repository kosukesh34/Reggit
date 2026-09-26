<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CouponBulkIssueController;
use Tests\TestCase;

class AdminCouponBulkIssueRoutesTest extends TestCase
{
    public function test_coupon_bulk_issue_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.coupons.bulk-issue' => 'create',
            'admin.coupons.bulk-issue.store' => 'store',
            'admin.coupons.quick-create' => 'quickCreate',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(CouponBulkIssueController::class.'@'.$method, $route->getActionName());
        }
    }
}
