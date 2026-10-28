<?php

namespace App\Domain\Survey;

use App\Util\EnumBase;

/** 回答依頼を自動で送るきっかけ。きっかけの日から N 日後に1回だけ送る。 */
class SurveyInviteTrigger extends EnumBase
{
    public const VISIT = 'visit';

    public const ORDER = 'order';

    public const RESERVATION = 'reservation';

    public const REGISTRATION = 'registration';

    /** @var array<string, string> */
    protected static array $values = [
        self::VISIT => '来店',
        self::ORDER => '注文の完了',
        self::RESERVATION => '予約の来店（完了）',
        self::REGISTRATION => '会員登録',
    ];
}
