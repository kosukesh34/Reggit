@extends('admin.layout')

@section('title', 'スタンプ付与QR')
@section('page-title', 'スタンプ付与QR')

@php
    $limitNote = \App\Enums\StampGrantLimit::getLabel($limit);
@endphp

@push('styles')
<style>
    .stamp-grant-layout { display: grid; grid-template-columns: minmax(280px, 420px) minmax(0, 1fr); gap: 24px; align-items: start; }
    .stamp-grant-qr { display: grid; place-items: center; min-height: 360px; padding: 24px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; }
    .stamp-grant-qr canvas { width: min(100%, 320px) !important; height: auto !important; }
    .stamp-grant-url { width: 100%; min-height: 112px; resize: vertical; word-break: break-all; }
    .stamp-grant-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
    .stamp-grant-note { margin-top: 16px; padding: 14px; color: #475569; background: #f8fafc; border-radius: 10px; line-height: 1.65; }
    .stamp-grant-settings { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .stamp-grant-form { margin-bottom: 24px; }
    @media (max-width: 800px) {
        .stamp-grant-layout { grid-template-columns: 1fr; }
        .stamp-grant-settings { grid-template-columns: 1fr; }
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
    <p class="page-header-desc">{{ $stampCard->name }}を、読み取り1回あたり {{ $amount }} 個付与します</p>
</div>

<form method="GET" action="{{ admin_route('admin.stamps.grant-qr', ['id' => $stampCard->id]) }}" class="card stamp-grant-form">
    <div class="card-header"><h2 class="card-title">付与条件</h2></div>
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-rotate"></i> この条件でQRを発行</button>
        </div>
    </div>
</form>

<div class="stamp-grant-layout">
    <div class="stamp-grant-qr" data-stamp-grant-qr data-url="{{ $stampUrl }}" aria-label="スタンプ付与QRコード"></div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">付与URL</h2></div>
        <div class="card-body">
            <label for="stampGrantUrl" class="form-label">お客様のスタンプ画面で読み取るURL</label>
            <textarea id="stampGrantUrl" class="form-control stamp-grant-url" readonly>{{ $stampUrl }}</textarea>
            <div class="stamp-grant-actions">
                <button type="button" class="btn btn-primary" data-copy-stamp-grant-url><i class="fas fa-copy"></i> URLをコピー</button>
            </div>
            <p class="stamp-grant-note"><strong>有効期限：{{ $expiresAt->format('Y/m/d H:i') }}</strong><br>1回の読み取りで {{ $amount }} 個付与します。{{ $limitNote }}。期限が切れたら条件を変えて発行し直してください。</p>
        </div>
    </div>
</div>
@endsection
