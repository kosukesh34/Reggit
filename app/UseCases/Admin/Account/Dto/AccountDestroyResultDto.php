<?php

namespace App\UseCases\Admin\Account\Dto;

class AccountDestroyResultDto
{
    public function __construct(
        public bool $success,
        public string $message
    ) {
    }
}
