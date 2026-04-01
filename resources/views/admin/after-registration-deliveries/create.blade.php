@extends('admin.layout')

@section('title', '登録後配信を追加')
@section('page-title', '登録後配信を追加')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.after-registration-deliveries.index') }}">登録後配信</a>
    <span class="breadcrumb-sep">/</span>
    <span>追加</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-plus"></i> 登録後配信を追加</h1>
    <p class="page-header-desc">会員登録から指定分数後に送るお知らせとクーポンを設定します</p>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ admin_route('admin.after-registration-deliveries.store') }}" method="POST">
    @csrf
    <div class="card mb-4">
        <div class="card-body">
            <div class="form-group">
                <label for="delay_minutes" class="form-label">登録から何分後に配信するか <span class="text-danger">*</span></label>
                <input type="number" name="delay_minutes" id="delay_minutes" class="form-control" value="{{ old('delay_minutes', 30) }}" min="1" max="10080" required>
                <p class="form-text text-muted">1〜10080（7日＝10080分）で指定してください</p>
            </div>
            <div class="form-group">
                <label for="title" class="form-label">お知らせタイトル <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required maxlength="200" placeholder="例: ご登録ありがとうございます">
            </div>
            <div class="form-group">
                <label for="body" class="form-label">お知らせ本文</label>
                <textarea name="body" id="body" class="form-control" rows="4" maxlength="5000" placeholder="プッシュ通知とお知らせ一覧に表示されます">{{ old('body') }}</textarea>
            </div>
            <div class="form-group">
                <label for="coupon_id" class="form-label">同時に配布するクーポン（任意）</label>
                <select name="coupon_id" id="coupon_id" class="form-control">
                    <option value="">配布しない</option>
                    @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id }}" {{ (string) old('coupon_id') === (string) $coupon->id ? 'selected' : '' }}>{{ $coupon->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>有効にする</span>
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> 追加する</button>
        <a href="{{ admin_route('admin.after-registration-deliveries.index') }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
</form>
@endsection
