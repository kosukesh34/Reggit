<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsQueueRequest;
use App\UseCases\Admin\Settings\GetQueueSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateQueueSettingsUseCase;

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

        return view('admin.settings.queue', [
            'avgServeMinutes' => $data['avgServeMinutes'],
            'requireMembership' => $data['requireMembership'],
            'channelLine' => $data['channelLine'],
            'channelNative' => $data['channelNative'],
        ]);
    }

    public function update(SettingsQueueRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateQueueSettingsUseCase->handle($projectId, $request->validatedSettings());

        return redirect()->route('admin.settings.queue', $this->buildAdminRouteParams())
            ->with('success', '順番待ち設定を更新しました');
    }
}
