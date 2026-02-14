<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Services\Admin\ProductAdminService;

class ProductController extends AdminBaseController
{
    public function __construct(
        private ProductAdminService $productAdminService
    ) {
    }

    public function products()
    {
        $products = $this->productAdminService->getPaginatedList(20);
        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.product-form');
    }

    public function storeProduct(ProductStoreRequest $request)
    {
        $this->productAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を追加しました');
    }

    public function editProduct($id)
    {
        $product = $this->productAdminService->findOrFail((int) $id);
        return view('admin.product-form', compact('product'));
    }

    public function updateProduct(ProductUpdateRequest $request, $id)
    {
        $this->productAdminService->update((int) $id, $request->validated());
        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を更新しました');
    }

    public function deleteProduct($id)
    {
        $this->productAdminService->delete((int) $id);
        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を削除しました');
    }
}
