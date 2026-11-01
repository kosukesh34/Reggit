<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherCouponRule extends Model
{
    use BelongsToProject;

    public const TARGET_STORE_MEMBERS = 'store_members';

    public const TARGET_ALL_MEMBERS = 'all_members';

    protected $fillable = [
        'project_id',
        'store_id',
        'coupon_id',
        'min_precipitation_probability',
        'min_rain_hours',
        'check_hours_before_open',
        'cutoff_hours_before_close',
        'weekly_limit',
        'target',
        'requires_approval',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'min_precipitation_probability' => 'integer',
        'min_rain_hours' => 'integer',
        'check_hours_before_open' => 'integer',
        'cutoff_hours_before_close' => 'integer',
        'weekly_limit' => 'integer',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
