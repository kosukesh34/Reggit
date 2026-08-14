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

    public function handle(?string $status, ?int $userId = null): AdminOrderIndexOutput
    {
        $orders = $this->orderAdminService->getPaginatedList($status, self::PER_PAGE, $userId);
        $statusCounts = $this->orderAdminService->getStatusCounts($userId);

        return new AdminOrderIndexOutput($orders, $statusCounts);
    }
}
