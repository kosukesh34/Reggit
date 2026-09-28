<?php

namespace App\Http\Middleware;

use App\Util\AdminContext;
use App\Util\StoreContext;
use App\Util\TenantContext;
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
        app(TenantContext::class)->requireProject(true);

        try {
            return $next($request);
        } finally {
            $this->clear();
        }
    }

    private function clear(): void
    {
        AdminContext::setCurrentProject(null);
        $storeContext = app(StoreContext::class);
        $storeContext->bound = false;
        $storeContext->projectStoreCount = 0;
        $storeContext->store = null;
        $storeContext->projectId = null;
        $storeContext->visibleStoreIds = null;
        $storeContext->admin = null;
        View::share('availableStores', null);
        View::share('selectedStore', null);
        View::share('canManageStores', false);
        app(TenantContext::class)->requireProject(false);
        View::share('currentProject', null);
        View::share('currentProjectId', null);
        View::share('adminProjectId', null);
    }
}
