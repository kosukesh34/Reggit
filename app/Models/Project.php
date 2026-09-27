<?php

namespace App\Models;

use App\Domain\Settings\Repositories\SmaregiSettings;
use App\Models\Settings\ProjectAdminUiSetting;
use App\Models\Settings\ProjectAnnouncementSetting;
use App\Models\Settings\ProjectBusinessSetting;
use App\Models\Settings\ProjectFeatureFlags;
use App\Models\Settings\ProjectLineLiffSetting;
use App\Models\Settings\ProjectMemberPointsSetting;
use App\Models\Settings\ProjectMemberRankSetting;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\Settings\ProjectWalletSetting;
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

    public function businessSetting(): HasOne
    {
        return $this->hasOne(ProjectBusinessSetting::class);
    }

    public function lineLiffSetting(): HasOne
    {
        return $this->hasOne(ProjectLineLiffSetting::class);
    }

    public function featureFlags(): HasOne
    {
        return $this->hasOne(ProjectFeatureFlags::class);
    }

    public function adminUiSetting(): HasOne
    {
        return $this->hasOne(ProjectAdminUiSetting::class);
    }

    public function profileSmaregiSetting(): HasOne
    {
        return $this->hasOne(ProjectProfileSmaregiSetting::class);
    }

    public function memberPointsSetting(): HasOne
    {
        return $this->hasOne(ProjectMemberPointsSetting::class);
    }

    public function memberRankSetting(): HasOne
    {
        return $this->hasOne(ProjectMemberRankSetting::class);
    }

    public function walletSetting(): HasOne
    {
        return $this->hasOne(ProjectWalletSetting::class);
    }

    public function announcementSetting(): HasOne
    {
        return $this->hasOne(ProjectAnnouncementSetting::class);
    }

    public function isSmaregiLinked(): bool
    {
        $contractId = app(SmaregiSettings::class)->getValue('smaregi_contract_id', null, $this->id);
        if (is_string($contractId) && trim($contractId) !== '') {
            return true;
        }
        $default = config('smaregi.contract_id');
        $default = is_string($default) ? trim($default) : '';

        return $default !== '';
    }
}
