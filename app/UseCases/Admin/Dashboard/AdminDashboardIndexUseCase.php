<?php

namespace App\UseCases\Admin\Dashboard;

use App\Models\Announcement;
use App\Models\LineFollowEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\DashboardService;
use App\UseCases\Admin\Dashboard\Data\AdminDashboardIndexOutput;
use App\Util\AdminContext;
use Illuminate\Support\Carbon;

/** 管理画面ダッシュボード一覧の表示を担当する UseCase。 */
class AdminDashboardIndexUseCase
{
    private const RECENT_ORDERS_LIMIT = 10;

    private const LINE_TREND_MONTHS = 6;

    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function handle(): AdminDashboardIndexOutput
    {
        $stats = $this->dashboardService->getStats();
        $recentOrders = $this->dashboardService->getRecentOrders(self::RECENT_ORDERS_LIMIT);
        $projectId = AdminContext::resolveProjectId(null);
        $linePlanEnabled = (bool) Project::query()->whereKey($projectId)->value('line_plan_enabled');

        return new AdminDashboardIndexOutput($stats, $recentOrders, $linePlanEnabled ? $this->lineBlockTrend($projectId) : null);
    }

    /**
     * 直近6か月の LINE 配信回数とブロックの推移。ブロック率は「その月のブロック数 ÷ 月末時点の LINE 会員数」。
     *
     * @return list<array{month: string, deliveries: int, blocks: int, follows: int, line_users: int, block_rate: float}>
     */
    private function lineBlockTrend(int $projectId): array
    {
        $rows = [];
        for ($i = self::LINE_TREND_MONTHS - 1; $i >= 0; $i--) {
            $start = Carbon::now()->startOfMonth()->subMonthsNoOverflow($i);
            $end = $start->copy()->endOfMonth();
            $events = LineFollowEvent::query()
                ->where('project_id', $projectId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type');
            $blocks = (int) ($events[LineFollowEvent::TYPE_UNFOLLOW] ?? 0);
            $lineUsers = User::query()
                ->where('project_id', $projectId)
                ->whereNotNull('line_user_id')
                ->where('line_user_id', '!=', '')
                ->where('created_at', '<=', $end)
                ->count();

            $rows[] = [
                'month' => $start->format('Y/m'),
                'deliveries' => Announcement::query()
                    ->where('project_id', $projectId)
                    ->where('send_notification', true)
                    ->where('delivery_status', Announcement::DELIVERY_STATUS_SENT)
                    ->whereBetween('sent_at', [$start, $end])
                    ->count(),
                'blocks' => $blocks,
                'follows' => (int) ($events[LineFollowEvent::TYPE_FOLLOW] ?? 0),
                'line_users' => $lineUsers,
                'block_rate' => $lineUsers > 0 ? round($blocks / $lineUsers * 100, 1) : 0.0,
            ];
        }

        return $rows;
    }
}
