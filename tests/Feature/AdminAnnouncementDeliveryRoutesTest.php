<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AnnouncementDeliveryController;
use Tests\TestCase;

class AdminAnnouncementDeliveryRoutesTest extends TestCase
{
    public function test_announcement_delivery_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.announcements.delivery.create' => 'create',
            'admin.announcements.delivery.store' => 'store',
            'admin.announcements.ajax.members' => 'searchMembers',
            'admin.announcements.segment.preview' => 'segmentPreview',
            'admin.announcements.audience.preview' => 'audiencePreview',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(AnnouncementDeliveryController::class.'@'.$method, $route->getActionName());
        }
    }
}
