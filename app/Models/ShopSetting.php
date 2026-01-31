<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    use BelongsToProject, HasFactory;

    private const DEFAULT_RESERVATION_CAPACITY = 20;

    protected $fillable = [
        'project_id',
        'key',
        'value',
        'type',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public static function getValue(string $key, $default = null, ?int $projectId = 1)
    {
        $setting = self::where('project_id', $projectId)->where('key', $key)->first();
        
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

    public static function setValue(string $key, $value, string $type = 'string', ?int $projectId = 1): void
    {
        if ($type === 'json') {
            $value = json_encode($value);
        }

        self::updateOrCreate(
            ['project_id' => $projectId, 'key' => $key],
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

    public static function getClosedDates(): array
    {
        return self::getValue('closed_dates', []);
    }

    public static function getAdvanceBookingDays(): int
    {
        return self::getValue('advance_booking_days', 30);
    }

    public static function getReservationCapacityPerSlot(): int
    {
        return self::getValue('reservation_capacity_per_slot', self::DEFAULT_RESERVATION_CAPACITY);
    }

    public static function getLineThemeColors(): array
    {
        $primary = self::getValue('line_primary_color', '#000000');
        return [
            'primary' => $primary,
            'primary_rgb' => self::hexToRgb($primary),
            'primary_dark' => self::getValue('line_primary_dark', '#333333'),
            'success' => self::getValue('line_success_color', '#000000'),
            'danger' => self::getValue('line_danger_color', '#dc3545'),
        ];
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '0, 0, 0';
        }
        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }

    public static function isDateAvailable(string $date): bool
    {
        $dayOfWeek = (int) date('w', strtotime($date));
        $closedDays = self::getClosedDays();
        
        if (in_array($dayOfWeek, $closedDays, true)) {
            return false;
        }

        $dateStr = date('Y-m-d', strtotime($date));
        $closedDates = self::getClosedDates();
        
        if (in_array($dateStr, $closedDates, true)) {
            return false;
        }

        return true;
    }
}
