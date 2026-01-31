<?php

namespace App\Models;

use Carbon\Carbon;
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
        'rank',
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

    /**
     * トランザクションの合計から現在の保有ポイントを算出する。
     * earned/refunded は加算、used/expired は減算。
     */
    public function getTotalPointsFromTransactions(): int
    {
        return (int) $this->pointTransactions()->sum('points');
    }

    public function addPoints(int $points, string $description = null, $orderId = null, $expiresAt = null)
    {
        $this->points += $points;
        $this->save();

        $expiresAt = $expiresAt ?? Carbon::now()->addYear();

        PointTransaction::create([
            'member_id' => $this->id,
            'type' => 'earned',
            'points' => $points,
            'description' => $description,
            'order_id' => $orderId,
            'expires_at' => $expiresAt,
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


