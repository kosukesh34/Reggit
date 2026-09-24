@extends('admin.layout')

@section('title', '詳細設定（配色）')
@section('page-title', '詳細設定（配色）')

@section('content')
@php $lineTheme = $settings['lineTheme'] ?? []; @endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>詳細設定（配色）</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-palette"></i> 配色</h1>
</div>


<form action="{{ admin_route('admin.settings.advanced.update') }}" method="POST" id="settingsAdvancedForm">
    @csrf
    <div class="card">
        <div class="card-body">
            <p class="form-section-desc">未設定の項目はデフォルトの色が使われます。</p>
            <div class="line-theme-grid">
                <div class="form-group">
                    <label class="form-label">メイン色（参照）</label>
                    <div class="color-input-row">
                        <input type="color" class="color-picker" value="{{ $lineTheme['primary'] ?? '#000000' }}" disabled aria-label="メイン色（参照）">
                        <input type="text" class="form-control color-hex" value="{{ $lineTheme['primary'] ?? '#000000' }}" maxlength="7" readonly>
                    </div>
                    <p class="form-text text-muted">ブランド色は <a href="{{ admin_route('admin.settings.liff-designer') }}">LIFFデザイン</a> のテーマ／アクセント色で変更します。デザイナー未設定時のみこの値が使われます。</p>
                </div>
                <div class="form-group">
                    <label class="form-label">メイン色（濃いめ・参照）</label>
                    <div class="color-input-row">
                        <input type="color" class="color-picker" value="{{ $lineTheme['primary_dark'] ?? '#333333' }}" disabled aria-label="メイン色（濃いめ・参照）">
                        <input type="text" class="form-control color-hex" value="{{ $lineTheme['primary_dark'] ?? '#333333' }}" maxlength="7" readonly>
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
            </div>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('.color-picker:not([disabled])').forEach(function(p) {
    const h = p.closest('.color-input-row').querySelector('.color-hex');
    p.addEventListener('input', function() { h.value = p.value; });
});
document.querySelectorAll('.color-hex:not([readonly])').forEach(function(h) {
    const p = h.closest('.color-input-row').querySelector('.color-picker');
    h.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(h.value)) p.value = h.value;
    });
});
</script>
@endsection
