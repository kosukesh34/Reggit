<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class UpdateQueueSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $this->shopSettingRepository->setValue('queue_avg_serve_minutes', (int) $validated['queue_avg_serve_minutes'], 'integer', $projectId);
    }
}
