<?php

namespace App\UseCases\Admin\Stamp;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\StampCard;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrOutput;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AdminStampGrantQrUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, string $projectKey, int $stampCardId): AdminStampGrantQrOutput
    {
        $stampCard = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->findOrFail($stampCardId);
        $expiresAt = now()->addMinutes(15);
        $grantUrl = URL::temporarySignedRoute('api.stamps.grant', $expiresAt, [
            'stampCard' => $stampCard->id,
            'nonce' => Str::random(40),
            'project_key' => $projectKey,
        ]);
        $configuredLiffUrl = trim($this->shopSettingRepository->getLineLiffUrl($projectId));
        $liffBaseUrl = $configuredLiffUrl !== '' ? $configuredLiffUrl : url('/liff/'.$projectKey);
        $liffBaseUrl = preg_replace('/#.*$/', '', $liffBaseUrl) ?: url('/liff/'.$projectKey);
        $stampUrl = $liffBaseUrl.'#/stamp?grant='.rawurlencode($grantUrl);

        return new AdminStampGrantQrOutput($stampCard, $stampUrl, $expiresAt);
    }
}
