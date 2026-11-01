<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    protected $fillable = ['project_id', 'store_key', 'name', 'address', 'phone', 'latitude', 'longitude', 'smaregi_store_id', 'smaregi_terminal_id', 'is_active', 'settings'];

    protected $casts = ['is_active' => 'boolean', 'settings' => 'array', 'latitude' => 'float', 'longitude' => 'float'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_stores')->withPivot('project_id')->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_stores')->withPivot('project_id')->withTimestamps();
    }
}
