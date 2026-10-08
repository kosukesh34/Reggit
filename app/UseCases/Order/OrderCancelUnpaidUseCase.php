<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\Models\User;
use App\Services\UnpaidOrderCancellationService;
use App\Util\AdminContext;

class OrderCancelUnpaidUseCase
{
    public function __construct(private UnpaidOrderCancellationService $cancellation) {}

    public function handle(User $user, int $orderId): Order
    {
        return $this->cancellation->cancel(
            $orderId,
            (int) AdminContext::resolveProjectId((int) $user->project_id),
            (int) $user->id,
        );
    }
}
