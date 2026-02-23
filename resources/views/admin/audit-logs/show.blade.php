@extends('admin.layout')

@section('title', '監査ログ詳細')
@section('page-title', '監査ログ詳細')

@section('content')
@php
    $isSystemContext = request()->routeIs('system-admin.*');
    $indexUrl = $isSystemContext
        ? route('system-admin.audit-logs.index')
        : admin_route('admin.audit-logs.index');
@endphp
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ $indexUrl }}">監査ログ</a>
    <span class="breadcrumb-sep">/</span>
    <span>詳細</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list"></i> ログ詳細</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label">操作内容</label>
            <div class="audit-log-action-label">{{ $log->getActionLabel() }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">日時</label>
            <div>{{ $log->created_at?->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">管理者</label>
            <div>{{ $log->adminUser?->name ?? '－' }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">プロジェクト</label>
            <div>{{ $log->project?->name ?? '－' }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">ルート</label>
            <div>{{ $log->route_name ?? '－' }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">メソッド</label>
            <div><code>{{ $log->method }}</code></div>
        </div>
        <div class="form-group">
            <label class="form-label">URL</label>
            <div class="text-muted">{{ $log->url }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">IP</label>
            <div>{{ $log->ip ?? '－' }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">User-Agent</label>
            <div class="text-muted">{{ $log->user_agent ?? '－' }}</div>
        </div>
        <div class="form-group">
            <label class="form-label">Payload</label>
            <pre class="code-block">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        <div class="form-actions">
            <a href="{{ $indexUrl }}" class="btn btn-secondary">戻る</a>
        </div>
    </div>
</div>
@endsection
