<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Carbon\Carbon;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use BelongsToProject, HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return MemberFactory::new();
    }

    protected $fillable = [
        'project_id',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function memberCoupons()
    {
        return $this->hasMany(MemberCoupon::class);
    }

    public function getTotalPointsFromTransactions(): int
    {
        return (int) $this->pointTransactions()->sum('points');
    }

    public function recalcPointsFromTransactions(): void
    {
        $this->update(['points' => $this->getTotalPointsFromTransactions()]);
    }

    public function addPoints(int $points, string $description = null, $orderId = null, $expiresAt = null)
    {
        $this->points += $points;
        $this->save();

        $expiresAt = $expiresAt ?? Carbon::now()->addYear();

        PointTransaction::create([
            'project_id' => $this->project_id,
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
            'project_id' => $this->project_id,
            'member_id' => $this->id,
            'type' => 'used',
            'points' => -$points,
            'description' => $description,
            'order_id' => $orderId,
        ]);

        return $this;
    }
}


