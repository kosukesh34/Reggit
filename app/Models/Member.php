<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use App\Util\StoreContext;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    use BelongsToProject, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (self $member) {
            if ($member->customer_store_id !== null && ($member->wasRecentlyCreated || $member->wasChanged('customer_store_id'))) {
                $member->stores()->syncWithoutDetaching([(int) $member->customer_store_id => ['project_id' => $member->project_id]]);
            }
        });
        static::deleting(function (self $member) {
            $admin = app(StoreContext::class)->admin;
            abort_unless(app(StoreContext::class)->projectStoreCount <= 1 || $admin === null || $admin->isSystemAdmin() || $admin->isProjectAdmin(), 403, '共通会員の退会は本部管理者が行ってください。');
        });
    }

    public function getMemoAttribute($value)
    {
        $context = app(StoreContext::class);
        if ($context->projectStoreCount > 1 && $context->admin !== null && ! $context->admin->isSystemAdmin() && ! $context->admin->isProjectAdmin()) {
            if ($context->store === null) {
                return null;
            }

            return DB::table('member_stores')->where('member_id', $this->id)->where('project_id', $this->project_id)->where('store_id', $context->store->id)->value('memo');
        }
        if ($context->projectStoreCount > 1 && $context->store !== null && $context->admin !== null) {
            return DB::table('member_stores')->where('member_id', $this->id)->where('project_id', $this->project_id)->where('store_id', $context->store->id)->value('memo');
        }

        return $value;
    }

    public function getLastVisitedAtAttribute($value)
    {
        $context = app(StoreContext::class);
        if ($context->projectStoreCount > 1 && $context->admin !== null && $context->visibleStoreIds !== null) {
            $value = MemberVisit::where('project_id', $this->project_id)->where('member_id', $this->id)->max('visited_at');
        }

        return $value === null ? null : $this->asDateTime($value);
    }

    protected static function newFactory()
    {
        return MemberFactory::new();
    }

    protected $fillable = [
        'project_id',
        'user_id',
        'member_number',
        'smaregi_customer_id',
        'points',
        'lifetime_granted_points',
        'status',
        'rank',
        'last_visited_at',
        'birthday',
        'address',
        'postal_code',
        'prefecture',
        'city',
        'street',
        'sex',
        'mail_receive_flag',
        'customer_store_id',
        'acquisition_source_id',
        'notification_preference',
        'profile_extra',
        'memo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'points' => 'integer',
        'lifetime_granted_points' => 'integer',
        'last_visited_at' => 'datetime',
        'birthday' => 'date',
        'profile_extra' => 'array',
    ];

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'member_stores')->withPivot('project_id')->withTimestamps();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function memberStamps(): HasMany
    {
        return $this->hasMany(MemberStamp::class);
    }

    public function stampTransactions(): HasMany
    {
        return $this->hasMany(MemberStampTransaction::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(MemberVisit::class);
    }

    public function memberCoupons(): HasMany
    {
        return $this->hasMany(MemberCoupon::class);
    }

    /** @return BelongsToMany<MemberTag> */
    public function tags()
    {
        return $this->belongsToMany(MemberTag::class, 'member_tag_members');
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function rankHistories(): HasMany
    {
        return $this->hasMany(MemberRankHistory::class);
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function getTotalStamps(): int
    {
        return (int) $this->memberStamps()
            ->join('stamp_cards', 'stamp_cards.id', '=', 'member_stamps.stamp_card_id')
            ->sum(DB::raw(
                'member_stamps.current_stamps + COALESCE(member_stamps.cycle_count, 0) * GREATEST(COALESCE(stamp_cards.required_stamps, 1), 1)'
            ));
    }

    public function displayAddress(): string
    {
        $address = trim((string) ($this->address ?? ''));
        if ($address !== '') {
            return $address;
        }

        return trim(implode('', array_filter([
            trim((string) ($this->prefecture ?? '')),
            trim((string) ($this->city ?? '')),
            trim((string) ($this->street ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }
}
