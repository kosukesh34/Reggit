@extends('admin.layout')

@section('title', '基本設定')
@section('page-title', '基本設定')

@section('content')
@php
    $businessHours = $settings['businessHours'] ?? ['start' => '10:00', 'end' => '22:00'];
    $orderMode = $settings['orderMode'] ?? \App\Models\ShopSetting::ORDER_MODE_BOTH;
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>基本設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-sliders-h"></i> 基本設定</h1>
    <p class="page-header-desc">店舗全体で共通となる運用設定を管理します。予約詳細は「予約設定」に分離しています。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'basic'])

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

<form action="{{ admin_route('admin.settings.basic.update') }}" method="POST" id="settingsBasicForm">
    @csrf
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock"></i> 店舗運用</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">開始時間</label>
                <input type="time" name="business_hours_start" class="form-control" value="{{ $businessHours['start'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">終了時間</label>
                <input type="time" name="business_hours_end" class="form-control" value="{{ $businessHours['end'] }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">注文モード</label>
                <select name="order_mode" class="form-control" required>
                    <option value="both" {{ $orderMode === 'both' ? 'selected' : '' }}>両方（モバイル注文 + 店内注文）</option>
                    <option value="mobile_only" {{ $orderMode === 'mobile_only' ? 'selected' : '' }}>モバイル注文のみ（注文時決済）</option>
                    <option value="in_store_only" {{ $orderMode === 'in_store_only' ? 'selected' : '' }}>店内注文のみ（後で決済）</option>
                </select>
                <small class="form-text">LIFFのカート内ボタン表示と注文可否に反映されます。</small>
            </div>
            <div class="quick-menu-section" style="margin-top:12px;">
                <h3 class="quick-menu-title"><i class="fas fa-compass"></i> 機能別の設定へ</h3>
                <p class="quick-menu-desc">重複を避けるため、各機能の詳細設定は専用画面に分けています。</p>
                <div class="quick-menu-grid">
                    <a href="{{ admin_route('admin.settings.reservations') }}" class="quick-menu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>予約設定</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.queue') }}" class="quick-menu-item">
                        <i class="fas fa-list-ol"></i>
                        <span>順番待ち設定</span>
                    </a>
                    <a href="{{ admin_route('admin.settings.home') }}" class="quick-menu-item">
                        <i class="fas fa-home"></i>
                        <span>ホーム設定</span>
                    </a>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                <a href="{{ admin_route('admin.settings.advanced') }}" class="btn btn-secondary">詳細設定（配色）へ</a>
            </div>
        </div>
    </div>
</form>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-toggle-on"></i> 利用機能</h2>
    </div>
    <div class="card-body">
        <p class="form-text" style="margin-bottom:12px;">LINE アプリで利用できる機能の ON / OFF を切り替えます。</p>
        <form action="{{ admin_route('admin.settings.feature-flags.update') }}" method="POST">
            @csrf
            <div class="feature-flags-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px 16px;">
                @foreach(\App\Models\ShopSetting::FEATURE_FLAG_LABELS as $key => $label)
                <label class="checkbox-label">
                    <input type="checkbox" name="feature_flags[{{ $key }}]" value="1" {{ ($featureFlags[$key] ?? false) ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
            <div class="form-actions" style="margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 利用機能を保存</button>
            </div>
        </form>
    </div>
</div>
@endsection
