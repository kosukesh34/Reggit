<?php

namespace App\Http\Controllers\Admin;

use App\Services\Smaregi\SmaregiProductSyncService;
use App\UseCases\Admin\Product\AdminProductEditUseCase;
use App\UseCases\Admin\Product\AdminProductIndexUseCase;

class ProductController extends AdminBaseController
{
    public function __construct(
        private AdminProductIndexUseCase $indexUseCase,
        private AdminProductEditUseCase $editUseCase,
    ) {}

    public function importSmaregi(SmaregiProductSyncService $sync)
    {
        try {
            $result = $sync->sync($this->resolveAdminProjectId());

            return back()->with($result['failed'] ? 'error' : 'success', sprintf('スマレジから取り込み：新規 %d件、更新 %d件、失敗 %d件', $result['created'], $result['updated'], $result['failed']));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function products()
    {
        $products = $this->indexUseCase->handle();

        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.product-form');
    }

    public function editProduct(string $projectKey, $id)
    {
        $product = $this->editUseCase->handle($this->resolveAdminProjectId(), (int) $id);

        return view('admin.product-form', compact('product'));
    }
}
