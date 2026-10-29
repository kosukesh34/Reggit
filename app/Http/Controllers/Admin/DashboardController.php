<?php

namespace App\Http\Controllers\Admin;

use App\Models\SystemNotice;
use App\UseCases\Admin\Dashboard\AdminDashboardIndexUseCase;
use Illuminate\Support\Facades\DB;

class DashboardController extends AdminBaseController
{
    private AdminDashboardIndexUseCase $dashboardIndexUseCase;

    public function __construct(AdminDashboardIndexUseCase $dashboardIndexUseCase)
    {
        $this->dashboardIndexUseCase = $dashboardIndexUseCase;
    }

    public function notice(string $project_key, int $id)
    {
        $notice = SystemNotice::visible()->findOrFail($id);
        DB::table('system_notice_reads')->updateOrInsert(
            ['system_notice_id' => $notice->id, 'admin_user_id' => auth('admin')->id()],
            ['read_at' => now()]
        );

        return view('admin.notice-detail', ['notice' => $notice]);
    }

    public function dashboard()
    {
        $output = $this->dashboardIndexUseCase->handle();

        $notices = SystemNotice::visible()->get();
        $readNoticeIds = DB::table('system_notice_reads')
            ->where('admin_user_id', auth('admin')->id())
            ->whereIn('system_notice_id', $notices->pluck('id'))
            ->pluck('system_notice_id')
            ->all();

        return view('admin.dashboard', [
            'systemNotices' => $notices,
            'readNoticeIds' => $readNoticeIds,
            'stats' => $output->stats,
            'recent_orders' => $output->recentOrders,
            'lineBlockTrend' => $output->lineBlockTrend,
        ]);
    }
}
