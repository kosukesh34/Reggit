<?php

namespace App\UseCases\Admin\Product;

use App\Services\Admin\ProductAdminService;

class AdminProductStoreUseCase
{
    private ProductAdminService $productAdminService;

    public function __construct(ProductAdminService $productAdminService)
    {
        $this->productAdminService = $productAdminService;
    }

    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $this->productAdminService->create($projectId, $validated);
    }
}
