<?php

namespace App\Enums;

use App\Util\EnumBase;

class HelpArticleCategory extends EnumBase
{
    public const GUIDE = 'guide';

    public const FAQ = 'faq';

    protected static array $values = [
        self::GUIDE => '使い方',
        self::FAQ => 'よくある質問',
    ];
}
