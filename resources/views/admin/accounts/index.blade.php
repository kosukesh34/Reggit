@extends('admin.layout')

@section('title', 'アカウント管理')
@section('page-title', 'アカウント管理')

@section('content')
@if(request()->routeIs('system-admin.accounts.*'))
@endif
<div class="card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-user-cog"></i> 管理者アカウント一覧</h3>
        @if($view->me->isStaff() === false)
            <a href="{{ route("{$view->routePrefix}.accounts.create", $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}" class="btn btn-primary"><i class="fas fa-plus"></i> 新規作成</a>
        @endif
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>ログインID</th>
                <th>メール</th>
                <th>名前</th>
                <th>権限</th>
                <th>担当店舗</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($view->adminUsers as $au)
            <tr>
                <td><span class="id-badge">{{ $au->id }}</span></td>
                <td><code>{{ $au->login_id }}</code></td>
                <td>{{ $au->email }}</td>
                <td>{{ $au->name }}</td>
                <td>
                    @php
                        $badgeClass = match($au->role) {
                            'system_admin' => 'primary',
                            'project_admin' => 'info',
                            'admin' => 'secondary',
                            'staff' => 'warning',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge badge-{{ $badgeClass }}" title="{{ \App\Models\AdminUser::getRoleLabel($au->role) }}">{{ \App\Models\AdminUser::getRoleLabelShort($au->role) }}</span>
                </td>
                <td>
                    @if($au->role === 'system_admin')
                        －（全プロジェクト）
                    @elseif($au->projects->isNotEmpty())
                        {{ $au->projects->pluck('name')->join('、') }}
                    @else
                        {{ $au->project?->name ?? '－' }}
                    @endif
                </td>
                <td class="table-actions-cell">
                    <div class="table-actions">
                        @if($view->me->canEditUser($au))
                            <a href="{{ route("{$view->routePrefix}.accounts.edit", array_merge(
                                ['id' => $au->id],
                                $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []
                            )) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            @if($au->id !== $view->me->id)
                                <form action="{{ route("{$view->routePrefix}.accounts.destroy", array_merge(
                                    ['id' => $au->id],
                                    $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []
                                )) }}" method="POST" class="d-inline" onsubmit="return confirm('このアカウントを削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>管理者アカウントがありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $view->adminUsers->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
