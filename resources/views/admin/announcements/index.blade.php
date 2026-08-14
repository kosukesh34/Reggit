@extends('admin.layout')

@section('title', 'お知らせ')
@section('page-title', 'お知らせ')

@section('content')
@php
    $hasSearchFilters = ($filters['keyword'] ?? '') !== ''
        || ($filters['published_from'] ?? '') !== ''
        || ($filters['published_to'] ?? '') !== '';
    $tabQueryBase = array_filter([
        'keyword' => $filters['keyword'] ?? '',
        'published_from' => $filters['published_from'] ?? '',
        'published_to' => $filters['published_to'] ?? '',
    ], fn ($value) => $value !== '');
@endphp
<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-bullhorn"></i> お知らせ</h1>
        <p class="page-header-desc">お客様へのお知らせの管理</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.after-registration-deliveries.index') }}" class="btn btn-secondary"><i class="fas fa-clock"></i> 登録後配信</a>
        <a href="{{ admin_route('admin.announcements.delivery.create') }}" class="btn btn-secondary"><i class="fas fa-paper-plane"></i> 配信</a>
        <a href="{{ admin_route('admin.announcements.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</a>
    </div>
</div>

<div class="card card-mb-16">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-search"></i> 検索・絞り込み</h3>
        @if($hasSearchFilters)
        <a href="{{ admin_route('admin.announcements', ['delivery_type' => $filters['delivery_type'] ?? 'all']) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> クリア
        </a>
        @endif
    </div>
    <form method="GET" action="{{ admin_route('admin.announcements') }}" class="member-search-form">
        <input type="hidden" name="delivery_type" value="{{ $filters['delivery_type'] ?? 'all' }}">
        <div class="filter-grid filter-grid--members">
            <div class="form-group">
                <label class="form-label" for="keyword">タイトル</label>
                <input type="text" name="keyword" id="keyword" class="form-control" placeholder="タイトルで検索" value="{{ $filters['keyword'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="published_from">公開日（開始）</label>
                <input type="date" name="published_from" id="published_from" class="form-control" value="{{ $filters['published_from'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="published_to">公開日（終了）</label>
                <input type="date" name="published_to" id="published_to" class="form-control" value="{{ $filters['published_to'] ?? '' }}">
            </div>
            <div class="form-group form-group--action">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 検索</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> 配信形態</h3>
    </div>
    <div class="status-filters">
        @foreach($deliveryTypeLabels as $typeKey => $typeLabel)
        <a href="{{ admin_route('admin.announcements', array_merge($tabQueryBase, ['delivery_type' => $typeKey])) }}"
           class="status-filter {{ ($filters['delivery_type'] ?? 'all') === $typeKey ? 'active' : '' }}">
            {{ $typeLabel }}
            <span class="badge">{{ $deliveryTypeCounts[$typeKey] ?? 0 }}</span>
        </a>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">お知らせ一覧</h3>
        @if(!$announcements->isEmpty())
        <span class="card-header-note">{{ $announcements->total() }}件</span>
        @endif
    </div>
    <div class="card-body">
        @if($announcements->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <p>お知らせがありません</p>
            <p class="empty-state-hint">条件を変えるか、「追加」「配信」から登録できます</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>配信形態</th>
                        <th>状態</th>
                        <th>既読数</th>
                        <th>公開日</th>
                        <th>掲載終了</th>
                        <th>固定</th>
                        <th class="table-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($announcements as $a)
                    <tr>
                        <td><strong>{{ Str::limit($a->title, 40) }}</strong></td>
                        <td><span class="badge badge-info">{{ $deliveryTypeLabels[$a->delivery_type] ?? '—' }}</span></td>
                        <td>
                            @if($a->delivery_status === 'sent')
                                <span class="badge badge-success">{{ $deliveryStatusLabels['sent'] }}</span>
                            @elseif($a->delivery_status === 'scheduled')
                                <span class="badge badge-info">{{ $deliveryStatusLabels['scheduled'] }}</span>
                            @else
                                <span class="badge badge-secondary">{{ $deliveryStatusLabels['draft'] ?? '下書き' }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ admin_route('admin.announcements.reads', ['id' => $a->id]) }}">{{ (int) ($a->reads_count ?? 0) }}人</a>
                        </td>
                        <td>{{ $a->published_at ? $a->published_at->format('Y/m/d') : '—' }}</td>
                        <td>{{ $a->expires_at ? $a->expires_at->format('Y/m/d') : '—' }}</td>
                        <td>@if($a->is_pinned)<span class="badge badge-info"><i class="fas fa-thumbtack"></i> 固定</span>@else<span class="text-muted">—</span>@endif</td>
                        <td class="table-actions-cell">
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.announcements.edit', ['id' => $a->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                                <form action="{{ admin_route('admin.announcements.delete', ['id' => $a->id]) }}" method="POST" class="d-inline delete-form" onsubmit="return confirm('削除しますか？\n配信済みのプッシュ通知も取り消されます。');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-action btn-danger no-loading"><i class="fas fa-trash"></i> 削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $announcements->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
