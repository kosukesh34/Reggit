@extends('admin.layout')

@section('title', '分析から配信')
@section('page-title', '分析から配信')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.analytics') }}">分析</a>
    <span class="breadcrumb-sep">/</span>
    <span>配信</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-paper-plane"></i> 分析から配信</h1>
</div>

<div class="card mb-4">
    <div class="card-header">分析AI向けAPI</div>
    <div class="card-body">
        <p>分析専用APIトークン（Bearer）で呼び出します。集計には会員個人の情報を含みません。</p>
        <p>分析API: <strong>{{ $project->analysis_api_enabled ? '有効' : '無効' }}</strong> ／ APIからの配信: <strong>{{ $project->analysis_delivery_enabled ? '有効' : '無効' }}</strong></p>
        @if(session('new_analysis_token'))
            <div class="alert alert-warning">新しい分析専用トークン（一度だけ表示します）: <code>{{ session('new_analysis_token') }}</code></div>
        @endif
        @if(auth('admin')->user()?->hasPermission('settings.manage') && auth('admin')->user()?->hasPermission('coupons.manage'))
            <form action="{{ admin_route('admin.analysis-deliveries.settings') }}" method="POST" class="mb-4">
                @csrf
                <label class="d-block"><input type="checkbox" name="analysis_api_enabled" value="1" @checked($project->analysis_api_enabled)> 外部AIによる分析APIの利用を許可</label>
                <label class="d-block"><input type="checkbox" name="analysis_delivery_enabled" value="1" @checked($project->analysis_delivery_enabled)> 外部AIからの全会員配信を許可</label>
                <button type="submit" class="btn btn-secondary">連携設定を保存</button>
            </form>
            <form action="{{ admin_route('admin.analysis-deliveries.token') }}" method="POST" class="mb-3">
                @csrf
                <button type="submit" class="btn btn-secondary">分析専用トークンを発行・再発行</button>
                <span class="form-text">再発行すると従来の分析専用トークンは失効します。有効期限は90日です。</span>
            </form>
            @foreach($analysisTokens as $token)
                <form action="{{ admin_route('admin.analysis-deliveries.token.revoke', ['token' => $token->id]) }}" method="POST" class="mb-2">
                    @csrf @method('DELETE')
                    <span>有効な分析専用トークン（期限: {{ $token->expires_at?->format('Y/m/d') ?? 'なし' }}）</span>
                    <button type="submit" class="btn btn-secondary">失効</button>
                </form>
            @endforeach
        @endif
        <p><code>GET /api/v1/{{ $projectKey }}/analysis/summary?start=2026-09-01&amp;end=2026-09-24</code></p>
        <p><code>GET /api/v1/{{ $projectKey }}/analysis/coupons</code></p>
        <p><code>POST /api/v1/{{ $projectKey }}/analysis/deliveries</code></p>
        <p class="form-text">本文: <code>{"coupon_id": 1, "send_notification": true}</code></p>
        <p class="form-text">MCP対応のAIクライアントには <code>scripts/analysis-mcp.mjs</code> を登録できます。配信ツールは全会員への即時配布です。</p>
    </div>
</div>

<div class="card">
    <div class="card-header">店舗から配信を実行</div>
    <div class="card-body">
        <form action="{{ admin_route('admin.analysis-deliveries.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="coupon_id" class="form-label">クーポン</label>
                <select name="coupon_id" id="coupon_id" class="form-control" required>
                    <option value="">選択してください</option>
                    @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id }}" @selected((int) old('coupon_id') === (int) $coupon->id)>{{ $coupon->name }}</option>
                    @endforeach
                </select>
                @error('coupon_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="send_notification" value="1" checked>
                    通知を送る
                </label>
            </div>
            <button type="submit" class="btn btn-primary">全員に今すぐ配信</button>
        </form>
    </div>
</div>
@endsection
