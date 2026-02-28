<?php

namespace App\UseCases\Admin\Product;

use App\Services\Admin\ProductAdminService;

class AdminProductUpdateUseCase
{
    private ProductAdminService $productAdminService;

    public function __construct(ProductAdminService $productAdminService)
    {
        $this->productAdminService = $productAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $id, array $validated): void
    {
        $this->productAdminService->update($id, $validated);
    }
}
