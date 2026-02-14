<?php

namespace App\UseCases\Admin;

use App\UseCases\Admin\Dto\RouteContextDto;
use Illuminate\Http\Request;

class RouteContextResolver
{
    public function resolve(Request $request): RouteContextDto
    {
        $routeName = $request->route()?->getName() ?? '';
        $routePrefix = str_starts_with($routeName, 'system-admin.') ? 'system-admin' : 'admin';

        $params = [];
        if ($routePrefix === 'admin') {
            $projectKey = $request->route('project_key');
            if ($projectKey !== null && $projectKey !== '') {
                $params = ['project_key' => $projectKey];
            }
        }

        return new RouteContextDto($routePrefix, $params);
    }
}
