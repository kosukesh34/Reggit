<?php

namespace App\Domain\Order\Repositories;

interface ProductStockRepository
{
    /**
     * 在庫管理対象（stock が null でない）商品のみ減算する。
     */
    public function decrease(int $productId, int $projectId, int $quantity): void;

    /**
     * 在庫管理対象（stock が null でない）商品のみ戻す。
     */
    public function restore(int $productId, int $projectId, int $quantity): void;
}
