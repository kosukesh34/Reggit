<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletPass extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'user_id',
        'member_id',
        'pass_type_identifier',
        'serial_number',
        'authentication_token',
        'last_updated_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'last_updated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(WalletPassRegistration::class);
    }
}
