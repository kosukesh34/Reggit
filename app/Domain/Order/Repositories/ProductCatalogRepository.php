<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\ValueObjects\ProductForOrder;

interface ProductCatalogRepository
{
    public function findForOrder(int $productId, int $projectId): ?ProductForOrder;

    public function findForOrderOrFail(int $productId, int $projectId): ProductForOrder;
}
