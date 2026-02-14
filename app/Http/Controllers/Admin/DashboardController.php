<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\DashboardService;

class DashboardController extends AdminBaseController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {
    }

    public function dashboard()
    {
        $stats = $this->dashboardService->getStats();
        $recent_orders = $this->dashboardService->getRecentOrders(10);
        return view('admin.dashboard', compact('stats', 'recent_orders'));
    }
}
