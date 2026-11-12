<?php

namespace App\Models;

use App\Models\Settings\ProjectFeatureFlags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_key',
        'custom_shop_domain',
        'name',
        'description',
        'is_active',
        'plan',
        'service_status',
        'max_active_stores',
        'analysis_api_enabled',
        'analysis_delivery_enabled',
        'line_plan_enabled',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'plan' => 'string',
        'max_active_stores' => 'integer',
        'analysis_api_enabled' => 'boolean',
        'analysis_delivery_enabled' => 'boolean',
        'line_plan_enabled' => 'boolean',
    ];

    public function isServiceAvailable(): bool
    {
        return $this->is_active && ($this->service_status ?? 'active') === 'active';
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function stampCards(): HasMany
    {
        return $this->hasMany(StampCard::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function memberCoupons(): HasMany
    {
        return $this->hasMany(MemberCoupon::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function featureFlags(): HasOne
    {
        return $this->hasOne(ProjectFeatureFlags::class);
    }
}
