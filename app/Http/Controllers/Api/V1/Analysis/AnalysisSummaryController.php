<?php

namespace App\Http\Controllers\Api\V1\Analysis;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Admin\Analytics\AdminAnalyticsUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AnalysisSummaryController extends BaseApiController
{
    public function __construct(
        private AdminAnalyticsUseCase $adminAnalyticsUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $start = (string) $request->query('start', now()->subDays(29)->toDateString());
        $end = (string) $request->query('end', now()->toDateString());

        try {
            $startDate = Carbon::createFromFormat('!Y-m-d', $start);
            $endDate = Carbon::createFromFormat('!Y-m-d', $end);
            if ($startDate === false || $endDate === false || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end || $startDate->gt($endDate) || $startDate->diffInDays($endDate) > 366) {
                return $this->error('期間は366日以内の正しい日付で指定してください', 422);
            }
        } catch (\Throwable) {
            return $this->error('期間は366日以内の正しい日付で指定してください', 422);
        }

        try {
            $view = $this->adminAnalyticsUseCase->handle($projectId, $start, $end);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Analysis] summary failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('分析の取得に失敗しました', 500);
        }

        return $this->success([
            'start' => $view->start,
            'end' => $view->end,
            'summary' => $view->summary,
            'chart' => $view->chart,
        ]);
    }
}
