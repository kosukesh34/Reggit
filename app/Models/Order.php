<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'order_number',
        'order_type',
        'total_amount',
        'status',
        'payment_method',
        'notes',
        'points_used',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'points_used' => 'integer',
            'points_earned' => 'integer',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if ($order->order_number === null || $order->order_number === '') {
                $order->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
        });
    }
}


