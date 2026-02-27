<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class QueueController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $avgServeMinutes = ShopSetting::getQueueAvgServeMinutes($projectId);
        return view('admin.settings.queue', compact('avgServeMinutes'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'queue_avg_serve_minutes' => 'required|integer|min:1|max:120',
        ]);
        ShopSetting::setValue('queue_avg_serve_minutes', (int) $validated['queue_avg_serve_minutes'], 'integer', $projectId);
        return redirect()->route('admin.settings.queue', $this->buildAdminRouteParams())
            ->with('success', '順番待ち設定を更新しました');
    }
}
