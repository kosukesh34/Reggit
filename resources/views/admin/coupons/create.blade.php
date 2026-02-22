@extends('admin.layout')
@section('title', 'クーポン作成')
@section('page-title', 'クーポン作成')
@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.coupons') }}">クーポン</a><span class="breadcrumb-sep">/</span><span>新規</span></nav>
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-ticket-alt"></i> 新規クーポン</h3></div>
    <div class="card-body">
        <form action="{{ admin_route('admin.coupons.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="code" class="form-label">コード <span class="text-danger">*</span></label>
                <input type="text" name="code" id="code" class="form-control" value="{{ old('code') }}" required maxlength="50" placeholder="例: WELCOME10">
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
                    <option value="manual" {{ old('issue_trigger', 'manual') === 'manual' ? 'selected' : '' }}>手動（管理画面で会員に付与）</option>
                    <option value="first_registration" {{ old('issue_trigger') === 'first_registration' ? 'selected' : '' }}>初回登録時</option>
                    <option value="stamp_completed" {{ old('issue_trigger') === 'stamp_completed' ? 'selected' : '' }}>スタンプカード達成時</option>
                </select>
                <small class="form-text text-muted">「初回登録時」は会員登録時に自動で発行されます。「スタンプカード達成時」は下で対象カードを選択してください。</small>
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
                <button type="submit" class="btn btn-primary">作成</button>
                <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('issue_trigger').addEventListener('change', function() {
    var g = document.getElementById('stamp_card_group');
    g.style.display = this.value === 'stamp_completed' ? 'block' : 'none';
    document.getElementById('stamp_card_id').required = this.value === 'stamp_completed';
});
</script>
@endsection
