<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueEntry extends Model
{
    use HasFactory;
    use BelongsToProject;

    protected $fillable = [
        'project_id', 'member_id', 'user_id', 'guest_name', 'guest_phone', 'party_size',
        'status', 'queue_number', 'qr_token', 'called_at', 'entered_at',
    ];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'queue_number' => 'integer',
            'called_at' => 'datetime',
            'entered_at' => 'datetime',
        ];
    }

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
}
