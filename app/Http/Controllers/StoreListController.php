<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Util\AdminContext;

class StoreListController extends Controller
{
    public function __invoke()
    {
        return response()->json(['stores' => Store::where('project_id', AdminContext::requireCurrentProjectId())->where('is_active', true)->orderBy('id')->get(['id', 'store_key', 'name', 'address', 'phone'])]);
    }
}
