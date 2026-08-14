@extends('admin.layout')
@section('title', 'クーポン作成')
@section('page-title', 'クーポン作成')
@section('page-kind', 'coupon-create')
@section('content')
@php $returnTo = $returnTo ?? null; @endphp
<div class="coupon-admin">
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    @if($returnTo === 'bulk-issue')
        <a href="{{ admin_route('admin.coupons.bulk-issue') }}">配布</a>
        <span class="breadcrumb-sep">/</span>
    @endif
    <span>新規</span>
</nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> 新規クーポン</h1>
    <p class="page-header-desc">デザインを選び、プレビューを確認しながら割引内容と発行条件を入力します。</p>
</div>
<div class="card cp-form-card">
    <div class="card-header card-header--stack">
        <div>
            <h3 class="card-title">クーポン設定</h3>
            <p class="card-desc text-muted small mb-0 mt-1">表示デザイン → クーポン情報の順で入力してください。</p>
        </div>
    </div>
    <div class="card-body">
        @if(session('error'))
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
        @endif
        <form action="{{ admin_route('admin.coupons.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(($returnTo ?? null) === 'bulk-issue')
                <input type="hidden" name="return" value="bulk-issue">
            @endif
            <section class="form-section mb-4" aria-labelledby="section-display-heading">
                <h4 id="section-display-heading" class="form-section-title"><span class="form-section-num">1</span> 表示の選択</h4>
                <p class="text-muted small mb-3">会員アプリに表示されるデザインとアクセント色を1つ選んでください。選択すると下の「配布されるクーポンの見た目」に即時反映されます。</p>
                @include('admin.coupons._ui_picker', ['selectedDesign' => old('design_template', 'classic'), 'selectedColor' => old('theme_color', '')])
            </section>
            @include('admin.coupons._preview')
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-line"></i> LINEアプリでの表示</h3>
                    <p class="card-desc text-muted small mb-0 mt-1">会員がLINEで開く「マイクーポン」画面では、上記「配布されるクーポンの見た目」のとおり表示されます。説明文・使用するボタン・バーコード/QRも反映されます。</p>
                </div>
                <div class="card-body">
                    <div class="line-app-preview-wrap">
                        <div class="line-app-mock">
                            <div class="line-app-mock-bar"><span>マイクーポン</span></div>
                            <div id="line-coupon-preview-inner" class="line-app-mock-body"></div>
                        </div>
                    </div>
                </div>
            </div>
            <section class="form-section mt-4" aria-labelledby="section-info-heading">
                <h4 id="section-info-heading" class="form-section-title"><span class="form-section-num">2</span> クーポン情報</h4>
                <p class="text-muted small mb-3">割引内容・有効期間・発行条件を設定します。名前・種別・値・有効期限はプレビューに表示されます。</p>
                <div class="form-group">
                    <label for="code" class="form-label">クーポンコード <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" required maxlength="50" placeholder="例: WELCOME10" aria-describedby="code-help">
                    <p id="code-help" class="form-text text-muted">会員が注文時に入力する英数字コード。重複のない値にしてください。</p>
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="name" class="form-label">名前</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" maxlength="255" placeholder="例: 新規会員10%オフ" aria-describedby="name-help">
                    <p id="name-help" class="form-text text-muted">会員に表示するクーポン名。プレビューに反映されます。</p>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="description" class="form-label">説明文</label>
                    <textarea name="description" id="description" class="form-control" rows="3" maxlength="2000" placeholder="例: ご来店の際、スタッフにこの画面をご提示ください。" aria-describedby="description-help">{{ old('description') }}</textarea>
                    <p id="description-help" class="form-text text-muted">会員のマイクーポン画面に表示する説明文。使い方や注意事項を入力できます。</p>
                    @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                @include('admin.partials._frontend_logo_dropzone', [
                    'logoInputName' => 'image',
                    'logoClearName' => 'image_clear',
                    'logoFieldId' => 'coupon_image',
                    'logoCurrentUrl' => (string) old('image_url', ''),
                    'logoLabel' => 'クーポン画像',
                    'logoHelp' => 'ファイルを選ぶか、下のURLを入力してください。ファイルがある場合はURLより優先されます。',
                ])
                <div class="form-group">
                    <label for="image_url" class="form-label">または 画像URL</label>
                    <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/coupon.png">
                    @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    @error('image')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="use_display" class="form-label">使用時の表示</label>
                    <select name="use_display" id="use_display" class="form-control" aria-describedby="use_display-help">
                        @foreach(\App\Models\Coupon::getUseDisplayOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('use_display', \App\Models\Coupon::USE_DISPLAY_BARCODE) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p id="use_display-help" class="form-text text-muted">会員が「使用する」を押したときに、バーコード・QRコード・またはコード番号を表示できます。「バーコード / QRコード（利用時に選択）」では、会員が表示形式を切り替えられます。レジで読み取る場合に便利です。</p>
                    @error('use_display')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="type" class="form-label">割引種別 <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-control" required aria-describedby="type-help">
                        <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>パーセント（%）</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>固定額（円）</option>
                    </select>
                    <p id="type-help" class="form-text text-muted">割引の計算方法。プレビューに「〇%オフ」「〇円オフ」と表示されます。</p>
                </div>
                <div class="form-group">
                    <label for="value" class="form-label">割引値 <span class="text-danger">*</span></label>
                    <input type="number" name="value" id="value" class="form-control" value="{{ old('value', 0) }}" min="0" step="0.01" required placeholder="例: 10 または 500" aria-describedby="value-help">
                    <p id="value-help" class="form-text text-muted">パーセントの場合は 1〜100、固定額の場合は円単位で入力。プレビューに反映されます。</p>
                    @error('value')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="min_order_amount" class="form-label">最低注文金額（円）</label>
                    <input type="number" name="min_order_amount" id="min_order_amount" class="form-control" value="{{ old('min_order_amount') }}" min="0" step="1">
                    @error('min_order_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="usage_limit" class="form-label">利用回数上限</label>
                    <input type="number" name="usage_limit" id="usage_limit" class="form-control" value="{{ old('usage_limit') }}" min="1">
                    @error('usage_limit')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="start_at" class="form-label">有効開始日時</label>
                    <input type="datetime-local" name="start_at" id="start_at" class="form-control" value="{{ old('start_at') }}">
                    @error('start_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="end_at" class="form-label">有効終了日時</label>
                    <input type="datetime-local" name="end_at" id="end_at" class="form-control" value="{{ old('end_at') }}">
                    @error('end_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="issue_trigger" class="form-label">発行条件 <span class="text-danger">*</span></label>
                    <select name="issue_trigger" id="issue_trigger" class="form-control" required>
                        @foreach(\App\Models\Coupon::getIssueTriggerOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('issue_trigger', 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">入会時・初回注文時・スタンプ達成時・誕生月は自動発行。手動の場合は会員詳細から配布してください。誕生月は日次バッチ（coupons:issue-birthday）で発行されます。</small>
                </div>
                <div class="form-group" id="stamp_card_group" style="{{ old('issue_trigger') === 'stamp_completed' ? '' : 'display:none;' }}">
                    <label for="stamp_card_id" class="form-label">対象スタンプカード <span class="text-danger">*</span></label>
                    <select name="stamp_card_id" id="stamp_card_id" class="form-control">
                        <option value="">選択してください</option>
                        @foreach($stampCards ?? [] as $card)
                        <option value="{{ $card->id }}" {{ (int) old('stamp_card_id') === (int) $card->id ? 'selected' : '' }}>{{ $card->name }}（{{ $card->required_stamps }}個で達成）</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ ((bool) old('is_active', true)) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">有効</label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>クーポンを作成</button>
                    <a href="{{ ($returnTo ?? null) === 'bulk-issue' ? admin_route('admin.coupons.bulk-issue') : admin_route('admin.coupons') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </section>
        </form>
    </div>
</div>
<script>
document.getElementById('issue_trigger').addEventListener('change', function() {
    var g = document.getElementById('stamp_card_group');
    g.style.display = this.value === 'stamp_completed' ? 'block' : 'none';
    document.getElementById('stamp_card_id').required = this.value === 'stamp_completed';
});
(function() {
    var themeColor = document.getElementById('theme_color');
    var themeDisplay = document.getElementById('theme_color_display');
    var themeCustom = document.getElementById('theme_color_custom');
    var swatches = document.querySelectorAll('.theme-swatch');
    function setColor(hex) {
        if (!themeColor) return;
        themeColor.value = hex || '';
        if (themeDisplay) themeDisplay.value = hex || '';
        if (themeCustom && hex && /^#[0-9A-Fa-f]{6}$/.test(hex)) themeCustom.value = hex;
        swatches.forEach(function(btn) {
            var selected = (btn.getAttribute('data-hex') || '') === (hex || '');
            btn.classList.toggle('is-selected', selected);
            btn.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        if (typeof updateCouponPreview === 'function') updateCouponPreview();
    }
    swatches.forEach(function(btn) {
        btn.addEventListener('click', function() {
            setColor(btn.getAttribute('data-hex') || '');
        });
    });
    if (themeCustom) themeCustom.addEventListener('input', function() {
        setColor(themeCustom.value);
    });
    if (themeDisplay) themeDisplay.addEventListener('input', function() {
        var v = themeDisplay.value.trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(v)) setColor(v);
        else if (v === '') setColor('');
    });
    document.querySelectorAll('.design-picker-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.design-picker-card').forEach(function(card) {
                card.classList.toggle('is-selected', card.contains(radio) && radio.checked);
            });
            if (typeof updateCouponPreview === 'function') updateCouponPreview();
        });
    });
})();
</script>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ config('app.asset_version') }}">
@endpush
@endsection
