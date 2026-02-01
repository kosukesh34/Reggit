<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StampCard extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'name', 'required_stamps', 'reward_description', 'is_active'];

    protected function casts(): array
    {
        return [
            'required_stamps' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function memberStamps(): HasMany
    {
        return $this->hasMany(MemberStamp::class);
    }
}
