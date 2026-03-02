<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\UseCases\Admin\Product\AdminProductDeleteUseCase;
use App\UseCases\Admin\Product\AdminProductEditUseCase;
use App\UseCases\Admin\Product\AdminProductIndexUseCase;
use App\UseCases\Admin\Product\AdminProductStoreUseCase;
use App\UseCases\Admin\Product\AdminProductUpdateUseCase;

class ProductController extends AdminBaseController
{
    private AdminProductIndexUseCase $indexUseCase;

    private AdminProductStoreUseCase $storeUseCase;

    private AdminProductEditUseCase $editUseCase;

    private AdminProductUpdateUseCase $updateUseCase;

    private AdminProductDeleteUseCase $deleteUseCase;

    public function __construct(
        AdminProductIndexUseCase $indexUseCase,
        AdminProductStoreUseCase $storeUseCase,
        AdminProductEditUseCase $editUseCase,
        AdminProductUpdateUseCase $updateUseCase,
        AdminProductDeleteUseCase $deleteUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
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

    public function storeProduct(ProductStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を追加しました');
    }

    public function editProduct($id)
    {
        $product = $this->editUseCase->handle((int) $id);

        return view('admin.product-form', compact('product'));
    }

    public function updateProduct(ProductUpdateRequest $request, $id)
    {
        $this->updateUseCase->handle((int) $id, $request->validated());

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を更新しました');
    }

    public function deleteProduct($id)
    {
        $this->deleteUseCase->handle((int) $id);

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を削除しました');
    }
}
