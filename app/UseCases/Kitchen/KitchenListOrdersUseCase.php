<?php

namespace App\UseCases\Kitchen;

use App\Services\Kitchen\KitchenOrderService;

class KitchenListOrdersUseCase
{
    public function __construct(
        private KitchenOrderService $kitchenOrderService,
    ) {}

    /**
     * @return list<array{
     *     id: int,
     *     order_number: string,
     *     status: string,
     *     order_type: string|null,
     *     notes: string|null,
     *     created_at: string|null,
     *     items: list<array{name: string, quantity: int, notes: string|null}>
     * }>
     */
    public function handle(int $projectId): array
    {
        return $this->kitchenOrderService->listActiveOrders($projectId);
    }
}
