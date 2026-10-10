<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\Store;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Console\Command;

class RetryCashPickupsCommand extends Command
{
    protected $signature = 'smaregi:retry-cash-pickups';

    protected $description = 'Retry persisted completed cash order pickups';

    public function handle(SmaregiOrderTransactionOutboundService $transactions, StoreContext $stores): int
    {
        $orders = Order::withoutGlobalScopes()->where('status', OrderStatus::COMPLETED)
            ->where('payment_method', PaymentMethod::CASH)->whereNotNull('user_id')
            ->where('smaregi_pickup_pending', true)->where('smaregi_pickup_retry_at', '<=', now())
            ->orderBy('smaregi_pickup_retry_at')->limit(100)->get();
        $previous = AdminContext::getCurrentProject();
        foreach ($orders as $order) {
            try {
                AdminContext::setCurrentProject(Project::findOrFail($order->project_id));
                $store = Store::where('project_id', $order->project_id)->findOrFail($order->store_id);
                $stores->runForStore($store, fn () => $transactions->finalizeCashLayawayWithRetry(
                    (int) $order->id, (int) $order->project_id, (int) $order->user_id,
                ));
            } catch (\Throwable $exception) {
                report($exception);
                Order::withoutGlobalScopes()->whereKey($order->id)->where('smaregi_pickup_retry_at', '<=', now())
                    ->update(['smaregi_pickup_retry_at' => now()->addMinute()]);
            } finally {
                AdminContext::setCurrentProject($previous);
            }
        }

        return self::SUCCESS;
    }
}
