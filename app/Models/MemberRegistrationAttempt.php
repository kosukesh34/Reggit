<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Internal reservations; callers must explicitly select the project and user. */
class MemberRegistrationAttempt extends Model
{
    protected $fillable = ['project_id', 'user_id', 'member_number', 'contract_id', 'registration_key'];

    protected $hidden = ['registration_key', 'contract_id'];
}
