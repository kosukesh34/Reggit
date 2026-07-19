<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class UpdateQueueSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /**
     * @param  array{
     *     queue_avg_serve_minutes: int,
     *     queue_require_membership: bool,
     *     queue_channel_line: bool,
     *     queue_channel_native: bool
     * }  $validated
     */
    public function handle(int $projectId, array $validated): void
    {
        $this->shopSettingRepository->setValue(
            'queue_avg_serve_minutes',
            (int) $validated['queue_avg_serve_minutes'],
            'integer',
            $projectId
        );
        $this->shopSettingRepository->setValue(
            'queue_require_membership',
            (bool) $validated['queue_require_membership'],
            'boolean',
            $projectId
        );
        $this->shopSettingRepository->setValue(
            'queue_channel_line',
            (bool) $validated['queue_channel_line'],
            'boolean',
            $projectId
        );
        $this->shopSettingRepository->setValue(
            'queue_channel_native',
            (bool) $validated['queue_channel_native'],
            'boolean',
            $projectId
        );
    }
}
