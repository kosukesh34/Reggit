<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AfterRegistrationDeliveryController;
use App\Http\Controllers\Admin\AfterRegistrationDeliveryMutationController;
use Tests\TestCase;

class AdminAfterRegistrationDeliveryRoutesTest extends TestCase
{
    public function test_read_and_mutation_routes_are_handled_by_separate_controllers(): void
    {
        $expectedActions = [
            'admin.after-registration-deliveries.index' => AfterRegistrationDeliveryController::class.'@index',
            'admin.after-registration-deliveries.create' => AfterRegistrationDeliveryController::class.'@create',
            'admin.after-registration-deliveries.edit' => AfterRegistrationDeliveryController::class.'@edit',
            'admin.after-registration-deliveries.store' => AfterRegistrationDeliveryMutationController::class.'@store',
            'admin.after-registration-deliveries.update' => AfterRegistrationDeliveryMutationController::class.'@update',
            'admin.after-registration-deliveries.destroy' => AfterRegistrationDeliveryMutationController::class.'@destroy',
        ];

        foreach ($expectedActions as $routeName => $action) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame($action, $route->getActionName());
        }
    }
}
