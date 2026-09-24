<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class ExistingMemberLinkSetting extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'is_enabled',
        'description',
        'identifier_fields',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_enabled' => 'boolean',
        'identifier_fields' => 'array',
    ];
}
