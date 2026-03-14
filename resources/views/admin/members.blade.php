@extends('admin.layout')

@section('title', '会員管理')
@section('page-title', '会員管理')

@section('content')

{{-- 検索・フィルター --}}
<div class="card card-mb-16">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-search"></i> 検索・絞り込み</h3>
        @if(collect($filters)->filter()->isNotEmpty())
        <a href="{{ admin_route('admin.members') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> クリア
        </a>
        @endif
    </div>
    <form method="GET" action="{{ admin_route('admin.members') }}" class="member-search-form">
        <div class="filter-grid filter-grid--members">
            <div class="form-group">
                <label class="form-label">名前・会員番号・電話番号</label>
                <input type="text" name="keyword" class="form-control" placeholder="キーワードで検索" value="{{ $filters['keyword'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label">ステータス</label>
                <select name="status" class="form-control">
                    <option value="">すべて</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>有効</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>無効</option>
                </select>
            </div>
            <div class="form-group form-group--action">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 検索</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> 会員一覧</h3>
        <div class="card-header-actions">
            <span class="card-header-note">{{ $members->total() }}件</span>
            <a href="{{ admin_route('admin.members.export', $filters) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-download"></i> CSV
            </a>
        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>会員番号</th>
                <th>ユーザー名</th>
                <th>ポイント</th>
                <th>ステータス</th>
                <th>登録日</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $member)
            <tr>
                <td><span class="id-badge">{{ $member->id }}</span></td>
                <td><code class="member-number">{{ $member->member_number }}</code></td>
                <td>{{ $member->user->name ?? 'N/A' }}</td>
                <td>{{ number_format($member->points) }}pt</td>
                <td>
                    <span class="badge badge-{{ $member->status === 'active' ? 'success' : 'danger' }}">
                        {{ $member->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td>{{ $member->created_at->format('Y/m/d') }}</td>
                <td class="table-actions-cell">
                    <div class="table-actions">
                        <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>
                        @if(collect($filters)->filter()->isNotEmpty())
                            検索条件に一致する会員がいません
                        @else
                            会員がありません
                        @endif
                    </p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    {{ $members->appends($filters)->links('vendor.pagination.admin') }}
</div>
@endsection

