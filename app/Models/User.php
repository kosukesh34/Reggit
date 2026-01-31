<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToProject, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'project_id',
        'line_user_id',
        'name',
        'email',
        'phone',
        'profile_image_url',
        'last_login_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }
}


