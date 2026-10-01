@extends('admin.layout')

@section('title', 'システム管理')
@section('page-title', 'システム管理')

@section('content')
<div class="settings-overview"><span class="settings-eyebrow">SYSTEM ADMINISTRATION</span><h1>システム管理</h1><p>契約・アカウントと、全店舗に共通する案内を管理します。</p></div>

<div class="stats-grid stats-grid--system">
    <a href="{{ route('system-admin.projects.index') }}" class="stat-card stat-card--link stat-card-primary">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div class="stat-content">
            <div class="stat-label">プロジェクト</div>
            <div class="stat-value">{{ $view->projectsCount ?? 0 }}</div>
            <div class="stat-link">一覧へ <i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
    <a href="{{ route('system-admin.accounts.index') }}" class="stat-card stat-card--link stat-card-info">
        <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
        <div class="stat-content">
            <div class="stat-label">アカウント</div>
            <div class="stat-value">{{ $view->accountsCount ?? 0 }}</div>
            <div class="stat-link">一覧へ <i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
    <a href="{{ route('system-admin.audit-logs.index') }}" class="stat-card stat-card--link stat-card-warning">
        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-content">
            <div class="stat-label">監査ログ</div>
            <div class="stat-value">—</div>
            <div class="stat-link">確認 <i class="fas fa-chevron-right"></i></div>
        </div>
    </a>
</div>

<div class="settings-hub">
    <section class="settings-hub-section">
        <h2 class="settings-hub-heading">契約・アカウント</h2>
        <div class="settings-hub-grid">
            @foreach([
                ['system-admin.projects.index', 'fas fa-building', 'プロジェクト・契約', 'プラン、利用店舗数、利用状態、スマレジ接続'],
                ['system-admin.accounts.index', 'fas fa-user-shield', '管理者アカウント', '管理者の追加、所属プロジェクト、権限'],
                ['system-admin.projects.input-home', 'fas fa-plus', '新規プロジェクト', '店舗の導入に必要な初期設定'],
            ] as [$route, $icon, $label, $hint])
                <a href="{{ route($route) }}" class="settings-hub-card"><span class="settings-hub-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span><span class="settings-hub-copy"><strong>{{ $label }}</strong><span>{{ $hint }}</span></span><i class="fas fa-chevron-right settings-hub-arrow" aria-hidden="true"></i></a>
            @endforeach
        </div>
    </section>
    <section class="settings-hub-section">
        <h2 class="settings-hub-heading">全店舗への案内・監査</h2>
        <div class="settings-hub-grid">
            @foreach([
                ['system-admin.notices.index', 'fas fa-bullhorn', '店舗向けのお知らせ', 'システムの更新や運用に関する案内'],
                ['system-admin.help-articles.index', 'fas fa-circle-question', 'ヘルプ記事', '店舗管理者向けの操作ガイド'],
                ['system-admin.audit-logs.index', 'fas fa-clipboard-list', '操作履歴', '管理者の操作と変更履歴を確認'],
            ] as [$route, $icon, $label, $hint])
                <a href="{{ route($route) }}" class="settings-hub-card"><span class="settings-hub-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span><span class="settings-hub-copy"><strong>{{ $label }}</strong><span>{{ $hint }}</span></span><i class="fas fa-chevron-right settings-hub-arrow" aria-hidden="true"></i></a>
            @endforeach
        </div>
    </section>
</div>
@endsection
