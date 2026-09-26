<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductMutationController;
use Tests\TestCase;

class AdminProductMutationRoutesTest extends TestCase
{
    public function test_product_mutation_routes_are_handled_by_the_dedicated_controller(): void
    {
        $expectedActions = [
            'admin.products.store' => 'store',
            'admin.products.update' => 'update',
            'admin.products.delete' => 'destroy',
        ];

        foreach ($expectedActions as $routeName => $method) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertSame(ProductMutationController::class.'@'.$method, $route->getActionName());
        }
    }
}
