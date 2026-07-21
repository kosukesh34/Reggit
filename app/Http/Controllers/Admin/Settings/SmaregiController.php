<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\AdminFullSyncProductsWithSmaregiUseCase;
use App\UseCases\Admin\Settings\AdminSyncProductsFromSmaregiUseCase;
use App\UseCases\Admin\Settings\GetSmaregiSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateSmaregiLinkUseCase;
use Illuminate\Http\Request;

class SmaregiController extends AdminBaseController
{
    public function __construct(
        private GetSmaregiSettingsUseCase $getSmaregiSettingsUseCase,
        private UpdateSmaregiLinkUseCase $updateSmaregiLinkUseCase,
        private AdminSyncProductsFromSmaregiUseCase $syncProductsFromSmaregiUseCase,
        private AdminFullSyncProductsWithSmaregiUseCase $fullSyncProductsWithSmaregiUseCase,
    ) {}

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
            'smaregi_store_id' => 'nullable|string|max:64',
            'smaregi_terminal_id' => 'nullable|string|max:64',
        ]);
        $projectId = $this->resolveAdminProjectId();
        $contractId = $request->input('smaregi_contract_id');
        $storeId = $request->input('smaregi_store_id');
        $terminalId = $request->input('smaregi_terminal_id');
        $this->updateSmaregiLinkUseCase->handle(
            $projectId,
            is_string($contractId) ? $contractId : null,
            is_string($storeId) ? $storeId : '',
            is_string($terminalId) ? $terminalId : '',
        );

        return redirect()->route('admin.settings.smaregi', $this->buildAdminRouteParams())
            ->with('success', 'スマレジ連携を更新しました');
    }

    /** スマレジの商品をDBに同期する（スマレジ→DB のみ） */
    public function syncProducts(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $result = $this->syncProductsFromSmaregiUseCase->handle($projectId);
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
        try {
            $result = $this->fullSyncProductsWithSmaregiUseCase->handle($projectId);
            $msg = sprintf(
                '完全同期しました（スマレジ→DB: 新規 %d・更新 %d、DB→スマレジ: %d 件登録、スマレジ重複削除: %d、在庫反映: %d、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['pushed_to_smaregi'],
                $result['deleted_from_smaregi'],
                $result['stock_synced'],
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
}
