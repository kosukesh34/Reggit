<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetSmaregiSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateSmaregiLinkUseCase;
use Illuminate\Http\Request;

class SmaregiController extends AdminBaseController
{
    public function __construct(
        private GetSmaregiSettingsUseCase $getSmaregiSettingsUseCase,
        private UpdateSmaregiLinkUseCase $updateSmaregiLinkUseCase,
    ) {
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getSmaregiSettingsUseCase->handle($projectId);
        return view('admin.settings.smaregi', ['view' => $view]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'smaregi_contract_id' => 'nullable|string|max:255',
        ]);
        $projectId = $this->resolveAdminProjectId();
        $contractId = $request->input('smaregi_contract_id');
        $this->updateSmaregiLinkUseCase->handle($projectId, is_string($contractId) ? $contractId : null);
        return redirect()->route('admin.settings.smaregi', $this->buildAdminRouteParams())
            ->with('success', 'スマレジ連携を更新しました');
    }
}
