<?php

namespace App\UseCases\Order;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Models\Order;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Services\UnpaidOrderCancellationService;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;

class OrderCancelUnpaidUseCase
{
    public function __construct(private UnpaidOrderCancellationService $cancellation, private CurrentMemberPrincipal $principals) {}

    public function handle(User $user, int $orderId): Order
    {
        $projectId = (int) AdminContext::resolveProjectId((int) $user->project_id);

        return DB::transaction(function () use ($user, $orderId, $projectId): Order {
            // Cancellation and reward restoration already lock Order before User and Member.
            Order::query()->where('project_id', $projectId)->where('user_id', $user->id)
                ->whereKey($orderId)->lockForUpdate()->firstOrFail();
            $owner = $this->principals->lock($user, $projectId);
            if ($owner === null) {
                throw new OrderOperationException('ログインし直してください');
            }

            return $this->cancellation->cancel($orderId, $projectId, (int) $owner->id);
        });
    }
}
