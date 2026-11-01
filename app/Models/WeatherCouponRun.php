<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class WeatherCouponRun extends Model
{
    use BelongsToProject;

    public const STATUS_PENDING = 'pending';

    public const STATUS_NO_RAIN = 'no_rain';

    public const STATUS_SENT = 'sent';

    public const STATUS_LIMIT_REACHED = 'limit_reached';

    public const STATUS_FAILED = 'failed';

    /** この状態になった営業日は、もう判定しない */
    public const FINAL_STATUSES = [self::STATUS_SENT, self::STATUS_LIMIT_REACHED];

    protected $fillable = [
        'project_id',
        'weather_coupon_rule_id',
        'business_date',
        'status',
        'max_precipitation_probability',
        'checked_at',
        'coupon_delivery_id',
        'issued_count',
        'error_message',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'business_date' => 'date',
        'checked_at' => 'datetime',
        'max_precipitation_probability' => 'integer',
        'issued_count' => 'integer',
    ];
}
