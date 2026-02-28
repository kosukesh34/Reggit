<?php

namespace App\UseCases\Admin\Product;

use App\Services\Admin\ProductAdminService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminProductIndexUseCase
{
    private const PER_PAGE = 20;

    private ProductAdminService $productAdminService;

    public function __construct(ProductAdminService $productAdminService)
    {
        $this->productAdminService = $productAdminService;
    }

    public function handle(): LengthAwarePaginator
    {
        return $this->productAdminService->getPaginatedList(self::PER_PAGE);
    }
}
