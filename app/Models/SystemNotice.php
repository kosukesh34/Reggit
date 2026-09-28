<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemNotice extends Model
{
    protected $fillable = ['title', 'body', 'is_published', 'starts_at', 'ends_at'];

    protected $casts = ['is_published' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest();
    }
}
