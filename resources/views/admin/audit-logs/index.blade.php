@extends('admin.layout')

@section('title', '監査ログ')
@section('page-title', '監査ログ')

@section('content')
<div class="page-header system-admin-page-header">
    <p class="page-header-desc">重要な操作の履歴を確認できます。</p>
</div>
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
                        <a href="{{ route('system-admin.audit-logs.show', $log->id) }}">{{ $log->getActionLabel() }}</a>
                        @if($log->route_name)
                            <div class="text-muted" style="font-size:11px;">{{ $log->route_name }}</div>
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
