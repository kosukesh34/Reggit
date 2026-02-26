@php
    $designOptions = \App\Models\Coupon::getDesignTemplateOptions();
    $colorPresets = \App\Models\Coupon::getThemeColorPresets();
    $selectedDesign = $selectedDesign ?? old('design_template', 'classic');
    $selectedColor = $selectedColor ?? old('theme_color', '');
    $designIcons = [
        'classic' => 'fa-border-all',
        'modern' => 'fa-mobile-screen',
        'minimal' => 'fa-minimize',
        'gradient' => 'fa-fill-drip',
        'card' => 'fa-id-card',
        'barcode' => 'fa-barcode',
        'qr' => 'fa-qrcode',
        'stripe' => 'fa-grip-lines',
        'ticket' => 'fa-ticket',
        'vintage' => 'fa-scroll',
        'neon' => 'fa-lightbulb',
    ];
@endphp
<div class="form-group">
    <span class="form-label d-block mb-2">表示デザイン</span>
    <p class="text-muted small mb-2">会員アプリでのクーポン表示スタイルを1つ選んでください。</p>
    <div class="design-picker-grid" role="group" aria-label="表示デザインの選択">
        @foreach($designOptions as $value => $label)
            <label class="design-picker-card {{ $selectedDesign === $value ? 'is-selected' : '' }}">
                <input type="radio" name="design_template" value="{{ $value }}" {{ $selectedDesign === $value ? 'checked' : '' }} class="sr-only design-picker-radio">
                <span class="design-picker-icon"><i class="fas {{ $designIcons[$value] ?? 'fa-ticket-alt' }}"></i></span>
                <span class="design-picker-label">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('design_template')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <span class="form-label d-block mb-2">アクセント色</span>
    <p class="text-muted small mb-2">クーポンカードのアクセント色を選ぶか、デフォルトのままにします。</p>
    <div class="theme-swatch-wrap">
        <input type="hidden" name="theme_color" id="theme_color" value="{{ $selectedColor }}" maxlength="7">
        <div class="theme-swatch-list" role="group" aria-label="アクセント色の選択">
            @foreach($colorPresets as $hex => $label)
                @if($hex === '')
                    <button type="button" class="theme-swatch theme-swatch-default {{ $selectedColor === '' ? 'is-selected' : '' }}" data-hex="" title="{{ $label }}" aria-pressed="{{ $selectedColor === '' ? 'true' : 'false' }}">
                        <span class="theme-swatch-inner" aria-hidden="true">—</span>
                    </button>
                @else
                    <button type="button" class="theme-swatch {{ $selectedColor === $hex ? 'is-selected' : '' }}" data-hex="{{ $hex }}" style="--swatch-color: {{ $hex }}" title="{{ $label }}" aria-pressed="{{ $selectedColor === $hex ? 'true' : 'false' }}">
                        <span class="theme-swatch-inner"></span>
                    </button>
                @endif
            @endforeach
        </div>
        <div class="theme-swatch-custom mt-2">
            <label class="d-inline-flex align-items-center gap-2">
                <span class="text-muted small">カスタム色</span>
                <input type="color" id="theme_color_custom" value="{{ $selectedColor && preg_match('/^#[0-9A-Fa-f]{6}$/', $selectedColor) ? $selectedColor : '#059669' }}" title="カスタム色" class="theme-color-input" aria-label="カスタム色を選択">
                <input type="text" id="theme_color_display" class="form-control form-control-sm" placeholder="#059669" maxlength="7" style="width: 90px;" value="{{ $selectedColor }}" aria-label="色コード">
            </label>
        </div>
    </div>
    @error('theme_color')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>
