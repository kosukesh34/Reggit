<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $fillable = [
        'project_id',
        'title',
        'body',
        'published_at',
        'expires_at',
        'is_pinned',
        'delivery_type',
        'scheduled_at',
        'sent_at',
        'target_member_ids',
        'segment_criteria',
        'delivery_coupon_id',
        'delivery_status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function deliveryCoupon()
    {
        return $this->belongsTo(Coupon::class, 'delivery_coupon_id');
    }

    /** @var array<string, string> */
    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_pinned' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'target_member_ids' => 'array',
        'segment_criteria' => 'array',
    ];
}
