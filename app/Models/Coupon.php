<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use BelongsToProject, HasFactory;

    public const ISSUE_TRIGGER_MANUAL = 'manual';
    public const ISSUE_TRIGGER_FIRST_REGISTRATION = 'first_registration';
    public const ISSUE_TRIGGER_FIRST_ORDER = 'first_order';
    public const ISSUE_TRIGGER_STAMP_COMPLETED = 'stamp_completed';
    public const ISSUE_TRIGGER_BIRTHDAY = 'birthday';

    public const DESIGN_CLASSIC = 'classic';
    public const DESIGN_MODERN = 'modern';
    public const DESIGN_MINIMAL = 'minimal';
    public const DESIGN_GRADIENT = 'gradient';
    public const DESIGN_CARD = 'card';
    public const DESIGN_BARCODE = 'barcode';
    public const DESIGN_QR = 'qr';
    public const DESIGN_STRIPE = 'stripe';
    public const DESIGN_TICKET = 'ticket';
    public const DESIGN_VINTAGE = 'vintage';
    public const DESIGN_NEON = 'neon';

    /** @return array<string, string> */
    public static function getIssueTriggerOptions(): array
    {
        return [
            self::ISSUE_TRIGGER_MANUAL => '手動（管理画面で会員に付与）',
            self::ISSUE_TRIGGER_FIRST_REGISTRATION => '入会時（会員登録直後に自動発行）',
            self::ISSUE_TRIGGER_FIRST_ORDER => '初回注文時（初めての注文完了時に自動発行）',
            self::ISSUE_TRIGGER_STAMP_COMPLETED => 'スタンプカード達成時',
            self::ISSUE_TRIGGER_BIRTHDAY => '誕生月（誕生月に1回のみ自動発行）',
        ];
    }

    /** @return array<string, string> */
    public static function getDesignTemplateOptions(): array
    {
        return [
            self::DESIGN_CLASSIC => 'クラシック',
            self::DESIGN_MODERN => 'モダン',
            self::DESIGN_MINIMAL => 'ミニマル',
            self::DESIGN_GRADIENT => 'グラデーション',
            self::DESIGN_CARD => 'カード風',
            self::DESIGN_BARCODE => 'バーコード風',
            self::DESIGN_QR => 'QRコード風',
            self::DESIGN_STRIPE => 'ストライプ',
            self::DESIGN_TICKET => 'チケット風',
            self::DESIGN_VINTAGE => 'ヴィンテージ',
            self::DESIGN_NEON => 'ネオン',
        ];
    }

    /** @return array<string, string> 配色プリセット（管理画面用） */
    public static function getThemeColorPresets(): array
    {
        return [
            '' => '指定なし（デフォルト）',
            '#059669' => '緑',
            '#0d9488' => 'ティール',
            '#0284c7' => '青',
            '#7c3aed' => '紫',
            '#dc2626' => '赤',
            '#ea580c' => 'オレンジ',
            '#ca8a04' => 'ゴールド',
            '#0891b2' => 'シアン',
        ];
    }

    protected $fillable = [
        'project_id', 'code', 'name', 'image_url', 'design_template', 'theme_color', 'type', 'value', 'min_order_amount',
        'usage_limit', 'used_count', 'start_at', 'end_at', 'is_active', 'issue_trigger', 'stamp_card_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function memberCoupons(): HasMany
    {
        return $this->hasMany(MemberCoupon::class);
    }
}
