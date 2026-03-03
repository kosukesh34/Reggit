@extends('admin.layout')
@section('title', '順番待ち設定')
@section('page-title', '順番待ち設定')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.queue') }}">順番待ち</a><span class="breadcrumb-sep">/</span><span>順番待ち設定</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-list-ol"></i> 順番待ち設定</h1>
    <p class="page-header-desc">1組あたりの平均対応時間を設定します。待ち時間の目安計算に使用されます。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'queue'])
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
<form action="{{ admin_route('admin.settings.queue.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">1組あたりの平均対応時間（分）</label>
                <input type="number" name="queue_avg_serve_minutes" class="form-control" value="{{ $avgServeMinutes }}" min="1" max="120" required>
                <small class="form-text text-muted">例: 10 と入力すると、待ち人数×10分が目安待ち時間になります。</small>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.queue') }}" class="btn btn-secondary">順番待ち一覧へ</a></div>
        </div>
    </div>
</form>
@endsection
