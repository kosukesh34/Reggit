<?php

namespace Tests\Support;

use App\Services\Smaregi\SmaregiApiService;
use App\Util\LogHelper;

/**
 * 統合テストでスマレジ側に作成したリソースを tearDown で削除する。
 * ノイズ（テスト会員・クーポンの残留）を防ぐ。
 */
trait CleansUpSmaregiCreatedResources
{
    /** @var list<array{type: 'customer'|'coupon', id: string}> */
    private array $smaregiCreatedResources = [];

    private ?string $smaregiCleanupContractId = null;

    protected function trackSmaregiCustomer(string $contractId, string $customerId): void
    {
        $customerId = trim($customerId);
        if ($customerId === '') {
            return;
        }
        $this->smaregiCleanupContractId = $contractId;
        $this->smaregiCreatedResources[] = ['type' => 'customer', 'id' => $customerId];
    }

    protected function trackSmaregiCoupon(string $contractId, string $couponId): void
    {
        $couponId = trim($couponId);
        if ($couponId === '') {
            return;
        }
        $this->smaregiCleanupContractId = $contractId;
        $this->smaregiCreatedResources[] = ['type' => 'coupon', 'id' => $couponId];
    }

    protected function tearDown(): void
    {
        $this->cleanupSmaregiCreatedResources();
        parent::tearDown();
    }

    private function cleanupSmaregiCreatedResources(): void
    {
        if ($this->smaregiCleanupContractId === null || $this->smaregiCreatedResources === []) {
            return;
        }

        $contractId = $this->smaregiCleanupContractId;
        $service = app(SmaregiApiService::class);

        foreach (array_reverse($this->smaregiCreatedResources) as $resource) {
            try {
                if ($resource['type'] === 'customer') {
                    $service->deleteCustomer($contractId, $resource['id']);
                } elseif ($resource['type'] === 'coupon') {
                    $service->deleteCoupon($contractId, $resource['id']);
                }
            } catch (\Throwable $e) {
                LogHelper::warning('[Test] Smaregi cleanup failed', [
                    'type' => $resource['type'],
                    'id' => $resource['id'],
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->smaregiCreatedResources = [];
        $this->smaregiCleanupContractId = null;
    }
}
