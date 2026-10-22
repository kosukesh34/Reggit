<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTicketUse extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'member_ticket_id', 'store_id', 'used_at', 'cancelled_at'];

    /** @var array<string, string> */
    protected $casts = [
        'used_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MemberTicket::class, 'member_ticket_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
