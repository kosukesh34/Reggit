<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'title', 'body', 'published_at', 'expires_at', 'is_pinned'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }
}
