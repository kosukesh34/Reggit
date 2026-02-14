<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
