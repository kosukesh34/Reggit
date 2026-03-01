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

    /** フロントメニュー項目を取得（ホーム設定で「表示する」にしたもののみ・order でソート） */
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
            $row = array_merge($defaults->get($tab), $item);
            // ホーム設定で「表示する」がオフのタブはユーザー画面に出さない
            if (isset($row['enabled']) && $row['enabled'] === false) {
                continue;
            }
            $merged[] = $row;
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
                'id' => 'welcome-home',
                'name' => 'ようこそトップ',
                'blocks' => [
                    ['type' => 'hero', 'title' => 'いらっしゃいませ', 'subtitle' => 'LINEから注文・予約・会員特典までまとめてご利用いただけます。', 'gradient_start' => '#0ea5e9', 'gradient_end' => '#22c55e'],
                    ['type' => 'banner', 'icon' => 'fa-gift', 'text' => '初回登録でドリンククーポンを配布中', 'color' => '#0f766e'],
                    ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products'],
                    ['type' => 'button', 'text' => '予約する', 'action' => '#reservations', 'color' => '#1d4ed8'],
                ],
            ],
            [
                'id' => 'campaign-slider',
                'name' => 'キャンペーン訴求',
                'blocks' => [
                    ['type' => 'slider', 'slide_height' => 180, 'slides' => [
                        ['image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200', 'caption' => '春の新メニュー公開中'],
                        ['image_url' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=1200', 'caption' => 'テイクアウト10%OFF'],
                        ['image_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200', 'caption' => '週末限定セット販売'],
                    ]],
                    ['type' => 'heading', 'text' => '注目キャンペーン', 'size' => 'large'],
                    ['type' => 'icon_text', 'rows' => [
                        ['icon' => 'fa-check-circle', 'text' => '会員はポイント2倍デーあり', 'color' => '#06C755'],
                        ['icon' => 'fa-check-circle', 'text' => '予約限定特典クーポン', 'color' => '#06C755'],
                        ['icon' => 'fa-check-circle', 'text' => '新商品は毎週更新', 'color' => '#06C755'],
                    ]],
                ],
            ],
            [
                'id' => 'quick-order',
                'name' => 'クイック注文導線',
                'blocks' => [
                    ['type' => 'heading', 'text' => 'すぐに注文する', 'size' => 'large'],
                    ['type' => 'text', 'text' => '人気カテゴリから選んで最短で注文できます。'],
                    ['type' => 'collection', 'layout' => 'grid', 'columns' => 2, 'items' => [
                        ['title' => '定番メニュー', 'description' => '人気No.1', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=600'],
                        ['title' => '季節限定', 'description' => '期間限定', 'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=600'],
                        ['title' => 'ドリンク', 'description' => 'テイクアウト可', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600'],
                        ['title' => 'スイーツ', 'description' => '人気急上昇', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600'],
                    ]],
                    ['type' => 'button', 'text' => '商品一覧へ', 'action' => '#products'],
                ],
            ],
            [
                'id' => 'member-benefits',
                'name' => '会員特典',
                'blocks' => [
                    ['type' => 'heading', 'text' => '会員特典のご案内', 'size' => 'large'],
                    ['type' => 'list', 'items' => ['来店ごとにスタンプを獲得', 'ランクアップで特典が増加', '会員限定クーポンを配信']],
                    ['type' => 'button', 'text' => '会員証を確認する', 'action' => '#member'],
                ],
            ],
            [
                'id' => 'super-pattern-classic',
                'name' => 'スーパターン: クラシックレストラン',
                'scope' => 'all_tabs',
                'description' => '温かみのある配色で、予約と会員導線を強化した標準構成です。',
                'layouts' => self::defaultLiffTabLayouts(),
                'tab_settings' => self::defaultLiffTabSettings(),
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-cafe',
                'name' => 'スーパターン: ナチュラルカフェ',
                'scope' => 'all_tabs',
                'description' => '軽やかな配色と写真訴求を中心にしたカフェ向け構成です。',
                'layouts' => self::defaultLiffCafePatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#f7f6f1'],
                    'products' => ['background_color' => '#f8faf7'],
                    'orders' => ['background_color' => '#f5f8f6'],
                    'stamps' => ['background_color' => '#f4fbf6'],
                    'coupons' => ['background_color' => '#f7faf4'],
                    'queue' => ['background_color' => '#f8f7f2'],
                    'reservations' => ['background_color' => '#f4f9f8'],
                    'member' => ['background_color' => '#f6f8f4'],
                    'gallery' => ['background_color' => '#f9f8f4'],
                    'announcements' => ['background_color' => '#f8f8f6'],
                ],
                'custom_css' => '',
            ],
            [
                'id' => 'super-pattern-fast',
                'name' => 'スーパターン: ファストカジュアル',
                'scope' => 'all_tabs',
                'description' => '注文速度を最優先にした、強いCTA中心の高速導線です。',
                'layouts' => self::defaultLiffFastPatternLayouts(),
                'tab_settings' => [
                    'home' => ['background_color' => '#f3f6ff'],
                    'products' => ['background_color' => '#f5f7ff'],
                    'orders' => ['background_color' => '#f3f4f8'],
                    'stamps' => ['background_color' => '#f6f6ff'],
                    'coupons' => ['background_color' => '#fff7f2'],
                    'queue' => ['background_color' => '#f7f7fa'],
                    'reservations' => ['background_color' => '#f2f8ff'],
                    'member' => ['background_color' => '#f5f5f7'],
                    'gallery' => ['background_color' => '#f6f8ff'],
                    'announcements' => ['background_color' => '#f5f7ff'],
                ],
                'custom_css' => '',
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
        return is_array($settings) && $settings !== [] ? $settings : self::defaultLiffTabSettings();
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
            'home' => [
                ['type' => 'hero', 'title' => 'モバイルオーダーへようこそ', 'subtitle' => '注文・予約・会員特典をこの画面でまとめて利用できます。', 'gradient_start' => '#0ea5e9', 'gradient_end' => '#22c55e'],
                ['type' => 'banner', 'icon' => 'fa-bell', 'text' => '本日限定: LINE会員はポイント2倍', 'color' => '#0f766e'],
                ['type' => 'heading', 'text' => 'おすすめメニュー', 'size' => 'large'],
                ['type' => 'collection', 'layout' => 'scroll', 'item_width' => 150, 'item_image_height' => 110, 'items' => [
                    ['title' => 'シグネチャープレート', 'description' => '人気No.1', 'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=800'],
                    ['title' => '季節のサラダ', 'description' => '旬の素材', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'],
                    ['title' => '本日のパスタ', 'description' => '限定メニュー', 'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=800'],
                ]],
                ['type' => 'button', 'text' => '今すぐ注文する', 'action' => '#products', 'color' => '#06C755'],
                ['type' => 'button', 'text' => '席を予約する', 'action' => '#reservations', 'color' => '#1d4ed8'],
                ['type' => 'data', 'source' => 'announcements'],
            ],
            'products' => [
                ['type' => 'heading', 'text' => '商品一覧', 'size' => 'large'],
                ['type' => 'text', 'text' => 'カテゴリから商品を選択し、カートに追加してください。'],
                ['type' => 'data', 'source' => 'products'],
            ],
            'orders' => [
                ['type' => 'heading', 'text' => '注文状況', 'size' => 'large'],
                ['type' => 'text', 'text' => '注文履歴と現在のステータスを確認できます。'],
                ['type' => 'banner', 'icon' => 'fa-clock', 'text' => '受け取り準備が完了すると通知します', 'color' => '#2563eb'],
                ['type' => 'data', 'source' => 'orders'],
            ],
            'stamps' => [
                ['type' => 'heading', 'text' => 'スタンプカード', 'size' => 'large'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => '来店ごとにスタンプ獲得', 'color' => '#06C755'],
                    ['icon' => 'fa-gift', 'text' => '満了で特典を獲得', 'color' => '#f59e0b'],
                ]],
                ['type' => 'data', 'source' => 'stamps'],
            ],
            'coupons' => [
                ['type' => 'heading', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'icon' => 'fa-ticket-alt', 'text' => '会員限定クーポンを配信中', 'color' => '#7c3aed'],
                ['type' => 'text', 'text' => 'ご注文前に利用可能なクーポンをご確認ください。'],
                ['type' => 'data', 'source' => 'coupons'],
            ],
            'queue' => [
                ['type' => 'heading', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'text', 'text' => '混雑時は順番待ち受付をご利用ください。'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-user-check', 'text' => '人数を入力して受付', 'color' => '#0f766e'],
                    ['icon' => 'fa-mobile-alt', 'text' => '呼び出し通知でお知らせ', 'color' => '#0f766e'],
                ]],
                ['type' => 'data', 'source' => 'queue'],
            ],
            'reservations' => [
                ['type' => 'heading', 'text' => '来店予約', 'size' => 'large'],
                ['type' => 'list', 'items' => ['日時を選択', '人数を指定', '内容を確認して確定']],
                ['type' => 'banner', 'icon' => 'fa-calendar-check', 'text' => '予約変更・キャンセルは注文履歴から可能です', 'color' => '#1d4ed8'],
                ['type' => 'data', 'source' => 'reservations'],
            ],
            'member' => [
                ['type' => 'heading', 'text' => '会員ページ', 'size' => 'large'],
                ['type' => 'text', 'text' => '会員証・ポイント・ランクを確認できます。'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-coins', 'text' => '購入でポイントが貯まります', 'color' => '#d97706'],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が増えます', 'color' => '#d97706'],
                ]],
                ['type' => 'data', 'source' => 'member_card'],
            ],
            'gallery' => [
                ['type' => 'heading', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'text', 'text' => '店内・メニュー写真を随時更新しています。'],
                ['type' => 'data', 'source' => 'gallery'],
            ],
            'announcements' => [
                ['type' => 'heading', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'icon' => 'fa-bullhorn', 'text' => '営業時間や限定情報を配信しています', 'color' => '#0f766e'],
                ['type' => 'data', 'source' => 'announcements'],
            ],
        ];
    }

    public static function defaultLiffTabSettings(): array
    {
        return [
            'home' => ['background_color' => '#f2f9f6'],
            'products' => ['background_color' => '#f7faf9'],
            'orders' => ['background_color' => '#f5f7fa'],
            'stamps' => ['background_color' => '#f8faf5'],
            'coupons' => ['background_color' => '#faf6ff'],
            'queue' => ['background_color' => '#f7f7f7'],
            'reservations' => ['background_color' => '#f4f8ff'],
            'member' => ['background_color' => '#f7f8f9'],
            'gallery' => ['background_color' => '#fafaf8'],
            'announcements' => ['background_color' => '#f6f9ff'],
        ];
    }

    private static function defaultLiffCafePatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => 'Natural Cafe', 'subtitle' => 'ゆったりした時間を、LINEからスマートに。', 'gradient_start' => '#84cc16', 'gradient_end' => '#14b8a6'],
            ['type' => 'heading', 'text' => '今週のおすすめ', 'size' => 'large'],
            ['type' => 'slider', 'slide_height' => 180, 'slides' => [
                ['image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200', 'caption' => '季節のハンドドリップ'],
                ['image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=1200', 'caption' => 'スイーツセット'],
            ]],
            ['type' => 'button', 'text' => 'メニューを見る', 'action' => '#products'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['coupons'] = [
            ['type' => 'heading', 'text' => 'カフェクーポン', 'size' => 'large'],
            ['type' => 'banner', 'icon' => 'fa-mug-hot', 'text' => 'モーニング時間帯はドリンク50円OFF', 'color' => '#166534'],
            ['type' => 'data', 'source' => 'coupons'],
        ];
        return $layouts;
    }

    private static function defaultLiffFastPatternLayouts(): array
    {
        $layouts = self::defaultLiffTabLayouts();
        $layouts['home'] = [
            ['type' => 'hero', 'title' => '最短30秒で注文', 'subtitle' => 'ピーク時でも、事前注文で待ち時間を短縮。', 'gradient_start' => '#2563eb', 'gradient_end' => '#ea580c'],
            ['type' => 'button', 'text' => '商品を選ぶ', 'action' => '#products', 'color' => '#ea580c'],
            ['type' => 'button', 'text' => '現在の注文を見る', 'action' => '#orders', 'color' => '#2563eb'],
            ['type' => 'banner', 'icon' => 'fa-bolt', 'text' => '受取時間を選んで、店頭でスムーズ受取', 'color' => '#1d4ed8'],
            ['type' => 'data', 'source' => 'announcements'],
        ];
        $layouts['products'] = [
            ['type' => 'heading', 'text' => 'クイックメニュー', 'size' => 'large'],
            ['type' => 'text', 'text' => '人気順で表示しています。まずはおすすめからどうぞ。'],
            ['type' => 'data', 'source' => 'products'],
        ];
        $layouts['queue'] = [
            ['type' => 'heading', 'text' => '待ち状況', 'size' => 'large'],
            ['type' => 'banner', 'icon' => 'fa-users', 'text' => '呼び出し通知を受け取れます', 'color' => '#0f766e'],
            ['type' => 'data', 'source' => 'queue'],
        ];
        return $layouts;
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

    /** 会員証のレイアウトデザイン。default, logo_top, logo_center, logo_header, compact, split_banner, minimal_line, emblem */
    public static function getMemberCardDesign(?int $projectId = null): string
    {
        $v = self::getValue('member_card_design', 'default', $projectId);
        return in_array($v, ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'], true) ? $v : 'default';
    }

    public static function setMemberCardDesign(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'], true) ? $value : 'default';
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
