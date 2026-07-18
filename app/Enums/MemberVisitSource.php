<?php

namespace App\Enums;

use App\Util\EnumBase;

class MemberVisitSource extends EnumBase
{
    protected static array $values = [
        'order' => '注文',
        'admin' => '管理画面',
        'smaregi' => 'スマレジPOS',
    ];

    public const ORDER = 'order';

    public const ADMIN = 'admin';

    public const SMAREGI = 'smaregi';
}
