@extends('admin.layout')

@section('title', 'システム管理')
@section('page-title', 'システム管理')

@section('content')
<div class="system-admin-welcome">
    <p class="system-admin-welcome-text">プロジェクト・アカウント・API・監査ログを一元管理できます。</p>
</div>

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
    <a href="{{ route('system-admin.api-tokens.index') }}" class="stat-card stat-card--link stat-card-secondary">
        <div class="stat-icon"><i class="fas fa-key"></i></div>
        <div class="stat-content">
            <div class="stat-label">APIトークン</div>
            <div class="stat-value">{{ $view->apiTokensCount ?? 0 }}</div>
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

<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bolt"></i> クイックアクション</h3>
    </div>
    <div class="card-body">
        <div class="quick-actions-grid">
            <a href="{{ route('system-admin.projects.input-home') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus"></i> プロジェクト入力ホーム
            </a>
            <a href="{{ route('system-admin.accounts.create') }}" class="btn btn-info btn-lg">
                <i class="fas fa-user-plus"></i> 新規アカウント
            </a>
            <a href="{{ route('system-admin.api-tokens.create') }}" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-key"></i> APIトークン発行
            </a>
            <a href="{{ route('system-admin.shop-app-ui-research') }}" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-lightbulb"></i> 店舗アプリUI学習データ
            </a>
        </div>
    </div>
</div>
@endsection
