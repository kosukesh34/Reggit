<?php

namespace App\Models;

use App\Util\StrHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

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

    public function businessSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectBusinessSetting::class);
    }

    public function lineLiffSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectLineLiffSetting::class);
    }

    public function featureFlags(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectFeatureFlags::class);
    }

    public function adminUiSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectAdminUiSetting::class);
    }

    public function profileSmaregiSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectProfileSmaregiSetting::class);
    }

    public function memberPointsSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectMemberPointsSetting::class);
    }

    public function memberRankSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectMemberRankSetting::class);
    }

    public function walletSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectWalletSetting::class);
    }

    public function announcementSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Settings\ProjectAnnouncementSetting::class);
    }

    public function isSmaregiLinked(): bool
    {
        $contractId = ShopSetting::getValue('smaregi_contract_id', null, $this->id);
        if (is_string($contractId) && trim($contractId) !== '') {
            return true;
        }
        $default = config('smaregi.contract_id');
        $default = is_string($default) ? trim($default) : '';

        return $default !== '';
    }
}
