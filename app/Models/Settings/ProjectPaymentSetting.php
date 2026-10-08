<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

/** Immutable versions: existing orders retain their original account and credentials. */
class ProjectPaymentSetting extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['secret_key', 'webhook_secret'];

    protected $casts = [
        'secret_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'methods' => 'array',
    ];

    public static function current(int $projectId): ?self
    {
        return static::where('project_id', $projectId)->latest('id')->first();
    }
}
