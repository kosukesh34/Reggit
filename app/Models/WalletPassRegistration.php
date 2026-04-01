<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPassRegistration extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'wallet_pass_id',
        'device_library_identifier',
        'push_token',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function walletPass(): BelongsTo
    {
        return $this->belongsTo(WalletPass::class);
    }
}
