@extends('admin.layout')

@section('title', '登録後配信を編集')
@section('page-title', '登録後配信を編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.after-registration-deliveries.index') }}">登録後配信</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-edit"></i> 登録後配信を編集</h1>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ admin_route('admin.after-registration-deliveries.update', ['id' => $delivery->id]) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card mb-4">
        <div class="card-body">
            <div class="form-group">
                <label for="delay_minutes" class="form-label">登録から何分後に配信するか <span class="text-danger">*</span></label>
                <input type="number" name="delay_minutes" id="delay_minutes" class="form-control" value="{{ old('delay_minutes', $delivery->delay_minutes) }}" min="1" max="10080" required>
            </div>
            <div class="form-group">
                <label for="title" class="form-label">お知らせタイトル <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $delivery->title) }}" required maxlength="200">
            </div>
            <div class="form-group">
                <label for="body" class="form-label">お知らせ本文</label>
                <textarea name="body" id="body" class="form-control" rows="4" maxlength="5000">{{ old('body', $delivery->body) }}</textarea>
            </div>
            <div class="form-group">
                <label for="coupon_id" class="form-label">同時に配布するクーポン（任意）</label>
                <select name="coupon_id" id="coupon_id" class="form-control">
                    <option value="">配布しない</option>
                    @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id }}" {{ old('coupon_id', $delivery->coupon_id) == $coupon->id ? 'selected' : '' }}>{{ $coupon->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $delivery->is_active) ? 'checked' : '' }}>
                    <span>有効にする</span>
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 更新する</button>
        <a href="{{ admin_route('admin.after-registration-deliveries.index') }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
</form>
@endsection
