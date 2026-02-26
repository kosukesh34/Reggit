@extends('admin.layout')
@section('title', 'クーポン作成')
@section('page-title', 'クーポン作成')
@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.coupons') }}">クーポン</a><span class="breadcrumb-sep">/</span><span>新規</span></nav>
<div class="card">
    <div class="card-header card-header--stack">
        <div>
            <h3 class="card-title"><i class="fas fa-ticket-alt"></i> 新規クーポン</h3>
            <p class="card-desc text-muted small mb-0 mt-1">会員に配布するクーポンを作成します。表示デザインと内容を設定してください。</p>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.coupons.store') }}" method="POST">
            @csrf
            <section class="form-section mb-4">
                <h4 class="form-section-title"><span class="form-section-num">1</span> 表示の選択</h4>
                <p class="text-muted small mb-3">会員アプリで表示されるクーポンのデザインとアクセント色を選びます。選択後、下のプレビューで見た目を確認できます。</p>
                @include('admin.coupons._ui_picker', ['selectedDesign' => old('design_template', 'classic'), 'selectedColor' => old('theme_color', '')])
            </section>
            @include('admin.coupons._preview')
            <section class="form-section mt-4">
                <h4 class="form-section-title"><span class="form-section-num">2</span> クーポン情報</h4>
                <p class="text-muted small mb-3">割引内容・有効期間・発行条件を設定します。</p>
                <div class="form-group">
                    <label for="code" class="form-label">クーポンコード <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" required maxlength="50" placeholder="例: WELCOME10" aria-describedby="code-help">
                    <p id="code-help" class="form-text text-muted">会員が注文時に入力する英数字コード。重複のない値にしてください。</p>
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="name" class="form-label">名前</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" maxlength="255">
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="image_url" class="form-label">画像URL</label>
                    <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/coupon.png">
                    <p class="form-text text-muted">未入力の場合はデフォルト画像が使用されます。</p>
                    @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="type" class="form-label">種別 <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>パーセント</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>固定額</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="value" class="form-label">値 <span class="text-danger">*</span></label>
                    <input type="number" name="value" id="value" class="form-control" value="{{ old('value', 0) }}" min="0" step="0.01" required>
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
                    <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">キャンセル</a>
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
@endsection
