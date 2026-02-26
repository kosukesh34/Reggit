<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiContractStatus extends EnumBase
{
    protected static array $values = [
        'active' => '有効',
        'inactive' => '無効',
    ];

    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
}
