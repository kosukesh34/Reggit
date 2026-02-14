<?php

namespace App\UseCases\Contact\Dto;

class ContactStoreResultDto
{
    public function __construct(public bool $success)
    {
    }
}
