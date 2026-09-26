<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ReservationStatusController;
use Tests\TestCase;

class AdminReservationStatusRoutesTest extends TestCase
{
    public function test_reservation_status_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.reservations.status' => 'update',
            'admin.reservations.complete' => 'complete',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(ReservationStatusController::class.'@'.$method, $route->getActionName());
        }
    }
}
