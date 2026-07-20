<?php

namespace App\UseCases\Admin\Member\Data;

class AdminMemberHistorySectionOutput
{
    public function __construct(
        public string $html,
        public int $total,
        public string $section,
        public string $keyword,
        public string $from = '',
        public string $to = '',
    ) {}
}
