<?php

namespace App\UseCases\Admin\Product;

use App\Services\Admin\ProductAdminService;

class AdminProductDeleteUseCase
{
    private ProductAdminService $productAdminService;

    public function __construct(ProductAdminService $productAdminService)
    {
        $this->productAdminService = $productAdminService;
    }

    public function handle(int $id): void
    {
        $this->productAdminService->delete($id);
    }
}
