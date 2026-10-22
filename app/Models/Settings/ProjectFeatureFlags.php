<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeatureFlags extends Model
{
    protected $table = 'project_feature_flags';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'feature_home_enabled',
        'feature_orders_enabled',
        'feature_reservations_enabled',
        'feature_queue_enabled',
        'feature_stamps_enabled',
        'feature_coupons_enabled',
        'feature_members_enabled',
        'feature_announcements_enabled',
        'feature_payments_enabled',
        'feature_gallery_enabled',
        'feature_shop_enabled',
        'feature_gacha_enabled',
        'feature_scratch_enabled',
        'feature_roulette_enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'feature_home_enabled' => 'boolean',
        'feature_orders_enabled' => 'boolean',
        'feature_reservations_enabled' => 'boolean',
        'feature_queue_enabled' => 'boolean',
        'feature_stamps_enabled' => 'boolean',
        'feature_coupons_enabled' => 'boolean',
        'feature_members_enabled' => 'boolean',
        'feature_announcements_enabled' => 'boolean',
        'feature_payments_enabled' => 'boolean',
        'feature_gallery_enabled' => 'boolean',
        'feature_shop_enabled' => 'boolean',
        'feature_gacha_enabled' => 'boolean',
        'feature_scratch_enabled' => 'boolean',
        'feature_roulette_enabled' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'feature_home_enabled' => true,
            'feature_orders_enabled' => true,
            'feature_reservations_enabled' => true,
            'feature_queue_enabled' => true,
            'feature_stamps_enabled' => true,
            'feature_coupons_enabled' => true,
            'feature_members_enabled' => true,
            'feature_announcements_enabled' => true,
            'feature_payments_enabled' => true,
            'feature_gallery_enabled' => true,
            'feature_shop_enabled' => false,
            'feature_gacha_enabled' => false,
            'feature_scratch_enabled' => false,
            'feature_roulette_enabled' => false,
        ];
    }
}
