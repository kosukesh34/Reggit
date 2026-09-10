<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingRegistration extends Model
{
    public const VERIFY_EXPIRES_HOURS = 24;

    public const FORM_EXPIRES_DAYS = 7;

    protected $fillable = [
        'email',
        'project_id',
        'admin_login_id',
        'email_verified_at',
        'form_url_sent_at',
        'store_name',
        'contact_name',
        'phone',
        'plan',
        'message',
        'submitted_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'form_url_sent_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
