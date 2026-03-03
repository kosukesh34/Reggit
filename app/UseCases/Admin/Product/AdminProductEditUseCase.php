<?php

namespace App\UseCases\Admin\Product;

use App\Models\Product;
use App\Services\Admin\ProductAdminService;

class AdminProductEditUseCase
{
    private ProductAdminService $productAdminService;

    public function __construct(ProductAdminService $productAdminService)
    {
        $this->productAdminService = $productAdminService;
    }

    public function handle(int $projectId, int $id): Product
    {
        return $this->productAdminService->findForProjectOrFail($projectId, $id);
    }
}
