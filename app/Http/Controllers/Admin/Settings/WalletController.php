<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Support\AdminSettingsInputFactory;
use App\UseCases\Admin\Settings\GetWalletSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateWalletSettingsUseCase;
use Illuminate\Http\Request;

/**
 * iPhone（Apple Wallet）・Google Wallet に表示する画像を管理画面から設定する。
 */
class WalletController extends AdminBaseController
{
    public function __construct(
        private GetWalletSettingsUseCase $getWalletSettingsUseCase,
        private UpdateWalletSettingsUseCase $updateWalletSettingsUseCase,
        private AdminSettingsInputFactory $inputFactory,
    ) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getWalletSettingsUseCase->handle($projectId);

        return view('admin.settings.wallet', ['view' => $view]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'wallet_apple_icon' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'wallet_apple_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'wallet_google_program_logo_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'wallet_google_hero_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'wallet_apple_icon_clear' => 'nullable|in:0,1',
            'wallet_apple_logo_clear' => 'nullable|in:0,1',
            'wallet_google_program_logo_clear' => 'nullable|in:0,1',
            'wallet_google_hero_clear' => 'nullable|in:0,1',
        ]);
        $messages = $this->updateWalletSettingsUseCase->handle(
            $this->inputFactory->walletSettings($projectId, $request, $validated),
        );
        $msg = $messages !== [] ? implode(' ', $messages) : 'Wallet 画像設定を保存しました。';

        return redirect()->route('admin.settings.wallet', $this->buildAdminRouteParams())
            ->with('success', $msg);
    }
}
