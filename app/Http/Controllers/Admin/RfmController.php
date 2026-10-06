<?php

namespace App\Http\Controllers\Admin;

use App\Models\Settings\ProjectRfmSetting;
use App\UseCases\Admin\Rfm\AdminRfmIndexUseCase;
use Illuminate\Http\Request;

class RfmController extends AdminBaseController
{
    private AdminRfmIndexUseCase $indexUseCase;

    public function __construct(AdminRfmIndexUseCase $indexUseCase)
    {
        $this->indexUseCase = $indexUseCase;
    }

    public function index(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filterSegment = (string) $request->input('segment', '');
        $view = $this->indexUseCase->handle($projectId, $filterSegment);

        return view('admin.rfm', ['view' => $view, 'settings' => ProjectRfmSetting::forProject($projectId)]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'recency_active_days' => ['required', 'integer', 'min:1', 'max:36500'],
            'recency_sleeping_days' => ['required', 'integer', 'gt:recency_active_days', 'max:36500'],
            'frequency_high' => ['required', 'integer', 'min:1', 'max:1000000'],
            'monetary_high' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ], [], [
            'recency_active_days' => 'アクティブ判定日数',
            'recency_sleeping_days' => '離反リスク判定日数',
            'frequency_high' => '高頻度の購買回数',
            'monetary_high' => '高額の累計購買額',
        ]);

        ProjectRfmSetting::updateOrCreate(['project_id' => $this->resolveAdminProjectId()], $validated);

        return redirect()->route('admin.rfm', ['project_key' => $request->route('project_key')])
            ->with('success', 'RFMの判定基準を保存しました。');
    }
}
