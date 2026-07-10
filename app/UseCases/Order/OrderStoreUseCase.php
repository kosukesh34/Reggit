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
        $orderPayload = $input->orderPayload;
        if (StrHelper::isEmptyStringOrNull($orderPayload['order_type'] ?? null)) {
            $orderPayload['order_type'] = OrderType::MOBILE;
        }

        $order = $this->orderService->createOrder($orderPayload, $input->user);

        return $this->orderFactory->create($order);
    }
}
