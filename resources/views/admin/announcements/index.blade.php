@extends('admin.layout')

@section('title', 'お知らせ')
@section('page-title', 'お知らせ')

@section('content')
@php
    $hasSearchFilters = ($view->filters['keyword'] ?? '') !== ''
        || ($view->filters['published_from'] ?? '') !== ''
        || ($view->filters['published_to'] ?? '') !== '';
    $tabQueryBase = array_filter([
        'keyword' => $view->filters['keyword'] ?? '',
        'published_from' => $view->filters['published_from'] ?? '',
        'published_to' => $view->filters['published_to'] ?? '',
    ], fn ($value) => $value !== '');
@endphp
<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-bullhorn"></i> お知らせ</h1>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.after-registration-deliveries.index') }}" class="btn btn-secondary"><i class="fas fa-clock"></i> スケジュール配信</a>
        <a href="{{ admin_route('admin.announcements.delivery.create') }}" class="btn btn-primary"><i class="fas fa-paper-plane"></i> お知らせを配信</a>
    </div>
</div>

<div class="settings-category-tabs" role="tablist" aria-label="お知らせの表示" data-announcement-tabs>
    <button type="button" role="tab" id="announcement-tab-list" aria-controls="announcement-panel-list" aria-selected="true">配信一覧</button>
    <button type="button" role="tab" id="announcement-tab-groups" aria-controls="announcement-panel-groups" aria-selected="false" tabindex="-1">配信グループ</button>
</div>
<div id="announcement-panel-list" role="tabpanel" aria-labelledby="announcement-tab-list" data-announcement-panel>

<div class="card card-mb-16">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-search"></i> 検索・絞り込み</h3>
        @if($hasSearchFilters)
        <a href="{{ admin_route('admin.announcements', ['delivery_type' => $view->filters['delivery_type'] ?? 'all']) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> クリア
        </a>
        @endif
    </div>
    <form method="GET" action="{{ admin_route('admin.announcements') }}" class="member-search-form">
        <input type="hidden" name="delivery_type" value="{{ $view->filters['delivery_type'] ?? 'all' }}">
        <div class="filter-grid filter-grid--members">
            <div class="form-group">
                <label class="form-label" for="keyword">タイトル</label>
                <input type="text" name="keyword" id="keyword" class="form-control" placeholder="タイトルで検索" value="{{ $view->filters['keyword'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="published_from">公開日（開始）</label>
                <input type="date" name="published_from" id="published_from" class="form-control" value="{{ $view->filters['published_from'] ?? '' }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="published_to">公開日（終了）</label>
                <input type="date" name="published_to" id="published_to" class="form-control" value="{{ $view->filters['published_to'] ?? '' }}">
            </div>
            <div class="form-group form-group--action">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 検索</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> 配信対象</h3>
    </div>
    <div class="status-filters">
        @foreach($view->deliveryTypeLabels as $typeKey => $typeLabel)
        <a href="{{ admin_route('admin.announcements', array_merge($tabQueryBase, ['delivery_type' => $typeKey])) }}"
           class="status-filter {{ ($view->filters['delivery_type'] ?? 'all') === $typeKey ? 'active' : '' }}">
            {{ $typeLabel }}
            <span class="badge">{{ $view->deliveryTypeCounts[$typeKey] ?? 0 }}</span>
        </a>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">お知らせ一覧</h3>
        @if(!$view->announcements->isEmpty())
        <span class="card-header-note">{{ $view->announcements->total() }}件</span>
        @endif
    </div>
    <div class="card-body">
        @if($view->announcements->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <p>お知らせがありません</p>
            <p class="empty-state-hint">条件を変えるか、「お知らせを配信」から登録できます</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>タイミング</th>
                        <th>配信対象</th>
                        <th>状態</th>
                        <th>対象者</th>
                        <th>既読</th>
                        <th>公開日</th>
                        <th>掲載終了</th>
                        <th>固定</th>
                        <th class="table-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($view->announcements as $a)
                    <tr>
                        <td><strong>{{ Str::limit($a->title, 40) }}</strong></td>
                        <td>
                            @if(($a->delivery_timing ?? null) === \App\Models\Announcement::DELIVERY_TIMING_SCHEDULED || $a->delivery_type === \App\Models\Announcement::DELIVERY_TYPE_SCHEDULED)
                                <span class="badge badge-info"><i class="far fa-clock"></i> 予約</span>
                            @else
                                <span class="badge badge-success"><i class="fas fa-bolt"></i> 即時</span>
                            @endif
                        </td>
                        <td><span class="badge badge-info">{{ $view->deliveryTypeLabels[$a->delivery_type] ?? '—' }}</span></td>
                        <td>
                            @if($a->delivery_status === 'sent')
                                <span class="badge badge-success">{{ $view->deliveryStatusLabels['sent'] }}</span>
                            @elseif($a->delivery_status === 'held')
                                <span class="badge badge-warning" title="作成者の権限または店舗状態が変更されたため保留しています。本部で対象を確認してください。">保留（権限確認）</span>
                            @elseif($a->delivery_status === 'scheduled')
                                <span class="badge badge-info">{{ $view->deliveryStatusLabels['scheduled'] }}</span>
                            @else
                                <span class="badge badge-secondary">{{ $view->deliveryStatusLabels['draft'] ?? '下書き' }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ admin_route('admin.announcements.reads', ['id' => $a->id]) }}">
                                @if($a->delivery_status === \App\Models\Announcement::DELIVERY_STATUS_SCHEDULED && $a->shouldResolveAudienceAtSend())
                                    配信時に確定
                                @else
                                    {{ is_array($a->target_member_ids) ? count($a->target_member_ids).'人' : '全員' }}
                                @endif
                            </a>
                        </td>
                        <td>
                            <a href="{{ admin_route('admin.announcements.reads', ['id' => $a->id]) }}">{{ (int) ($a->reads_count ?? 0) }}人</a>
                        </td>
                        <td>{{ $a->published_at ? $a->published_at->format('Y/m/d H:i') : '—' }}</td>
                        <td>{{ $a->expires_at ? $a->expires_at->format('Y/m/d H:i') : '—' }}</td>
                        <td>@if($a->is_pinned)<span class="badge badge-info"><i class="fas fa-thumbtack"></i> 固定</span>@else<span class="text-muted">—</span>@endif</td>
                        <td class="table-actions-cell">
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.announcements.reads', ['id' => $a->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-chart-bar"></i> 配信結果</a>
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
        {{ $view->announcements->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
</div>

<div id="announcement-panel-groups" role="tabpanel" aria-labelledby="announcement-tab-groups" data-announcement-panel hidden>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group"></i> 配信グループ</h3>
        </div>
        <div class="card-body">
            <p class="text-muted">条件を保存したグループです。お知らせ配信の「条件で絞る」から、条件として追加できます。同じ名前で保存すると条件を上書きします。</p>
            @if($view->audienceGroups->isEmpty())
            <div class="empty-state">
                <i class="fas fa-layer-group"></i>
                <p>配信グループはまだありません</p>
                <p class="empty-state-hint">「お知らせを配信」で条件を選び、「グループとして保存」してください</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>グループ名</th>
                            <th>条件</th>
                            <th class="table-actions-cell">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($view->audienceGroups as $group)
                        <tr>
                            <td><strong>{{ $group->name }}</strong></td>
                            <td>{{ count(array_diff(array_keys(is_array($group->criteria) ? $group->criteria : []), ['operators', 'match_mode'])) }}件</td>
                            <td class="table-actions-cell">
                                <a href="{{ admin_route('admin.announcements.delivery.create') }}" class="btn btn-action btn-edit"><i class="fas fa-paper-plane"></i> 配信に使う</a>
                                <form action="{{ admin_route('admin.announcements.audience-groups.destroy', ['id' => $group->id]) }}" method="POST" class="d-inline delete-form" onsubmit="return confirm('この配信グループを削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-action btn-danger no-loading"><i class="fas fa-trash"></i> 削除</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
(function () {
    const tablist = document.querySelector('[data-announcement-tabs]');
    if (tablist === null) {
        return;
    }
    const buttons = Array.from(tablist.querySelectorAll('[role="tab"]'));
    const panels = Array.from(document.querySelectorAll('[data-announcement-panel]'));
    const activate = (id) => {
        buttons.forEach((button) => {
            const selected = button.getAttribute('aria-controls') === id;
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.tabIndex = selected ? 0 : -1;
        });
        panels.forEach((panel) => {
            panel.hidden = panel.id !== id;
        });
    };
    buttons.forEach((button) => {
        button.addEventListener('click', () => activate(button.getAttribute('aria-controls')));
    });
    if (window.location.hash === '#groups') {
        activate('announcement-panel-groups');
    }
})();
</script>
@endsection
