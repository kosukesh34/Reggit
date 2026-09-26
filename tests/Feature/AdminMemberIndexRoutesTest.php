<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MemberIndexController;
use Tests\TestCase;

class AdminMemberIndexRoutesTest extends TestCase
{
    public function test_member_index_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.members' => 'index',
            'admin.members.export' => 'export',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(MemberIndexController::class.'@'.$method, $route->getActionName());
        }
    }
}
