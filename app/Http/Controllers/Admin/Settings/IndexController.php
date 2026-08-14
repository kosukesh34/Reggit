<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use Illuminate\View\View;

class IndexController extends AdminBaseController
{
    public function index(): View
    {
        return view('admin.settings.index');
    }
}
