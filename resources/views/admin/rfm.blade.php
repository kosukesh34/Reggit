@extends('admin.layout')
@section('title', 'RFM分析')
@section('page-title', 'RFM分析')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>RFM分析</span>
</nav>

{{-- セグメント別サマリー --}}
<div class="rfm-segment-grid">
    @foreach($view->segmentSummary as $segKey => $seg)
    <a href="{{ admin_route('admin.rfm', ['segment' => $view->filterSegment === $segKey ? '' : $segKey]) }}"
       class="rfm-segment-card {{ $view->filterSegment === $segKey ? 'is-active' : '' }}">
        <span class="rfm-segment-dot" style="background:{{ $seg['color'] }};"></span>
        <span class="rfm-segment-label">{{ $seg['label'] }}</span>
        <span class="rfm-segment-count">{{ number_format($seg['count']) }}人</span>
    </a>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users"></i>
            @if($view->filterSegment !== '')
                {{ $view->segmentSummary[$view->filterSegment]['label'] ?? $view->filterSegment }}
            @else
                全会員
            @endif
            （{{ number_format($view->rows->count()) }}件）
        </h3>
        @if($view->filterSegment !== '')
        <a href="{{ admin_route('admin.rfm') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> フィルタ解除
        </a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>会員番号</th>
                    <th>ユーザー名</th>
                    <th>セグメント</th>
                    <th>最終購買（日前）</th>
                    <th>購買回数</th>
                    <th>累計購買額</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($view->rows as $row)
                <tr>
                    <td><code class="member-number">{{ $row['member_number'] }}</code></td>
                    <td>{{ $row['name'] }}</td>
                    <td>
                        <span class="rfm-badge" style="background: {{ $view->segmentSummary[$row['segment']]['color'] ?? '#6b7280' }}">
                            {{ $row['segment_label'] }}
                        </span>
                    </td>
                    <td>{{ $row['recency_days'] !== null ? $row['recency_days'].'日前' : '—' }}</td>
                    <td>{{ number_format($row['frequency']) }}回</td>
                    <td>¥{{ number_format($row['monetary']) }}</td>
                    <td class="table-actions-cell">
                        <a href="{{ admin_route('admin.members.detail', ['id' => $row['member_id']]) }}" class="btn btn-action btn-edit">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <p>該当する会員がいません</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
.rfm-segment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.rfm-segment-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 12px;
    background: var(--bg-primary);
    border: 2px solid var(--border-light);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--text-primary);
    font-size: 13px;
    transition: all .15s ease;
    cursor: pointer;
}
.rfm-segment-card:hover { border-color: var(--primary); box-shadow: var(--shadow); }
.rfm-segment-card.is-active { border-color: var(--primary); background: var(--primary-bg); }
.rfm-segment-dot { width: 12px; height: 12px; border-radius: 50%; }
.rfm-segment-label { font-weight: 600; }
.rfm-segment-count { font-size: 18px; font-weight: 700; }
.rfm-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    color: #fff;
    white-space: nowrap;
}
</style>
@endpush
@endsection
