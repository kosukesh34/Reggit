@extends('admin.layout')

@section('title', '分析')
@section('page-title', '分析')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>分析</span>
</nav>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> 期間</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ admin_route('admin.analytics') }}" class="form-inline" style="display:flex; gap:12px; flex-wrap:wrap;">
            <div class="form-group">
                <label for="start" class="form-label">開始日</label>
                <input type="date" id="start" name="start" class="form-control" value="{{ $start }}">
            </div>
            <div class="form-group">
                <label for="end" class="form-label">終了日</label>
                <input type="date" id="end" name="end" class="form-control" value="{{ $end }}">
            </div>
            <div class="form-actions" style="margin:0;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> 集計</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line"></i> サマリー</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">注文数</div>
                <div class="info-value">{{ number_format($summary['orders']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">売上</div>
                <div class="info-value">¥{{ number_format($summary['revenue']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">平均注文単価</div>
                <div class="info-value">¥{{ number_format($summary['avg_order_value'], 0) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">新規会員</div>
                <div class="info-value">{{ number_format($summary['new_members']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">予約</div>
                <div class="info-value">{{ number_format($summary['reservations']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">順番待ち</div>
                <div class="info-value">{{ number_format($summary['queue']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">クーポン発行</div>
                <div class="info-value">{{ number_format($summary['coupons_issued']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">クーポン使用</div>
                <div class="info-value">{{ number_format($summary['coupons_used']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ポイント獲得</div>
                <div class="info-value">{{ number_format($summary['points_earned']) }}pt</div>
            </div>
            <div class="info-item">
                <div class="info-label">ポイント使用</div>
                <div class="info-value">{{ number_format($summary['points_used']) }}pt</div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-area"></i> 推移</h3>
    </div>
    <div class="card-body">
        <canvas id="ordersChart" height="120"></canvas>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> 新規会員</h3>
    </div>
    <div class="card-body">
        <canvas id="membersChart" height="120"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($chart);
const orderCtx = document.getElementById('ordersChart');
const memberCtx = document.getElementById('membersChart');

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
</script>
@endsection
