<?php

namespace App\Domain\Survey\Exceptions;

/** 回答が質問の条件を満たさない。キーは answers.{質問番号} */
final class InvalidSurveyAnswers extends \DomainException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('アンケートの回答を確認してください。');
    }
}
