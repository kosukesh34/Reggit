@extends('admin.layout')

@section('title', 'スタンプ付与QR')
@section('page-title', 'スタンプ付与QR')

@push('styles')
<style>
    .stamp-grant-form { margin-bottom: 24px; }
    .stamp-grant-settings { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .stamp-grant-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .stamp-grant-list { display: grid; gap: 20px; }
    .stamp-grant-item { display: grid; grid-template-columns: minmax(220px, 280px) minmax(0, 1fr); gap: 20px; align-items: start; }
    .stamp-grant-qr { display: grid; place-items: center; min-height: 240px; padding: 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; }
    .stamp-grant-qr canvas { width: min(100%, 240px) !important; height: auto !important; }
    .stamp-grant-url { width: 100%; min-height: 72px; resize: vertical; word-break: break-all; }
    .stamp-grant-note { margin-top: 12px; padding: 12px; color: #475569; background: #f8fafc; border-radius: 10px; line-height: 1.65; }
    .stamp-grant-empty { padding: 24px; color: #64748b; }
    @media (max-width: 800px) {
        .stamp-grant-settings { grid-template-columns: 1fr; }
        .stamp-grant-item { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>付与QR</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-qrcode"></i> スタンプ付与QR</h1>
    <p class="page-header-desc">{{ $stampCard->name }}の付与QRを発行します。発行済みのQRは下に残ります。</p>
</div>

<form method="POST" action="{{ admin_route('admin.stamps.grant-qr.store', ['id' => $stampCard->id]) }}" class="card stamp-grant-form">
    @csrf
    <div class="card-header"><h2 class="card-title">新しいQRを発行</h2></div>
    <div class="card-body">
        <div class="stamp-grant-settings">
            <div class="form-group">
                <label for="expires_minutes" class="form-label">QRの有効期限</label>
                <select name="expires_minutes" id="expires_minutes" class="form-control">
                    @foreach(\App\Enums\StampGrantLimit::EXPIRES_OPTIONS as $minutes => $label)
                        <option value="{{ $minutes }}" {{ (int) $expiresMinutes === (int) $minutes ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="amount" class="form-label">付与する個数</label>
                <input type="number" name="amount" id="amount" class="form-control" min="1" max="{{ \App\Enums\StampGrantLimit::MAX_AMOUNT }}" value="{{ $amount }}" required>
            </div>
            <div class="form-group">
                <label for="limit" class="form-label">利用上限</label>
                <select name="limit" id="limit" class="form-control">
                    @foreach(\App\Enums\StampGrantLimit::getValues() as $value => $label)
                        <option value="{{ $value }}" {{ $limit === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="stamp-grant-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> QRを発行</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><h2 class="card-title">発行済みQR</h2></div>
    <div class="card-body">
        @if($codes->isEmpty())
            <p class="stamp-grant-empty">まだQRはありません。上の条件で発行してください。</p>
        @else
            <div class="stamp-grant-list">
                @foreach($codes as $code)
                    @php
                        $openUrl = $code->openUrl();
                        $limitNote = \App\Enums\StampGrantLimit::getLabel($code->usage_limit);
                        $expiresNote = $code->isExpired()
                            ? '期限切れ'
                            : ($code->expires_at === null
                                ? '無期限'
                                : $code->expires_at->format('Y/m/d H:i').'まで');
                    @endphp
                    <div class="stamp-grant-item">
                        <div class="stamp-grant-qr" data-stamp-grant-qr data-url="{{ $openUrl }}" aria-label="スタンプ付与QRコード"></div>
                        <div>
                            <label class="form-label" for="stampGrantUrl{{ $code->id }}">読み取り用URL</label>
                            <textarea id="stampGrantUrl{{ $code->id }}" class="form-control stamp-grant-url" readonly>{{ $openUrl }}</textarea>
                            <div class="stamp-grant-actions">
                                <button type="button" class="btn btn-primary" data-copy-stamp-grant-url data-copy-target="stampGrantUrl{{ $code->id }}"><i class="fas fa-copy"></i> URLをコピー</button>
                            </div>
                            <p class="stamp-grant-note">{{ $expiresNote }} / 1回 {{ $code->amount }} 個 / {{ $limitNote }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
