<?php

namespace App\Domain\Survey\Exceptions;

final class SurveyWithoutQuestions extends \DomainException
{
    public function __construct()
    {
        parent::__construct('質問を1つ以上設定してください。');
    }
}
