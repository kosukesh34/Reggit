<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Models\Concerns\BelongsToProject;
use App\Services\ReservationNumberAssigner;
use App\Support\DailySequenceNumber;
use App\Util\AdminContext;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use BelongsToProject, HasFactory;

    protected static function newFactory()
    {
        return ReservationFactory::new();
    }

    protected $fillable = [
        'project_id',
        'user_id',
        'reservation_number',
        'reserved_at',
        'number_of_people',
        'status',
        'notes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'reserved_at' => 'datetime',
        'number_of_people' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
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
                $projectId = (int) (AdminContext::resolveProjectId($reservation->project_id !== null ? (int) $reservation->project_id : null));
                $reservation->reservation_number = app(ReservationNumberAssigner::class)
                    ->buildNext($projectId);
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

    public function canEdit(): bool
    {
        return $this->status !== ReservationStatus::CANCELLED
            && $this->status !== ReservationStatus::COMPLETED;
    }

    public function formattedReservationNumber(): string
    {
        return DailySequenceNumber::formatStoredForDisplay($this->reservation_number);
    }
}
