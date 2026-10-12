<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletPassUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'member_id',
        'pass_type_identifier',
        'serial_number',
        'updated_at',
        'revision',
    ];

    public $timestamps = false;
}
