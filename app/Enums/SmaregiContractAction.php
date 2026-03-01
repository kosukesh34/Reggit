<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiContractAction extends EnumBase
{
    protected static array $values = [
        'start' => '利用開始',
        'end' => '利用停止',
        'change-plan' => 'プラン変更',
        'change-options' => 'オプション変更',
        'force-stop' => '強制停止',
        'cancel-force-stop' => '強制停止解除',
    ];

    public const START = 'start';

    public const END = 'end';

    public const CHANGE_PLAN = 'change-plan';

    public const CHANGE_OPTIONS = 'change-options';

    public const FORCE_STOP = 'force-stop';

    public const CANCEL_FORCE_STOP = 'cancel-force-stop';

    public static function isInactiveAction(string $action): bool
    {
        return $action === self::END || $action === self::FORCE_STOP;
    }
}
