<?php

namespace App\Enums;

use App\Util\EnumBase;

class ProjectPlan extends EnumBase
{
    protected static array $values = [
        'normal' => 'ノーマルプラン',
        'distribution' => '配信プラン',
        'premium' => 'プレミアムプラン',
    ];

    public const NORMAL = 'normal';

    public const DISTRIBUTION = 'distribution';

    public const PREMIUM = 'premium';
}
