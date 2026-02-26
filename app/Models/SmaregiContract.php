<?php

namespace App\Models;

use App\Enums\SmaregiContractAction;
use App\Enums\SmaregiContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmaregiContract extends Model
{
    protected $fillable = [
        'contract_id',
        'project_id',
        'client_id',
        'plan',
        'options',
        'last_action',
        'status',
        'last_notified_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'plan' => 'array',
        'options' => 'array',
        'last_notified_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isActive(): bool
    {
        return $this->status === SmaregiContractStatus::ACTIVE;
    }

    public static function deriveStatusFromAction(string $action): string
    {
        return SmaregiContractAction::isInactiveAction($action)
            ? SmaregiContractStatus::INACTIVE
            : SmaregiContractStatus::ACTIVE;
    }
}
