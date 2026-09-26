<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Product\AdminProductEditUseCase;
use App\UseCases\Admin\Product\AdminProductIndexUseCase;

class ProductController extends AdminBaseController
{
    public function __construct(
        private AdminProductIndexUseCase $indexUseCase,
        private AdminProductEditUseCase $editUseCase,
    ) {}

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
