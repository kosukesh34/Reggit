<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class MemberCodeSettingsView
{
    public function __construct(
        public int $projectId,
        public string $type,
        public string $format,
        public int $length,
        public string $prefix,
    ) {}
}
