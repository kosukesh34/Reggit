<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletPassRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'member_id',
        'pass_type_identifier',
        'serial_number',
        'device_library_identifier',
        'push_token',
    ];
}
