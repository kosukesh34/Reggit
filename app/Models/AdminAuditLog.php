<?php

namespace App\Models;

use App\Util\StrHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'project_id',
        'route_name',
        'method',
        'url',
        'ip',
        'user_agent',
        'payload',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload' => 'array',
    ];

    /** 保存時に payload が配列なら必ず JSON 文字列に変換（Array to string conversion 防止） */
    protected function setPayloadAttribute($value): void
    {
        $this->attributes['payload'] = is_array($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE)
            : (is_string($value) ? $value : '');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** 操作内容の表示用ラベル（どのような変更か） */
    public function getActionLabel(): string
    {
        $payload = $this->payload;
        if (is_array($payload) && ! StrHelper::isEmptyStringOrNull($payload['event'] ?? null)) {
            return match ((string) $payload['event']) {
                'login_success' => 'ログイン',
                'logout' => 'ログアウト',
                default => (string) $payload['event'],
            };
        }

        $route = $this->route_name;
        $method = $this->method ?? '';
        if ($route === null || $route === '') {
            return $method ? "{$method} リクエスト" : '操作';
        }

        $label = self::ROUTE_ACTION_LABELS[$route] ?? null;
        if ($label !== null) {
            return $label;
        }

        if (str_ends_with($route, '.store')) {
            return self::routeSegmentToLabel($route, 'store').'を作成';
        }
        if (str_ends_with($route, '.update')) {
            return self::routeSegmentToLabel($route, 'update').'を更新';
        }
        if (str_ends_with($route, '.destroy') || str_ends_with($route, '.delete')) {
            return self::routeSegmentToLabel($route, 'destroy').'を削除';
        }
        if (str_ends_with($route, '.revoke')) {
            return 'APIトークンを失効';
        }
        if (str_ends_with($route, '.rotate')) {
            return 'APIトークンを再発行';
        }
        if (str_ends_with($route, '.status')) {
            return 'ステータスを更新';
        }
        if (str_ends_with($route, '.complete')) {
            return '完了処理';
        }

        return $route;
    }

    private static function routeSegmentToLabel(string $route, string $suffix): string
    {
        $segments = explode('.', $route);
        $resource = $segments[count($segments) - 2] ?? 'データ';

        return self::RESOURCE_LABELS[$resource] ?? $resource;
    }

    /** ルート名 → 操作ラベル（完全一致用） */
    private const ROUTE_ACTION_LABELS = [
        'admin.login.submit' => 'ログイン',
        'system-admin.projects.store' => 'プロジェクトを作成',
        'system-admin.projects.update' => 'プロジェクトを更新',
        'system-admin.accounts.store' => 'アカウントを作成',
        'system-admin.accounts.update' => 'アカウントを更新',
        'system-admin.accounts.destroy' => 'アカウントを削除',
        'system-admin.api-tokens.store' => 'APIトークンを発行',
        'system-admin.api-tokens.update' => 'APIトークンを更新',
        'system-admin.api-tokens.revoke' => 'APIトークンを失効',
        'system-admin.api-tokens.rotate' => 'APIトークンを再発行',
        'admin.products.store' => '商品を作成',
        'admin.products.update' => '商品を更新',
        'admin.products.delete' => '商品を削除',
        'admin.orders.status' => '注文ステータスを更新',
        'admin.orders.complete' => '注文を完了',
        'admin.members.update' => '会員を更新',
        'admin.reservations.status' => '予約ステータスを更新',
        'admin.reservations.complete' => '予約を完了',
        'admin.queue.status' => '順番待ちステータスを更新',
        'admin.stamps.store' => 'スタンプカードを作成',
        'admin.stamps.update' => 'スタンプカードを更新',
        'admin.stamps.delete' => 'スタンプカードを削除',
        'admin.coupons.store' => 'クーポンを作成',
        'admin.coupons.update' => 'クーポンを更新',
        'admin.coupons.delete' => 'クーポンを削除',
        'admin.announcements.store' => 'お知らせを作成',
        'admin.announcements.update' => 'お知らせを更新',
        'admin.announcements.delete' => 'お知らせを削除',
        'admin.settings.basic.update' => '基本設定を更新',
        'admin.settings.kitchen-token.regenerate' => 'キッチンモニター用トークンを発行',
        'admin.settings.advanced.update' => '詳細設定を更新',
        'admin.settings.home.update' => 'ホーム設定を更新',
        'admin.settings.menu.update' => 'メニュー設定を更新', // 廃止（ホームへリダイレクト。互換のためキーは残す）
        'admin.settings.features.update' => '機能・カスタマイズ設定を更新',
    ];

    private const RESOURCE_LABELS = [
        'projects' => 'プロジェクト',
        'accounts' => 'アカウント',
        'api-tokens' => 'APIトークン',
        'products' => '商品',
        'orders' => '注文',
        'members' => '会員',
        'reservations' => '予約',
        'queue' => '順番待ち',
        'stamps' => 'スタンプ',
        'coupons' => 'クーポン',
        'announcements' => 'お知らせ',
        'settings' => '設定',
    ];

    /** A human-readable, PII-safe description of the affected area. */
    public function getTargetLabel(): string
    {
        $route = (string) ($this->route_name ?? '');
        $segments = explode('.', $route);
        $resource = $segments[count($segments) - 2] ?? '';
        $resourceLabel = self::RESOURCE_LABELS[$resource] ?? match ($resource) {
            'api-tokens' => 'APIトークン',
            'accounts' => 'アカウント',
            'projects' => 'プロジェクト',
            'settings' => '設定',
            default => '',
        };

        if ($resourceLabel === '') {
            return $this->project?->name ?? '管理画面';
        }

        $payload = is_array($this->payload) ? $this->payload : [];
        $id = $payload['id'] ?? null;
        $params = request()->route()?->parameters() ?? [];
        $id ??= $params['id'] ?? null;

        return $resourceLabel.($id ? ' #'.$id : '');
    }

    /** PII-safe list of the changed field names, never their submitted values. */
    public function getChangedFieldsLabel(): string
    {
        $payload = is_array($this->payload) ? $this->payload : [];
        $labels = [
            'name' => '名称', 'title' => 'タイトル', 'price' => '価格',
            'status' => 'ステータス', 'service_status' => 'サービス状態',
            'is_active' => '公開状態', 'role' => '権限', 'permissions' => '権限設定',
            'permission_ids' => '権限設定', 'sort_order' => '表示順',
            'display_order' => '表示順', 'type' => '種類',
        ];
        $fields = array_values(array_unique(array_filter(array_map(
            fn ($key) => $labels[$key] ?? null,
            array_keys($payload)
        ))));

        return $fields === [] ? '変更項目の詳細は記録されていません' : implode('、', $fields);
    }
}
