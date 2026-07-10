<?php

namespace App\Http\Support;

use App\UseCases\Admin\Data\RouteContext;
use Illuminate\Http\Request;

class RouteContextResolver
{
    public function resolveFromRequest(Request $request): RouteContext
    {
        return $this->resolve(
            $request->route()?->getName() ?? '',
            $request->route('project_key'),
        );
    }

    public function resolve(string $routeName, ?string $projectKey): RouteContext
    {
        $routePrefix = str_starts_with($routeName, 'system-admin.') ? 'system-admin' : 'admin';

        $params = [];
        if ($routePrefix === 'admin' && $projectKey !== null && $projectKey !== '') {
            $params = ['project_key' => $projectKey];
        }

        return new RouteContext($routePrefix, $params);
    }
}
