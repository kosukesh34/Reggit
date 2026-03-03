<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Services\Smaregi\SmaregiProductSyncService;
use App\UseCases\Admin\Settings\GetSmaregiPosDataUseCase;
use App\UseCases\Admin\Settings\GetSmaregiSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateSmaregiLinkUseCase;
use Illuminate\Http\Request;

class SmaregiController extends AdminBaseController
{
    public function __construct(
        private GetSmaregiSettingsUseCase $getSmaregiSettingsUseCase,
        private GetSmaregiPosDataUseCase $getSmaregiPosDataUseCase,
        private UpdateSmaregiLinkUseCase $updateSmaregiLinkUseCase,
        private SmaregiProductSyncService $smaregiProductSyncService,
    ) {}

    /** 仮想POSデータ確認（開発用・後で削除可） */
    public function posData()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getSmaregiPosDataUseCase->handle($projectId);

        return view('admin.settings.smaregi-pos-data', ['view' => $view]);
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

    /** スマレジの商品をDBに同期する（スマレジ→DB のみ） */
    public function syncProducts(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $result = $this->smaregiProductSyncService->sync($projectId);
            $msg = sprintf(
                '商品を同期しました（新規: %d、更新: %d、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['failed']
            );
            if ($result['failed'] > 0 && isset($result['errors']) && $result['errors'] !== []) {
                $msg .= '。失敗詳細: '.implode('; ', array_slice($result['errors'], 0, 5));
                if (count($result['errors']) > 5) {
                    $msg .= ' …';
                }
            }

            return redirect()->route('admin.settings.smaregi', $this->buildAdminRouteParams())
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.smaregi', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }
    }

    /** DB とスマレジの商品を完全同期する（双方向・データ漏れなく両者を揃える） */
    public function fullSyncProducts(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $redirectTo = $request->get('from') === 'pos-data'
            ? 'admin.settings.smaregi.pos-data'
            : 'admin.settings.smaregi';
        try {
            $result = $this->smaregiProductSyncService->fullSync($projectId);
            $msg = sprintf(
                '完全同期しました（スマレジ→DB: 新規 %d・更新 %d、DB→スマレジ: %d 件登録、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['pushed_to_smaregi'],
                $result['failed']
            );
            if ($result['failed'] > 0 && isset($result['errors']) && $result['errors'] !== []) {
                $msg .= '。失敗詳細: '.implode('; ', array_slice($result['errors'], 0, 5));
                if (count($result['errors']) > 5) {
                    $msg .= ' …';
                }
            }

            return redirect()->route($redirectTo, $this->buildAdminRouteParams())
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route($redirectTo, $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }
    }
}
