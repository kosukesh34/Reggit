<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'type',
        'points',
        'description',
        'order_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'expires_at' => 'date',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}


