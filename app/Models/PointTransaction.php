<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id',
        'member_id',
        'type',
        'points',
        'description',
        'order_id',
        'smaregi_transaction_head_id',
        'expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
