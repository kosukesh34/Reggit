<?php

namespace App\Util;

use App\Enums\OrderStatus;
use App\Enums\QueueStatus;
use App\Enums\ReservationStatus;

final class AdminStatusLabels
{
    public static function orderStatusLabel(string $status): string
    {
        return OrderStatus::getLabel($status);
    }

    public static function queueStatusLabel(string $status): string
    {
        return QueueStatus::getLabel($status);
    }

    public static function reservationStatusLabel(string $status): string
    {
        return ReservationStatus::getLabel($status);
    }
}
