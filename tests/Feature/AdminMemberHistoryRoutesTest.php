<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberHistoryController;
use Tests\TestCase;

class AdminMemberHistoryRoutesTest extends TestCase
{
    public function test_member_history_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.members.history' => 'index',
            'admin.members.visits.detail' => 'visit',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(MemberHistoryController::class.'@'.$method, $route->getActionName());
        }
    }
}
