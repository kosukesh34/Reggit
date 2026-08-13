<?php

namespace App\UseCases\Admin\Stamp;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\StampGrantLimit;
use App\Models\StampCard;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrInput;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrOutput;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AdminStampGrantQrUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(AdminStampGrantQrInput $input): AdminStampGrantQrOutput
    {
        $stampCard = StampCard::query()
            ->where('project_id', $input->projectId)
            ->where('is_active', true)
            ->findOrFail($input->stampCardId);
        $expiresMinutes = StampGrantLimit::normalizeExpiresMinutes($input->expiresMinutes);
        $amount = StampGrantLimit::normalizeAmount($input->amount);
        $limit = StampGrantLimit::normalize($input->limit);
        $expiresAt = now()->addMinutes($expiresMinutes);
        $grantUrl = URL::temporarySignedRoute('api.stamps.grant', $expiresAt, [
            'stampCard' => $stampCard->id,
            'nonce' => Str::random(40),
            'project_key' => $input->projectKey,
            'amount' => $amount,
            'limit' => $limit,
        ]);
        $configuredLiffUrl = trim($this->shopSettingRepository->getLineLiffUrl($input->projectId));
        $liffBaseUrl = $configuredLiffUrl !== '' ? $configuredLiffUrl : url('/liff/'.$input->projectKey);
        $liffBaseUrl = preg_replace('/#.*$/', '', $liffBaseUrl) ?: url('/liff/'.$input->projectKey);
        $stampUrl = $liffBaseUrl.'#/stamp?grant='.rawurlencode($grantUrl);

        return new AdminStampGrantQrOutput(
            $stampCard,
            $stampUrl,
            $expiresAt,
            $expiresMinutes,
            $amount,
            $limit,
        );
    }
}
