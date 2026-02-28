<?php

namespace App\UseCases\Admin\Order;

use App\Models\Order;
use App\Services\Admin\OrderAdminService;

class AdminOrderDetailUseCase
{
    private OrderAdminService $orderAdminService;

    public function __construct(OrderAdminService $orderAdminService)
    {
        $this->orderAdminService = $orderAdminService;
    }

    public function handle(int $id): Order
    {
        return $this->orderAdminService->findOrFail($id);
    }
}
