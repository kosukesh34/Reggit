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
    public function __construct(
        private StoreContext $storeContext,
        private TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->clear();
        $this->tenantContext->requireProject(true);

        try {
            return $next($request);
        } finally {
            $this->clear();
        }
    }

    private function clear(): void
    {
        AdminContext::setCurrentProject(null);
        $this->storeContext->bound = false;
        $this->storeContext->projectStoreCount = 0;
        $this->storeContext->store = null;
        $this->storeContext->projectId = null;
        $this->storeContext->visibleStoreIds = null;
        $this->storeContext->admin = null;
        View::share('availableStores', null);
        View::share('selectedStore', null);
        View::share('canManageStores', false);
        $this->tenantContext->requireProject(false);
        View::share('currentProject', null);
        View::share('currentProjectId', null);
        View::share('adminProjectId', null);
    }
}
