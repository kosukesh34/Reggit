<?php

namespace App\UseCases\Member\Dto;

class MemberPointCardDto
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public bool $requiresMembership,
        public array $data
    ) {
    }
}
