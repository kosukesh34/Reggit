<?php

namespace App\UseCases\Admin\Stamp;

use App\Enums\StampGrantLimit;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use App\UseCases\Admin\Stamp\Data\AdminStampGrantQrIndexOutput;

class AdminStampGrantQrIndexUseCase
{
    public function handle(
        int $projectId,
        int $stampCardId,
        int $expiresMinutes,
        int $amount,
        string $limit,
        int $quota,
        ?int $issuedCodeId = null,
    ): AdminStampGrantQrIndexOutput {
        $stampCard = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->findOrFail($stampCardId);

        $codes = StampGrantCode::query()
            ->where('stamp_card_id', $stampCard->id)
            ->withCount('redemptions')
            ->orderByDesc('id')
            ->get();

        $issuedCode = null;
        if ($issuedCodeId !== null && $issuedCodeId > 0) {
            $issuedCode = $codes->firstWhere('id', $issuedCodeId);
        }

        return new AdminStampGrantQrIndexOutput(
            $stampCard,
            $codes,
            StampGrantLimit::normalizeExpiresMinutes($expiresMinutes),
            StampGrantLimit::normalizeAmount($amount),
            StampGrantLimit::normalize($limit),
            StampGrantLimit::normalizeQuota($quota),
            $issuedCode,
        );
    }
}
