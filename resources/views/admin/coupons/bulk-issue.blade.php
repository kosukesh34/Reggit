@extends('admin.layout')

@section('title', 'プレゼント配信（一括配布）')
@section('page-title', 'プレゼント配信（一括配布）')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>一括配布</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-gift"></i> プレゼント配信（一括配布）</h1>
    <p class="page-header-desc">選択したクーポンを会員に一括で配布します</p>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ admin_route('admin.coupons.bulk-issue.store') }}" method="POST" id="bulkIssueForm">
    @csrf
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">配布内容</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="coupon_id" class="form-label">クーポン <span class="text-danger">*</span></label>
                <select name="coupon_id" id="coupon_id" class="form-control" required>
                    <option value="">選択してください</option>
                    @foreach($coupons as $c)
                    <option value="{{ $c->id }}" {{ (string)old('coupon_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name ?? $c->code }}（{{ $c->code }}）</option>
                    @endforeach
                </select>
                @if($coupons->isEmpty())
                <p class="form-text text-muted">有効なクーポンがありません。先にクーポンを作成してください。</p>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">配布対象 <span class="text-danger">*</span></label>
                <div class="delivery-target-options">
                    <label class="radio-label">
                        <input type="radio" name="target" value="all" {{ old('target', 'all') === 'all' ? 'checked' : '' }} data-block="all">
                        <span>全員に配布</span>
                        <small class="d-block text-muted">登録会員全員（{{ number_format($memberCount) }}人）に配布します</small>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="target" value="selected" {{ old('target') === 'selected' ? 'checked' : '' }} data-block="selected">
                        <span>会員を選択して配布</span>
                        <small class="d-block text-muted">会員管理から対象を選び、一覧で選択してください</small>
                    </label>
                </div>
            </div>
            <div id="selectedMembersBlock" class="form-group" style="display: none;">
                <label class="form-label">対象会員のID</label>
                <p class="form-text text-muted">会員管理で対象を確認し、会員IDをカンマ区切りで入力（例: 1,2,3）</p>
                <input type="text" name="member_ids_input" id="member_ids_input" class="form-control" value="{{ is_array(old('member_ids')) ? implode(', ', old('member_ids')) : old('member_ids_input') }}" placeholder="1, 2, 3">
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-gift"></i> 配布する</button>
        <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
</form>

@push('styles')
<style>
.delivery-target-options .radio-label { display: block; margin-bottom: 1rem; padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 6px; cursor: pointer; }
.delivery-target-options .radio-label:hover { background: #f8f9fa; }
.delivery-target-options input[type=radio] { margin-right: 0.5rem; }
</style>
@endpush
@push('scripts')
<script>
(function() {
    function toggleBlock() {
        var target = document.querySelector('input[name="target"]:checked');
        var block = document.getElementById('selectedMembersBlock');
        block.style.display = (target && target.value === 'selected') ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="target"]').forEach(function(r) { r.addEventListener('change', toggleBlock); });
    toggleBlock();

})();
</script>
@endpush
@endsection
