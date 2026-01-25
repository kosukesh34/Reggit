<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ReservationFactory::new();
    }

    protected $fillable = [
        'user_id',
        'reservation_number',
        'reserved_at',
        'number_of_people',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'number_of_people' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if ($reservation->reservation_number === null || $reservation->reservation_number === '') {
                $reservation->reservation_number = 'RES-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
            if ($reservation->status === null || $reservation->status === '') {
                $reservation->status = ReservationStatus::PENDING;
            }
        });
    }

    public function isPending(): bool
    {
        return $this->status === ReservationStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === ReservationStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::CANCELLED;
    }

    public function canCancel(): bool
    {
        return $this->status !== ReservationStatus::CANCELLED 
            && $this->status !== ReservationStatus::COMPLETED;
    }
}
