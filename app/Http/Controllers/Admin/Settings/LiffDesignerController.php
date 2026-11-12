<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use Illuminate\Http\RedirectResponse;

class LiffDesignerController extends AdminBaseController
{
    public function index(): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'LINE専用の画面デザインは廃止しました。ホーム・メニューがSkipとLINEの共通画面です。');
    }
}
