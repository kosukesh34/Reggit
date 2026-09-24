<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class ReservationCourse extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'name', 'price', 'description', 'is_active', 'sort_order'];

    protected $casts = ['price' => 'integer', 'is_active' => 'boolean', 'sort_order' => 'integer'];
}
