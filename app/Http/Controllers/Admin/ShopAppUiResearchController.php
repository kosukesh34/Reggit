<?php

namespace App\Http\Controllers\Admin;

use App\Support\ShopAppUiResearchCatalog;
use App\UseCases\Admin\GetShopAppUiResearchUseCase;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShopAppUiResearchController extends AdminBaseController
{
    public function index(GetShopAppUiResearchUseCase $useCase): View
    {
        return view('admin.system-admin.shop-app-ui-research', [
            'research' => $useCase->handle(),
        ]);
    }

    public function asset(string $path): BinaryFileResponse|Response
    {
        $resolved = ShopAppUiResearchCatalog::resolveAssetPath($path);
        if ($resolved === null) {
            abort(404);
        }

        return response()->file($resolved);
    }
}
