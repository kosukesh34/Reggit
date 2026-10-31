@inject('memberIdentifier', 'App\Support\AdminMemberIdentifier')
@extends('admin.layout')
@section('title', 'RFM分析')
@section('page-title', 'RFM分析')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>RFM分析</span>
</nav>

<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">RFMの判定基準</h3></div>
    <div class="card-body">
        <p>このテナントの有効な会員を、キャンセル以外の全期間の注文で集計します。最終購買日は注文の作成日時です。保存した基準は、お知らせ・クーポン配信のRFM条件にも反映されます。</p>
        <table class="table">
            <thead><tr><th>セグメント</th><th>現在の判定基準</th></tr></thead>
            <tbody>
                <tr><td>優良顧客</td><td>{{ $settings->recency_active_days }}日以内・{{ $settings->frequency_high }}回以上・累計{{ number_format($settings->monetary_high) }}円以上</td></tr>
                <tr><td>ロイヤル顧客</td><td>{{ $settings->recency_active_days }}日以内・{{ $settings->frequency_high }}回以上・累計{{ number_format($settings->monetary_high) }}円未満</td></tr>
                <tr><td>アクティブ</td><td>{{ $settings->recency_active_days }}日以内・{{ $settings->frequency_high }}回未満</td></tr>
                <tr><td>離反リスク</td><td>{{ $settings->recency_active_days + 1 }}〜{{ $settings->recency_sleeping_days }}日前</td></tr>
                <tr><td>休眠顧客</td><td>{{ $settings->recency_sleeping_days + 1 }}日以上前</td></tr>
                <tr><td>未購買</td><td>集計対象の注文なし</td></tr>
            </tbody>
        </table>
        @if(auth('admin')->user()->hasPermission('settings.manage'))
            <form method="POST" action="{{ admin_route('admin.rfm.settings.update') }}" class="mt-3">
                @csrf
                @method('PUT')
                @foreach([
                    'recency_active_days' => ['アクティブ判定日数（日）', 1, 36500],
                    'recency_sleeping_days' => ['離反リスク判定日数（日）', 1, 36500],
                    'frequency_high' => ['高頻度の購買回数（回）', 1, 1000000],
                    'monetary_high' => ['高額の累計購買額（円）', 0, 4294967295],
                ] as $field => [$label, $min, $max])
                <div class="form-group">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input class="form-control" type="number" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings->{$field}) }}" min="{{ $min }}" max="{{ $max }}" step="1" required>
                    @error($field)<p class="text-danger">{{ $message }}</p>@enderror
                </div>
                @endforeach
                <p>離反リスク判定日数はアクティブ判定日数より大きい値にしてください。デフォルトは30日・90日・5回・10,000円です。入力した値は「判定基準を保存」を押すと反映され、現在の注文データで分類が更新されます。</p>
                <button type="submit" class="btn btn-primary">判定基準を保存</button>
            </form>
        @endif
    </div>
</div>

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
                    <th>{{ $memberIdentifier->label() }}</th>
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
                    <td><code class="member-number">{{ $memberIdentifier->fromValues($row['member_id'] ?? $row['id'] ?? null, $row['member_number']) }}</code></td>
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
