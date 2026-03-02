<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetQueueSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateQueueSettingsUseCase;
use Illuminate\Http\Request;

class QueueController extends AdminBaseController
{
    private GetQueueSettingsUseCase $getQueueSettingsUseCase;

    private UpdateQueueSettingsUseCase $updateQueueSettingsUseCase;

    public function __construct(
        GetQueueSettingsUseCase $getQueueSettingsUseCase,
        UpdateQueueSettingsUseCase $updateQueueSettingsUseCase
    ) {
        $this->getQueueSettingsUseCase = $getQueueSettingsUseCase;
        $this->updateQueueSettingsUseCase = $updateQueueSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $data = $this->getQueueSettingsUseCase->handle($projectId);

        return view('admin.settings.queue', ['avgServeMinutes' => $data['avgServeMinutes']]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'queue_avg_serve_minutes' => 'required|integer|min:1|max:120',
        ]);
        $this->updateQueueSettingsUseCase->handle($projectId, $validated);

        return redirect()->route('admin.settings.queue', $this->buildAdminRouteParams())
            ->with('success', '順番待ち設定を更新しました');
    }
}
