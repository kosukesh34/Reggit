<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ShopSetting;
use App\Util\StrHelper;

class Project extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'project_key',
        'name',
        'description',
        'is_active',
        'plan',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'plan' => 'string',
    ];

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

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function userDevices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function shopSettings(): HasMany
    {
        return $this->hasMany(ShopSetting::class);
    }

    public function isSmaregiLinked(): bool
    {
        $contractId = ShopSetting::getValue('smaregi_contract_id', null, $this->id);
        return !StrHelper::isEmptyStringOrNull($contractId);
    }
}
