@extends('admin.layout')
@section('title', '順番待ち')
@section('page-title', '順番待ち')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.queue') }}">順番待ち</a><span class="breadcrumb-sep">/</span><span>順番待ち</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-list-ol"></i> 順番待ち</h1>
</div>
@if($errors->any())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ admin_route('admin.settings.queue.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">1組あたりの平均対応時間（分）</label>
                <input type="number" name="queue_avg_serve_minutes" class="form-control" value="{{ old('queue_avg_serve_minutes', $view->avgServeMinutes) }}" min="1" max="120" required>
                <small class="form-text text-muted">例: 10 と入力すると、待ち人数×10分が目安待ち時間になります。</small>
            </div>
            <div class="form-group">
                <label class="form-label">QR読み取り時の起動アプリ</label>
                <input type="hidden" name="queue_channel_line" value="0">
                <label class="checkbox-label">
                    <input type="checkbox" name="queue_channel_line" value="1" {{ old('queue_channel_line', $view->channelLine ? '1' : '0') === '1' ? 'checked' : '' }}>
                    <span>LINE</span>
                </label>
                <input type="hidden" name="queue_channel_native" value="0">
                <label class="checkbox-label">
                    <input type="checkbox" name="queue_channel_native" value="1" {{ old('queue_channel_native', $view->channelNative ? '1' : '0') === '1' ? 'checked' : '' }}>
                    <span>ネイティブアプリ</span>
                </label>
                <small class="form-text text-muted">両方選択時はお客様が起動アプリを選べます。片方のみの場合はそのアプリを起動します。呼び出し通知をLINEで受け取るにはLINEにチェックを入れてください（お知らせ配信と同じ経路です）。</small>
            </div>
            <div class="form-group">
                <label class="form-label">会員登録</label>
                <label class="radio-label-inline">
                    <input type="radio" name="queue_require_membership" value="1" {{ old('queue_require_membership', $view->requireMembership ? '1' : '0') === '1' ? 'checked' : '' }}> 必須
                </label>
                <label class="radio-label-inline">
                    <input type="radio" name="queue_require_membership" value="0" {{ old('queue_require_membership', $view->requireMembership ? '1' : '0') === '0' ? 'checked' : '' }}> なし
                </label>
                <small class="form-text text-muted">必須の場合、会員登録していないと順番待ちに登録できません。</small>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.queue') }}" class="btn btn-secondary">順番待ち一覧へ</a></div>
        </div>
    </div>
</form>
@endsection
