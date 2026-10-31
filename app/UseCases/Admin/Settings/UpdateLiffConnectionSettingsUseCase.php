<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Line\LiffConnectionSettingsUpdater;

class UpdateLiffConnectionSettingsUseCase
{
    public function __construct(private readonly LiffConnectionSettingsUpdater $liffConnectionSettings) {}

    /**
     * @param  array{
     *     line_liff_id: string,
     *     line_liff_url: string,
     *     line_friend_add_url?: string|null,
     *     line_channel_access_token?: string|null,
     *     line_channel_secret?: string|null,
     * }  $payload
     */
    public function handle(int $projectId, array $payload): void
    {
        $this->liffConnectionSettings->update($projectId, $payload);
    }
}
