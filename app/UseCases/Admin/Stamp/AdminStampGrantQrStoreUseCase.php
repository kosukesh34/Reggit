<?php

namespace App\UseCases\Admin\Stamp;

use App\Enums\StampGrantLimit;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrInput;

class AdminStampGrantQrStoreUseCase
{
    public function handle(AdminStampGrantQrInput $input): StampGrantCode
    {
        $stampCard = StampCard::query()
            ->where('project_id', $input->projectId)
            ->where('is_active', true)
            ->findOrFail($input->stampCardId);
        $expiresMinutes = StampGrantLimit::normalizeExpiresMinutes($input->expiresMinutes);

        $limit = StampGrantLimit::normalize($input->limit);

        return StampGrantCode::query()->create([
            'project_id' => $input->projectId,
            'stamp_card_id' => $stampCard->id,
            'token' => $this->newToken(),
            'amount' => StampGrantLimit::normalizeAmount($input->amount),
            'usage_limit' => $limit,
            'usage_quota' => $limit === StampGrantLimit::ONCE
                ? StampGrantLimit::normalizeQuota($input->quota)
                : 1,
            'expires_at' => $expiresMinutes === StampGrantLimit::NEVER_EXPIRES
                ? null
                : now()->addMinutes($expiresMinutes),
        ]);
    }

    private function newToken(): string
    {
        do {
            $token = bin2hex(random_bytes(6));
        } while (StampGrantCode::query()->where('token', $token)->exists());

        return $token;
    }
}
