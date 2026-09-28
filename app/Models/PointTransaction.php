<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id', 'store_id', 'origin',
        'member_id',
        'type',
        'points',
        'description',
        'order_id',
        'expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
