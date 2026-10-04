<?php

namespace App\UseCases\Admin\Qr\Data;

use App\Models\PointGrantCode;
use App\Models\StampCard;
use App\Models\StampGrantCode;
use Illuminate\Database\Eloquent\Collection;

class QrIndexOutput
{
    /**
     * @param  array<string, array{title: string, url: string, description: string}>  $links
     * @param  Collection<int, StampCard>  $stampCards
     */
    public function __construct(public array $links, public Collection $stampCards, public ?PointGrantCode $issuedPointCode = null,
        public ?StampGrantCode $issuedStampCode = null) {}
}
