<?php

namespace Tests\Unit;

use App\Http\Support\RouteContextResolver;
use Tests\TestCase;

class RouteContextResolverTest extends TestCase
{
    public function test_resolve_システム管理者プレフィックスが解決されること(): void
    {
        $resolver = new RouteContextResolver;
        $context = $resolver->resolve('system-admin.accounts.index', null);

        $this->assertSame('system-admin', $context->routePrefix);
        $this->assertSame([], $context->params);
    }

    public function test_resolve_プロジェクトキー付き管理画面プレフィックスが解決されること(): void
    {
        $resolver = new RouteContextResolver;
        $context = $resolver->resolve('admin.dashboard', 'default');

        $this->assertSame('admin', $context->routePrefix);
        $this->assertSame(['project_key' => 'default'], $context->params);
    }
}
