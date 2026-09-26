<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Gallery\ItemMutationController;
use App\Http\Controllers\Admin\MemberMutationController;
use App\Http\Controllers\Admin\ProjectMutationController;
use App\Http\Controllers\Admin\Settings\StaffMutationController;
use Tests\TestCase;

class AdminMutationBoundaryRoutesTest extends TestCase
{
    public function test_mutation_routes_are_handled_by_focused_controllers(): void
    {
        $expectedActions = [
            'admin.members.update' => MemberMutationController::class.'@update',
            'admin.members.delete' => MemberMutationController::class.'@destroy',
            'admin.settings.staff.store' => StaffMutationController::class.'@store',
            'admin.settings.staff.update' => StaffMutationController::class.'@update',
            'admin.gallery.items.store' => ItemMutationController::class.'@store',
            'admin.gallery.items.update' => ItemMutationController::class.'@update',
            'admin.gallery.items.delete' => ItemMutationController::class.'@destroy',
            'system-admin.projects.store' => ProjectMutationController::class.'@store',
            'system-admin.projects.update' => ProjectMutationController::class.'@update',
        ];

        foreach ($expectedActions as $routeName => $action) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame($action, $route->getActionName());
        }
    }
}
