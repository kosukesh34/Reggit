<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Analytics\AdminAnalyticsUseCase;
use Illuminate\Http\Request;

class AnalyticsController extends AdminBaseController
{
    public function __construct(
        private AdminAnalyticsUseCase $adminAnalyticsUseCase
    ) {
    }

    public function analytics(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $start = $request->query('start', now()->subDays(29)->toDateString());
        $end = $request->query('end', now()->toDateString());
        $view = $this->adminAnalyticsUseCase->handle($projectId, $start, $end);

        return view('admin.analytics', ['view' => $view]);
    }
}
