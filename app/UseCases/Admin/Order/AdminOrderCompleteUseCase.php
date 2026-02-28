<?php

namespace App\UseCases\Admin\Order;

use App\Services\Admin\OrderAdminService;

class AdminOrderCompleteUseCase
{
    private OrderAdminService $orderAdminService;

    public function __construct(OrderAdminService $orderAdminService)
    {
        $this->orderAdminService = $orderAdminService;
    }

    public function handle(int $id): void
    {
        $this->orderAdminService->complete($id);
    }
}
