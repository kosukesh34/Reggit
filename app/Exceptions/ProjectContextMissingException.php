<?php

namespace App\Exceptions;

use RuntimeException;

class ProjectContextMissingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('店舗情報を確認できませんでした');
    }
}
