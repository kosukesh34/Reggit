<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberActivityController;
use Tests\TestCase;

class AdminMemberActivityRoutesTest extends TestCase
{
    public function test_member_activity_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.members.history' => 'history',
            'admin.members.visits.detail' => 'visitDetail',
            'admin.members.issue-coupon' => 'issueCoupon',
            'admin.members.delete-coupon' => 'deleteCoupon',
            'admin.members.adjust-stamps' => 'adjustStamps',
            'admin.members.record-visit' => 'recordVisit',
            'admin.members.tags.attach' => 'attachTag',
            'admin.members.tags.detach' => 'detachTag',
            'admin.members.memo' => 'updateMemo',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(MemberActivityController::class.'@'.$method, $route->getActionName());
        }
    }
}
