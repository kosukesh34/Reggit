@extends('admin.layout')

@section('title', '分析')
@section('page-title', '分析')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>分析</span>
</nav>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> 期間</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ admin_route('admin.analytics') }}" class="form-inline form-inline-flex">
            <div class="form-group">
                <label for="start" class="form-label">開始日</label>
                <input type="date" id="start" name="start" class="form-control" value="{{ $view->start }}">
            </div>
            <div class="form-group">
                <label for="end" class="form-label">終了日</label>
                <input type="date" id="end" name="end" class="form-control" value="{{ $view->end }}">
            </div>
            <div class="form-actions form-actions-zero">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 集計</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line"></i> サマリー</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">注文数</div>
                <div class="info-value">{{ number_format($view->summary['orders']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">売上</div>
                <div class="info-value">¥{{ number_format($view->summary['revenue']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">平均注文単価</div>
                <div class="info-value">¥{{ number_format($view->summary['avg_order_value'], 0) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">新規会員</div>
                <div class="info-value">{{ number_format($view->summary['new_members']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">予約</div>
                <div class="info-value">{{ number_format($view->summary['reservations']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">順番待ち</div>
                <div class="info-value">{{ number_format($view->summary['queue']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">クーポン発行</div>
                <div class="info-value">{{ number_format($view->summary['coupons_issued']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">クーポン使用</div>
                <div class="info-value">{{ number_format($view->summary['coupons_used']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ポイント獲得</div>
                <div class="info-value">{{ number_format($view->summary['points_earned']) }}pt</div>
            </div>
            <div class="info-item">
                <div class="info-label">ポイント使用</div>
                <div class="info-value">{{ number_format($view->summary['points_used']) }}pt</div>
            </div>
            <div class="info-item">
                <div class="info-label">配信お知らせ数</div>
                <div class="info-value">{{ number_format($view->summary['announcements_published']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">既読ユーザー数</div>
                <div class="info-value">{{ number_format($view->summary['announcement_read_users']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">平均既読率</div>
                <div class="info-value">{{ number_format($view->summary['announcement_avg_read_rate'], 1) }}%</div>
            </div>
        </div>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-area"></i> 推移</h3>
    </div>
    <div class="card-body">
        <canvas id="ordersChart" height="120"></canvas>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> 時間帯別注文数</h3>
    </div>
    <div class="card-body">
        <canvas id="hourlyChart" height="120"></canvas>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> 新規会員</h3>
    </div>
    <div class="card-body">
        <canvas id="membersChart" height="120"></canvas>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags"></i> 注文ステータス</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            @foreach($view->breakdowns['order_statuses'] as $row)
            <div class="info-item">
                <div class="info-label">{{ $row['label'] }}</div>
                <div class="info-value">{{ number_format($row['count']) }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-credit-card"></i> 支払い方法</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            @foreach($view->breakdowns['payment_methods'] as $row)
            <div class="info-item">
                <div class="info-label">{{ $row['label'] ?? ($row['method'] ?? '不明') }}</div>
                <div class="info-value">{{ number_format($row['count']) }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group"></i> 注文金額分布</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            @foreach($view->breakdowns['order_value_buckets'] as $label => $count)
            <div class="info-item">
                <div class="info-label">{{ $label }}</div>
                <div class="info-value">{{ number_format($count) }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-th-large"></i> カテゴリ別売上</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>カテゴリ</th>
                        <th>数量</th>
                        <th>売上</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($view->breakdowns['category_sales'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ number_format($row['quantity']) }}</td>
                        <td>¥{{ number_format($row['revenue']) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>対象期間の注文がありません</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-star"></i> 人気商品（数量上位）</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>商品</th>
                        <th>数量</th>
                        <th>売上</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($view->breakdowns['top_products'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ number_format($row['quantity']) }}</td>
                        <td>¥{{ number_format($row['revenue']) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>対象期間の注文がありません</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-th"></i> 登録月コホート分析（再購買率 %）</h3>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">登録月ごとの会員が、登録月・翌月・2ヶ月後…に注文した割合（%）です。</p>
        <div class="table-responsive">
            <table class="table cohort-table">
                <thead>
                    <tr>
                        <th>コホート</th>
                        <th>登録人数</th>
                        <th>0ヶ月</th>
                        <th>1ヶ月後</th>
                        <th>2ヶ月後</th>
                        <th>3ヶ月後</th>
                        <th>4ヶ月後</th>
                        <th>5ヶ月後</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($view->cohort as $row)
                    <tr>
                        <td>{{ $row['cohort'] }}</td>
                        <td>{{ number_format($row['total']) }}人</td>
                        @foreach(['month0','month1','month2','month3','month4','month5'] as $col)
                        <td>
                            @if($row['total'] > 0)
                            <span class="cohort-cell" style="--pct: {{ $row[$col] }}">{{ $row[$col] }}%</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty-state"><i class="fas fa-chart-bar"></i><p>データがありません</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bullhorn"></i> お知らせ既読状況（上位）</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>公開日</th>
                        <th>既読ユーザー</th>
                        <th>既読率</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($view->breakdowns['announcement_reads']['top_announcements'] as $row)
                    <tr>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['published_at'] }}</td>
                        <td>{{ number_format($row['read_users']) }}</td>
                        <td>{{ number_format($row['read_rate'], 1) }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="fas fa-bullhorn"></i>
                            <p>対象期間に配信されたお知らせがありません</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-muted" style="margin-top: 8px;">
            対象ユーザー数: {{ number_format($view->breakdowns['announcement_reads']['total_users']) }}人
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($view->chart);
const hourlyData = @json($view->breakdowns['hourly_orders']);
const orderCtx = document.getElementById('ordersChart');
const memberCtx = document.getElementById('membersChart');
const hourlyCtx = document.getElementById('hourlyChart');

if (orderCtx) {
    new Chart(orderCtx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: '注文数',
                    data: chartData.orders,
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.15)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: '売上',
                    data: chartData.revenue,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                    tension: 0.3,
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

if (memberCtx) {
    new Chart(memberCtx, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: '新規会員数',
                    data: chartData.members,
                    backgroundColor: 'rgba(234, 88, 12, 0.6)',
                    borderColor: '#ea580c',
                    borderWidth: 1,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

if (hourlyCtx) {
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: Array.from({ length: 24 }, (_, i) => `${i}時`),
            datasets: [
                {
                    label: '注文数',
                    data: Object.values(hourlyData),
                    backgroundColor: 'rgba(14, 116, 144, 0.6)',
                    borderColor: '#0e7490',
                    borderWidth: 1,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
}
</script>
@endsection
