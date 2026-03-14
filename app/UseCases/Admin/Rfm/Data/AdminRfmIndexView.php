<?php

namespace App\UseCases\Admin\Rfm\Data;

use Illuminate\Support\Collection;

class AdminRfmIndexView
{
    /**
     * @param  Collection<int, array{member_id: int, member_number: string, name: string, recency_days: int|null, frequency: int, monetary: float, segment: string, segment_label: string}>  $rows
     * @param  array<string, array{label: string, count: int, color: string}>  $segmentSummary
     */
    public function __construct(
        public readonly Collection $rows,
        public readonly array $segmentSummary,
        public readonly string $filterSegment,
    ) {}
}
