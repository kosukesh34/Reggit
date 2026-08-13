<?php

namespace App\UseCases\Admin\Stamp\Data;

use App\Models\StampCard;
use Carbon\Carbon;

class AdminStampGrantQrOutput
{
    public function __construct(
        public readonly StampCard $stampCard,
        public readonly string $stampUrl,
        public readonly Carbon $expiresAt,
    ) {}
}
