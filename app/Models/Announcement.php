<?php

namespace App\Models;

use App\Enums\AnnouncementAudienceResolveMode;
use App\Models\Concerns\BelongsToProject;
use App\Util\StoreContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class Announcement extends Model
{
    use BelongsToProject, HasFactory;

    public const DELIVERY_TYPE_IMMEDIATE_BULK = 'immediate_bulk';

    public const DELIVERY_TYPE_PERSONAL = 'personal';

    public const DELIVERY_TYPE_SCHEDULED = 'scheduled';

    public const DELIVERY_TYPE_SEGMENT = 'segment';

    public const DELIVERY_TYPE_AFTER_REGISTRATION = 'after_registration';

    public const DELIVERY_STATUS_DRAFT = 'draft';

    public const DELIVERY_STATUS_SENT = 'sent';

    public const DELIVERY_STATUS_SCHEDULED = 'scheduled';

    public const DELIVERY_TIMING_IMMEDIATE = 'immediate';

    public const DELIVERY_TIMING_SCHEDULED = 'scheduled';

    protected static function booted(): void
    {
        static::creating(function (self $announcement) {
            if (is_array($announcement->target_member_ids) || in_array($announcement->delivery_type, [self::DELIVERY_TYPE_PERSONAL, self::DELIVERY_TYPE_SEGMENT, self::DELIVERY_TYPE_AFTER_REGISTRATION], true)) {
                $announcement->visibility = 'recipients';
            }
            $context = app(StoreContext::class);
            if ($context->bound && $context->admin !== null) {
                if (! $context->admin->isSystemAdmin() && ! $context->admin->isProjectAdmin()) {
                    $context->resolveForWrite((int) $announcement->project_id);
                }
                $announcement->store_ids = $context->store !== null ? [(int) $context->store->id] : null;
                $announcement->created_by_admin_id = $context->admin->id;
                if ($announcement->delivery_type !== null) {
                    $announcement->visibility = 'recipients';
                }
            }
        });
        static::saving(function (self $announcement) {
            $context = app(StoreContext::class);
            if (! $announcement->exists || ! $context->bound || $context->admin === null || $context->admin->isSystemAdmin() || $context->admin->isProjectAdmin()) {
                return;
            }
            $ids = $announcement->exists ? $announcement->getOriginal('store_ids') : $announcement->store_ids;
            if ($ids === null && $context->projectStoreCount === 1) {
                $announcement->store_ids = $context->visibleStoreIds;

                return;
            }
            abort_unless(is_array($ids) && $ids !== [] && count(array_diff($ids, $context->visibleStoreIds ?? [])) === 0, 403, '担当店舗の配信だけを変更できます。');
        });
        static::deleting(function (self $announcement) {
            $context = app(StoreContext::class);
            if ($context->bound && $context->admin !== null && ! $context->admin->isSystemAdmin() && ! $context->admin->isProjectAdmin()) {
                if ($announcement->store_ids === null && $context->projectStoreCount === 1) {
                    return;
                }
                abort_unless(is_array($announcement->store_ids) && count(array_diff($announcement->store_ids, $context->visibleStoreIds ?? [])) === 0, 403);
            }
        });
    }

    public function getImageUrlAttribute($value)
    {
        if ($this->image_paths && $this->exists) {
            return $this->imageUrls()[0] ?? null;
        }
        if ($this->private_image_path !== null && $this->exists) {
            $projectKey = Project::whereKey($this->project_id)->value('project_key');

            return url('/api/v1/announcements/'.$this->id.'/image').'?project_key='.urlencode((string) $projectKey);
        }

        return $value;
    }

    /** @return list<string> */
    public function imageUrls(): array
    {
        if ($this->image_paths && $this->exists) {
            return array_map(fn ($index) => $this->signedImageUrl($index, $this->image_paths[$index]), array_keys($this->image_paths));
        }

        return $this->image_url ? [$this->image_url] : [];
    }

    public function signedImageUrl(int $index, string $path): string
    {
        return URL::signedRoute('announcements.media', [
            'id' => $this->id, 'index' => $index, 'version' => hash('sha256', $path),
        ]);
    }

    /** @return list<string> */
    public function lineImageUrls(): array
    {
        if ($this->image_paths) {
            return array_map(fn ($index) => $this->signedImageUrl($index, $this->image_paths[$index]), array_keys($this->image_paths));
        }
        if ($this->private_image_path && $this->exists) {
            return [$this->signedImageUrl(0, $this->private_image_path)];
        }

        return $this->imageUrls();
    }

    protected $hidden = ['private_image_path', 'image_paths', 'notification_pending', 'notification_retry_at'];

    protected $fillable = [
        'project_id',
        'store_ids', 'visibility', 'created_by_admin_id', 'private_image_path',
        'image_paths',
        'title',
        'body',
        'action_path',
        'action_label',
        'image_url',
        'published_at',
        'expires_at',
        'is_pinned',
        'send_notification',
        'delivery_type',
        'delivery_timing',
        'scheduled_at',
        'sent_at',
        'target_member_ids',
        'audience_resolve_mode',
        'segment_criteria',
        'delivery_coupon_id',
        'delivery_coupon_ids',
        'delivery_status',
        'push_notification_ids',
        'notification_pending',
        'notification_retry_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function deliveryCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'delivery_coupon_id');
    }

    /** @var array<string, string> */
    protected $casts = [
        'image_paths' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_pinned' => 'boolean',
        'send_notification' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'notification_pending' => 'boolean',
        'notification_retry_at' => 'datetime',
        'target_member_ids' => 'array',
        'store_ids' => 'array',
        'segment_criteria' => 'array',
        'delivery_coupon_ids' => 'array',
        'push_notification_ids' => 'array',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'audience_resolve_mode' => AnnouncementAudienceResolveMode::SNAPSHOT,
        'send_notification' => true,
    ];

    public function shouldResolveAudienceAtSend(): bool
    {
        return $this->audience_resolve_mode === AnnouncementAudienceResolveMode::AT_SEND
            && $this->delivery_type !== self::DELIVERY_TYPE_PERSONAL;
    }

    /** @return list<int> */
    public function deliveryCouponIds(): array
    {
        $ids = is_array($this->delivery_coupon_ids) ? $this->delivery_coupon_ids : [];
        if ($this->delivery_coupon_id !== null) {
            array_unshift($ids, (int) $this->delivery_coupon_id);
        }

        return collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all();
    }

    public function hasDeliveryCoupons(): bool
    {
        return $this->deliveryCouponIds() !== [];
    }
}
