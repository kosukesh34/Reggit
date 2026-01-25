<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return MemberFactory::new();
    }

    protected $fillable = [
        'user_id',
        'member_number',
        'points',
        'status',
        'birthday',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'points' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function addPoints(int $points, string $description = null, $orderId = null)
    {
        $this->points += $points;
        $this->save();

        PointTransaction::create([
            'member_id' => $this->id,
            'type' => 'earned',
            'points' => $points,
            'description' => $description,
            'order_id' => $orderId,
        ]);

        return $this;
    }

    public function usePoints(int $points, string $description = null, $orderId = null)
    {
        if ($this->points < $points) {
            throw new \Exception('Insufficient points');
        }

        $this->points -= $points;
        $this->save();

        PointTransaction::create([
            'member_id' => $this->id,
            'type' => 'used',
            'points' => -$points,
            'description' => $description,
            'order_id' => $orderId,
        ]);

        return $this;
    }
}


