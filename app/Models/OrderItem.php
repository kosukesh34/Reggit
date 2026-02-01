<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id',
        'order_id',
        'product_id',
        'quantity',
        'price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::creating(function (OrderItem $item) {
            if ($item->project_id === null && $item->order_id !== null) {
                $order = Order::find($item->order_id);
                if ($order) {
                    $item->project_id = $order->project_id;
                }
            }
        });
    }
}


