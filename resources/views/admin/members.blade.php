@extends('admin.layout')

@section('title', '会員管理')
@section('page-title', '会員管理')

@section('content')
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>会員番号</th>
                <th>ユーザー名</th>
                <th>ポイント</th>
                <th>ステータス</th>
                <th>登録日</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $member)
            <tr>
                <td>{{ $member->member_number }}</td>
                <td>{{ $member->user->name ?? 'N/A' }}</td>
                <td>{{ number_format($member->points) }}pt</td>
                <td>
                    <span class="badge badge-{{ $member->status === 'active' ? 'success' : 'danger' }}">
                        {{ $member->status === 'active' ? '有効' : '無効' }}
                    </span>
                </td>
                <td>{{ $member->created_at->format('Y/m/d') }}</td>
                <td>
                    <a href="{{ route('admin.members.detail', $member->id) }}" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">会員がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $members->links() }}
    </div>
</div>
@endsection


