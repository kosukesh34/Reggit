<?php

namespace App\Support;

use App\Enums\ProjectPlan;
use App\Models\AdminUser;

final class AdminSettingsNavigation
{
    public static function sections(?AdminUser $user, string $plan = ProjectPlan::NORMAL): array
    {
        $can = fn (string $permission): bool => $user === null || $user->hasPermission($permission);
        $hasPlan = fn (string $feature): bool => PlanFeatures::hasFeature($plan, $feature);

        $sections = [
            ['title' => '店舗・運用', 'items' => [
                $can('settings.view') ? ['route' => 'admin.settings.basic', 'icon' => 'fas fa-store', 'label' => '店舗の基本', 'hint' => '営業時間・注文'] : null,
                $can('settings.view') ? ['route' => 'admin.settings.features', 'icon' => 'fas fa-toggle-on', 'label' => '機能の表示', 'hint' => '機能の有効化'] : null,
                $can('reservations.view') ? ['route' => 'admin.settings.reservations', 'icon' => 'fas fa-calendar-check', 'label' => '予約', 'hint' => '時間帯・予約枠'] : null,
                $can('queue.view') ? ['route' => 'admin.settings.queue', 'icon' => 'fas fa-list-ol', 'label' => '順番待ち', 'hint' => '待ち時間'] : null,
            ]],
            ['title' => 'お客様向け画面', 'items' => [
                $can('settings.view') ? ['route' => 'admin.settings.home', 'icon' => 'fas fa-home', 'label' => 'ホーム・メニュー', 'hint' => 'SkipとLINEの共通画面'] : null,
                $can('settings.view') ? ['route' => 'admin.settings.advanced', 'icon' => 'fas fa-palette', 'label' => '配色', 'hint' => '成功・注意などの色'] : null,
            ]],
            ['title' => '会員・販促', 'items' => [
                ($can('members.view') && $can('settings.view')) ? ['route' => 'admin.settings.ranks', 'icon' => 'fas fa-id-card', 'label' => '会員証・ランク', 'hint' => '表示・ポイント'] : null,
                ($can('members.view') && $can('settings.view')) ? ['route' => 'admin.settings.profile', 'icon' => 'fas fa-user-cog', 'label' => 'プロフィール', 'hint' => '登録項目'] : null,
                ($can('settings.view') && $hasPlan(PlanFeatures::WALLET)) ? ['route' => 'admin.settings.wallet', 'icon' => 'fas fa-wallet', 'label' => 'Wallet画像', 'hint' => '会員証の画像'] : null,
                $can('coupons.view') ? ['route' => 'admin.settings.coupons', 'icon' => 'fas fa-ticket-alt', 'label' => 'クーポン', 'hint' => '利用・初期画像'] : null,
                ($can('coupons.view') && $hasPlan(PlanFeatures::LOTTERY)) ? ['route' => 'admin.settings.lottery', 'icon' => 'fas fa-dice', 'label' => '購入特典ゲーム', 'hint' => '条件・景品'] : null,
                $can('announcements.view') ? ['route' => 'admin.settings.announcements', 'icon' => 'fas fa-bullhorn', 'label' => 'お知らせ', 'hint' => '表示・掲載期間'] : null,
            ]],
            ['title' => '管理', 'items' => [
                ($user !== null && $user->canCreateRole(AdminUser::ROLE_STAFF)) ? ['route' => 'admin.settings.staff', 'icon' => 'fas fa-user-shield', 'label' => 'スタッフ・権限', 'hint' => '追加・操作権限'] : null,
                $can('audit_logs.view') ? ['route' => 'admin.audit-logs.index', 'icon' => 'fas fa-clipboard-list', 'label' => '操作履歴', 'hint' => '監査ログ'] : null,
            ]],
        ];

        return array_values(array_filter(array_map(static function (array $section): array {
            $section['items'] = array_values(array_filter($section['items']));

            return $section;
        }, $sections), static fn (array $section): bool => $section['items'] !== []));
    }
}
