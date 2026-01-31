@extends('admin.layout')

@section('title', '詳細設定（配色）')
@section('page-title', '詳細設定（配色）')

@section('content')
@php $lineTheme = $settings['lineTheme'] ?? []; @endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>詳細設定（配色）</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-palette"></i> 配色</h1>
    <p class="page-header-desc">LINEアプリでお客様に表示するボタンや強調色を変更します</p>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

<form action="{{ route('admin.settings.advanced.update') }}" method="POST" id="settingsAdvancedForm">
    @csrf
    <div class="card">
        <div class="card-body">
            <p class="form-section-desc">未設定の項目はデフォルトの色が使われます。</p>
            <div class="line-theme-grid">
                <div class="form-group">
                    <label class="form-label">メイン色</label>
                    <div class="color-input-row">
                        <input type="color" id="line_primary_color" class="color-picker" value="{{ $lineTheme['primary'] ?? '#000000' }}" aria-label="メイン色">
                        <input type="text" name="line_primary_color" class="form-control color-hex" value="{{ $lineTheme['primary'] ?? '#000000' }}" maxlength="7">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">メイン色（濃いめ）</label>
                    <div class="color-input-row">
                        <input type="color" id="line_primary_dark" class="color-picker" value="{{ $lineTheme['primary_dark'] ?? '#333333' }}" aria-label="メイン色（濃いめ）">
                        <input type="text" name="line_primary_dark" class="form-control color-hex" value="{{ $lineTheme['primary_dark'] ?? '#333333' }}" maxlength="7">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">成功・ポイント色</label>
                    <div class="color-input-row">
                        <input type="color" id="line_success_color" class="color-picker" value="{{ $lineTheme['success'] ?? '#000000' }}" aria-label="成功色">
                        <input type="text" name="line_success_color" class="form-control color-hex" value="{{ $lineTheme['success'] ?? '#000000' }}" maxlength="7">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">注意・キャンセル色</label>
                    <div class="color-input-row">
                        <input type="color" id="line_danger_color" class="color-picker" value="{{ $lineTheme['danger'] ?? '#dc3545' }}" aria-label="注意色">
                        <input type="text" name="line_danger_color" class="form-control color-hex" value="{{ $lineTheme['danger'] ?? '#dc3545' }}" maxlength="7">
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
                <a href="{{ route('admin.settings.basic') }}" class="btn btn-secondary">基本設定へ</a>
            </div>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('.color-picker').forEach(function(p) {
    var h = p.closest('.color-input-row').querySelector('.color-hex');
    p.addEventListener('input', function() { h.value = p.value; });
});
document.querySelectorAll('.color-hex').forEach(function(h) {
    var p = h.closest('.color-input-row').querySelector('.color-picker');
    h.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(h.value)) p.value = h.value;
    });
});
</script>
@endsection
