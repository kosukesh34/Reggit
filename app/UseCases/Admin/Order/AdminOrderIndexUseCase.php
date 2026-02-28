<?php

namespace App\UseCases\Admin\Order;

use App\Services\Admin\OrderAdminService;
use App\UseCases\Admin\Order\Data\AdminOrderIndexOutput;

class AdminOrderIndexUseCase
{
    private const PER_PAGE = 20;

    private OrderAdminService $orderAdminService;

    public function __construct(OrderAdminService $orderAdminService)
    {
        $this->orderAdminService = $orderAdminService;
    }

    public function handle(?string $status): AdminOrderIndexOutput
    {
        $orders = $this->orderAdminService->getPaginatedList($status, self::PER_PAGE);
        $statusCounts = $this->orderAdminService->getStatusCounts();

        return new AdminOrderIndexOutput($orders, $statusCounts);
    }
}
