<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;

/**
 * メニュー設定はホーム設定に統合済み。旧URLはホーム設定へリダイレクトする。
 */
class MenuController extends AdminBaseController
{
    public function index()
    {
        return redirect()->route('admin.settings.home', $this->buildAdminRouteParams());
    }

    public function update()
    {
        return redirect()->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('info', 'メニュー設定はホーム設定に統合されています。タブの表示・ラベル・アイコンはホーム設定で変更してください。');
    }
}
