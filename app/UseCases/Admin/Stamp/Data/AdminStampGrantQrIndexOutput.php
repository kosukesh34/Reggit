<?php

namespace App\UseCases\Admin\Stamp\Data;

use App\Models\StampCard;
use App\Models\StampGrantCode;
use Illuminate\Support\Collection;

class AdminStampGrantQrIndexOutput
{
    /**
     * @param  Collection<int, StampGrantCode>  $codes
     */
    public function __construct(
        public readonly StampCard $stampCard,
        public readonly Collection $codes,
        public readonly int $expiresMinutes,
        public readonly int $amount,
        public readonly string $limit,
        public readonly int $quota,
        public readonly ?StampGrantCode $issuedCode,
    ) {}
}
