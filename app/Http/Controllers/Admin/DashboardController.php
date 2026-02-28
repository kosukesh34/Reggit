<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Dashboard\AdminDashboardIndexUseCase;

class DashboardController extends AdminBaseController
{
    private AdminDashboardIndexUseCase $dashboardIndexUseCase;

    public function __construct(AdminDashboardIndexUseCase $dashboardIndexUseCase)
    {
        $this->dashboardIndexUseCase = $dashboardIndexUseCase;
    }

    public function dashboard()
    {
        $output = $this->dashboardIndexUseCase->handle();

        return view('admin.dashboard', [
            'stats' => $output->stats,
            'recent_orders' => $output->recentOrders,
        ]);
    }
}
