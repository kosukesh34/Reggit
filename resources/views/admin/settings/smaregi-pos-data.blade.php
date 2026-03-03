@extends('admin.layout')

@section('title', '仮想POSデータ確認（開発用）')
@section('page-title', '仮想POSデータ確認')

@section('content')
{{-- 開発用: サーバーがスマレジAPIから取得したPOSデータを表示。本番デプロイ時はこの画面を削除可 --}}
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.settings.smaregi') }}">スマレジ連携</a>
    <span class="breadcrumb-sep">/</span>
    <span>仮想POSデータ確認</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-database"></i> 仮想POSデータ確認（開発用）</h1>
    <p class="page-header-desc">サーバー側でスマレジAPIから取得したPOSデータを表示します。<a href="https://developers.smaregi.dev/platform-api-reference/apis/pos/" target="_blank" rel="noopener">POS仕様書</a>の一覧取得APIをすべて呼び出しています。</p>
    <p class="small text-muted mb-0">※ データはどこにも保存していません。表示はAPIの都度取得のみです。</p>
</div>

@if($view->hasError())
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> {{ $view->error }}
        @if(isset($view->envCheck) && $view->envCheck !== null)
        <hr class="my-2">
        <p class="mb-1 small"><strong>.env の読み込み状況:</strong> {{ $view->envCheck }}</p>
        <p class="mb-0 small">SMAREGI_CLIENT_ID / SMAREGI_CLIENT_SECRET は .env にのみ設定します（管理画面では設定できません）。プロジェクト直下の <code>.env</code> に記載し、変更後は <code>php artisan config:clear</code> を実行してから再読み込みしてください。</p>
        @endif
    </div>
    <p><a href="{{ admin_route('admin.settings.smaregi') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> スマレジ連携に戻る</a></p>
@else
    @if(session('success'))
        <div class="alert alert-success mb-4"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4"><i class="fas fa-exclamation-triangle"></i> {{ session('error') }}</div>
    @endif
    <div class="card mb-4">
        <div class="card-header bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>接続情報</span>
            <form action="{{ admin_route('admin.settings.smaregi.full-sync-products') }}" method="POST" class="d-inline" onsubmit="return confirm('DBとスマレジの商品を完全同期します。\n・スマレジにあってDBにない商品 → DBに登録\n・DBにあってスマレジにない商品 → スマレジに登録\nデータ漏れがないように両方を揃えます。よろしいですか？');">
                @csrf
                <input type="hidden" name="from" value="pos-data">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-exchange-alt"></i> 完全同期（双方向）</button>
            </form>
        </div>
        <div class="card-body">
            <p class="mb-0">契約ID: <code>{{ $view->contractId }}</code></p>
        </div>
    </div>

    @foreach($view->sections as $section)
    <div class="card mb-4">
        <div class="card-header">{{ $section['label'] }}（{{ $section['path'] }}）</div>
        <div class="card-body">
            @if(isset($section['error']) && $section['error'] !== null)
                <p class="text-warning mb-0">{{ $section['error'] }}</p>
            @elseif(count($section['items']) === 0)
                <p class="text-muted mb-0">データがありません。</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                @foreach(array_keys($section['items'][0] ?? []) as $key)
                                    <th>{{ $key }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section['items'] as $row)
                                <tr>
                                    @foreach($row as $val)
                                        <td>{{ is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0">表示: {{ count($section['items']) }} 件 / 総数: {{ $section['total'] }} 件</p>
            @endif
        </div>
    </div>
    @endforeach

    <p><a href="{{ admin_route('admin.settings.smaregi') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> スマレジ連携に戻る</a></p>
@endif
@endsection
