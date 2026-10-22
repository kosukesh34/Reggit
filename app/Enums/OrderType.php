<?php

namespace App\Enums;

use App\Util\EnumBase;

class OrderType extends EnumBase
{
    protected static array $values = [
        'mobile' => 'モバイル注文',
        'in_store' => '店内注文',
        'pos' => 'POS会計',
        'shipping' => '配送注文',
        'ticket' => '回数券購入',
        'subscription' => 'サブスク',
    ];

    public const MOBILE = 'mobile';

    public const IN_STORE = 'in_store';

    public const POS = 'pos';

    public const SHIPPING = 'shipping';

    public const TICKET = 'ticket';

    public const SUBSCRIPTION = 'subscription';
}
