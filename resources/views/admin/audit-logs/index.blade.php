@extends('admin.layout')

@section('title', '監査ログ')
@section('page-title', '監査ログ')

@section('content')
@php
    $isSystemContext = request()->routeIs('system-admin.*');
@endphp
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list"></i> 管理操作ログ</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>日時</th>
                <th>操作内容</th>
                <th>管理者</th>
                <th>プロジェクト</th>
                <th>メソッド</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td><span class="id-badge">{{ $log->id }}</span></td>
                    <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>
                        <a href="{{ $isSystemContext ? route('system-admin.audit-logs.show', $log->id) : admin_route('admin.audit-logs.show', ['id' => $log->id]) }}">{{ $log->getActionLabel() }}</a>
                        @if($log->route_name)
                            <div class="text-muted text-muted-xs">{{ $log->route_name }}</div>
                        @endif
                    </td>
                    <td>{{ $log->adminUser?->name ?? '－' }}</td>
                    <td>{{ $log->project?->name ?? '－' }}</td>
                    <td><code>{{ $log->method }}</code></td>
                    <td>{{ $log->ip ?? '－' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>監査ログがありません</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $logs->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
