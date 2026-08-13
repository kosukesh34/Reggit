<?php

namespace App\UseCases\Kitchen;

use App\Services\Kitchen\KitchenOrderService;

class KitchenUpdateOrderStatusUseCase
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     order_number: string,
     *     status: string,
     *     order_type: string|null,
     *     notes: string|null,
     *     created_at: string|null,
     *     items: list<array{name: string, quantity: int, notes: string|null}>
     * }
     */
    public function handle(int $projectId, int $orderId, string $status): array
    {
        return $this->kitchenOrderService->updateStatus($projectId, $orderId, $status);
    }
}
