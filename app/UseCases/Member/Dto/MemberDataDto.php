<?php

namespace App\UseCases\Member\Dto;

class MemberDataDto
{
    public function __construct(public array $data)
    {
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
