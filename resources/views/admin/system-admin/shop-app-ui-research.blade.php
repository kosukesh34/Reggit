@extends('admin.layout')

@section('title', '店舗アプリUI学習データ')
@section('page-title', '店舗アプリUI学習データ')

@push('styles')
<style>
.research-featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
}
.research-featured-card {
    margin: 0;
    background: #fff;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 12px;
    overflow: hidden;
}
.research-featured-card a {
    display: block;
    color: inherit;
    text-decoration: none;
}
.research-featured-card img {
    display: block;
    width: 100%;
    aspect-ratio: 9 / 16;
    object-fit: cover;
    background: #f3f4f6;
}
.research-featured-card figcaption {
    padding: 12px 14px 14px;
}
.research-featured-card strong {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
}
.research-featured-card span,
.research-featured-card em {
    display: block;
    font-size: 12px;
    color: #6b7280;
    font-style: normal;
    line-height: 1.45;
}
.research-featured-card em {
    margin-top: 6px;
    color: #0f766e;
    font-weight: 600;
}
.research-principle {
    border-left: 3px solid #0f766e;
    padding: 12px 14px;
    margin-bottom: 10px;
    background: #f8fafc;
    border-radius: 0 10px 10px 0;
}
.research-principle__id {
    display: inline-block;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #0f766e;
    margin-bottom: 4px;
}
.research-principle h4 {
    margin: 0 0 6px;
    font-size: 15px;
}
.research-principle p {
    margin: 0;
    font-size: 13px;
    color: #374151;
    line-height: 1.55;
}
.research-principle small {
    display: block;
    margin-top: 6px;
    color: #6b7280;
}
.research-md {
    font-size: 14px;
    line-height: 1.65;
}
.research-md h1,
.research-md h2,
.research-md h3 {
    margin: 1.2em 0 0.5em;
}
.research-md table {
    width: 100%;
    border-collapse: collapse;
    margin: 12px 0;
}
.research-md th,
.research-md td {
    border: 1px solid #e5e7eb;
    padding: 8px 10px;
    text-align: left;
    vertical-align: top;
}
.research-md code {
    font-size: 12px;
}
</style>
@endpush

@section('content')
<div class="page-header system-admin-page-header">
    <p class="page-header-desc">
        App Store 公式スクショとブランド調査から得た店舗アプリ UI の学習データです。App Studio テンプレ改善の根拠として参照できます。
    </p>
</div>

<div class="stats-grid stats-grid--system">
    <div class="stat-card stat-card-primary">
        <div class="stat-icon"><i class="fas fa-store"></i></div>
        <div class="stat-content">
            <div class="stat-label">調査ブランド</div>
            <div class="stat-value">{{ number_format($research->summary['brands']) }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-info">
        <div class="stat-icon"><i class="fas fa-mobile-alt"></i></div>
        <div class="stat-content">
            <div class="stat-label">公式アプリあり</div>
            <div class="stat-value">{{ number_format($research->summary['with_app']) }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-secondary">
        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
        <div class="stat-content">
            <div class="stat-label">アプリ未確認</div>
            <div class="stat-value">{{ number_format($research->summary['without_app']) }}</div>
        </div>
    </div>
    <div class="stat-card stat-card-warning">
        <div class="stat-icon"><i class="fas fa-camera"></i></div>
        <div class="stat-content">
            <div class="stat-label">代表スクショ</div>
            <div class="stat-value">{{ count(array_filter($research->featuredShots, fn ($s) => $s['exists'])) }}</div>
        </div>
    </div>
</div>

<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lightbulb"></i> 学習原則（業種横断）</h3>
    </div>
    <div class="card-body">
        @foreach($research->principles as $principle)
            <div class="research-principle">
                <span class="research-principle__id">{{ $principle['id'] }}</span>
                <h4>{{ $principle['title'] }}</h4>
                <p>{{ $principle['body'] }}</p>
                <small>参考: {{ $principle['source'] }}</small>
            </div>
        @endforeach
    </div>
</div>

<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-images"></i> 代表スクショ</h3>
    </div>
    <div class="card-body">
        <div class="research-featured-grid">
            @foreach($research->featuredShots as $shot)
                <figure class="research-featured-card">
                    @if($shot['exists'])
                        <a href="{{ route('system-admin.shop-app-ui-research.asset', ['path' => 'app-ui-screenshots/_featured/'.$shot['file']]) }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ route('system-admin.shop-app-ui-research.asset', ['path' => 'app-ui-screenshots/_featured/'.$shot['file']]) }}" alt="{{ $shot['title'] }}" loading="lazy">
                            <figcaption>
                                <strong>{{ $shot['title'] }}</strong>
                                <span>{{ $shot['note'] }}</span>
                                <em>{{ $shot['template'] }}</em>
                            </figcaption>
                        </a>
                    @else
                        <figcaption>
                            <strong>{{ $shot['title'] }}</strong>
                            <span>画像未配置</span>
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</div>

<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Reggit テンプレ反映マップ</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>学習元</th>
                <th>反映先</th>
            </tr>
        </thead>
        <tbody>
            @foreach($research->templateMappings as $map)
                <tr>
                    <td>{{ $map['source'] }}</td>
                    <td><code>{{ $map['target'] }}</code></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie"></i> カテゴリ別アプリ保有</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>カテゴリ</th>
                <th>調査数</th>
                <th>アプリあり</th>
                <th>保有率</th>
            </tr>
        </thead>
        <tbody>
            @foreach($research->summary['by_category'] as $category => $row)
                @php
                    $total = (int) ($row['total'] ?? 0);
                    $withApp = (int) ($row['with_app'] ?? 0);
                    $rate = $total > 0 ? round(($withApp / $total) * 100) : 0;
                @endphp
                <tr>
                    <td>{{ $category }}</td>
                    <td>{{ number_format($total) }}</td>
                    <td>{{ number_format($withApp) }}</td>
                    <td>{{ $rate }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card system-admin-card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-star"></i> 高信頼ブランド（評価件数順・上位40）</h3>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('system-admin.shop-app-ui-research.asset', ['path' => 'app-store-lookup/brand_app_lookup.csv']) }}">
            <i class="fas fa-download"></i> CSV
        </a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ブランド</th>
                <th>カテゴリ</th>
                <th>アプリ名</th>
                <th>評価</th>
                <th>件数</th>
                <th>スクショ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($research->highlightBrands as $brand)
                <tr>
                    <td>{{ $brand['brand'] }}</td>
                    <td>{{ $brand['categories'] }}</td>
                    <td>
                        @if($brand['appStoreUrl'] !== '')
                            <a href="{{ $brand['appStoreUrl'] }}" target="_blank" rel="noopener noreferrer">{{ $brand['trackName'] }}</a>
                        @else
                            {{ $brand['trackName'] }}
                        @endif
                    </td>
                    <td>{{ number_format($brand['averageUserRating'], 2) }}</td>
                    <td>{{ number_format($brand['userRatingCount']) }}</td>
                    <td>{{ $brand['screenshotCount'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-state"><p>ブランドデータがありません</p></td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($research->learningsHtml !== '')
<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book"></i> 学習メモ全文</h3>
    </div>
    <div class="card-body research-md">
        {!! $research->learningsHtml !!}
    </div>
</div>
@endif

<div class="card system-admin-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-folder-open"></i> 元データ</h3>
    </div>
    <div class="card-body">
        <ul>
            <li><code>docs/research/APP_UI_SCREENSHOT_LEARNINGS.md</code></li>
            <li><code>docs/research/BRAND_APP_LOOKUP_REPORT.md</code></li>
            <li><code>docs/research/smaregi_shop_app_ui_all_stores.csv</code></li>
            <li><code>docs/research/app-store-lookup/</code></li>
            <li><code>docs/research/app-ui-screenshots/</code></li>
            <li><code>docs/SMAREGI_SHOP_APP_UI_DESIGN_REFERENCE.md</code></li>
        </ul>
    </div>
</div>
@endsection
