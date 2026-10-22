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
            ['title' => '店舗運用', 'items' => [
                $can('settings.view') ? ['route' => 'admin.settings.features', 'icon' => 'fas fa-toggle-on', 'label' => '利用機能', 'hint' => 'お客様向けアプリで利用する機能の有効化'] : null,
                ['route' => 'admin.stores.index', 'icon' => 'fas fa-store', 'label' => '利用店舗', 'hint' => 'スマレジ店舗の同期・利用店舗の選択'],
                $can('settings.view') ? ['route' => 'admin.settings.basic', 'icon' => 'fas fa-store', 'label' => '店舗運用設定', 'hint' => '営業時間、注文・通知、店舗端末'] : null,
                $can('settings.view') ? ['route' => 'admin.settings.shop', 'icon' => 'fas fa-shipping-fast', 'label' => 'ショップ', 'hint' => '配送ECの公開・送料・トップ販促・注目商品'] : null,
                $can('reservations.view') ? ['route' => 'admin.settings.reservations', 'icon' => 'fas fa-calendar-check', 'label' => '予約', 'hint' => '時間帯・予約枠'] : null,
                $can('queue.view') ? ['route' => 'admin.settings.queue', 'icon' => 'fas fa-list-ol', 'label' => '順番待ち', 'hint' => '待ち時間'] : null,
            ]],
            ['title' => '画面・デザイン', 'items' => [
                $can('settings.view') ? ['route' => 'admin.settings.home', 'icon' => 'fas fa-mobile-alt', 'label' => 'アプリデザイン', 'hint' => 'ホーム・メニュー、配色、店舗名・ロゴ、会員証、Wallet'] : null,
                $can('settings.view') ? ['route' => 'admin.settings.admin-design', 'icon' => 'fas fa-desktop', 'label' => '管理画面デザイン', 'hint' => 'スタッフ向け管理画面の配色'] : null,
            ]],
            ['title' => '会員', 'items' => [
                ($can('members.view') && $can('settings.view')) ? ['route' => 'admin.settings.ranks', 'icon' => 'fas fa-id-card', 'label' => 'ランク・ポイント・スタンプ', 'hint' => 'ランクの判定条件、ポイント・スタンプの付与ルール'] : null,
                ($can('members.view') && $can('settings.view')) ? ['route' => 'admin.settings.profile', 'icon' => 'fas fa-user-cog', 'label' => '会員登録・プロフィール', 'hint' => '登録方法、入力項目、会員番号の発行ルール'] : null,
                ($can('settings.view') || $can('members.manage')) ? ['route' => 'admin.settings.existing-member-link', 'icon' => 'fas fa-link', 'label' => '既存会員連携', 'hint' => 'スマレジの会員を結びつける'] : null,
            ]],
            ['title' => '販促', 'items' => [
                $can('coupons.view') ? ['route' => 'admin.settings.coupons', 'icon' => 'fas fa-ticket-alt', 'label' => 'クーポン', 'hint' => '利用状況の確認とクーポン管理への案内'] : null,
                ($can('coupons.view') && $hasPlan(PlanFeatures::LOTTERY)) ? ['route' => 'admin.settings.lottery', 'icon' => 'fas fa-dice', 'label' => '購入特典ゲーム', 'hint' => '条件・景品'] : null,
                $can('announcements.view') ? ['route' => 'admin.settings.announcements', 'icon' => 'fas fa-bullhorn', 'label' => 'お知らせ', 'hint' => '新規お知らせの固定表示・掲載期間'] : null,
            ]],
            ['title' => '連携・管理', 'items' => [
                $can('settings.manage') ? ['route' => 'admin.settings.payments', 'icon' => 'fas fa-credit-card', 'label' => '決済・入金先', 'hint' => 'Stripeアカウント、PayPay・Apple Pay・決済ブランド'] : null,
                $can('settings.view') ? ['route' => 'admin.settings.liff-connection', 'icon' => 'fab fa-line', 'label' => 'LINE接続・通知API', 'hint' => 'LIFF ID・URL、友だち追加、Messaging API'] : null,
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
