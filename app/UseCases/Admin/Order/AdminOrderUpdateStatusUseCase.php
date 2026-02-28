<?php

namespace App\UseCases\Admin\Order;

use App\Services\Admin\OrderAdminService;

class AdminOrderUpdateStatusUseCase
{
    private OrderAdminService $orderAdminService;

    public function __construct(OrderAdminService $orderAdminService)
    {
        $this->orderAdminService = $orderAdminService;
    }

    public function handle(int $id, string $status): void
    {
        $this->orderAdminService->updateStatus($id, $status);
    }
}
