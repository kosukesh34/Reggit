<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\ShopSetting;
use App\Services\Line\LineLiffAdminService;

class UpdateLiffConnectionSettingsUseCase
{
    public function __construct(
        private readonly ShopSettingRepository $shopSettingRepository,
        private readonly LineLiffAdminService $lineLiffAdminService,
    ) {}

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
        $liffId = trim($payload['line_liff_id']);
        $liffUrl = trim($payload['line_liff_url']);
        $this->shopSettingRepository->setLineLiffId($liffId, $projectId);
        $this->shopSettingRepository->setLineLiffUrl($liffUrl, $projectId);
        ShopSetting::setLineFriendAddUrl(
            isset($payload['line_friend_add_url']) ? (string) $payload['line_friend_add_url'] : '',
            $projectId
        );

        $accessToken = isset($payload['line_channel_access_token'])
            ? trim((string) $payload['line_channel_access_token'])
            : '';
        if ($accessToken !== '') {
            ShopSetting::setLineChannelAccessToken($accessToken, $projectId);
        }

        $channelSecret = isset($payload['line_channel_secret'])
            ? trim((string) $payload['line_channel_secret'])
            : '';
        if ($channelSecret !== '') {
            ShopSetting::setLineChannelSecret($channelSecret, $projectId);
        }

        if (config('line.auto_enable_liff_module_mode', true) && $liffId !== '') {
            $this->lineLiffAdminService->hideActionMenu(
                $liffId,
                ShopSetting::getLineChannelAccessToken($projectId),
            );
        }
    }
}
