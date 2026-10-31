<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Dashboard\AdminDashboardIndexUseCase;
use App\UseCases\Admin\Dashboard\AdminSystemNoticeReadUseCase;

class DashboardController extends AdminBaseController
{
    private AdminDashboardIndexUseCase $dashboardIndexUseCase;

    public function __construct(AdminDashboardIndexUseCase $dashboardIndexUseCase)
    {
        $this->dashboardIndexUseCase = $dashboardIndexUseCase;
    }

    public function notice(string $project_key, int $id, AdminSystemNoticeReadUseCase $notices)
    {
        return view('admin.notice-detail', ['notice' => $notices->open($id, (int) auth('admin')->id())]);
    }

    public function dashboard(AdminSystemNoticeReadUseCase $systemNotices)
    {
        $output = $this->dashboardIndexUseCase->handle();

        $notices = $systemNotices->visible((int) auth('admin')->id());

        return view('admin.dashboard', [
            'systemNotices' => $notices['notices'],
            'readNoticeIds' => $notices['readIds'],
            'stats' => $output->stats,
            'recent_orders' => $output->recentOrders,
            'lineBlockTrend' => $output->lineBlockTrend,
        ]);
    }
}
