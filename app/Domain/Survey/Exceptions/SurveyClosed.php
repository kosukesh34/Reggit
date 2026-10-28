<?php

namespace App\Domain\Survey\Exceptions;

final class SurveyClosed extends \DomainException
{
    public function __construct()
    {
        parent::__construct('このアンケートは受付を終了しました。');
    }
}
