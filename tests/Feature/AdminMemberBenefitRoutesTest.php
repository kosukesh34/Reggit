<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberBenefitController;
use Tests\TestCase;

class AdminMemberBenefitRoutesTest extends TestCase
{
    public function test_member_benefit_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.members.issue-coupon' => 'issueCoupon',
            'admin.members.delete-coupon' => 'deleteCoupon',
            'admin.members.adjust-stamps' => 'adjustStamps',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(MemberBenefitController::class.'@'.$method, $route->getActionName());
        }
    }
}
