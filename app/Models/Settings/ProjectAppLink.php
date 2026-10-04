<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class ProjectAppLink extends Model
{
    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = ['project_id', 'download_url'];
}
