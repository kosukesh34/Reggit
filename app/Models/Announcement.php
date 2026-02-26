<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = ['project_id', 'title', 'body', 'published_at', 'expires_at', 'is_pinned'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /** @var array<string, string> */
    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];
}
