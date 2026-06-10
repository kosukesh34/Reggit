<?php

namespace App\UseCases\Order;

use App\Enums\OrderType;
use App\Services\OrderService;
use App\UseCases\Order\Data\OrderFactory;
use App\UseCases\Order\Data\OrderOutput;
use App\UseCases\Order\Data\OrderStoreInput;
use App\Util\StrHelper;

class OrderStoreUseCase
{
    public function __construct(
        private OrderService $orderService,
        private OrderFactory $orderFactory,
    ) {}

    public function handle(OrderStoreInput $input): OrderOutput
    {
        $validated = $input->validated;
        if (StrHelper::isEmptyStringOrNull($validated['order_type'] ?? null)) {
            $validated['order_type'] = OrderType::MOBILE;
        }
        $order = $this->orderService->createOrder($validated, $input->user);

        return $this->orderFactory->create($order);
    }
}
