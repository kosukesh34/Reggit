@extends('admin.layout')

@section('title', 'アプリデザイン')
@section('page-title', 'アプリデザイン')

@section('content')
@php $lineTheme = $settings['lineTheme'] ?? []; @endphp

@include('admin.settings._app-design-navigation', ['activeDesign' => 'colors'])




<form action="{{ admin_route('admin.settings.advanced.update') }}" method="POST" id="settingsAdvancedForm">
    @csrf
    @include('admin.settings._section-tabs', ['tabsLabel' => 'アプリの配色', 'sectionTabs' => ['colors-chrome' => 'ヘッダー・フッター', 'colors-buttons' => 'ボタン', 'colors-status' => 'LINEの状態表示']])
    <section id="colors-status">
    <div class="card">
        <div class="card-body">
            <p class="form-section-desc">未設定の項目はデフォルトの色が使われます。</p>
            <div class="line-theme-grid">
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
            <p class="form-text">上記はLINEアプリの通知・状態表示の色です。</p>

        </div>
    </div>
    </section>
    <section id="colors-chrome"><div class="card"><div class="card-header"><h2 class="card-title">ヘッダー・フッターの配色</h2></div><div class="card-body">
            <div class="form-group">
                <label for="navigation_header_background_color" class="form-label">ヘッダーの背景</label>
                <div class="color-input-row">
                    <input type="color" id="navigation_header_background_color_picker" value="{{ old('navigation_header_background_color', $navigationHeaderBackgroundColor ?? '#FFFFFF') }}" class="color-picker">
                    <input type="text" class="form-control color-hex" id="navigation_header_background_color" name="navigation_header_background_color" value="{{ old('navigation_header_background_color', $navigationHeaderBackgroundColor ?? '#FFFFFF') }}" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
            </div>
    </div>
<div class="card-body"><h3 class="card-title" style="margin-bottom:16px">ヘッダーのアイコン</h3><div class="chrome-color-grid"><div class="form-group"><label for="chrome-header_icon" class="form-label">ヘッダーボタンの文字・アイコン</label><input id="chrome-header_icon" type="color" name="native_chrome_colors[header_icon]" value="{{ old('native_chrome_colors.header_icon', $nativeChromeColors['header_icon'] ?? '#292524') }}" style="width:64px;height:44px"></div></div></div><div class="card-body"><h3 class="card-title" style="margin-bottom:16px">フッターの背景・アイコン</h3><div class="chrome-color-grid"><div class="form-group"><label for="chrome-footer_background" class="form-label">フッター全体の背景</label><input id="chrome-footer_background" type="color" name="native_chrome_colors[footer_background]" value="{{ old('native_chrome_colors.footer_background', $nativeChromeColors['footer_background'] ?? '#FFFFFF') }}" style="width:64px;height:44px"></div><div class="form-group"><label for="chrome-footer_icon" class="form-label">フッターボタンの文字・アイコン</label><input id="chrome-footer_icon" type="color" name="native_chrome_colors[footer_icon]" value="{{ old('native_chrome_colors.footer_icon', $nativeChromeColors['footer_icon'] ?? '#78716C') }}" style="width:64px;height:44px"></div><div class="form-group"><label for="chrome-footer_active_icon" class="form-label">フッター選択中の文字・アイコン</label><input id="chrome-footer_active_icon" type="color" name="native_chrome_colors[footer_active_icon]" value="{{ old('native_chrome_colors.footer_active_icon', $nativeChromeColors['footer_active_icon'] ?? '#173F35') }}" style="width:64px;height:44px"></div><div class="form-group"><label for="chrome-footer_active_background" class="form-label">フッター選択中の背景</label><input id="chrome-footer_active_background" type="color" name="native_chrome_colors[footer_active_background]" value="{{ old('native_chrome_colors.footer_active_background', $nativeChromeColors['footer_active_background'] ?? '#EAF2EE') }}" style="width:64px;height:44px"></div></div></div>    </div></section>
    <section id="colors-buttons">
<div class="card"><div class="card-header"><h2 class="card-title">アプリ内のボタン</h2></div><div class="card-body"><p class="section-help">保存・予約・商品追加などの主要ボタンと、キャンセルなどの補助ボタンに反映します。メニューや会員ランクの色は変更しません。</p><div class="chrome-color-grid"><div class="form-group"><label class="form-label" for="chrome-primary_button_background">主要ボタンの背景</label><input id="chrome-primary_button_background" type="color" name="native_chrome_colors[primary_button_background]" value="{{ old('native_chrome_colors.primary_button_background', $nativeChromeColors['primary_button_background'] ?? '#173F35') }}" style="width:64px;height:44px"></div><div class="form-group"><label class="form-label" for="chrome-primary_button_text">主要ボタンの文字・アイコン</label><input id="chrome-primary_button_text" type="color" name="native_chrome_colors[primary_button_text]" value="{{ old('native_chrome_colors.primary_button_text', $nativeChromeColors['primary_button_text'] ?? '#FFFFFF') }}" style="width:64px;height:44px"></div><div class="form-group"><label class="form-label" for="chrome-secondary_button_background">補助ボタンの背景</label><input id="chrome-secondary_button_background" type="color" name="native_chrome_colors[secondary_button_background]" value="{{ old('native_chrome_colors.secondary_button_background', $nativeChromeColors['secondary_button_background'] ?? '#EDF1ED') }}" style="width:64px;height:44px"></div><div class="form-group"><label class="form-label" for="chrome-secondary_button_text">補助ボタンの文字・アイコン</label><input id="chrome-secondary_button_text" type="color" name="native_chrome_colors[secondary_button_text]" value="{{ old('native_chrome_colors.secondary_button_text', $nativeChromeColors['secondary_button_text'] ?? '#17211C') }}" style="width:64px;height:44px"></div></div></div></div>    </section>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
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
