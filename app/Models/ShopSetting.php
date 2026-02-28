<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use App\Util\AdminContext;
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

    public static function getValue(string $key, $default = null, ?int $projectId = null)
    {
        $projectId = $projectId ?? AdminContext::getCurrentProjectId() ?? 1;
        $query = self::query();
        if (func_num_args() >= 3 && $projectId !== null) {
            $query->withoutGlobalScopes();
        }
        $setting = $query->where('project_id', $projectId)->where('key', $key)->first();
        
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
        $projectId = $projectId ?? AdminContext::getCurrentProjectId() ?? 1;
        if ($type === 'json') {
            $value = json_encode($value);
        }

        self::updateOrCreate(
            ['project_id' => $projectId, 'key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    public static function getBusinessHours(?int $projectId = 1): array
    {
        return [
            'start' => self::getValue('business_hours_start', '10:00', $projectId),
            'end' => self::getValue('business_hours_end', '22:00', $projectId),
        ];
    }

    public static function getReservationTimeSlots(?int $projectId = 1): array
    {
        return self::getValue('reservation_time_slots', ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'], $projectId);
    }

    public static function getClosedDays(?int $projectId = 1): array
    {
        return self::getValue('closed_days', [], $projectId);
    }

    public static function getClosedDates(?int $projectId = 1): array
    {
        return self::getValue('closed_dates', [], $projectId);
    }

    public static function getAdvanceBookingDays(?int $projectId = 1): int
    {
        return self::getValue('advance_booking_days', 30, $projectId);
    }

    public static function getReservationCapacityPerSlot(?int $projectId = 1): int
    {
        return self::getValue('reservation_capacity_per_slot', self::DEFAULT_RESERVATION_CAPACITY, $projectId);
    }

    /** 順番待ち 1組あたりの平均対応時間（分）。予測待ち時間の計算に使用。 */
    public static function getQueueAvgServeMinutes(?int $projectId = 1): int
    {
        return self::getValue('queue_avg_serve_minutes', 10, $projectId);
    }

    public static function getLineThemeColors(?int $projectId = 1): array
    {
        $primary = self::getValue('line_primary_color', '#000000', $projectId);
        return [
            'primary' => $primary,
            'primary_rgb' => self::hexToRgb($primary),
            'primary_dark' => self::getValue('line_primary_dark', '#333333', $projectId),
            'success' => self::getValue('line_success_color', '#000000', $projectId),
            'danger' => self::getValue('line_danger_color', '#dc3545', $projectId),
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

    /** フロント（ユーザー向け）のヘッダー・フッターに表示するメニュー項目のデフォルト */
    public static function defaultFrontendMenuItems(): array
    {
        return [
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'order' => 0],
            ['tab' => 'products', 'label' => '商品', 'icon' => 'fa-utensils', 'order' => 1],
            ['tab' => 'orders', 'label' => '注文', 'icon' => 'fa-receipt', 'order' => 2],
            ['tab' => 'stamps', 'label' => 'スタンプ', 'icon' => 'fa-stamp', 'order' => 3],
            ['tab' => 'coupons', 'label' => 'クーポン', 'icon' => 'fa-ticket-alt', 'order' => 4],
            ['tab' => 'queue', 'label' => '順番待ち', 'icon' => 'fa-list-ol', 'order' => 5],
            ['tab' => 'reservations', 'label' => '予約', 'icon' => 'fa-calendar-check', 'order' => 6],
            ['tab' => 'member', 'label' => '会員証', 'icon' => 'fa-id-card', 'order' => 7],
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'icon' => 'fa-images', 'order' => 8],
        ];
    }

    /** フロントメニュー項目を取得（表示するもののみ・order でソート） */
    public static function getFrontendMenuItems(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;
        $items = self::getValue('frontend_menu_items', null, $projectId);
        if ($items === null || ! is_array($items)) {
            return self::defaultFrontendMenuItems();
        }
        $defaults = collect(self::defaultFrontendMenuItems())->keyBy('tab');
        $merged = [];
        foreach ($items as $item) {
            $tab = $item['tab'] ?? null;
            if (\App\Util\StrHelper::isEmptyStringOrNull($tab) || $defaults->has($tab) === false) {
                continue;
            }
            $merged[] = array_merge($defaults->get($tab), $item);
        }
        if ($merged === []) {
            return self::defaultFrontendMenuItems();
        }
        usort($merged, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        return $merged;
    }

    /** フロントメニュー項目を保存 */
    public static function setFrontendMenuItems(array $items, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        self::setValue('frontend_menu_items', $items, 'json', $projectId);
    }

    // ── 機能フラグ ──

    /** 機能フラグのデフォルト値 */
    public const FEATURE_FLAGS = [
        'feature_home_enabled'          => true,
        'feature_orders_enabled'        => true,
        'feature_reservations_enabled'  => true,
        'feature_queue_enabled'         => true,
        'feature_stamps_enabled'        => true,
        'feature_coupons_enabled'       => true,
        'feature_members_enabled'       => true,
        'feature_announcements_enabled' => true,
        'feature_payments_enabled'      => true,
        'feature_gallery_enabled'       => true,
    ];

    /** 機能フラグの表示名 */
    public const FEATURE_FLAG_LABELS = [
        'feature_home_enabled'          => 'ホーム',
        'feature_orders_enabled'        => 'モバイルオーダー',
        'feature_reservations_enabled'  => '予約',
        'feature_queue_enabled'         => '順番待ち',
        'feature_stamps_enabled'        => 'スタンプカード',
        'feature_coupons_enabled'       => 'クーポン',
        'feature_members_enabled'       => '会員管理',
        'feature_announcements_enabled' => 'お知らせ',
        'feature_payments_enabled'      => '決済（Stripe等）',
        'feature_gallery_enabled'       => 'ギャラリー',
    ];

    // ── LIFF ビルダー ──

    public static function getLiffBuilderBlocks(?int $projectId = null): array
    {
        $blocks = self::getValue('liff_builder_blocks', null, $projectId);
        if (!is_array($blocks) || $blocks === []) {
            return self::defaultLiffBuilderTemplates()[0]['blocks'];
        }
        return $blocks;
    }

    public static function setLiffBuilderBlocks(array $blocks, ?int $projectId = null): void
    {
        self::setValue('liff_builder_blocks', $blocks, 'json', $projectId);
    }

    public static function defaultLiffBuilderTemplates(): array
    {
        return [
            [
                'id' => 'welcome',
                'name' => 'ウェルカム',
                'blocks' => [
                    ['type' => 'hero', 'title' => 'ようこそ', 'body' => 'スマホで快適に注文・予約ができます。', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200', 'button_label' => 'メニューを見る', 'button_url' => '#products'],
                    ['type' => 'text', 'title' => 'お知らせ', 'body' => '初回登録でクーポン配布中。'],
                    ['type' => 'button', 'title' => '会員登録', 'button_label' => '会員証を作成', 'button_url' => '#member'],
                ],
            ],
            [
                'id' => 'campaign',
                'name' => 'キャンペーン',
                'blocks' => [
                    ['type' => 'hero', 'title' => '期間限定キャンペーン', 'body' => '今週だけの特別メニュー。', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200'],
                    ['type' => 'list', 'title' => 'おすすめ', 'list_items' => ['季節限定バーガー', 'スペシャルドリンク', '数量限定クーポン']],
                    ['type' => 'button', 'button_label' => 'クーポンを確認', 'button_url' => '#coupons'],
                ],
            ],
            [
                'id' => 'reservation',
                'name' => '予約案内',
                'blocks' => [
                    ['type' => 'hero', 'title' => '事前予約でスムーズに', 'body' => '予約枠を確認して、希望の時間を押さえましょう。', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200'],
                    ['type' => 'button', 'button_label' => '予約する', 'button_url' => '#reservations'],
                    ['type' => 'text', 'title' => '注意事項', 'body' => '当日キャンセルはお控えください。'],
                ],
            ],
            [
                'id' => 'gallery',
                'name' => 'ギャラリー',
                'blocks' => [
                    ['type' => 'text', 'title' => '店内・商品ギャラリー', 'body' => '最新の写真をチェック。'],
                    ['type' => 'gallery', 'source' => 'gallery'],
                ],
            ],
            [
                'id' => 'form',
                'name' => '問い合わせ',
                'blocks' => [
                    ['type' => 'text', 'title' => 'お問い合わせ', 'body' => 'ご意見・ご要望をお聞かせください。'],
                    ['type' => 'form', 'title' => '簡単フォーム', 'form_fields' => [
                        ['label' => 'お名前', 'type' => 'text', 'placeholder' => '例: 山田 太郎', 'required' => true],
                        ['label' => 'メール', 'type' => 'email', 'placeholder' => 'example@example.com', 'required' => true],
                        ['label' => '内容', 'type' => 'textarea', 'placeholder' => 'ご用件', 'required' => true],
                    ]],
                ],
            ],
        ];
    }

    public static function defaultLiffDesignerTemplates(): array
    {
        return [
            [
                'id' => 'simple-home',
                'name' => 'シンプルホーム',
                'blocks' => [
                    ['type' => 'label', 'title' => 'ようこそ'],
                    ['type' => 'text', 'body' => '簡単に注文や予約ができます。'],
                    ['type' => 'button', 'button_label' => 'メニュー', 'button_url' => '#products'],
                    ['type' => 'collection', 'source' => 'products', 'title' => '人気商品'],
                ],
            ],
            [
                'id' => 'story',
                'name' => 'ストーリー',
                'blocks' => [
                    ['type' => 'hero', 'title' => '本日のおすすめ', 'body' => '季節の食材を使用した特別メニュー', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200', 'button_label' => '予約', 'button_url' => '#reservations'],
                    ['type' => 'text', 'title' => 'こだわり', 'body' => '丁寧に仕上げた料理と落ち着いた空間。'],
                    ['type' => 'gallery', 'source' => 'gallery', 'title' => 'ギャラリー'],
                ],
            ],
            [
                'id' => 'member',
                'name' => '会員案内',
                'blocks' => [
                    ['type' => 'label', 'title' => '会員サービス'],
                    ['type' => 'text', 'body' => 'ポイントやスタンプでお得に。'],
                    ['type' => 'data', 'source' => 'member', 'title' => '会員情報'],
                    ['type' => 'data', 'source' => 'stamps', 'title' => 'スタンプ'],
                    ['type' => 'data', 'source' => 'coupons', 'title' => 'クーポン'],
                ],
            ],
            [
                'id' => 'order-flow',
                'name' => '注文フロー',
                'blocks' => [
                    ['type' => 'label', 'title' => '注文の流れ'],
                    ['type' => 'list', 'title' => 'ステップ', 'list_items' => ['メニューを見る', 'カートに追加', '注文確定', '受け取り'] ],
                    ['type' => 'data', 'source' => 'orders', 'title' => '注文状況'],
                ],
            ],
            [
                'id' => 'contact',
                'name' => 'お問い合わせ',
                'blocks' => [
                    ['type' => 'label', 'title' => 'お問い合わせ'],
                    ['type' => 'form', 'title' => 'フォーム', 'form_fields' => [
                        ['label' => 'お名前', 'type' => 'text', 'placeholder' => '山田 太郎', 'required' => true],
                        ['label' => 'メール', 'type' => 'email', 'placeholder' => 'example@example.com', 'required' => true],
                        ['label' => '内容', 'type' => 'textarea', 'placeholder' => 'お問い合わせ内容', 'required' => true],
                    ], 'form_button_label' => '送信'],
                ],
            ],
        ];
    }

    // ── LIFF タブレイアウト ──

    public static function getLiffTabLayouts(?int $projectId = null): array
    {
        $layouts = self::getValue('liff_tab_layouts', null, $projectId);
        if (!is_array($layouts) || $layouts === []) {
            return self::defaultLiffTabLayouts();
        }
        return $layouts;
    }

    public static function getLiffCustomCss(?int $projectId = null): string
    {
        return (string) self::getValue('liff_custom_css', '', $projectId);
    }

    public static function setLiffCustomCss(string $css, ?int $projectId = null): void
    {
        self::setValue('liff_custom_css', $css, 'string', $projectId);
    }

    public static function getLiffTabSettings(?int $projectId = null): array
    {
        $settings = self::getValue('liff_tab_settings', null, $projectId);
        return is_array($settings) ? $settings : [];
    }

    public static function setLiffTabSettings(array $settings, ?int $projectId = null): void
    {
        self::setValue('liff_tab_settings', $settings, 'json', $projectId);
    }

    public static function setLiffTabLayouts(array $layouts, ?int $projectId = null): void
    {
        self::setValue('liff_tab_layouts', $layouts, 'json', $projectId);
    }

    public static function getLineLiffId(?int $projectId = null): string
    {
        $value = trim((string) self::getValue('line_liff_id', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_id', ''));
    }

    public static function setLineLiffId(?string $liffId, ?int $projectId = null): void
    {
        self::setValue('line_liff_id', trim((string) ($liffId ?? '')), 'string', $projectId);
    }

    public static function getLineLiffUrl(?int $projectId = null): string
    {
        $value = trim((string) self::getValue('line_liff_url', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_url', ''));
    }

    public static function setLineLiffUrl(?string $liffUrl, ?int $projectId = null): void
    {
        self::setValue('line_liff_url', trim((string) ($liffUrl ?? '')), 'string', $projectId);
    }

    public static function defaultLiffTabLayouts(): array
    {
        return [
            'home' => self::defaultLiffBuilderTemplates()[0]['blocks'],
            'products' => [
                ['type' => 'data', 'source' => 'products'],
            ],
            'orders' => [
                ['type' => 'data', 'source' => 'orders'],
            ],
            'stamps' => [
                ['type' => 'data', 'source' => 'stamps'],
            ],
            'coupons' => [
                ['type' => 'data', 'source' => 'coupons'],
            ],
            'queue' => [
                ['type' => 'data', 'source' => 'queue'],
            ],
            'reservations' => [
                ['type' => 'data', 'source' => 'reservations'],
            ],
            'member' => [
                ['type' => 'data', 'source' => 'member'],
            ],
            'gallery' => [
                ['type' => 'data', 'source' => 'gallery'],
            ],
            'announcements' => [
                ['type' => 'data', 'source' => 'announcements'],
            ],
        ];
    }

    /** 全機能フラグを取得 */
    public static function getFeatureFlags(?int $projectId = null): array
    {
        $flags = [];
        foreach (self::FEATURE_FLAGS as $key => $default) {
            $flags[$key] = (bool) self::getValue($key, $default, $projectId);
        }
        return $flags;
    }

    /** 指定機能が有効か */
    public static function isFeatureEnabled(string $featureKey, ?int $projectId = null): bool
    {
        $default = self::FEATURE_FLAGS[$featureKey] ?? true;
        return (bool) self::getValue($featureKey, $default, $projectId);
    }

    /** 機能フラグを一括更新 */
    public static function setFeatureFlags(array $flags, ?int $projectId = null): void
    {
        foreach (self::FEATURE_FLAGS as $key => $default) {
            $value = isset($flags[$key]) ? '1' : '0';
            self::setValue($key, $value, 'boolean', $projectId);
        }
    }

    // ── プロフィール項目設定（スマレジ連携・必須/任意・任意項目） ──

    /** スマレジと同一内容で登録できるプロフィール項目のキーとラベル */
    public const PROFILE_SMAREGI_FIELDS = [
        'name' => 'お名前',
        'kana' => 'お名前（カナ）',
        'phone' => '電話番号',
        'email' => 'メールアドレス',
        'postal_code' => '郵便番号',
        'prefecture' => '都道府県',
        'city' => '市区町村',
        'street' => '番地・建物名',
        'address' => '住所（自由入力）',
        'birthday' => '生年月日',
        'sex' => '性別',
    ];

    /** プロフィール項目設定のデフォルト（スマレジ項目は表示ON・名前と電話は必須、任意項目は空） */
    public static function defaultProfileFieldSettings(): array
    {
        $smaregi = [];
        foreach (self::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $smaregi[$key] = [
                'label' => $label,
                'enabled' => true,
                'required' => in_array($key, ['name', 'phone'], true),
            ];
        }
        return [
            'smaregi_fields' => $smaregi,
            'custom_fields' => [],
        ];
    }

    /** プロフィール項目設定を取得（会員登録・編集フォームの表示とバリデーションに使用） */
    public static function getProfileFieldSettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;
        $raw = self::getValue('profile_field_settings', null, $projectId);
        if (! is_array($raw)) {
            return self::defaultProfileFieldSettings();
        }
        $defaults = self::defaultProfileFieldSettings();
        $smaregi = $defaults['smaregi_fields'];
        foreach ($defaults['smaregi_fields'] as $key => $def) {
            if (isset($raw['smaregi_fields'][$key]) && is_array($raw['smaregi_fields'][$key])) {
                $smaregi[$key] = [
                    'label' => $raw['smaregi_fields'][$key]['label'] ?? $def['label'],
                    'enabled' => (bool) ($raw['smaregi_fields'][$key]['enabled'] ?? $def['enabled']),
                    'required' => (bool) ($raw['smaregi_fields'][$key]['required'] ?? $def['required']),
                ];
            }
        }
        $custom = isset($raw['custom_fields']) && is_array($raw['custom_fields'])
            ? $raw['custom_fields']
            : [];
        return [
            'smaregi_fields' => $smaregi,
            'custom_fields' => $custom,
        ];
    }

    /** プロフィール項目設定を保存 */
    public static function setProfileFieldSettings(array $settings, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        self::setValue('profile_field_settings', $settings, 'json', $projectId);
    }

    // ── UI カスタマイズ（管理画面） ──

    public static function getAdminSidebarColor(?int $projectId = null): ?string
    {
        return self::getValue('admin_sidebar_color', null, $projectId);
    }

    public static function getAdminHeaderColor(?int $projectId = null): ?string
    {
        return self::getValue('admin_header_color', null, $projectId);
    }

    public static function getStampDefaultImageUrl(?int $projectId = null): ?string
    {
        return self::getValue('stamp_default_image_url', null, $projectId);
    }

    public static function getCouponDefaultImageUrl(?int $projectId = null): ?string
    {
        return self::getValue('coupon_default_image_url', null, $projectId);
    }

    public static function getPointEarnRate(?int $projectId = null): int
    {
        return (int) self::getValue('point_earn_rate', 100, $projectId);
    }

    public static function getPointsValidDays(?int $projectId = null): int
    {
        return (int) self::getValue('points_valid_days', 365, $projectId);
    }

    public static function getRankBasis(?int $projectId = null): string
    {
        return (string) self::getValue('member_rank_basis', 'points', $projectId);
    }

    /** 会員コード生成: sequential=連番, random=ランダム */
    public static function getMemberCodeType(?int $projectId = null): string
    {
        return (string) self::getValue('member_code_type', 'random', $projectId);
    }

    /** 会員コード形式: numeric=数字のみ, alphanumeric=英数字混在 */
    public static function getMemberCodeFormat(?int $projectId = null): string
    {
        return (string) self::getValue('member_code_format', 'numeric', $projectId);
    }

    /** 会員コード桁数（プレフィックス除く） */
    public static function getMemberCodeLength(?int $projectId = null): int
    {
        return (int) self::getValue('member_code_length', 10, $projectId);
    }

    /** 会員コード先頭プレフィックス（任意） */
    public static function getMemberCodePrefix(?int $projectId = null): string
    {
        return (string) self::getValue('member_code_prefix', '', $projectId);
    }

    /** 連番時の次に使う番号（内部用） */
    public static function getMemberCodeNextNumber(?int $projectId = null): int
    {
        return (int) self::getValue('member_code_next_number', 1, $projectId);
    }

    public static function setMemberCodeNextNumber(int $next, ?int $projectId = null): void
    {
        self::setValue('member_code_next_number', $next, 'integer', $projectId);
    }

    /** フロントでポイント数を表示するか */
    public static function getShowPointsDisplay(?int $projectId = null): bool
    {
        return (bool) self::getValue('show_points_display', true, $projectId);
    }

    /** フロントで「次のランクまで」を表示するか */
    public static function getShowRankProgress(?int $projectId = null): bool
    {
        return (bool) self::getValue('show_rank_progress', true, $projectId);
    }

    /** 会員未登録（ゲスト）のときに会員証カードを表示するか。false のときはカードを表示せずメッセージのみ */
    public static function getShowMemberCardForGuest(?int $projectId = null): bool
    {
        return (bool) self::getValue('show_member_card_for_guest', true, $projectId);
    }

    public static function setShowMemberCardForGuest(bool $value, ?int $projectId = null): void
    {
        self::setValue('show_member_card_for_guest', $value, 'boolean', $projectId ?? 1);
    }

    /** 会員証のレイアウトデザイン。default, logo_top, logo_center, logo_header, compact */
    public static function getMemberCardDesign(?int $projectId = null): string
    {
        $v = self::getValue('member_card_design', 'default', $projectId);
        return in_array($v, ['default', 'logo_top', 'logo_center', 'logo_header', 'compact'], true) ? $v : 'default';
    }

    public static function setMemberCardDesign(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['default', 'logo_top', 'logo_center', 'logo_header', 'compact'], true) ? $value : 'default';
        self::setValue('member_card_design', $value, 'string', $projectId ?? 1);
    }

    /** 会員証の表示形式。card=カード型（裏表あり）, sheet=シート型（1面表示） */
    public static function getMemberCardDisplayType(?int $projectId = null): string
    {
        $v = self::getValue('member_card_display_type', 'card', $projectId);
        return in_array($v, ['card', 'sheet'], true) ? $v : 'card';
    }

    public static function setMemberCardDisplayType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['card', 'sheet'], true) ? $value : 'card';
        self::setValue('member_card_display_type', $value, 'string', $projectId ?? 1);
    }

    /** 会員証の会員番号表示。barcode=バーコード, qrcode=QRコード */
    public static function getMemberCardCodeType(?int $projectId = null): string
    {
        $v = self::getValue('member_card_code_type', 'barcode', $projectId);
        return in_array($v, ['barcode', 'qrcode'], true) ? $v : 'barcode';
    }

    public static function setMemberCardCodeType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['barcode', 'qrcode'], true) ? $value : 'barcode';
        self::setValue('member_card_code_type', $value, 'string', $projectId ?? 1);
    }

    public static function getMemberRankSettings(?int $projectId = null): array
    {
        $settings = self::getValue('member_rank_settings', null, $projectId);
        if (!is_array($settings) || $settings === []) {
            return self::defaultMemberRankSettings();
        }
        $normalized = [];
        foreach ($settings as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = $row['key'] ?? null;
            if (\App\Util\StrHelper::isEmptyStringOrNull($key)) {
                continue;
            }
            $normalized[] = [
                'key' => (string) $key,
                'label' => (string) ($row['label'] ?? $key),
                'min_points' => (int) ($row['min_points'] ?? 0),
                'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
                'icon_url' => $row['icon_url'] ?? null,
                'stamp_background_url' => $row['stamp_background_url'] ?? null,
                'card_color' => isset($row['card_color']) && $row['card_color'] !== '' ? (string) $row['card_color'] : null,
                'order' => (int) ($row['order'] ?? 0),
            ];
        }
        if ($normalized === []) {
            return self::defaultMemberRankSettings();
        }
        usort($normalized, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));
        return $normalized;
    }

    public static function setMemberRankSettings(array $settings, ?int $projectId = null): void
    {
        self::setValue('member_rank_settings', $settings, 'json', $projectId);
    }

    public static function defaultMemberRankSettings(): array
    {
        return [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#cd7f32', 'order' => 1],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 1000, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#c0c0c0', 'order' => 2],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 5000, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#ffd700', 'order' => 3],
            ['key' => 'platinum', 'label' => 'プラチナ', 'min_points' => 10000, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#e5e4e2', 'order' => 4],
        ];
    }
    public static function getAdminPrimaryColor(?int $projectId = null): ?string
    {
        return self::getValue('admin_primary_color', null, $projectId);
    }

    public static function getFrontendLogoUrl(?int $projectId = null): ?string
    {
        return self::getValue('frontend_logo_url', null, $projectId);
    }

    /** Google Wallet プログラムロゴ画像URL（管理画面で設定、未設定時は config を使用） */
    public static function getWalletGoogleProgramLogoUrl(?int $projectId = null): ?string
    {
        $v = self::getValue('wallet_google_program_logo_url', null, $projectId);
        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    /** Google Wallet ヒーロー画像URL（管理画面で設定、未設定時は config を使用） */
    public static function getWalletGoogleHeroImageUrl(?int $projectId = null): ?string
    {
        $v = self::getValue('wallet_google_hero_image_url', null, $projectId);
        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    public static function setWalletGoogleProgramLogoUrl(?string $url, ?int $projectId = null): void
    {
        self::setValue('wallet_google_program_logo_url', trim((string) ($url ?? '')), 'string', $projectId ?? 1);
    }

    public static function setWalletGoogleHeroImageUrl(?string $url, ?int $projectId = null): void
    {
        self::setValue('wallet_google_hero_image_url', trim((string) ($url ?? '')), 'string', $projectId ?? 1);
    }

    public static function getFrontendHeaderText(?int $projectId = null): ?string
    {
        return self::getValue('frontend_header_text', null, $projectId);
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
