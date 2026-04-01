<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Services\WalletPassSyncService;

class PointTransactionObserver
{
    public function created(PointTransaction $pointTransaction): void
    {
        app(WalletPassSyncService::class)->touchAndNotifyByPointTransaction($pointTransaction);
    }
}
