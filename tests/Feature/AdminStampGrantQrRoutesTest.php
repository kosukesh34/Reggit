<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\StampGrantQrController;
use Tests\TestCase;

class AdminStampGrantQrRoutesTest extends TestCase
{
    public function test_stamp_grant_qr_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.stamps.grant-qr' => 'show',
            'admin.stamps.grant-qr.store' => 'store',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(StampGrantQrController::class.'@'.$method, $route->getActionName());
        }
    }
}
