<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueCallLog extends Model
{
    use BelongsToProject;
    use HasFactory;

    protected $fillable = [
        'project_id',
        'queue_entry_id',
        'queue_number',
        'line_user_id',
        'called_at',
        'push_status',
        'error_message',
    ];

    protected $casts = [
        'queue_number' => 'integer',
        'called_at' => 'datetime',
    ];

    public function queueEntry(): BelongsTo
    {
        return $this->belongsTo(QueueEntry::class);
    }
}
