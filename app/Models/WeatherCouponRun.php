<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherCouponRun extends Model
{
    use BelongsToProject;

    public const STATUS_PENDING = 'pending';

    public const STATUS_NO_RAIN = 'no_rain';

    /** 気温の配信で、最高気温・最低気温が条件に届かなかった */
    public const STATUS_NOT_MET = 'not_met';

    public const STATUS_SENT = 'sent';

    public const STATUS_LIMIT_REACHED = 'limit_reached';

    public const STATUS_FAILED = 'failed';

    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    /** 承認されないまま期限が切れた。次の判定で雨ならもう一度承認を求める */
    public const STATUS_EXPIRED = 'expired';

    /** 管理者が「今日は配らない」を選んだ */
    public const STATUS_DECLINED = 'declined';

    /** この状態になった営業日は、もう判定しない */
    public const FINAL_STATUSES = [self::STATUS_SENT, self::STATUS_LIMIT_REACHED, self::STATUS_DECLINED];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => '判定中',
        self::STATUS_NO_RAIN => '雨の予報なし',
        self::STATUS_NOT_MET => '気温の条件に合わず',
        self::STATUS_SENT => '配布済み',
        self::STATUS_LIMIT_REACHED => '週の上限',
        self::STATUS_FAILED => '失敗',
        self::STATUS_AWAITING_APPROVAL => '承認待ち',
        self::STATUS_EXPIRED => '承認期限切れ',
        self::STATUS_DECLINED => '今日は配らない',
    ];

    protected $fillable = [
        'project_id',
        'after_registration_delivery_id',
        'business_date',
        'status',
        'max_precipitation_probability',
        'forecast_temperature',
        'checked_at',
        'approval_expires_at',
        'coupon_delivery_id',
        'issued_count',
        'error_message',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'business_date' => 'date',
        'checked_at' => 'datetime',
        'approval_expires_at' => 'datetime',
        'max_precipitation_probability' => 'integer',
        'forecast_temperature' => 'float',
        'issued_count' => 'integer',
    ];

    /** 判定に使った予報（「最大降水確率 80%」「最高気温 31.5℃」） */
    public function forecastLabel(): string
    {
        if ($this->forecast_temperature !== null) {
            $kind = $this->delivery?->param('temperature_condition') === AfterRegistrationDelivery::TEMPERATURE_BELOW ? '最低気温' : '最高気温';

            return $kind.' '.$this->forecast_temperature.'℃';
        }

        return $this->max_precipitation_probability !== null ? '最大降水確率 '.$this->max_precipitation_probability.'%' : '—';
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(AfterRegistrationDelivery::class, 'after_registration_delivery_id');
    }
}
