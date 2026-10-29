@extends('admin.layout')

@section('title', '会員管理')
@section('page-title', '会員管理')

@section('content')

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-users"></i> 会員管理</h1>
</div>

@if(!empty($view->lineInviteUrl))
<div class="card card-mb-16" id="member-line-qr-card">
    <div class="card-header member-line-qr-header">
        <h3 class="card-title"><i class="fab fa-line"></i> LINE招待QR</h3>
        <button type="button" class="btn btn-sm btn-primary" id="member-line-qr-toggle" aria-expanded="false" aria-controls="member-line-qr-panel">
            QRを表示
        </button>
    </div>
    <div id="member-line-qr-panel" class="card-body member-line-qr-body" hidden>
        <div id="member-line-qr" class="member-line-qr" data-url="{{ $view->lineInviteUrl }}" aria-label="LINE招待QRコード"></div>
        <div class="member-line-qr-meta">
            <p class="form-label">{{ $view->lineInviteLabel ?? 'LINE URL' }}</p>
            <code class="smaregi-url">{{ $view->lineInviteUrl }}</code>
            <p class="form-text text-muted mt-2">来店客にこのQRを読み取ってもらい、LINEアプリへ誘導できます。友だち追加URLはシステム管理の「契約・連携設定」で変更できます。</p>
        </div>
    </div>
</div>
@endif

<div class="card card-mb-16">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-search"></i> 検索・絞り込み</h3>
        @if($view->hasSearchFilters())
        <a href="{{ admin_route('admin.members') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> クリア
        </a>
        @endif
    </div>
    <form method="GET" action="{{ admin_route('admin.members') }}" class="member-search-form">
        @if(($view->filters['sort'] ?? '') !== '')
        <input type="hidden" name="sort" value="{{ $view->filters['sort'] }}">
        @endif
        @if(($view->filters['dir'] ?? '') !== '')
        <input type="hidden" name="dir" value="{{ $view->filters['dir'] }}">
        @endif
        <div class="filter-grid filter-grid--members">
            <div class="form-group">
                <label class="form-label" for="member-keyword">キーワード</label>
                <input type="text" name="keyword" id="member-keyword" class="form-control" placeholder="名前・{{ \App\Support\AdminMemberIdentifier::label() }}・電話・メール・フリガナ" value="{{ $view->filters['keyword'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="member-rank">ランク</label>
                <select name="rank" id="member-rank" class="form-control">
                    <option value="">すべて</option>
                    @foreach($view->rankOptions as $rankKey => $rankLabel)
                    <option value="{{ $rankKey }}" {{ ($view->filters['rank'] ?? '') === (string) $rankKey ? 'selected' : '' }}>{{ $rankLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="member-channel">登録チャネル</label>
                <select name="channel" id="member-channel" class="form-control">
                    <option value="">すべて</option>
                    @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                        <option value="{{ $value }}" {{ ($view->filters['channel'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($view->tags->isNotEmpty())
            <div class="form-group">
                <label class="form-label" for="member-tag">タグ</label>
                <select name="tag_id" id="member-tag" class="form-control">
                    <option value="">すべて</option>
                    @foreach($view->tags as $tag)
                    <option value="{{ $tag->id }}" {{ (string) ($view->filters['tag_id'] ?? '') === (string) $tag->id ? 'selected' : '' }}>{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group">
                <label class="form-label" for="member-visited-from">最終来店（開始）</label>
                <input type="date" name="visited_from" id="member-visited-from" class="form-control" value="{{ $view->filters['visited_from'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="member-visited-to">最終来店（終了）</label>
                <input type="date" name="visited_to" id="member-visited-to" class="form-control" value="{{ $view->filters['visited_to'] ?? '' }}">
            </div>
            <div class="form-group form-group--action">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 検索</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-id-card"></i> 会員一覧</h3>
        <div class="card-header-actions">
            <span class="card-header-note">{{ $view->members->total() }}件</span>
            <a href="{{ admin_route('admin.member-tags') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-tags"></i> タグ
            </a>
            <a href="{{ admin_route('admin.members.export', $view->queryFilters()) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-download"></i> CSV
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>
                        <a href="{{ $view->sortHref(\App\Support\AdminMemberIdentifier::usesId() ? 'id' : 'member_number') }}" class="th-sort{{ $view->isSortedBy(\App\Support\AdminMemberIdentifier::usesId() ? 'id' : 'member_number') ? ' is-active' : '' }}">
                            {{ \App\Support\AdminMemberIdentifier::label() }} <i class="{{ $view->sortIconClass(\App\Support\AdminMemberIdentifier::usesId() ? 'id' : 'member_number') }}" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $view->sortHref('name') }}" class="th-sort{{ $view->isSortedBy('name') ? ' is-active' : '' }}">
                            会員名 <i class="{{ $view->sortIconClass('name') }}" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $view->sortHref('rank') }}" class="th-sort{{ $view->isSortedBy('rank') ? ' is-active' : '' }}">
                            ランク <i class="{{ $view->sortIconClass('rank') }}" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th>登録チャネル</th>
                    <th>
                        <a href="{{ $view->sortHref('points') }}" class="th-sort{{ $view->isSortedBy('points') ? ' is-active' : '' }}">
                            ポイント <i class="{{ $view->sortIconClass('points') }}" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $view->sortHref('last_visited_at') }}" class="th-sort{{ $view->isSortedBy('last_visited_at') ? ' is-active' : '' }}">
                            最終来店 <i class="{{ $view->sortIconClass('last_visited_at') }}" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th>
                        <a href="{{ $view->sortHref('created_at') }}" class="th-sort{{ $view->isSortedBy('created_at') ? ' is-active' : '' }}">
                            登録日 <i class="{{ $view->sortIconClass('created_at') }}" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($view->members as $member)
                <tr>
                    <td><span class="id-badge">{{ \App\Support\AdminMemberIdentifier::value($member) }}</span></td>
                    <td>
                        <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}"><strong>{{ admin_person_name($member->user?->name) }}</strong></a>
                        @if($member->tags->isNotEmpty())
                        <span class="table-cell-sub member-list-tags">
                            @foreach($member->tags as $tag)
                            <span class="member-tag-badge" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                            @endforeach
                        </span>
                        @endif
                    </td>
                    <td>{{ $view->rankLabel($member->rank) }}</td>
                    <td>
                        @php $channelLabel = \App\Support\MemberChannel::label($member->user); @endphp
                        @if($channelLabel === '—')
                            <span class="text-muted">—</span>
                        @else
                            <span class="badge badge-info">{{ $channelLabel }}</span>
                        @endif
                    </td>
                    <td>{{ number_format($member->points) }}pt</td>
                    <td>
                        @if($member->last_visited_at !== null)
                            {{ $member->last_visited_at->format('Y/m/d H:i') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $member->created_at->format('Y/m/d') }}</td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                            @php $adminUser = auth('admin')->user(); @endphp
                            @if($adminUser === null || $adminUser->hasPermission('members.manage'))
                            <a href="{{ admin_route('admin.members.edit', ['id' => $member->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>
                            @if($view->hasSearchFilters())
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
    </div>
    {{ $view->members->appends($view->queryFilters())->links('vendor.pagination.admin') }}
</div>
@endsection
