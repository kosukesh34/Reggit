<?php

namespace App\Support;

use Illuminate\Http\Request;

final class WebNavigation
{
    /** @return array{href: string, label: string}|null */
    public static function adminBack(Request $request): ?array
    {
        $name = (string) $request->route()?->getName();
        $system = str_starts_with($name, 'system-admin.');
        $prefix = $system ? 'system-admin.' : 'admin.';
        $page = substr($name, strlen($prefix));
        if (in_array($page, ['dashboard', 'project.home'], true) || str_starts_with($page, 'passkey')) {
            return null;
        }

        $params = $system ? [] : ['project_key' => $request->route('project_key')];
        $target = 'dashboard';
        $label = 'ダッシュボードへ戻る';
        if (! $system && (str_starts_with($page, 'settings.') || str_starts_with($page, 'stores.')) && $page !== 'settings.index') {
            $target = 'settings.index';
            $label = '設定一覧へ戻る';
        } elseif (in_array($page, ['members.edit', 'members.visits.detail', 'reservations.edit'], true)) {
            $target = str_starts_with($page, 'members.') ? 'members.detail' : 'reservations.detail';
            $params['id'] = $request->route('id');
            $label = str_starts_with($page, 'members.') ? '会員詳細へ戻る' : '予約詳細へ戻る';
        } elseif ($page === 'coupons.deliveries.show') {
            $target = 'coupons.bulk-issue';
            $label = 'クーポン配布へ戻る';
        } else {
            $lists = $system ? [
                'projects' => ['projects.index', 'プロジェクト一覧'],
                'accounts' => ['accounts.index', 'アカウント一覧'],
                'audit-logs' => ['audit-logs.index', '監査ログ一覧'],
                'help-articles' => ['help-articles.index', 'ヘルプ一覧'],
                'notices' => ['notices.index', '運営からのお知らせ'],
            ] : [
                'orders' => ['orders', '注文一覧'],
                'products' => ['products', '商品一覧'],
                'members' => ['members', '会員一覧'],
                'reservations' => ['reservations', '予約一覧'],
                'coupons' => ['coupons', 'クーポン一覧'],
                'stamps' => ['stamps', 'スタンプ一覧'],
                'announcements' => ['announcements', 'お知らせ一覧'],
                'after-registration-deliveries' => ['after-registration-deliveries.index', '登録後配信一覧'],
                'accounts' => ['accounts.index', 'アカウント一覧'],
                'audit-logs' => ['audit-logs.index', '監査ログ一覧'],
                'help' => ['help', 'ヘルプ一覧'],
                'gallery' => ['gallery.items', '画像一覧'],
            ];
            $section = explode('.', $page)[0];
            if (isset($lists[$section]) && $page !== $lists[$section][0]) {
                [$target, $parent] = $lists[$section];
                $label = $parent.'へ戻る';
            }
        }

        return ['href' => route($prefix.$target, $params), 'label' => $label];
    }
}
