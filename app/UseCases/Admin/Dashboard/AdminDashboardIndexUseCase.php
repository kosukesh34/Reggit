<?php

namespace App\UseCases\Admin\Dashboard;

use App\Services\Admin\DashboardService;
use App\UseCases\Admin\Dashboard\Data\AdminDashboardIndexOutput;

/** 管理画面ダッシュボード一覧の表示を担当する UseCase。 */
class AdminDashboardIndexUseCase
{
    private const RECENT_ORDERS_LIMIT = 10;

    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function handle(): AdminDashboardIndexOutput
    {
        $stats = $this->dashboardService->getStats();
        $recentOrders = $this->dashboardService->getRecentOrders(self::RECENT_ORDERS_LIMIT);

        return new AdminDashboardIndexOutput($stats, $recentOrders);
    }
}
