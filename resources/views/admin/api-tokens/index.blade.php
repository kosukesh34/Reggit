@extends('admin.layout')

@section('title', 'APIトークン管理')
@section('page-title', 'APIトークン管理')

@section('content')
<div class="info-box info-box--system">
    <h4 class="info-box-title"><i class="fas fa-info-circle"></i> APIトークンについて</h4>
    <p>APIトークンは、<strong>モバイルアプリや外部システム</strong>から <code>/api/v1</code> のAPIを呼び出すときに使います。リクエストヘッダーに <code>Authorization: Bearer &lt;トークン&gt;</code> を付与して認証します。</p>
    <p class="mb-0"><strong>LIFF（ブラウザ）のみで注文・会員機能を使う場合は不要です。</strong> その場合はトークンを作成せずにご利用ください。</p>
</div>

@if(session('issued_token_plain'))
    <div class="alert alert-success">
        <div>新しいAPIトークンを発行しました。安全な場所に保存してください。</div>
        <div class="mt-2"><code>{{ session('issued_token_plain') }}</code></div>
        <div class="text-muted mt-1">※ 再表示はできません。</div>
    </div>
@endif

<div class="card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-key"></i> APIトークン一覧</h3>
        <a href="{{ route('system-admin.api-tokens.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 新規発行</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>名前</th>
                <th>プロジェクト</th>
                <th>状態</th>
                <th>最終利用</th>
                <th>有効期限</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tokens as $token)
                <tr>
                    <td><span class="id-badge">{{ $token->id }}</span></td>
                    <td>{{ $token->name }}</td>
                    <td>{{ $token->project?->name ?? '－' }}</td>
                    <td>
                        @if($token->is_active)
                            <span class="badge badge-success">有効</span>
                        @else
                            <span class="badge badge-secondary">無効</span>
                        @endif
                    </td>
                    <td>{{ $token->last_used_at?->format('Y-m-d H:i') ?? '－' }}</td>
                    <td>{{ $token->expires_at?->format('Y-m-d') ?? '－' }}</td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ route('system-admin.api-tokens.edit', $token->id) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            <form action="{{ route('system-admin.api-tokens.rotate', $token->id) }}" method="POST" class="d-inline" onsubmit="return confirm('新しいトークンを再発行し、旧トークンを失効しますか？');">
                                @csrf
                                <button type="submit" class="btn btn-action btn-outline">再発行</button>
                            </form>
                            @if($token->is_active)
                                <form action="{{ route('system-admin.api-tokens.revoke', $token->id) }}" method="POST" class="d-inline" onsubmit="return confirm('このトークンを失効しますか？');">
                                    @csrf
                                    <button type="submit" class="btn btn-action btn-danger"><i class="fas fa-ban"></i> 失効</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-key"></i>
                        <p>APIトークンがありません</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $tokens->links('vendor.pagination.admin') }}
    </div>
</div>
@endsection
