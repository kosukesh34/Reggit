<?php

namespace App\Models;

use App\Enums\ScheduledDeliveryCategory;
use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AfterRegistrationDelivery extends Model
{
    use BelongsToProject;

    /** @var array<int, string> */
    public const NEW_USER_MINUTE_OPTIONS = [
        0, 3, 5, 10, 15, 20, 30, 45, 60, 90, 120, 180, 240, 300, 360, 480, 600, 720,
    ];

    protected $fillable = [
        'project_id',
        'category',
        'schedule_type',
        'content_type',
        'delay_minutes',
        'title',
        'body',
        'coupon_id',
        'send_notification',
        'push_hour',
        'push_minute',
        'params',
        'is_active',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'category' => ScheduledDeliveryCategory::CONDITION,
        'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
        'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT,
        'delay_minutes' => 0,
        'title' => '',
        'send_notification' => true,
        'push_hour' => 10,
        'push_minute' => 0,
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'send_notification' => 'boolean',
        'params' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(ScheduledDeliverySend::class, 'after_registration_delivery_id');
    }

    public function isCouponOnly(): bool
    {
        return $this->content_type === ScheduledDeliveryContentType::COUPON_ONLY;
    }

    public function shouldSendNotification(): bool
    {
        if ($this->isCouponOnly()) {
            return $this->send_notification === true;
        }

        return true;
    }

    public function param(string $key, int|string|null $default = null): int|string|null
    {
        $params = is_array($this->params) ? $this->params : [];
        if (! array_key_exists($key, $params) || $params[$key] === null || $params[$key] === '') {
            return $default;
        }

        return $params[$key];
    }

    public function intParam(string $key, int $default): int
    {
        return (int) $this->param($key, $default);
    }

    public function summaryLabel(): string
    {
        $type = (string) $this->schedule_type;
        $name = ScheduledDeliveryType::getLabel($type);
        $category = ScheduledDeliveryCategory::getLabel((string) $this->category);

        return $category.' / '.$name.' / '.$this->detailLabel();
    }

    public function detailLabel(): string
    {
        return match ((string) $this->schedule_type) {
            ScheduledDeliveryType::NEW_USER_MINUTES => '入会から'.$this->resolvedDelayMinutes().'分後',
            ScheduledDeliveryType::NEW_USER_DAYS => '入会から'.$this->intParam('delay_days', 1).'日後',
            ScheduledDeliveryType::LAST_VISIT => '最終来店から'.$this->intParam('delay_days', 7).'日後',
            ScheduledDeliveryType::VISIT_MILESTONE => $this->intParam('visit_times', 5).'回目の来店から'.$this->intParam('visit_after_days', 0).'日後',
            ScheduledDeliveryType::RANK_UP_ARRIVAL => ($this->param('reach_rank', '') !== '' && $this->param('reach_rank', '') !== null)
                ? (string) $this->param('reach_rank', '').'に昇格時'
                : '指定ランクに昇格時',
            ScheduledDeliveryType::TOTAL_POINTS => $this->intParam('total_points', 0).'ポイント到達',
            ScheduledDeliveryType::TOTAL_STAMPS => $this->intParam('total_stamps', 0).'個到達',
            ScheduledDeliveryType::BIRTHDAY_DAYS_BEFORE => '誕生日の'.$this->intParam('before_days', 7).'日前',
            ScheduledDeliveryType::BIRTHDAY_MONTH_FIRST => '誕生月の1日',
            ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH => '毎月'.$this->dayOfMonthLabel($this->param('day_of_month', 1)).'に来月誕生日の人',
            ScheduledDeliveryType::WEEKLY => '毎週'.$this->weekdayLabel($this->intParam('day_of_week', 1)),
            ScheduledDeliveryType::MONTHLY => '毎月'.$this->dayOfMonthLabel($this->param('day_of_month', 1)),
            ScheduledDeliveryType::LAST_MONTH_VISITS => '前月'.$this->intParam('last_month_visit_min', 1).'回来店以上の人に毎月'.$this->dayOfMonthLabel($this->param('day_of_month', 1)),
            default => ScheduledDeliveryType::getLabel((string) $this->schedule_type),
        };
    }

    public function resolvedDelayMinutes(): int
    {
        $fromParams = $this->intParam('delay_minutes', 0);
        if ($fromParams > 0) {
            return $fromParams;
        }

        return max(0, (int) $this->delay_minutes);
    }

    public function sendKey(\DateTimeInterface $now): string
    {
        $type = (string) $this->schedule_type;
        if (ScheduledDeliveryType::isOneShot($type)) {
            return 'once';
        }
        if ($type === ScheduledDeliveryType::BIRTHDAY_DAYS_BEFORE || $type === ScheduledDeliveryType::BIRTHDAY_MONTH_FIRST) {
            return $now->format('Y');
        }

        return $now->format('Y-m-d');
    }

    private function weekdayLabel(int $day): string
    {
        return ['日', '月', '火', '水', '木', '金', '土'][$day] ?? (string) $day;
    }

    private function dayOfMonthLabel(int|string|null $day): string
    {
        if ($day === 'last_day') {
            return '月末';
        }

        return ((int) $day).'日';
    }
}
