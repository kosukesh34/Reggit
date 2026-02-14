<?php

namespace App\Enums;

use App\Util\EnumBase;

class MemberStatus extends EnumBase
{
    protected static array $values = [
        'active' => '有効',
        'inactive' => '無効',
        'suspended' => '停止',
    ];

    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const SUSPENDED = 'suspended';
}
