@extends('admin.layout')

@section('title', '基本設定')
@section('page-title', '基本設定')

@section('content')
@php
    $businessHours = $settings['businessHours'] ?? ['start' => '10:00', 'end' => '22:00'];
    $orderMode = $settings['orderMode'] ?? \App\Domain\Settings\ShopSettingKeys::ORDER_MODE_BOTH;
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>基本設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-sliders-h"></i> 基本設定</h1>
</div>


@if(auth('admin')->user()?->hasPermission('settings.manage'))
<div class="card">
    <div class="card-header"><h2 class="card-title">受付・キッチン端末の認証</h2></div>
    <div class="card-body">
        <label for="storeDeviceToken" class="form-label">店舗端末トークン</label>
        <input id="storeDeviceToken" type="text" class="form-control" readonly autocomplete="off" value="{{ $settings['storeDeviceToken'] ?? '' }}">
        <p class="form-text">この値を受付・キッチンアプリの設定に入力してください。再発行すると、接続中のすべての端末で再設定が必要です。</p>
        <form action="{{ admin_route('admin.settings.kitchen-token.regenerate') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary">端末トークンを発行・再発行</button>
        </form>
    </div>
</div>
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
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="order_line_notify_enabled" value="1" {{ ($settings['orderLineNotifyEnabled'] ?? true) ? 'checked' : '' }}>
                    <span>モバイル注文確定時に顧客へ LINE 通知する</span>
                </label>
                <small class="form-text">引換番号付きの注文完了メッセージを顧客の LINE に送信します。</small>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="order_ready_line_notify_enabled" value="1" {{ ($settings['orderReadyLineNotifyEnabled'] ?? true) ? 'checked' : '' }}>
                    <span>受け取り可能になったときに顧客へ LINE 通知する</span>
                </label>
                <small class="form-text">キッチン／管理画面で「受取可」に更新したとき、顧客の LINE に通知します。</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
            </div>
        </div>
    </div>
</form>

@endsection
