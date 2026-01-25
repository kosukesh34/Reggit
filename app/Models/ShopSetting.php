<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if ($setting === null) {
            return $default;
        }

        if ($setting->type === 'json') {
            return json_decode($setting->value, true);
        }

        if ($setting->type === 'integer') {
            return (int) $setting->value;
        }

        if ($setting->type === 'boolean') {
            return (bool) $setting->value;
        }

        return $setting->value;
    }

    public static function setValue(string $key, $value, string $type = 'string'): void
    {
        if ($type === 'json') {
            $value = json_encode($value);
        }

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    public static function getBusinessHours(): array
    {
        return [
            'start' => self::getValue('business_hours_start', '10:00'),
            'end' => self::getValue('business_hours_end', '22:00'),
        ];
    }

    public static function getReservationTimeSlots(): array
    {
        return self::getValue('reservation_time_slots', ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00']);
    }

    public static function getClosedDays(): array
    {
        return self::getValue('closed_days', []);
    }

    public static function getAdvanceBookingDays(): int
    {
        return self::getValue('advance_booking_days', 30);
    }

    public static function isDateAvailable(string $date): bool
    {
        $dayOfWeek = (int) date('w', strtotime($date));
        $closedDays = self::getClosedDays();
        
        if (in_array($dayOfWeek, $closedDays, true)) {
            return false;
        }

        $dateStr = date('Y-m-d', strtotime($date));
        $closedDates = self::getValue('closed_dates', []);
        
        if (in_array($dateStr, $closedDates, true)) {
            return false;
        }

        return true;
    }
}
