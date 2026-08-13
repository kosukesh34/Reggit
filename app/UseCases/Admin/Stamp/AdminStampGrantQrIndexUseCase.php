<?php

namespace App\UseCases\Admin\Stamp;

use App\Enums\StampGrantLimit;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrIndexOutput;

class AdminStampGrantQrIndexUseCase
{
    public function handle(int $projectId, int $stampCardId, int $expiresMinutes, int $amount, string $limit): AdminStampGrantQrIndexOutput
    {
        $stampCard = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->findOrFail($stampCardId);

        return new AdminStampGrantQrIndexOutput(
            $stampCard,
            StampGrantCode::query()
                ->where('stamp_card_id', $stampCard->id)
                ->orderByDesc('id')
                ->get(),
            StampGrantLimit::normalizeExpiresMinutes($expiresMinutes),
            StampGrantLimit::normalizeAmount($amount),
            StampGrantLimit::normalize($limit),
        );
    }
}
