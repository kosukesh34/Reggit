<?php

namespace App\Http\Controllers;

use App\UseCases\Store\ActiveStoreListUseCase;
use App\Util\AdminContext;

class StoreListController extends Controller
{
    public function __invoke(ActiveStoreListUseCase $stores)
    {
        return response()->json(['stores' => $stores->handle(AdminContext::requireCurrentProjectId())]);
    }
}
