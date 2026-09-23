<?php

namespace App\Http\Middleware;

use App\Util\AdminContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/** Prevent tenant and shared-view state from crossing HTTP requests in persistent workers. */
class ResetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->clear();

        try {
            return $next($request);
        } finally {
            $this->clear();
        }
    }

    private function clear(): void
    {
        AdminContext::setCurrentProject(null);
        View::share('currentProject', null);
        View::share('currentProjectId', null);
        View::share('adminProjectId', null);
    }
}
