<?php

namespace App\Models;

use App\Enums\QueueStatus;
use App\Models\Concerns\BelongsToProject;
use App\Support\DailySequenceNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueEntry extends Model
{
    use BelongsToProject;
    use HasFactory;

    protected $fillable = [
        'project_id', 'member_id', 'user_id', 'guest_name', 'guest_phone', 'party_size',
        'status', 'queue_number', 'qr_token', 'called_at', 'entered_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'party_size' => 'integer',
        'queue_number' => 'integer',
        'called_at' => 'datetime',
        'entered_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedQueueNumber(): string
    {
        if ($this->queue_number === null) {
            return (string) $this->id;
        }

        return DailySequenceNumber::formatStoredForDisplay((string) $this->queue_number);
    }

    /**
     * 管理画面・受付一覧向け: 直近1日以内、かつ入店済みは入店から30分以内のみ。
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeVisibleOnQueueBoard($query)
    {
        return $query
            ->where('created_at', '>=', now()->subDay())
            ->where(function ($q) {
                $q->where('status', '!=', QueueStatus::ENTERED)
                    ->orWhere('entered_at', '>=', now()->subMinutes(30));
            });
    }
}
