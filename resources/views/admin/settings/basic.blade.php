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

@if(!auth('admin')->user()->isStaff())
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-tablet-alt"></i> 店舗端末の登録</h2>
    </div>
    <div class="card-body">
        <p>Kitchen／Waiting は店舗ごとに各1台だけ登録できます。コードは一度だけ使用でき、再発行すると現在の端末は即時ログアウトされます。</p>
        @if(session('store_app_enrollment'))
            <div class="alert alert-warning" role="alert">
                <strong>{{ session('store_app_enrollment.app') === 'kitchen' ? 'Kitchen' : 'Waiting' }} 登録コード（この画面でのみ表示）</strong>
                <div style="font-size:1.35rem;font-weight:700;letter-spacing:.08em;margin-top:.5rem;word-break:break-all">{{ session('store_app_enrollment.token') }}</div>
                <small>対象iPadへ入力後、このコードは再利用できません。</small>
            </div>
        @endif
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem">
            @foreach(['kitchen' => 'Kitchen', 'waiting' => 'Waiting'] as $app => $label)
                @php($credential = $storeAppCredentials->get($app))
                <div class="card" style="margin:0">
                    <div class="card-body">
                        <h3>{{ $label }}</h3>
                        <p>{{ $credential?->claimed_at ? '登録済み: '.$credential->device_name : ($credential?->enrollment_token_hash ? '登録待ち' : '未登録') }}</p>
                        <form action="{{ admin_route('admin.settings.store-app-credential.store') }}" method="POST" onsubmit="return confirm('現在の端末認証を失効し、新しい登録コードを発行します。よろしいですか？')">
                            @csrf
                            <input type="hidden" name="app" value="{{ $app }}">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> {{ $credential ? '登録コードを再発行' : '登録コードを発行' }}</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
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
