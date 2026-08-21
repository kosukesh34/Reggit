@extends('admin.layout')

@section('title', '設定')
@section('page-title', '設定')

@section('content')
@php
    $adminUser = auth('admin')->user();
    $can = function (string $permission) use ($adminUser): bool {
        return $adminUser === null || $adminUser->hasPermission($permission);
    };
    $plan = isset($currentProject) && $currentProject !== null
        ? (string) ($currentProject->plan ?? \App\Enums\ProjectPlan::NORMAL)
        : \App\Enums\ProjectPlan::NORMAL;
    $hasPlan = function (string $feature) use ($plan): bool {
        return \App\Support\PlanFeatures::hasFeature($plan, $feature);
    };
    $sections = [
        [
            'title' => '店舗アプリ',
            'items' => [
                $can('settings.view') ? ['href' => admin_route('admin.settings.home'), 'icon' => 'fas fa-home', 'label' => 'ホーム設定', 'desc' => 'LIFFのタブ表示・並び・ラベル'] : null,
                $can('settings.view') ? ['href' => admin_route('admin.settings.liff-connection'), 'icon' => 'fab fa-line', 'label' => 'LIFF設定', 'desc' => 'LIFF ID と Messaging API'] : null,
                ($can('liff_designer.manage') && $hasPlan(\App\Support\PlanFeatures::LIFF_DESIGNER_ADVANCED)) ? ['href' => admin_route('admin.settings.liff-designer'), 'icon' => 'fas fa-paint-brush', 'label' => 'LIFFデザイン', 'desc' => 'お客様向け画面の見た目'] : null,
                $can('settings.view') ? ['href' => admin_route('admin.settings.advanced'), 'icon' => 'fas fa-palette', 'label' => '詳細設定（配色）', 'desc' => '成功色・注意色'] : null,
            ],
        ],
        [
            'title' => '店舗',
            'items' => [
                $can('settings.view') ? ['href' => admin_route('admin.settings.basic'), 'icon' => 'fas fa-sliders-h', 'label' => '基本設定', 'desc' => '営業時間・注文モード'] : null,
                $can('settings.view') ? ['href' => admin_route('admin.settings.features'), 'icon' => 'fas fa-toggle-on', 'label' => '機能設定', 'desc' => '機能のON/OFFと管理画面の色'] : null,
                $can('settings.view') ? ['href' => admin_route('admin.settings.smaregi'), 'icon' => 'fas fa-plug', 'label' => 'スマレジ連携', 'desc' => '契約IDの紐づけと商品同期'] : null,
            ],
        ],
        [
            'title' => '会員',
            'items' => [
                ($can('members.view') && $can('settings.view')) ? ['href' => admin_route('admin.settings.ranks'), 'icon' => 'fas fa-crown', 'label' => 'ランク設定', 'desc' => 'ランク判定・ポイント・会員証デザイン'] : null,
                ($can('members.view') && $can('settings.view')) ? ['href' => admin_route('admin.settings.profile'), 'icon' => 'fas fa-user-cog', 'label' => 'プロフィール設定', 'desc' => '登録項目の表示と必須'] : null,
                ($can('settings.view') && $hasPlan(\App\Support\PlanFeatures::WALLET)) ? ['href' => admin_route('admin.settings.wallet'), 'icon' => 'fas fa-wallet', 'label' => 'Wallet画像', 'desc' => 'Apple / Google Wallet の画像'] : null,
            ],
        ],
        [
            'title' => '機能ごとの設定',
            'items' => [
                $can('reservations.view') ? ['href' => admin_route('admin.settings.reservations'), 'icon' => 'fas fa-calendar-check', 'label' => '予約設定', 'desc' => '時間帯・休業日・予約枠'] : null,
                $can('queue.view') ? ['href' => admin_route('admin.settings.queue'), 'icon' => 'fas fa-list-ol', 'label' => '順番待ち設定', 'desc' => '待ち時間の目安'] : null,
                $can('coupons.view') ? ['href' => admin_route('admin.settings.coupons'), 'icon' => 'fas fa-ticket-alt', 'label' => 'クーポン設定', 'desc' => 'クーポン機能とデフォルト画像'] : null,
                ($can('coupons.view') && $hasPlan(\App\Support\PlanFeatures::LOTTERY)) ? ['href' => admin_route('admin.settings.lottery'), 'icon' => 'fas fa-dice', 'label' => 'ガチャ・スクラッチ', 'desc' => '権利の付与と景品確率'] : null,
                $can('announcements.view') ? ['href' => admin_route('admin.settings.announcements'), 'icon' => 'fas fa-bullhorn', 'label' => 'お知らせの設定', 'desc' => 'お知らせ機能のON/OFF'] : null,
                $can('gallery.view') ? ['href' => admin_route('admin.settings.gallery'), 'icon' => 'fas fa-images', 'label' => 'ギャラリー設定', 'desc' => 'ギャラリー機能のON/OFF'] : null,
            ],
        ],
        [
            'title' => 'セキュリティ',
            'items' => [
                $can('audit_logs.view') ? ['href' => admin_route('admin.audit-logs.index'), 'icon' => 'fas fa-clipboard-list', 'label' => '監査ログ', 'desc' => '操作履歴の確認'] : null,
            ],
        ],
    ];
@endphp

<p class="page-header-desc settings-hub-lead">よく使う画面は左のメニュー、設定はここにまとめています。</p>

@foreach($sections as $section)
    @php
        $items = array_values(array_filter($section['items']));
    @endphp
    @if($items !== [])
    <section class="settings-hub-section">
        <h2 class="settings-hub-heading">{{ $section['title'] }}</h2>
        <div class="settings-hub-grid">
            @foreach($items as $item)
            <a href="{{ $item['href'] }}" class="settings-hub-card">
                <span class="settings-hub-icon" aria-hidden="true"><i class="{{ $item['icon'] }}"></i></span>
                <span class="settings-hub-copy">
                    <strong>{{ $item['label'] }}</strong>
                    <span>{{ $item['desc'] }}</span>
                </span>
            </a>
            @endforeach
        </div>
    </section>
    @endif
@endforeach
@endsection
