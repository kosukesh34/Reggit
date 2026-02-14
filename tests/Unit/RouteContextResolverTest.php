<?php

namespace Tests\Unit;

use App\UseCases\Admin\RouteContextResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

class RouteContextResolverTest extends TestCase
{
    public function testResolvesSystemAdminPrefix(): void
    {
        $resolver = new RouteContextResolver();
        $request = Request::create('/system-admin/accounts');
        $request->setRouteResolver(function () {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->method('getName')->willReturn('system-admin.accounts.index');
            $route->method('parameter')->willReturn(null);
            return $route;
        });

        $context = $resolver->resolve($request);

        $this->assertSame('system-admin', $context->routePrefix);
        $this->assertSame([], $context->params);
    }

    public function testResolvesAdminPrefixWithProjectKey(): void
    {
        $resolver = new RouteContextResolver();
        $request = Request::create('/admin/default/dashboard');
        $request->setRouteResolver(function () {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->method('getName')->willReturn('admin.dashboard');
            $route->method('parameter')->with('project_key')->willReturn('default');
            return $route;
        });

        $context = $resolver->resolve($request);

        $this->assertSame('admin', $context->routePrefix);
        $this->assertSame(['project_key' => 'default'], $context->params);
    }
}
