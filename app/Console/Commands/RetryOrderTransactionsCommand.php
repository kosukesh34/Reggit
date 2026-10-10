<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Project;
use App\Models\Store;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Console\Command;

class RetryOrderTransactionsCommand extends Command
{
    protected $signature = 'smaregi:retry-order-transactions';

    protected $description = 'Retry persisted order transaction creation requests';

    public function handle(SmaregiOrderTransactionOutboundService $transactions, StoreContext $stores): int
    {
        $orders = Order::withoutGlobalScopes()->whereNotNull('user_id')
            ->where('smaregi_create_pending', true)->where('smaregi_create_retry_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('smaregi_create_leased_until')->orWhere('smaregi_create_leased_until', '<=', now()))
            ->orderBy('smaregi_create_retry_at')->limit(100)->get();
        $previous = AdminContext::getCurrentProject();
        foreach ($orders as $order) {
            try {
                AdminContext::setCurrentProject(Project::findOrFail($order->project_id));
                $store = Store::where('project_id', $order->project_id)->findOrFail($order->store_id);
                $stores->runForStore($store, fn () => $transactions->syncOrderTransactionWithRetry(
                    (int) $order->id, (int) $order->project_id, (int) $order->user_id,
                ));
            } catch (\Throwable $exception) {
                report($exception);
                Order::withoutGlobalScopes()->whereKey($order->id)->where('smaregi_create_retry_at', '<=', now())
                    ->update(['smaregi_create_retry_at' => now()->addMinute()]);
            } finally {
                AdminContext::setCurrentProject($previous);
            }
        }

        return self::SUCCESS;
    }
}
