<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpArticle extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'summary',
        'category',
        'body',
        'sort_order',
        'is_published',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];
}
